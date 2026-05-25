<?php
// api/alertas.php
// CRUD AlertaLocal — ER: frecuencia_alerta, rango_km, zona propia, idUsuario
// DISPARA: AlertaLocal → Notificacion → Usuario

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$rolSesion = estaLogueado() ? rolActual() : '';
$idUsuarioSesion = estaLogueado() ? (int) ($_SESSION['usuario_id'] ?? 0) : 0;

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
    $tablasPermitidas = ['alerta_local', 'usuario'];
    $camposPermitidos = ['idAlerta', 'idUsuario'];

    if (!in_array($tabla, $tablasPermitidas, true) || !in_array($campo, $camposPermitidos, true)) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM $tabla WHERE $campo = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);

    return (int) $st->fetchColumn() > 0;
}

function validarFrecuencia(string $frecuencia): bool
{
    return in_array($frecuencia, ['inmediata', 'diaria', 'semanal'], true);
}

function normalizarDecimal($valor): ?float
{
    if ($valor === null || $valor === '') {
        return null;
    }

    return (float) $valor;
}

function alertaPerteneceAUsuario(PDO $pdo, int $idAlerta, int $idUsuario): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM alerta_local WHERE idAlerta = ? AND idUsuario = ?');
    $st->execute([$idAlerta, $idUsuario]);

    return (int) $st->fetchColumn() > 0;
}

function exigirAccesoAlerta(PDO $pdo, int $idAlerta): void
{
    if ($GLOBALS['rolSesion'] === 'ciudadano') {
        if (!$GLOBALS['idUsuarioSesion'] || !alertaPerteneceAUsuario($pdo, $idAlerta, $GLOBALS['idUsuarioSesion'])) {
            responder(['error' => 'No puede modificar alertas de otro usuario'], 403);
        }
    }
}

function extraerZona(array $data): array
{
    $zona = $data['zona'] ?? $data['ubicacion'] ?? $data;

    $ciudad = trim((string) ($zona['ciudad'] ?? ''));

    if ($ciudad === '') {
        responder(['error' => 'La ciudad de la zona de alerta es obligatoria'], 400);
    }

    return [
        'departamento' => !empty($zona['departamento']) ? trim((string) $zona['departamento']) : null,
        'ciudad' => $ciudad,
        'barrio' => !empty($zona['barrio']) ? trim((string) $zona['barrio']) : null,
    ];
}

function columnaExiste(PDO $pdo, string $tabla, string $columna): bool
{
    $st = $pdo->prepare(
        "SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?"
    );
    $st->execute([$tabla, $columna]);

    return (int) $st->fetchColumn() > 0;
}

function asegurarEsquemaAlerta(PDO $pdo): void
{
    if (columnaExiste($pdo, 'alerta_local', 'ciudad')) {
        return;
    }

    $alteraciones = [
        'ALTER TABLE alerta_local ADD COLUMN departamento VARCHAR(100) NULL',
        'ALTER TABLE alerta_local ADD COLUMN ciudad VARCHAR(100) NULL',
        'ALTER TABLE alerta_local ADD COLUMN barrio VARCHAR(100) NULL',
    ];

    foreach ($alteraciones as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
        }
    }

    if (columnaExiste($pdo, 'alerta_local', 'idUbicacion')) {
        $pdo->exec(
            'UPDATE alerta_local a
            INNER JOIN ubicacion u ON a.idUbicacion = u.idUbicacion
            SET a.departamento = u.departamento,
                a.ciudad = u.ciudad,
                a.barrio = u.barrio'
        );

        $stFk = $pdo->query(
            "SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'alerta_local'
                AND COLUMN_NAME = 'idUbicacion'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1"
        );
        $fk = $stFk->fetchColumn();

        if ($fk) {
            $pdo->exec('ALTER TABLE alerta_local DROP FOREIGN KEY `' . str_replace('`', '', $fk) . '`');
        }

        try {
            $pdo->exec('ALTER TABLE alerta_local DROP COLUMN idUbicacion');
        } catch (PDOException $e) {
        }
    }
}

function sqlSelectAlertas(): string
{
    return "SELECT
                a.idAlerta,
                a.frecuencia_alerta,
                a.rango_km,
                a.departamento,
                a.ciudad,
                a.barrio,
                a.idUsuario,
                CONCAT(u.nombres, ' ', u.apellido_1) AS usuario,
                u.email
            FROM alerta_local a
            LEFT JOIN usuario u ON a.idUsuario = u.idUsuario";
}

function crearNotificacionAlerta(PDO $pdo, int $idUsuario, int $idAlerta, array $zona, float $rangoKm): void
{
    $textoZona = trim(
        ($zona['barrio'] ?? '') . ' ' .
        ($zona['ciudad'] ?? '')
    );

    $mensaje = $textoZona !== ''
        ? 'Alerta activa en la zona: ' . $textoZona . ' (radio ' . $rangoKm . ' km).'
        : 'Se configuró una alerta local para una zona de interés.';

    $sql = "INSERT INTO notificacion
                (titulo, mensaje, tipo, leida, idUsuario, idReporte, idAlerta)
            VALUES (?, ?, 'alerta_local', 0, ?, NULL, ?)";

    $st = $pdo->prepare($sql);
    $st->execute([
        'Alerta local configurada',
        $mensaje,
        $idUsuario,
        $idAlerta
    ]);
}

try {
    $pdo = getConnection();
    asegurarEsquemaAlerta($pdo);

    switch ($method) {

        case 'GET':

            if ($id) {
                $sql = sqlSelectAlertas() . ' WHERE a.idAlerta = ?';
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $alerta = $st->fetch();

                if (!$alerta) {
                    responder(['error' => 'Alerta local no encontrada'], 404);
                }

                if ($rolSesion === 'ciudadano' && (int) $alerta['idUsuario'] !== $idUsuarioSesion) {
                    responder(['error' => 'No autorizado'], 403);
                }

                responder($alerta);
            }

            $where = [];
            $params = [];

            if ($rolSesion === 'ciudadano') {
                $where[] = 'a.idUsuario = ?';
                $params[] = $idUsuarioSesion;
            } elseif (!empty($_GET['idUsuario'])) {
                $where[] = 'a.idUsuario = ?';
                $params[] = (int) $_GET['idUsuario'];
            }

            $sql = sqlSelectAlertas();

            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY a.idAlerta DESC';

            $st = $pdo->prepare($sql);
            $st->execute($params);

            responder($st->fetchAll());

        case 'POST':

            $data = leerJson();
            $frecuencia = trim((string) ($data['frecuencia_alerta'] ?? ''));

            if ($frecuencia === '' || !validarFrecuencia($frecuencia)) {
                responder(['error' => 'Frecuencia inválida (inmediata, diaria o semanal)'], 400);
            }

            if (empty($data['rango_km'])) {
                responder(['error' => 'El rango en kilómetros es obligatorio'], 400);
            }

            $idUsuario = $rolSesion === 'ciudadano'
                ? $idUsuarioSesion
                : (int) ($data['idUsuario'] ?? 0);

            if ($idUsuario < 1 || !existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                responder(['error' => 'El usuario indicado no existe'], 400);
            }

            $zona = extraerZona($data);
            $rangoKm = (float) $data['rango_km'];

            $pdo->beginTransaction();

            $sql = "INSERT INTO alerta_local
                        (frecuencia_alerta, rango_km,
                        departamento, ciudad, barrio,
                        idUsuario)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $st = $pdo->prepare($sql);
            $st->execute([
                $frecuencia,
                (float) $data['rango_km'],
                $zona['departamento'],
                $zona['ciudad'],
                $zona['barrio'],
                $idUsuario
            ]);

            $idAlerta = (int) $pdo->lastInsertId();

            crearNotificacionAlerta($pdo, $idUsuario, $idAlerta, $zona, $rangoKm);

            $pdo->commit();

            responder([
                'mensaje' => 'Alerta local creada correctamente',
                'idAlerta' => $idAlerta
            ], 201);

        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'alerta_local', 'idAlerta', $id)) {
                responder(['error' => 'Alerta local no encontrada'], 404);
            }

            exigirAccesoAlerta($pdo, $id);

            $data = leerJson();
            $frecuencia = trim((string) ($data['frecuencia_alerta'] ?? ''));

            if ($frecuencia === '' || !validarFrecuencia($frecuencia)) {
                responder(['error' => 'Frecuencia inválida (inmediata, diaria o semanal)'], 400);
            }

            if (empty($data['rango_km'])) {
                responder(['error' => 'El rango en kilómetros es obligatorio'], 400);
            }

            $idUsuario = $rolSesion === 'ciudadano'
                ? $idUsuarioSesion
                : (int) ($data['idUsuario'] ?? 0);

            if ($idUsuario < 1 || !existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                responder(['error' => 'El usuario indicado no existe'], 400);
            }

            $zona = extraerZona($data);

            $sql = "UPDATE alerta_local
                    SET frecuencia_alerta = ?,
                        rango_km = ?,
                        departamento = ?,
                        ciudad = ?,
                        barrio = ?,
                        idUsuario = ?
                    WHERE idAlerta = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                $frecuencia,
                (float) $data['rango_km'],
                $zona['departamento'],
                $zona['ciudad'],
                $zona['barrio'],
                $idUsuario,
                $id
            ]);

            responder([
                'mensaje' => 'Alerta local actualizada correctamente',
                'idAlerta' => $id
            ]);

        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'alerta_local', 'idAlerta', $id)) {
                responder(['error' => 'Alerta local no encontrada'], 404);
            }

            exigirAccesoAlerta($pdo, $id);

            $st = $pdo->prepare('DELETE FROM alerta_local WHERE idAlerta = ?');
            $st->execute([$id]);

            responder(['mensaje' => 'Alerta local eliminada correctamente']);

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
