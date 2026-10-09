<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";
$success = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $sql = "INSERT INTO pasien (nama, alamat, no_hp, tanggal_lahir, jenis_kelamin)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['nama'],
            $_POST['alamat'],
            $_POST['no_hp'],
            $_POST['tanggal_lahir'],
            $_POST['jenis_kelamin']
        ]);
        header("Location: index.php?success=1");
        exit();
    } catch (Exception $e) {
        $error = "Gagal menyimpan data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pasien | Puskesmas</title>
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
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 800px;
            width: 100%;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            color: white;
            text-align: center;
        }

        .form-header-icon {
            font-size: 4rem;
            margin-bottom: 15px;
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
            padding: 40px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Form Groups */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .form-label .required {
            color: #dc3545;
            margin-left: 3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: #999;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-textarea {
            padding: 14px 15px;
            resize: vertical;
            min-height: 100px;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Radio Group */
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-option {
            flex: 1;
        }

        .radio-option input[type="radio"] {
            display: none;
        }

        .radio-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-weight: 600;
        }

        .radio-option input[type="radio"]:checked+.radio-label {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .radio-label:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
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

        /* Helper Text */
        .helper-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
                align-items: flex-start;
            }

            .form-header {
                padding: 30px 20px;
            }

            .form-header h1 {
                font-size: 1.5rem;
            }

            .form-header-icon {
                font-size: 3rem;
            }

            .form-body {
                padding: 25px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .radio-group {
                flex-direction: column;
            }

            .form-actions {
                flex-direction: column-reverse;
            }
        }

        /* Loading State */
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary:disabled:hover {
            transform: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-card">
            <!-- Form Header -->
            <div class="form-header">
                <div class="form-header-icon">👤</div>
                <h1>Tambah Pasien Baru</h1>
                <p>Lengkapi form di bawah untuk menambahkan data pasien</p>
            </div>

            <!-- Form Body -->
            <div class="form-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="pasienForm">
                    <!-- Nama Lengkap -->
                    <div class="form-group">
                        <label class="form-label">
                            Nama Lengkap <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text"
                                name="nama"
                                class="form-input"
                                placeholder="Masukkan nama lengkap pasien"
                                required
                                maxlength="100">
                        </div>
                    </div>

                    <!-- Row: No HP & Tanggal Lahir -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Nomor HP/Telepon <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <span class="input-icon">📱</span>
                                <input type="tel"
                                    name="no_hp"
                                    class="form-input"
                                    placeholder="08xxxxxxxxxx"
                                    required
                                    pattern="[0-9]{10,13}"
                                    title="Masukkan nomor HP yang valid (10-13 digit)">
                            </div>
                            <p class="helper-text">Format: 10-13 digit angka</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Tanggal Lahir <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <span class="input-icon">📅</span>
                                <input type="date"
                                    name="tanggal_lahir"
                                    class="form-input"
                                    required
                                    max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="form-group">
                        <label class="form-label">
                            Alamat Lengkap <span class="required">*</span>
                        </label>
                        <textarea name="alamat"
                            class="form-textarea"
                            placeholder="Masukkan alamat lengkap pasien (jalan, RT/RW, kelurahan, kecamatan)"
                            required
                            maxlength="500"></textarea>
                        <p class="helper-text">Masukkan alamat selengkap mungkin</p>
                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="form-group">
                        <label class="form-label">
                            Jenis Kelamin <span class="required">*</span>
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio"
                                    name="jenis_kelamin"
                                    id="laki"
                                    value="L"
                                    required>
                                <label for="laki" class="radio-label">
                                    <span>👨</span>
                                    <span>Laki-laki</span>
                                </label>
                            </div>
                            <div class="radio-option">
                                <input type="radio"
                                    name="jenis_kelamin"
                                    id="perempuan"
                                    value="P"
                                    required>
                                <label for="perempuan" class="radio-label">
                                    <span>👩</span>
                                    <span>Perempuan</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <span>←</span>
                            <span>Batal</span>
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span>💾</span>
                            <span>Simpan Data</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Form Validation
        const form = document.getElementById('pasienForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            const noHp = form.elements['no_hp'].value;

            // Validasi nomor HP
            if (!/^[0-9]{10,13}$/.test(noHp)) {
                e.preventDefault();
                alert('Nomor HP harus berupa 10-13 digit angka');
                return false;
            }

            // Disable button saat submit
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>⏳</span><span>Menyimpan...</span>';
        });

        // Auto-format phone number (remove non-numeric)
        const phoneInput = form.elements['no_hp'];
        phoneInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Validate date (tidak boleh tanggal masa depan)
        const dateInput = form.elements['tanggal_lahir'];
        dateInput.addEventListener('change', function(e) {
            const selectedDate = new Date(this.value);
            const today = new Date();

            if (selectedDate > today) {
                alert('Tanggal lahir tidak boleh di masa depan');
                this.value = '';
            }
        });
    </script>
</body>

</html>