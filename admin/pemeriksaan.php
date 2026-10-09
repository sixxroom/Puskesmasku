<?php
session_start();
require_once "../koneksi.php";

// Hanya admin yang bisa mengakses
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
$pesanSukses = false;
$edit = null;

// Proses simpan pemeriksaan
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Permintaan tidak valid. Silakan muat ulang halaman.");
    }

    $aksi = $_POST["aksi"] ?? "";

    if ($aksi === "simpan") {
        $antrean_id = (int) ($_POST["antrean_id"] ?? 0);
        $keluhan = trim($_POST["keluhan"] ?? "");
        $diagnosis = trim($_POST["diagnosis"] ?? "");
        $catatan = trim($_POST["catatan"] ?? "");

        if ($antrean_id <= 0 || $keluhan === "" || $diagnosis === "") {
            $pesan = "Antrean, keluhan, dan diagnosis wajib diisi.";
        } else {
            try {
                // Pastikan antrean ada dan belum dibatalkan
                $stmt = $pdo->prepare(
                    "SELECT id, status
                     FROM antrean
                     WHERE id = ?"
                );
                $stmt->execute([$antrean_id]);
                $antrean = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$antrean || $antrean["status"] === "Batal") {
                    $pesan = "Antrean tidak ditemukan atau sudah dibatalkan.";
                } else {
                    // Cek apakah pemeriksaan untuk antrean ini sudah ada
                    $stmt = $pdo->prepare(
                        "SELECT id FROM pemeriksaan WHERE antrean_id = ?"
                    );
                    $stmt->execute([$antrean_id]);
                    $pemeriksaanLama = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($pemeriksaanLama) {
                        $stmt = $pdo->prepare(
                            "UPDATE pemeriksaan
                             SET keluhan = ?, diagnosis = ?, catatan = ?
                             WHERE antrean_id = ?"
                        );

                        $stmt->execute([
                            $keluhan,
                            $diagnosis,
                            $catatan,
                            $antrean_id
                        ]);

                        $pesan = "Hasil pemeriksaan berhasil diperbarui.";
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO pemeriksaan
                             (antrean_id, keluhan, diagnosis, catatan,
                              tanggal_pemeriksaan)
                             VALUES (?, ?, ?, ?, NOW())"
                        );

                        $stmt->execute([
                            $antrean_id,
                            $keluhan,
                            $diagnosis,
                            $catatan
                        ]);

                        $pesan = "Hasil pemeriksaan berhasil disimpan.";
                    }

                    // Tandai antrean sudah selesai diperiksa
                    $stmt = $pdo->prepare(
                        "UPDATE antrean
                         SET status = 'Selesai'
                         WHERE id = ?"
                    );
                    $stmt->execute([$antrean_id]);

                    $pesanSukses = true;
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $pesan = "Gagal menyimpan pemeriksaan. Periksa struktur tabel database.";
            }
        }
    }
}

// Ambil antrean yang dapat diperiksa.
// Antrean yang sudah punya pemeriksaan tetap ditampilkan agar bisa diedit.
$sql = "SELECT
            a.id AS antrean_id,
            a.nomor_antrean,
            a.tanggal,
            a.status,
            p.nama AS nama_pasien,
            p.nik,
            d.nama AS nama_dokter,
            pm.id AS pemeriksaan_id,
            pm.keluhan,
            pm.diagnosis,
            pm.catatan,
            pm.tanggal_pemeriksaan
        FROM antrean a
        JOIN pasien p ON a.pasien_id = p.id
        JOIN dokter d ON a.dokter_id = d.id
        LEFT JOIN pemeriksaan pm ON pm.antrean_id = a.id
        WHERE a.status != 'Batal'
        ORDER BY a.tanggal DESC, a.nomor_antrean ASC";

$stmt = $pdo->query($sql);
$daftarAntrean = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Antrean yang dipilih untuk diedit
if (isset($_GET["edit"])) {
    $idEdit = (int) $_GET["edit"];

    foreach ($daftarAntrean as $a) {
        if ((int) $a["antrean_id"] === $idEdit) {
            $edit = $a;
            break;
        }
    }
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
    <title>Pemeriksaan Pasien - Puskesmas</title>

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

        .content > p.subjudul {
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
            margin: 0 0 14px;
            color: var(--navy-900);
            text-align: center;
        }

        /* Form */
        .form-pemeriksaan {
            max-width: 760px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin: 14px 0 6px;
            color: var(--blue-700);
            font-weight: bold;
        }

        input, select, textarea {
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

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--blue-300);
            box-shadow: 0 0 0 3px rgba(111, 163, 216, 0.3);
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 22px;
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
            min-width: 820px;
            border-collapse: collapse;
            background: var(--white);
        }

        th, td {
            padding: 12px;
            text-align: left;
            vertical-align: top;
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

        .status {
            display: inline-block;
            padding: 4px 10px;
            background: var(--tint);
            color: var(--blue-700);
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
            border: 1px solid var(--blue-100);
            border-radius: 999px;
        }

        /* Pesan */
        .content > p.success,
        .content > p.error {
            margin: 0 0 16px;
            padding: 12px 14px;
            text-align: left;
            border-left: 4px solid;
            border-radius: var(--radius-sm);
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
            .panel {
                padding: 28px 14px 14px;
            }
        }
    </style>
</head>

<body>
    <main class="content">
        <a class="top-link" href="index.php">← Kembali ke Dashboard Admin</a>

        <h1>Pemeriksaan Pasien</h1>
        <p class="subjudul">
            Catat keluhan, diagnosis, dan catatan pemeriksaan pasien.
        </p>

        <?php if ($pesan !== ""): ?>
            <p class="<?= $pesanSukses ? "success" : "error" ?>">
                <?= e($pesan) ?>
            </p>
        <?php endif; ?>

        <section class="panel">
            <h2>
                <?= $edit ? "Edit Hasil Pemeriksaan" : "Input Pemeriksaan" ?>
            </h2>

            <form method="POST" action="pemeriksaan.php" class="form-pemeriksaan">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION["csrf_token"]) ?>">

                <input type="hidden" name="aksi" value="simpan">

                <label for="antrean_id">Pasien dan Nomor Antrean</label>
                <select id="antrean_id" name="antrean_id" required>
                    <option value="">-- Pilih Antrean Pasien --</option>

                    <?php foreach ($daftarAntrean as $a): ?>
                        <?php if ($a["status"] !== "Selesai" || $a["pemeriksaan_id"]): ?>
                            <option
                                value="<?= (int) $a["antrean_id"] ?>"
                                <?= (int) ($edit["antrean_id"] ?? 0) === (int) $a["antrean_id"]
                                    ? "selected" : "" ?>
                            >
                                <?= e($a["tanggal"]) ?>
                                - Antrean <?= e($a["nomor_antrean"]) ?>
                                - <?= e($a["nama_pasien"]) ?>
                                - <?= e($a["nama_dokter"]) ?>
                                <?= $a["pemeriksaan_id"] ? "(Sudah diperiksa)" : "" ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>

                <label for="keluhan">Keluhan Pasien</label>
                <textarea id="keluhan" name="keluhan" required
                    placeholder="Tuliskan keluhan pasien"><?= e($edit["keluhan"] ?? "") ?></textarea>

                <label for="diagnosis">Diagnosis</label>
                <textarea id="diagnosis" name="diagnosis" required
                    placeholder="Tuliskan diagnosis berdasarkan pemeriksaan"><?= e($edit["diagnosis"] ?? "") ?></textarea>

                <label for="catatan">Catatan Pemeriksaan</label>
                <textarea id="catatan" name="catatan"
                    placeholder="Catatan tambahan, jika ada"><?= e($edit["catatan"] ?? "") ?></textarea>

                <div class="form-actions">
                    <button type="submit" class="button">
                        <?= $edit ? "Simpan Perubahan" : "Simpan Pemeriksaan" ?>
                    </button>

                    <?php if ($edit): ?>
                        <a href="pemeriksaan.php" class="button button-outline">
                            Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Riwayat Pemeriksaan</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Antrean</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Diagnosis</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    $adaPemeriksaan = false;
                    foreach ($daftarAntrean as $a):
                        if (!$a["pemeriksaan_id"]) {
                            continue;
                        }
                        $adaPemeriksaan = true;
                    ?>
                        <tr>
                            <td><?= e($a["tanggal"]) ?></td>
                            <td><?= e($a["nomor_antrean"]) ?></td>
                            <td><?= e($a["nama_pasien"]) ?></td>
                            <td><?= e($a["nama_dokter"]) ?></td>
                            <td><?= e($a["diagnosis"]) ?></td>
                            <td>
                                <span class="status"><?= e($a["status"]) ?></span>
                            </td>
                            <td>
                                <a
                                    class="button button-small"
                                    href="pemeriksaan.php?edit=<?= (int) $a["antrean_id"] ?>"
                                >
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$adaPemeriksaan): ?>
                        <tr>
                            <td colspan="7">Belum ada riwayat pemeriksaan.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>