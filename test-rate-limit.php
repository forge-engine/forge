<?php

function curlRequest($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-KEY: your-secure-api-key'
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

$starttime = microtime(true);
$data = [];
for ($i = 0; $i < 102; $i++) {
    $data[] = "http://localhost:8000/api2/v2/users";
}

foreach ($data as $val) {
    $r = curlRequest($val);
    echo "<pre>";
    print_r($r . '\n');
    echo "</pre>";
}

$endtime = microtime(true);
$diff = round($endtime - $starttime, 5);
echo "Time elapsed: " . $diff . " seconds \n";
