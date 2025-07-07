
<?php
require 'phpqrcode/qrlib.php';

$amount = $_GET['amount'] ?? 0; // Jumlah pembayaran
$order_id = $_GET['order_id'] ?? 0; // ID Pesanan
$method = $_GET['method'] ?? ''; // Metode pembayaran

// Data yang dienkode dalam QR Code
$data = json_encode([
    'amount' => $amount,
    'order_id' => $order_id,
    'method' => $method,
]);

// Generate QR Code
header('Content-Type: image/png');
QRcode::png($data);
