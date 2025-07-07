function togglePaymentInfo() {
    const method = document.getElementById('method').value;
    const cashInfo = document.getElementById('cash-info');
    const qrCode = document.getElementById('qr-code');
    const qrImage = document.getElementById('qr-image');

    cashInfo.style.display = method === 'cash' ? 'block' : 'none';
    qrCode.style.display = ['ovo', 'dana', 'shopeepay'].includes(method) ? 'block' : 'none';

    if (qrCode.style.display === 'block') {
        const amount = <?= json_encode($total) ?>;
        const orderId = <?= json_encode($order_id) ?>;
        qrImage.src = `generate_qr.php?amount=${amount}&order_id=${orderId}&method=${method}`;
    }
}

window.onload = togglePaymentInfo;
