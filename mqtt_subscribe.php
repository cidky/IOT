<?php
require("phpMQTT.php"); // File hasil copy-paste sebelumnya

use Bluerhinos\phpMQTT;

$server = "broker.hivemq.com";
$port = 1883;
$username = "";
$password = "";
$client_id = "G.231.22.0182-9988";

$mqtt = new phpMQTT($server, $port, $client_id);
$mqtt->debug = true;

if(!$mqtt->connect(true, NULL, $username, $password)) {
    exit("Gagal konek MQTT\n");
}

$topic = "iot/testing/G.231.22.0182";

$mqtt->subscribe([$topic => ["qos" => 0, "function" => "simpanKeDB"]]);

while($mqtt->proc()) {}

$mqtt->close();

function simpanKeDB($topic, $msg){
    echo "Diterima: $msg\n";

    $data = json_decode($msg, true);
    $suhu = $data["suhu"];
    $kelembaban = $data["kelembaban"];

    $db = new mysqli("localhost", "root", "", "iot_project");
    if ($db->connect_error) {
        echo "Koneksi DB gagal: " . $db->connect_error;
        return;
    }

    $sql = "INSERT INTO sensor_log (suhu, kelembaban) VALUES ('$suhu', '$kelembaban')";
    if ($db->query($sql)) {
        echo "Tersimpan\n";
    } else {
        echo "Gagal Simpan\n";
    }
}