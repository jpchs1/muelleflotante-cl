<?php
/**
 * Deploy Webhook Universal - Hosting Imporlan/Banahosting
 *
 * Recibe webhooks de GitHub y despliega automaticamente.
 * Funciona para TODOS los repositorios configurados.
 *
 * Configuracion de repos en $repos array.
 * GitHub Webhook URL: https://costacolbun.cl/deploy-webhook.php
 * Content type: application/json
 * Secret: (el definido abajo)
 *
 * @version 2.0
 */

// ============================================
// CONFIGURACION
// ============================================
$webhookSecret = 'costa_colbun_deploy_2025_xK9mP';

// Repositorios y sus rutas de deploy
$repos = [
    'jpchs1/muelleflotante-cl' => [
        'branch' => 'main-branch',
        'deploy_path' => '/home/wwimpo/costacolbun.cl',
        'name' => 'Costa Colbun'
    ],
    'jpchs1/Imporlan' => [
        'branch' => 'main',
        'deploy_path' => '/home/wwimpo/public_html',
        'name' => 'Imporlan'
    ]
    // Agrega mas repos aqui:
    // 'jpchs1/otro-repo' => [
    //     'branch' => 'main',
    //     'deploy_path' => '/home/wwimpo/otrodominio.cl',
    //     'name' => 'Otro Sitio'
    // ]
];

// Archivos que NO deben sobrescribirse
$globalProtected = [
    'wp-config.php',
    'php.ini',
    '.htaccess',
    'api/config.php',
    'api/db_config.php',
    'error_log',
    'deploy-webhook.php'
];

// ============================================
// VERIFICACION DEL WEBHOOK
// ============================================
header('Content-Type: application/json');

// Permitir deploy manual via GET con token
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';
    $repoName = $_GET['repo'] ?? '';
    
    if ($token !== $webhookSecret) {
        http_response_code(403);
        die(json_encode(['error' => 'Token invalido']));
    }
    
    if (!isset($repos[$repoName])) {
        http_response_code(400);
        die(json_encode([
            'error' => 'Repo no encontrado',
            'available' => array_keys($repos)
        ]));
    }
    
    $repoFullName = $repoName;
    $branch = $repos[$repoName]['branch'];
    
} else {
    // Webhook de GitHub via POST
    $payload = file_get_contents('php://input');
    
    // Verificar firma
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);
    
    if (!hash_equals($expected, $signature)) {
        // Tambien aceptar sin firma para testing
        $testToken = $_GET['token'] ?? '';
        if ($testToken !== $webhookSecret) {
            http_response_code(403);
            die(json_encode(['error' => 'Firma invalida']));
        }
    }
    
    $data = json_decode($payload, true);
    
    if (!$data) {
        http_response_code(400);
        die(json_encode(['error' => 'Payload invalido']));
    }
    
    // Extraer info del push
    $repoFullName = $data['repository']['full_name'] ?? '';
    $ref = $data['ref'] ?? '';
    $branch = str_replace('refs/heads/', '', $ref);
}

// ============================================
// DEPLOY
// ============================================
if (!isset($repos[$repoFullName])) {
    echo json_encode(['message' => 'Repo no configurado, ignorando', 'repo' => $repoFullName]);
    exit;
}

$config = $repos[$repoFullName];

// Verificar que es la branch correcta
if ($branch !== $config['branch']) {
    echo json_encode(['message' => "Branch $branch ignorada, solo se despliega {$config['branch']}"]);
    exit;
}

$deployPath = $config['deploy_path'];
$tempPath = '/home/wwimpo/deploy_temp_' . time() . '_' . rand(1000, 9999);
$log = [];
$startTime = microtime(true);

try {
    $log[] = "Iniciando deploy de {$config['name']}...";
    $log[] = "Repo: $repoFullName | Branch: $branch";
    $log[] = "Destino: $deployPath";
    
    // 1. Clonar repo
    $log[] = 'Clonando repositorio...';
    $cloneUrl = "https://github.com/$repoFullName.git";
    $cmd = "git clone --depth 1 --branch {$config['branch']} $cloneUrl $tempPath 2>&1";
    $output = shell_exec($cmd);
    $log[] = trim($output);
    
    if (!is_dir($tempPath)) {
        throw new Exception('Clone fallo');
    }
    
    // 2. Obtener info del commit
    $commitHash = trim(shell_exec("cd $tempPath && git log -1 --format='%h' 2>&1"));
    $commitMsg = trim(shell_exec("cd $tempPath && git log -1 --format='%s' 2>&1"));
    $log[] = "Commit: $commitHash - $commitMsg";
    
    // 3. Backup de archivos protegidos
    $log[] = 'Respaldando archivos protegidos...';
    $backups = [];
    foreach ($globalProtected as $file) {
        $fullPath = "$deployPath/$file";
        if (file_exists($fullPath)) {
            $backups[$file] = file_get_contents($fullPath);
            $log[] = "  Protegido: $file";
        }
    }
    
    // 4. Sincronizar con rsync
    $log[] = 'Sincronizando archivos...';
    $excludes = implode(' ', array_map(function($f) {
        return "--exclude='$f'";
    }, $globalProtected));
    
    $rsyncCmd = "rsync -av --delete --exclude='.git' --exclude='deploy-webhook.php' $excludes $tempPath/ $deployPath/ 2>&1";
    $output = shell_exec($rsyncCmd);
    $changedFiles = substr_count($output, "\n") - 4; // rsync footer lines
    $log[] = "Archivos sincronizados: ~$changedFiles";
    
    // 5. Restaurar archivos protegidos
    $log[] = 'Restaurando archivos protegidos...';
    foreach ($backups as $file => $content) {
        $fullPath = "$deployPath/$file";
        $dir = dirname($fullPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($fullPath, $content);
    }
    
    // 6. Limpiar
    shell_exec("rm -rf $tempPath");
    
    $elapsed = round(microtime(true) - $startTime, 2);
    $log[] = "Deploy completado en {$elapsed}s";
    
    // 7. Registrar en historial
    $deployRecord = [
        'timestamp' => date('Y-m-d H:i:s'),
        'repo' => $repoFullName,
        'site' => $config['name'],
        'branch' => $branch,
        'commit' => "$commitHash - $commitMsg",
        'duration' => "{$elapsed}s",
        'success' => true
    ];
    
    $logFile = '/home/wwimpo/deploy_history.json';
    $history = [];
    if (file_exists($logFile)) {
        $history = json_decode(file_get_contents($logFile), true) ?? [];
    }
    $history[] = $deployRecord;
    $history = array_slice($history, -200);
    file_put_contents($logFile, json_encode($history, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'site' => $config['name'],
        'commit' => "$commitHash - $commitMsg",
        'duration' => "{$elapsed}s",
        'log' => $log
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if (is_dir($tempPath)) shell_exec("rm -rf $tempPath");
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'log' => $log
    ], JSON_PRETTY_PRINT);
}
