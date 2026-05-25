<?php
// api/tickets.php
// CRUD de Tickets
// Maneja número de caso, proveedor, funcionario y sincronización con reporte

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

function validarPrioridad(string $prioridad): bool
{
    return in_array($prioridad, ['baja', 'media', 'alta', 'critica'], true);
}

function validarEstadoTicket(string $estado): bool
{
    return in_array($estado, ['abierto', 'en_proceso', 'cerrado'], true);
}

function existeRegistro(PDO $pdo, string $tabla, string $campo, int $id): bool
{
    $tablasPermitidas = ['ticket', 'reporte', 'proveedor', 'usuario'];
    $camposPermitidos = ['idTicket', 'idReporte', 'idProveedor', 'idUsuario'];

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
    $sql = "SELECT idReporte, titulo, estado, idUsuario, idCategoria, idUbicacion
            FROM reporte
            WHERE idReporte = ?";

    $st = $pdo->prepare($sql);
    $st->execute([$idReporte]);
    $reporte = $st->fetch();

    return $reporte ?: null;
}

function reporteTieneTicket(PDO $pdo, int $idReporte, ?int $idTicketIgnorar = null): bool
{
    if ($idTicketIgnorar === null) {
        $sql = "SELECT COUNT(*) FROM ticket WHERE idReporte = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$idReporte]);
    } else {
        $sql = "SELECT COUNT(*) FROM ticket WHERE idReporte = ? AND idTicket <> ?";
        $st = $pdo->prepare($sql);
        $st->execute([$idReporte, $idTicketIgnorar]);
    }

    return (int) $st->fetchColumn() > 0;
}

function buscarProveedorResponsable(PDO $pdo, int $idCategoria, int $idUbicacion): ?int
{
    $sql = "SELECT idProveedor
            FROM proveedor_categoria_ubicacion
            WHERE idCategoria = ?
                AND idUbicacion = ?
            LIMIT 1";

    $st = $pdo->prepare($sql);
    $st->execute([$idCategoria, $idUbicacion]);
    $idProveedor = $st->fetchColumn();

    if ($idProveedor) {
        return (int) $idProveedor;
    }

    $sqlAlterno = "SELECT idProveedor
                    FROM proveedor_categoria_ubicacion
                    WHERE idCategoria = ?
                    LIMIT 1";

    $st = $pdo->prepare($sqlAlterno);
    $st->execute([$idCategoria]);
    $idProveedorAlterno = $st->fetchColumn();

    return $idProveedorAlterno ? (int) $idProveedorAlterno : null;
}

function buscarFuncionarioDisponible(PDO $pdo): ?int
{
    $sql = "SELECT idUsuario
            FROM usuario
            WHERE rol = 'funcionario'
                AND activo = 1
            ORDER BY idUsuario
            LIMIT 1";

    $st = $pdo->query($sql);
    $idFuncionario = $st->fetchColumn();

    return $idFuncionario ? (int) $idFuncionario : null;
}

function generarNumeroCaso(PDO $pdo): string
{
    $sql = "SELECT COUNT(*) FROM ticket";
    $total = (int) $pdo->query($sql)->fetchColumn();

    return 'VR311-' . str_pad((string) ($total + 1), 6, '0', STR_PAD_LEFT);
}

function convertirEstadoReporte(string $estadoTicket): string
{
    return match ($estadoTicket) {
        'abierto' => 'recibido',
        'en_proceso' => 'en_proceso',
        'cerrado' => 'resuelto',
        default => 'recibido'
    };
}

function crearNotificacion(
    PDO $pdo,
    ?int $idUsuario,
    int $idReporte,
    string $titulo,
    string $mensaje,
    string $tipo
): void {
    if ($idUsuario === null) {
        return;
    }

    $sql = "INSERT INTO notificacion
                (titulo, mensaje, tipo, leida, idUsuario, idReporte)
            VALUES (?, ?, ?, 0, ?, ?)";

    $st = $pdo->prepare($sql);
    $st->execute([
        $titulo,
        $mensaje,
        $tipo,
        $idUsuario,
        $idReporte
    ]);
}

try {
    $pdo = getConnection();

    switch ($method) {

        // =====================================================
        // GET: listar tickets o consultar uno por ID
        // =====================================================
        case 'GET':

            if ($id) {
                $sql = "SELECT
                            t.idTicket,
                            t.numeroCaso,
                            t.prioridad,
                            t.estado,
                            t.fechaAsignacion,
                            t.fechaResolucion,
                            t.idReporte,
                            t.idProveedor,
                            t.idFuncionario,

                            r.titulo AS tituloReporte,
                            r.estado AS estadoReporte,
                            r.idUsuario,

                            c.idCategoria,
                            c.nombre AS categoria,

                            p.nombreEntidad AS proveedor,

                            CONCAT(f.nombres, ' ', f.apellido_1) AS funcionario,
                            CONCAT(u.nombres, ' ', u.apellido_1) AS ciudadano,

                            ub.barrio,
                            ub.ciudad,
                            ub.direccionTexto
                        FROM ticket t
                        INNER JOIN reporte r
                            ON t.idReporte = r.idReporte
                        INNER JOIN categoria c
                            ON r.idCategoria = c.idCategoria
                        INNER JOIN ubicacion ub
                            ON r.idUbicacion = ub.idUbicacion
                        LEFT JOIN proveedor p
                            ON t.idProveedor = p.idProveedor
                        LEFT JOIN usuario f
                            ON t.idFuncionario = f.idUsuario
                        LEFT JOIN usuario u
                            ON r.idUsuario = u.idUsuario
                        WHERE t.idTicket = ?";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $ticket = $st->fetch();

                if (!$ticket) {
                    responder(['error' => 'Ticket no encontrado'], 404);
                }

                responder($ticket);
            }

            $sql = "SELECT
                        t.idTicket,
                        t.numeroCaso,
                        t.prioridad,
                        t.estado,
                        t.fechaAsignacion,
                        t.fechaResolucion,
                        t.idReporte,
                        t.idProveedor,
                        t.idFuncionario,

                        r.titulo AS tituloReporte,
                        r.estado AS estadoReporte,
                        r.esAnonimo,

                        c.nombre AS categoria,

                        p.nombreEntidad AS proveedor,

                        CONCAT(f.nombres, ' ', f.apellido_1) AS funcionario,
                        CONCAT(u.nombres, ' ', u.apellido_1) AS ciudadano,

                        CONCAT(ub.barrio, ', ', ub.ciudad) AS ubicacion
                    FROM ticket t
                    INNER JOIN reporte r
                        ON t.idReporte = r.idReporte
                    INNER JOIN categoria c
                        ON r.idCategoria = c.idCategoria
                    INNER JOIN ubicacion ub
                        ON r.idUbicacion = ub.idUbicacion
                    LEFT JOIN proveedor p
                        ON t.idProveedor = p.idProveedor
                    LEFT JOIN usuario f
                        ON t.idFuncionario = f.idUsuario
                    LEFT JOIN usuario u
                        ON r.idUsuario = u.idUsuario
                    ORDER BY t.fechaAsignacion DESC";

            $st = $pdo->query($sql);
            responder($st->fetchAll());

        // =====================================================
        // POST: crear ticket manualmente
        // Normalmente el ticket se crea desde api/reportes.php
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['idReporte'])) {
                responder(['error' => 'El reporte asociado es obligatorio'], 400);
            }

            $idReporte = (int) $data['idReporte'];
            $reporte = obtenerReporte($pdo, $idReporte);

            if (!$reporte) {
                responder(['error' => 'El reporte indicado no existe'], 400);
            }

            if (reporteTieneTicket($pdo, $idReporte)) {
                responder(['error' => 'Este reporte ya tiene un ticket asociado'], 400);
            }

            $prioridad = $data['prioridad'] ?? 'media';

            if (!validarPrioridad($prioridad)) {
                responder(['error' => 'Prioridad no válida'], 400);
            }

            $estado = $data['estado'] ?? 'abierto';

            if (!validarEstadoTicket($estado)) {
                responder(['error' => 'Estado de ticket no válido'], 400);
            }

            $idProveedor = !empty($data['idProveedor'])
                ? (int) $data['idProveedor']
                : buscarProveedorResponsable(
                    $pdo,
                    (int) $reporte['idCategoria'],
                    (int) $reporte['idUbicacion']
                );

            if ($idProveedor !== null && !existeRegistro($pdo, 'proveedor', 'idProveedor', $idProveedor)) {
                responder(['error' => 'El proveedor indicado no existe'], 400);
            }

            $idFuncionario = !empty($data['idFuncionario'])
                ? (int) $data['idFuncionario']
                : buscarFuncionarioDisponible($pdo);

            if ($idFuncionario !== null && !existeRegistro($pdo, 'usuario', 'idUsuario', $idFuncionario)) {
                responder(['error' => 'El funcionario indicado no existe'], 400);
            }

            $numeroCaso = !empty($data['numeroCaso'])
                ? trim($data['numeroCaso'])
                : generarNumeroCaso($pdo);

            $fechaResolucion = !empty($data['fechaResolucion'])
                ? $data['fechaResolucion']
                : (($estado === 'cerrado') ? date('Y-m-d H:i:s') : null);

            $estadoReporte = convertirEstadoReporte($estado);

            $pdo->beginTransaction();

            $sql = "INSERT INTO ticket
                        (numeroCaso, prioridad, estado, fechaAsignacion,
                        fechaResolucion, idReporte, idProveedor, idFuncionario)
                    VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)";

            $st = $pdo->prepare($sql);
            $st->execute([
                $numeroCaso,
                $prioridad,
                $estado,
                $fechaResolucion,
                $idReporte,
                $idProveedor,
                $idFuncionario
            ]);

            $idTicket = (int) $pdo->lastInsertId();

            $sqlReporte = "UPDATE reporte
                            SET estado = ?,
                                fechaActualizacion = NOW(),
                                fechaCierre = ?
                            WHERE idReporte = ?";

            $fechaCierre = $estado === 'cerrado'
                ? date('Y-m-d H:i:s')
                : null;

            $stReporte = $pdo->prepare($sqlReporte);
            $stReporte->execute([
                $estadoReporte,
                $fechaCierre,
                $idReporte
            ]);

            crearNotificacion(
                $pdo,
                $reporte['idUsuario'] ? (int) $reporte['idUsuario'] : null,
                $idReporte,
                'Ticket asignado',
                'Su reporte ya tiene el ticket ' . $numeroCaso . ' para seguimiento.',
                'ticket_asignado'
            );

            $pdo->commit();

            responder([
                'mensaje' => 'Ticket creado correctamente',
                'idTicket' => $idTicket,
                'numeroCaso' => $numeroCaso,
                'idProveedor' => $idProveedor,
                'idFuncionario' => $idFuncionario
            ], 201);

        // =====================================================
        // PUT: actualizar ticket y sincronizar reporte
        // =====================================================
        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'ticket', 'idTicket', $id)) {
                responder(['error' => 'Ticket no encontrado'], 404);
            }

            $data = leerJson();

            if (empty($data['idReporte'])) {
                responder(['error' => 'El reporte asociado es obligatorio'], 400);
            }

            $idReporte = (int) $data['idReporte'];
            $reporte = obtenerReporte($pdo, $idReporte);

            if (!$reporte) {
                responder(['error' => 'El reporte indicado no existe'], 400);
            }

            if (reporteTieneTicket($pdo, $idReporte, $id)) {
                responder(['error' => 'Este reporte ya tiene otro ticket asociado'], 400);
            }

            $prioridad = $data['prioridad'] ?? 'media';

            if (!validarPrioridad($prioridad)) {
                responder(['error' => 'Prioridad no válida'], 400);
            }

            $estado = $data['estado'] ?? 'abierto';

            if (!validarEstadoTicket($estado)) {
                responder(['error' => 'Estado de ticket no válido'], 400);
            }

            $idProveedor = !empty($data['idProveedor'])
                ? (int) $data['idProveedor']
                : buscarProveedorResponsable(
                    $pdo,
                    (int) $reporte['idCategoria'],
                    (int) $reporte['idUbicacion']
                );

            if ($idProveedor !== null && !existeRegistro($pdo, 'proveedor', 'idProveedor', $idProveedor)) {
                responder(['error' => 'El proveedor indicado no existe'], 400);
            }

            $idFuncionario = !empty($data['idFuncionario'])
                ? (int) $data['idFuncionario']
                : buscarFuncionarioDisponible($pdo);

            if ($idFuncionario !== null && !existeRegistro($pdo, 'usuario', 'idUsuario', $idFuncionario)) {
                responder(['error' => 'El funcionario indicado no existe'], 400);
            }

            $sqlTicketActual = "SELECT estado, numeroCaso
                                FROM ticket
                                WHERE idTicket = ?";

            $stActual = $pdo->prepare($sqlTicketActual);
            $stActual->execute([$id]);
            $ticketActual = $stActual->fetch();

            $numeroCaso = !empty($data['numeroCaso'])
                ? trim($data['numeroCaso'])
                : $ticketActual['numeroCaso'];

            $fechaResolucion = !empty($data['fechaResolucion'])
                ? $data['fechaResolucion']
                : (($estado === 'cerrado') ? date('Y-m-d H:i:s') : null);

            $estadoReporte = convertirEstadoReporte($estado);

            $pdo->beginTransaction();

            $sql = "UPDATE ticket
                    SET numeroCaso = ?,
                        prioridad = ?,
                        estado = ?,
                        fechaResolucion = ?,
                        idReporte = ?,
                        idProveedor = ?,
                        idFuncionario = ?
                    WHERE idTicket = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                $numeroCaso,
                $prioridad,
                $estado,
                $fechaResolucion,
                $idReporte,
                $idProveedor,
                $idFuncionario,
                $id
            ]);

            $fechaCierre = $estado === 'cerrado'
                ? date('Y-m-d H:i:s')
                : null;

            $sqlReporte = "UPDATE reporte
                            SET estado = ?,
                                fechaActualizacion = NOW(),
                                fechaCierre = ?
                            WHERE idReporte = ?";

            $stReporte = $pdo->prepare($sqlReporte);
            $stReporte->execute([
                $estadoReporte,
                $fechaCierre,
                $idReporte
            ]);

            if ($ticketActual['estado'] !== $estado) {
                crearNotificacion(
                    $pdo,
                    $reporte['idUsuario'] ? (int) $reporte['idUsuario'] : null,
                    $idReporte,
                    'Cambio de estado del ticket',
                    'El ticket ' . $numeroCaso . ' cambió a estado: ' . str_replace('_', ' ', $estado) . '.',
                    'cambio_estado'
                );
            }

            $pdo->commit();

            responder([
                'mensaje' => 'Ticket actualizado correctamente',
                'idTicket' => $id,
                'numeroCaso' => $numeroCaso,
                'estadoTicket' => $estado,
                'estadoReporte' => $estadoReporte
            ]);

        // =====================================================
        // DELETE: eliminar ticket
        // =====================================================
        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'ticket', 'idTicket', $id)) {
                responder(['error' => 'Ticket no encontrado'], 404);
            }

            $sql = "DELETE FROM ticket WHERE idTicket = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);

            responder(['mensaje' => 'Ticket eliminado correctamente']);

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