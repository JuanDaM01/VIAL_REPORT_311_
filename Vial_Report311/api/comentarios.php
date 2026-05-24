<?php
// api/comentarios.php
// CRUD de Comentarios asociados a reportes

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
    $tablasPermitidas = ['comentario', 'reporte', 'usuario'];
    $camposPermitidos = ['idComentario', 'idReporte', 'idUsuario'];

    if (!in_array($tabla, $tablasPermitidas, true) || !in_array($campo, $camposPermitidos, true)) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM $tabla WHERE $campo = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);

    return (int) $st->fetchColumn() > 0;
}

function obtenerReporte(PDO $pdo, int $idReporte): ?array
{
    $sql = "SELECT idReporte, titulo, idUsuario
            FROM reporte
            WHERE idReporte = ?";

    $st = $pdo->prepare($sql);
    $st->execute([$idReporte]);
    $reporte = $st->fetch();

    return $reporte ?: null;
}

function crearNotificacionComentario(PDO $pdo, ?int $idUsuarioDestino, int $idReporte): void
{
    if ($idUsuarioDestino === null) {
        return;
    }

    $sql = "INSERT INTO notificacion
                (titulo, mensaje, tipo, leida, idUsuario, idReporte)
            VALUES (?, ?, 'comentario', 0, ?, ?)";

    $st = $pdo->prepare($sql);
    $st->execute([
        'Nuevo comentario',
        'Se agregó un nuevo comentario a uno de sus reportes.',
        $idUsuarioDestino,
        $idReporte
    ]);
}

try {
    $pdo = getConnection();

    switch ($method) {

        case 'GET':

            if ($id) {
                $sql = "SELECT
                            c.idComentario,
                            c.contenido,
                            c.fechaComentario,
                            c.idReporte,
                            c.idUsuario,
                            r.titulo AS reporte,
                            CONCAT(u.nombres, ' ', u.apellido_1) AS usuario,
                            u.rol
                        FROM comentario c
                        INNER JOIN reporte r
                            ON c.idReporte = r.idReporte
                        LEFT JOIN usuario u
                            ON c.idUsuario = u.idUsuario
                        WHERE c.idComentario = ?";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $comentario = $st->fetch();

                if (!$comentario) {
                    responder(['error' => 'Comentario no encontrado'], 404);
                }

                responder($comentario);
            }

            $where = [];
            $params = [];

            if (!empty($_GET['idReporte'])) {
                $where[] = "c.idReporte = ?";
                $params[] = (int) $_GET['idReporte'];
            }

            if (!empty($_GET['idUsuario'])) {
                $where[] = "c.idUsuario = ?";
                $params[] = (int) $_GET['idUsuario'];
            }

            $sql = "SELECT
                        c.idComentario,
                        c.contenido,
                        c.fechaComentario,
                        c.idReporte,
                        c.idUsuario,
                        r.titulo AS reporte,
                        CONCAT(u.nombres, ' ', u.apellido_1) AS usuario,
                        u.rol
                    FROM comentario c
                    INNER JOIN reporte r
                        ON c.idReporte = r.idReporte
                    LEFT JOIN usuario u
                        ON c.idUsuario = u.idUsuario";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY c.fechaComentario DESC";

            $st = $pdo->prepare($sql);
            $st->execute($params);

            responder($st->fetchAll());

        case 'POST':

            $data = leerJson();

            if (empty($data['contenido'])) {
                responder(['error' => 'El contenido del comentario es obligatorio'], 400);
            }

            if (empty($data['idReporte'])) {
                responder(['error' => 'Debe indicar el reporte asociado'], 400);
            }

            $idReporte = (int) $data['idReporte'];
            $reporte = obtenerReporte($pdo, $idReporte);

            if (!$reporte) {
                responder(['error' => 'El reporte indicado no existe'], 400);
            }

            $idUsuario = null;

            if (!empty($data['idUsuario'])) {
                $idUsuario = (int) $data['idUsuario'];

                if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                    responder(['error' => 'El usuario indicado no existe'], 400);
                }
            }

            $pdo->beginTransaction();

            $sql = "INSERT INTO comentario
                        (contenido, fechaComentario, idReporte, idUsuario)
                    VALUES (?, NOW(), ?, ?)";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['contenido']),
                $idReporte,
                $idUsuario
            ]);

            $idComentario = (int) $pdo->lastInsertId();

            $idUsuarioDestino = $reporte['idUsuario'] ? (int) $reporte['idUsuario'] : null;

            if ($idUsuarioDestino !== null && $idUsuarioDestino !== $idUsuario) {
                crearNotificacionComentario($pdo, $idUsuarioDestino, $idReporte);
            }

            $pdo->commit();

            responder([
                'mensaje' => 'Comentario creado correctamente',
                'idComentario' => $idComentario
            ], 201);

        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'comentario', 'idComentario', $id)) {
                responder(['error' => 'Comentario no encontrado'], 404);
            }

            $data = leerJson();

            if (empty($data['contenido'])) {
                responder(['error' => 'El contenido del comentario es obligatorio'], 400);
            }

            if (empty($data['idReporte'])) {
                responder(['error' => 'Debe indicar el reporte asociado'], 400);
            }

            $idReporte = (int) $data['idReporte'];

            if (!existeRegistro($pdo, 'reporte', 'idReporte', $idReporte)) {
                responder(['error' => 'El reporte indicado no existe'], 400);
            }

            $idUsuario = null;

            if (!empty($data['idUsuario'])) {
                $idUsuario = (int) $data['idUsuario'];

                if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                    responder(['error' => 'El usuario indicado no existe'], 400);
                }
            }

            $sql = "UPDATE comentario
                    SET contenido = ?,
                        idReporte = ?,
                        idUsuario = ?
                    WHERE idComentario = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['contenido']),
                $idReporte,
                $idUsuario,
                $id
            ]);

            responder([
                'mensaje' => 'Comentario actualizado correctamente',
                'idComentario' => $id
            ]);

        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'comentario', 'idComentario', $id)) {
                responder(['error' => 'Comentario no encontrado'], 404);
            }

            $sql = "DELETE FROM comentario WHERE idComentario = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);

            responder(['mensaje' => 'Comentario eliminado correctamente']);

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