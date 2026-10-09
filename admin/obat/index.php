<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";
$rows = $conn->query("SELECT * FROM obat ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Obat | Puskesmas</title>
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

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-tablet {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-kapsul {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-sirup {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-salep {
            background: #e8f5e9;
            color: #388e3c;
        }

        .badge-injeksi {
            background: #ffebee;
            color: #c62828;
        }

        .badge-default {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .medicine-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .medicine-icon {
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

        .stock-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .stock-low {
            background: #ffebee;
            color: #c62828;
        }

        .stock-medium {
            background: #fff3e0;
            color: #f57c00;
        }

        .stock-high {
            background: #e8f5e9;
            color: #388e3c;
        }

        .price-tag {
            color: #667eea;
            font-weight: 700;
            font-size: 1rem;
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

            .medicine-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <span class="header-icon">💊</span>
                <div>
                    <h1>Data Obat</h1>
                    <p class="header-subtitle">Kelola data obat Puskesmas</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="tambah.php" class="btn btn-primary">
                    <span>➕</span> Tambah Obat
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
                <div class="label">Total Obat</div>
                <div class="value"><?= count($rows); ?></div>
            </div>
            <div class="stat-box">
                <div class="icon">📦</div>
                <div class="label">Total Stok</div>
                <div class="value"><?= array_sum(array_column($rows, 'stok')); ?></div>
            </div>
            <div class="stat-box">
                <div class="icon">⚠️</div>
                <div class="label">Stok Rendah</div>
                <div class="value"><?= count(array_filter($rows, fn($r) => $r['stok'] < 50)); ?></div>
            </div>
            <div class="stat-box">
                <div class="icon">💰</div>
                <div class="label">Total Nilai Stok</div>
                <div class="value">Rp <?= number_format(array_sum(array_map(fn($r) => $r['stok'] * $r['harga'], $rows)), 0, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-box">
                <input type="text" 
                       class="search-input" 
                       id="searchInput" 
                       placeholder="🔍 Cari nama obat, jenis, atau harga..." 
                       onkeyup="searchTable()">
                <select class="search-input" style="max-width: 250px;" onchange="filterJenis(this.value)">
                    <option value="">Semua Jenis</option>
                    <?php
                    $jenis_obat = array_unique(array_map(fn($r) => $r['jenis'], $rows));
                    foreach ($jenis_obat as $jenis):
                    ?>
                        <option value="<?= htmlspecialchars($jenis) ?>"><?= htmlspecialchars($jenis) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h2>📋 Daftar Obat</h2>
            </div>

            <?php if (count($rows) > 0): ?>
            <div class="table-container">
                <table id="obatTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Obat</th>
                            <th>Jenis</th>
                            <th>Stok</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): 
                            $stok = $r['stok'];
                            $stockClass = $stok < 50 ? 'stock-low' : ($stok < 100 ? 'stock-medium' : 'stock-high');
                            
                            $jenis = strtolower($r['jenis']);
                            $badgeClass = 'badge-default';
                            if (strpos($jenis, 'tablet') !== false) $badgeClass = 'badge-tablet';
                            elseif (strpos($jenis, 'kapsul') !== false) $badgeClass = 'badge-kapsul';
                            elseif (strpos($jenis, 'sirup') !== false || strpos($jenis, 'cair') !== false) $badgeClass = 'badge-sirup';
                            elseif (strpos($jenis, 'salep') !== false || strpos($jenis, 'krim') !== false) $badgeClass = 'badge-salep';
                            elseif (strpos($jenis, 'injeksi') !== false || strpos($jenis, 'suntik') !== false) $badgeClass = 'badge-injeksi';
                        ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($r['id']); ?></strong></td>
                            <td>
                                <div class="medicine-name">
                                    <div class="medicine-icon">
                                        <?= strtoupper(substr($r['nama_obat'], 0, 1)); ?>
                                    </div>
                                    <span><?= htmlspecialchars($r['nama_obat']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?php
                                    if ($badgeClass == 'badge-tablet') echo '💊 ';
                                    elseif ($badgeClass == 'badge-kapsul') echo '💊 ';
                                    elseif ($badgeClass == 'badge-sirup') echo '🧪 ';
                                    elseif ($badgeClass == 'badge-salep') echo '🧴 ';
                                    elseif ($badgeClass == 'badge-injeksi') echo '💉 ';
                                    else echo '💊 ';
                                    ?>
                                    <?= htmlspecialchars($r['jenis']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="stock-badge <?= $stockClass ?>">
                                    <?php
                                    if ($stockClass == 'stock-low') echo '⚠️ ';
                                    elseif ($stockClass == 'stock-medium') echo '📦 ';
                                    else echo '✅ ';
                                    ?>
                                    <?= $stok ?> unit
                                </span>
                            </td>
                            <td>
                                <span class="price-tag">Rp <?= number_format($r['harga'], 0, ',', '.'); ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?= $r['id']; ?>" class="btn-action btn-edit">
                                        ✏️ Edit
                                    </a>
                                    <a href="hapus.php?id=<?= $r['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus obat ini?')">
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
                    Menampilkan <strong><?= count($rows); ?></strong> obat
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Belum ada data obat</h3>
                <p>Klik tombol "Tambah Obat" untuk menambahkan data obat baru</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search Function
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('obatTable');
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

        // Filter by Jenis
        function filterJenis(jenis) {
            const table = document.getElementById('obatTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[2];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (jenis === '' || txtValue.toLowerCase().includes(jenis.toLowerCase())) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>