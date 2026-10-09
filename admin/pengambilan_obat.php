<?php
session_start();
require_once "../koneksi.php";

// Hanya admin yang boleh mengakses
if (($_SESSION["user_id"] ?? "") === "" || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

// Token keamanan form
$_SESSION["csrf_token"] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION["csrf_token"];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$pesan = "";
$jenisPesan = "error";

// Konfirmasi pengambilan obat
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = (string) ($_POST["csrf_token"] ?? "");
    $kode = strtoupper(trim($_POST["kode_pengambilan"] ?? ""));

    if (!hash_equals($csrf, $token)) {
        $pesan = "Permintaan tidak valid. Silakan muat ulang halaman.";
    } elseif ($kode === "") {
        $pesan = "Kode pengambilan obat wajib diisi.";
    } else {
        try {
            // Satu query atomik: hanya berhasil jika status masih "Belum Diambil",
            // sehingga tidak bisa dikonfirmasi dua kali secara bersamaan.
            $stmt = $pdo->prepare(
                "UPDATE transaksi_obat
                 SET status = 'Sudah Diambil'
                 WHERE kode_pengambilan = ? AND status = 'Belum Diambil'"
            );
            $stmt->execute([$kode]);

            if ($stmt->rowCount() === 1) {
                $pesan = "Berhasil! Pengambilan obat telah dikonfirmasi.";
                $jenisPesan = "success";
            } else {
                // Cari tahu alasan gagal agar pesannya jelas
                $stmt = $pdo->prepare(
                    "SELECT status FROM transaksi_obat WHERE kode_pengambilan = ?"
                );
                $stmt->execute([$kode]);
                $status = $stmt->fetchColumn();

                $pesan = match ($status) {
                    false => "Kode pengambilan obat tidak ditemukan.",
                    "Sudah Diambil" => "Obat dengan kode tersebut sudah dikonfirmasi diambil sebelumnya.",
                    default => "Status transaksi tidak valid.",
                };
            }
        } catch (Throwable $err) {
            error_log($err->getMessage());
            $pesan = "Terjadi kesalahan saat memproses konfirmasi.";
        }
    }
}

// Ambil daftar transaksi obat (dengan pencarian)
$cari = trim($_GET["cari"] ?? "");

$sql = "SELECT t.id, t.kode_pengambilan, t.tanggal, t.status,
               p.nama AS nama_pasien, p.nik,
               a.nomor_antrean, d.nama AS nama_dokter, pm.diagnosis
        FROM transaksi_obat t
        JOIN pemeriksaan pm ON t.pemeriksaan_id = pm.id
        JOIN antrean a ON pm.antrean_id = a.id
        JOIN pasien p ON a.pasien_id = p.id
        JOIN dokter d ON a.dokter_id = d.id";

$params = [];

if ($cari !== "") {
    $sql .= " WHERE t.kode_pengambilan LIKE ?
                 OR p.nama LIKE ?
                 OR p.nik LIKE ?";
    $params = array_fill(0, 3, "%" . $cari . "%");
}

$sql .= " ORDER BY (t.status = 'Belum Diambil') DESC, t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksiList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengambilan Obat - Admin</title>

    <style>
        /* ==========================================================
           Tema Puskesmas - dominan putih
           Palet: A4C8E1, 6FA3D8, 3B7A99, 1F4E79, 0D3B66
           ========================================================== */
        :root {
            --white: #fff;
            --tint: #eaf3fa;
            --border: #d6e4ef;
            --blue-100: #a4c8e1;
            --blue-300: #6fa3d8;
            --teal-500: #3b7a99;
            --blue-700: #1f4e79;
            --navy-900: #0d3b66;
            --text-muted: #4f6d85;
            --radius: 6px;
            --ease: 0.2s ease;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--white);
            color: var(--navy-900);
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

        h1 {
            margin: 0 0 6px;
            font-size: 30px;
            text-align: center;
        }

        .subjudul, .panel h2 {
            text-align: center;
        }

        .subjudul {
            margin: 0 0 16px;
            color: var(--text-muted);
        }

        /* Tombol & tautan */
        .button, .top-link {
            display: inline-block;
            padding: 10px 18px;
            font: inherit;
            font-weight: bold;
            text-decoration: none;
            border: 1px solid var(--blue-700);
            border-radius: var(--radius);
            background: var(--blue-700);
            color: var(--white);
            cursor: pointer;
            transition: background var(--ease), border-color var(--ease);
        }

        .button:hover {
            background: var(--navy-900);
            border-color: var(--navy-900);
        }

        .button-outline, .top-link {
            background: var(--white);
            color: var(--blue-700);
            border-color: var(--blue-100);
        }

        .button-outline:hover, .top-link:hover {
            background: var(--tint);
            border-color: var(--blue-300);
        }

        .top-link {
            display: table;
            margin: 0 auto 18px;
        }

        .button-small {
            padding: 6px 12px;
            font-size: 14px;
        }

        /* Kartu */
        .panel {
            position: relative;
            overflow: hidden;
            margin-top: 20px;
            padding: 30px 22px 22px;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(13, 59, 102, 0.06);
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 6px;
            background: var(--teal-500);
        }

        .panel h2 { margin: 0 0 14px; }

        /* Form */
        .form-inline {
            display: flex;
            align-items: end;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .field {
            flex: 1;
            min-width: 220px;
            max-width: 480px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: var(--blue-700);
            font-weight: bold;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            font: inherit;
            color: inherit;
            border: 1px solid var(--blue-100);
            border-radius: var(--radius);
            transition: border-color var(--ease), box-shadow var(--ease);
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--blue-300);
            box-shadow: 0 0 0 3px rgba(111, 163, 216, 0.3);
        }

        /* Tabel */
        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            min-width: 760px;
            margin-top: 18px;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--blue-700);
            color: var(--white);
        }

        tbody tr:nth-child(even) { background: #f6fafd; }
        tbody tr:hover { background: var(--tint); }

        td[colspan] {
            padding: 24px 12px;
            color: var(--text-muted);
            text-align: center;
        }

        .code {
            font-weight: bold;
            letter-spacing: 1px;
        }

        .muted {
            color: var(--text-muted);
            font-size: 13px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
            border: 1px solid;
            border-radius: 999px;
        }

        .done {
            background: var(--tint);
            color: var(--blue-700);
            border-color: var(--blue-100);
        }

        .pending {
            background: #fff4d6;
            color: #7a5a00;
            border-color: #f0d58a;
        }

        .confirm-form { margin: 0; }

        /* Pesan */
        .alert {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-left: 4px solid;
            border-radius: var(--radius);
        }

        .success {
            background: var(--tint);
            color: var(--navy-900);
            border-left-color: var(--teal-500);
        }

        .error {
            background: #fdecec;
            color: #9b1c1c;
            border-left-color: #c53030;
        }

        @media (max-width: 600px) {
            .panel { padding: 28px 14px 14px; }
            .field { max-width: none; }
        }
    </style>
</head>

<body>
    <main class="content">
        <a class="top-link" href="index.php">← Kembali ke Dashboard Admin</a>

        <h1>Pengambilan Obat</h1>
        <p class="subjudul">
            Cari kode pengambilan, periksa data pasien, lalu konfirmasi
            setelah obat benar-benar diberikan kepada pasien.
        </p>

        <?php if ($pesan !== ""): ?>
            <p class="alert <?= e($jenisPesan) ?>"><?= e($pesan) ?></p>
        <?php endif; ?>

        <section class="panel">
            <h2>Konfirmasi dengan Kode Obat</h2>

            <form method="POST" class="form-inline">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                <div class="field">
                    <label for="kode_pengambilan">Kode Pengambilan</label>
                    <input
                        type="text"
                        id="kode_pengambilan"
                        name="kode_pengambilan"
                        placeholder="Contoh: OBT-A7K9P2"
                        maxlength="30"
                        required
                    >
                </div>

                <button type="submit" class="button">
                    Konfirmasi Sudah Diambil
                </button>
            </form>
        </section>

        <section class="panel">
            <h2>Daftar Transaksi Obat</h2>

            <form method="GET" class="form-inline">
                <div class="field">
                    <label for="cari">Cari transaksi</label>
                    <input
                        type="text"
                        id="cari"
                        name="cari"
                        value="<?= e($cari) ?>"
                        placeholder="Masukkan kode, nama pasien, atau NIK"
                    >
                </div>

                <button type="submit" class="button">Cari</button>
                <a class="button button-outline" href="pengambilan_obat.php">
                    Reset
                </a>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode Obat</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!$transaksiList): ?>
                        <tr>
                            <td colspan="6">
                                Belum ada transaksi obat yang ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($transaksiList as $item):
                        $belum = $item["status"] === "Belum Diambil";
                    ?>
                        <tr>
                            <td>
                                <span class="code"><?= e($item["kode_pengambilan"]) ?></span><br>
                                <span class="muted">Antrean: <?= e($item["nomor_antrean"]) ?></span>
                            </td>

                            <td>
                                <?= e($item["nama_pasien"]) ?><br>
                                <span class="muted">NIK: <?= e($item["nik"]) ?></span>
                            </td>

                            <td>
                                <?= e($item["nama_dokter"]) ?><br>
                                <span class="muted">
                                    <?= e($item["diagnosis"] ?: "Diagnosis belum tersedia") ?>
                                </span>
                            </td>

                            <td><?= e($item["tanggal"]) ?></td>

                            <td>
                                <span class="badge <?= $belum ? "pending" : "done" ?>">
                                    <?= e($item["status"]) ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($belum): ?>
                                    <form
                                        method="POST"
                                        class="confirm-form"
                                        onsubmit="return confirm('Pastikan obat sudah diberikan kepada pasien. Konfirmasi sekarang?');"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="kode_pengambilan"
                                               value="<?= e($item["kode_pengambilan"]) ?>">

                                        <button type="submit" class="button button-small">
                                            Konfirmasi
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">Sudah selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>