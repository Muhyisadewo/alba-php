
<?php
include __DIR__.'/../../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ambil = $_POST['ambil'] ?? [];

    $conn->begin_transaction();

    try {
        $total_ambil = 0;

        foreach ($ambil as $barang_id => $qty_ambil) {
            $qty_ambil = intval($qty_ambil);

            if ($qty_ambil > 0) {
                // Update qty di gudang_pecahon table
                $sql_update_gudang_pecahon = "UPDATE gudang_pecahon SET qty = qty - ? WHERE id = ?";
                $stmt_update_gudang_pecahon = $conn->prepare($sql_update_gudang_pecahon);
                $stmt_update_gudang_pecahon->bind_param("ii", $qty_ambil, $barang_id);
                $stmt_update_gudang_pecahon->execute();
                $stmt_update_gudang_pecahon->close();

                $total_ambil += $qty_ambil;
            }
        }

        $conn->commit();

        echo "<script>alert('Barang berhasil diambil dari gudang pecahon!'); window.location.href='?path=gudang_pecahon';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Gagal mengambil barang: " . $e->getMessage() . "'); window.location.href='?path=gudang_pecahon';</script>";
    }
} else {
    header("Location: ?path=gudang_pecahon");
    exit();
}

$conn->close();
?>
