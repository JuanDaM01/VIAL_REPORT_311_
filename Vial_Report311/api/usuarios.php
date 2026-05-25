<?php
// api/usuarios.php
// CRUD de Usuario
// Maneja ciudadano, funcionario y administrador mediante el campo rol

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

function validarRol(string $rol): bool
{
    return in_array($rol, ['ciudadano', 'funcionario', 'administrador'], true);
}

function validarTipoRegistro(string $tipoRegistro): bool
{
    return in_array($tipoRegistro, ['local', 'google', 'facebook'], true);
}

function validarEstadoCuenta(?string $estadoCuenta): bool
{
    if ($estadoCuenta === null || $estadoCuenta === '') {
        return true;
    }

    return in_array($estadoCuenta, ['activo', 'inactivo', 'suspendido'], true);
}

function existeUsuario(PDO $pdo, int $idUsuario): bool
{
    $sql = "SELECT COUNT(*) FROM usuario WHERE idUsuario = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$idUsuario]);

    return (int) $st->fetchColumn() > 0;
}

function emailDuplicado(PDO $pdo, string $email, ?int $idUsuarioIgnorar = null): bool
{
    if ($idUsuarioIgnorar === null) {
        $sql = "SELECT COUNT(*) FROM usuario WHERE email = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$email]);
    } else {
        $sql = "SELECT COUNT(*) FROM usuario WHERE email = ? AND idUsuario <> ?";
        $st = $pdo->prepare($sql);
        $st->execute([$email, $idUsuarioIgnorar]);
    }

    return (int) $st->fetchColumn() > 0;
}

try {
    $pdo = getConnection();

    switch ($method) {

        // =====================================================
        // GET: listar usuarios o consultar uno por ID
        // Filtro opcional: api/usuarios.php?rol=ciudadano
        // =====================================================
        case 'GET':

            if ($id) {
                $sql = "SELECT
                            idUsuario,
                            nombres,
                            apellido_1,
                            apellido_2,
                            email,
                            telefono,
                            edad,
                            fecha_nacimiento_dia,
                            fecha_nacimiento_mes,
                            fecha_nacimiento_ano,
                            activo,
                            rol,
                            tipoRegistro,
                            cantidadReportes,
                            cargo,
                            nivelAcceso,
                            idProveedor,
                            estadoCuenta,
                            fechaAsignacionRol,
                            fechaRegistro
                        FROM usuario
                        WHERE idUsuario = ?";

                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $usuario = $st->fetch();

                if (!$usuario) {
                    responder(['error' => 'Usuario no encontrado'], 404);
                }

                responder($usuario);
            }

            $rol = $_GET['rol'] ?? null;

            if ($rol !== null && !validarRol($rol)) {
                responder(['error' => 'Rol no válido'], 400);
            }

            if ($rol !== null) {
                $sql = "SELECT
                            idUsuario,
                            nombres,
                            apellido_1,
                            apellido_2,
                            email,
                            telefono,
                            edad,
                            activo,
                            rol,
                            tipoRegistro,
                            cantidadReportes,
                            cargo,
                            nivelAcceso,
                            idProveedor,
                            estadoCuenta,
                            fechaAsignacionRol,
                            fechaRegistro
                        FROM usuario
                        WHERE rol = ?
                        ORDER BY idUsuario";

                $st = $pdo->prepare($sql);
                $st->execute([$rol]);
                responder($st->fetchAll());
            }

            $sql = "SELECT
                        idUsuario,
                        nombres,
                        apellido_1,
                        apellido_2,
                        email,
                        telefono,
                        edad,
                        activo,
                        rol,
                        tipoRegistro,
                        cantidadReportes,
                        cargo,
                        nivelAcceso,
                        idProveedor,
                        estadoCuenta,
                        fechaAsignacionRol,
                        fechaRegistro
                    FROM usuario
                    ORDER BY rol, idUsuario";

            $st = $pdo->query($sql);
            responder($st->fetchAll());

        // =====================================================
        // POST: crear usuario
        // =====================================================
        case 'POST':

            $data = leerJson();

            if (empty($data['nombres'])) {
                responder(['error' => 'El nombre es obligatorio'], 400);
            }

            if (empty($data['apellido_1'])) {
                responder(['error' => 'El primer apellido es obligatorio'], 400);
            }

            if (empty($data['email'])) {
                responder(['error' => 'El email es obligatorio'], 400);
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                responder(['error' => 'El email no tiene un formato válido'], 400);
            }

            if (empty($data['contrasena'])) {
                responder(['error' => 'La contraseña es obligatoria'], 400);
            }

            $rol = $data['rol'] ?? 'ciudadano';

            if (!validarRol($rol)) {
                responder(['error' => 'El rol indicado no es válido'], 400);
            }

            $tipoRegistro = $data['tipoRegistro'] ?? 'local';

            if (!validarTipoRegistro($tipoRegistro)) {
                responder(['error' => 'El tipo de registro no es válido'], 400);
            }

            $estadoCuenta = $data['estadoCuenta'] ?? null;

            if (!validarEstadoCuenta($estadoCuenta)) {
                responder(['error' => 'El estado de cuenta no es válido'], 400);
            }

            if (emailDuplicado($pdo, $data['email'])) {
                responder(['error' => 'Ya existe un usuario con ese email'], 400);
            }

            $cargo = null;
            $nivelAcceso = null;
            $idProveedor = null;
            $fechaAsignacionRol = null;

            if ($rol === 'funcionario') {
                $cargo = isset($data['cargo']) ? trim((string) $data['cargo']) : null;
                $cargo = $cargo !== '' ? $cargo : null;
                $nivelAcceso = $data['nivelAcceso'] ?? null;
                $idProveedor = !empty($data['idProveedor']) ? (int) $data['idProveedor'] : null;

                if ($cargo === null) {
                    responder(['error' => 'El cargo es obligatorio para funcionarios'], 400);
                }
            }

            if ($rol === 'administrador') {
                $estadoCuenta = $estadoCuenta ?: 'activo';
                $fechaAsignacionRol = date('Y-m-d H:i:s');
            }

            $sql = "INSERT INTO usuario
                    (
                        nombres,
                        apellido_1,
                        apellido_2,
                        email,
                        contrasena,
                        telefono,
                        edad,
                        fecha_nacimiento_dia,
                        fecha_nacimiento_mes,
                        fecha_nacimiento_ano,
                        activo,
                        rol,
                        tipoRegistro,
                        cantidadReportes,
                        cargo,
                        nivelAcceso,
                        idProveedor,
                        estadoCuenta,
                        fechaAsignacionRol
                    )
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $hash = password_hash($data['contrasena'], PASSWORD_DEFAULT);

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['nombres']),
                trim($data['apellido_1']),
                $data['apellido_2'] ?? null,
                trim($data['email']),
                $hash,
                $data['telefono'] ?? null,
                $data['edad'] ?? null,
                $data['fecha_nacimiento_dia'] ?? null,
                $data['fecha_nacimiento_mes'] ?? null,
                $data['fecha_nacimiento_ano'] ?? null,
                isset($data['activo']) ? (int) $data['activo'] : 1,
                $rol,
                $tipoRegistro,
                $data['cantidadReportes'] ?? 0,
                $cargo,
                $nivelAcceso,
                $idProveedor,
                $estadoCuenta,
                $fechaAsignacionRol
            ]);

            responder([
                'mensaje' => 'Usuario creado correctamente',
                'idUsuario' => (int) $pdo->lastInsertId()
            ], 201);

        // =====================================================
        // PUT: actualizar usuario
        // =====================================================
        case 'PUT':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeUsuario($pdo, $id)) {
                responder(['error' => 'Usuario no encontrado'], 404);
            }

            $data = leerJson();

            if (empty($data['nombres'])) {
                responder(['error' => 'El nombre es obligatorio'], 400);
            }

            if (empty($data['apellido_1'])) {
                responder(['error' => 'El primer apellido es obligatorio'], 400);
            }

            if (empty($data['email'])) {
                responder(['error' => 'El email es obligatorio'], 400);
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                responder(['error' => 'El email no tiene un formato válido'], 400);
            }

            $rol = $data['rol'] ?? 'ciudadano';

            if (!validarRol($rol)) {
                responder(['error' => 'El rol indicado no es válido'], 400);
            }

            $tipoRegistro = $data['tipoRegistro'] ?? 'local';

            if (!validarTipoRegistro($tipoRegistro)) {
                responder(['error' => 'El tipo de registro no es válido'], 400);
            }

            $estadoCuenta = $data['estadoCuenta'] ?? null;

            if (!validarEstadoCuenta($estadoCuenta)) {
                responder(['error' => 'El estado de cuenta no es válido'], 400);
            }

            if (emailDuplicado($pdo, $data['email'], $id)) {
                responder(['error' => 'Ya existe otro usuario con ese email'], 400);
            }

            $cargo = null;
            $nivelAcceso = null;
            $idProveedor = null;
            $fechaAsignacionRol = $data['fechaAsignacionRol'] ?? null;

            if ($rol === 'funcionario') {
                $cargo = isset($data['cargo']) ? trim((string) $data['cargo']) : null;
                $cargo = $cargo !== '' ? $cargo : null;
                $nivelAcceso = $data['nivelAcceso'] ?? null;
                $idProveedor = !empty($data['idProveedor']) ? (int) $data['idProveedor'] : null;

                if ($cargo === null) {
                    responder(['error' => 'El cargo es obligatorio para funcionarios'], 400);
                }
            }

            if ($rol === 'administrador') {
                $estadoCuenta = $estadoCuenta ?: 'activo';

                if (empty($fechaAsignacionRol)) {
                    $fechaAsignacionRol = date('Y-m-d H:i:s');
                }
            } else {
                $estadoCuenta = null;
                $fechaAsignacionRol = null;
            }

            $pdo->beginTransaction();

            $sql = "UPDATE usuario
                    SET nombres = ?,
                        apellido_1 = ?,
                        apellido_2 = ?,
                        email = ?,
                        telefono = ?,
                        edad = ?,
                        fecha_nacimiento_dia = ?,
                        fecha_nacimiento_mes = ?,
                        fecha_nacimiento_ano = ?,
                        activo = ?,
                        rol = ?,
                        tipoRegistro = ?,
                        cargo = ?,
                        nivelAcceso = ?,
                        idProveedor = ?,
                        estadoCuenta = ?,
                        fechaAsignacionRol = ?
                    WHERE idUsuario = ?";

            $st = $pdo->prepare($sql);
            $st->execute([
                trim($data['nombres']),
                trim($data['apellido_1']),
                $data['apellido_2'] ?? null,
                trim($data['email']),
                $data['telefono'] ?? null,
                $data['edad'] ?? null,
                $data['fecha_nacimiento_dia'] ?? null,
                $data['fecha_nacimiento_mes'] ?? null,
                $data['fecha_nacimiento_ano'] ?? null,
                isset($data['activo']) ? (int) $data['activo'] : 1,
                $rol,
                $tipoRegistro,
                $cargo,
                $nivelAcceso,
                $idProveedor,
                $estadoCuenta,
                $fechaAsignacionRol,
                $id
            ]);

            if (!empty($data['contrasena'])) {
                $hash = password_hash($data['contrasena'], PASSWORD_DEFAULT);

                $sqlPass = "UPDATE usuario
                            SET contrasena = ?
                            WHERE idUsuario = ?";

                $stPass = $pdo->prepare($sqlPass);
                $stPass->execute([$hash, $id]);
            }

            $pdo->commit();

            responder([
                'mensaje' => 'Usuario actualizado correctamente',
                'idUsuario' => $id
            ]);

        // =====================================================
        // DELETE: eliminar usuario
        // =====================================================
        case 'DELETE':

            if (!$id) {
                responder(['error' => 'ID requerido'], 400);
            }

            if (!existeUsuario($pdo, $id)) {
                responder(['error' => 'Usuario no encontrado'], 404);
            }

            $sql = "DELETE FROM usuario WHERE idUsuario = ?";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);

            responder(['mensaje' => 'Usuario eliminado correctamente']);

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