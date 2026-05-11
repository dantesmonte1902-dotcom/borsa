<?php
date_default_timezone_set("Europe/Istanbul");
set_time_limit(0);
ini_set('max_execution_time', 0);

// --- Ayarlar ---
$txtDir = __DIR__ . "/bist_hisseler";
if(!file_exists($txtDir)) mkdir($txtDir, 0777, true);

$safeStart = 10; // işlem yapabileceğin saat aralığı
$safeEnd = 16;

// --- TradingView Screener ---
$url = "https://scanner.tradingview.com/turkey/scan";
$postData = [
    "symbols" => ["tickers"=>[],"query"=>["types"=>[]]],
    "columns" => ["name"]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$symbols = [];
if(isset($data['data'])) {
    foreach($data['data'] as $row) {
        if(isset($row['s'])) $symbols[] = $row['s'];
    }
}
echo "Toplam hisse: ".count($symbols)."\n";

// --- Sembol verilerini çek ve .txt dosyasına kaydet ---
foreach($symbols as $symbol) {
    try {
        $url = "https://scanner.tradingview.com/turkey/scan";
        $post = [
            "symbols" => ["tickers"=>[$symbol],"query"=>["types"=>[]]],
            "columns" => ["close","high","low","volume"]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $res = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($res, true);
        if(!isset($json["data"][0]["d"][0])) continue;

        $price = floatval($json["data"][0]["d"][0]);
        $high = floatval($json["data"][0]["d"][1] ?? $price);
        $low = floatval($json["data"][0]["d"][2] ?? $price);
        $volume = floatval($json["data"][0]["d"][3] ?? 0);

        $hour = intval(date("H"));
        if($hour < $safeStart || $hour > $safeEnd) continue;

        $txtFile = "$txtDir/".str_replace("BIST:","",$symbol).".txt";
        if(!file_exists($txtFile)) file_put_contents($txtFile, "time,price,high,low,volume\n");

        $line = date("Y-m-d H:i").",$price,$high,$low,$volume\n";
        file_put_contents($txtFile, $line, FILE_APPEND);

        echo "Kaydedildi: $symbol | Fiyat: $price\n";

        sleep(1); // rate limit önleme
    } catch(Exception $e) {
        echo "Hata: ".$e->getMessage()."\n";
        continue;
    }
}

echo "Tüm veriler kaydedildi.\n";
?>
