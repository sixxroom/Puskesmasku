<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?force=1");
    exit();
}

require "../config/db.php";

$error = "";
$success = "";

// Ambil data untuk dropdown
$pasien    = $conn->query("SELECT id, nama FROM pasien ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
$dokter    = $conn->query("SELECT id, nama, spesialis FROM dokter ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
$obat      = $conn->query("SELECT id, nama_obat, stok, harga FROM obat WHERE stok > 0 ORDER BY nama_obat")->fetchAll(PDO::FETCH_ASSOC);
$penyakit  = $conn->query("SELECT id, nama_penyakit FROM penyakit ORDER BY nama_penyakit")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pasien_id   = $_POST['pasien_id']   ?? null;
        $dokter_id   = $_POST['dokter_id']   ?? null;
        $obat_id     = $_POST['obat_id']     ?? null;
        $penyakit_id = $_POST['penyakit_id'] ?? null;
        $qty         = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
        $tanggal     = date('Y-m-d');

        // Validasi
        if (!$pasien_id || !$dokter_id || !$obat_id || !$penyakit_id || $qty <= 0) {
            throw new Exception("Pastikan semua data sudah diisi dengan benar.");
        }

        // Ambil harga & stok obat
        $stmt = $conn->prepare("SELECT harga, stok, nama_obat FROM obat WHERE id = ?");
        $stmt->execute([$obat_id]);
        $ob = $stmt->fetch();

        if (!$ob) {
            throw new Exception("Obat tidak ditemukan.");
        }

        if ($ob['stok'] < $qty) {
            throw new Exception("Stok obat tidak cukup. Stok tersedia: " . $ob['stok']);
        }

        $biaya = $ob['harga'] * $qty;

        // Kurangi stok obat
        $stmt = $conn->prepare("UPDATE obat SET stok = stok - ? WHERE id = ?");
        $stmt->execute([$qty, $obat_id]);

        // Insert transaksi
        $stmt = $conn->prepare("
            INSERT INTO transaksi (pasien_id, dokter_id, obat_id, penyakit_id, tanggal, biaya)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$pasien_id, $dokter_id, $obat_id, $penyakit_id, $tanggal, $biaya]);

        header("Location: beranda.php?success=1");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Transaksi | Puskesmas</title>
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
            max-width: 700px;
            width: 100%;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .form-header .icon {
            font-size: 4rem;
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
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .form-body {
            padding: 40px 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            color: #1565c0;
        }

        .info-box .icon {
            margin-right: 8px;
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

        .form-group label .icon {
            font-size: 1.2rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        .required {
            color: #e74c3c;
            margin-left: 3px;
        }

        .form-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qty-control input {
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #667eea;
            background: white;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #667eea;
            font-weight: bold;
        }

        .qty-btn:hover {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }

        .price-preview {
            background: #f8f9ff;
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
            text-align: center;
        }

        .price-preview .label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .price-preview .value {
            color: #667eea;
            font-size: 2rem;
            font-weight: 700;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        /* Stock Badge */
        .stock-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .stock-high {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .stock-low {
            background: #fff3e0;
            color: #f57c00;
        }

        .stock-empty {
            background: #ffebee;
            color: #c62828;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .form-header {
                padding: 30px 20px;
            }

            .form-header h1 {
                font-size: 1.5rem;
            }

            .form-body {
                padding: 30px 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .qty-control {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-card">
            <!-- Form Header -->
            <div class="form-header">
                <div class="icon">📊</div>
                <h1>Tambah Transaksi Baru</h1>
                <p>Lengkapi formulir transaksi pelayanan kesehatan</p>
            </div>

            <!-- Form Body -->
            <div class="form-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <span class="icon">ℹ️</span>
                    <strong>Perhatian:</strong> Pastikan data pasien, dokter, obat, dan diagnosa penyakit sudah benar sebelum menyimpan transaksi.
                </div>

                <form method="POST" id="formTransaksi">
                    <!-- Pilih Pasien -->
                    <div class="form-group">
                        <label>
                            <span class="icon">🧑‍🤝‍🧑</span>
                            Pilih Pasien
                            <span class="required">*</span>
                        </label>
                        <select name="pasien_id" class="form-control" required>
                            <option value="">-- Pilih Pasien --</option>
                            <?php foreach ($pasien as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($pasien)): ?>
                            <div class="form-hint" style="color: #c62828;">
                                ⚠️ Belum ada data pasien. <a href="../pasien/create.php">Tambah pasien baru</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pilih Dokter -->
                    <div class="form-group">
                        <label>
                            <span class="icon">🩺</span>
                            Pilih Dokter
                            <span class="required">*</span>
                        </label>
                        <select name="dokter_id" class="form-control" required>
                            <option value="">-- Pilih Dokter --</option>
                            <?php foreach ($dokter as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= htmlspecialchars($d['nama']) ?>
                                    <?php if ($d['spesialis']): ?>
                                        - (<?= htmlspecialchars($d['spesialis']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($dokter)): ?>
                            <div class="form-hint" style="color: #c62828;">
                                ⚠️ Belum ada data dokter. <a href="../dokter/tambah.php">Tambah dokter baru</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pilih Penyakit -->
                    <div class="form-group">
                        <label>
                            <span class="icon">🦠</span>
                            Diagnosa Penyakit
                            <span class="required">*</span>
                        </label>
                        <select name="penyakit_id" class="form-control" required>
                            <option value="">-- Pilih Penyakit --</option>
                            <?php foreach ($penyakit as $py): ?>
                                <option value="<?= $py['id'] ?>"><?= htmlspecialchars($py['nama_penyakit']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($penyakit)): ?>
                            <div class="form-hint" style="color: #c62828;">
                                ⚠️ Belum ada data penyakit. <a href="../penyakit/create.php">Tambah penyakit baru</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pilih Obat -->
                    <div class="form-group">
                        <label>
                            <span class="icon">💊</span>
                            Pilih Obat
                            <span class="required">*</span>
                        </label>
                        <select name="dosis" class="form-control" required>
                            <option value="">-- Pilih Dosis --</option>
                            <option value="1x sehari">1x sehari</option>
                            <option value="2x sehari">2x sehari</option>
                            <option value="3x sehari">3x sehari</option>
                            <option value="Pagi - Siang - Malam">Pagi - Siang - Malam</option>
                            <option value="2x 1 hari">2x 1 hari</option>
                            <option value="Sesuai anjuran dokter">Sesuai anjuran dokter</option>
                        </select>
                        <select name="obat_id" id="obatSelect" class="form-control" required onchange="updatePrice()">
                            <option value="">-- Pilih Obat --</option>
                            <?php foreach ($obat as $o):
                                $stockClass = $o['stok'] > 50 ? 'stock-high' : ($o['stok'] > 20 ? 'stock-low' : 'stock-empty');
                            ?>
                                <option value="<?= $o['id'] ?>"
                                    data-harga="<?= $o['harga'] ?>"
                                    data-stok="<?= $o['stok'] ?>">
                                    <?= htmlspecialchars($o['nama_obat']) ?>
                                    - Rp <?= number_format($o['harga'], 0, ',', '.') ?>
                                    (Stok: <?= $o['stok'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($obat)): ?>
                            <div class="form-hint" style="color: #c62828;">
                                ⚠️ Tidak ada obat dengan stok tersedia. <a href="../obat/create.php">Tambah obat baru</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Jumlah -->
                    <div class="form-group">
                        <label>
                            <span class="icon">📦</span>
                            Jumlah Obat
                            <span class="required">*</span>
                        </label>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" onclick="decreaseQty()">−</button>
                            <input type="number"
                                name="qty"
                                id="qtyInput"
                                class="form-control"
                                value="1"
                                min="1"
                                style="max-width: 100px;"
                                required
                                onchange="updatePrice()">
                            <button type="button" class="qty-btn" onclick="increaseQty()">+</button>
                        </div>
                        <div class="form-hint">Pastikan jumlah tidak melebihi stok yang tersedia</div>
                    </div>

                    <!-- Price Preview -->
                    <div class="price-preview">
                        <div class="label">💰 Total Biaya</div>
                        <div class="value" id="totalBiaya">Rp 0</div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <span>←</span> Kembali Ke Pemilihan
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span>💾</span> Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Update price calculation
        function updatePrice() {
            const select = document.getElementById('obatSelect');
            const qtyInput = document.getElementById('qtyInput');
            const totalBiaya = document.getElementById('totalBiaya');

            if (select.value && qtyInput.value) {
                const selectedOption = select.options[select.selectedIndex];
                const harga = parseInt(selectedOption.getAttribute('data-harga'));
                const stok = parseInt(selectedOption.getAttribute('data-stok'));
                const qty = parseInt(qtyInput.value);

                // Validate quantity against stock
                if (qty > stok) {
                    alert(`Jumlah melebihi stok! Stok tersedia: ${stok}`);
                    qtyInput.value = stok;
                    return;
                }

                const total = harga * qty;
                totalBiaya.textContent = 'Rp ' + total.toLocaleString('id-ID');
            } else {
                totalBiaya.textContent = 'Rp 0';
            }
        }

        // Quantity controls
        function increaseQty() {
            const qtyInput = document.getElementById('qtyInput');
            const select = document.getElementById('obatSelect');

            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const stok = parseInt(selectedOption.getAttribute('data-stok'));
                const currentQty = parseInt(qtyInput.value);

                if (currentQty < stok) {
                    qtyInput.value = currentQty + 1;
                    updatePrice();
                } else {
                    alert('Jumlah sudah mencapai maksimal stok!');
                }
            } else {
                alert('Pilih obat terlebih dahulu!');
            }
        }

        function decreaseQty() {
            const qtyInput = document.getElementById('qtyInput');
            const currentQty = parseInt(qtyInput.value);

            if (currentQty > 1) {
                qtyInput.value = currentQty - 1;
                updatePrice();
            }
        }

        // Form submission
        document.getElementById('formTransaksi').addEventListener('submit', function(e) {
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span>⏳</span> Menyimpan...';
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updatePrice();
        });
    </script>
</body>

</html>