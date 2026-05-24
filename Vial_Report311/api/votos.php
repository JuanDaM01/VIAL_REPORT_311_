<?php
// api/votos.php
// Gestión de votos entre usuarios y reportes
// Relación N:N: Usuario ── VOTA ── Reporte

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

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
    $tablasPermitidas = ['usuario', 'reporte'];
    $camposPermitidos = ['idUsuario', 'idReporte'];

    if (!in_array($tabla, $tablasPermitidas, true) || !in_array($campo, $camposPermitidos, true)) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM $tabla WHERE $campo = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);

    return (int) $st->fetchColumn() > 0;
}

function existeVoto(PDO $pdo, int $idUsuario, int $idReporte): bool
{
    $sql = "SELECT COUNT(*)
            FROM votar
            WHERE idUsuario = ?
              AND idReporte = ?";

    $st = $pdo->prepare($sql);
    $st->execute([$idUsuario, $idReporte]);

    return (int) $st->fetchColumn() > 0;
}

function actualizarContadorVotos(PDO $pdo, int $idReporte): void
{
    $sql = "UPDATE reporte
            SET voto = (
                SELECT COUNT(*)
                FROM votar
                WHERE votar.idReporte = reporte.idReporte
            )
            WHERE idReporte = ?";

    $st = $pdo->prepare($sql);
    $st->execute([$idReporte]);
}

try {
    $pdo = getConnection();

    switch ($method) {

        // =====================================================
        // GET: listar votos
        // Filtros:
        // api/votos.php?idUsuario=1
        // api/votos.php?idReporte=1
        // api/votos.php?idUsuario=1&idReporte=1
        // =====================================================
        case 'GET':

            $where = [];
            $params = [];

            if (!empty($_GET['idUsuario'])) {
                $where[] = "v.idUsuario = ?";
                $params[] = (int) $_GET['idUsuario'];
            }

            if (!empty($_GET['idReporte'])) {
                $where[] = "v.idReporte = ?";
                $params[] = (int) $_GET['idReporte'];
            }

            $sql = "SELECT
                        v.idUsuario,
                        v.idReporte,
                        v.fechaVoto,

                        CONCAT(u.nombres, ' ', u.apellido_1) AS usuario,
                        u.email,
                        u.rol,

                        r.titulo AS reporte,
                        r.estado AS estadoReporte,
                        r.voto AS totalVotosReporte,

                        c.nombre AS categoria,

                        CONCAT(ub.barrio, ', ', ub.ciudad) AS ubicacion
                    FROM votar v
                    INNER JOIN usuario u
                        ON v.idUsuario = u.idUsuario
                    INNER JOIN reporte r
                        ON v.idReporte = r.idReporte
                    INNER JOIN categoria c
                        ON r.idCategoria = c.idCategoria
                    INNER JOIN ubicacion ub
                        ON r.idUbicacion = ub.idUbicacion";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY v.fechaVoto DESC";

            $st = $pdo->prepare($sql);
            $st->execute($params);

            responder($st->fetchAll());

        // =====================================================
        // POST: registrar voto
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['idUsuario'])) {
                responder(['error' => 'Debe indicar el usuario que vota'], 400);
            }

            if (empty($data['idReporte'])) {
                responder(['error' => 'Debe indicar el reporte votado'], 400);
            }

            $idUsuario = (int) $data['idUsuario'];
            $idReporte = (int) $data['idReporte'];

            if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                responder(['error' => 'El usuario indicado no existe'], 400);
            }

            if (!existeRegistro($pdo, 'reporte', 'idReporte', $idReporte)) {
                responder(['error' => 'El reporte indicado no existe'], 400);
            }

            if (existeVoto($pdo, $idUsuario, $idReporte)) {
                responder(['error' => 'Este usuario ya votó por este reporte'], 409);
            }

            $pdo->beginTransaction();

            $sql = "INSERT INTO votar
                        (idUsuario, idReporte, fechaVoto)
                    VALUES (?, ?, NOW())";

            $st = $pdo->prepare($sql);
            $st->execute([
                $idUsuario,
                $idReporte
            ]);

            actualizarContadorVotos($pdo, $idReporte);

            $pdo->commit();

            responder([
                'mensaje' => 'Voto registrado correctamente',
                'idUsuario' => $idUsuario,
                'idReporte' => $idReporte
            ], 201);

        // =====================================================
        // DELETE: eliminar voto
        // Requiere:
        // api/votos.php?idUsuario=1&idReporte=1
        // =====================================================
        case 'DELETE':

            if (empty($_GET['idUsuario'])) {
                responder(['error' => 'Debe indicar idUsuario'], 400);
            }

            if (empty($_GET['idReporte'])) {
                responder(['error' => 'Debe indicar idReporte'], 400);
            }

            $idUsuario = (int) $_GET['idUsuario'];
            $idReporte = (int) $_GET['idReporte'];

            if (!existeVoto($pdo, $idUsuario, $idReporte)) {
                responder(['error' => 'El voto indicado no existe'], 404);
            }

            $pdo->beginTransaction();

            $sql = "DELETE FROM votar
                    WHERE idUsuario = ?
                      AND idReporte = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                $idUsuario,
                $idReporte
            ]);

            actualizarContadorVotos($pdo, $idReporte);

            $pdo->commit();

            responder([
                'mensaje' => 'Voto eliminado correctamente',
                'idUsuario' => $idUsuario,
                'idReporte' => $idReporte
            ]);

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