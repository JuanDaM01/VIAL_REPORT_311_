<?php
// api/tickets.php
// CRUD completo de Ticket — PHP puro con PDO, sin frameworks

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
    //  READ — GET /api/tickets.php         → listar todos
    //  READ — GET /api/tickets.php?id=N    → uno por ID
    // ──────────────────────────────────────────────────────────
    case 'GET':
        if ($id) {
            $sql = "SELECT t.idTicket, t.prioridad, t.estado,
                           t.fechaAsignacion, t.fechaResolucion, t.idReporte,
                           r.titulo AS tituloReporte, r.categoria
                    FROM   ticket t
                    LEFT JOIN reporte r ON t.idReporte = r.idReporte
                    WHERE  t.idTicket = ?";
            $st = getConnection()->prepare($sql);
            $st->execute([$id]);
            $fila = $st->fetch();
            echo $fila ? json_encode($fila) : json_encode(['error' => 'No encontrado']);
        } else {
            $sql = "SELECT t.idTicket, t.prioridad, t.estado,
                           t.fechaAsignacion, t.fechaResolucion,
                           r.idReporte, r.titulo AS tituloReporte,
                           r.categoria, r.estado AS estadoReporte
                    FROM   ticket t
                    LEFT JOIN reporte r ON t.idReporte = r.idReporte
                    ORDER  BY t.fechaAsignacion DESC";
            $st = getConnection()->query($sql);
            echo json_encode($st->fetchAll());
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  CREATE — POST /api/tickets.php
    // ──────────────────────────────────────────────────────────
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['idReporte'])) {
            http_response_code(400);
            echo json_encode(['error' => 'El reporte asociado es obligatorio']);
            break;
        }
        if (empty($data['prioridad'])) {
            http_response_code(400);
            echo json_encode(['error' => 'La prioridad es obligatoria']);
            break;
        }

        $sql = "INSERT INTO ticket (prioridad, estado, idReporte, fechaResolucion)
                VALUES (?, ?, ?, ?)";
        $st = getConnection()->prepare($sql);
        $ok = $st->execute([
            $data['prioridad'],
            $data['estado']          ?? 'abierto',
            (int)$data['idReporte'],
            !empty($data['fechaResolucion']) ? $data['fechaResolucion'] : null,
        ]);
        echo json_encode($ok
            ? ['mensaje' => 'Ticket creado correctamente']
            : ['error'   => 'No se pudo crear el ticket']);
        break;

    // ──────────────────────────────────────────────────────────
    //  UPDATE — PUT /api/tickets.php?id=N
    // ──────────────────────────────────────────────────────────
    case 'PUT':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
        $data = json_decode(file_get_contents('php://input'), true);

        // Si el estado pasa a "cerrado", registrar fecha de resolución automáticamente
        $fechaResolucion = !empty($data['fechaResolucion'])
            ? $data['fechaResolucion']
            : (($data['estado'] === 'cerrado') ? date('Y-m-d H:i:s') : null);

        $sql = "UPDATE ticket
                SET    prioridad       = ?,
                       estado          = ?,
                       idReporte       = ?,
                       fechaResolucion = ?
                WHERE  idTicket = ?";
        $st = getConnection()->prepare($sql);
        $ok = $st->execute([
            $data['prioridad'],
            $data['estado']    ?? 'abierto',
            (int)$data['idReporte'],
            $fechaResolucion,
            $id,
        ]);
        echo json_encode($ok
            ? ['mensaje' => 'Ticket actualizado correctamente']
            : ['error'   => 'No se pudo actualizar']);
        break;

    // ──────────────────────────────────────────────────────────
    //  DELETE — DELETE /api/tickets.php?id=N
    // ──────────────────────────────────────────────────────────
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
        $st = getConnection()->prepare("DELETE FROM ticket WHERE idTicket = ?");
        $ok = $st->execute([$id]);
        echo json_encode($ok
            ? ['mensaje' => 'Ticket eliminado correctamente']
            : ['error'   => 'No se pudo eliminar']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
