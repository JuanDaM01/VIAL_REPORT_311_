<?php
// api/categorias.php
// CRUD completo de Categoría — PHP puro con PDO, sin frameworks

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ──────────────────────────────────────────────────────────
    //  READ — GET /api/categorias.php         → listar todas
    //  READ — GET /api/categorias.php?id=N    → una por ID
    // ──────────────────────────────────────────────────────────
    case 'GET':
        if ($id) {
            $sql = "SELECT c.idCategoria, c.nombre, c.descripcion,
                           COUNT(r.idReporte) AS totalReportes
                    FROM   categoria c
                    LEFT JOIN reporte r ON r.categoria = c.nombre
                    WHERE  c.idCategoria = ?
                    GROUP  BY c.idCategoria";
            $st = getConnection()->prepare($sql);
            $st->execute([$id]);
            $fila = $st->fetch();
            echo $fila ? json_encode($fila) : json_encode(['error' => 'No encontrada']);
        } else {
            $sql = "SELECT c.idCategoria, c.nombre, c.descripcion,
                           COUNT(r.idReporte) AS totalReportes
                    FROM   categoria c
                    LEFT JOIN reporte r ON r.categoria = c.nombre
                    GROUP  BY c.idCategoria
                    ORDER  BY c.nombre";
            $st = getConnection()->query($sql);
            echo json_encode($st->fetchAll());
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  CREATE — POST /api/categorias.php
    // ──────────────────────────────────────────────────────────
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio']);
            break;
        }

        $sql = "INSERT INTO categoria (nombre, descripcion) VALUES (?, ?)";
        $st  = getConnection()->prepare($sql);
        try {
            $ok = $st->execute([
                strtolower(trim($data['nombre'])),
                $data['descripcion'] ?? null,
            ]);
            echo json_encode($ok
                ? ['mensaje' => 'Categoría creada correctamente']
                : ['error'   => 'No se pudo crear la categoría']);
        } catch (PDOException $e) {
            // Nombre duplicado (UNIQUE)
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe una categoría con ese nombre']);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  UPDATE — PUT /api/categorias.php?id=N
    // ──────────────────────────────────────────────────────────
    case 'PUT':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio']);
            break;
        }

        $sql = "UPDATE categoria
                SET    nombre      = ?,
                       descripcion = ?
                WHERE  idCategoria = ?";
        $st = getConnection()->prepare($sql);
        try {
            $ok = $st->execute([
                strtolower(trim($data['nombre'])),
                $data['descripcion'] ?? null,
                $id,
            ]);
            echo json_encode($ok
                ? ['mensaje' => 'Categoría actualizada correctamente']
                : ['error'   => 'No se pudo actualizar']);
        } catch (PDOException $e) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe una categoría con ese nombre']);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  DELETE — DELETE /api/categorias.php?id=N
    // ──────────────────────────────────────────────────────────
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
        $st = getConnection()->prepare("DELETE FROM categoria WHERE idCategoria = ?");
        $ok = $st->execute([$id]);
        echo json_encode($ok
            ? ['mensaje' => 'Categoría eliminada correctamente']
            : ['error'   => 'No se pudo eliminar']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
