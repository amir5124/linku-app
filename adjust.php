<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

error_reporting(E_ALL);
ini_set('display_errors', 0); 

// --- CONFIG ---
$apiKeyJagel = "c6wA9HlUkN2PYEpEOYmDwiehrw7QMIVAvPETMpR2NRN4jjnYPO";

$inputData = file_get_contents('php://input');
$data = json_decode($inputData, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["success" => false, "message" => "Metode aksi tidak ditentukan"]);
    exit;
}

$action = $data['action'];
$value = $data['value'] ?? '';

switch ($action) {
    case 'check_balance':
        $apiUrl = "https://api.jagel.id/v1/balance/check?type=username&value=" . urlencode($value) . "&apikey=" . $apiKeyJagel;
        $response = callJagelApi($apiUrl, null, 'GET');
        break;

    case 'adjust_balance':
        $apiUrl = "https://api.jagel.id/v1/balance/adjust";
        
        // MENGGUNAKAN ROUND UNTUK MENGHINDARI SELISIH DESIMAL
        // Kita gunakan floatval lalu dibulatkan ke integer terdekat
        $cleanAmount = round(floatval($data['amount'] ?? 0));
        
        $payload = [
            "type"   => "username",
            "value"  => $value,
            "amount" => $cleanAmount, 
            "note"   => $data['note'] ?? 'Hotel Booking',
            "apikey" => $apiKeyJagel
        ];
        $response = callJagelApi($apiUrl, $payload, 'POST');
        break;

    case 'send_message':
        $apiUrl = "https://api.jagel.id/v1/message/send";
        $payload = [
            "type"    => "username",
            "value"   => $value,
            "content" => $data['content'] ?? '',
            "apikey"  => $apiKeyJagel
        ];
        $response = callJagelApi($apiUrl, $payload, 'POST');
        break;

    default:
        echo json_encode(["success" => false, "message" => "Aksi tidak dikenal"]);
        exit;
}

if (!$response) {
    echo json_encode(["success" => false, "message" => "CURL Error: No Response"]);
    exit;
}

$resArray = json_decode($response, true);
if (isset($resArray['message']) && strpos($resArray['message'], "IP Ditolak") !== false) {
    $resArray['debug_vps_ip'] = $_SERVER['SERVER_ADDR'] ?? 'Unknown';
    echo json_encode($resArray);
} else {
    echo $response;
}

function callJagelApi($url, $data = null, $method = 'POST') {
    if (!function_exists('curl_init')) {
        return json_encode(["success" => false, "message" => "PHP CURL tidak aktif"]);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Solusi untuk error getaddrinfo() thread failed
    curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        $msg = curl_error($ch);
        curl_close($ch);
        return json_encode(["success" => false, "message" => "CURL Error: $msg"]);
    }
    curl_close($ch);
    return $result;
}