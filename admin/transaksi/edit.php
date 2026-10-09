<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";

// Validate ID
if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$id = $_GET["id"];

// Get transaksi data
$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "Data transaksi tidak ditemukan!";
    exit();
}

// Dropdown data
$pasienList   = $conn->query("SELECT id, nama FROM pasien ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
$dokterList   = $conn->query("SELECT id, nama, spesialis FROM dokter ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
$obatList     = $conn->query("SELECT id, nama_obat AS nama FROM obat ORDER BY nama_obat")->fetchAll(PDO::FETCH_ASSOC);
$penyakitList = $conn->query("SELECT id, nama_penyakit FROM penyakit ORDER BY nama_penyakit")->fetchAll(PDO::FETCH_ASSOC);

// Update transaksi
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sql = "UPDATE transaksi 
            SET pasien_id=?, dokter_id=?, obat_id=?, penyakit_id=?, dosis=?, tanggal=?, biaya=?
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $_POST["pasien_id"],
        $_POST["dokter_id"],
        $_POST["obat_id"],
        $_POST["penyakit_id"],
        $_POST["dosis"],
        $_POST["tanggal"],
        $_POST["biaya"],
        $id
    ]);

    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi | Puskesmas</title>

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
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .form-body {
            padding: 30px;
        }

        .transaction-id {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            border: 2px dashed #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .info-badge {
            display: inline-block;
            background: #e8f4fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 5px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-card">

            <div class="form-header">
                <div class="icon">📋</div>
                <h1>Edit Data Transaksi</h1>
                <p>Perbarui informasi transaksi medis</p>
            </div>

            <div class="form-body">

                <div class="transaction-id">
                    <strong>ID Transaksi: #<?= htmlspecialchars($data['id']); ?></strong>
                </div>

                <form method="POST" id="formTransaksi">

                    <!-- Row 1 -->
                    <div class="form-row">

                        <div class="form-group">
                            <label>Pasien <span class="info-badge">Wajib</span></label>
                            <select name="pasien_id" class="form-control" required>
                                <option value="">Pilih Pasien</option>
                                <?php foreach ($pasienList as $p): ?>
                                    <option value="<?= $p['id']; ?>" <?= $data['pasien_id'] == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Dokter <span class="info-badge">Wajib</span></label>
                            <select name="dokter_id" class="form-control" required>
                                <option value="">Pilih Dokter</option>
                                <?php foreach ($dokterList as $d): ?>
                                    <option value="<?= $d['id']; ?>" <?= $data['dokter_id'] == $d['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['nama']); ?> - <?= htmlspecialchars($d['spesialis']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <!-- Row 2 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Penyakit <span class="info-badge">Wajib</span></label>
                            <select name="penyakit_id" class="form-control" required>
                                <option value="">Pilih Penyakit</option>
                                <?php foreach ($penyakitList as $pn): ?>
                                    <option value="<?= $pn['id']; ?>" <?= $data['penyakit_id'] == $pn['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pn['nama_penyakit']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Obat <span class="info-badge">Wajib</span></label>
                            <select name="obat_id" class="form-control" required>
                                <option value="">Pilih Obat</option>
                                <?php foreach ($obatList as $o): ?>
                                    <option value="<?= $o['id']; ?>" <?= $data['obat_id'] == $o['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($o['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Dosis -->
                    <div class="form-group">
                        <label>Dosis Obat <span class="info-badge">Wajib</span></label>
                        <select name="dosis" class="form-control" required>
                            <option value="">-- Pilih Dosis --</option>
                            <?php
                            $dosisOptions = [
                                "1x sehari",
                                "2x sehari",
                                "3x sehari",
                                "Pagi - Siang - Malam",
                                "Sesuai anjuran dokter"
                            ];

                            foreach ($dosisOptions as $dosis): ?>
                                <option value="<?= $dosis ?>" <?= $data['dosis'] == $dosis ? 'selected' : '' ?>>
                                    <?= $dosis ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Row 3 -->
                    <div class="form-row">

                        <div class="form-group">
                            <label>Tanggal Transaksi</label>
                            <input type="date" name="tanggal" class="form-control"
                                value="<?= htmlspecialchars($data['tanggal']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Biaya (Rp)</label>
                            <input type="number" name="biaya" class="form-control"
                                value="<?= htmlspecialchars($data['biaya']); ?>"
                                min="0" step="1000" required>
                        </div>

                    </div>

                    <!-- Action buttons -->
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">← Batal</a>
                        <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script>
        // Prevent non-numeric input
        document.querySelector('input[name="biaya"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Disable button on submit
        document.getElementById('formTransaksi').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = '⏳ Menyimpan...';
        });

        // Max date = today
        const dateInput = document.querySelector('input[name="tanggal"]');
        dateInput.max = new Date().toISOString().split('T')[0];
    </script>

</body>

</html>