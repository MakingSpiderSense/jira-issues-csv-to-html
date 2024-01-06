<?php
// Helper functions


// PHP Console Log
function console_log($data) {
    echo '<script>';
    echo 'console.log('.json_encode($data).')';
    echo '</script>';
}


// Load .env file
function loadEnv($envPath) {
    if (!file_exists($envPath)) {
        return [];
    }

    $env = [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        list($key, $value) = explode('=', $line, 2);
        $env[$key] = $value;
    }
    return $env;
}


// Get environment variable
function getEnvVariable($key, $default = null) {
    static $envVariables;

    if (!$envVariables) {
        $envPath = __DIR__ . '/.env';
        $envVariables = loadEnv($envPath);
    }

    return $envVariables[$key] ?? $default;
}

// Get the base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    return $protocol . '://' . $host . $uri . '/';
}


?>