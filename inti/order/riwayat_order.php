<?php
// Redirect to routed URL if accessed directly
if (!isset($_GET['path'])) {
    header("Location: /index.php?path=riwayat_order.php" . (isset($_GET['sales_id']) ? "&sales_id=" . intval($_GET['sales_id']) : ""));
    exit();
}

include __DIR__ . '/../../config.php';

// Ambil sales_id jika ada
$sales_id = isset($_GET['sales_id']) ? intval($_GET['sales_id']) : null;

// Ambil semua order sesuai sales_id
if ($sales_id) {
    $queryOrder = "
        SELECT ro.id, ro.order_id, ro.created_at, ro.total_harga
        FROM riwayat_order ro
        JOIN orders o ON ro.order_id = o.id
        WHERE o.sales_id = $sales_id
        ORDER BY ro.created_at DESC
    ";
} else {
    $queryOrder = "SELECT id, order_id, created_at, total_harga FROM riwayat_order ORDER BY created_at DESC";
}

$resultOrder = $conn->query($queryOrder);

// Simpan daftar order
$orders = [];
while ($row = $resultOrder->fetch_assoc()) {
    $orders[] = $row;
}

// Ambil semua item order
$queryItems = "
    SELECT d.riwayat_order_id, d.nama_barang, d.qty, d.harga, d.subtotal, d.gambar
    FROM riwayat_order_detail d
    ORDER BY d.created_at DESC
";
$resultItems = $conn->query($queryItems);

// Kelompokkan item berdasarkan riwayat_order_id
$groupedItems = [];
while ($row = $resultItems->fetch_assoc()) {
    $groupedItems[$row['riwayat_order_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Order<?= $sales_id ? " - Sales #$sales_id" : "" ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Reset dasar untuk konsistensi */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 10px;
            max-width: 1200px;
            margin: 0 auto;
            min-height: 100vh;
        }

        /* Judul utama */
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 600;
        }

        /* Pesan jika tidak ada transaksi */
        p {
            text-align: center;
            font-size: 1rem;
            color: #7f8c8d;
            margin: 20px 0;
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Kartu order */
        .order-card {
            background-color: #fff;
            border: 1px solid #e1e8ed;
            border-radius: 12px;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        /* Header order: tanggal dan tombol download */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #34495e;
            font-weight: 500;
        }

        .download-btn {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            margin-right: 10px;
        }

        .download-btn:hover {
            background: linear-gradient(45deg, #2980b9, #21618c);
            transform: scale(1.05);
        }

        .print-btn {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            margin-right: 10px;
        }

        .print-btn:hover {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            transform: scale(1.05);
        }

        /* Tabel produk (untuk desktop/tablet) */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 0.9rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #e9ecef;
            font-weight: 600;
            color: #495057;
        }

        .thumb {
            max-width: 50px;
            max-height: 50px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        /* Container untuk card produk (untuk mobile) */
        .products-container {
            display: none; /* Default hidden, shown on mobile */
        }

        /* Card produk untuk mobile */
        .product-card {
            background-color: #fff;
            border: 1px solid #e1e8ed;
            border-radius: 10px;
            margin-bottom: 10px;
            padding: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-card img {
            align-self: center;
            max-width: 60px;
            max-height: 60px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .product-card .product-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
        }

        .product-card .product-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .product-card .product-price {
            font-weight: 500;
            color: #27ae60;
        }

        /* Jika tidak ada produk */
        .no-products {
            text-align: center;
            color: #95a5a6;
            font-style: italic;
            padding: 10px;
        }

        /* Total */
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 1.1rem;
            color: #27ae60;
            margin-bottom: 10px;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #c3e6cb;
        }

        /* Tombol hapus */
        .delete-btn {
            display: inline-block;
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .delete-btn:hover {
            background: linear-gradient(45deg, #c0392b, #a93226);
            transform: scale(1.05);
        }

        /* Responsivitas: Prioritas layar kecil (mobile-first) */
        @media (max-width: 768px) {
            body {
                padding: 5px;
                background: #f5f7fa; /* Simpler background for mobile */
            }

            h2 {
                font-size: 1.3rem;
                margin-bottom: 15px;
            }

            .order-card {
                padding: 12px;
                margin-bottom: 15px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                font-size: 0.85rem;
            }

            .download-btn, .print-btn {
                align-self: flex-end;
                padding: 8px 12px;
                font-size: 0.85rem;
            }

            /* Sembunyikan tabel, tampilkan card produk */
            table {
                display: none;
            }

            .products-container {
                display: block;
            }

            .total {
                text-align: center;
                font-size: 1rem;
                padding: 12px;
            }

            .delete-btn {
                display: block;
                text-align: center;
                width: 100%;
                margin-top: 15px;
                padding: 12px;
                font-size: 1rem;
            }
        }

        /* Untuk layar sedang (tablet) */
        @media (min-width: 769px) and (max-width: 1024px) {
            .order-card {
                padding: 20px;
            }

            table {
                font-size: 1rem;
            }

            .thumb {
                max-width: 60px;
                max-height: 60px;
            }
        }

        /* Untuk layar besar (desktop) */
        @media (min-width: 1025px) {
            .order-card {
                padding: 25px;
            }

            table {
                font-size: 1rem;
            }

            .thumb {
                max-width: 70px;
                max-height: 70px;
            }
        }
    </style>
</head>
<body>

<h2>Riwayat Order<?= $sales_id ? " - Sales #$sales_id" : "" ?></h2>

<?php if (empty($orders)) { ?>
    <p>Belum ada transaksi untuk sales ini.</p>
<?php } ?>

<?php foreach ($orders as $ord) { ?>

<div class="order-card">
    <div class="order-header">
        <div><?= date('d M Y - H:i', strtotime($ord['created_at'])) ?></div>
        <div>
            <a class="print-btn" href="/index.php?path=print_order.php&id=<?= $ord['id'] ?>" target="_blank"><i class="fa-solid fa-print"></i></a>
            <a class="download-btn" href="/index.php?path=unduh_excel.php&id=<?= $ord['id'] ?>"><i class="fa-solid fa-download"></i></a>
        </div>
    </div>

    <!-- Tabel untuk desktop/tablet -->
    <table>
        <tr>
            <th>Produk</th>
            <th>Gambar</th>
            <th>Qty</th>
            <th>Harga</th>
            <th>Subtotal</th>
        </tr>

        <?php 
        $total = 0;
        if (isset($groupedItems[$ord['id']])) {
            foreach ($groupedItems[$ord['id']] as $it) {
                $total += $it['subtotal'];
        ?>
        <tr>
            <td><?= htmlspecialchars($it['nama_barang']) ?></td>
            <td>
                <?php if (!empty($it['gambar'])) { ?>
                    <img class="thumb" src="../../uploads/barang/<?= $it['gambar'] ?>" alt="Gambar Produk">
                <?php } else { ?>
                    Tidak Ada
                <?php } ?>
            </td>
            <td><?= $it['qty'] ?></td>
            <td>Rp <?= number_format($it['harga'], 0, ',', '.') ?></td>
            <td>Rp <?= number_format($it['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php } } else { ?>
        <tr>
            <td colspan="5">Tidak ada produk dalam order ini.</td>
        </tr>
        <?php } ?>
    </table>

    <!-- Container card produk untuk mobile -->
    <div class="products-container">
        <?php 
        if (isset($groupedItems[$ord['id']])) {
            foreach ($groupedItems[$ord['id']] as $it) {
        ?>
        <div class="product-card">
            <?php if (!empty($it['gambar'])) { ?>
                <img class="thumb" src="../../uploads/barang/<?= $it['gambar'] ?>" alt="Gambar Produk">
            <?php } else { ?>
                <div style="text-align: center; color: #95a5a6;">Tidak Ada Gambar</div>
            <?php } ?>
            <div class="product-name"><?= htmlspecialchars($it['nama_barang']) ?></div>
            <div class="product-details">
                <span>Qty: <?= $it['qty'] ?></span>
                <span class="product-price">Rp <?= number_format($it['harga'], 0, ',', '.') ?> | Subtotal: Rp <?= number_format($it['subtotal'], 0, ',', '.') ?></span>
            </div>
        </div>
        <?php } } else { ?>
        <div class="no-products">Tidak ada produk dalam order ini.</div>
        <?php } ?>
    </div>

    <div class="total">
        Total: Rp <?= number_format($total, 0, ',', '.') ?>
    </div>

    <br>
    <a class="delete-btn" href="/index.php?path=hapus_order.php&id=<?= $ord['id'] ?>"><i class="fa-solid fa-trash"></i></a>
</div>

<?php } ?>

</body>
</html>