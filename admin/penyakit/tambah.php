<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->prepare("INSERT INTO penyakit (nama_penyakit, deskripsi) VALUES (?, ?)")
         ->execute([$_POST['nama_penyakit'], $_POST['deskripsi']]);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Penyakit | Puskesmas</title>
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

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
            line-height: 1.6;
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
            top: 15px;
            font-size: 1.2rem;
            opacity: 0.5;
            pointer-events: none;
        }

        .form-control:focus + .input-icon {
            opacity: 0.8;
            color: #667eea;
        }

        .help-text {
            color: #666;
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }

        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: #999;
            margin-top: 5px;
        }

        .char-counter.warning {
            color: #ff9800;
        }

        .char-counter.danger {
            color: #f44336;
        }

        /* Common diseases quick select */
        .quick-select {
            margin-bottom: 15px;
        }

        .quick-select-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: block;
        }

        .disease-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .disease-tag {
            padding: 8px 15px;
            background: #f0f0f0;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .disease-tag:hover {
            background: #667eea;
            border-color: #667eea;
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
                <div class="icon">🦠</div>
                <h1>Tambah Penyakit Baru</h1>
                <p>Lengkapi formulir di bawah ini untuk menambahkan data penyakit</p>
            </div>

            <!-- Form Body -->
            <div class="form-body">
                <div class="info-box">
                    <span class="icon">ℹ️</span>
                    <strong>Perhatian:</strong> Pastikan nama penyakit dan deskripsi yang diisi sudah benar dan lengkap.
                </div>

                <form method="POST" id="formPenyakit">
                    <!-- Quick Select Common Diseases -->
                    <div class="quick-select">
                        <span class="quick-select-label">💡 Pilihan Cepat (Klik untuk mengisi otomatis):</span>
                        <div class="disease-tags">
                            <span class="disease-tag" data-name="Demam Berdarah" data-desc="Penyakit yang disebabkan oleh virus dengue yang ditularkan melalui gigitan nyamuk Aedes aegypti. Gejala: demam tinggi, nyeri otot, ruam kulit.">🦟 Demam Berdarah</span>
                            <span class="disease-tag" data-name="Influenza" data-desc="Infeksi virus yang menyerang sistem pernapasan. Gejala: demam, batuk, pilek, nyeri tubuh, dan kelelahan.">🤒 Influenza</span>
                            <span class="disease-tag" data-name="Hipertensi" data-desc="Tekanan darah tinggi yang dapat meningkatkan risiko penyakit jantung dan stroke. Tekanan darah normal adalah 120/80 mmHg.">💓 Hipertensi</span>
                            <span class="disease-tag" data-name="Diabetes Mellitus" data-desc="Penyakit kronis akibat gangguan metabolisme gula darah. Gejala: sering haus, sering buang air kecil, mudah lelah.">🩸 Diabetes</span>
                            <span class="disease-tag" data-name="Diare" data-desc="Kondisi di mana feses menjadi encer dan frekuensi buang air besar meningkat. Dapat disebabkan oleh infeksi virus, bakteri, atau keracunan makanan.">🚽 Diare</span>
                        </div>
                    </div>

                    <!-- Nama Penyakit -->
                    <div class="form-group">
                        <label>
                            <span class="icon">🦠</span>
                            Nama Penyakit
                            <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                name="nama_penyakit" 
                                id="namaPenyakit"
                                class="form-control" 
                                placeholder="Contoh: Demam Berdarah Dengue (DBD)"
                                required
                                autocomplete="off">
                            <span class="input-icon">✍️</span>
                        </div>
                        <small class="help-text">Masukkan nama penyakit dengan lengkap dan jelas</small>
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group">
                        <label>
                            <span class="icon">📝</span>
                            Deskripsi Penyakit
                        </label>
                        <textarea 
                            name="deskripsi" 
                            id="deskripsiPenyakit"
                            class="form-control" 
                            placeholder="Jelaskan tentang penyakit ini, termasuk gejala, penyebab, dan cara penanganannya..."
                            maxlength="1000"></textarea>
                        <div class="char-counter" id="charCounter">0 / 1000 karakter</div>
                        <small class="help-text">Berikan penjelasan yang detail mengenai penyakit (opsional)</small>
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
        document.getElementById('formPenyakit').addEventListener('submit', function(e) {
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;
            btnSubmit.classList.add('loading');
            btnSubmit.innerHTML = '<span>💾</span> Menyimpan';
        });

        // Auto-capitalize first letter of disease name
        const nameInput = document.getElementById('namaPenyakit');
        nameInput.addEventListener('blur', function(e) {
            if (this.value) {
                this.value = this.value.split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
            }
        });

        // Character counter for description
        const deskripsiInput = document.getElementById('deskripsiPenyakit');
        const charCounter = document.getElementById('charCounter');

        deskripsiInput.addEventListener('input', function() {
            const length = this.value.length;
            const maxLength = 1000;
            charCounter.textContent = `${length} / ${maxLength} karakter`;

            // Change color based on length
            charCounter.classList.remove('warning', 'danger');
            if (length > 800) {
                charCounter.classList.add('warning');
            }
            if (length >= 950) {
                charCounter.classList.add('danger');
            }
        });

        // Quick select diseases
        const diseaseTags = document.querySelectorAll('.disease-tag');
        diseaseTags.forEach(tag => {
            tag.addEventListener('click', function() {
                const name = this.getAttribute('data-name');
                const desc = this.getAttribute('data-desc');
                
                nameInput.value = name;
                deskripsiInput.value = desc;
                
                // Trigger character counter update
                deskripsiInput.dispatchEvent(new Event('input'));
                
                // Smooth scroll to form
                nameInput.focus();
                
                // Visual feedback
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 200);
            });
        });
    </script>
</body>
</html>