<?php
header("Access-Control-Allow-Origin: *");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengakses nilai dari body request
     $username = $_POST['username'];
    $amount = $_POST['amount'];
    $note = $_POST['note'];

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.jagel.id/v1/balance/adjust',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'type' => 'username',
            'value' => $username,
            'apikey' => 'wxo6DiWJ2kaZ4sjk6Dv0Ld0Iea2l649YhutdoTSnBOe3YD5Lwz',
            'amount' => $amount,
            'note' => $note
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
} else {
    echo "Method not allowed.";
}
?>
