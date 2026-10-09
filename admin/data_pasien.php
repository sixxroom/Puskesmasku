<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya admin yang bisa membuka halaman
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

// Fungsi untuk mengamankan output HTML
function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Pencarian pasien
$cari = trim($_GET["cari"] ?? "");

$sql = "SELECT id, nik, nama, tanggal_lahir, jenis_kelamin,
               alamat, no_hp
        FROM pasien";

if ($cari !== "") {
    $sql .= " WHERE nama LIKE :cari OR nik LIKE :cari";
}

$sql .= " ORDER BY nama ASC";

$stmt = $pdo->prepare($sql);

if ($cari !== "") {
    $stmt->execute(["cari" => "%$cari%"]);
} else {
    $stmt->execute();
}

$pasien = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Detail pasien yang dipilih
$detail = null;

if (isset($_GET["detail"]) && ctype_digit((string)$_GET["detail"])) {
    $stmtDetail = $pdo->prepare(
        "SELECT id, nik, nama, tanggal_lahir, jenis_kelamin,
                alamat, no_hp
         FROM pasien WHERE id = ? LIMIT 1"
    );

    $stmtDetail->execute([(int)$_GET["detail"]]);
    $detail = $stmtDetail->fetch(PDO::FETCH_ASSOC);
}

// Query string pencarian untuk dipertahankan di tautan
$qsCari = $cari !== "" ? "cari=" . urlencode($cari) : "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien - Puskesmas</title>

    <style>
        /* ==========================================================
           Tema Puskesmas - dominan putih
           Palet: A4C8E1, 6FA3D8, 3B7A99, 1F4E79, 0D3B66
           ========================================================== */
        :root {
            --white: #ffffff;
            --tint: #eaf3fa;
            --border: #d6e4ef;
            --blue-100: #a4c8e1;
            --blue-300: #6fa3d8;
            --teal-500: #3b7a99;
            --blue-700: #1f4e79;
            --navy-900: #0d3b66;
            --text: #0d3b66;
            --text-muted: #4f6d85;
            --radius-sm: 6px;
            --radius-lg: 14px;
            --shadow: 0 2px 10px rgba(13, 59, 102, 0.06);
            --transition: 0.2s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--white);
            color: var(--text);
            font-family: "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
        }

        :focus-visible {
            outline: 3px solid var(--blue-300);
            outline-offset: 2px;
        }

        .content {
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 18px;
        }

        /* Judul dan navigasi (rata tengah) */
        .content > h1 {
            margin: 0 0 6px;
            color: var(--navy-900);
            font-size: 30px;
            text-align: center;
        }

        .content > p {
            margin: 0 0 16px;
            color: var(--text-muted);
            text-align: center;
        }

        .top-link {
            display: table;
            margin: 0 auto 18px;
            padding: 10px 18px;
            background: var(--white);
            color: var(--blue-700);
            font-weight: bold;
            text-decoration: none;
            border: 1px solid var(--blue-100);
            border-radius: var(--radius-sm);
            transition: background var(--transition),
                        border-color var(--transition);
        }

        .top-link:hover {
            background: var(--tint);
            border-color: var(--blue-300);
        }

        /* Kartu */
        .panel {
            position: relative;
            overflow: hidden;
            margin-top: 20px;
            padding: 30px 22px 22px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 6px;
            background: var(--teal-500);
        }

        .panel h2 {
            margin: 0 0 6px;
            color: var(--navy-900);
            text-align: center;
        }

        .panel > p {
            margin: 0 0 14px;
            color: var(--text-muted);
            text-align: center;
        }

        /* Form pencarian */
        .filter {
            display: flex;
            align-items: end;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .filter .field {
            flex: 1;
            max-width: 480px;
            min-width: 220px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: var(--blue-700);
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            background: var(--white);
            color: var(--text);
            font: inherit;
            border: 1px solid var(--blue-100);
            border-radius: var(--radius-sm);
            transition: border-color var(--transition),
                        box-shadow var(--transition);
        }

        input:focus {
            outline: none;
            border-color: var(--blue-300);
            box-shadow: 0 0 0 3px rgba(111, 163, 216, 0.3);
        }

        /* Tombol */
        .button {
            display: inline-block;
            padding: 10px 18px;
            background: var(--blue-700);
            color: var(--white);
            font: inherit;
            font-weight: bold;
            text-decoration: none;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition);
        }

        .button:hover {
            background: var(--navy-900);
        }

        .button-outline {
            background: var(--white);
            color: var(--blue-700);
            border: 1px solid var(--blue-100);
        }

        .button-outline:hover {
            background: var(--tint);
            border-color: var(--blue-300);
        }

        .button-small {
            padding: 6px 12px;
            font-size: 14px;
        }

        /* Tabel */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            background: var(--white);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: none;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--blue-700);
            color: var(--white);
        }

        tbody tr:nth-child(even) {
            background: #f6fafd;
        }

        tbody tr:hover {
            background: var(--tint);
        }

        td[colspan] {
            padding: 24px 12px;
            color: var(--text-muted);
            text-align: center;
        }

        /* Detail pasien */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .detail-item {
            padding: 12px 14px;
            background: var(--tint);
            border-left: 4px solid var(--teal-500);
            border-radius: var(--radius-sm);
        }

        .detail-item small {
            display: block;
            margin-bottom: 4px;
            color: var(--blue-700);
            font-weight: bold;
        }

        .detail-item strong {
            overflow-wrap: anywhere;
        }

        .detail-actions {
            margin-top: 20px;
            text-align: center;
        }

        /* Pesan */
        .notice {
            margin: 20px 0 0;
            padding: 12px 14px;
            background: #fdecec;
            color: #9b1c1c;
            border-left: 4px solid #c53030;
            border-radius: var(--radius-sm);
        }

        .notice a {
            color: inherit;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .panel {
                padding: 28px 14px 14px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .filter .field {
                max-width: none;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="content">
        <a class="top-link" href="index.php">← Kembali ke Dashboard Admin</a>

        <h1>Data Pasien</h1>
        <p>Lihat dan cari informasi pasien yang terdaftar.</p>

        <?php if ($detail): ?>
            <section class="panel">
                <h2>Detail Pasien</h2>
                <p>ID Pasien: <?= e($detail["id"]) ?></p>

                <div class="detail-grid">
                    <div class="detail-item">
                        <small>Nama Lengkap</small>
                        <strong><?= e($detail["nama"]) ?></strong>
                    </div>

                    <div class="detail-item">
                        <small>NIK</small>
                        <strong><?= e($detail["nik"]) ?: "-" ?></strong>
                    </div>

                    <div class="detail-item">
                        <small>Tanggal Lahir</small>
                        <strong><?= e($detail["tanggal_lahir"]) ?: "-" ?></strong>
                    </div>

                    <div class="detail-item">
                        <small>Jenis Kelamin</small>
                        <strong><?= e($detail["jenis_kelamin"]) ?: "-" ?></strong>
                    </div>

                    <div class="detail-item">
                        <small>Nomor HP</small>
                        <strong><?= e($detail["no_hp"]) ?: "-" ?></strong>
                    </div>

                    <div class="detail-item">
                        <small>Alamat</small>
                        <strong><?= e($detail["alamat"]) ?: "-" ?></strong>
                    </div>
                </div>

                <div class="detail-actions">
                    <a class="button button-outline"
                       href="data_pasien.php<?= $qsCari !== "" ? "?" . $qsCari : "" ?>">
                        Tutup Detail
                    </a>
                </div>
            </section>
        <?php elseif (isset($_GET["detail"])): ?>
            <p class="notice">
                Data pasien tidak ditemukan.
                <a href="data_pasien.php">Kembali</a>
            </p>
        <?php endif; ?>

        <section class="panel">
            <h2>Daftar Pasien</h2>
            <p>Jumlah hasil: <?= count($pasien) ?> pasien</p>

            <form class="filter" method="GET" action="data_pasien.php">
                <div class="field">
                    <label for="cari">Cari pasien</label>
                    <input
                        type="text"
                        id="cari"
                        name="cari"
                        placeholder="Cari nama atau NIK pasien..."
                        value="<?= e($cari) ?>"
                    >
                </div>

                <button class="button" type="submit">Cari Pasien</button>

                <?php if ($cari !== ""): ?>
                    <a class="button button-outline" href="data_pasien.php">
                        Reset
                    </a>
                <?php endif; ?>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Pasien</th>
                            <th>NIK</th>
                            <th>Jenis Kelamin</th>
                            <th>Nomor HP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($pasien) > 0): ?>
                        <?php foreach ($pasien as $index => $p): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($p["nama"]) ?></td>
                                <td><?= e($p["nik"]) ?: "-" ?></td>
                                <td><?= e($p["jenis_kelamin"]) ?: "-" ?></td>
                                <td><?= e($p["no_hp"]) ?: "-" ?></td>
                                <td>
                                    <a class="button button-small"
                                       href="data_pasien.php?detail=<?= (int)$p["id"] ?><?= $qsCari !== "" ? "&" . $qsCari : "" ?>">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                Tidak ada data pasien yang ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>