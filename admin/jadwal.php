<?php
session_start();
require_once "../koneksi.php";

// Cek login admin
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

// Token keamanan
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pesan = "";
$hariList = [
    "Senin", "Selasa", "Rabu", "Kamis",
    "Jumat", "Sabtu", "Minggu"
];

// Proses tambah dan edit jadwal
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"]
        )
    ) {
        die("Permintaan tidak valid. Muat ulang halaman.");
    }

    $aksi = $_POST["aksi"] ?? "";

    try {
        if ($aksi === "simpan") {
            $id = (int) ($_POST["id"] ?? 0);
            $dokter_id = (int) ($_POST["dokter_id"] ?? 0);
            $hari = $_POST["hari"] ?? "";
            $jam_mulai = $_POST["jam_mulai"] ?? "";
            $jam_selesai = $_POST["jam_selesai"] ?? "";

            if (
                $dokter_id <= 0 ||
                !in_array($hari, $hariList, true) ||
                !preg_match('/^\d{2}:\d{2}$/', $jam_mulai) ||
                !preg_match('/^\d{2}:\d{2}$/', $jam_selesai) ||
                $jam_mulai >= $jam_selesai
            ) {
                $pesan = "Data tidak valid. Periksa dokter, hari, dan jam praktik.";
            } else {
                // Pastikan dokter tersedia
                $cek = $pdo->prepare(
                    "SELECT COUNT(*) FROM dokter WHERE id = ?"
                );
                $cek->execute([$dokter_id]);

                if ((int) $cek->fetchColumn() === 0) {
                    $pesan = "Dokter tidak ditemukan.";
                } else {
                    if ($id > 0) {
                        $sql = "UPDATE jadwal_dokter
                                SET dokter_id = ?, hari = ?,
                                    jam_mulai = ?, jam_selesai = ?
                                WHERE id = ?";

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            $dokter_id,
                            $hari,
                            $jam_mulai,
                            $jam_selesai,
                            $id
                        ]);

                        $pesan = "Jadwal berhasil diperbarui.";
                    } else {
                        $sql = "INSERT INTO jadwal_dokter
                                (dokter_id, hari, jam_mulai, jam_selesai)
                                VALUES (?, ?, ?, ?)";

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            $dokter_id,
                            $hari,
                            $jam_mulai,
                            $jam_selesai
                        ]);

                        $pesan = "Jadwal berhasil ditambahkan.";
                    }
                }
            }
        } elseif ($aksi === "hapus") {
            $id = (int) ($_POST["id"] ?? 0);

            $stmt = $pdo->prepare(
                "DELETE FROM jadwal_dokter WHERE id = ?"
            );
            $stmt->execute([$id]);

            $pesan = "Jadwal berhasil dihapus.";
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $pesan = "Gagal memproses jadwal. Periksa database atau jadwal yang sudah ada.";
    }
}

// Ambil data dokter untuk formulir
$stmt = $pdo->query(
    "SELECT id, nama, spesialisasi FROM dokter ORDER BY nama"
);
$dokterList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data jadwal
$sql = "SELECT j.id, j.dokter_id, j.hari,
               j.jam_mulai, j.jam_selesai,
               d.nama AS nama_dokter,
               d.spesialisasi
        FROM jadwal_dokter j
        JOIN dokter d ON j.dokter_id = d.id
        ORDER BY
            FIELD(j.hari, 'Senin', 'Selasa', 'Rabu',
                  'Kamis', 'Jumat', 'Sabtu', 'Minggu'),
            j.jam_mulai";

$stmt = $pdo->query($sql);
$jadwalList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil jadwal yang akan diedit
$edit = null;

if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM jadwal_dokter WHERE id = ?"
    );
    $stmt->execute([(int) $_GET["edit"]]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

function e($nilai) {
    return htmlspecialchars((string) $nilai, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Jadwal Dokter - Puskesmas</title>

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
            --danger: #c53030;
            --danger-dark: #9b1c1c;
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
            padding: 24px;
            background: var(--white);
            color: var(--text);
            font-family: "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
        }

        :focus-visible {
            outline: 3px solid var(--blue-300);
            outline-offset: 2px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        /* Judul dan navigasi (rata tengah) */
        h1 {
            margin: 0 0 6px;
            color: var(--navy-900);
            font-size: 30px;
            text-align: center;
        }

        .subjudul {
            margin: 0 0 20px;
            color: var(--text-muted);
        }

        .container > p {
            text-align: center;
        }

        /* Kartu */
        .panel {
            position: relative;
            overflow: hidden;
            margin-bottom: 22px;
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
            color: var(--text-muted);
            text-align: center;
        }

        /* Form */
        label {
            display: block;
            margin: 13px 0 6px;
            color: var(--blue-700);
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 11px;
            background: var(--white);
            color: var(--text);
            font: inherit;
            border: 1px solid var(--blue-100);
            border-radius: var(--radius-sm);
            transition: border-color var(--transition),
                        box-shadow var(--transition);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--blue-300);
            box-shadow: 0 0 0 3px rgba(111, 163, 216, 0.3);
        }

        /* Tombol */
        button, .tombol {
            display: inline-block;
            padding: 10px 16px;
            background: var(--blue-700);
            color: var(--white);
            font: inherit;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            border: 1px solid var(--blue-700);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition),
                        color var(--transition),
                        border-color var(--transition);
        }

        button:hover, .tombol:hover {
            background: var(--navy-900);
            border-color: var(--navy-900);
        }

        .sekunder {
            background: var(--white);
            color: var(--blue-700);
            border-color: var(--blue-100);
        }

        .sekunder:hover {
            background: var(--tint);
            color: var(--navy-900);
            border-color: var(--blue-300);
        }

        .hapus {
            background: var(--danger);
            border-color: var(--danger);
        }

        .hapus:hover {
            background: var(--danger-dark);
            border-color: var(--danger-dark);
        }

        /* Pesan */
        .pesan {
            margin-bottom: 18px;
            padding: 12px 14px;
            background: var(--tint);
            color: var(--navy-900);
            border-left: 4px solid var(--teal-500);
            border-radius: var(--radius-sm);
        }

        /* Tabel */
        .tabel-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
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

        .aksi {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .aksi form {
            margin: 0;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .panel {
                padding: 28px 14px 14px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Jadwal Dokter</h1>
    <p class="subjudul">
        Atur hari dan jam praktik dokter Puskesmas.
    </p>

    <p>
        <a href="index.php" class="tombol sekunder">
            &larr; Kembali ke Dashboard
        </a>
        <a href="dokter.php" class="tombol sekunder">
            Data Dokter
        </a>
    </p>

    <?php if ($pesan !== ""): ?>
        <div class="pesan"><?= e($pesan) ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2><?= $edit ? "Edit Jadwal" : "Tambah Jadwal" ?></h2>

        <?php if (count($dokterList) === 0): ?>
            <p>
                Belum ada dokter. Tambahkan dokter terlebih dahulu
                melalui menu Data Dokter.
            </p>
        <?php else: ?>

        <form method="POST" action="jadwal.php">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <input type="hidden" name="aksi" value="simpan">

            <input type="hidden" name="id"
                   value="<?= e($edit["id"] ?? "") ?>">

            <label>Nama Dokter</label>
            <select name="dokter_id" required>
                <option value="">-- Pilih Dokter --</option>

                <?php foreach ($dokterList as $d): ?>
                    <option
                        value="<?= (int) $d["id"] ?>"
                        <?= (int) ($edit["dokter_id"] ?? 0) === (int) $d["id"]
                            ? "selected" : "" ?>
                    >
                        <?= e($d["nama"]) ?>
                        - <?= e($d["spesialisasi"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Hari Praktik</label>
            <select name="hari" required>
                <option value="">-- Pilih Hari --</option>

                <?php foreach ($hariList as $hari): ?>
                    <option
                        value="<?= e($hari) ?>"
                        <?= ($edit["hari"] ?? "") === $hari
                            ? "selected" : "" ?>
                    >
                        <?= e($hari) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Jam Mulai</label>
            <input
                type="time"
                name="jam_mulai"
                value="<?= e(isset($edit["jam_mulai"])
                    ? substr($edit["jam_mulai"], 0, 5) : "") ?>"
                required
            >

            <label>Jam Selesai</label>
            <input
                type="time"
                name="jam_selesai"
                value="<?= e(isset($edit["jam_selesai"])
                    ? substr($edit["jam_selesai"], 0, 5) : "") ?>"
                required
            >

            <br><br>

            <button type="submit">
                <?= $edit ? "Simpan Perubahan" : "Tambah Jadwal" ?>
            </button>

            <?php if ($edit): ?>
                <a href="jadwal.php" class="tombol sekunder">
                    Batal
                </a>
            <?php endif; ?>
        </form>

        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Daftar Jadwal Praktik</h2>

        <div class="tabel-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Dokter</th>
                        <th>Spesialisasi</th>
                        <th>Hari</th>
                        <th>Jam Praktik</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($jadwalList) > 0): ?>
                    <?php foreach ($jadwalList as $i => $j): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($j["nama_dokter"]) ?></td>
                            <td><?= e($j["spesialisasi"]) ?></td>
                            <td><?= e($j["hari"]) ?></td>
                            <td>
                                <?= e(substr($j["jam_mulai"], 0, 5)) ?>
                                -
                                <?= e(substr($j["jam_selesai"], 0, 5)) ?>
                            </td>
                            <td>
                                <div class="aksi">
                                    <a
                                        class="tombol sekunder"
                                        href="jadwal.php?edit=<?= (int) $j["id"] ?>"
                                    >Edit</a>

                                    <form method="POST" action="jadwal.php"
                                        onsubmit="return confirm('Yakin ingin menghapus jadwal ini?')">

                                        <input type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                            name="aksi" value="hapus">

                                        <input type="hidden"
                                            name="id"
                                            value="<?= (int) $j["id"] ?>">

                                        <button class="hapus" type="submit">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Belum ada jadwal praktik.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>