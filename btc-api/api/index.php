<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require 'db.php';

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$endpoint = rtrim($request_uri, '/');

switch ($endpoint) {
    case '/btc':
        handleBTCRequests();
        break;
    case '/history':
        handleHistoryRequests();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

function handleBTCRequests() {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getCurrentBTCPrice();
            break;
        case 'POST':
            saveBTCPrice();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleHistoryRequests() {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getPriceHistory();
            break;
        case 'DELETE':
            clearHistory();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// GET /btc - Obtém o preço atual
function getCurrentBTCPrice() {
    try {
        $price = fetchBTCPriceFromAPI();
        echo json_encode([
            'status' => 'success',
            'data' => ['price' => $price],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// POST /btc - Salva um preço manualmente
function saveBTCPrice() {
    global $pdo;
    
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!isset($data['price'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Price is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO prices (price) VALUES (?)");
        $stmt->execute([$data['price']]);
        
        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'message' => 'Price saved',
            'id' => $pdo->lastInsertId()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// GET /history - Obtém histórico
function getPriceHistory() {
    global $pdo;
    
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $stmt = $pdo->prepare("SELECT id, timestamp, price FROM prices ORDER BY timestamp DESC LIMIT ?");
        $stmt->execute([$limit]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'count' => count($history),
            'data' => $history
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// DELETE /history - Limpa o histórico
function clearHistory() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("TRUNCATE TABLE prices");
        $stmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'History cleared'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Função auxiliar para buscar preço da Binance
function fetchBTCPriceFromAPI() {
    $api_url = 'https://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Binance API error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (!isset($data['price'])) {
        throw new Exception('Invalid response from Binance API');
    }
    
    return $data['price'];
}

?>