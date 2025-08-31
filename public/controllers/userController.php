<?php

class UserController {
    private $db; 

    public function __construct(mysqli $conexion) {
        $this->db = $conexion;
    }

    public function register() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Email y contraseña son obligatorios.']);
            return;
        }

        // 1. Verificar si el email ya existe
        $stmt_check = $this->db->prepare("SELECT UserID FROM Usuarios WHERE Email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está registrado.']);
            $stmt_check->close();
            return;
        }
        $stmt_check->close();
        
        // 2. Manejo de la subida del archivo de documento
        $docImagenURL = null;
        if (isset($_FILES['docImage']) && $_FILES['docImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/documents/'; // Relativo a la carpeta /public
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = uniqid() . '-' . basename($_FILES['docImage']['name']);
            $uploadFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['docImage']['tmp_name'], $uploadFile)) {
                $docImagenURL = 'uploads/documents/' . $fileName; 
            }
        }

        // 3. Encriptar la contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 4. Insertar el nuevo usuario
        $stmt = $this->db->prepare("INSERT INTO Usuarios (PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, Email, PasswordHash, TipoDocumento, NumeroDocumento, DocumentoImagenURL, VerificacionEstado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')");
        $stmt->bind_param("sssssssss", 
            $_POST['primerNombre'], $_POST['segundoNombre'], $_POST['primerApellido'], $_POST['segundoApellido'],
            $email, $passwordHash, $_POST['tipoDocumento'], $_POST['numeroDocumento'], $docImagenURL
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al registrar el usuario: ' . $stmt->error]);
        }
        $stmt->close();
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['password'])) {
            echo json_encode(['success' => false, 'error' => 'Correo y contraseña son obligatorios.']);
            return;
        }

        $stmt = $this->db->prepare("SELECT UserID, PasswordHash, PrimerNombre FROM Usuarios WHERE Email = ? AND VerificacionEstado = 'Aprobado'");
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            if (password_verify($data['password'], $usuario['PasswordHash'])) {
                $_SESSION['user_id'] = $usuario['UserID'];
                $_SESSION['user_name'] = $usuario['PrimerNombre'];
                echo json_encode(['success' => true, 'redirect' => '/remesas/public/dashboard/']);
            } else {
                echo json_encode(['success' => false, 'error' => 'La contraseña es incorrecta.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado o no verificado.']);
        }
        $stmt->close();
    }
    
}