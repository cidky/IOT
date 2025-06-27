<?php
require("phpMQTT.php");
use Bluerhinos\phpMQTT;

$server = "broker.hivemq.com";
$port = 1883;
$username = "";
$password = "";
$client_id = "G.231.22.0182-55555";

$mqtt = new phpMQTT($server, $port, $client_id);
$mqtt->debug = true;

if($mqtt->connect(true, NULL, $username, $password)) {
    echo "✅ BERHASIL konek ke broker MQTT\n";
    $mqtt->close();
} else {
    echo "❌ GAGAL konek ke broker MQTT\n";
}