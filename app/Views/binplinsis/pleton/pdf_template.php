<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Daftar Nilai Mental Hasil Pengamatan'; ?></title>
    <style>
        /* Pengaturan Kertas Landscape & Margin Tipis */
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .header-text {
            font-weight: bold;
            font-size: 9px;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .title-container {
            text-align: center;
            margin-bottom: 10px;
        }

        .title-container h3 {
            font-size: 11px;
            margin: 0 0 3px 0;
            text-decoration: underline;
        }

        .title-container p {
            font-size: 9px;
            margin: 0;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 9px;
        }

        .meta-table td {
            padding: 1px 0;
        }

        /* Styling Tabel Utama */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.main-table th,
        table.main-table td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 2px 1px;
            overflow: hidden;
        }

        table.main-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-left {
            text-align: left !important;
            padding-left: 4px !important;
        }

        /* Bagian Tanda Tangan */
        .sign-section {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
            font-size: 9px;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .sign-table td {
            width: 33.33%;
            vertical-align: top;
            padding-top: 5px;
        }

        .sign-space {
            height: 50px;
        }
    </style>
</head>

<body>

    <!-- Header Instansi -->
    <div class="header-text">
        LEMBAGA PENDIDIKAN DAN PELATIHAN POLRI<br>
        SEKOLAH POLISI WANITA
    </div>

    <!-- Judul Laporan & Angkatan Dinamis -->
    <div class="title-container">
        <h3><?= esc($title ?? 'DAFTAR NILAI MENTAL HASIL PENGAMATAN'); ?></h3>
        <p><?= esc($subtitle ?? ''); ?></p>
    </div>

    <!-- Informasi Pleton & Tanggal Dinamis -->
    <table class="meta-table">
        <tr>
            <td style="width: 15%;"><strong><?= esc(strtoupper($nama_pleton ?? '-')); ?></strong></td>
            <td style="width: 50%;"></td>
            <td style="width: 10%;">HARI</td>
            <td style="width: 2%;">:</td>
            <td style="width: 23%;"><?= esc(strtoupper($hari ?? '-')); ?></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>TANGGAL</td>
            <td>:</td>
            <td><?= esc(strtoupper($tanggal_format ?? '-')); ?></td>
        </tr>
    </table>

    <!-- Tabel Matriks Nilai -->
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">NO</th>
                <th rowspan="2" style="width: 110px;">NAMA</th>
                <th rowspan="2" style="width: 65px;">NOSIS</th>
                <th colspan="3">MENTAL SPIRITUAL</th>
                <th colspan="3">MENTAL IDEOLOGI</th>
                <th colspan="4">MENTAL KEJUANGAN</th>
                <th colspan="4">WATAK PRIBADI</th>
                <th colspan="8">MENTAL KEPEMIMPINAN</th>
                <th rowspan="2" style="width: 25px;">JML SKOR</th>
                <th rowspan="2" style="width: 25px;">JML HSL PENGAMATAN</th>
                <th rowspan="2" style="width: 30px;">NILAI KONVERSI</th>
                <th colspan="2">TIND DILUAR INDIKATOR</th>
                <th rowspan="2" style="width: 35px;">NILAI AKHIR</th>
            </tr>
            <tr>
                <!-- Spiritual (3) -->
                <th style="width: 14px;">1</th>
                <th style="width: 14px;">2</th>
                <th style="width: 14px;">3</th>
                <!-- Ideologi (3) -->
                <th style="width: 14px;">1</th>
                <th style="width: 14px;">2</th>
                <th style="width: 14px;">3</th>
                <!-- Kejuangan (4) -->
                <th style="width: 14px;">1</th>
                <th style="width: 14px;">2</th>
                <th style="width: 14px;">3</th>
                <th style="width: 14px;">4</th>
                <!-- Watak Pribadi (4) -->
                <th style="width: 14px;">1</th>
                <th style="width: 14px;">2</th>
                <th style="width: 14px;">3</th>
                <th style="width: 14px;">4</th>
                <!-- Kepemimpinan (8) -->
                <th style="width: 14px;">1</th>
                <th style="width: 14px;">2</th>
                <th style="width: 14px;">3</th>
                <th style="width: 14px;">4</th>
                <th style="width: 14px;">5</th>
                <th style="width: 14px;">6</th>
                <th style="width: 14px;">7</th>
                <th style="width: 14px;">8</th>
                <!-- Tind Diluar Indikator (2) -->
                <th style="width: 15px;">-</th>
                <th style="width: 15px;">+</th>
            </tr>
            <tr style="font-size: 7px; background-color: #fafafa;">
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <?php for ($i = 1; $i <= 25; $i++): ?>
                    <th><?= $i; ?></th>
                <?php endfor; ?>
                <th>26</th>
                <th>27</th>
                <th>28</th>
                <th>29</th>
                <th>30</th>
                <th>31</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if (empty($data_list)):
            ?>
                <tr>
                    <td colspan="32" style="text-align: center; padding: 10px;">Data peserta didik tidak ditemukan.</td>
                </tr>
                <?php
            else:
                foreach ($data_list as $row):
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td class="text-left"><?= esc($row['nama'] ?? $row['nama_siswa'] ?? '-'); ?></td>
                        <td><?= esc($row['nosis'] ?? '-'); ?></td>

                        <?php
                        // Pastikan jumlah kolom aspek sesuai total indikator (25 kolom dari 1 sampai 25)
                        $nilai_aspek = $row['nilai'] ?? array_fill(0, 25, '');
                        foreach ($nilai_aspek as $val):
                        ?>
                            <td><?= esc($val); ?></td>
                        <?php endforeach; ?>

                        <td><?= esc($row['jml_skor'] ?? ''); ?></td>
                        <td><?= esc($row['jml_hsl'] ?? ''); ?></td>
                        <td><?= esc($row['konversi'] ?? ''); ?></td>
                        <td><?= esc($row['tind_min'] ?? ''); ?></td>
                        <td><?= esc($row['tind_plus'] ?? ''); ?></td>
                        <td><strong><?= esc($row['akhir'] ?? ''); ?></strong></td>
                    </tr>
            <?php
                endforeach;
            endif;
            ?>
        </tbody>
    </table>

    <!-- Bagian Tanda Tangan Pejabat Dinamis -->
    <div class="sign-section">
        <table class="sign-table">
            <tr>
                <td>
                    DANKI<br>
                    <div class="sign-space"></div>
                    <b><u><?= esc($pejabat['danki']['nama'] ?? '-'); ?></u></b><br>
                    <?= esc($pejabat['danki']['pangkat_nrp'] ?? '-'); ?>
                </td>
                <td>
                    DANYON<br>
                    <div class="sign-space"></div>
                    <b><u><?= esc($pejabat['danyon']['nama'] ?? '-'); ?></u></b><br>
                    <?= esc($pejabat['danyon']['pangkat_nrp'] ?? '-'); ?>
                </td>
                <td>
                    DANTON<br>
                    <div class="sign-space"></div>
                    <b><u><?= esc($pejabat['danton']['nama'] ?? '-'); ?></u></b><br>
                    <?= esc($pejabat['danton']['pangkat_nrp'] ?? '-'); ?>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>