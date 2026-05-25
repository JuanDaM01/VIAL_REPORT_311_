<?php
// api/reportes.php
// CRUD de Reportes alineado con el diagrama ER corregido
// Crea Reporte + Ticket + Notificación + Evidencias

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
    $tablasPermitidas = ['usuario', 'ubicacion', 'categoria', 'proveedor', 'reporte'];
    $camposPermitidos = ['idUsuario', 'idUbicacion', 'idCategoria', 'idProveedor', 'idReporte'];

    if (!in_array($tabla, $tablasPermitidas, true) || !in_array($campo, $camposPermitidos, true)) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM $tabla WHERE $campo = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);

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

function obtenerNombreCategoria(PDO $pdo, int $idCategoria): string
{
    $sql = "SELECT nombre FROM categoria WHERE idCategoria = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$idCategoria]);
    $nombre = $st->fetchColumn();

    return $nombre ? strtolower($nombre) : 'otro';
}

function calcularPrioridad(PDO $pdo, int $idCategoria): string
{
    $categoria = obtenerNombreCategoria($pdo, $idCategoria);

    if (in_array($categoria, ['semaforo', 'hueco', 'alumbrado'], true)) {
        return 'alta';
    }

    if (in_array($categoria, ['anden', 'señalizacion'], true)) {
        return 'media';
    }

    return 'baja';
}

function generarNumeroCaso(PDO $pdo): string
{
    $sql = "SELECT COUNT(*) FROM ticket";
    $total = (int) $pdo->query($sql)->fetchColumn();

    return 'VR311-' . str_pad((string) ($total + 1), 6, '0', STR_PAD_LEFT);
}

function convertirEstadoTicket(string $estadoReporte): string
{
    return match ($estadoReporte) {
        'recibido' => 'abierto',
        'en_proceso' => 'en_proceso',
        'resuelto', 'rechazado' => 'cerrado',
        default => 'abierto'
    };
}

function guardarEvidencias(PDO $pdo, int $idReporte, array $data): void
{
    $evidencias = [];

    if (!empty($data['evidencias']) && is_array($data['evidencias'])) {
        $evidencias = $data['evidencias'];
    }

    if (!empty($data['urlArchivo'])) {
        $evidencias[] = [
            'urlArchivo' => $data['urlArchivo'],
            'tamanoKb' => $data['tamanoKb'] ?? null,
            'contenido' => $data['contenido'] ?? null
        ];
    }

    foreach ($evidencias as $evidencia) {
        if (empty($evidencia['urlArchivo'])) {
            continue;
        }

        $sql = "INSERT INTO evidencia
                    (urlArchivo, tamanoKb, contenido, idReporte)
                VALUES (?, ?, ?, ?)";

        $st = $pdo->prepare($sql);
        $st->execute([
            $evidencia['urlArchivo'],
            $evidencia['tamanoKb'] ?? null,
            $evidencia['contenido'] ?? null,
            $idReporte
        ]);
    }
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
        // GET: listar reportes o consultar uno por ID
        // =====================================================
        case 'GET':

            if ($id) {
                $sql = "SELECT
                            r.idReporte,
                            r.titulo,
                            r.descripcion,
                            r.estado,
                            r.esAnonimo,
                            (SELECT COUNT(*) FROM votar v WHERE v.idReporte = r.idReporte) AS totalVotos,
                            (SELECT COUNT(*) FROM votar v WHERE v.idReporte = r.idReporte) AS voto,
                            r.fechaCreacion,
                            r.fechaActualizacion,
                            r.fechaCierre,

                            r.idUsuario,
                            r.idUbicacion,
                            r.idCategoria,

                            c.nombre AS categoria,
                            c.descripcion AS descripcionCategoria,

                            CONCAT(u.nombres, ' ', u.apellido_1) AS nombreUsuario,

                            ub.departamento,
                            ub.ciudad,
                            ub.barrio,
                            ub.direccionTexto,
                            ub.latitud,
                            ub.longitud,

                            t.idTicket,
                            t.numeroCaso,
                            t.prioridad,
                            t.idFuncionario,
                            t.estado AS estadoTicket,
t.fechaAsignacion,
t.fechaResolucion,
t.idFuncionario,

                            p.idProveedor,
                            p.nombreEntidad AS proveedor
                        FROM reporte r
                        INNER JOIN categoria c
                            ON r.idCategoria = c.idCategoria
                        INNER JOIN ubicacion ub
                            ON r.idUbicacion = ub.idUbicacion
                        LEFT JOIN usuario u
                            ON r.idUsuario = u.idUsuario
                        LEFT JOIN ticket t
                            ON r.idReporte = t.idReporte
                        LEFT JOIN proveedor p
                            ON t.idProveedor = p.idProveedor
                        WHERE r.idReporte = ?";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $reporte = $st->fetch();

                if (!$reporte) {
                    responder(['error' => 'Reporte no encontrado'], 404);
                }

                $sqlEvidencias = "SELECT idEvidencia, urlArchivo, tamanoKb, contenido
                                  FROM evidencia
                                  WHERE idReporte = ?
                                  ORDER BY idEvidencia";

                $stEv = $pdo->prepare($sqlEvidencias);
                $stEv->execute([$id]);
                $reporte['evidencias'] = $stEv->fetchAll();

                $sqlComentarios = "SELECT
                                      co.idComentario,
                                      co.contenido,
                                      co.fechaComentario,
                                      co.idUsuario,
                                      CONCAT(u.nombres, ' ', u.apellido_1) AS usuario
                                   FROM comentario co
                                   LEFT JOIN usuario u
                                      ON co.idUsuario = u.idUsuario
                                   WHERE co.idReporte = ?
                                   ORDER BY co.fechaComentario DESC";

                $stCo = $pdo->prepare($sqlComentarios);
                $stCo->execute([$id]);
                $reporte['comentarios'] = $stCo->fetchAll();

                responder($reporte);
            }

            $sql = "SELECT
                        r.idReporte,
                        r.titulo,
                        r.descripcion,
                        r.estado,
                        r.esAnonimo,
                        (SELECT COUNT(*) FROM votar v WHERE v.idReporte = r.idReporte) AS totalVotos,
                        (SELECT COUNT(*) FROM votar v WHERE v.idReporte = r.idReporte) AS voto,
                        r.fechaCreacion,
                        r.fechaActualizacion,
                        r.fechaCierre,

                        r.idUsuario,
                        r.idUbicacion,
                        r.idCategoria,

                        c.nombre AS categoria,

                        CONCAT(u.nombres, ' ', u.apellido_1) AS nombreUsuario,

                        CONCAT(ub.barrio, ', ', ub.ciudad) AS ubicacion,
                        ub.barrio,
                        ub.ciudad,

                        t.idTicket,
                        t.numeroCaso,
                        t.prioridad,
                        t.estado AS estadoTicket,
                        t.fechaAsignacion,
                        t.fechaResolucion,
                        t.idFuncionario,

                        p.idProveedor,
                        p.nombreEntidad AS proveedor
                    FROM reporte r
                    INNER JOIN categoria c
                        ON r.idCategoria = c.idCategoria
                    INNER JOIN ubicacion ub
                        ON r.idUbicacion = ub.idUbicacion
                    LEFT JOIN usuario u
                        ON r.idUsuario = u.idUsuario
                    LEFT JOIN ticket t
                        ON r.idReporte = t.idReporte
                    LEFT JOIN proveedor p
                        ON t.idProveedor = p.idProveedor
                    ORDER BY r.fechaCreacion DESC";

            $st = $pdo->query($sql);
            responder($st->fetchAll());

        // =====================================================
        // POST: crear reporte + ticket + evidencia + notificación
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['titulo'])) {
                responder(['error' => 'El título del reporte es obligatorio'], 400);
            }

            if (empty($data['idCategoria'])) {
                responder(['error' => 'La categoría es obligatoria. Debe enviar idCategoria'], 400);
            }

            if (empty($data['idUbicacion'])) {
                responder(['error' => 'La ubicación es obligatoria. Debe enviar idUbicacion'], 400);
            }

            $idCategoria = (int) $data['idCategoria'];
            $idUbicacion = (int) $data['idUbicacion'];
            $esAnonimo = isset($data['esAnonimo']) ? (int) $data['esAnonimo'] : 0;

            if (!existeRegistro($pdo, 'categoria', 'idCategoria', $idCategoria)) {
                responder(['error' => 'La categoría indicada no existe'], 400);
            }

            if (!existeRegistro($pdo, 'ubicacion', 'idUbicacion', $idUbicacion)) {
                responder(['error' => 'La ubicación indicada no existe'], 400);
            }

            $idUsuario = null;

            if ($esAnonimo === 0) {
                if (empty($data['idUsuario'])) {
                    responder(['error' => 'Para un reporte no anónimo debe enviar idUsuario'], 400);
                }

                $idUsuario = (int) $data['idUsuario'];

                if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                    responder(['error' => 'El usuario indicado no existe'], 400);
                }
            }

            $pdo->beginTransaction();

            $sqlReporte = "INSERT INTO reporte
                            (titulo, descripcion, estado, esAnonimo,
                             idUsuario, idUbicacion, idCategoria)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stReporte = $pdo->prepare($sqlReporte);
            $stReporte->execute([
                trim($data['titulo']),
                $data['descripcion'] ?? null,
                $data['estado'] ?? 'recibido',
                $esAnonimo,
                $idUsuario,
                $idUbicacion,
                $idCategoria
            ]);

            $idReporte = (int) $pdo->lastInsertId();

            guardarEvidencias($pdo, $idReporte, $data);

            $idProveedor = !empty($data['idProveedor'])
                ? (int) $data['idProveedor']
                : buscarProveedorResponsable($pdo, $idCategoria, $idUbicacion);

            $idFuncionario = !empty($data['idFuncionario'])
                ? (int) $data['idFuncionario']
                : buscarFuncionarioDisponible($pdo);

            $prioridad = $data['prioridad'] ?? calcularPrioridad($pdo, $idCategoria);
            $estadoTicket = convertirEstadoTicket($data['estado'] ?? 'recibido');
            $numeroCaso = generarNumeroCaso($pdo);

            $sqlTicket = "INSERT INTO ticket
                            (numeroCaso, prioridad, estado, fechaAsignacion,
                             fechaResolucion, idReporte, idProveedor, idFuncionario)
                          VALUES (?, ?, ?, NOW(), NULL, ?, ?, ?)";

            $stTicket = $pdo->prepare($sqlTicket);
            $stTicket->execute([
                $numeroCaso,
                $prioridad,
                $estadoTicket,
                $idReporte,
                $idProveedor,
                $idFuncionario
            ]);

            $idTicket = (int) $pdo->lastInsertId();

            if ($idUsuario !== null) {
                $sqlCantidad = "UPDATE usuario
                                SET cantidadReportes = cantidadReportes + 1
                                WHERE idUsuario = ?";

                $stCantidad = $pdo->prepare($sqlCantidad);
                $stCantidad->execute([$idUsuario]);
            }

            crearNotificacion(
                $pdo,
                $idUsuario,
                $idReporte,
                'Reporte creado',
                'Su reporte fue registrado correctamente. Se generó el ticket ' . $numeroCaso . '.',
                'creacion_reporte'
            );

            $pdo->commit();

            responder([
                'mensaje' => 'Reporte creado correctamente',
                'idReporte' => $idReporte,
                'idTicket' => $idTicket,
                'numeroCaso' => $numeroCaso,
                'idProveedor' => $idProveedor,
                'idFuncionario' => $idFuncionario
            ], 201);

        // =====================================================
        // PUT: actualizar reporte y sincronizar ticket/notificación
        // =====================================================
        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            $data = leerJson();

            $sqlBuscar = "SELECT *
                          FROM reporte
                          WHERE idReporte = ?";

            $stBuscar = $pdo->prepare($sqlBuscar);
            $stBuscar->execute([$id]);
            $reporteActual = $stBuscar->fetch();

            if (!$reporteActual) {
                responder(['error' => 'Reporte no encontrado'], 404);
            }

            if (empty($data['titulo'])) {
                responder(['error' => 'El título del reporte es obligatorio'], 400);
            }

            if (empty($data['idCategoria'])) {
                responder(['error' => 'Debe enviar idCategoria'], 400);
            }

            if (empty($data['idUbicacion'])) {
                responder(['error' => 'Debe enviar idUbicacion'], 400);
            }

            $idCategoria = (int) $data['idCategoria'];
            $idUbicacion = (int) $data['idUbicacion'];
            $estadoNuevo = $data['estado'] ?? $reporteActual['estado'];
            $esAnonimo = isset($data['esAnonimo']) ? (int) $data['esAnonimo'] : (int) $reporteActual['esAnonimo'];

            if (!existeRegistro($pdo, 'categoria', 'idCategoria', $idCategoria)) {
                responder(['error' => 'La categoría indicada no existe'], 400);
            }

            if (!existeRegistro($pdo, 'ubicacion', 'idUbicacion', $idUbicacion)) {
                responder(['error' => 'La ubicación indicada no existe'], 400);
            }

            $idUsuario = null;

            if ($esAnonimo === 0) {
                // Si no viene idUsuario en el payload, conservar el propietario original
                if (!empty($data['idUsuario'])) {
                    $idUsuario = (int) $data['idUsuario'];

                    if (!existeRegistro($pdo, 'usuario', 'idUsuario', $idUsuario)) {
                        responder(['error' => 'El usuario indicado no existe'], 400);
                    }
                } else {
                    // Mantener el idUsuario que ya tiene el reporte en la BD
                    $idUsuario = $reporteActual['idUsuario'] ?? null;
                }
            }

            $fechaCierreSql = in_array($estadoNuevo, ['resuelto', 'rechazado'], true)
                ? "NOW()"
                : "NULL";

            $pdo->beginTransaction();

            $sqlActualizar = "UPDATE reporte
                              SET titulo = ?,
                                  descripcion = ?,
                                  estado = ?,
                                  esAnonimo = ?,
                                  idUsuario = ?,
                                  idUbicacion = ?,
                                  idCategoria = ?,
                                  fechaActualizacion = NOW(),
                                  fechaCierre = $fechaCierreSql
                              WHERE idReporte = ?";

            $stActualizar = $pdo->prepare($sqlActualizar);
            $stActualizar->execute([
                trim($data['titulo']),
                $data['descripcion'] ?? null,
                $estadoNuevo,
                $esAnonimo,
                $idUsuario,
                $idUbicacion,
                $idCategoria,
                $id
            ]);

            $idProveedor = !empty($data['idProveedor'])
                ? (int) $data['idProveedor']
                : buscarProveedorResponsable($pdo, $idCategoria, $idUbicacion);

            $estadoTicket = convertirEstadoTicket($estadoNuevo);

            $fechaResolucionSql = in_array($estadoNuevo, ['resuelto', 'rechazado'], true)
                ? "NOW()"
                : "NULL";

            $sqlTicket = "UPDATE ticket
                          SET estado = ?,
                              idProveedor = ?,
                              fechaResolucion = $fechaResolucionSql
                          WHERE idReporte = ?";

            $stTicket = $pdo->prepare($sqlTicket);
            $stTicket->execute([
                $estadoTicket,
                $idProveedor,
                $id
            ]);

            if ($reporteActual['estado'] !== $estadoNuevo) {
                crearNotificacion(
                    $pdo,
                    $idUsuario,
                    $id,
                    'Cambio de estado',
                    'El estado de su reporte cambió a: ' . str_replace('_', ' ', $estadoNuevo) . '.',
                    'cambio_estado'
                );
            }

            guardarEvidencias($pdo, $id, $data);

            $pdo->commit();

            responder([
                'mensaje' => 'Reporte actualizado correctamente',
                'idReporte' => $id,
                'estadoReporte' => $estadoNuevo,
                'estadoTicket' => $estadoTicket,
                'idProveedor' => $idProveedor
            ]);

        // =====================================================
        // DELETE: eliminar reporte
        // =====================================================
        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeRegistro($pdo, 'reporte', 'idReporte', $id)) {
                responder(['error' => 'Reporte no encontrado'], 404);
            }

            $sql = "DELETE FROM reporte WHERE idReporte = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);

            responder(['mensaje' => 'Reporte eliminado correctamente']);

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