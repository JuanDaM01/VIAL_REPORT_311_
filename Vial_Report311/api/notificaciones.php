<?php
// api/notificaciones.php
// CRUD de Notificaciones

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

function validarTipoNotificacion(string $tipo): bool
{
    return in_array($tipo, [
        'creacion_reporte',
        'cambio_estado',
        'comentario',
        'ticket_asignado',
        'alerta_local'
    ], true);
}

function existeRegistro(PDO $pdo, string $tabla, string $campo, int $id): bool
{
    $tablasPermitidas = ['usuario', 'reporte', 'notificacion'];
    $camposPermitidos = ['idUsuario', 'idReporte', 'idNotificacion'];

    if (!in_array($tabla, $tablasPermitidas, true) || !in_array($campo, $camposPermitidos, true)) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM $tabla WHERE $campo = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);

    return (int) $st->fetchColumn() > 0;
}

try {
    $pdo = getConnection();

    switch ($method) {

        // =====================================================
        // GET: listar notificaciones o consultar una por ID
        // Filtros:
        // api/notificaciones.php?idUsuario=1
        // api/notificaciones.php?leida=0
        // =====================================================
        case 'GET':

            if ($id) {
                $sql = "SELECT
                            n.idNotificacion,
                            n.titulo,
                            n.mensaje,
                            n.tipo,
                            n.leida,
                            n.fechaCreacion,
                            n.idUsuario,
                            n.idReporte,
                            CONCAT(u.nombres, ' ', u.apellido_1) AS usuario,
                            r.titulo AS reporte
                        FROM notificacion n
                        LEFT JOIN usuario u
                            ON n.idUsuario = u.idUsuario
                        LEFT JOIN reporte r
                            ON n.idReporte = r.idReporte
                        WHERE n.idNotificacion = ?";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $notificacion = $st->fetch();

                if (!$notificacion) {
                    responder(['error' => 'Notificación no encontrada'], 404);
                }

                responder($notificacion);
            }

            $where = [];
            $params = [];

            if (isset($_GET['idUsuario']) && $_GET['idUsuario'] !== '') {
                $where[] = "n.idUsuario = ?";
                $params[] = (int) $_GET['idUsuario'];
            }

            if (isset($_GET['leida']) && $_GET['leida'] !== '') {
                $where[] = "n.leida = ?";
                $params[] = (int) $_GET['leida'];
            }

            $sql = "SELECT
                        n.idNotificacion,
                        n.titulo,
                        n.mensaje,
                        n.tipo,
                        n.leida,
                        n.fechaCreacion,
                        n.idUsuario,
                        n.idReporte,
                        CONCAT(u.nombres, ' ', u.apellido_1) AS usuario,
                        r.titulo AS reporte
                    FROM notificacion n
                    LEFT JOIN usuario u
                        ON n.idUsuario = u.idUsuario
                    LEFT JOIN reporte r
                        ON n.idReporte = r.idReporte";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY n.fechaCreacion DESC";

            $st = $pdo->prepare($sql);
            $st->execute($params);

            responder($st->fetchAll());

        // =====================================================
        // POST: crear notificación manual
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['titulo'])) {
                responder(['error' => 'El título es obligatorio'], 400);
            }

            if (empty($data['mensaje'])) {
                responder(['error' => 'El mensaje es obligatorio'], 400);
            }

            if (empty($data['tipo'])) {
                responder(['error' => 'El tipo de notificación es obligatorio'], 400);
            }

            if (!validarTipoNotificacion($data['tipo'])) {
                responder(['error' => 'El tipo de notificación no es válido'], 400);
            }

            if (empty($data['idUsuario'])) {
                responder(['error' => 'Debe indicar el usuario destinatario'], 400);
            }

            $idUsuario = (int) $data['idUsuario'];

            if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                responder(['error' => 'El usuario indicado no existe'], 400);
            }

            $idReporte = null;

            if (!empty($data['idReporte'])) {
                $idReporte = (int) $data['idReporte'];

                if (!existeRegistro($pdo, 'reporte', 'idReporte', $idReporte)) {
                    responder(['error' => 'El reporte indicado no existe'], 400);
                }
            }

            $sql = "INSERT INTO notificacion
                        (titulo, mensaje, tipo, leida, idUsuario, idReporte)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['titulo']),
                trim($data['mensaje']),
                $data['tipo'],
                isset($data['leida']) ? (int) $data['leida'] : 0,
                $idUsuario,
                $idReporte
            ]);

            responder([
                'mensaje' => 'Notificación creada correctamente',
                'idNotificacion' => (int) $pdo->lastInsertId()
            ], 201);

        // =====================================================
        // PUT: actualizar notificación o marcar como leída/no leída
        // =====================================================
        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'notificacion', 'idNotificacion', $id)) {
                responder(['error' => 'Notificación no encontrada'], 404);
            }

            $data = leerJson();

            if (isset($data['soloLectura'])) {
                $sql = "UPDATE notificacion
                        SET leida = ?
                        WHERE idNotificacion = ?";

                $st = $pdo->prepare($sql);
                $st->execute([
                    isset($data['leida']) ? (int) $data['leida'] : 1,
                    $id
                ]);

                responder([
                    'mensaje' => 'Estado de lectura actualizado correctamente',
                    'idNotificacion' => $id
                ]);
            }

            if (empty($data['titulo'])) {
                responder(['error' => 'El título es obligatorio'], 400);
            }

            if (empty($data['mensaje'])) {
                responder(['error' => 'El mensaje es obligatorio'], 400);
            }

            if (empty($data['tipo'])) {
                responder(['error' => 'El tipo es obligatorio'], 400);
            }

            if (!validarTipoNotificacion($data['tipo'])) {
                responder(['error' => 'El tipo de notificación no es válido'], 400);
            }

            if (empty($data['idUsuario'])) {
                responder(['error' => 'Debe indicar el usuario destinatario'], 400);
            }

            $idUsuario = (int) $data['idUsuario'];

            if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                responder(['error' => 'El usuario indicado no existe'], 400);
            }

            $idReporte = null;

            if (!empty($data['idReporte'])) {
                $idReporte = (int) $data['idReporte'];

                if (!existeRegistro($pdo, 'reporte', 'idReporte', $idReporte)) {
                    responder(['error' => 'El reporte indicado no existe'], 400);
                }
            }

            $sql = "UPDATE notificacion
                    SET titulo = ?,
                        mensaje = ?,
                        tipo = ?,
                        leida = ?,
                        idUsuario = ?,
                        idReporte = ?
                    WHERE idNotificacion = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['titulo']),
                trim($data['mensaje']),
                $data['tipo'],
                isset($data['leida']) ? (int) $data['leida'] : 0,
                $idUsuario,
                $idReporte,
                $id
            ]);

            responder([
                'mensaje' => 'Notificación actualizada correctamente',
                'idNotificacion' => $id
            ]);

        // =====================================================
        // DELETE: eliminar notificación
        // =====================================================
        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'notificacion', 'idNotificacion', $id)) {
                responder(['error' => 'Notificación no encontrada'], 404);
            }

            $sql = "DELETE FROM notificacion WHERE idNotificacion = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);

            responder(['mensaje' => 'Notificación eliminada correctamente']);

        default:
            responder(['error' => 'Método no permitido'], 405);
    }

} catch (PDOException $e) {
    responder([
        'error' => 'Error en la operación',
        'detalle' => $e->getMessage()
    ], 500);
}