<?php
/**
 * Provera PHP postavki za upload fajlova
 * 
 * Pristup: https://digital.kotor.me/check-upload-settings.php
 * 
 * NAPOMENA: Obrišite ovaj fajl nakon provere zbog bezbednosti!
 */

// Konvertuje string veličine (npr. "10M", "2G") u bajtove
function convertToBytes($size) {
    $size = trim($size);
    $last = strtolower($size[strlen($size) - 1]);
    $value = (int) $size;
    
    switch ($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    
    return $value;
}

// Formatira bajtove u čitljiv format
function formatBytes($bytes) {
    if ($bytes >= 1024 * 1024 * 1024) {
        return round($bytes / (1024 * 1024 * 1024), 2) . 'G';
    } elseif ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 2) . 'M';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . 'K';
    }
    return $bytes . 'B';
}

$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxFileUploads = ini_get('max_file_uploads');
$maxExecutionTime = ini_get('max_execution_time');
$memoryLimit = ini_get('memory_limit');

$uploadMaxBytes = convertToBytes($uploadMaxFilesize);
$postMaxBytes = convertToBytes($postMaxSize);
$laravelMaxKB = 2048; // 2 MB (ograničeno PHP upload_max_filesize)
$laravelMaxBytes = $laravelMaxKB * 1024;

?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Upload Settings Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 10px;
        }
        h2 {
            color: #666;
            margin-top: 30px;
            font-size: 18px;
        }
        .setting {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            margin: 5px 0;
            background: #f9fafb;
            border-radius: 4px;
        }
        .setting-name {
            font-weight: bold;
            color: #333;
        }
        .setting-value {
            color: #3b82f6;
            font-family: monospace;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 PHP Upload Settings Check</h1>
        
        <h2>PHP Postavke</h2>
        <div class="setting">
            <span class="setting-name">upload_max_filesize:</span>
            <span class="setting-value"><?php echo htmlspecialchars($uploadMaxFilesize); ?></span>
        </div>
        <div class="setting">
            <span class="setting-name">post_max_size:</span>
            <span class="setting-value"><?php echo htmlspecialchars($postMaxSize); ?></span>
        </div>
        <div class="setting">
            <span class="setting-name">max_file_uploads:</span>
            <span class="setting-value"><?php echo htmlspecialchars($maxFileUploads); ?></span>
        </div>
        <div class="setting">
            <span class="setting-name">max_execution_time:</span>
            <span class="setting-value"><?php echo htmlspecialchars($maxExecutionTime); ?> sekundi</span>
        </div>
        <div class="setting">
            <span class="setting-name">memory_limit:</span>
            <span class="setting-value"><?php echo htmlspecialchars($memoryLimit); ?></span>
        </div>
        
        <h2>Laravel Validacija</h2>
        <div class="info">
            <strong>Maksimalna veličina po fajlu (dokumenti):</strong> <?php echo round($laravelMaxKB / 1024, 1); ?> MB (<?php echo $laravelMaxKB; ?> KB)
        </div>
        
        <h2>Preporuke</h2>
        <?php if ($uploadMaxBytes < $laravelMaxBytes): ?>
            <div class="warning">
                <strong>⚠️ Upozorenje:</strong> upload_max_filesize (<?php echo htmlspecialchars($uploadMaxFilesize); ?>) je manji od Laravel limita (<?php echo round($laravelMaxKB / 1024, 1); ?> MB)!<br>
                <strong>Preporuka:</strong> Povećajte upload_max_filesize na najmanje <?php echo round($laravelMaxKB / 1024, 1); ?>M u PHP konfiguraciji.
            </div>
        <?php else: ?>
            <div class="success">
                ✓ upload_max_filesize je dovoljno velik za Laravel validaciju.
            </div>
        <?php endif; ?>
        
        <?php
        $recommendedPostMax = $maxFileUploads * $laravelMaxBytes;
        if ($postMaxBytes < $recommendedPostMax):
        ?>
            <div class="warning">
                <strong>⚠️ Upozorenje:</strong> post_max_size (<?php echo htmlspecialchars($postMaxSize); ?>) može biti ograničavajući za više fajlova!<br>
                <strong>Preporuka:</strong> Povećajte post_max_size na najmanje <?php echo formatBytes($recommendedPostMax); ?> da biste omogućili upload <?php echo $maxFileUploads; ?> fajlova od 10MB svaki.
            </div>
        <?php else: ?>
            <div class="success">
                ✓ post_max_size je dovoljno velik za više fajlova.
            </div>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 30px;">
            <strong>📝 Napomena:</strong> Obrišite ovaj fajl nakon provere zbog bezbednosti!
        </div>
    </div>
</body>
</html>
