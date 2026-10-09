<?php
session_start();

// Cek login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Panggil koneksi database
require "../../config/db.php";

// Ambil data penyakit
$rows = $conn->query("SELECT * FROM penyakit ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penyakit | Puskesmas</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon {
            font-size: 2.5rem;
        }

        .header-left h1 {
            color: #333;
            font-size: 2rem;
        }

        .header-subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-top: 5px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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

        /* Stats Summary */
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-box .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-box .label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .stat-box .value {
            color: #333;
            font-size: 1.8rem;
            font-weight: 700;
        }

        /* Search Section */
        .search-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .search-box {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Table Card */
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .table-header {
            padding: 25px 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .table-header h2 {
            color: #333;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        thead th {
            padding: 18px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: #f8f9ff;
            transform: scale(1.01);
        }

        tbody td {
            padding: 18px 20px;
            color: #333;
            font-size: 0.95rem;
        }

        .disease-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .disease-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .description-text {
            max-width: 400px;
            line-height: 1.6;
            color: #555;
        }

        .description-text.truncate {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background: #fff3e0;
            color: #f57c00;
        }

        .btn-edit:hover {
            background: #f57c00;
            color: white;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }

        .btn-delete:hover {
            background: #c62828;
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        /* Pagination */
        .pagination {
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #f0f0f0;
        }

        .pagination-info {
            color: #666;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-left h1 {
                font-size: 1.5rem;
            }

            .search-box {
                flex-direction: column;
            }

            .search-input {
                width: 100%;
            }

            table {
                font-size: 0.85rem;
            }

            thead th,
            tbody td {
                padding: 12px 10px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .disease-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .description-text {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <span class="header-icon">🦠</span>
                <div>
                    <h1>Data Penyakit</h1>
                    <p class="header-subtitle">Kelola data penyakit Puskesmas</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="tambah.php" class="btn btn-primary">
                    <span>➕</span> Tambah Penyakit
                </a>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <span>←</span> Kembali
                </a>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="stats-summary">
            <div class="stat-box">
                <div class="icon">📊</div>
                <div class="label">Total Penyakit</div>
                <div class="value"><?= count($rows); ?></div>
            </div>
            <div class="stat-box">
                <div class="icon">📝</div>
                <div class="label">Penyakit Terdaftar</div>
                <div class="value"><?= count($rows); ?></div>
            </div>
            <div class="stat-box">
                <div class="icon">🏥</div>
                <div class="label">Database Penyakit</div>
                <div class="value">Aktif</div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-box">
                <input type="text" 
                       class="search-input" 
                       id="searchInput" 
                       placeholder="🔍 Cari nama penyakit atau deskripsi..." 
                       onkeyup="searchTable()">
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h2>📋 Daftar Penyakit</h2>
            </div>

            <?php if (count($rows) > 0): ?>
            <div class="table-container">
                <table id="penyakitTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Penyakit</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($r['id']); ?></strong></td>
                            <td>
                                <div class="disease-name">
                                    <div class="disease-icon">
                                        <?= strtoupper(substr($r['nama_penyakit'], 0, 1)); ?>
                                    </div>
                                    <span><?= htmlspecialchars($r['nama_penyakit']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="description-text truncate">
                                    <?= htmlspecialchars($r['deskripsi']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?= $r['id']; ?>" class="btn-action btn-edit">
                                        ✏️ Edit
                                    </a>
                                    <a href="hapus.php?id=<?= $r['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data penyakit ini?')">
                                        🗑️ Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <div class="pagination-info">
                    Menampilkan <strong><?= count($rows); ?></strong> penyakit
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Belum ada data penyakit</h3>
                <p>Klik tombol "Tambah Penyakit" untuk menambahkan data penyakit baru</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search Function
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('penyakitTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length - 1; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }

        // Update showing count after search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const table = document.getElementById('penyakitTable');
            const tr = table.getElementsByTagName('tr');
            let visibleCount = 0;

            for (let i = 1; i < tr.length; i++) {
                if (tr[i].style.display !== 'none') {
                    visibleCount++;
                }
            }

            const paginationInfo = document.querySelector('.pagination-info');
            if (paginationInfo) {
                paginationInfo.innerHTML = `Menampilkan <strong>${visibleCount}</strong> penyakit`;
            }
        });
    </script>
</body>
</html>