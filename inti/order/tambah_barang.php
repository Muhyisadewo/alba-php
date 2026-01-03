<?php
include __DIR__ . '/../../config.php';

// Validasi parameter sales_id
if (!isset($_GET['sales_id']) || !is_numeric($_GET['sales_id'])) {
    die("Sales tidak ditemukan.");
}

$sales_id = strval($_GET['sales_id']);

// Ambil data sales (sesuai tabel sales yang Anda punya)
$stmtSales = $conn->prepare("
    SELECT nama_sales, perusahaan
    FROM sales
    WHERE id = ?
");
$stmtSales->bind_param("s", $sales_id);
$stmtSales->execute();
$sales = $stmtSales->get_result()->fetch_assoc();

if (!$sales) {
    echo "Debug: sales_id = $sales_id<br>";
    echo "Debug: query error: " . $stmtSales->error . "<br>";
    die("Data sales tidak ditemukan.");
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Tambah Barang</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    /* Mobile-first CSS untuk layar HP kecil */

    /* Reset dan base styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        background-color: #f5f5f5;
        color: #333;
        line-height: 1.6;
        padding: 10px;
    }

    .container {
        max-width: 100%;
        margin: 0 auto;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    h2 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 1.5rem;
        text-align: center;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
    }

    .info-box {
        background: #ecf0f1;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 15px;
        border-left: 4px solid #3498db;
    }

    .info-box p {
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    .info-box strong {
        color: #2c3e50;
    }

    .btn {
        display: inline-block;
        background: #3498db;
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 5px;
        margin-bottom: 15px;
        font-size: 0.9rem;
        transition: background 0.3s ease;
        width: 100%;
        text-align: center;
    }

    .btn:hover {
        background: #2980b9;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    input[type="text"],
    input[type="number"],
    input[type="file"] {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="number"]:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
    }

    input[type="file"] {
        padding: 8px;
        background: #f9f9f9;
    }

    button[type="submit"] {
        background: #27ae60;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s ease;
        width: 100%;
    }

    button[type="submit"]:hover {
        background: #229954;
    }

    /* Media queries untuk layar yang lebih besar */
    @media (min-width: 576px) {
        body {
            padding: 20px;
        }

        .container {
            max-width: 500px;
            padding: 20px;
        }

        h2 {
            font-size: 1.8rem;
        }

        .btn {
            width: auto;
            display: inline-block;
        }

        button[type="submit"] {
            width: auto;
        }
    }

    @media (min-width: 768px) {
        .container {
            max-width: 600px;
        }

        form {
            gap: 20px;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"] {
            padding: 15px;
        }

        button[type="submit"] {
            padding: 15px 30px;
        }
    }
</style>
</head>
<body>
<div class="container">
<h2>Tambah Barang</h2>
<div class="info-box">
    <p><strong>Nama Sales:</strong> <?= htmlspecialchars($sales['nama_sales']); ?></p>
    <p><strong>Supplier / Perusahaan:</strong> <?= htmlspecialchars($sales['perusahaan']); ?></p>
</div>
<a class="btn" href="?path=daftar_barang&sales_id=<?= $sales_id ?>">Kembali</a>
<form action="?path=proses_tambah_barang" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="sales_id" value="<?= $sales_id ?>">

    <label>Nama Barang</label>
    <input type="text" name="nama_barang" required>

    <label>Harga Ambil</label>
    <input type="number" name="harga_ambil" required>

    <label>Qty</label>
    <input type="number" name="qty" required>

    <label>Gambar</label>
    <input type="file" name="gambar">

    <button type="submit">Simpan Barang</button>

</form>

</div>

</body>
</html>
