<?php
// Speichert den Spielstand gemeinsam in data.json im selben Ordner wie index.html.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dir = __DIR__;
$file = $dir . '/data.json';
$action = isset($_GET['action']) ? $_GET['action'] : '';

function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'load') {
    if (!file_exists($file)) { echo 'null'; exit; }
    if (!is_readable($file)) fail(500, 'data.json ist nicht lesbar (Rechte prüfen)');
    echo file_get_contents($file);
    exit;
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_array($data)) fail(400, 'Ungültige Daten');
    $writable = file_exists($file) ? is_writable($file) : is_writable($dir);
    if (!$writable) fail(500, 'Ordner oder data.json ist nicht beschreibbar (Schreibrechte für den Webserver setzen)');
    $fp = fopen($file, 'c+');
    if ($fp === false) fail(500, 'data.json kann nicht geöffnet werden');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $body);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['ok' => true]);
    exit;
}

fail(400, 'unbekannte Aktion');
