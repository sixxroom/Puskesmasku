<?php
session_start();
require_once "../koneksi.php";

// Hanya admin yang boleh mengakses
if (
    empty($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

// Fungsi keamanan output
function e($nilai): string
{
    return htmlspecialchars(
        (string) $nilai,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Token keamanan form
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pesan = "";
$berhasil = $_SESSION["kode_obat_berhasil"] ?? null;
unset($_SESSION["kode_obat_berhasil"]);

// Membuat kode pengambilan unik
function buatKodeObat(PDO $pdo): string
{
    $karakter = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";

    for ($percobaan = 0; $percobaan < 10; $percobaan++) {
        $kode = "OBT-";

        for ($i = 0; $i < 6; $i++) {
            $kode .= $karakter[random_int(
                0,
                strlen($karakter) - 1
            )];
        }

        $stmt = $pdo->prepare(
            "SELECT id FROM transaksi_obat
             WHERE kode_pengambilan = ?"
        );

        $stmt->execute([$kode]);

        if (!$stmt->fetch()) {
            return $kode;
        }
    }

    throw new RuntimeException(
        "Gagal membuat kode. Silakan coba lagi."
    );
}

// ==================================================
// PROSES TRANSAKSI OBAT
// ==================================================

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

    $pemeriksaanId = filter_var(
        $_POST["pemeriksaan_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $obatIds = $_POST["obat_id"] ?? [];
    $jumlahs = $_POST["jumlah"] ?? [];
    $aturanPakais = $_POST["aturan_pakai"] ?? [];

    $pilihanObat = [];

    // Validasi bentuk data form
    if (
        !is_array($obatIds) ||
        !is_array($jumlahs) ||
        !is_array($aturanPakais)
    ) {
        $pesan = "Format data obat tidak valid.";
    }

    // Validasi setiap obat
    if ($pesan === "") {
        foreach ($obatIds as $index => $obatIdInput) {

            $obatId = filter_var(
                $obatIdInput,
                FILTER_VALIDATE_INT
            );

            $jumlahInput = $jumlahs[$index] ?? "";
            $aturanPakai = trim(
                (string) ($aturanPakais[$index] ?? "")
            );

            // Lewati baris kosong
            if (
                ($obatId === false || $obatId === 0 || $obatId === null) &&
                $jumlahInput === "" &&
                $aturanPakai === ""
            ) {
                continue;
            }

            $jumlah = filter_var(
                $jumlahInput,
                FILTER_VALIDATE_INT
            );

            if (
                !$obatId ||
                $obatId < 1 ||
                $jumlah === false ||
                $jumlah < 1
            ) {
                $pesan = "Pilih obat dan isi jumlah dengan benar.";
                break;
            }

            if ($aturanPakai === "") {
                $pesan = "Aturan pakai setiap obat wajib diisi.";
                break;
            }

            if (strlen($aturanPakai) > 255) {
                $pesan = "Aturan pakai maksimal 255 karakter.";
                break;
            }

            // Gabungkan obat yang sama jika aturan pakainya sama
            if (isset($pilihanObat[$obatId])) {

                if (
                    $pilihanObat[$obatId]["aturan_pakai"]
                    !== $aturanPakai
                ) {
                    $pesan =
                        "Obat yang sama dipilih dengan aturan pakai berbeda. "
                        . "Gabungkan menjadi satu baris.";
                    break;
                }

                $pilihanObat[$obatId]["jumlah"] += $jumlah;

            } else {
                $pilihanObat[$obatId] = [
                    "jumlah" => $jumlah,
                    "aturan_pakai" => $aturanPakai
                ];
            }
        }
    }

    if (
        $pesan === "" &&
        (!$pemeriksaanId || $pemeriksaanId < 1)
    ) {
        $pesan = "Pilih pemeriksaan pasien terlebih dahulu.";
    }

    if ($pesan === "" && empty($pilihanObat)) {
        $pesan = "Pilih minimal satu obat.";
    }

    // Simpan transaksi secara atomik
    if ($pesan === "") {
        try {
            $pdo->beginTransaction();

            // Kunci data pemeriksaan
            $stmt = $pdo->prepare(
                "SELECT id
                 FROM pemeriksaan
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$pemeriksaanId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    "Pemeriksaan pasien tidak ditemukan."
                );
            }

            // Satu pemeriksaan hanya memiliki satu transaksi obat
            $stmt = $pdo->prepare(
                "SELECT id
                 FROM transaksi_obat
                 WHERE pemeriksaan_id = ?"
            );

            $stmt->execute([$pemeriksaanId]);

            if ($stmt->fetch()) {
                throw new RuntimeException(
                    "Pemeriksaan ini sudah memiliki transaksi obat."
                );
            }

            $daftarDetail = [];

            // Periksa stok semua obat terlebih dahulu
            foreach ($pilihanObat as $obatId => $pilihan) {

                $jumlah = $pilihan["jumlah"];

                $stmt = $pdo->prepare(
                    "SELECT id, nama_obat, stok, harga
                     FROM obat
                     WHERE id = ?
                     FOR UPDATE"
                );

                $stmt->execute([$obatId]);

                $obat = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$obat) {
                    throw new RuntimeException(
                        "Ada obat yang tidak ditemukan."
                    );
                }

                if ((int) $obat["stok"] < $jumlah) {
                    throw new RuntimeException(
                        "Stok " . $obat["nama_obat"]
                        . " tidak mencukupi. Stok tersedia: "
                        . $obat["stok"]
                    );
                }

                $daftarDetail[] = [
                    "id" => (int) $obat["id"],
                    "jumlah" => $jumlah,
                    "harga" => $obat["harga"],
                    "aturan_pakai" => $pilihan["aturan_pakai"]
                ];
            }

            // Buat kode pengambilan
            $kode = buatKodeObat($pdo);

            // Simpan transaksi utama
            $stmt = $pdo->prepare(
                "INSERT INTO transaksi_obat
                 (pemeriksaan_id, kode_pengambilan, tanggal, status)
                 VALUES (?, ?, CURDATE(), 'Belum Diambil')"
            );

            $stmt->execute([$pemeriksaanId, $kode]);

            $transaksiId = (int) $pdo->lastInsertId();

            // Simpan detail obat beserta aturan pakai
            $stmtDetail = $pdo->prepare(
                "INSERT INTO detail_obat
                 (transaksi_id, obat_id, jumlah, harga_satuan, aturan_pakai)
                 VALUES (?, ?, ?, ?, ?)"
            );

            // Kurangi stok dengan pengecekan ulang
            $stmtStok = $pdo->prepare(
                "UPDATE obat
                 SET stok = stok - ?
                 WHERE id = ? AND stok >= ?"
            );

            foreach ($daftarDetail as $detail) {

                $stmtDetail->execute([
                    $transaksiId,
                    $detail["id"],
                    $detail["jumlah"],
                    $detail["harga"],
                    $detail["aturan_pakai"]
                ]);

                $stmtStok->execute([
                    $detail["jumlah"],
                    $detail["id"],
                    $detail["jumlah"]
                ]);

                if ($stmtStok->rowCount() !== 1) {
                    throw new RuntimeException(
                        "Stok berubah atau tidak mencukupi. "
                        . "Silakan ulangi transaksi."
                    );
                }
            }

            $pdo->commit();

            // Tampilkan kode setelah halaman dimuat ulang
            $_SESSION["kode_obat_berhasil"] = $kode;

            header("Location: transaksi_obat.php");
            exit;

        } catch (Throwable $ex) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($ex instanceof RuntimeException) {
                $pesan = $ex->getMessage();
            } else {
                error_log($ex->getMessage());

                $pesan =
                    "Transaksi gagal disimpan. "
                    . "Periksa struktur database dan coba lagi.";
            }
        }
    }
}

// ==================================================
// DATA PEMERIKSAAN YANG BELUM MEMILIKI TRANSAKSI
// ==================================================

$stmt = $pdo->query(
    "SELECT
        pm.id AS pemeriksaan_id,
        pm.diagnosis,
        pm.tanggal_pemeriksaan,
        p.nama AS nama_pasien,
        p.nik,
        a.nomor_antrean,
        a.tanggal AS tanggal_antrean,
        d.nama AS nama_dokter
     FROM pemeriksaan pm
     JOIN antrean a ON pm.antrean_id = a.id
     JOIN pasien p ON a.pasien_id = p.id
     JOIN dokter d ON a.dokter_id = d.id
     LEFT JOIN transaksi_obat t ON t.pemeriksaan_id = pm.id
     WHERE t.id IS NULL
     ORDER BY pm.id DESC"
);

$daftarPemeriksaan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================================================
// DATA OBAT YANG STOKNYA TERSEDIA
// ==================================================

$stmt = $pdo->query(
    "SELECT id, nama_obat, satuan, stok, harga
     FROM obat
     WHERE stok > 0
     ORDER BY nama_obat ASC"
);

$daftarObat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================================================
// RIWAYAT TRANSAKSI OBAT
// ==================================================

$stmt = $pdo->query(
    "SELECT
        t.id,
        t.kode_pengambilan,
        t.tanggal,
        t.status,
        p.nama AS nama_pasien,
        a.nomor_antrean,
        COUNT(DISTINCT det.obat_id) AS jumlah_jenis_obat
     FROM transaksi_obat t
     JOIN pemeriksaan pm ON t.pemeriksaan_id = pm.id
     JOIN antrean a ON pm.antrean_id = a.id
     JOIN pasien p ON a.pasien_id = p.id
     LEFT JOIN detail_obat det ON det.transaksi_id = t.id
     GROUP BY
        t.id, t.kode_pengambilan, t.tanggal,
        t.status, p.nama, a.nomor_antrean
     ORDER BY t.id DESC"
);

$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Obat - Puskesmas</title>

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

        .panel h3 {
            margin: 24px 0 4px;
            color: var(--navy-900);
        }

        .panel p.subjudul {
            margin: 0 0 14px;
            color: var(--text-muted);
        }

        .panel > p {
            text-align: center;
            color: var(--text-muted);
        }

        /* Form */
        .form-transaksi {
            max-width: 900px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin: 14px 0 6px;
            color: var(--blue-700);
            font-weight: bold;
        }

        select,
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

        select:focus,
        input:focus {
            outline: none;
            border-color: var(--blue-300);
            box-shadow: 0 0 0 3px rgba(111, 163, 216, 0.3);
        }

        .obat-row {
            display: grid;
            grid-template-columns: 2fr 0.8fr 2fr auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 14px;
            padding: 4px 14px 14px;
            background: #f6fafd;
            border: 1px solid var(--border);
            border-left: 4px solid var(--teal-500);
            border-radius: var(--radius-sm);
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
            border: 1px solid var(--blue-700);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition),
                        border-color var(--transition);
        }

        .button:hover {
            background: var(--navy-900);
            border-color: var(--navy-900);
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

        .button-danger {
            background: var(--white);
            color: #9b1c1c;
            border: 1px solid #f0b7b2;
        }

        .button-danger:hover {
            background: #fdecec;
            border-color: #c53030;
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

        td small {
            color: var(--text-muted);
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
        .pesan {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-left: 4px solid;
            border-radius: var(--radius-sm);
        }

        .pesan.error {
            background: #fdecec;
            color: #9b1c1c;
            border-left-color: #c53030;
        }

        .pesan.sukses {
            background: var(--tint);
            color: var(--navy-900);
            border-left-color: var(--teal-500);
            text-align: center;
        }

        .kode {
            display: block;
            margin: 10px 0;
            color: var(--blue-700);
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 3px;
        }

        @media (max-width: 700px) {
            .panel {
                padding: 28px 14px 14px;
            }

            .obat-row {
                grid-template-columns: 1fr;
            }

            .kode {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <main class="content">
        <a class="top-link" href="index.php">← Kembali ke Dashboard Admin</a>

        <h1>Transaksi Obat</h1>

        <p class="subjudul">
            Pilih pemeriksaan pasien, masukkan obat dan aturan pakai,
            lalu buat kode pengambilan.
        </p>

        <?php if ($pesan !== ""): ?>
            <div class="pesan error">
                <?= e($pesan) ?>
            </div>
        <?php endif; ?>

        <?php if ($berhasil): ?>
            <div class="pesan sukses">
                <strong>Transaksi obat berhasil dibuat!</strong>

                <span class="kode"><?= e($berhasil) ?></span>

                Simpan kode ini untuk pengambilan obat oleh pasien.

                <div class="form-actions">
                    <button
                        type="button"
                        class="button button-outline"
                        onclick="salinKode(<?= e(json_encode($berhasil)) ?>)">
                        Salin Kode
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <section class="panel">
            <h2>Buat Transaksi Baru</h2>

            <?php if (empty($daftarPemeriksaan)): ?>

                <p>
                    Tidak ada pemeriksaan yang menunggu transaksi obat.
                    Pastikan pasien sudah diperiksa dan belum memiliki
                    transaksi obat.
                </p>

            <?php elseif (empty($daftarObat)): ?>

                <p>
                    Belum ada obat dengan stok tersedia.
                    Tambahkan obat atau perbarui stok terlebih dahulu.
                </p>

            <?php else: ?>

                <form method="POST" id="formTransaksi" class="form-transaksi">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["csrf_token"]) ?>">

                    <label for="pemeriksaan_id">
                        Pasien / Hasil Pemeriksaan
                    </label>

                    <select
                        name="pemeriksaan_id"
                        id="pemeriksaan_id"
                        required>

                        <option value="">-- Pilih pasien --</option>

                        <?php foreach ($daftarPemeriksaan as $p): ?>
                            <option value="<?= (int) $p["pemeriksaan_id"] ?>">
                                <?= e($p["nama_pasien"]) ?>
                                | Antrean <?= e($p["nomor_antrean"]) ?>
                                | <?= e($p["tanggal_antrean"]) ?>
                                | Diagnosis: <?= e($p["diagnosis"]) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <h3>Daftar Obat</h3>

                    <p class="subjudul">
                        Isi sesuai resep atau instruksi tenaga medis.
                        Aturan pakai akan ditampilkan kepada pasien.
                    </p>

                    <div id="daftarObat">

                        <div class="obat-row">

                            <div>
                                <label>Nama Obat</label>

                                <select name="obat_id[]" required>
                                    <option value="0">-- Pilih obat --</option>

                                    <?php foreach ($daftarObat as $o): ?>
                                        <option value="<?= (int) $o["id"] ?>">
                                            <?= e($o["nama_obat"]) ?>
                                            | Stok: <?= (int) $o["stok"] ?>
                                            <?= e($o["satuan"]) ?>
                                            | Rp <?= number_format(
                                                (float) $o["harga"],
                                                0,
                                                ",",
                                                "."
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div>
                                <label>Jumlah</label>

                                <input
                                    type="number"
                                    name="jumlah[]"
                                    min="1"
                                    step="1"
                                    placeholder="Jumlah"
                                    required>
                            </div>

                            <div>
                                <label>Aturan Pakai</label>

                                <input
                                    type="text"
                                    name="aturan_pakai[]"
                                    maxlength="255"
                                    placeholder="Sesuai instruksi tenaga medis"
                                    required>
                            </div>

                            <div>
                                <button
                                    type="button"
                                    class="button button-danger"
                                    onclick="hapusObat(this)">
                                    Hapus
                                </button>
                            </div>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="button button-outline"
                        onclick="tambahObat()">
                        + Tambah Obat
                    </button>

                    <p class="subjudul" style="margin-top: 16px; text-align: center;">
                        Stok diperiksa kembali saat transaksi disimpan.
                    </p>

                    <div class="form-actions">
                        <button
                            type="submit"
                            class="button"
                            onclick="return confirm(
                                'Buat transaksi dan kurangi stok obat sesuai jumlah?'
                            )">
                            Buat Transaksi &amp; Kode
                        </button>
                    </div>

                </form>

            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Riwayat Transaksi Obat</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pasien</th>
                            <th>Kode Pengambilan</th>
                            <th>Jenis Obat</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($riwayat)): ?>

                            <tr>
                                <td colspan="5">
                                    Belum ada transaksi obat.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($riwayat as $r): ?>
                                <tr>
                                    <td><?= e($r["tanggal"]) ?></td>

                                    <td>
                                        <?= e($r["nama_pasien"]) ?><br>
                                        <small>
                                            Antrean <?= e($r["nomor_antrean"]) ?>
                                        </small>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= e($r["kode_pengambilan"]) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= (int) $r["jumlah_jenis_obat"] ?> jenis
                                    </td>

                                    <td>
                                        <span class="status">
                                            <?= e($r["status"]) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </section>
    </main>

<script>
function tambahObat() {
    const daftar = document.getElementById("daftarObat");
    const baris = daftar.querySelector(".obat-row");
    const baru = baris.cloneNode(true);

    baru.querySelector("select").selectedIndex = 0;

    baru.querySelector('input[name="jumlah[]"]').value = "";

    baru.querySelector('input[name="aturan_pakai[]"]').value = "";

    daftar.appendChild(baru);
}

function hapusObat(tombol) {
    const daftar = document.getElementById("daftarObat");
    const baris = tombol.closest(".obat-row");

    if (daftar.querySelectorAll(".obat-row").length > 1) {
        baris.remove();
    } else {
        baris.querySelector("select").selectedIndex = 0;
        baris.querySelector('input[name="jumlah[]"]').value = "";
        baris.querySelector('input[name="aturan_pakai[]"]').value = "";
    }
}

function salinKode(kode) {
    navigator.clipboard.writeText(kode)
        .then(() => {
            alert("Kode berhasil disalin.");
        })
        .catch(() => {
            alert("Gagal menyalin kode. Silakan salin secara manual.");
        });
}
</script>

</body>
</html>