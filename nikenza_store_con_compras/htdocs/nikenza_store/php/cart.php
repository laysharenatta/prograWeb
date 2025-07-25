<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Debe iniciar sesión para usar el carrito']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener contenido del carrito
            $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
            $cartItems = [];
            $total = 0;
            
            if (!empty($cart)) {
                $pdo = getDBConnection();
                $productIds = array_keys($cart);
                $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                
                $stmt = $pdo->prepare("SELECT id, name, price, image_icon FROM productos WHERE id IN ($placeholders) AND active = 1");
                $stmt->execute($productIds);
                $products = $stmt->fetchAll();
                
                foreach ($products as $product) {
                    $quantity = $cart[$product['id']];
                    $subtotal = $product['price'] * $quantity;
                    $total += $subtotal;
                    
                    $cartItems[] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'image_icon' => $product['image_icon'],
                        'quantity' => $quantity,
                        'subtotal' => $subtotal
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'cart' => $cartItems,
                'total' => $total,
                'itemCount' => array_sum($cart)
            ]);
            break;
            
        case 'POST':
            // Agregar producto al carrito
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['product_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID del producto requerido']);
                exit();
            }
            
            $productId = intval($input['product_id']);
            $quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;
            
            if ($quantity <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'La cantidad debe ser mayor a 0']);
                exit();
            }
            
            // Verificar que el producto existe y está activo
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, name, price FROM productos WHERE id = ? AND active = 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Producto no encontrado o no disponible']);
                exit();
            }
            
            // Inicializar carrito si no existe
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Agregar o actualizar cantidad
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] += $quantity;
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'product' => $product,
                'quantity' => $_SESSION['cart'][$productId],
                'cartCount' => array_sum($_SESSION['cart'])
            ]);
            break;
            
        case 'PUT':
            // Actualizar cantidad de producto en carrito
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['product_id']) || !isset($input['quantity'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID del producto y cantidad requeridos']);
                exit();
            }
            
            $productId = intval($input['product_id']);
            $quantity = intval($input['quantity']);
            
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            if ($quantity <= 0) {
                // Eliminar producto del carrito
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Carrito actualizado',
                'cartCount' => array_sum($_SESSION['cart'])
            ]);
            break;
            
        case 'DELETE':
            // Eliminar producto del carrito o vaciar carrito completo
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            if ($input && isset($input['product_id'])) {
                // Eliminar producto específico
                $productId = intval($input['product_id']);
                unset($_SESSION['cart'][$productId]);
                $message = 'Producto eliminado del carrito';
            } else {
                // Vaciar carrito completo
                $_SESSION['cart'] = [];
                $message = 'Carrito vaciado';
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'cartCount' => array_sum($_SESSION['cart'])
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>

