<?php
// api/catalogos.php
// Catálogos auxiliares para formularios: categorías, ubicaciones, usuarios, funcionarios y proveedores

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

function responder($data, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function obtenerCategorias(PDO $pdo): array
{
    $sql = "SELECT idCategoria, nombre, descripcion
            FROM categoria
            ORDER BY nombre";

    return $pdo->query($sql)->fetchAll();
}

function obtenerUbicaciones(PDO $pdo): array
{
    $sql = "SELECT idUbicacion, departamento, ciudad, barrio, direccionTexto, latitud, longitud
            FROM ubicacion
            ORDER BY ciudad, barrio, direccionTexto";

    return $pdo->query($sql)->fetchAll();
}

function obtenerCiudadanos(PDO $pdo): array
{
    $sql = "SELECT idUsuario,
                    nombres,
                    apellido_1,
                    apellido_2,
                    email,
                    telefono
            FROM usuario
            WHERE rol = 'ciudadano'
                AND activo = 1
            ORDER BY nombres, apellido_1";

    return $pdo->query($sql)->fetchAll();
}

function obtenerFuncionarios(PDO $pdo): array
{
    $sql = "SELECT idUsuario,
                    nombres,
                    apellido_1,
                    apellido_2,
                    email,
                    cargo,
                    nivelAcceso
            FROM usuario
            WHERE rol = 'funcionario'
                AND activo = 1
            ORDER BY nombres, apellido_1";

    return $pdo->query($sql)->fetchAll();
}

function obtenerProveedores(PDO $pdo): array
{
    $sql = "SELECT idProveedor,
                    nombreEntidad,
                    telefono,
                    correo,
                    nivel,
                    solucionesResueltas
            FROM proveedor
            ORDER BY nombreEntidad";

    return $pdo->query($sql)->fetchAll();
}

try {
    $pdo = getConnection();

    $recurso = $_GET['recurso'] ?? 'todo';

    switch ($recurso) {
        case 'categorias':
            responder(obtenerCategorias($pdo));

        case 'ubicaciones':
            responder(obtenerUbicaciones($pdo));

        case 'ciudadanos':
            responder(obtenerCiudadanos($pdo));

        case 'funcionarios':
            responder(obtenerFuncionarios($pdo));

        case 'proveedores':
            responder(obtenerProveedores($pdo));

        case 'todo':
            responder([
                'categorias' => obtenerCategorias($pdo),
                'ubicaciones' => obtenerUbicaciones($pdo),
                'ciudadanos' => obtenerCiudadanos($pdo),
                'funcionarios' => obtenerFuncionarios($pdo),
                'proveedores' => obtenerProveedores($pdo)
            ]);

        default:
            responder(['error' => 'Recurso no válido'], 400);
    }

} catch (PDOException $e) {
    responder([
        'error' => 'Error al cargar catálogos',
        'detalle' => $e->getMessage()
    ], 500);
}