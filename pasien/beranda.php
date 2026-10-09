```php
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Pasien Puskesmas</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #ffffff;
            color: #333333;
        }

        header {
            padding: 20px 8%;
            border-bottom: 1px solid #eeeeee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #1976d2;
        }

        .logo span {
            color: #333333;
        }

        .container {
            max-width: 1000px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h1 {
            font-size: 27px;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #777777;
            line-height: 1.6;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 18px;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .card {
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 24px;
            text-decoration: none;
            color: #333333;
            transition: 0.2s;
        }

        .card:hover {
            border-color: #1976d2;
            background: #f8fbff;
        }

        .icon {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .card h3 {
            font-size: 17px;
            margin-bottom: 9px;
        }

        .card p {
            font-size: 14px;
            color: #777777;
            line-height: 1.5;
        }

        .arrow {
            display: block;
            margin-top: 18px;
            color: #1976d2;
            font-size: 14px;
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 25px 15px;
            margin-top: 50px;
            border-top: 1px solid #eeeeee;
            color: #888888;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px 20px;
            }

            .container {
                margin-top: 30px;
            }

            .welcome h1 {
                font-size: 23px;
            }

            .menu {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <header>
        <div class="logo">
            Puskesmas<span>Ku</span>
        </div>

        <div>Portal Pasien</div>
    </header>

    <main class="container">

        <section class="welcome">
            <h1>Layanan Pasien</h1>
            <p>
                Selamat datang di layanan Puskesmas.
                Silakan pilih layanan yang Anda butuhkan.
            </p>
        </section>

        <h2 class="section-title">Menu Layanan</h2>

        <section class="menu">

            <a href="daftar_antrian.php" class="card">
                <div class="icon">📋</div>
                <h3>Daftar Antrean</h3>
                <p>
                    Daftarkan diri Anda dan dapatkan
                    nomor antrean pemeriksaan.
                </p>
                <span class="arrow">Buka layanan →</span>
            </a>

            <a href="jadwal_dokter.php" class="card">
                <div class="icon">🩺</div>
                <h3>Jadwal Dokter</h3>
                <p>
                    Lihat jadwal praktik dokter
                    sebelum berkunjung ke Puskesmas.
                </p>
                <span class="arrow">Buka layanan →</span>
            </a>

            <a href="kode_obat.php" class="card">
                <div class="icon">💊</div>
                <h3>Pengambilan Obat</h3>
                <p>
                    Periksa kode pengambilan obat
                    yang diberikan setelah pemeriksaan.
                </p>
                <span class="arrow">Buka layanan →</span>
            </a>

            <a href="riwayat_kunjungan.php" class="card">
                <div class="icon">📁</div>
                <h3>Riwayat Kunjungan</h3>
                <p>
                    Lihat riwayat pemeriksaan dan
                    kunjungan Anda sebelumnya.
                </p>
                <span class="arrow">Buka layanan →</span>
            </a>

        </section>

    </main>

    <footer>
        &copy; 2026 PuskesmasKu
        <br><br>
        Layanan Kesehatan untuk Masyarakat
    </footer>

</body>
</html>
```