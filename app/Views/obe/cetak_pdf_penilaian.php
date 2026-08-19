<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Daftar Nilai Ujian</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            margin: 20px;
        }

        .text-center {
            text-align: center;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table.data,
        table.data th,
        table.data td {
            border: 1px solid #000;
        }

        table.data th,
        table.data td {
            padding: 6px;
        }

        table.data th {
            background-color: #d9d9d9;
            text-align: center;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 15px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
            Cetak / Simpan PDF
        </button>
    </div>

    <!-- Judul -->
    <h2 class="text-center" style="margin-bottom: 5px;">REKAPITULASI NILAI UJIAN OBE</h2>
    <p class="text-center" style="margin-top: 0; margin-bottom: 20px;">
        <b>SISWA DIKTUK BINTARA POLWAN <?= esc($angkatan['nama_angkatan'] ?? '-'); ?> TAHUN ANGKATAN <?= esc($angkatan['tahun_angkatan'] ?? '-'); ?>/<?= (isset($angkatan['tahun_angkatan']) && is_numeric($angkatan['tahun_angkatan'])) ? $angkatan['tahun_angkatan'] + 1 : '-'; ?></b>
    </p>

    <?php
    // Format Hari, Tanggal, dan Waktu Bahasa Indonesia (opsional jika dikirim dari controller atau diolah di view)
    $namaBulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
    $hariIndo = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];

    $hariUjian = '-';
    $tanggalUjian = '-';
    if (!empty($ujian['tanggal'])) {
        $tglKey = date('d', strtotime($ujian['tanggal']));
        $blnKey = date('m', strtotime($ujian['tanggal']));
        $thnKey = date('Y', strtotime($ujian['tanggal']));
        $hariKey = date('D', strtotime($ujian['tanggal']));
        $hariUjian = $hariIndo[$hariKey] ?? '-';
        $tanggalUjian = "{$tglKey} " . ($namaBulan[$blnKey] ?? '') . " {$thnKey}";
    }

    $jamMulai = !empty($ujian['jam_mulai']) ? date('H:i', strtotime($ujian['jam_mulai'])) : '-';
    $jamSelesai = !empty($ujian['jam_selesai']) ? date('H:i', strtotime($ujian['jam_selesai'])) : '-';
    $waktuUjian = "Pukul {$jamMulai} - {$jamSelesai} WIB";
    $gadikPenguji = (!empty($ujian['nama_pangkat']) ? $ujian['nama_pangkat'] . ' ' : '') . ($ujian['nama_gadik'] ?? '-');
    $jumlahPeserta = count($peserta ?? []);
    ?>

    <!-- Informasi Detail (2 Kolom) -->
    <table style="width:100%; border:none; border-collapse:collapse; margin-bottom: 20px;">
        <tr style="border:none;">
            <!-- Kolom Kiri -->
            <td style="width: 45%; vertical-align: top; border:none; padding: 2px 0;">
                <b>Kelas Ujian</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= esc($ujian['nama_kelas'] ?? '-'); ?><br>
                <b>Mata Pelajaran</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= esc($ujian['nama_mapel'] ?? '-'); ?><br>
                <b>Jumlah Peserta</b> &nbsp;&nbsp;&nbsp;: <?= $jumlahPeserta; ?> Siswa
            </td>
            <!-- Kolom Kanan -->
            <td style="width: 55%; vertical-align: top; border:none; padding: 2px 0;">
                <b>Hari</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= $hariUjian; ?><br>
                <b>Tanggal</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= $tanggalUjian; ?><br>
                <b>Waktu</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= $waktuUjian; ?><br>
                <b>Gadik Penguji</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= esc($gadikPenguji); ?>
            </td>
        </tr>
    </table>

    <!-- Tabel Data Siswa -->
    <table class="data">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Nosis</th>
                <th style="width: 45%;">Nama Siswa</th>
                <th style="width: 15%;">Nilai Akhir</th>
                <th style="width: 20%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($peserta as $p): ?>
                <tr>
                    <td style="text-align: center;"><?= $no++; ?></td>
                    <td style="text-align: center;"><?= esc($p['nosis']); ?></td>
                    <td><?= esc($p['nama_siswa']); ?></td>
                    <td style="text-align: center;"><?= !empty($p['nilai_akhir']) ? number_format($p['nilai_akhir'], 2) : '-'; ?></td>
                    <td style="text-align: center;"><?= !empty($p['nilai_akhir']) ? 'Sudah Dinilai' : 'Belum Dinilai'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>