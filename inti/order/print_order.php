<?php
include __DIR__ . '/../../config.php';

$id = $_GET['id'];

// Query header order
$sqlHeader = "
SELECT ro.id, ro.order_id, ro.created_at, ro.total_harga, s.nama_sales, s.perusahaan
FROM riwayat_order ro
JOIN orders o ON ro.order_id = o.id
JOIN sales s ON o.sales_id = s.id
WHERE ro.id = '$id'
";
$resultHeader = $conn->query($sqlHeader);
$header = $resultHeader->fetch_assoc();

// Query detail order
$sqlDetail = "
SELECT nama_barang, qty, harga, subtotal
FROM riwayat_order_detail
WHERE riwayat_order_id = '$id'
";
$resultDetail = $conn->query($sqlDetail);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Order #<?= $header['order_id'] ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
            background: white;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
        }

        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .info-left, .info-right {
            width: 48%;
        }

        .info-left p, .info-right p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .total {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }

        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>AL-BAROKAH TOSERBA 01</h1>
    <p>Jl. Sunan Kalijaga NO.35 Betengan-Demak</p>
    <p>Telp: +62 853-3310-1413 | Email: albaone01@gmail.com</p>
</div>

<div class="order-info">
    <div class="info-left">
        <p><strong>Order ID:</strong> #<?= $header['order_id'] ?></p>
        <p><strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($header['created_at'])) ?></p>
    </div>
    <div class="info-right">
        <p><strong>Sales:</strong> <?= htmlspecialchars($header['nama_sales']) ?></p>
        <p><strong>Perusahaan:</strong> <?= htmlspecialchars($header['perusahaan']) ?></p>
    </div>
</div>

<table>
    <tr>
        <th>No</th>
        <th>Nama Barang</th>
        <th>Qty</th>
        <th>Harga</th>
        <th>Subtotal</th>
    </tr>
    <?php
    $no = 1;
    $total = 0;
    while ($row = $resultDetail->fetch_assoc()) {
        $total += $row['subtotal'];
    ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
        <td><?= $row['qty'] ?></td>
        <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
        <td>Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
    </tr>
    <?php } ?>
</table>

<div class="total">
    <p>Total: Rp <?= number_format($total, 0, ',', '.') ?></p>
</div>

<div class="footer">
    <p>Terima kasih atas kunjungan Anda ke AL-BAROKAH TOSERBA 01</p>
    <p>Dicetak pada: <?= date('d M Y H:i:s') ?></p>
</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>
