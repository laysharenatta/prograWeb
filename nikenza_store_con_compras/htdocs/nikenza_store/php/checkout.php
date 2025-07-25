<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Debe iniciar sesión para realizar una compra']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Procesar compra
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos requeridos
        if (!$input || empty($input['nombre']) || empty($input['email']) || empty($input['telefono'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos requeridos (nombre, email, teléfono)']);
            exit();
        }
        
        // Verificar que hay productos en el carrito
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            http_response_code(400);
            echo json_encode(['error' => 'El carrito está vacío']);
            exit();
        }
        
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        try {
            // Obtener información de productos del carrito
            $cart = $_SESSION['cart'];
            $productIds = array_keys($cart);
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            
            $stmt = $pdo->prepare("SELECT id, name, price FROM productos WHERE id IN ($placeholders) AND active = 1");
            $stmt->execute($productIds);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($products) !== count($productIds)) {
                throw new Exception('Algunos productos del carrito ya no están disponibles');
            }
            
            // Calcular total
            $total = 0;
            $orderItems = [];
            
            foreach ($products as $product) {
                $quantity = $cart[$product['id']];
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                
                $orderItems[] = [
                    'product_id' => $product['id'],
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'subtotal' => $subtotal
                ];
            }
            
            // Crear el pedido
            $stmt = $pdo->prepare("
                INSERT INTO pedidos (usuario_id, total, nombre_cliente, email_cliente, telefono_cliente, direccion_entrega, notas, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $total,
                cleanInput($input['nombre']),
                cleanInput($input['email']),
                cleanInput($input['telefono']),
                cleanInput($input['direccion'] ?? ''),
                cleanInput($input['notas'] ?? '')
            ]);
            
            $pedidoId = $pdo->lastInsertId();
            
            // Insertar detalles del pedido
            $stmt = $pdo->prepare("
                INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($orderItems as $item) {
                $stmt->execute([
                    $pedidoId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['subtotal']
                ]);
            }
            
            // Confirmar transacción
            $pdo->commit();
            
            // Limpiar carrito
            $_SESSION['cart'] = [];
            
            // Obtener información completa del pedido
            $stmt = $pdo->prepare("
                SELECT p.*, u.username 
                FROM pedidos p 
                JOIN usuarios u ON p.usuario_id = u.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$pedidoId]);
            $pedido = $stmt->fetch();
            
            // Obtener detalles del pedido
            $stmt = $pdo->prepare("
                SELECT dp.*, pr.name as producto_nombre 
                FROM detalles_pedido dp 
                JOIN productos pr ON dp.producto_id = pr.id 
                WHERE dp.pedido_id = ?
            ");
            $stmt->execute([$pedidoId]);
            $detalles = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'message' => 'Compra realizada exitosamente',
                'pedido' => [
                    'id' => $pedido['id'],
                    'total' => $pedido['total'],
                    'estado' => $pedido['estado'],
                    'fecha_pedido' => $pedido['fecha_pedido'],
                    'nombre_cliente' => $pedido['nombre_cliente'],
                    'email_cliente' => $pedido['email_cliente'],
                    'telefono_cliente' => $pedido['telefono_cliente'],
                    'direccion_entrega' => $pedido['direccion_entrega'],
                    'notas' => $pedido['notas'],
                    'detalles' => $detalles
                ]
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } elseif ($method === 'GET') {
        // Obtener historial de pedidos del usuario
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT p.*, COUNT(dp.id) as total_items
            FROM pedidos p 
            LEFT JOIN detalles_pedido dp ON p.id = dp.pedido_id 
            WHERE p.usuario_id = ? 
            GROUP BY p.id 
            ORDER BY p.fecha_pedido DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $pedidos = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'pedidos' => $pedidos
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>

