<?php

require_once 'config.php';

$db = new pwclass();
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Extraer la acción del path
$pathParts = explode('/', trim($path, '/'));
$action = end($pathParts);

switch ($method) {
    case 'POST':
        if ($action === 'login') {
            handleLogin($db);
        } elseif ($action === 'register') {
            handleRegister($db);
        } elseif ($action === 'logout') {
            handleLogout();
        } else {
            sendResponse(['error' => 'Endpoint no encontrado'], 404);
        }
        break;
        
    case 'GET':
        if ($action === 'check-session') {
            handleCheckSession($db);
        } elseif ($action === 'me') {
            handleGetCurrentUser($db);
        } else {
            sendResponse(['error' => 'Endpoint no encontrado'], 404);
        }
        break;
        
    default:
        sendResponse(['error' => 'Método no permitido'], 405);
}

function handleLogin($db) {
    $data = getJsonInput();
    
    if (!$data || !isset($data['username']) || !isset($data['password'])) {
        sendResponse(['error' => 'Usuario y contraseña son requeridos'], 400);
    }
    
    $username = $data['username'];
    $password = $data['password'];
    
    try {
        // Buscar usuario por username o email
        $user = $db->getOne('users', 'username = ? OR email = ?', [$username, $username]);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            sendResponse(['error' => 'Credenciales inválidas'], 401);
        }
        
        if (!$user['is_active']) {
            sendResponse(['error' => 'Cuenta desactivada'], 401);
        }
        
        // Crear sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Remover password_hash de la respuesta
        unset($user['password_hash']);
        
        sendResponse([
            'message' => 'Inicio de sesión exitoso',
            'user' => $user
        ]);
        
    } catch (Exception $e) {
        sendResponse(['error' => 'Error interno del servidor'], 500);
    }
}

function handleRegister($db) {
    $data = getJsonInput();
    
    if (!$data || !isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
        sendResponse(['error' => 'Username, email y password son requeridos'], 400);
    }
    
    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];
    $role = isset($data['role']) ? $data['role'] : 'cliente';
    
    // Validar que el rol sea válido
    if (!in_array($role, ['cliente', 'admin'])) {
        sendResponse(['error' => 'Rol inválido'], 400);
    }
    
    try {
        // Verificar si el usuario ya existe
        if ($db->exists('users', 'username = ? OR email = ?', [$username, $email])) {
            sendResponse(['error' => 'Usuario o email ya existe'], 409);
        }
        
        // Crear nuevo usuario
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role
        ]);
        
        // Obtener el usuario creado
        $user = $db->getById('users', $userId);
        unset($user['password_hash']);
        
        sendResponse([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user
        ], 201);
        
    } catch (Exception $e) {
        sendResponse(['error' => 'Error interno del servidor'], 500);
    }
}

function handleLogout() {
    session_destroy();
    sendResponse(['message' => 'Sesión cerrada exitosamente']);
}

function handleCheckSession($db) {
    if (isset($_SESSION['user_id'])) {
        try {
            $user = $db->getById('users', $_SESSION['user_id']);
            if ($user && $user['is_active']) {
                unset($user['password_hash']);
                sendResponse([
                    'authenticated' => true,
                    'user' => $user
                ]);
            }
        } catch (Exception $e) {
            // Error al obtener usuario, limpiar sesión
            session_destroy();
        }
    }
    
    sendResponse(['authenticated' => false]);
}

function handleGetCurrentUser($db) {
    requireAuth();
    
    try {
        $user = $db->getById('users', $_SESSION['user_id']);
        if (!$user) {
            sendResponse(['error' => 'Usuario no encontrado'], 404);
        }
        
        unset($user['password_hash']);
        sendResponse($user);
        
    } catch (Exception $e) {
        sendResponse(['error' => 'Error interno del servidor'], 500);
    }
}

?>

