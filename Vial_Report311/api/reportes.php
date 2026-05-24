<?php
// api/reportes.php
// CRUD completo de Reporte — PHP puro con PDO, sin frameworks

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
    //  READ — GET /api/reportes.php         → listar todos
    //  READ — GET /api/reportes.php?id=N    → uno por ID
    // ──────────────────────────────────────────────────────────
    case 'GET':
        if ($id) {
            $sql = "SELECT r.idReporte, r.titulo, r.descripcion, r.estado,
                           r.esAnonimo, r.voto, r.categoria,
                           r.fechaCreacion, r.fechaActualizacion, r.fechaCierre,
                           r.idUsuario, r.idUbicacion
                    FROM   reporte r
                    WHERE  r.idReporte = ?";
            $st = getConnection()->prepare($sql);
            $st->execute([$id]);
            $fila = $st->fetch();
            echo $fila ? json_encode($fila) : json_encode(['error' => 'No encontrado']);
        } else {
            $sql = "SELECT r.idReporte, r.titulo, r.categoria, r.estado,
                           r.esAnonimo, r.voto, r.fechaCreacion,
                           CONCAT(u.nombres,' ',u.apellido_1) AS nombreUsuario,
                           CONCAT(ub.barrio,', ',ub.ciudad)    AS ubicacion
                    FROM   reporte r
                    LEFT JOIN usuario   u  ON r.idUsuario   = u.idUsuario
                    LEFT JOIN ubicacion ub ON r.idUbicacion = ub.idUbicacion
                    ORDER  BY r.fechaCreacion DESC";
            $st = getConnection()->query($sql);
            echo json_encode($st->fetchAll());
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  CREATE — POST /api/reportes.php
    // ──────────────────────────────────────────────────────────
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['titulo']))    { http_response_code(400); echo json_encode(['error' => 'El título es obligatorio']); break; }
        if (empty($data['categoria'])) { http_response_code(400); echo json_encode(['error' => 'La categoría es obligatoria']); break; }

        $sql = "INSERT INTO reporte
                    (titulo, descripcion, estado, esAnonimo, categoria, idUsuario, idUbicacion)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $st = getConnection()->prepare($sql);
        $ok = $st->execute([
            $data['titulo'],
            $data['descripcion'] ?? null,
            $data['estado']      ?? 'recibido',
            isset($data['esAnonimo']) ? (int)$data['esAnonimo'] : 0,
            $data['categoria'],
            !empty($data['idUsuario'])   ? (int)$data['idUsuario']   : null,
            !empty($data['idUbicacion']) ? (int)$data['idUbicacion'] : null,
        ]);
        echo json_encode($ok
            ? ['mensaje' => 'Reporte creado correctamente']
            : ['error'   => 'No se pudo crear el reporte']);
        break;

    // ──────────────────────────────────────────────────────────
    //  UPDATE — PUT /api/reportes.php?id=N
    // ──────────────────────────────────────────────────────────
    case 'PUT':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
        $data = json_decode(file_get_contents('php://input'), true);

        $sql = "UPDATE reporte
                SET    titulo              = ?,
                       descripcion         = ?,
                       estado              = ?,
                       esAnonimo           = ?,
                       categoria           = ?,
                       idUsuario           = ?,
                       idUbicacion         = ?,
                       fechaActualizacion  = NOW()
                WHERE  idReporte = ?";
        $st = getConnection()->prepare($sql);
        $ok = $st->execute([
            $data['titulo'],
            $data['descripcion'] ?? null,
            $data['estado']      ?? 'recibido',
            isset($data['esAnonimo']) ? (int)$data['esAnonimo'] : 0,
            $data['categoria'],
            !empty($data['idUsuario'])   ? (int)$data['idUsuario']   : null,
            !empty($data['idUbicacion']) ? (int)$data['idUbicacion'] : null,
            $id,
        ]);
        echo json_encode($ok
            ? ['mensaje' => 'Reporte actualizado correctamente']
            : ['error'   => 'No se pudo actualizar']);
        break;

    // ──────────────────────────────────────────────────────────
    //  DELETE — DELETE /api/reportes.php?id=N
    // ──────────────────────────────────────────────────────────
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
        $st = getConnection()->prepare("DELETE FROM reporte WHERE idReporte = ?");
        $ok = $st->execute([$id]);
        echo json_encode($ok
            ? ['mensaje' => 'Reporte eliminado correctamente']
            : ['error'   => 'No se pudo eliminar']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
