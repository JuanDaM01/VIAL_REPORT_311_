<?php
// api/usuarios.php
// CRUD completo de Usuario — PHP puro con PDO, sin frameworks

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {

    // ──────────────────────────────────────────────────────────
    //  READ — GET /api/usuarios.php         → listar todos
    //  READ — GET /api/usuarios.php?id=N    → uno por ID
    // ──────────────────────────────────────────────────────────
    case 'GET':
        if ($id) {
            $sql = "SELECT idUsuario, nombres, apellido_1, apellido_2, email,
                           telefono, edad, tipoRegistro, activo, cargo,
                           nivelAcceso, estadoCuenta, cantidadReportes, fecha_registro
                    FROM   usuario
                    WHERE  idUsuario = ?";
            $st = getConnection()->prepare($sql);
            $st->execute([$id]);
            $fila = $st->fetch();
            echo $fila ? json_encode($fila) : json_encode(['error' => 'No encontrado']);
        } else {
            $sql = "SELECT u.idUsuario, u.nombres, u.apellido_1, u.apellido_2,
                           u.email, u.telefono, u.edad, u.tipoRegistro,
                           u.activo, u.cantidadReportes, u.fecha_registro
                    FROM   usuario u
                    ORDER  BY u.idUsuario";
            $st = getConnection()->query($sql);
            echo json_encode($st->fetchAll());
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  CREATE — POST /api/usuarios.php
    // ──────────────────────────────────────────────────────────
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        // Validaciones
        if (empty($data['nombres']))   { http_response_code(400); echo json_encode(['error' => 'El nombre es obligatorio']); break; }
        if (empty($data['apellido_1'])){ http_response_code(400); echo json_encode(['error' => 'El primer apellido es obligatorio']); break; }
        if (empty($data['email']))     { http_response_code(400); echo json_encode(['error' => 'El email es obligatorio']); break; }
        if (empty($data['contrasena'])){ http_response_code(400); echo json_encode(['error' => 'La contraseña es obligatoria']); break; }

        $sql = "INSERT INTO usuario
                    (nombres, apellido_1, apellido_2, email, contrasena,
                     telefono, edad, tipoRegistro, activo, cargo, nivelAcceso)
                VALUES (?, ?, ?, ?, MD5(?), ?, ?, ?, ?, ?, ?)";
        $st = getConnection()->prepare($sql);
        $ok = $st->execute([
            $data['nombres'],
            $data['apellido_1'],
            $data['apellido_2']  ?? null,
            $data['email'],
            $data['contrasena'],
            $data['telefono']    ?? null,
            $data['edad']        ?? null,
            $data['tipoRegistro']?? 'local',
            isset($data['activo']) ? (int)$data['activo'] : 1,
            $data['cargo']       ?? null,
            $data['nivelAcceso'] ?? null,
        ]);
        echo json_encode($ok
            ? ['mensaje' => 'Usuario creado correctamente']
            : ['error'   => 'No se pudo crear el usuario']);
        break;

    // ──────────────────────────────────────────────────────────
    //  UPDATE — PUT /api/usuarios.php?id=N
    // ──────────────────────────────────────────────────────────
    case 'PUT':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
        $data = json_decode(file_get_contents('php://input'), true);

        $sql = "UPDATE usuario
                SET    nombres      = ?,
                       apellido_1   = ?,
                       apellido_2   = ?,
                       email        = ?,
                       telefono     = ?,
                       edad         = ?,
                       tipoRegistro = ?,
                       activo       = ?,
                       cargo        = ?,
                       nivelAcceso  = ?
                WHERE  idUsuario = ?";
        $st = getConnection()->prepare($sql);
        $ok = $st->execute([
            $data['nombres'],
            $data['apellido_1'],
            $data['apellido_2']  ?? null,
            $data['email'],
            $data['telefono']    ?? null,
            $data['edad']        ?? null,
            $data['tipoRegistro']?? 'local',
            isset($data['activo']) ? (int)$data['activo'] : 1,
            $data['cargo']       ?? null,
            $data['nivelAcceso'] ?? null,
            $id,
        ]);
        echo json_encode($ok
            ? ['mensaje' => 'Usuario actualizado correctamente']
            : ['error'   => 'No se pudo actualizar']);
        break;

    // ──────────────────────────────────────────────────────────
    //  DELETE — DELETE /api/usuarios.php?id=N
    // ──────────────────────────────────────────────────────────
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }
        $st = getConnection()->prepare("DELETE FROM usuario WHERE idUsuario = ?");
        $ok = $st->execute([$id]);
        echo json_encode($ok
            ? ['mensaje' => 'Usuario eliminado correctamente']
            : ['error'   => 'No se pudo eliminar']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
