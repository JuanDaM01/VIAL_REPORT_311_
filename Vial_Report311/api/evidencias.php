<?php
// api/evidencias.php
// CRUD de Evidencias asociadas a reportes

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
    $tablasPermitidas = ['evidencia', 'reporte'];
    $camposPermitidos = ['idEvidencia', 'idReporte'];

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

        case 'GET':

            if ($id) {
                $sql = "SELECT
                            e.idEvidencia,
                            e.urlArchivo,
                            e.tamanoKb,
                            e.contenido,
                            e.idReporte,
                            r.titulo AS reporte
                        FROM evidencia e
                        INNER JOIN reporte r
                            ON e.idReporte = r.idReporte
                        WHERE e.idEvidencia = ?";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $evidencia = $st->fetch();

                if (!$evidencia) {
                    responder(['error' => 'Evidencia no encontrada'], 404);
                }

                responder($evidencia);
            }

            $where = [];
            $params = [];

            if (!empty($_GET['idReporte'])) {
                $where[] = "e.idReporte = ?";
                $params[] = (int) $_GET['idReporte'];
            }

            $sql = "SELECT
                        e.idEvidencia,
                        e.urlArchivo,
                        e.tamanoKb,
                        e.contenido,
                        e.idReporte,
                        r.titulo AS reporte,
                        r.estado AS estadoReporte
                    FROM evidencia e
                    INNER JOIN reporte r
                        ON e.idReporte = r.idReporte";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY e.idEvidencia DESC";

            $st = $pdo->prepare($sql);
            $st->execute($params);

            responder($st->fetchAll());

        case 'POST':

            $data = leerJson();

            if (empty($data['urlArchivo'])) {
                responder(['error' => 'La URL o ruta del archivo es obligatoria'], 400);
            }

            if (empty($data['idReporte'])) {
                responder(['error' => 'Debe indicar el reporte asociado'], 400);
            }

            $idReporte = (int) $data['idReporte'];

            if (!existeRegistro($pdo, 'reporte', 'idReporte', $idReporte)) {
                responder(['error' => 'El reporte indicado no existe'], 400);
            }

            $sql = "INSERT INTO evidencia
                        (urlArchivo, tamanoKb, contenido, idReporte)
                    VALUES (?, ?, ?, ?)";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['urlArchivo']),
                $data['tamanoKb'] ?? null,
                $data['contenido'] ?? null,
                $idReporte
            ]);

            responder([
                'mensaje' => 'Evidencia creada correctamente',
                'idEvidencia' => (int) $pdo->lastInsertId()
            ], 201);

        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'evidencia', 'idEvidencia', $id)) {
                responder(['error' => 'Evidencia no encontrada'], 404);
            }

            $data = leerJson();

            if (empty($data['urlArchivo'])) {
                responder(['error' => 'La URL o ruta del archivo es obligatoria'], 400);
            }

            if (empty($data['idReporte'])) {
                responder(['error' => 'Debe indicar el reporte asociado'], 400);
            }

            $idReporte = (int) $data['idReporte'];

            if (!existeRegistro($pdo, 'reporte', 'idReporte', $idReporte)) {
                responder(['error' => 'El reporte indicado no existe'], 400);
            }

            $sql = "UPDATE evidencia
                    SET urlArchivo = ?,
                        tamanoKb = ?,
                        contenido = ?,
                        idReporte = ?
                    WHERE idEvidencia = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['urlArchivo']),
                $data['tamanoKb'] ?? null,
                $data['contenido'] ?? null,
                $idReporte,
                $id
            ]);

            responder([
                'mensaje' => 'Evidencia actualizada correctamente',
                'idEvidencia' => $id
            ]);

        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'evidencia', 'idEvidencia', $id)) {
                responder(['error' => 'Evidencia no encontrada'], 404);
            }

            $sql = "DELETE FROM evidencia WHERE idEvidencia = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);

            responder(['mensaje' => 'Evidencia eliminada correctamente']);

        default:
            responder(['error' => 'Método no permitido'], 405);
    }

} catch (PDOException $e) {
    responder([
        'error' => 'Error en la operación',
        'detalle' => $e->getMessage()
    ], 500);
}