<?php
include 'koneksi.php';

// Ambil Data Diagnosis & Stats untuk JS (Poin 2 & 6)
$diag_query = mysqli_query($conn, "SELECT * FROM diagnosis_stats");
$diag_data = [];
while ($row = mysqli_fetch_assoc($diag_query)) {
    $diag_data[$row['diagnosis']] = $row;
}

// Ambil Data Rumah Sakit untuk Autocomplete (Poin 4)
$hosp_query = mysqli_query($conn, "SELECT hospital_name FROM hospital_risk");
$hospitals = [];
while ($row = mysqli_fetch_assoc($hosp_query)) {
    $hospitals[] = $row['hospital_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fraud Detection System</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4"> <div class="hero-container">
            <div class="hero-left">
                <h1 class="hero-title">AI-Powered <br>Fraud Detection</h1>
                <p class="hero-sub">Sistem cerdas untuk mendeteksi klaim asuransi fiktif menggunakan Python & PHP.</p>
                <a href="history.php" class="btn btn-dark mt-3">📊 Buka Database (History)</a>
            </div>

            <div class="hero-right w-100"> <div class="form-card text-start">
                    <h3 class="form-title">Input Klaim Baru</h3>
                    
                    <form action="process.php" method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label>Nama Pasien</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Diagnosa</label>
                                <select name="diagnosis" id="diagnosisSelect" class="form-select" onchange="updateStats()" required>
                                    <option value="" disabled selected>-- Pilih Diagnosa --</option>
                                    <?php foreach ($diag_data as $diag => $data): ?>
                                        <option value="<?= $diag ?>"><?= $diag ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Total Biaya (Rp)</label>
                                <input type="text" name="amount_display" id="amountDisplay" class="form-control" placeholder="0" onkeyup="formatRupiah(this)" required>
                                <input type="hidden" name="amount" id="amountReal"> <small id="costEst" class="text-muted fst-italic d-block mt-1"></small>
                            </div>
                        </div>

                        <div class="mb-3 position-relative">
                            <label>Nama Rumah Sakit</label>
                            <input type="text" name="hospital_name" id="hospitalInput" class="form-control" oninput="showSuggestions()" required>
                            <div id="suggestBox" class="list-group position-absolute w-100 shadow" style="display:none; max-height: 200px; overflow-y: auto;"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Polis (Hari)</label>
                                <input type="number" name="policy_age" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Frekuensi</label>
                                <input type="number" name="frequency" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Rawat (Hari)</label>
                                <input type="number" name="treatment_days" class="form-control" value="0" required>
                                <small id="dayEst" class="text-muted fst-italic d-block mt-1"></small>
                            </div>
                        </div>
                        
                        <input type="hidden" name="doctor_name" value="Dr. Default"> <button type="submit" class="btn btn-primary w-100 mt-3">Analisa Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP disimpan ke JS Constant
        const diagStats = <?= json_encode($diag_data) ?>;
        const hospitals = <?= json_encode($hospitals) ?>;

        // --- Poin 2 & 6: Update Estimasi Biaya & Hari ---
// --- Update Estimasi Biaya & Hari ---
        function updateStats() {
            let selected = document.getElementById('diagnosisSelect').value;
            let data = diagStats[selected];
            
            if(data) {
                // Format angka ke rupiah
                let min = new Intl.NumberFormat('id-ID').format(data.min_cost);
                let max = new Intl.NumberFormat('id-ID').format(data.max_cost);
                
                // KITA PERSINGKAT TEXTNYA DAN PAKAI CLASS BARU (.est-text)
                document.getElementById('costEst').className = "text-muted fst-italic est-text";
                document.getElementById('costEst').innerHTML = `Range: <span class="text-primary">Rp ${min} - Rp ${max}</span>`;
                
                document.getElementById('dayEst').className = "text-muted fst-italic est-text";
                document.getElementById('dayEst').innerHTML = `Est: <span class="text-primary">${data.expected_min_days} - ${data.expected_max_days} Hari</span>`;
            }
        }

        // --- Poin 3: Format Rupiah (Visual ada titik, Real polosan) ---
        function formatRupiah(elem) {
            let val = elem.value.replace(/[^,\d]/g, '').toString();
            let split = val.split(',');
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            elem.value = rupiah;
            
            // Simpan angka murni ke hidden input untuk dikirim ke PHP
            document.getElementById('amountReal').value = val; 
        }

        // --- Poin 4: Autocomplete Rumah Sakit ---
        function showSuggestions() {
            let input = document.getElementById('hospitalInput').value.toLowerCase();
            let box = document.getElementById('suggestBox');
            box.innerHTML = '';
            
            if (input.length === 0) {
                box.style.display = 'none';
                return;
            }

            let filtered = hospitals.filter(h => h.toLowerCase().includes(input));
            
            if (filtered.length > 0) {
                box.style.display = 'block';
                filtered.forEach(hosp => {
                    let item = document.createElement('a');
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = hosp;
                    item.href = "#";
                    item.onclick = function(e) {
                        e.preventDefault();
                        document.getElementById('hospitalInput').value = hosp;
                        box.style.display = 'none';
                    }
                    box.appendChild(item);
                });
            } else {
                box.style.display = 'none';
            }
        }

        // Tutup suggest jika klik diluar
        document.addEventListener('click', function(e) {
            if (e.target.id !== 'hospitalInput') {
                document.getElementById('suggestBox').style.display = 'none';
            }
        });
    </script>
</body>
</html>