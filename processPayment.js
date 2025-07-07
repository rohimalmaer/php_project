async function checkPaymentStatus(orderId) {
    try {
        const response = await fetch(`/api/check-payment-status?order_id=${orderId}`);
        const result = await response.json();

        if (result.status === 'paid') {
            alert('Pembayaran berhasil!');
            window.location.href = '/order-summary?order_id=' + orderId;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Jalankan polling setiap 5 detik
setInterval(() => {
    const orderId = document.querySelector('input[name="order_id"]').value;
    checkPaymentStatus(orderId);
}, 5000);
