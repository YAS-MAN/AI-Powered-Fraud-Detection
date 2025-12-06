<?php
include 'koneksi.php';

// --- LOGIKA DELETE (HAPUS DATA) ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Trigger tr_before_delete_claim akan otomatis jalan di database
    $query = "DELETE FROM claims WHERE id = '$id'";
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data berhasil dihapus!'); window.location='history.php';</script>";
    }
}

// --- LOGIKA SEARCH (PENCARIAN) ---
$search = "";
$query_sql = "SELECT * FROM v_claim_report"; // Memanggil VIEW

if (isset($_GET['q'])) {
    $search = $_GET['q'];
    // Mencari berdasarkan nama atau diagnosa
    $query_sql = "SELECT * FROM v_claim_report 
                  WHERE customer_name LIKE '%$search%' 
                  OR diagnosis LIKE '%$search%'
                  ORDER BY analysis_date DESC";
}

$result = mysqli_query($conn, $query_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Admin Dashboard - Fraud History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Sembunyikan tombol saat dicetak */
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>

<body class="bg-light p-4">

    <div class="container bg-white p-4 shadow rounded">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>📊 Laporan Analisis Fraud</h2>
                <a href="index.php" class="btn btn-outline-primary btn-sm mt-2">← Kembali ke Input</a>
            </div>
            <button onclick="window.print()" class="btn btn-secondary no-print">🖨️ Cetak Laporan</button>
        </div>

        <form method="GET" class="row g-2 mb-3 no-print">
            <div class="col-auto">
                <input type="text" name="q" class="form-control" placeholder="Cari Nama / Diagnosa..." value="<?= $search ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Cari</button>
                <a href="history.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nama Pasien</th>
                    <th>Diagnosa</th>
                    <th>RS</th>
                    <th>Total Klaim</th>
                    <th>Skor</th>
                    <th>Kategori (Auto)</th>
                    <th class="no-print">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['claim_id'] ?></td>
                    <td><?= $row['customer_name'] ?></td>
                    <td><?= $row['diagnosis'] ?></td>
                    <td><?= $row['hospital_name'] ?></td>
                    <td>Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                    <td><?= round($row['final_score'], 1) ?>%</td>
                    <td>
                        <?php if($row['risk_category'] == 'HIGH RISK'): ?>
                            <span class="badge bg-danger">HIGH RISK</span>
                        <?php elseif($row['risk_category'] == 'SUSPICIOUS'): ?>
                            <span class="badge bg-warning text-dark">SUSPICIOUS</span>
                        <?php else: ?>
                            <span class="badge bg-success">SAFE</span>
                        <?php endif; ?>
                    </td>
                    <td class="no-print">
                        <a href="edit.php?id=<?= $row['claim_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        
                        <a href="history.php?hapus=<?= $row['claim_id'] ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Yakin hapus? Data akan di-backup otomatis oleh Trigger.')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>

</body>
</html>