<?php
session_start();
require_once "../koneksi.php";

// Hanya admin yang boleh mengakses halaman ini.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pesan = "";
$error = "";

// Proses perubahan status antrean.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $status = $_POST["status"] ?? "";
    $token = $_POST["csrf_token"] ?? "";
    $tanggalForm = $_POST["tanggal"] ?? "";

    $daftarStatus = ["Menunggu", "Dipanggil", "Selesai", "Batal"];

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Permintaan tidak valid. Silakan coba lagi.";
    } elseif (!$id || !in_array($status, $daftarStatus, true)) {
        $error = "Data antrean atau status tidak valid.";
    } else {
        $stmt = $pdo->prepare(
            "UPDATE antrean SET status = ? WHERE id = ?"
        );
        $stmt->execute([$status, $id]);

        header(
            "Location: antrean.php?tanggal=" .
            urlencode($tanggalForm) . "&berhasil=1"
        );
        exit;
    }
}

// Pilih tanggal antrean yang ingin dilihat.
$tanggal = $_GET["tanggal"] ?? date("Y-m-d");

$dateObj = DateTime::createFromFormat("!Y-m-d", $tanggal);
if (
    !$dateObj ||
    $dateObj->format("Y-m-d") !== $tanggal
) {
    $tanggal = date("Y-m-d");
}

if (isset($_GET["berhasil"])) {
    $pesan = "Status antrean berhasil diperbarui.";
}

// Ambil data antrean beserta pasien dan dokter.
$sql = "SELECT
            a.id,
            a.nomor_antrean,
            a.tanggal,
            a.status,
            p.nama AS nama_pasien,
            p.nik,
            d.nama AS nama_dokter
        FROM antrean a
        JOIN pasien p ON a.pasien_id = p.id
        JOIN dokter d ON a.dokter_id = d.id
        WHERE a.tanggal = ?
        ORDER BY a.nomor_antrean ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal]);
$antrean = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Antrean</title>

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

        /* Form filter */
        .filter {
            display: flex;
            align-items: end;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: var(--blue-700);
            font-weight: bold;
        }

        input, select {
            padding: 10px;
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

        /* Tabel */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 760px;
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

        .status-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .status-form select {
            min-width: 125px;
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

        <h1>Manajemen Antrean</h1>
        <p>Lihat antrean pasien dan perbarui status pelayanan.</p>

        <?php if ($pesan): ?>
            <p class="success"><?= e($pesan) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <section class="panel">
            <form method="GET" class="filter">
                <div>
                    <label for="tanggal">Tanggal antrean</label>
                    <input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        value="<?= e($tanggal) ?>"
                        required
                    >
                </div>
                <button type="submit" class="button">Tampilkan</button>
            </form>
        </section>

        <section class="panel">
            <h2>Daftar Antrean</h2>
            <p>Tanggal: <?= e($tanggal) ?></p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No. Antrean</th>
                            <th>Nama Pasien</th>
                            <th>NIK</th>
                            <th>Dokter</th>
                            <th>Status</th>
                            <th>Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($antrean)): ?>
                        <tr>
                            <td colspan="6">
                                Belum ada antrean pada tanggal ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($antrean as $a): ?>
                            <tr>
                                <td><?= e($a["nomor_antrean"]) ?></td>
                                <td><?= e($a["nama_pasien"]) ?></td>
                                <td><?= e($a["nik"]) ?></td>
                                <td><?= e($a["nama_dokter"]) ?></td>
                                <td><?= e($a["status"]) ?></td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION["csrf_token"]) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= e($a["id"]) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="tanggal"
                                            value="<?= e($tanggal) ?>"
                                        >

                                        <select name="status" required>
                                            <?php foreach (
                                                ["Menunggu", "Dipanggil", "Selesai", "Batal"]
                                                as $s
                                            ): ?>
                                                <option
                                                    value="<?= e($s) ?>"
                                                    <?= $a["status"] === $s ? "selected" : "" ?>
                                                >
                                                    <?= e($s) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" class="button">
                                            Simpan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>