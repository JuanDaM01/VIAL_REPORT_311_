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
    // Con el nuevo modelo un proveedor tiene una sola categoría (`idCategoria`).
    $sql = "SELECT p.idCategoria, c.nombre AS categoria
            FROM proveedor p
            LEFT JOIN categoria c ON p.idCategoria = c.idCategoria
            WHERE p.idProveedor = ?";

    $st = $pdo->prepare($sql);
    $st->execute([$idProveedor]);

    $fila = $st->fetch();

    if (!$fila || $fila['idCategoria'] === null) {
        return [];
    }

    return [[
        'idProveedor' => $idProveedor,
        'idCategoria' => (int) $fila['idCategoria'],
        'categoria' => $fila['categoria'] ?? null,
        'idUbicacion' => null
    ]];
}

function guardarAsignaciones(PDO $pdo, int $idProveedor, array $asignaciones): void
{
    // Ahora almacenamos una sola categoría en la tabla `proveedor` (campo `idCategoria`).
    if (empty($asignaciones)) {
        $sql = "UPDATE proveedor SET idCategoria = NULL WHERE idProveedor = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$idProveedor]);
        return;
    }

    // Tomamos la primera asignación válida que incluya `idCategoria`.
    $idCategoria = null;

    foreach ($asignaciones as $asignacion) {
        if (!empty($asignacion['idCategoria'])) {
            $idCategoria = (int) $asignacion['idCategoria'];
            break;
        }
    }

    if ($idCategoria === null) {
        $sql = "UPDATE proveedor SET idCategoria = NULL WHERE idProveedor = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$idProveedor]);
        return;
    }

    if (!existeRegistro($pdo, 'categoria', 'idCategoria', $idCategoria)) {
        responder(['error' => 'La categoría indicada no existe'], 400);
    }

    $sql = "UPDATE proveedor SET idCategoria = ? WHERE idProveedor = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$idCategoria, $idProveedor]);
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
                        (p.idCategoria IS NOT NULL) AS totalCategorias,
                        0 AS totalUbicaciones,
                        COUNT(DISTINCT t.idTicket) AS totalTickets
                    FROM proveedor p
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