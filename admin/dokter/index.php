<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";
$stmt = $conn->query("SELECT * FROM dokter ORDER BY id DESC");
$dokters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dokter | Puskesmas</title>
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

        .badge-specialist {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .badge-general {
            background: #e3f2fd;
            color: #1976d2;
        }

        .doctor-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .doctor-avatar {
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

            .doctor-avatar {
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
                <span class="header-icon">👨‍⚕️</span>
                <div>
                    <h1>Data Dokter</h1>
                    <p class="header-subtitle">Kelola data dokter Puskesmas</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="tambah.php" class="btn btn-primary">
                    <span>➕</span> Tambah Dokter
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
                <div class="label">Total Dokter</div>
                <div class="value"><?= count($dokters); ?></div>
            </div>
            <div class="stat-box">
                <div class="icon">⭐</div>
                <div class="label">Dokter Spesialis</div>
                <div class="value"><?= count(array_filter($dokters, fn($d) => strtolower($d['spesialis']) != 'umum')); ?></div>
            </div>
            <div class="stat-box">
                <div class="icon">🏥</div>
                <div class="label">Dokter Umum</div>
                <div class="value"><?= count(array_filter($dokters, fn($d) => strtolower($d['spesialis']) == 'umum')); ?></div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-box">
                <input type="text" 
                       class="search-input" 
                       id="searchInput" 
                       placeholder="🔍 Cari nama dokter, spesialis, atau nomor HP..." 
                       onkeyup="searchTable()">
                <select class="search-input" style="max-width: 250px;" onchange="filterSpecialist(this.value)">
                    <option value="">Semua Spesialis</option>
                    <option value="umum">Dokter Umum</option>
                    <?php
                    $specialists = array_unique(array_map(fn($d) => $d['spesialis'], $dokters));
                    foreach ($specialists as $spec):
                        if (strtolower($spec) != 'umum'):
                    ?>
                        <option value="<?= htmlspecialchars($spec) ?>"><?= htmlspecialchars($spec) ?></option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h2>📋 Daftar Dokter</h2>
            </div>

            <?php if (count($dokters) > 0): ?>
            <div class="table-container">
                <table id="dokterTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Dokter</th>
                            <th>Spesialis</th>
                            <th>No. HP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dokters as $d): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($d['id']); ?></strong></td>
                            <td>
                                <div class="doctor-name">
                                    <div class="doctor-avatar">
                                        <?= strtoupper(substr($d['nama'], 0, 1)); ?>
                                    </div>
                                    <span><?= htmlspecialchars($d['nama']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= strtolower($d['spesialis']) == 'umum' ? 'badge-general' : 'badge-specialist'; ?>">
                                    <?= strtolower($d['spesialis']) == 'umum' ? '🏥' : '⭐'; ?>
                                    <?= htmlspecialchars($d['spesialis']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($d['no_hp']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?= $d['id']; ?>" class="btn-action btn-edit">
                                        ✏️ Edit
                                    </a>
                                    <a href="hapus.php?id=<?= $d['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data dokter ini?')">
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
                    Menampilkan <strong><?= count($dokters); ?></strong> dokter
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Belum ada data dokter</h3>
                <p>Klik tombol "Tambah Dokter" untuk menambahkan data dokter baru</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search Function
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('dokterTable');
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

        // Filter by Specialist
        function filterSpecialist(specialist) {
            const table = document.getElementById('dokterTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[2];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (specialist === '' || txtValue.toLowerCase().includes(specialist.toLowerCase())) {
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