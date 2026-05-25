<?php
// api/proveedores.php
// CRUD de Proveedores y asignación por categoría + ubicación

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
    $tablasPermitidas = ['proveedor', 'categoria', 'ubicacion'];
    $camposPermitidos = ['idProveedor', 'idCategoria', 'idUbicacion'];

    if (!in_array($tabla, $tablasPermitidas, true) || !in_array($campo, $camposPermitidos, true)) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM $tabla WHERE $campo = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);

    return (int) $st->fetchColumn() > 0;
}

function obtenerAsignaciones(PDO $pdo, int $idProveedor): array
{
    $sql = "SELECT
                pcu.idProveedor,
                pcu.idCategoria,
                pcu.idUbicacion,
                c.nombre AS categoria,
                ub.departamento,
                ub.ciudad,
                ub.barrio,
                ub.direccionTexto
            FROM proveedor_categoria_ubicacion pcu
            INNER JOIN categoria c
                ON pcu.idCategoria = c.idCategoria
            INNER JOIN ubicacion ub
                ON pcu.idUbicacion = ub.idUbicacion
            WHERE pcu.idProveedor = ?
            ORDER BY c.nombre, ub.ciudad, ub.barrio";

    $st = $pdo->prepare($sql);
    $st->execute([$idProveedor]);

    return $st->fetchAll();
}

function guardarAsignaciones(PDO $pdo, int $idProveedor, array $asignaciones): void
{
    $sqlDelete = "DELETE FROM proveedor_categoria_ubicacion
                    WHERE idProveedor = ?";
    $stDelete = $pdo->prepare($sqlDelete);
    $stDelete->execute([$idProveedor]);

    if (empty($asignaciones)) {
        return;
    }

    $sqlInsert = "INSERT INTO proveedor_categoria_ubicacion
                    (idProveedor, idCategoria, idUbicacion)
                    VALUES (?, ?, ?)";

    $stInsert = $pdo->prepare($sqlInsert);
    $registradas = [];

    foreach ($asignaciones as $asignacion) {
        if (empty($asignacion['idCategoria']) || empty($asignacion['idUbicacion'])) {
            continue;
        }

        $idCategoria = (int) $asignacion['idCategoria'];
        $idUbicacion = (int) $asignacion['idUbicacion'];

        if (!existeRegistro($pdo, 'categoria', 'idCategoria', $idCategoria)) {
            responder(['error' => 'Una de las categorías asignadas no existe'], 400);
        }

        if (!existeRegistro($pdo, 'ubicacion', 'idUbicacion', $idUbicacion)) {
            responder(['error' => 'Una de las ubicaciones asignadas no existe'], 400);
        }

        $clave = $idCategoria . '-' . $idUbicacion;

        if (isset($registradas[$clave])) {
            continue;
        }

        $registradas[$clave] = true;

        $stInsert->execute([
            $idProveedor,
            $idCategoria,
            $idUbicacion
        ]);
    }
}

try {
    $pdo = getConnection();

    switch ($method) {

        // =====================================================
        // GET: listar proveedores o consultar uno por ID
        // =====================================================
        case 'GET':

            if ($id) {
                $sql = "SELECT
                            p.idProveedor,
                            p.nombreEntidad,
                            p.telefono,
                            p.correo,
                            p.nivel,
                            p.solucionesResueltas,
                            COUNT(DISTINCT t.idTicket) AS totalTickets
                        FROM proveedor p
                        LEFT JOIN ticket t
                            ON t.idProveedor = p.idProveedor
                        WHERE p.idProveedor = ?
                        GROUP BY
                            p.idProveedor,
                            p.nombreEntidad,
                            p.telefono,
                            p.correo,
                            p.nivel,
                            p.solucionesResueltas";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $proveedor = $st->fetch();

                if (!$proveedor) {
                    responder(['error' => 'Proveedor no encontrado'], 404);
                }

                $proveedor['asignaciones'] = obtenerAsignaciones($pdo, $id);

                responder($proveedor);
            }

            $sql = "SELECT
                        p.idProveedor,
                        p.nombreEntidad,
                        p.telefono,
                        p.correo,
                        p.nivel,
                        p.solucionesResueltas,
                        COUNT(DISTINCT pcu.idCategoria) AS totalCategorias,
                        COUNT(DISTINCT pcu.idUbicacion) AS totalUbicaciones,
                        COUNT(DISTINCT t.idTicket) AS totalTickets
                    FROM proveedor p
                    LEFT JOIN proveedor_categoria_ubicacion pcu
                        ON p.idProveedor = pcu.idProveedor
                    LEFT JOIN ticket t
                        ON t.idProveedor = p.idProveedor
                    GROUP BY
                        p.idProveedor,
                        p.nombreEntidad,
                        p.telefono,
                        p.correo,
                        p.nivel,
                        p.solucionesResueltas
                    ORDER BY p.nombreEntidad";

            $st = $pdo->query($sql);
            responder($st->fetchAll());

        // =====================================================
        // POST: crear proveedor
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['nombreEntidad'])) {
                responder(['error' => 'El nombre de la entidad es obligatorio'], 400);
            }

            $pdo->beginTransaction();

            $sql = "INSERT INTO proveedor
                        (nombreEntidad, telefono, correo, nivel, solucionesResueltas)
                    VALUES (?, ?, ?, ?, ?)";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['nombreEntidad']),
                $data['telefono'] ?? null,
                $data['correo'] ?? null,
                $data['nivel'] ?? null,
                isset($data['solucionesResueltas']) ? (int) $data['solucionesResueltas'] : 0
            ]);

            $idProveedor = (int) $pdo->lastInsertId();

            guardarAsignaciones($pdo, $idProveedor, $data['asignaciones'] ?? []);

            $pdo->commit();

            responder([
                'mensaje' => 'Proveedor creado correctamente',
                'idProveedor' => $idProveedor
            ], 201);

        // =====================================================
        // PUT: actualizar proveedor
        // =====================================================
        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'proveedor', 'idProveedor', $id)) {
                responder(['error' => 'Proveedor no encontrado'], 404);
            }

            $data = leerJson();

            if (empty($data['nombreEntidad'])) {
                responder(['error' => 'El nombre de la entidad es obligatorio'], 400);
            }

            $pdo->beginTransaction();

            $sql = "UPDATE proveedor
                    SET nombreEntidad = ?,
                        telefono = ?,
                        correo = ?,
                        nivel = ?,
                        solucionesResueltas = ?
                    WHERE idProveedor = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['nombreEntidad']),
                $data['telefono'] ?? null,
                $data['correo'] ?? null,
                $data['nivel'] ?? null,
                isset($data['solucionesResueltas']) ? (int) $data['solucionesResueltas'] : 0,
                $id
            ]);

            guardarAsignaciones($pdo, $id, $data['asignaciones'] ?? []);

            $pdo->commit();

            responder([
                'mensaje' => 'Proveedor actualizado correctamente',
                'idProveedor' => $id
            ]);

        // =====================================================
        // DELETE: eliminar proveedor
        // =====================================================
        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'proveedor', 'idProveedor', $id)) {
                responder(['error' => 'Proveedor no encontrado'], 404);
            }

            $pdo->beginTransaction();

            $sqlRelaciones = "DELETE FROM proveedor_categoria_ubicacion
                                WHERE idProveedor = ?";
            $stRelaciones = $pdo->prepare($sqlRelaciones);
            $stRelaciones->execute([$id]);

            $sqlTickets = "UPDATE ticket
                            SET idProveedor = NULL
                            WHERE idProveedor = ?";
            $stTickets = $pdo->prepare($sqlTickets);
            $stTickets->execute([$id]);

            $sqlProveedor = "DELETE FROM proveedor
                            WHERE idProveedor = ?";
            $stProveedor = $pdo->prepare($sqlProveedor);
            $stProveedor->execute([$id]);

            $pdo->commit();

            responder(['mensaje' => 'Proveedor eliminado correctamente']);

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