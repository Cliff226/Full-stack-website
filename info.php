<?php
// Your API key from football-data.org
$apiKey = "0c98b44563234432be112138964c7529";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.football-data.org/v4/competitions/PD/standings",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYHOST => 0,  // temporary
    CURLOPT_SSL_VERIFYPEER => 0,   // temporary
    CURLOPT_HTTPHEADER => [
        "X-Auth-Token: $apiKey"
    ]
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo "<h2>HTTP Status Code: $httpCode</h2>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";