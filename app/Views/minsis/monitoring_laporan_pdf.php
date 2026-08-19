<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Monitoring Siswa Diktuk Bintara Polwan</title>
    <style>
        @page {
            margin: 1.2cm 1cm 1cm 1cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            line-height: 1.3;
            color: #000000;
        }

        /* ==================================================================
           PERBAIKAN LAYOUT KOP: Menggantikan Float dengan Tabel Penolong 
           ================================================================== */
        .table-kop-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .table-kop-container td {
            border: none !important;
            padding: 0 !important;
        }

        /* Mengunci lebar box KOP di sisi kiri (45% lebar halaman) */
        .kop-box {
            width: 40%;
            text-align: center;
            /* Membuat teks di dalamnya menjadi rata tengah */
        }

        .kop-lembaga-text {
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.3;
            text-transform: uppercase;
            margin-left: -5px;
        }

        /* Garis bawah KOP presisi mengikuti lebar box teks */
        .kop-border-line {
            border-bottom: 1.5px solid #000000;
            margin-top: 4px;
            width: 90%;
        }

        /* ==================================================================
           Blok Judul Dokumen Utama Tengah 
           ================================================================== */
        .judul-blok {
            text-align: center;
            margin-bottom: 5px;
            width: 100%;
        }

        .judul-utama {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.4;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .judul-line {
            width: 50%;
            margin: 5px auto 15px auto;
            border-bottom: 1.5px solid #000000;
        }

        /* ==================================================================
           PERBAIKAN LAYOUT META INFO: Sejajar Sempurna Kiri (Ton) & Kanan (Periode)
           ================================================================== */
        .table-meta-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            margin-top: 10px;
        }

        .table-meta-row td {
            border: none !important;
            padding: 0 !important;
            vertical-align: bottom;
        }

        .meta-pleton-left {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
        }

        .meta-periode-right {
            text-align: right;
        }

        .table-periode {
            margin-left: auto;
            /* Mendorong tabel periode mentok ke kanan */
            border-collapse: collapse;
        }

        .table-periode td {
            border: none !important;
            padding: 1px 3px !important;
            font-size: 9.5pt !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
            vertical-align: top !important;
            text-align: left !important;
        }

        /* ==================================================================
           Struktur Tabel Utama Sesuai Blangko 
           ================================================================== */
        table.laporan-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.laporan-table th,
        table.laporan-table td {
            border: 1px solid #000000;
            padding: 5px 6px;
            vertical-align: top;
            font-size: 9pt;
            word-wrap: break-word;
        }

        table.laporan-table th {
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }

        /* Baris Angka Petunjuk (1, 2, 3...) */
        .row-number td {
            text-align: center;
            font-size: 8.5pt !important;
            padding: 2px !important;
        }

        .text-center {
            text-align: center;
        }

        .font-weight-bold {
            font-weight: bold;
        }

        /* Jeda Halaman Per Pleton */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    <?php if (!empty($laporan_data)): ?>
        <?php
        $pletonKeys = array_keys($laporan_data);
        $lastPletonKey = end($pletonKeys);

        // Counter Alfabet untuk Bidang Utama (A, B, C, dst)
        $alphabet = range('A', 'Z');
        ?>

        <?php foreach ($laporan_data as $pletonName => $bidangGroup): ?>
            <?php $bidangCounter = 0; ?>

            <table class="table-kop-container">
                <tr>
                    <td>
                        <div class="kop-box">
                            <div class="kop-lembaga-text">
                                LEMBAGA PENDIDIKAN DAN PELATIHAN POLRI<br>
                                SEKOLAH POLISI WANITA
                            </div>
                            <div class="kop-border-line"></div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="judul-blok">
                <div class="judul-utama">
                    LAPORAN MONITORING<br>
                    SISWA DIKTUK BINTARA POLWAN ANGKATAN KE-<?= esc($periode['nama_angkatan'] ?? '-') ?><br>
                    TAHUN ANGGARAN <?= esc($periode['tahun_angkatan'] ?? '-') ?>/<?= isset($periode['tahun_angkatan']) ? (intval($periode['tahun_angkatan']) + 1) : '-' ?>
                </div>
                <div class="judul-line"></div>
            </div>

            <table class="table-meta-row">
                <tr>
                    <td class="meta-pleton-left">
                        TON : &nbsp; <?= esc($pletonName) ?>
                    </td>
                    <td class="meta-periode-right">
                        <table class="table-periode">
                            <tr>
                                <td>MINGGU</td>
                                <td>:</td>
                                <td><?= esc($periode['minggu_ke'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td>PERIODE</td>
                                <td>:</td>
                                <td>
                                    <?= isset($periode['periode_awal']) ? date('d M Y', strtotime($periode['periode_awal'])) : '-' ?>
                                    S/D
                                    <?= isset($periode['periode_akhir']) ? date('d M Y', strtotime($periode['periode_akhir'])) : '-' ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="laporan-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">NO</th>
                        <th style="width: 20%;">BIDANG</th>
                        <th style="width: 25%;">INDIKATOR PENILAIAN</th>
                        <th style="width: 16%;">GIAT SERDIK</th>
                        <th style="width: 17%;">HASIL YANG DICAPAI</th>
                        <th style="width: 17%;">GIAT PENGASUH</th>
                    </tr>
                    <tr class="row-number">
                        <td>1</td>
                        <td>2</td>
                        <td>3</td>
                        <td>4</td>
                        <td>5</td>
                        <td>6</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bidangGroup as $bidangName => $subRows): ?>
                        <?php
                        $totalRowspan = count($subRows);
                        $isRowspanTampil = true;

                        // Mengambil huruf pembuka bidang utama saat ini (A, B, C...)
                        $currentAlphabet = $alphabet[$bidangCounter] ?? '';
                        $bidangCounter++;

                        // Counter angka untuk sub-bidang internal
                        $subBidangCounter = 1;
                        ?>

                        <?php foreach ($subRows as $row): ?>
                            <?php if (!empty($row['id'])): ?>
                                <tr>
                                    <?php if ($isRowspanTampil): ?>
                                        <td rowspan="<?= $totalRowspan ?>" class="text-center" style="vertical-align: top; font-weight: bold;">
                                            <?= $currentAlphabet ?>
                                        </td>
                                        <td rowspan="<?= $totalRowspan ?>" class="font-weight-bold" style="text-transform: uppercase; vertical-align: top;">
                                            <?= esc($bidangName) ?>
                                        </td>
                                        <?php $isRowspanTampil = false; ?>
                                    <?php endif; ?>

                                    <td>
                                        <div class="font-weight-bold" style="margin-bottom: 4px;">
                                            <?= $subBidangCounter ?>. <?= esc($row['sub_bidang']) ?>
                                        </div>
                                        <div><?= nl2br(esc($row['indikator'])) ?></div>
                                    </td>

                                    <td><?= nl2br(esc($row['giat_serdik'])) ?></td>
                                    <td><?= nl2br(esc($row['hasil_dicapai'])) ?></td>
                                    <td><?= nl2br(esc($row['giat_pengasuh'])) ?></td>
                                </tr>
                                <?php $subBidangCounter++; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="color: #444; font-style: italic; padding: 20px;">
                                        Belum ada data rekapan instrumen monitoring yang dimasukkan untuk <?= esc($pletonName) ?>.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($pletonName !== $lastPletonKey): ?>
                <div class="page-break"></div>
            <?php endif; ?>

        <?php endforeach; ?>
    <?php endif; ?>

</body>

</html>