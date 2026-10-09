<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM penyakit WHERE id=?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE penyakit SET nama_penyakit=?, deskripsi=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $_POST['nama_penyakit'],
        $_POST['deskripsi'],
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
    <title>Edit Penyakit | Puskesmas</title>
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

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
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

        .disease-id {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 2px dashed #667eea;
        }

        .disease-id strong {
            color: #667eea;
            font-size: 1.1rem;
        }

        .form-hint {
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
                padding: 25px 20px;
            }

            .form-body {
                padding: 25px 20px;
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
            <!-- Form Header -->
            <div class="form-header">
                <div class="icon">🩺</div>
                <h1>Edit Data Penyakit</h1>
                <p>Perbarui informasi penyakit</p>
            </div>

            <!-- Form Body -->
            <div class="form-body">
                <!-- Disease ID Info -->
                <div class="disease-id">
                    <strong>ID Penyakit: #<?= htmlspecialchars($data['id']); ?></strong>
                </div>

                <form method="POST" id="formPenyakit">
                    <!-- Nama Penyakit -->
                    <div class="form-group">
                        <label>Nama Penyakit</label>
                        <input 
                            type="text" 
                            name="nama_penyakit" 
                            class="form-control" 
                            value="<?= htmlspecialchars($data['nama_penyakit']); ?>"
                            placeholder="Contoh: Demam Berdarah, Diabetes, dll"
                            required>
                        <div class="form-hint">Masukkan nama penyakit dengan jelas</div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group">
                        <label>Deskripsi Penyakit</label>
                        <textarea 
                            name="deskripsi" 
                            class="form-control"
                            placeholder="Jelaskan gejala, penyebab, dan informasi penting lainnya tentang penyakit ini..."><?= htmlspecialchars($data['deskripsi']); ?></textarea>
                        <div class="form-hint">Berikan informasi lengkap mengenai penyakit (opsional)</div>
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
        // Form submit handling
        document.getElementById('formPenyakit').addEventListener('submit', function(e) {
            const btnSubmit = this.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '⏳ Menyimpan...';
        });

        // Auto-resize textarea
        const textarea = document.querySelector('textarea[name="deskripsi"]');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>