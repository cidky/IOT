<?php
require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// Ambil input dari body JSON (axios.post)
$data = json_decode(file_get_contents('php://input'), true);
$led = $data['led'] ?? null;
$action = $data['action'] ?? null;

if (!$led || !$action) {
    echo json_encode(['error' => 'Parameter tidak lengkap']);
    exit;
}

$clientId = 'control-web-' . rand(1000, 9999);
$server   = 'mqtt.revolusi-it.com';
$port     = 1883;
$username = 'usm';
$password = 'usmjaya1';
$topic    = 'iot/G.231.22.0182/control';

$message = "LED{$led}_{$action}";

try {
    $mqtt = new MqttClient($server, $port, $clientId);
    $mqtt->connect((new ConnectionSettings)->setUsername($username)->setPassword($password));
    $mqtt->publish($topic, $message, 0);
    $mqtt->disconnect();

    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}