<?php
// Aktifkan semua error agar tampil di browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$koneksi = new mysqli("localhost", "root", "", "iot_project");

if ($koneksi->connect_error) {
    die(json_encode(["error" => "Koneksi database gagal"]));
}

$sql = "SELECT waktu, suhu, kelembaban FROM sensor_log ORDER BY waktu DESC LIMIT 20";
$result = $koneksi->query($sql);

if (!$result) {
    die(json_encode(["error" => "Query gagal"]));
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['waktu'] = date('H:i:s', strtotime($row['waktu']));
    $data[] = $row;
}

echo json_encode(array_reverse($data));
$koneksi->close();
?>