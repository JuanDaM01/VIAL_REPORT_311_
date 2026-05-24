<?php
// api/categorias.php
// CRUD de Categorías alineado con el modelo ER corregido

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

function existeCategoria(PDO $pdo, int $idCategoria): bool
{
    $sql = "SELECT COUNT(*) FROM categoria WHERE idCategoria = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$idCategoria]);

    return (int) $st->fetchColumn() > 0;
}

function categoriaTieneReportes(PDO $pdo, int $idCategoria): bool
{
    $sql = "SELECT COUNT(*) FROM reporte WHERE idCategoria = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$idCategoria]);

    return (int) $st->fetchColumn() > 0;
}

try {
    $pdo = getConnection();

    switch ($method) {

        // =====================================================
        // GET: listar categorías o consultar una por ID
        // =====================================================
        case 'GET':

            if ($id) {
                $sql = "SELECT
                            c.idCategoria,
                            c.nombre,
                            c.descripcion,
                            COUNT(r.idReporte) AS totalReportes
                        FROM categoria c
                        LEFT JOIN reporte r
                            ON r.idCategoria = c.idCategoria
                        WHERE c.idCategoria = ?
                        GROUP BY c.idCategoria, c.nombre, c.descripcion";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $categoria = $st->fetch();

                if (!$categoria) {
                    responder(['error' => 'Categoría no encontrada'], 404);
                }

                responder($categoria);
            }

            $sql = "SELECT
                        c.idCategoria,
                        c.nombre,
                        c.descripcion,
                        COUNT(r.idReporte) AS totalReportes
                    FROM categoria c
                    LEFT JOIN reporte r
                        ON r.idCategoria = c.idCategoria
                    GROUP BY c.idCategoria, c.nombre, c.descripcion
                    ORDER BY c.nombre";

            $st = $pdo->query($sql);
            responder($st->fetchAll());

        // =====================================================
        // POST: crear categoría
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['nombre'])) {
                responder(['error' => 'El nombre de la categoría es obligatorio'], 400);
            }

            $nombre = strtolower(trim($data['nombre']));

            $sql = "INSERT INTO categoria
                        (nombre, descripcion)
                    VALUES (?, ?)";

            try {
                $st = $pdo->prepare($sql);
                $st->execute([
                    $nombre,
                    $data['descripcion'] ?? null
                ]);

                responder([
                    'mensaje' => 'Categoría creada correctamente',
                    'idCategoria' => (int) $pdo->lastInsertId()
                ], 201);

            } catch (PDOException $e) {
                responder(['error' => 'Ya existe una categoría con ese nombre'], 409);
            }

        // =====================================================
        // PUT: actualizar categoría
        // =====================================================
        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeCategoria($pdo, $id)) {
                responder(['error' => 'Categoría no encontrada'], 404);
            }

            $data = leerJson();

            if (empty($data['nombre'])) {
                responder(['error' => 'El nombre de la categoría es obligatorio'], 400);
            }

            $nombre = strtolower(trim($data['nombre']));

            $sql = "UPDATE categoria
                    SET nombre = ?,
                        descripcion = ?
                    WHERE idCategoria = ?";

            try {
                $st = $pdo->prepare($sql);
                $st->execute([
                    $nombre,
                    $data['descripcion'] ?? null,
                    $id
                ]);

                responder([
                    'mensaje' => 'Categoría actualizada correctamente',
                    'idCategoria' => $id
                ]);

            } catch (PDOException $e) {
                responder(['error' => 'Ya existe una categoría con ese nombre'], 409);
            }

        // =====================================================
        // DELETE: eliminar categoría
        // =====================================================
        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeCategoria($pdo, $id)) {
                responder(['error' => 'Categoría no encontrada'], 404);
            }

            if (categoriaTieneReportes($pdo, $id)) {
                responder([
                    'error' => 'No se puede eliminar la categoría porque tiene reportes asociados'
                ], 409);
            }

            $sql = "DELETE FROM categoria WHERE idCategoria = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);

            responder(['mensaje' => 'Categoría eliminada correctamente']);

        default:
            responder(['error' => 'Método no permitido'], 405);
    }

} catch (PDOException $e) {
    responder([
        'error' => 'Error en la operación',
        'detalle' => $e->getMessage()
    ], 500);
}