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
    /* --- Global Styles & Reset --- */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #437057; /* Latar belakang abu-abu muda */
    color: #333;
    line-height: 1.6;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Mulai dari atas */
    min-height: 100vh;
}

.container {
    background-color: #ffffff; /* Latar belakang kontainer putih */
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* Bayangan lembut */
    max-width: 100%;
    width: 90%;
}

/* --- Typography --- */
h2 {
    color:  #437057; /* Biru cerah untuk judul */
    text-align: center;
    font-weight: 600;
    border-bottom: 2px solid #e9ecef;
    font-size: 2.8em;
}

/* --- Info Box (Detail Sales) --- */
.info-box {
    background-color: #e9f5ff; /* Latar belakang biru sangat muda */
    border: 1px solid #cce5ff;
    border-left: 5px solid #437057; /* Garis biru di kiri */
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 6px;
    font-size: 1.3em;
}

.info-box p {
    margin: 5px 0;
}

.info-box strong {
    font-size: 1.1em;
    color:  #437057;
    font-weight: 700;
}

/* --- Form Styling --- */
form {
    display: flex;
    flex-direction: column;
    font-size: 1.8em;
}

label {
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
    margin-top: 15px;
    font-size: 1.1em;
}

input[type="text"],
input[type="number"],
input[type="file"] {
    width: 100%;
    font-size: 1.1em;
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    box-sizing: border-box; /* Penting untuk padding */
    transition: border-color 0.3s, box-shadow 0.3s;
}

input[type="text"]:focus,
input[type="number"]:focus {
    border-color:  #437057;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    outline: none;
}

/* Kustomisasi input file agar terlihat lebih baik */
input[type="file"] {
    padding: 12px 0; /* Hapus padding horizontal bawaan */
    border: none;
    background-color: transparent;
}
/* Menghilangkan border dan background bawaan pada input file */
input[type="file"]::-webkit-file-upload-button {
    background:  #437057;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin-right: 10px;
}

input[type="file"]::-webkit-file-upload-button:hover {
    background: #0056b3;
}


/* --- Buttons (Link dan Submit) --- */
.btn,
button[type="submit"] {
    display: inline-block;
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1em;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    transition: background-color 0.3s, transform 0.1s;
    margin-top: 10px;
}

/* Tombol Kembali (Link) */
.btn {
    background-color: #6c757d; /* Abu-abu netral */
    color: white;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.btn:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

/* Tombol Simpan Barang (Submit) */
button[type="submit"] {
    background-color: #437057; /* Hijau sukses */
    color: white;
    margin-top: 25px;
    font-size: 1.3em;
}

button[type="submit"]:hover {
    background-color: #1e7e34;
    transform: translateY(-1px);
}

/* --- Responsiveness (Untuk layar kecil) --- */
/* ... (Bagian CSS Global di atas tetap sama) ... */

/* --- Responsiveness (Untuk layar kecil) --- */
@media (max-width: 600px) {
    /* Mengatur padding body untuk tampilan mobile */
    body {
        padding: 20px 10px;
        align-items: flex-start;
    }

    .container {
        padding: 20px;
        width: 100%;
        max-width: none;
    }

    /* 1. Judul Halaman */
    h2 {
        font-size: 2.0em; /* Dibuat lebih besar dari 1.5em yang Anda set sebelumnya */
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    /* 2. Info Box (Sales Detail) */
    .info-box {
        padding: 18px;
        margin-bottom: 20px;
        font-size: 1.05em; /* Ukuran font detail diperbesar */
    }

    .info-box p {
        margin: 8px 0;
    }

    .info-box strong {
        font-size: 1.15em; /* Teks tebal dibuat sangat menonjol */
    }

    /* 3. Label dan Input Form */
    form {
        font-size: 1em; /* Mengatur dasar ukuran font form */
    }

    label {
        font-size: 1.1em; /* Label lebih besar */
        margin-top: 10px;
        margin-bottom: 5px;
    }

    input[type="text"],
    input[type="number"] {
        padding: 14px; /* Padding Input ditingkatkan untuk target sentuh lebih besar */
        font-size: 1.15em; /* Ukuran teks di dalam input ditingkatkan */
    }
    
    /* 4. Tombol */
    .btn,
    button[type="submit"] {
        width: 100%; /* Tombol menjadi penuh */
        padding: 15px 0; /* Padding vertikal tombol ditingkatkan */
        font-size: 1.15em; /* Ukuran teks tombol ditingkatkan */
        margin-left: 0;
        margin-right: 0;
    }
    
    .btn {
        margin-bottom: 15px;
    }
    
    button[type="submit"] {
        margin-top: 20px;
    }

    /* Kustomisasi tombol input file untuk mobile */
    input[type="file"]::-webkit-file-upload-button {
        padding: 10px 18px;
        font-size: 1em;
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
<a class="btn" href="?path=daftar_barang.php?sales_id=<?= $sales_id ?>">Kembali</a>
<form action="?path=proses_tambah_barang.php" method="POST" enctype="multipart/form-data">

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
