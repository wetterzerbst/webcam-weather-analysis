<?php

function downloadImage($url, $saveTo) {
    $timestamp = time();
    $urlWithTimestamp = $url . "?t=" . $timestamp; // Füge den Timestamp zur URL hinzu
    $img = file_get_contents($urlWithTimestamp);
    if ($img === false) {
        die("Fehler: Bild konnte nicht heruntergeladen werden.");
    }
    file_put_contents($saveTo, $img);
}

function analyzeWeather($imagePath) {
    $image = imagecreatefromjpeg($imagePath);
    if (!$image) {
        die("Fehler: Konnte Bild nicht öffnen.");
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $totalPixels = ($width * $height) / 10; // Reduzierte Stichprobengröße für bessere Performance
    
    $bluePixels = 0;
    $cloudPixels = 0;
    $brightnessSum = 0;
    
    for ($x = 0; $x < $width; $x += 10) { // Analyse in 10-Pixel-Schritten
        for ($y = 0; $y < $height / 2; $y += 10) { // Nur obere Bildhälfte (Himmel)
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            $brightness = ($r + $g + $b) / 3; // Durchschnittliche Helligkeit berechnen
            $brightnessSum += $brightness;
            
            // Identifikation von Himmelsfarben und Wolken
            if ($b > $r && $b > $g && $b > 50 && ($b - max($r, $g)) > 10) { // Optimierte Blauton-Erkennung
                $bluePixels++;
            }
            if ($r > 245 && $g > 245 && $b > 245 && abs($r - $g) < 4 && abs($r - $b) < 4) { // Noch feinere Wolkenerkennung
                $cloudPixels++;
            }
        }
    }
    
    imagedestroy($image);
    
    $blueRatio = ($bluePixels / $totalPixels) * 100;
    $cloudRatio = ($cloudPixels / $totalPixels) * 100;
    $avgBrightness = $brightnessSum / $totalPixels;
    
    // Debugging-Ausgabe zur besseren Analyse
    file_put_contents("debug_log.txt", "Helligkeit: $avgBrightness, Blauanteil: $blueRatio%, Wolkenanteil: $cloudRatio%\n", FILE_APPEND);
    
    // Nachtmodus-Erkennung (Schwelle auf 5 gesenkt für präzisere Erkennung)
    if ($avgBrightness < 5) {
        return "Nacht - Keine Wetterbestimmung möglich";
    }
    
    // Finale Wetterklassifikation ohne unklare Einstufungen
    if ($blueRatio > 55 && $cloudRatio < 10) {
        return "Sonnig";
    } elseif ($blueRatio > 40 && $cloudRatio >= 10 && $cloudRatio < 30) {
        return "Leicht bewölkt";
    } elseif ($blueRatio > 25 && $cloudRatio >= 30 && $cloudRatio < 60) {
        return "Aufgelockerte Bewölkung";
    } elseif ($cloudRatio >= 60) {
        return "Stark bewölkt oder bedeckt";
    } else {
        return "Klarer Himmel - Keine signifikanten Wolken";
    }
}

function logWeatherAnalysis($weather) {
    $logFile = "weather_log.txt";
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logFile, "$timestamp - $weather\n", FILE_APPEND);
}

$webcamURL = "https://zerbst.x10.mx/webcam/livecam.jpg";
$localImage = "webcam.jpg";

downloadImage($webcamURL, $localImage);
$weather = analyzeWeather($localImage);
logWeatherAnalysis($weather);

echo "Aktuelle Wetterlage: $weather";
?>
