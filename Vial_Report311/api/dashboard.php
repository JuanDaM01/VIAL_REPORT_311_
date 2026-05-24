<?php
// api/dashboard.php
// Dashboard principal del sistema

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

function responder($data, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    $pdo = getConnection();

    // =====================================================
    // TOTALES GENERALES
    // =====================================================

    $totales = [];

    $tablas = [
        'usuarios'   => 'usuario',
        'reportes'   => 'reporte',
        'tickets'    => 'ticket',
        'proveedores'=> 'proveedor',
        'categorias' => 'categoria',
        'ubicaciones'=> 'ubicacion',
        'comentarios'=> 'comentario',
        'evidencias' => 'evidencia',
        'alertas'    => 'alerta_local'
    ];

    foreach ($tablas as $clave => $tabla) {
        $sql = "SELECT COUNT(*) FROM $tabla";
        $totales[$clave] = (int) $pdo->query($sql)->fetchColumn();
    }

    // =====================================================
    // REPORTES POR ESTADO
    // =====================================================

    $reportesPorEstado = $pdo->query(
        "SELECT estado, COUNT(*) AS total
         FROM reporte
         GROUP BY estado
         ORDER BY total DESC"
    )->fetchAll();

    // =====================================================
    // TICKETS POR PRIORIDAD
    // =====================================================

    $ticketsPorPrioridad = $pdo->query(
        "SELECT prioridad, COUNT(*) AS total
         FROM ticket
         GROUP BY prioridad
         ORDER BY total DESC"
    )->fetchAll();

    // =====================================================
    // REPORTES MÁS VOTADOS
    // =====================================================

    $reportesMasVotados = $pdo->query(
        "SELECT
             r.idReporte,
             r.titulo,
             r.estado,
             r.totalVotos,
             c.nombre AS categoria,
             CONCAT(ub.barrio, ', ', ub.ciudad) AS ubicacion
         FROM reporte r
         INNER JOIN categoria c  ON r.idCategoria = c.idCategoria
         INNER JOIN ubicacion ub ON r.idUbicacion  = ub.idUbicacion
         ORDER BY r.totalVotos DESC, r.fechaCreacion DESC
         LIMIT 5"
    )->fetchAll();

    // =====================================================
    // ÚLTIMOS REPORTES
    // =====================================================

    $ultimosReportes = $pdo->query(
        "SELECT
             r.idReporte,
             r.titulo,
             r.estado,
             r.fechaCreacion,
             c.nombre AS categoria,
             CONCAT(ub.barrio, ', ', ub.ciudad) AS ubicacion
         FROM reporte r
         INNER JOIN categoria c  ON r.idCategoria = c.idCategoria
         INNER JOIN ubicacion ub ON r.idUbicacion  = ub.idUbicacion
         ORDER BY r.fechaCreacion DESC
         LIMIT 5"
    )->fetchAll();

    // =====================================================
    // TOP PROVEEDORES
    // =====================================================

    $topProveedores = $pdo->query(
        "SELECT
             p.idProveedor,
             p.nombreEntidad,
             p.solucionesResueltas,
             COUNT(t.idTicket) AS ticketsAsignados
         FROM proveedor p
         LEFT JOIN ticket t ON p.idProveedor = t.idProveedor
         GROUP BY p.idProveedor, p.nombreEntidad, p.solucionesResueltas
         ORDER BY ticketsAsignados DESC
         LIMIT 5"
    )->fetchAll();

    responder([
        'totales'             => $totales,
        'reportesPorEstado'   => $reportesPorEstado,
        'ticketsPorPrioridad' => $ticketsPorPrioridad,
        'reportesMasVotados'  => $reportesMasVotados,
        'ultimosReportes'     => $ultimosReportes,
        'topProveedores'      => $topProveedores,
    ]);

} catch (PDOException $e) {
    responder([
        'error'   => 'Error al cargar dashboard',
        'detalle' => $e->getMessage()
    ], 500);
}