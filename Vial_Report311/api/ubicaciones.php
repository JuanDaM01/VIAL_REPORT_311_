<?php
// api/ubicaciones.php
// CRUD de Ubicaciones alineado con el modelo ER corregido

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

function existeUbicacion(PDO $pdo, int $idUbicacion): bool
{
    $sql = "SELECT COUNT(*) FROM ubicacion WHERE idUbicacion = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$idUbicacion]);

    return (int) $st->fetchColumn() > 0;
}

function ubicacionTieneReportes(PDO $pdo, int $idUbicacion): bool
{
    $sql = "SELECT COUNT(*) FROM reporte WHERE idUbicacion = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$idUbicacion]);

    return (int) $st->fetchColumn() > 0;
}

function normalizarDecimal($valor): ?float
{
    if ($valor === null || $valor === '') {
        return null;
    }

    return (float) $valor;
}

try {
    $pdo = getConnection();

    switch ($method) {

        // =====================================================
        // GET: listar ubicaciones o consultar una por ID
        // =====================================================
        case 'GET':

            if ($id) {
                $sql = "SELECT
                            u.idUbicacion,
                            u.departamento,
                            u.ciudad,
                            u.barrio,
                            u.direccionTexto,
                            u.latitud,
                            u.longitud,
                            COUNT(DISTINCT r.idReporte) AS totalReportes,
                            COUNT(DISTINCT pcu.idProveedor) AS totalProveedores,
                            COUNT(DISTINCT a.idAlerta) AS totalAlertas
                        FROM ubicacion u
                        LEFT JOIN reporte r
                            ON r.idUbicacion = u.idUbicacion
                        LEFT JOIN proveedor_categoria_ubicacion pcu
                            ON pcu.idUbicacion = u.idUbicacion
                        LEFT JOIN alerta_local a
                            ON a.idUbicacion = u.idUbicacion
                        WHERE u.idUbicacion = ?
                        GROUP BY
                            u.idUbicacion,
                            u.departamento,
                            u.ciudad,
                            u.barrio,
                            u.direccionTexto,
                            u.latitud,
                            u.longitud";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $ubicacion = $st->fetch();

                if (!$ubicacion) {
                    responder(['error' => 'Ubicación no encontrada'], 404);
                }

                responder($ubicacion);
            }

            $sql = "SELECT
                        u.idUbicacion,
                        u.departamento,
                        u.ciudad,
                        u.barrio,
                        u.direccionTexto,
                        u.latitud,
                        u.longitud,
                        COUNT(DISTINCT r.idReporte) AS totalReportes,
                        COUNT(DISTINCT pcu.idProveedor) AS totalProveedores,
                        COUNT(DISTINCT a.idAlerta) AS totalAlertas
                    FROM ubicacion u
                    LEFT JOIN reporte r
                        ON r.idUbicacion = u.idUbicacion
                    LEFT JOIN proveedor_categoria_ubicacion pcu
                        ON pcu.idUbicacion = u.idUbicacion
                    LEFT JOIN alerta_local a
                        ON a.idUbicacion = u.idUbicacion
                    GROUP BY
                        u.idUbicacion,
                        u.departamento,
                        u.ciudad,
                        u.barrio,
                        u.direccionTexto,
                        u.latitud,
                        u.longitud
                    ORDER BY u.ciudad, u.barrio, u.direccionTexto";

            $st = $pdo->query($sql);
            responder($st->fetchAll());

        // =====================================================
        // POST: crear ubicación
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['ciudad'])) {
                responder(['error' => 'La ciudad es obligatoria'], 400);
            }

            $sql = "INSERT INTO ubicacion
                        (departamento, ciudad, barrio, direccionTexto, latitud, longitud)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $st = $pdo->prepare($sql);
            $st->execute([
                $data['departamento'] ?? null,
                trim($data['ciudad']),
                $data['barrio'] ?? null,
                $data['direccionTexto'] ?? null,
                normalizarDecimal($data['latitud'] ?? null),
                normalizarDecimal($data['longitud'] ?? null)
            ]);

            responder([
                'mensaje' => 'Ubicación creada correctamente',
                'idUbicacion' => (int) $pdo->lastInsertId()
            ], 201);

        // =====================================================
        // PUT: actualizar ubicación
        // =====================================================
        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeUbicacion($pdo, $id)) {
                responder(['error' => 'Ubicación no encontrada'], 404);
            }

            $data = leerJson();

            if (empty($data['ciudad'])) {
                responder(['error' => 'La ciudad es obligatoria'], 400);
            }

            $sql = "UPDATE ubicacion
                    SET departamento = ?,
                        ciudad = ?,
                        barrio = ?,
                        direccionTexto = ?,
                        latitud = ?,
                        longitud = ?
                    WHERE idUbicacion = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                $data['departamento'] ?? null,
                trim($data['ciudad']),
                $data['barrio'] ?? null,
                $data['direccionTexto'] ?? null,
                normalizarDecimal($data['latitud'] ?? null),
                normalizarDecimal($data['longitud'] ?? null),
                $id
            ]);

            responder([
                'mensaje' => 'Ubicación actualizada correctamente',
                'idUbicacion' => $id
            ]);

        // =====================================================
        // DELETE: eliminar ubicación
        // =====================================================
        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeUbicacion($pdo, $id)) {
                responder(['error' => 'Ubicación no encontrada'], 404);
            }

            if (ubicacionTieneReportes($pdo, $id)) {
                responder([
                    'error' => 'No se puede eliminar la ubicación porque tiene reportes asociados'
                ], 409);
            }

            $pdo->beginTransaction();

            $sqlProveedor = "DELETE FROM proveedor_categoria_ubicacion
                             WHERE idUbicacion = ?";
            $stProveedor = $pdo->prepare($sqlProveedor);
            $stProveedor->execute([$id]);

            $sqlAlerta = "UPDATE alerta_local
                          SET idUbicacion = NULL
                          WHERE idUbicacion = ?";
            $stAlerta = $pdo->prepare($sqlAlerta);
            $stAlerta->execute([$id]);

            $sqlUbicacion = "DELETE FROM ubicacion
                             WHERE idUbicacion = ?";
            $stUbicacion = $pdo->prepare($sqlUbicacion);
            $stUbicacion->execute([$id]);

            $pdo->commit();

            responder(['mensaje' => 'Ubicación eliminada correctamente']);

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