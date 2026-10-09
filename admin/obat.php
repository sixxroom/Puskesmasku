<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya admin yang bisa mengakses
if (
    empty($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

// Fungsi keamanan output HTML
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Token keamanan form
if (empty($_SESSION["csrf_obat"])) {
    $_SESSION["csrf_obat"] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION["csrf_obat"];
$error = "";
$editObat = null;
$keyword = trim($_GET["cari"] ?? "");

// Proses tambah, edit, dan hapus
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf"]) ||
        !hash_equals($csrf, $_POST["csrf"])
    ) {
        die("Permintaan tidak valid. Silakan muat ulang halaman.");
    }

    $aksi = $_POST["aksi"] ?? "";

    try {
        if ($aksi === "tambah" || $aksi === "edit") {
            $nama = trim($_POST["nama_obat"] ?? "");
            $satuan = trim($_POST["satuan"] ?? "");
            $stokInput = $_POST["stok"] ?? "";
            $hargaInput = $_POST["harga"] ?? "";

            if (
                $nama === "" ||
                $satuan === "" ||
                $stokInput === "" ||
                $hargaInput === ""
            ) {
                throw new Exception("Semua kolom wajib diisi.");
            }

            if (
                !is_numeric($stokInput) ||
                (float)$stokInput < 0 ||
                (float)$stokInput != (int)$stokInput ||
                !is_numeric($hargaInput) ||
                (float)$hargaInput < 0 ||
                (float)$hargaInput != (int)$hargaInput
            ) {
                throw new Exception(
                    "Stok dan harga harus berupa angka bulat, minimal 0."
                );
            }

            $stok = (int)$stokInput;
            $harga = (int)$hargaInput;

            if ($aksi === "tambah") {
                $sql = "INSERT INTO obat
                        (nama_obat, satuan, stok, harga)
                        VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama, $satuan, $stok, $harga]);

                header("Location: obat.php?pesan=tambah");
                exit;
            }

            $id = filter_var(
                $_POST["id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new Exception("ID obat tidak valid.");
            }

            $sql = "UPDATE obat
                    SET nama_obat = ?, satuan = ?, stok = ?, harga = ?
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $satuan, $stok, $harga, $id]);

            header("Location: obat.php?pesan=edit");
            exit;
        }

        if ($aksi === "hapus") {
            $id = filter_var(
                $_POST["id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new Exception("ID obat tidak valid.");
            }

            $stmt = $pdo->prepare("DELETE FROM obat WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: obat.php?pesan=hapus");
            exit;
        }
    } catch (PDOException $ex) {
        if ($ex->getCode() === "23000") {
            $error = "Obat tidak bisa dihapus karena masih digunakan "
                   . "dalam transaksi. Periksa data terkait terlebih dahulu.";
        } else {
            $error = "Terjadi kesalahan database. Periksa kembali data "
                   . "dan struktur tabel obat.";
        }
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

// Ambil data untuk diedit
if (isset($_GET["edit"])) {
    $idEdit = filter_var($_GET["edit"], FILTER_VALIDATE_INT);

    if ($idEdit && $idEdit > 0) {
        $stmt = $pdo->prepare("SELECT * FROM obat WHERE id = ?");
        $stmt->execute([$idEdit]);
        $editObat = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Ambil daftar obat, dengan pencarian
if ($keyword !== "") {
    $stmt = $pdo->prepare(
        "SELECT * FROM obat
         WHERE nama_obat LIKE ?
         ORDER BY nama_obat ASC"
    );
    $stmt->execute(["%" . $keyword . "%"]);
    $daftarObat = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query(
        "SELECT * FROM obat ORDER BY nama_obat ASC"
    );
    $daftarObat = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalObat = count($daftarObat);
$pesan = $_GET["pesan"] ?? "";

$pesanTeks = [
    "tambah" => "Obat berhasil ditambahkan.",
    "edit" => "Data obat berhasil diperbarui.",
    "hapus" => "Permintaan penghapusan obat selesai."
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Obat - Admin Puskesmas</title>

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
            margin: 0 0 6px;
            color: var(--navy-900);
            text-align: center;
        }

        .panel > p.muted {
            margin: 0 0 14px;
            color: var(--text-muted);
            text-align: center;
        }

        /* Form */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            max-width: 760px;
            margin: 14px auto 0;
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

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        /* Form pencarian */
        .search-form {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .search-form input {
            flex: 1;
            max-width: 480px;
            min-width: 220px;
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

        .button-danger {
            background: var(--white);
            color: #9b1c1c;
            border: 1px solid #f0b7b2;
        }

        .button-danger:hover {
            background: #fdecec;
            border-color: #c53030;
        }

        .button-small {
            padding: 6px 12px;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
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
            white-space: nowrap;
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .search-form input {
                max-width: none;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="content">
        <a class="top-link" href="index.php">← Kembali ke Dashboard Admin</a>

        <h1>Data Obat</h1>
        <p class="subjudul">
            Kelola nama obat, satuan, stok, dan harga obat Puskesmas.
        </p>

        <?php if ($error !== ""): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if ($pesan !== "" && isset($pesanTeks[$pesan])): ?>
            <p class="success"><?= e($pesanTeks[$pesan]) ?></p>
        <?php endif; ?>

        <section class="panel">
            <h2><?= $editObat ? "Edit Data Obat" : "Tambah Obat Baru" ?></h2>

            <form method="post" action="obat.php">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="aksi"
                       value="<?= $editObat ? "edit" : "tambah" ?>">

                <?php if ($editObat): ?>
                    <input type="hidden" name="id"
                           value="<?= e($editObat["id"]) ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div>
                        <label for="nama_obat">Nama Obat</label>
                        <input id="nama_obat" name="nama_obat" required
                               maxlength="255"
                               value="<?= e($editObat["nama_obat"] ?? "") ?>"
                               placeholder="Contoh: Paracetamol">
                    </div>

                    <div>
                        <label for="satuan">Satuan</label>
                        <input id="satuan" name="satuan" required
                               maxlength="50"
                               value="<?= e($editObat["satuan"] ?? "") ?>"
                               placeholder="Contoh: Tablet, botol, strip">
                    </div>

                    <div>
                        <label for="stok">Stok</label>
                        <input id="stok" name="stok" type="number"
                               min="0" step="1" required
                               value="<?= e($editObat["stok"] ?? "0") ?>">
                    </div>

                    <div>
                        <label for="harga">Harga per Satuan (Rp)</label>
                        <input id="harga" name="harga" type="number"
                               min="0" step="1" required
                               value="<?= e($editObat["harga"] ?? "0") ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button">
                        <?= $editObat ? "Simpan Perubahan" : "Tambah Obat" ?>
                    </button>

                    <?php if ($editObat): ?>
                        <a href="obat.php" class="button button-outline">
                            Batal Edit
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Daftar Obat</h2>
            <p class="muted">Jumlah hasil: <?= $totalObat ?> obat</p>

            <form method="get" class="search-form">
                <input type="text" name="cari"
                       placeholder="Cari nama obat..."
                       aria-label="Cari nama obat"
                       value="<?= e($keyword) ?>">
                <button type="submit" class="button">Cari</button>
                <a href="obat.php" class="button button-outline">Reset</a>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Obat</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!$daftarObat): ?>
                        <tr>
                            <td colspan="6">Belum ada data obat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarObat as $i => $obat): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($obat["nama_obat"]) ?></td>
                                <td><?= e($obat["satuan"]) ?></td>
                                <td><?= e($obat["stok"]) ?></td>
                                <td>Rp <?= number_format(
                                    (float)$obat["harga"], 0, ",", "."
                                ) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="button button-small"
                                           href="obat.php?edit=<?= e($obat["id"]) ?>">
                                            Edit
                                        </a>

                                        <form method="post" action="obat.php"
                                              onsubmit="return confirm(
                                                  'Yakin ingin menghapus obat ini?'
                                              )">
                                            <input type="hidden" name="csrf"
                                                   value="<?= e($csrf) ?>">
                                            <input type="hidden" name="aksi"
                                                   value="hapus">
                                            <input type="hidden" name="id"
                                                   value="<?= e($obat["id"]) ?>">
                                            <button type="submit"
                                                    class="button button-small button-danger">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
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