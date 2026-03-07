<?php
// Set headers agar bisa diakses dari frontend (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Tangani Preflight request dari browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

// Aktifkan error reporting untuk debug (bisa dimatikan jika sudah produksi)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set ke 0 agar tidak merusak format JSON jika ada warning

// --- CONFIG ---
$apiKeyJagel = "c6wA9HlUkN2PYEpEOYmDwiehrw7QMIVAvPETMpR2NRN4jjnYPO";

// Ambil input JSON dari body request
$inputData = file_get_contents('php://input');
$data = json_decode($inputData, true);

// Validasi input awal
if (!$data || !isset($data['action'])) {
    echo json_encode([
        "success" => false, 
        "message" => "Metode aksi (action) tidak ditentukan",
        "received" => $inputData // Debugging: melihat apa yang diterima server
    ]);
    exit;
}

$action = $data['action'];
$value = $data['value'] ?? ''; // Biasanya username atau nomor HP

// --- LOGIKA ROUTING ---
switch ($action) {
    case 'check_balance':
        $apiUrl = "https://api.jagel.id/v1/balance/check?type=username&value=" . urlencode($value) . "&apikey=" . $apiKeyJagel;
        $response = callJagelApi($apiUrl, null, 'GET');
        break;

    case 'adjust_balance':
        $apiUrl = "https://api.jagel.id/v1/balance/adjust";
        $payload = [
            "type"   => "username",
            "value"  => $value,
            "amount" => (int)($data['amount'] ?? 0),
            "note"   => $data['note'] ?? 'Adjustment via VPS',
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
        echo json_encode(["success" => false, "message" => "Aksi '$action' tidak dikenal"]);
        exit;
}

// Tambahkan proteksi jika $response kosong (misal CURL gagal total)
if (!$response) {
    echo json_encode(["success" => false, "message" => "Gagal mendapatkan respon dari API Jagel (CURL Error)"]);
    exit;
}

// Decode response dari Jagel untuk pengecekan tambahan
$resArray = json_decode($response, true);

// Tambahkan Debug IP jika IP ditolak oleh Jagel
if (isset($resArray['message']) && strpos($resArray['message'], "IP Ditolak") !== false) {
    $resArray['debug_vps_ip'] = $_SERVER['SERVER_ADDR'] ?? 'IP tidak terdeteksi';
    echo json_encode($resArray);
} else {
    // Kembalikan response asli dari Jagel
    echo $response;
}

/**
 * Fungsi pembantu untuk memanggil API Jagel via cURL
 */
function callJagelApi($url, $data = null, $method = 'POST') {
    // Cek apakah ekstensi CURL tersedia di VPS
    if (!function_exists('curl_init')) {
        return json_encode(["success" => false, "message" => "PHP CURL tidak aktif di server ini"]);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Beri batas waktu 30 detik
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $result = curl_exec($ch);

    // Tangkap error jika CURL gagal mengeksekusi request
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return json_encode(["success" => false, "message" => "CURL Error: $error_msg"]);
    }

    curl_close($ch);
    return $result;
}