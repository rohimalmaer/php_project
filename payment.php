<?php
include 'db.php';
$order_id = $_GET['order_id'] ?? 0;

// Ambil data order
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();

$menu_ids = explode(",", $order['menu_ids']);
$quantities = explode(",", $order['quantities']);

// Ambil data menu terkait
$menu_query = $conn->query("SELECT * FROM menu WHERE id IN (" . implode(',', array_map('intval', $menu_ids)) . ")");
$menus = [];
$total = 0;

// Gabungkan menu dengan jumlah
while ($row = $menu_query->fetch_assoc()) {
    $id = $row['id'];
    $index = array_search($id, $menu_ids); // cari posisi item
    $qty = intval($quantities[$index] ?? 1); // fallback ke 1
    $subtotal = $row['price'] * $qty;
    $menus[] = [
        'name' => $row['name'],
        'price' => $row['price'],
        'qty' => $qty,
        'subtotal' => $subtotal
    ];
    $total += $subtotal;
}

// Format tanggal
$tanggal_order = date('d F Y, H:i', strtotime($order['created_at'])) . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran</title>
    <link rel="stylesheet" href="pay.css">
    <link src="bayar.js">
</head>
<style>
    body {
        background-image: url('img/dalam.jpg'); /* Ganti path sesuai lokasi gambar */
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
    }
</style>
<body>
<div class="container">
    <h2>Detail Pembayaran</h2>

    <p><strong>No Order:</strong> <?= $order['order_code'] ?></p>
    <p><strong>Waktu Order:</strong> <?= $tanggal_order ?></p>
    <p><strong>Nomor Meja:</strong> <?= $order['table_number'] ?></p>

    <h3>Pesanan:</h3>
    <ul>
        <?php foreach ($menus as $item): ?>
            <li><?= $item['name'] ?> × <?= $item['qty'] ?> - Rp<?= number_format($item['subtotal'], 0, ',', '.') ?></li>
        <?php endforeach; ?>
    </ul>
    <p><strong>Total:</strong> Rp<?= number_format($total, 0, ',', '.') ?></p>

    <form action="notify.php" method="POST">
        <input type="hidden" name="order_id" value="<?= $order_id ?>">
        <input type="hidden" name="total" value="<?= $total ?>">

        <label for="method">Pilih Metode Pembayaran:</label>
        <select name="method" id="method" onchange="togglePaymentInfo()">
            <option value="cash">Cash</option>
            <option value="ovo">OVO</option>
            <option value="dana">DANA</option>
            <option value="shopeepay">ShopeePay</option>
        </select>

        <div id="cash-info" style="margin-top:10px;">
            <p><strong>Tunjukkan total ini ke kasir:</strong></p>
            <div style="font-size: 24px; font-weight: bold; color: green;">
                Rp<?= number_format($total, 0, ',', '.') ?>
            </div>
        </div>

        <div id="ovo-info" style="display:none; margin-top:10px;">
    <p><strong>Klik tombol di bawah untuk membayar dengan OVO:</strong></p>
    
    <a href="ovo://pay?amount=<?= $total ?>&merchantid=YOUR_MERCHANT_ID" 
       style="display: inline-block; background-color: #5A2D82; color: white; padding: 10px 20px; font-size: 16px; border-radius: 5px;">
        Bayar dengan OVO
    </a>
</div>

<div id="dana-info" style="display:none; margin-top:10px;">
    <p><strong>Klik tombol di bawah untuk membayar dengan DANA:</strong></p>
    <a href="dana://payment?amount=<?= $total ?>&merchantid=YOUR_MERCHANT_ID" 
       style="display: inline-block; background-color: #007AFF; color: white; padding: 10px 20px; font-size: 16px; border-radius: 5px;">
        Bayar dengan DANA
    </a>
</div>

<div id="shopeepay-info" style="display:none; margin-top:10px;">
    <p><strong>Klik tombol di bawah untuk membayar dengan ShopeePay:</strong></p>
    <a href="shopeepay://pay?amount=<?= $total ?>&merchantid=YOUR_MERCHANT_ID" 
       style="display: inline-block; background-color: #FF5722; color: white; padding: 10px 20px; font-size: 16px; border-radius: 5px;">
        Bayar dengan ShopeePay
    </a>
</div>


        <button type="submit">Konfirmasi Pembayaran</button>
    </form>
</div>
async function processPayment(method) {
    const total = <?= json_encode($total) ?>; // Pastikan data berasal dari backend
    const orderId = <?= json_encode($order_id) ?>;
    const endpoints = {
        ovo: '/api/pay/ovo',
        dana: '/api/pay/dana',
        shopeepay: '/api/pay/shopeepay',
    };

    // Validasi metode pembayaran
    if (!endpoints[method]) {
        alert('Metode pembayaran tidak valid. Silakan coba lagi.');
        return;
    }

    try {
        const response = await fetch(endpoints[method], {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ amount: total, orderId: orderId }),
        });

        if (!response.ok) {
            throw new Error(`Server Error: ${response.status}`);
        }

        const result = await response.json();
        if (result.redirectUrl) {
            window.location.href = result.redirectUrl; // Redirect ke aplikasi pembayaran
        } else {
            alert('Gagal memproses pembayaran. Silakan coba lagi.');
        }
    } catch (error) {
        console.error('Error:', error); // Untuk debugging
        alert('Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.');
    }
}

function togglePaymentInfo() {
    const method = document.getElementById('method').value;
    const cashInfo = document.getElementById('cash-info');
    const ovoInfo = document.getElementById('ovo-info');
    const danaInfo = document.getElementById('dana-info');
    const shopeepayInfo = document.getElementById('shopeepay-info');

    cashInfo.style.display = method === 'cash' ? 'block' : 'none';
    ovoInfo.style.display = method === 'ovo' ? 'block' : 'none';
    danaInfo.style.display = method === 'dana' ? 'block' : 'none';
    shopeepayInfo.style.display = method === 'shopeepay' ? 'block' : 'none';
}

window.onload = togglePaymentInfo;

<script>
function togglePaymentInfo() {
    const method = document.getElementById('method').value;
    const cashInfo = document.getElementById('cash-info');
    const ovoInfo = document.getElementById('ovo-info');
    const danaInfo = document.getElementById('dana-info');
    const shopeepayInfo = document.getElementById('shopeepay-info');

    cashInfo.style.display = method === 'cash' ? 'block' : 'none';
    ovoInfo.style.display = method === 'ovo' ? 'block' : 'none';
    danaInfo.style.display = method === 'dana' ? 'block' : 'none';
    shopeepayInfo.style.display = method === 'shopeepay' ? 'block' : 'none';
}
window.onload = togglePaymentInfo;
</script>
</body>
</html>
