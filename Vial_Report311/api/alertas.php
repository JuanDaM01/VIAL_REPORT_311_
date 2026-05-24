<?php
// api/alertas.php
// CRUD de Alertas Locales asociadas a usuarios y ubicaciones

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

function responder($data, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function leerJson(): array
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        responder(['error' => 'JSON inválido o cuerpo vacío'], 400);
    }

    return $data;
}

function existeRegistro(PDO $pdo, string $tabla, string $campo, int $id): bool
{
    $tablasPermitidas = ['alerta_local', 'usuario', 'ubicacion'];
    $camposPermitidos = ['idAlerta', 'idUsuario', 'idUbicacion'];

    if (!in_array($tabla, $tablasPermitidas, true) || !in_array($campo, $camposPermitidos, true)) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM $tabla WHERE $campo = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);

    return (int) $st->fetchColumn() > 0;
}

function crearNotificacionAlerta(PDO $pdo, int $idUsuario, ?int $idUbicacion): void
{
    $mensaje = 'Se configuró una alerta local para una zona de interés.';

    if ($idUbicacion !== null) {
        $sqlUbicacion = "SELECT barrio, ciudad, direccionTexto
                         FROM ubicacion
                         WHERE idUbicacion = ?";

        $stUbicacion = $pdo->prepare($sqlUbicacion);
        $stUbicacion->execute([$idUbicacion]);
        $ubicacion = $stUbicacion->fetch();

        if ($ubicacion) {
            $zona = trim(
                ($ubicacion['barrio'] ?? '') . ' ' .
                ($ubicacion['direccionTexto'] ?? '') . ' ' .
                ($ubicacion['ciudad'] ?? '')
            );

            $mensaje = 'Se configuró una alerta local para la zona: ' . $zona . '.';
        }
    }

    $sql = "INSERT INTO notificacion
                (titulo, mensaje, tipo, leida, idUsuario, idReporte)
            VALUES (?, ?, 'alerta_local', 0, ?, NULL)";

    $st = $pdo->prepare($sql);
    $st->execute([
        'Alerta local configurada',
        $mensaje,
        $idUsuario
    ]);
}

try {
    $pdo = getConnection();

    switch ($method) {

        // =====================================================
        // GET: listar alertas o consultar una por ID
        // Filtros:
        // api/alertas.php?idUsuario=1
        // api/alertas.php?idUbicacion=2
        // =====================================================
        case 'GET':

            if ($id) {
                $sql = "SELECT
                            a.idAlerta,
                            a.frecuencia_alerta,
                            a.rango_km,
                            a.idUbicacion,
                            a.idUsuario,

                            CONCAT(u.nombres, ' ', u.apellido_1) AS usuario,
                            u.email,
                            u.rol,

                            ub.departamento,
                            ub.ciudad,
                            ub.barrio,
                            ub.direccionTexto,
                            ub.latitud,
                            ub.longitud
                        FROM alerta_local a
                        LEFT JOIN usuario u
                            ON a.idUsuario = u.idUsuario
                        LEFT JOIN ubicacion ub
                            ON a.idUbicacion = ub.idUbicacion
                        WHERE a.idAlerta = ?";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $alerta = $st->fetch();

                if (!$alerta) {
                    responder(['error' => 'Alerta local no encontrada'], 404);
                }

                responder($alerta);
            }

            $where = [];
            $params = [];

            if (!empty($_GET['idUsuario'])) {
                $where[] = "a.idUsuario = ?";
                $params[] = (int) $_GET['idUsuario'];
            }

            if (!empty($_GET['idUbicacion'])) {
                $where[] = "a.idUbicacion = ?";
                $params[] = (int) $_GET['idUbicacion'];
            }

            $sql = "SELECT
                        a.idAlerta,
                        a.frecuencia_alerta,
                        a.rango_km,
                        a.idUbicacion,
                        a.idUsuario,

                        CONCAT(u.nombres, ' ', u.apellido_1) AS usuario,
                        u.email,
                        u.rol,

                        ub.departamento,
                        ub.ciudad,
                        ub.barrio,
                        ub.direccionTexto,
                        ub.latitud,
                        ub.longitud
                    FROM alerta_local a
                    LEFT JOIN usuario u
                        ON a.idUsuario = u.idUsuario
                    LEFT JOIN ubicacion ub
                        ON a.idUbicacion = ub.idUbicacion";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY a.idAlerta DESC";

            $st = $pdo->prepare($sql);
            $st->execute($params);

            responder($st->fetchAll());

        // =====================================================
        // POST: crear alerta local
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['frecuencia_alerta'])) {
                responder(['error' => 'La frecuencia de la alerta es obligatoria'], 400);
            }

            if (empty($data['rango_km'])) {
                responder(['error' => 'El rango en kilómetros es obligatorio'], 400);
            }

            if (empty($data['idUsuario'])) {
                responder(['error' => 'Debe indicar el usuario asociado a la alerta'], 400);
            }

            if (empty($data['idUbicacion'])) {
                responder(['error' => 'Debe indicar la ubicación asociada a la alerta'], 400);
            }

            $idUsuario = (int) $data['idUsuario'];
            $idUbicacion = (int) $data['idUbicacion'];

            if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                responder(['error' => 'El usuario indicado no existe'], 400);
            }

            if (!existeRegistro($pdo, 'ubicacion', 'idUbicacion', $idUbicacion)) {
                responder(['error' => 'La ubicación indicada no existe'], 400);
            }

            $pdo->beginTransaction();

            $sql = "INSERT INTO alerta_local
                        (frecuencia_alerta, rango_km, idUbicacion, idUsuario)
                    VALUES (?, ?, ?, ?)";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['frecuencia_alerta']),
                (float) $data['rango_km'],
                $idUbicacion,
                $idUsuario
            ]);

            $idAlerta = (int) $pdo->lastInsertId();

            crearNotificacionAlerta($pdo, $idUsuario, $idUbicacion);

            $pdo->commit();

            responder([
                'mensaje' => 'Alerta local creada correctamente',
                'idAlerta' => $idAlerta
            ], 201);

        // =====================================================
        // PUT: actualizar alerta local
        // =====================================================
        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'alerta_local', 'idAlerta', $id)) {
                responder(['error' => 'Alerta local no encontrada'], 404);
            }

            $data = leerJson();

            if (empty($data['frecuencia_alerta'])) {
                responder(['error' => 'La frecuencia de la alerta es obligatoria'], 400);
            }

            if (empty($data['rango_km'])) {
                responder(['error' => 'El rango en kilómetros es obligatorio'], 400);
            }

            if (empty($data['idUsuario'])) {
                responder(['error' => 'Debe indicar el usuario asociado a la alerta'], 400);
            }

            if (empty($data['idUbicacion'])) {
                responder(['error' => 'Debe indicar la ubicación asociada a la alerta'], 400);
            }

            $idUsuario = (int) $data['idUsuario'];
            $idUbicacion = (int) $data['idUbicacion'];

            if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                responder(['error' => 'El usuario indicado no existe'], 400);
            }

            if (!existeRegistro($pdo, 'ubicacion', 'idUbicacion', $idUbicacion)) {
                responder(['error' => 'La ubicación indicada no existe'], 400);
            }

            $sql = "UPDATE alerta_local
                    SET frecuencia_alerta = ?,
                        rango_km = ?,
                        idUbicacion = ?,
                        idUsuario = ?
                    WHERE idAlerta = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['frecuencia_alerta']),
                (float) $data['rango_km'],
                $idUbicacion,
                $idUsuario,
                $id
            ]);

            responder([
                'mensaje' => 'Alerta local actualizada correctamente',
                'idAlerta' => $id
            ]);

        // =====================================================
        // DELETE: eliminar alerta local
        // =====================================================
        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'alerta_local', 'idAlerta', $id)) {
                responder(['error' => 'Alerta local no encontrada'], 404);
            }

            $sql = "DELETE FROM alerta_local WHERE idAlerta = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);

            responder(['mensaje' => 'Alerta local eliminada correctamente']);

        default:
            responder(['error' => 'Método no permitido'], 405);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    responder([
        'error' => 'Error en la operación',
        'detalle' => $e->getMessage()
    ], 500);
}