<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['sales_id'])) {
    die("Sales ID tidak ditemukan.");
}

$sales_id = $_GET['sales_id'];

// Ambil data sales
$sales_query = "
    SELECT s.*, jk.nama_jenis AS kunjungan
    FROM sales s
    LEFT JOIN jenis_kunjungan jk ON s.jenis_kunjungan_id = jk.id
    WHERE s.id = ?
";
$sales_stmt = $conn->prepare($sales_query);
$sales_stmt->bind_param("i", $sales_id);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();

if ($sales_result->num_rows == 0) {
    die("Sales tidak ditemukan.");
}

$sales = $sales_result->fetch_assoc();
$sales_stmt->close();

// Ambil riwayat pembayaran
$payment_query = "
    SELECT rp.*, o.id as order_id, CONCAT('ORD-', LPAD(o.id, 5, '0')) as kode_order, s.nama_sales, s.perusahaan
    FROM riwayat_pembayaran rp
    JOIN orders o ON rp.order_id = o.id
    JOIN sales s ON o.sales_id = s.id
    WHERE o.sales_id = ?
    ORDER BY rp.created_at DESC
";
$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bind_param("i", $sales_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$payments = [];
while ($row = $payment_result->fetch_assoc()) {
    $payments[] = $row;
}
$payment_stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran - ALBA</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #437057;
            --primary-dark: #2d4d3b;
            --gold-accent: #c5a059;
            --text-dark: #2c3e50;
            --bg-light: #f4f7f6;
            --white: #ffffff;
            --shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --radius: 20px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-light);
            background-image: radial-gradient(circle at top right, #e8edea, transparent);
            color: var(--text-dark);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .card {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .sales-info {
            background: rgba(67, 112, 87, 0.05);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
        }

        .sales-info h3 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .payment-table th,
        .payment-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(67, 112, 87, 0.1);
        }

        .payment-table th {
            background: rgba(67, 112, 87, 0.05);
            color: var(--primary-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .payment-table tr:hover {
            background: rgba(67, 112, 87, 0.02);
        }

        .amount {
            font-weight: 700;
            color: var(--primary-color);
        }

        .btn-back {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 700;
            margin-top: 20px;
            transition: color 0.3s ease;
        }

        .btn-back:hover {
            color: var(--gold-accent);
        }

        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }

        @media (max-width: 600px) {
            body { padding: 10px; }
            .card { padding: 20px 15px; }
            h2 { font-size: 1.4rem; }
            .payment-table th, .payment-table td { padding: 8px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Riwayat Pembayaran</h2>

        <div class="card">
            <div class="sales-info">
                <h3>Informasi Sales</h3>
                <p><strong>Nama Sales:</strong> <?= htmlspecialchars($sales['nama_sales']) ?></p>
                <p><strong>Perusahaan:</strong> <?= htmlspecialchars($sales['perusahaan']) ?></p>
            </div>

            <?php if (!empty($payments)): ?>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode Order</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($payment['tanggal_pembayaran'])) ?></td>
                                <td><?= htmlspecialchars($payment['kode_order']) ?></td>
                                <td class="amount">Rp <?= number_format($payment['jumlah_pembayaran'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($payment['metode_pembayaran']) ?></td>
                                <td><?= htmlspecialchars($payment['catatan'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    Belum ada riwayat pembayaran untuk sales ini.
                </div>
            <?php endif; ?>

            <a class="btn-back" href="?path=monitor_sales&sales_id=<?= $sales_id ?>">
                ← Kembali ke Monitor Sales
            </a>
        </div>
    </div>
</body>
</html>
