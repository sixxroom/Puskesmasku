<?php
session_start();

// 1. Harus login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php?force=1");
    exit();
}

// 2. Role harus pasien
if ($_SESSION['role'] !== 'pasien') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// 3. Pastikan sudah isi biodata pasien
if (!isset($_SESSION['pasien_id'])) {
    header("Location: tambah_pasien.php");
    exit();
}

require "../config/db.php";

// Ambil data dokter
$dokterStmt = $conn->query("SELECT * FROM dokter ORDER BY id DESC");
$dokters = $dokterStmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data obat
$obatStmt = $conn->query("SELECT * FROM obat ORDER BY id DESC");
$obats = $obatStmt->fetchAll(PDO::FETCH_ASSOC);

// Jika form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!empty($_POST['dokter_id']) && !empty($_POST['obat_id'])) {

        $_SESSION['dokter_id'] = $_POST['dokter_id'];
        $_SESSION['obat_id'] = $_POST['obat_id'];

        header("Location: tambah_transaksi.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Layanan | Puskesmas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 650px;
            width: 100%;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 35px;
            text-align: center;
            color: white;
        }

        .form-header .icon {
            font-size: 3rem;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .form-header h1 {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .form-body {
            padding: 35px;
        }

        .info-box {
            background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }

        .info-box p {
            color: #5a67d8;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .label-icon {
            font-size: 1.2rem;
        }

        .form-select {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-select option {
            padding: 10px;
        }

        .form-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .selection-preview {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            border: 2px dashed #667eea;
            display: none;
        }

        .selection-preview.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .selection-preview h3 {
            color: #667eea;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .selection-preview p {
            color: #555;
            font-size: 0.85rem;
            margin: 5px 0;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .service-count {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-around;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .count-item {
            text-align: center;
        }

        .count-item .number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .count-item .label {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .form-header {
                padding: 30px 20px;
            }

            .form-body {
                padding: 25px 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .service-count {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-card">
            <!-- Form Header -->
            <div class="form-header">
                <div class="icon">🏥</div>
                <h1>Pilih Layanan Kesehatan</h1>
                <p>Pilih dokter dan obat untuk konsultasi Anda</p>
            </div>

            <!-- Form Body -->
            <div class="form-body">
                <!-- Info Box -->
                <div class="info-box">
                    <p>💡 <strong>Informasi:</strong> Silakan pilih dokter spesialis sesuai keluhan Anda dan obat yang dibutuhkan untuk melanjutkan proses transaksi.</p>
                </div>

                <!-- Service Count -->
                <div class="service-count">
                    <div class="count-item">
                        <span class="number"><?= count($dokters) ?></span>
                        <span class="label">Dokter Tersedia</span>
                    </div>
                    <div class="count-item">
                        <span class="number"><?= count($obats) ?></span>
                        <span class="label">Obat Tersedia</span>
                    </div>
                </div>

                <form method="POST" id="formLayanan">
                    <!-- Pilih Dokter -->
                    <div class="form-group">
                        <label>
                            <span class="label-icon">👨‍⚕️</span>
                            Pilih Dokter
                        </label>
                        <select name="dokter_id" id="dokter_id" class="form-select" required>
                            <option value="">-- Pilih Dokter --</option>
                            <?php foreach ($dokters as $d): ?>
                                <option value="<?= $d['id'] ?>" data-nama="<?= htmlspecialchars($d['nama']) ?>" data-spesialis="<?= htmlspecialchars($d['spesialis']) ?>">
                                    <?= htmlspecialchars($d['nama']) ?> - <?= htmlspecialchars($d['spesialis']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">
                            ℹ️ Pilih dokter berdasarkan spesialisasi yang sesuai
                        </div>
                    </div>

                    <!-- Pilih Obat -->
                    <div class="form-group">
                        <label>
                            <span class="label-icon">💊</span>
                            Pilih Obat
                        </label>
                        <select name="obat_id" id="obat_id" class="form-select" required>
                            <option value="">-- Pilih Obat --</option>
                            <?php foreach ($obats as $o): ?>
                                <option value="<?= $o['id'] ?>" data-nama="<?= htmlspecialchars($o['nama_obat']) ?>">
                                    <?= htmlspecialchars($o['nama_obat']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">
                            ℹ️ Pilih obat yang dibutuhkan sesuai resep
                        </div>
                    </div>

                    <!-- Selection Preview -->
                    <div class="selection-preview" id="previewBox">
                        <h3>📋 Ringkasan Pilihan Anda:</h3>
                        <p><strong>Dokter:</strong> <span id="previewDokter">-</span></p>
                        <p><strong>Obat:</strong> <span id="previewObat">-</span></p>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="../pasien/tambah_pasien.php" class="btn btn-secondary">
                            ← Kembali ke Daftar Pasien
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                            Lanjutkan →
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const dokterSelect = document.getElementById('dokter_id');
        const obatSelect = document.getElementById('obat_id');
        const previewBox = document.getElementById('previewBox');
        const previewDokter = document.getElementById('previewDokter');
        const previewObat = document.getElementById('previewObat');
        const btnSubmit = document.getElementById('btnSubmit');
        const form = document.getElementById('formLayanan');

        function updatePreview() {
            const dokterSelected = dokterSelect.value;
            const obatSelected = obatSelect.value;

            if (dokterSelected && obatSelected) {
                const dokterOption = dokterSelect.options[dokterSelect.selectedIndex];
                const obatOption = obatSelect.options[obatSelect.selectedIndex];

                previewDokter.textContent = dokterOption.getAttribute('data-nama') + ' - ' + dokterOption.getAttribute('data-spesialis');
                previewObat.textContent = obatOption.getAttribute('data-nama');

                previewBox.classList.add('active');
                btnSubmit.disabled = false;
            } else {
                previewBox.classList.remove('active');
                btnSubmit.disabled = true;
            }
        }

        dokterSelect.addEventListener('change', updatePreview);
        obatSelect.addEventListener('change', updatePreview);

        form.addEventListener('submit', function(e) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '⏳ Memproses...';
        });
    </script>
</body>

</html>