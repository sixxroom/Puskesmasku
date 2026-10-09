<?php
session_start();
require_once "../koneksi.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pesan = "";
$edit = null;

$hariList = [
    "Senin", "Selasa", "Rabu", "Kamis",
    "Jumat", "Sabtu", "Minggu"
];

// Proses form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Permintaan tidak valid. Muat ulang halaman.");
    }

    $aksi = $_POST["aksi"] ?? "";

    try {
        // Tambah atau edit dokter beserta jadwal
        if ($aksi === "simpan") {
            $id = (int) ($_POST["id"] ?? 0);
            $nama = trim($_POST["nama"] ?? "");
            $spesialisasi = trim($_POST["spesialisasi"] ?? "");
            $no_hp = trim($_POST["no_hp"] ?? "");

            $hari = $_POST["hari"] ?? [];
            $jam_mulai = $_POST["jam_mulai"] ?? [];
            $jam_selesai = $_POST["jam_selesai"] ?? [];

            if ($nama === "" || $spesialisasi === "") {
                $pesan = "Nama dan spesialisasi wajib diisi.";
            } elseif (!is_array($hari) ||
                      !is_array($jam_mulai) ||
                      !is_array($jam_selesai)) {
                $pesan = "Format jadwal tidak valid.";
            } else {
                $jadwal = [];
                $valid = true;

                foreach ($hari as $i => $h) {
                    $mulai = $jam_mulai[$i] ?? "";
                    $selesai = $jam_selesai[$i] ?? "";

                    if (
                        !in_array($h, $hariList, true) ||
                        !preg_match('/^\d{2}:\d{2}$/', $mulai) ||
                        !preg_match('/^\d{2}:\d{2}$/', $selesai) ||
                        $mulai >= $selesai
                    ) {
                        $valid = false;
                        break;
                    }

                    $jadwal[] = [$h, $mulai, $selesai];
                }

                if (!$valid) {
                    $pesan = "Periksa kembali hari dan jam praktik.";
                } else {
                    $pdo->beginTransaction();

                    if ($id > 0) {
                        $stmt = $pdo->prepare(
                            "UPDATE dokter
                             SET nama = ?, spesialisasi = ?, no_hp = ?
                             WHERE id = ?"
                        );
                        $stmt->execute([
                            $nama, $spesialisasi, $no_hp, $id
                        ]);

                        // Ganti jadwal lama dengan jadwal yang dikirim
                        $stmt = $pdo->prepare(
                            "DELETE FROM jadwal_dokter WHERE dokter_id = ?"
                        );
                        $stmt->execute([$id]);

                        $dokter_id = $id;
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO dokter
                             (nama, spesialisasi, no_hp)
                             VALUES (?, ?, ?)"
                        );
                        $stmt->execute([
                            $nama, $spesialisasi, $no_hp
                        ]);

                        $dokter_id = (int) $pdo->lastInsertId();
                    }

                    $stmtJadwal = $pdo->prepare(
                        "INSERT INTO jadwal_dokter
                         (dokter_id, hari, jam_mulai, jam_selesai)
                         VALUES (?, ?, ?, ?)"
                    );

                    foreach ($jadwal as $j) {
                        $stmtJadwal->execute([
                            $dokter_id, $j[0], $j[1], $j[2]
                        ]);
                    }

                    $pdo->commit();

                    $pesan = "Data dokter dan jadwal berhasil disimpan.";
                }
            }
        }

        // Hapus dokter jika belum memiliki antrean
        elseif ($aksi === "hapus") {
            $id = (int) ($_POST["id"] ?? 0);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM antrean WHERE dokter_id = ?"
            );
            $stmt->execute([$id]);

            if ((int) $stmt->fetchColumn() > 0) {
                $pesan = "Dokter tidak bisa dihapus karena memiliki riwayat antrean.";
            } else {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "DELETE FROM jadwal_dokter WHERE dokter_id = ?"
                );
                $stmt->execute([$id]);

                $stmt = $pdo->prepare(
                    "DELETE FROM dokter WHERE id = ?"
                );
                $stmt->execute([$id]);

                $pdo->commit();

                $pesan = "Dokter berhasil dihapus.";
            }
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());
        $pesan = "Terjadi kesalahan. Periksa struktur tabel database.";
    }
}

// Data dokter dan jadwalnya
$stmt = $pdo->query(
    "SELECT * FROM dokter ORDER BY nama ASC"
);
$dokterList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dokter yang diedit
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM dokter WHERE id = ?"
    );
    $stmt->execute([(int) $_GET["edit"]]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($edit) {
        $stmt = $pdo->prepare(
            "SELECT hari, jam_mulai, jam_selesai
             FROM jadwal_dokter
             WHERE dokter_id = ?
             ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu',
                            'Kamis', 'Jumat', 'Sabtu', 'Minggu')"
        );
        $stmt->execute([$edit["id"]]);
        $jadwalEdit = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Ambil semua jadwal untuk tabel daftar dokter
$stmt = $pdo->query(
    "SELECT * FROM jadwal_dokter
     ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu',
                    'Kamis', 'Jumat', 'Sabtu', 'Minggu'),
              jam_mulai"
);
$semuaJadwal = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $j) {
    $semuaJadwal[$j["dokter_id"]][] = $j;
}

function e($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

if (!isset($jadwalEdit)) {
    $jadwalEdit = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Dokter - Puskesmas</title>

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
    --radius-md: 10px;
    --radius-lg: 14px;
    --shadow: 0 2px 10px rgba(13, 59, 102, 0.06);
    --transition: 0.2s ease;
}

* { box-sizing: border-box; }

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

.container { max-width: 1100px; margin: auto; }

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

.container > p { text-align: center; }

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

.panel h3 {
    margin: 26px 0 4px;
    color: var(--blue-700);
}

.panel h3 + p {
    margin: 0 0 14px;
    color: var(--text-muted);
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
    padding: 10px;
    background: var(--white);
    color: var(--text);
    font: inherit;
    border: 1px solid var(--blue-100);
    border-radius: var(--radius-sm);
    transition: border-color var(--transition), box-shadow var(--transition);
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
    transition: background var(--transition), color var(--transition),
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

.hapus, .hapus-baris {
    background: var(--danger);
    border-color: var(--danger);
}

.hapus:hover, .hapus-baris:hover {
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

/* Baris jadwal */
.jadwal-baris {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 10px;
    align-items: end;
    margin-bottom: 12px;
    padding: 12px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
}

.jadwal-baris label { margin-top: 0; }

/* Tabel */
.tabel-wrapper { overflow-x: auto; }

table {
    width: 100%;
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

tbody tr:nth-child(even) { background: #f6fafd; }
tbody tr:hover { background: var(--tint); }

td[colspan] {
    padding: 24px 12px;
    color: var(--text-muted);
    text-align: center;
}

.aksi { display: flex; gap: 7px; align-items: center; }
.aksi form { margin: 0; }

@media (max-width: 650px) {
    body { padding: 12px; }
    .panel { padding: 28px 14px 14px; }
    .jadwal-baris { grid-template-columns: 1fr; }
}
</style>
</head>

<body>
<div class="container">

<h1>Data Dokter</h1>
<p class="subjudul">Kelola informasi dan jadwal praktik dokter.</p>

<p>
    <a href="index.php" class="tombol sekunder">← Dashboard</a>
    <a href="jadwal.php" class="tombol sekunder">Kelola Jadwal</a>
</p>

<?php if ($pesan !== ""): ?>
    <div class="pesan"><?= e($pesan) ?></div>
<?php endif; ?>

<div class="panel">
<h2><?= $edit ? "Edit Dokter" : "Tambah Dokter" ?></h2>

<form method="POST" action="dokter.php">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
<input type="hidden" name="aksi" value="simpan">
<input type="hidden" name="id" value="<?= e($edit["id"] ?? "") ?>">

<label>Nama Dokter</label>
<input type="text" name="nama" required
       value="<?= e($edit["nama"] ?? "") ?>"
       placeholder="Contoh: dr. Andi">

<label>Spesialisasi</label>
<input type="text" name="spesialisasi" required
       value="<?= e($edit["spesialisasi"] ?? "") ?>"
       placeholder="Contoh: Dokter Umum">

<label>Nomor HP</label>
<input type="text" name="no_hp"
       value="<?= e($edit["no_hp"] ?? "") ?>"
       placeholder="Nomor HP dokter">

<h3>Jadwal Praktik</h3>
<p>Tambahkan satu atau beberapa hari praktik dokter.</p>

<div id="daftar-jadwal">
<?php foreach ($jadwalEdit as $j): ?>
    <div class="jadwal-baris">
        <div>
            <label>Hari</label>
            <select name="hari[]" required>
                <?php foreach ($hariList as $h): ?>
                    <option value="<?= e($h) ?>"
                        <?= $j["hari"] === $h ? "selected" : "" ?>>
                        <?= e($h) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Jam Mulai</label>
            <input type="time" name="jam_mulai[]" required
                   value="<?= e(substr($j["jam_mulai"], 0, 5)) ?>">
        </div>

        <div>
            <label>Jam Selesai</label>
            <input type="time" name="jam_selesai[]" required
                   value="<?= e(substr($j["jam_selesai"], 0, 5)) ?>">
        </div>

        <button type="button" class="hapus-baris"
                onclick="this.closest('.jadwal-baris').remove()">
            Hapus Hari
        </button>
    </div>
<?php endforeach; ?>
</div>

<button type="button" class="tombol sekunder" onclick="tambahJadwal()">
    + Tambah Hari Praktik
</button>

<br><br>

<button type="submit">
    <?= $edit ? "Simpan Perubahan" : "Simpan Dokter dan Jadwal" ?>
</button>

<?php if ($edit): ?>
    <a href="dokter.php" class="tombol sekunder">Batal</a>
<?php endif; ?>
</form>
</div>

<div class="panel">
<h2>Daftar Dokter</h2>

<div class="tabel-wrapper">
<table>
<thead>
<tr>
    <th>No.</th>
    <th>Nama Dokter</th>
    <th>Spesialisasi</th>
    <th>Nomor HP</th>
    <th>Jadwal Praktik</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>

<?php if (count($dokterList) > 0): ?>
<?php foreach ($dokterList as $i => $d): ?>
<tr>
    <td><?= $i + 1 ?></td>
    <td><?= e($d["nama"]) ?></td>
    <td><?= e($d["spesialisasi"]) ?></td>
    <td><?= e($d["no_hp"] ?? "-") ?></td>
    <td>
        <?php if (!empty($semuaJadwal[$d["id"]])): ?>
            <?php foreach ($semuaJadwal[$d["id"]] as $j): ?>
                <?= e($j["hari"]) ?>,
                <?= e(substr($j["jam_mulai"], 0, 5)) ?>–<?= e(substr($j["jam_selesai"], 0, 5)) ?>
                <br>
            <?php endforeach; ?>
        <?php else: ?>
            Belum ada jadwal
        <?php endif; ?>
    </td>
    <td>
        <div class="aksi">
            <a class="tombol sekunder"
               href="dokter.php?edit=<?= (int) $d["id"] ?>">Edit</a>

            <form method="POST" action="dokter.php"
                  onsubmit="return confirm('Yakin ingin menghapus dokter ini?')">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= (int) $d["id"] ?>">
                <button type="submit" class="hapus">Hapus</button>
            </form>
        </div>
    </td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6">Belum ada data dokter.</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>

</div>

<script>
function tambahJadwal() {
    const wadah = document.getElementById("daftar-jadwal");

    const baris = document.createElement("div");
    baris.className = "jadwal-baris";

    baris.innerHTML = `
        <div>
            <label>Hari</label>
            <select name="hari[]" required>
                <option value="">Pilih Hari</option>
                <option>Senin</option>
                <option>Selasa</option>
                <option>Rabu</option>
                <option>Kamis</option>
                <option>Jumat</option>
                <option>Sabtu</option>
                <option>Minggu</option>
            </select>
        </div>

        <div>
            <label>Jam Mulai</label>
            <input type="time" name="jam_mulai[]" required>
        </div>

        <div>
            <label>Jam Selesai</label>
            <input type="time" name="jam_selesai[]" required>
        </div>

        <button type="button" class="hapus-baris"
                onclick="this.closest('.jadwal-baris').remove()">
            Hapus Hari
        </button>
    `;

    wadah.appendChild(baris);
}

// Saat menambah dokter baru, tampilkan satu baris jadwal.
<?php if (!$edit): ?>
tambahJadwal();
<?php endif; ?>
</script>

</body>
</html>