<?php
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil Data
    $name = $_POST['customer_name'];
    $diag = $_POST['diagnosis'];
    $amount = $_POST['amount']; // Ambil dari Hidden Input (Angka murni tanpa titik)
    $hosp = $_POST['hospital_name'];
    $pol_age = $_POST['policy_age'];
    $freq = $_POST['frequency'];
    $days = $_POST['treatment_days'];
    $doc = $_POST['doctor_name'];

    // 2. Insert Klaim (Create)
    $stmt = $conn->prepare("INSERT INTO claims (customer_name, diagnosis, amount, policy_age, frequency, hospital_name, treatment_days, doctor_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiisis", $name, $diag, $amount, $pol_age, $freq, $hosp, $days, $doc);
    
    if ($stmt->execute()) {
        $claim_id = $stmt->insert_id;
    } else {
        die("Error Insert Claim: " . $conn->error);
    }

    // 3. Panggil Python
    $command = "python fraud_cli.py \"$name\" \"$diag\" \"$amount\" \"$hosp\" \"$pol_age\" \"$freq\" \"$days\"";
    $output = shell_exec($command);
    $result_data = json_decode($output, true);

    if (isset($result_data['error'])) { die("Python Error: " . $result_data['error']); }

    $final_score = $result_data['score'];
    $details = $result_data['details'];
    $details_json = json_encode($details);

    // 4. Tentukan Kategori
    $category = "SAFE";
    if ($final_score > 75) $category = "HIGH RISK";
    else if ($final_score > 45) $category = "SUSPICIOUS";

    // 5. Insert Hasil Analisis
    $stmt_res = $conn->prepare("INSERT INTO results (claim_id, final_score, category, details_json) VALUES (?, ?, ?, ?)");
    $stmt_res->bind_param("idss", $claim_id, $final_score, $category, $details_json);
    $stmt_res->execute();

    // --- Poin 12: AUTO-UPDATE Database jika HIGH RISK ---
    if ($category == "HIGH RISK") {
        // A. Masukkan ke database kasus fraud (fraud_cases)
        // Kita simpan untuk pembelajaran mesin ke depannya (CBR)
        $desc = "Detected by System: High Score ($final_score%)";
        $stmt_fraud = $conn->prepare("INSERT INTO fraud_cases (diagnosis, amount, policy_age, frequency, hospital_name, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_fraud->bind_param("siisss", $diag, $amount, $pol_age, $freq, $hosp, $desc);
        $stmt_fraud->execute();

        // B. Update Statistik Risiko Rumah Sakit (hospital_risk)
        // Cek dulu apakah RS sudah ada di list
        $check_hosp = mysqli_query($conn, "SELECT * FROM hospital_risk WHERE hospital_name = '$hosp'");
        
        if (mysqli_num_rows($check_hosp) > 0) {
            // Jika ada, update jumlah kasus
            mysqli_query($conn, "UPDATE hospital_risk SET total_cases = total_cases + 1, fraud_cases = fraud_cases + 1 WHERE hospital_name = '$hosp'");
        } else {
            // Jika RS baru, buat data baru (Risk Score default tinggi karena kasus pertamanya fraud)
            mysqli_query($conn, "INSERT INTO hospital_risk (hospital_name, total_cases, fraud_cases) VALUES ('$hosp', 1, 1)");
        }
    } else {
        // Jika SAFE/SUSPICIOUS, tetap update total_cases di RS (tapi fraud_cases tidak nambah)
        $check_hosp = mysqli_query($conn, "SELECT * FROM hospital_risk WHERE hospital_name = '$hosp'");
        if (mysqli_num_rows($check_hosp) > 0) {
            mysqli_query($conn, "UPDATE hospital_risk SET total_cases = total_cases + 1 WHERE hospital_name = '$hosp'");
        } else {
            mysqli_query($conn, "INSERT INTO hospital_risk (hospital_name, total_cases, fraud_cases) VALUES ('$hosp', 1, 0)");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Analysis Result</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="card shadow p-4 mx-auto" style="max-width: 900px;">
            <h3 class="mb-3">Fraud Analysis Result for <span class="text-primary"><?= htmlspecialchars($name) ?></span></h3>
            
            <div class="alert <?= $category == 'HIGH RISK' ? 'alert-danger' : ($category == 'SUSPICIOUS' ? 'alert-warning' : 'alert-success') ?>">
                <h4 class="alert-heading fw-bold mb-0">
                    Final Score: <?= round($final_score, 2) ?>% (<?= $category ?>)
                </h4>
            </div>

            <h5 class="mt-4">Breakdown Analysis</h5>
            <div class="row">
                <div class="col-md-6">
                    <ul>
                        <li>Bayesian Probability: <b><?= round($details['bayesian_probability'], 2) ?>%</b></li>
                        <li>Anomaly Score: <b><?= round($details['anomaly_score'], 2) ?>%</b></li>
                        <li>Hospital Risk: <b><?= round($details['hospital_risk'], 2) ?>%</b></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul>
                        <li>Diagnosis Severity: <b><?= $details['diagnosis_severity'] ?></b></li>
                        <li>CBR Similarity: <b><?= round($details['cbr_similarity'], 2) ?>%</b></li>
                        <li>Rule Flags: 
                            <?php foreach($details['rule_flags'] as $flag): ?>
                                <span class="badge bg-secondary"><?= $flag ?></span>
                            <?php endforeach; ?>
                        </li>
                    </ul>
                </div>
            </div>

            <h5 class="mt-3">Reasons</h5>
            <ul>
                <?php foreach ($details['explanation'] as $reason): ?>
                    <li class="text-danger"><?= $reason ?></li>
                <?php endforeach; ?>
            </ul>

            <h5 class="mt-4">Most Similar Fraud Cases (Reference)</h5>
            <table class="table table-bordered table-sm mt-2">
                <thead class="table-light">
                    <tr>
                        <th>Similarity</th>
                        <th>Diagnosis</th>
                        <th>Amount</th>
                        <th>Freq</th>
                        <th>Polis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($details['top3_cases'])): ?>
                        <?php foreach ($details['top3_cases'] as $case): 
                            // Python mengembalikan array [score, dict_case]
                            $sim = $case[0] * 100;
                            $data = $case[1];
                        ?>
                        <tr>
                            <td><?= round($sim, 1) ?>%</td>
                            <td><?= $data['diagnosis'] ?></td>
                            <td>Rp <?= number_format($data['amount'], 0, ',', '.') ?></td>
                            <td><?= $data['frequency'] ?></td>
                            <td><?= $data['policy_age'] ?> hari</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No similar historical cases found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="mt-4 d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn btn-secondary flex-fill">🖨️ Cetak PDF</button>
                <a href="index.php" class="btn btn-primary flex-fill">Input Baru</a>
                <a href="history.php" class="btn btn-outline-dark flex-fill">Lihat Database</a>
            </div>
        </div>
    </div>
</body>
</html>