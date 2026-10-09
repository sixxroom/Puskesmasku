<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM obat WHERE id=?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE obat SET nama_obat=?, jenis=?, stok=?, harga=? WHERE id=?";
    $conn->prepare($sql)->execute([
        $_POST['nama_obat'],
        $_POST['jenis'],
        (int)$_POST['stok'],
        (float)$_POST['harga'],
        $id
    ]);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Obat | Puskesmas</title>
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
            max-width: 550px;
            width: 100%;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .form-header .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .form-header h1 {
            font-size: 1.6rem;
            margin-bottom: 5px;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .form-body {
            padding: 30px;
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
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
        }

        .input-with-icon .form-control {
            padding-left: 45px;
        }

        .obat-id {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 2px dashed #667eea;
        }

        .obat-id strong {
            color: #667eea;
            font-size: 1.1rem;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .form-header {
                padding: 25px 20px;
            }

            .form-body {
                padding: 25px 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Loading state */
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <!-- Form Header -->
            <div class="form-header">
                <div class="icon">💊</div>
                <h1>Edit Data Obat</h1>
                <p>Perbarui informasi obat</p>
            </div>

            <!-- Form Body -->
            <div class="form-body">
                <!-- Obat ID Info -->
                <div class="obat-id">
                    <strong>ID Obat: #<?= htmlspecialchars($data['id']); ?></strong>
                </div>

                <form method="POST" id="formObat">
                    <!-- Nama Obat -->
                    <div class="form-group">
                        <label>Nama Obat</label>
                        <div class="input-with-icon">
                            <span class="input-icon">💊</span>
                            <input 
                                type="text" 
                                name="nama_obat" 
                                class="form-control" 
                                value="<?= htmlspecialchars($data['nama_obat']); ?>"
                                placeholder="Contoh: Paracetamol 500mg"
                                required>
                        </div>
                    </div>

                    <!-- Jenis Obat -->
                    <div class="form-group">
                        <label>Jenis Obat</label>
                        <div class="input-with-icon">
                            <span class="input-icon">📋</span>
                            <input 
                                type="text" 
                                name="jenis" 
                                class="form-control" 
                                value="<?= htmlspecialchars($data['jenis']); ?>"
                                placeholder="Contoh: Tablet, Sirup, Kapsul">
                        </div>
                    </div>

                    <!-- Stok dan Harga -->
                    <div class="form-row">
                        <!-- Stok -->
                        <div class="form-group">
                            <label>Stok</label>
                            <div class="input-with-icon">
                                <span class="input-icon">📦</span>
                                <input 
                                    type="number" 
                                    name="stok" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($data['stok']); ?>"
                                    min="0"
                                    placeholder="0">
                            </div>
                            <span class="info-badge">Unit</span>
                        </div>

                        <!-- Harga -->
                        <div class="form-group">
                            <label>Harga</label>
                            <div class="input-with-icon">
                                <span class="input-icon">💰</span>
                                <input 
                                    type="number" 
                                    name="harga" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($data['harga']); ?>"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00">
                            </div>
                            <span class="info-badge">Rp</span>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">
                            ← Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            💾 Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Number input validation
        const stokInput = document.querySelector('input[name="stok"]');
        const hargaInput = document.querySelector('input[name="harga"]');

        stokInput.addEventListener('input', function(e) {
            if (this.value < 0) this.value = 0;
        });

        hargaInput.addEventListener('input', function(e) {
            if (this.value < 0) this.value = 0;
        });

        // Form submit handling
        document.getElementById('formObat').addEventListener('submit', function(e) {
            const btnSubmit = this.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '⏳ Menyimpan...';
        });

        // Format currency display on blur
        hargaInput.addEventListener('blur', function(e) {
            if (this.value) {
                const value = parseFloat(this.value);
                if (!isNaN(value)) {
                    this.value = value.toFixed(2);
                }
            }
        });
    </script>
</body>
</html>