<?php
include __DIR__ . '/../../config.php';

/* ===============================
   1. VALIDASI SALES ID
================================ */
if (!isset($_GET['sales_id']) || !is_numeric($_GET['sales_id'])) {
    die("Sales tidak ditemukan.");
}

$sales_id = (int) $_GET['sales_id'];

/* ===============================
   2. AMBIL DATA SALES
================================ */
$stmtSales = $conn->prepare("
    SELECT id, nama_sales, perusahaan 
    FROM sales 
    WHERE id = ?
");
$stmtSales->bind_param("i", $sales_id);
$stmtSales->execute();
$sales = $stmtSales->get_result()->fetch_assoc();
$stmtSales->close();

if (!$sales) {
    die("Data sales tidak ditemukan.");
}

/* ===============================
   3. AMBIL SEMUA BARANG MILIK SALES
================================ */
$sql = "
    SELECT
        id,
        nama_barang,
        harga_ambil,
        qty,
        gambar
    FROM daftar_barang
    WHERE sales_id = ?
    ORDER BY nama_barang ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$barang = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang - <?= htmlspecialchars($sales['nama_sales']) ?></title>
    <style>
        body {
            background-color: #437057;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 1rem;
            font-family: Arial, sans-serif;
        }
        .container {
            background-color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            padding: 1.5rem;
            width: 100%;
            max-width: 48rem;
        }
        @media (min-width: 768px) {
            .container {
                max-width: 56rem;
            }
        }
        @media (min-width: 1024px) {
            .container {
                max-width: 64rem;
            }
        }
        /* Responsif untuk layar kecil: penuhi semua layar */
        @media (max-width: 767px) {
            body {
                padding: 0;
            }
            .container {
                width: 100%;
                max-width: none;
                padding: 1rem;
                border-radius: 0;
                box-shadow: none;
            }
        }
        h2 {
            font-size: 1.5rem;
            font-weight: bold;
            color: #437057;
            margin-bottom: 1rem;
            text-align: center;
        }
        @media (min-width: 768px) {
            h2 {
                font-size: 1.875rem;
            }
        }
        .info-box {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .info-box p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
            color: #374151;
        }
        .btn {
            display: inline-block;
            background-color: #437057;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #365a46;
        }
        .btn:focus {
            outline: 2px solid #437057;
            outline-offset: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #d1d5db;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        tr:hover {
            background-color: #f9fafb;
        }
        img {
            max-width: 55px;
            height: 55px;
            border-radius: 0.25rem;
            object-fit: cover;
        }
        .aksi-btn {
            display: flex;
            gap: 0.25rem;
        }
        .aksi-btn a {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .edit-btn {
            background-color: #3b82f6;
            color: white;
        }
        .edit-btn:hover {
            background-color: #2563eb;
        }
        .delete-btn {
            background-color: #ef4444;
            color: white;
        }
        .delete-btn:hover {
            background-color: #dc2626;
        }
        /* Responsif tabel */
        @media (max-width: 767px) {
            table {
                font-size: 0.65rem;
            }
            th, td {
                padding: 0.5rem;
            }
            .aksi-btn {
                flex-direction: column;
                gap: 0.125rem;
            }
            .aksi-btn a {
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <h2>Daftar Barang - <?= htmlspecialchars($sales['nama_sales']) ?></h2>

    <div class="info-box">
        <p><strong>Nama Sales:</strong> <?= htmlspecialchars($sales['nama_sales']) ?></p>
        <p><strong>Supplier / Perusahaan:</strong> <?= htmlspecialchars($sales['perusahaan']) ?></p>
    </div>

    <a class="btn" href="/index.php">Kembali</a>
    <a class="btn" href="?path=tambah_barang&sales_id=<?= $sales_id ?>">Tambah Barang</a>

    <table>
        <tr>
            <th>No</th>
            <th>Nama Barang</th>
            <th>Harga</th>
            <th>Qty</th>
            <th>Subt</th>
            <th>Foto</th>
            <th>Aksi</th>
        </tr>

        <?php
        if ($barang->num_rows > 0) {
            $no = 1;
            while ($row = $barang->fetch_assoc()) {
                $subtotal = $row['qty'] * $row['harga_ambil'];
        ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
            <td>Rp <?= number_format($row['harga_ambil'],0,',','.'); ?></td>
            <td><?= $row['qty']; ?></td>
            <td>Rp <?= number_format($subtotal,0,',','.'); ?></td>
            <td>
                <?php if (!empty($row['gambar'])): ?>
                    <img 
                        src="../../uploads/barang/<?= rawurlencode($row['gambar']) ?>"
                        width="55" height="55"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                    >
                    <span style="display:none;color:red;">Gambar tidak ditemukan</span>
                <?php else: ?>
                    Belum ada
                <?php endif; ?>
            </td>
            <td class="aksi-btn">
                <a class="edit-btn" href="?path=edit_barang&id=<?= $row['id'] ?>">Edit</a>
                <a class="delete-btn" href="?path=hapus_order_barang&id=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus barang ini?')">Hapus</a>
            </td>
        </tr>
        <?php } } else { ?>
        <tr>
            <td colspan="7">Belum ada barang untuk sales ini</td>
        </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>

<?php $conn->close(); ?>
