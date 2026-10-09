<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sql = "INSERT INTO dokter (nama, spesialis, no_hp)
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $_POST['nama'],
        $_POST['spesialis'],
        $_POST['no_hp']
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
    <title>Tambah Dokter | Puskesmas</title>
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
            max-width: 600px;
            width: 100%;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
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

        .form-control::placeholder {
            color: #999;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
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

        .btn-primary:active {
            transform: translateY(0);
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

        .required {
            color: #e74c3c;
            margin-left: 3px;
        }

        /* Input Icons */
        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            opacity: 0.5;
            pointer-events: none;
        }

        .form-control:focus + .input-icon {
            opacity: 0.8;
            color: #667eea;
        }

        /* Specialist Options Styling */
        .specialist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .specialist-option {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .specialist-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .specialist-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
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

            .btn {
                width: 100%;
            }
        }

        /* Loading State */
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary.loading::after {
            content: "...";
            animation: dots 1.5s infinite;
        }

        @keyframes dots {
            0%, 20% { content: "."; }
            40% { content: ".."; }
            60%, 100% { content: "..."; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <!-- Form Header -->
            <div class="form-header">
                <div class="icon">👨‍⚕️</div>
                <h1>Tambah Dokter Baru</h1>
                <p>Lengkapi formulir di bawah ini untuk menambahkan dokter</p>
            </div>

            <!-- Form Body -->
            <div class="form-body">
                <div class="info-box">
                    <span class="icon">ℹ️</span>
                    <strong>Perhatian:</strong> Pastikan semua data yang diisi sudah benar dan sesuai.
                </div>

                <form method="POST" id="formDokter">
                    <!-- Nama Dokter -->
                    <div class="form-group">
                        <label>
                            <span class="icon">👤</span>
                            Nama Dokter
                            <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                name="nama" 
                                class="form-control" 
                                placeholder="Contoh: Dr. Ahmad Suryadi, Sp.PD"
                                required
                                autocomplete="off">
                            <span class="input-icon">✍️</span>
                        </div>
                    </div>

                    <!-- Spesialis -->
                    <div class="form-group">
                        <label>
                            <span class="icon">⭐</span>
                            Spesialis
                            <span class="required">*</span>
                        </label>
                        <select name="spesialis" class="form-control" required>
                            <option value="">-- Pilih Spesialis --</option>
                            <option value="Umum">🏥 Dokter Umum</option>
                            <option value="Anak">👶 Spesialis Anak</option>
                            <option value="Penyakit Dalam">💊 Spesialis Penyakit Dalam</option>
                            <option value="Bedah">🔪 Spesialis Bedah</option>
                            <option value="Kandungan">🤰 Spesialis Kandungan</option>
                            <option value="THT">👂 Spesialis THT</option>
                            <option value="Mata">👁️ Spesialis Mata</option>
                            <option value="Kulit">🧴 Spesialis Kulit</option>
                            <option value="Gigi">🦷 Spesialis Gigi</option>
                            <option value="Jantung">❤️ Spesialis Jantung</option>
                            <option value="Saraf">🧠 Spesialis Saraf</option>
                            <option value="Orthopedi">🦴 Spesialis Orthopedi</option>
                        </select>
                    </div>

                    <!-- Nomor HP -->
                    <div class="form-group">
                        <label>
                            <span class="icon">📱</span>
                            Nomor HP
                            <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                name="no_hp" 
                                class="form-control" 
                                placeholder="Contoh: 081234567890"
                                required
                                pattern="[0-9]{10,13}"
                                title="Masukkan nomor HP yang valid (10-13 digit)"
                                autocomplete="off">
                            <span class="input-icon">☎️</span>
                        </div>
                        <small style="color: #666; font-size: 0.85rem; margin-top: 5px; display: block;">
                            Format: 08xxxxxxxxxx (10-13 digit angka)
                        </small>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <span>←</span> Batal
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span>💾</span> Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Form validation and enhancement
        document.getElementById('formDokter').addEventListener('submit', function(e) {
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;
            btnSubmit.classList.add('loading');
            btnSubmit.innerHTML = '<span>💾</span> Menyimpan';
        });

        // Phone number formatting
        const phoneInput = document.querySelector('input[name="no_hp"]');
        phoneInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Auto-capitalize first letter of name
        const nameInput = document.querySelector('input[name="nama"]');
        nameInput.addEventListener('blur', function(e) {
            if (this.value) {
                this.value = this.value.split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
            }
        });
    </script>
</body>
</html>