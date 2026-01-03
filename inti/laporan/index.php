<?php
// Pastikan file config.php sudah ada dan koneksi database berjalan
include __DIR__ . '/../../config.php';

// Query untuk ringkasan metrik utama
$totalBarangQuery = "SELECT COUNT(*) as total FROM gudang";
$totalBarangResult = $conn->query($totalBarangQuery);
$totalBarang = $totalBarangResult->fetch_assoc()['total'];

$totalPesananQuery = "SELECT COUNT(*) as total FROM orders";
$totalPesananResult = $conn->query($totalPesananQuery);
$totalPesanan = $totalPesananResult->fetch_assoc()['total'];

$totalReturQuery = "SELECT COUNT(*) as total FROM returs";
$totalReturResult = $conn->query($totalReturQuery);
$totalRetur = $totalReturResult->fetch_assoc()['total'];

$totalSupplierQuery = "SELECT COUNT(*) as total FROM supplier";
$totalSupplierResult = $conn->query($totalSupplierQuery);
$totalSupplier = $totalSupplierResult->fetch_assoc()['total'];

$totalSalesQuery = "SELECT COUNT(*) as total FROM sales";
$totalSalesResult = $conn->query($totalSalesQuery);
$totalSales = $totalSalesResult->fetch_assoc()['total'];

// Query untuk transaksi terbaru (orders)
$recentOrdersQuery = "SELECT o.id, o.tanggal_order, o.total_harga, s.nama_sales
                      FROM orders o
                      LEFT JOIN sales s ON o.sales_id = s.id
                      ORDER BY o.tanggal_order DESC LIMIT 5";
$recentOrdersResult = $conn->query($recentOrdersQuery);

// Query untuk barang dengan stok rendah (qty < 10)
$lowStockQuery = "SELECT nama_barang, qty FROM gudang WHERE qty < 10 ORDER BY qty ASC LIMIT 5";
$lowStockResult = $conn->query($lowStockQuery);

// Query untuk retur terbaru
$recentRetursQuery = "SELECT r.id, r.created_at, r.qty, db.nama_barang
                      FROM returs r
                      LEFT JOIN daftar_barang db ON r.daftar_barang_id = db.id
                      ORDER BY r.created_at DESC LIMIT 5";
$recentRetursResult = $conn->query($recentRetursQuery);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Index - ALBA TOSERBA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --color-primary: #437057;
            --color-secondary: #f0f0f0;
            --color-accent: #C8993F;
            --color-text-dark: #212529;
            --color-text-light: #ffffff;
            --shadow-subtle: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        body {
            background-color: var(--color-secondary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background-color: var(--color-text-light);
            box-shadow: var(--shadow-subtle);
            padding: 15px 0;
        }

        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .metric-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-subtle);
            transition: transform 0.3s ease;
            border: none;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .metric-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--color-primary);
        }

        .metric-label {
            color: var(--color-text-dark);
            font-weight: 600;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-subtle);
            margin-bottom: 20px;
        }

        .recent-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .alert-custom {
            border-radius: 12px;
            border: none;
        }

        @media (max-width: 767.98px) {
            .metric-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

    <header class="page-header">
        <div class="header-nav">
            <h3 class="mb-0" style="color: var(--color-primary); font-weight: 600;">
                <i class="fas fa-chart-line me-2"></i>Laporan Index
            </h3>
            <a href="?path=index.php" class="btn btn-secondary" title="Kembali ke Beranda">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </header>

    <div class="container mt-4">

        <!-- Ringkasan Metrik Utama -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card text-center">
                    <div class="metric-icon text-primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalBarang); ?></div>
                    <div class="metric-label">Total Barang</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card text-center">
                    <div class="metric-icon text-success">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalPesanan); ?></div>
                    <div class="metric-label">Total Pesanan</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card text-center">
                    <div class="metric-icon text-warning">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalRetur); ?></div>
                    <div class="metric-label">Total Retur</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card text-center">
                    <div class="metric-icon text-info">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalSupplier); ?></div>
                    <div class="metric-label">Total Supplier</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card text-center">
                    <div class="metric-icon text-danger">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalSales); ?></div>
                    <div class="metric-label">Total Sales</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card text-center">
                    <div class="metric-icon text-secondary">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="metric-value"><?php echo date('d/m'); ?></div>
                    <div class="metric-label">Tanggal Hari Ini</div>
                </div>
            </div>
        </div>

        <!-- Notifikasi/Alerts -->
        <?php if ($lowStockResult->num_rows > 0): ?>
        <div class="alert alert-warning alert-custom mb-4">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Peringatan Stok Rendah</h5>
            <p>Barang berikut memiliki stok rendah:</p>
            <ul class="mb-0">
                <?php while ($row = $lowStockResult->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($row['nama_barang']); ?> (Stok: <?php echo $row['qty']; ?>)</li>
                <?php endwhile; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Transaksi Terbaru -->
            <div class="col-lg-6 mb-4">
                <div class="chart-container">
                    <h5 class="mb-3" style="color: var(--color-primary);">
                        <i class="fas fa-clock me-2"></i>Pesanan Terbaru
                    </h5>
                    <div class="recent-list">
                        <?php if ($recentOrdersResult->num_rows > 0): ?>
                            <?php while ($row = $recentOrdersResult->fetch_assoc()): ?>
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['nama_sales'] ?? 'N/A'); ?></strong><br>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($row['tanggal_order'])); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">Belum ada pesanan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Retur Terbaru -->
            <div class="col-lg-6 mb-4">
                <div class="chart-container">
                    <h5 class="mb-3" style="color: var(--color-primary);">
                        <i class="fas fa-undo me-2"></i>Retur Terbaru
                    </h5>
                    <div class="recent-list">
                        <?php if ($recentRetursResult->num_rows > 0): ?>
                            <?php while ($row = $recentRetursResult->fetch_assoc()): ?>
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['nama_barang'] ?? 'N/A'); ?></strong><br>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-warning">Qty: <?php echo $row['qty']; ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">Belum ada retur.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigasi ke Laporan Detail -->
        <div class="row">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="mb-3" style="color: var(--color-primary);">
                        <i class="fas fa-file-alt me-2"></i>Laporan Detail
                    </h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="?path=order/riwayat_order.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-shopping-cart me-2"></i>Laporan Pesanan
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="?path=retur/index.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-undo me-2"></i>Laporan Retur
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="?path=gudang/index.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-boxes me-2"></i>Laporan Gudang
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="?path=order/riwayat_pembayaran.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-money-bill me-2"></i>Laporan Pembayaran
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
