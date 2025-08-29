<?php
// api/index.php

// Iniciar la sesión al principio de todo, ya que la usaremos para el login.
session_start();

require '../../src/database/db_connection.php';

// Determinar qué acción se solicita
$accion = $_GET['accion'] ?? '';

switch ($accion) {
    // ... (los 'case' de getPaises, getCuentas, etc. que ya teníamos se mantienen aquí) ...
    case 'getPaises':
        getPaises($conexion);
        break;
    case 'getCuentas':
        getCuentas($conexion);
        break;
    case 'getTasa':
        getTasa($conexion);
        break;
    case 'addCuenta':
        addCuenta($conexion);
        break;
    case 'createTransaccion':
        createTransaccion($conexion);
        break;

    // --- NUEVAS ACCIONES ---
    case 'registerUser':
        registerUser($conexion);
        break;
    case 'loginUser':
        loginUser($conexion);
        break;
    // ----------------------

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

$conexion->close();

// --- FUNCIONES EXISTENTES (getPaises, getCuentas, etc. se mantienen aquí) ---

// ...

// --- NUEVAS FUNCIONES PARA USUARIOS ---

function registerUser($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validación básica
    if (empty($data['email']) || empty($data['password']) || empty($data['primerNombre']) || empty($data['primerApellido'])) {
        echo json_encode(['success' => false, 'error' => 'Faltan campos obligatorios.']);
        return;
    }

    // 1. Verificar si el email ya existe
    $sql_check = "SELECT UserID FROM Usuarios WHERE Email = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("s", $data['email']);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está registrado.']);
        $stmt_check->close();
        return;
    }
    $stmt_check->close();

    // 2. Encriptar la contraseña (¡MUY IMPORTANTE!)
    $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

    // 3. Insertar el nuevo usuario
    $sql = "INSERT INTO Usuarios (PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, Email, PasswordHash, TipoDocumento, NumeroDocumento, VerificacionEstado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssssss", 
        $data['primerNombre'], $data['segundoNombre'], $data['primerApellido'], $data['segundoApellido'],
        $data['email'], $passwordHash, $data['tipoDocumento'], $data['numeroDocumento']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al registrar el usuario: ' . $stmt->error]);
    }
    $stmt->close();
}

function loginUser($conexion) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['email']) || empty($data['password'])) {
        echo json_encode(['success' => false, 'error' => 'Correo y contraseña son obligatorios.']);
        return;
    }

    // 1. Buscar al usuario por su email
    $sql = "SELECT UserID, PasswordHash, PrimerNombre FROM Usuarios WHERE Email = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // 2. Verificar la contraseña encriptada
        if (password_verify($data['password'], $usuario['PasswordHash'])) {
            // ¡Contraseña correcta! Iniciar sesión.
            $_SESSION['user_id'] = $usuario['UserID'];
            $_SESSION['user_name'] = $usuario['PrimerNombre'];
            
            echo json_encode([
                'success' => true,
                'redirect' => '/remesas/public/dashboard/' // Redirigir al panel de control
            ]);

        } else {
            // Contraseña incorrecta
            echo json_encode(['success' => false, 'error' => 'La contraseña es incorrecta.']);
        }
    } else {
        // Usuario no encontrado
        echo json_encode(['success' => false, 'error' => 'El correo electrónico no está registrado.']);
    }
    $stmt->close();
}