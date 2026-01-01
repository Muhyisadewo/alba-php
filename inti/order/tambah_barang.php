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
<style>
    /* Reset dan base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
    color: #f0f0f0;
    min-height: 100vh;
    padding: 15px;
    line-height: 1.6;
}

.container {
    max-width: 100%;
    margin: 0 auto;
    background-color: rgba(26, 26, 26, 0.9);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 215, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #D4AF37, #FFD700, #D4AF37);
    border-radius: 16px 16px 0 0;
}

/* Heading */
h2 {
    color: #FFD700;
    text-align: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(255, 215, 0, 0.3);
    font-weight: 600;
    font-size: 1.8rem;
    letter-spacing: 1px;
}

/* Info box */
.info-box {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(26, 26, 26, 0.8));
    border-left: 4px solid #D4AF37;
    padding: 18px;
    margin-bottom: 25px;
    border-radius: 0 10px 10px 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.info-box p {
    margin-bottom: 10px;
    font-size: 1rem;
}

.info-box strong {
    color: #FFD700;
    font-weight: 600;
}

/* Tombol */
.btn {
    display: inline-block;
    background: linear-gradient(to right, #D4AF37, #FFD700);
    color: #1a1a1a;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    text-align: center;
    margin-bottom: 25px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(212, 175, 55, 0.3);
    width: 100%;
    font-size: 1rem;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(212, 175, 55, 0.5);
}

/* Form */
form {
    background-color: rgba(40, 40, 40, 0.7);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid rgba(255, 215, 0, 0.15);
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #FFD700;
    font-size: 1rem;
}

input[type="text"],
input[type="number"],
input[type="file"] {
    width: 100%;
    padding: 14px;
    margin-bottom: 20px;
    background-color: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 8px;
    color: #f0f0f0;
    font-size: 1rem;
    transition: all 0.3s ease;
}

input:focus {
    outline: none;
    border-color: #FFD700;
    box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
}

input[type="file"] {
    padding: 10px;
    background-color: rgba(255, 215, 0, 0.1);
}

/* Tombol submit */
button[type="submit"] {
    background: linear-gradient(to right, #D4AF37, #FFD700);
    color: #1a1a1a;
    border: none;
    padding: 16px;
    width: 100%;
    border-radius: 50px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
    letter-spacing: 1px;
}

button[type="submit"]:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(212, 175, 55, 0.5);
}

/* Responsiveness untuk layar lebih besar */
@media (min-width: 576px) {
    .container {
        max-width: 540px;
        padding: 30px;
        margin: 30px auto;
    }
    
    .btn {
        width: auto;
        display: inline-block;
    }
}

/* Animasi untuk efek loading */
@keyframes shimmer {
    0% { background-position: -200px 0; }
    100% { background-position: 200px 0; }
}

.loading {
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.1) 25%, rgba(255, 215, 0, 0.2) 50%, rgba(255, 215, 0, 0.1) 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s infinite;
}

/* Placeholder styling */
::placeholder {
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
}

/* File input custom styling */
input[type="file"]::file-selector-button {
    background: linear-gradient(to right, #D4AF37, #FFD700);
    color: #1a1a1a;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    margin-right: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

input[type="file"]::file-selector-button:hover {
    background: linear-gradient(to right, #FFD700, #D4AF37);
}

/* Scrollbar styling */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(26, 26, 26, 0.8);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #D4AF37, #FFD700);
    border-radius: 4px;
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
