<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php?force=1");
    exit();
}


echo "";


require "../config/db.php";

// Ambil data statistik dari database
try {
    // Total Pasien
    $stmtPasien = $conn->query("SELECT COUNT(*) as total FROM pasien");
    $totalPasien = $stmtPasien->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Dokter
    $stmtDokter = $conn->query("SELECT COUNT(*) as total FROM dokter");
    $totalDokter = $stmtDokter->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Obat
    $stmtObat = $conn->query("SELECT COUNT(*) as total FROM obat");
    $totalObat = $stmtObat->fetch(PDO::FETCH_ASSOC)['total'];

    // Total Penyakit
    $stmtPenyakit = $conn->query("SELECT COUNT(*) as total FROM penyakit");
    $totalPenyakit = $stmtPenyakit->fetch(PDO::FETCH_ASSOC)['total'];

    // Transaksi Hari Ini
    $stmtTransaksi = $conn->query("SELECT COUNT(*) as total FROM transaksi WHERE DATE(tanggal) = CURDATE()");
    $transaksiHariIni = $stmtTransaksi->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    // Set default values jika terjadi error
    $totalPasien = 0;
    $totalDokter = 0;
    $totalObat = 0;
    $totalPenyakit = 0;
    $transaksiHariIni = 0;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Puskesmas</title>
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left {
            flex: 1;
        }

        .header-left h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon {
            font-size: 2.5rem;
        }

        .welcome-text {
            color: #666;
            font-size: 1.1rem;
        }

        .user-name {
            color: #667eea;
            font-weight: 600;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            text-align: right;
        }

        .user-role {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .stat-title {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-subtitle {
            color: #999;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        /* Menu Grid */
        .menu-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .menu-section h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 25px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
        }

        .menu-card.logout {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        .menu-card.logout:hover {
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.5);
        }

        .menu-icon {
            font-size: 2.5rem;
            opacity: 0.9;
        }

        .menu-content {
            flex: 1;
        }

        .menu-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .menu-desc {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .quick-actions h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .action-btn.primary {
            background: #667eea;
            color: white;
        }

        .action-btn.primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-left h1 {
                font-size: 1.5rem;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1200px) and (min-width: 769px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 992px) and (min-width: 769px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Footer */
        .footer {
            text-align: center;
            color: white;
            margin-top: 30px;
            padding: 20px;
            opacity: 0.9;
        }

        /* Counter Animation */
        @keyframes countUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-value {
            animation: countUp 0.5s ease-out;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>
                    <span class="header-icon">🏥</span>
                    Dashboard Puskesmas
                </h1>
                <p class="welcome-text">
                    Selamat datang</span>!
                </p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-role">
                        <?= isset($_SESSION['role']) ? ucfirst(htmlspecialchars($_SESSION['role'])) : 'User' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <a href="../admin/pasien/" class="stat-card">
                <div class="stat-icon">🧑‍🤝‍🧑</div>
                <div class="stat-title">Total Pasien</div>
                <div class="stat-value"><?= number_format($totalPasien); ?></div>
                <div class="stat-subtitle">Klik untuk detail</div>
            </a>

            <a href="../admin/dokter/" class="stat-card">
                <div class="stat-icon">🩺</div>
                <div class="stat-title">Total Doktor</div>
                <div class="stat-value"><?= number_format($totalDokter); ?></div>
                <div class="stat-subtitle">Klik untuk detail</div>
            </a>

            <a href="../admin/obat/" class="stat-card">
                <div class="stat-icon">💉</div>
                <div class="stat-title">Jenis Obat</div>
                <div class="stat-value"><?= number_format($totalObat); ?></div>
                <div class="stat-subtitle">Klik untuk detail</div>
            </a>

            <a href="../admin/penyakit/" class="stat-card">
                <div class="stat-icon">🦠</div>
                <div class="stat-title">Data Penyakit</div>
                <div class="stat-value"><?= number_format($totalPenyakit); ?></div>
                <div class="stat-subtitle">Klik untuk detail</div>
            </a>

            <a href="../admin/transaksi/" class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-title">Transaksi Hari Ini</div>
                <div class="stat-value"><?= number_format($transaksiHariIni); ?></div>
                <div class="stat-subtitle">Klik untuk detail</div>
            </a>
        </div>

        <!-- Menu Section -->
        <div class="menu-section">
            <h2>📋 Menu Utama</h2>
            <div class="menu-grid">
                <a href="../admin/pasien/" class="menu-card">
                    <div class="menu-icon">🧑‍🤝‍🧑</div>
                    <div class="menu-content">
                        <div class="menu-title">Data Pasien</div>
                        <div class="menu-desc">Kelola data pasien</div>
                    </div>
                </a>

                <a href="../admin/dokter/" class="menu-card">
                    <div class="menu-icon">🩺</div>
                    <div class="menu-content">
                        <div class="menu-title">Data Dokter</div>
                        <div class="menu-desc">Kelola data dokter</div>
                    </div>
                </a>

                <a href="../admin/obat/" class="menu-card">
                    <div class="menu-icon">💉</div>
                    <div class="menu-content">
                        <div class="menu-title">Data Obat</div>
                        <div class="menu-desc">Kelola data obat</div>
                    </div>
                </a>

                <a href="../admin/penyakit/" class="menu-card">
                    <div class="menu-icon">🦠</div>
                    <div class="menu-content">
                        <div class="menu-title">Data Penyakit</div>
                        <div class="menu-desc">Kelola data penyakit</div>
                    </div>
                </a>

                <a href="../admin/transaksi/" class="menu-card">
                    <div class="menu-icon">📊</div>
                    <div class="menu-content">
                        <div class="menu-title">Data Transaksi</div>
                        <div class="menu-desc">Kelola transaksi</div>
                    </div>
                </a>

                <a href="../auth/logout.php" class="menu-card logout">
                    <div class="menu-icon">🚪</div>
                    <div class="menu-content">
                        <div class="menu-title">Logout</div>
                        <div class="menu-desc">Keluar dari sistem</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>⚡ Aksi Cepat</h2>
            <div class="action-buttons">
                <a href="pasien/tambah.php" class="action-btn primary">
                    <span>➕</span> Tambah Pasien Baru
                </a>
                <a href="transaksi/tambah.php" class="action-btn primary">
                    <span>📝</span> Buat Transaksi
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© 2025 Sistem Informasi Kesehatan - Puskesmas. All rights reserved.</p>
        </div>
    </div>
</body>

</html>