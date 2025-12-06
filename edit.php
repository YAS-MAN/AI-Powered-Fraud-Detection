<?php
include 'koneksi.php';

$id = $_GET['id'];
// Ambil data lama untuk ditampilkan di form
$data = mysqli_query($conn, "SELECT * FROM claims WHERE id = '$id'");
$row = mysqli_fetch_assoc($data);

if (isset($_POST['update'])) {
    $nama = $_POST['customer_name'];
    $diag = $_POST['diagnosis'];
    $rs   = $_POST['hospital_name'];
    $jml  = $_POST['amount'];

    // Panggil STORED PROCEDURE untuk update (Requirement SP)
    $query = "CALL sp_update_claim($id, '$nama', '$diag', $jml, '$rs')";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data Berhasil Diupdate!'); window.location='history.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Data Klaim</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

    <div class="card p-4 shadow" style="width: 400px;">
        <h3 class="mb-3 text-center">Edit Data Klaim</h3>
        <form method="POST">
            <div class="mb-3">
                <label>Nama Pasien</label>
                <input type="text" name="customer_name" class="form-control" value="<?= $row['customer_name'] ?>" required>
            </div>
            <div class="mb-3">
                <label>Diagnosa</label>
                <input type="text" name="diagnosis" class="form-control" value="<?= $row['diagnosis'] ?>" required>
            </div>
            <div class="mb-3">
                <label>Rumah Sakit</label>
                <input type="text" name="hospital_name" class="form-control" value="<?= $row['hospital_name'] ?>" required>
            </div>
            <div class="mb-3">
                <label>Jumlah Klaim (Rp)</label>
                <input type="number" name="amount" class="form-control" value="<?= $row['amount'] ?>" required>
            </div>
            
            <button type="submit" name="update" class="btn btn-primary w-100">Simpan Perubahan (Via Stored Proc)</button>
            <a href="history.php" class="btn btn-secondary w-100 mt-2">Batal</a>
        </form>
    </div>

</body>
</html>