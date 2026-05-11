<?php
date_default_timezone_set("Europe/Istanbul");
set_time_limit(0);
ini_set('max_execution_time', 0);

// --- Ayarlar ---
$txtDir = __DIR__ . "/bist_hisseler";   // Sembol txt dosyaları
$chartsDir = __DIR__ . "/charts";       // Grafiklerin kaydedileceği klasör
if(!file_exists($chartsDir)) mkdir($chartsDir, 0777, true);

$safeStart = 10; // işlem yapabileceğin saat aralığı
$safeEnd = 23;

// Telegram ayarları
$telegram_token = "8314780142:AAGrfwofJZW7YbjJX4E1IINl-OKm_wGuN48";
$telegram_chat_id = "7826058694";

// --- Fonksiyonlar ---
function sendTelegramPhoto($photoPath, $caption="", $token, $chat_id){
    $url = "https://api.telegram.org/bot$token/sendPhoto";
    $postFields = [
        'chat_id' => $chat_id,
        'caption' => $caption,
        'photo' => new CURLFile(realpath($photoPath))
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// --- txt dosyalarından sembolleri al ---
$files = glob($txtDir . "/*.txt");
$symbols = [];
foreach($files as $f){
    $sym = basename($f, ".txt");
    $symbols[] = "BIST:" . $sym;
}

echo "Toplam sembol txt'den alındı: " . count($symbols) . "\n";

// --- Her sembol için grafik üret ve Telegram'a gönder ---
foreach($symbols as $symbol){
    $hour = intval(date("H"));
    if($hour < $safeStart || $hour > $safeEnd){
        echo "Saat uygun değil, $symbol atlandı.\n";
        continue;
    }

    echo "Grafik alınıyor: $symbol\n";

    // Node.js chart.js scriptini çalıştır (chart.js dosyan hazır olmalı)
    $nodePath = "C:\\Program Files\\nodejs\\node.exe"; // Windows örnek
    $cmd = "\"$nodePath\" chart.js $symbol 2>&1";
    $output = shell_exec($cmd);
    echo $output;

    // Grafik dosya kontrolü
    $chartFile = $chartsDir . "/" . str_replace(":", "_", $symbol) . ".png";
    if(file_exists($chartFile)){
        echo "Grafik hazır: $chartFile\n";
        sendTelegramPhoto($chartFile, "$symbol 12 aylık grafik", $telegram_token, $telegram_chat_id);
        echo "Telegram'a gönderildi: $symbol\n";
    } else {
        echo "Grafik bulunamadı: $symbol\n";
    }

    sleep(2); // rate limit önleme
}

echo "Tüm grafikler işlendi.\n";
?>
