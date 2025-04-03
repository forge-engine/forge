<?php

$url = "http://localhost:8000";
$requests = 100;
$startTime = microtime(true);

for ($i = 0; $i < $requests; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

$endTime = microtime(true);
$totalTime = $endTime - $startTime;
$requestsPerSecond = $requests / $totalTime;

echo "Total time: $totalTime seconds\n";
echo "Requests per second: $requestsPerSecond\n";
