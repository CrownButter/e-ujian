<?php
// Header agar browser mendownload file sebagai Excel (.xls)
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Nilai_Mental_Pleton_" . $pleton . "_Minggu_" . $minggu . ".xls");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 9pt;
        }

        th,
        td {
            border: 1px solid #000000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-left {
            text-align: left;
        }

        .header-title {
            font-weight: bold;
            font-size: 10pt;
            text-align: left;
            border: none;
        }
    </style>
</head>

<body>

    <!-- KOP INSTANSI -->
    <table style="border: none; margin-bottom: 5px;">
        <tr>
            <td class="header-title" style="border: none;">LEMBAGA PENDIDIKAN DAN PELATIHAN POLRI</td>
        </tr>
        <tr>
            <td class="header-title" style="border: none;">SEKOLAH POLISI WANITA</td>
        </tr>
    </table>

    <br>
    <h3 style="text-align: center; font-size: 11pt; margin-bottom: 2px;">DAFTAR NILAI MENTAL HASIL PENGAMATAN</h3>
    <p style="text-align: center; font-size: 10pt; margin-top: 0; margin-bottom: 15px;">
        <b>PESERTA DIDIK <?= strtoupper($angkatanAktif['nama_angkatan'] ?? 'DIKTUK BINTARA POLWAN'); ?> <?= isset($angkatanAktif['angkatan']) ? 'ANGKATAN KE-' . $angkatanAktif['angkatan'] : 'ANGKATAN KE-58'; ?> TAHUN ANGGARAN <?= $angkatanAktif['tahun_anggaran'] ?? '2025-2026'; ?></b>
    </p>

    <!-- INFORMASI PLETON & HARI -->
    <table style="border: none; margin-bottom: 10px;">
        <tr>
            <td style="border: none; text-align: left; width: 50%;"><b><?= strtoupper($pleton); ?></b></td>
            <td style="border: none; text-align: right; width: 15%;">HARI</td>
            <td style="border: none; text-align: center; width: 2%;">:</td>
            <td style="border: none; text-align: left; width: 33%;"><b><?= strtoupper($hari_nama); ?></b></td>
        </tr>
        <tr>
            <td style="border: none;"></td>
            <td style="border: none; text-align: right;">TANGGAL</td>
            <td style="border: none; text-align: center;">:</td>
            <td style="border: none; text-align: left;"><?= strtoupper(date('d F Y', strtotime($tanggal_terhitung))); ?></td>
        </tr>
    </table>

    <!-- TABEL UTAMA -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 35px;">NO</th>
                <th rowspan="2" style="width: 180px;">NAMA</th>
                <th rowspan="2" style="width: 85px;">NOSIS</th>
                <th colspan="3">MENTAL SPIRITUAL</th>
                <th colspan="3">MENTAL IDEOLOGI</th>
                <th colspan="4">MENTAL KEJUANGAN</th>
                <th colspan="4">WATAK PRIBADI</th>
                <th colspan="8">MENTAL KEPEMIMPINAN</th>
                <th rowspan="2" style="width: 45px;">JML SKOR</th>
                <th rowspan="2" style="width: 45px;">JML HSL PENGAMATAN</th>
                <th rowspan="2" style="width: 50px;">NILAI KONVERSI</th>
                <th colspan="2">TIND. DILUAR INDIKATOR</th>
                <th rowspan="2" style="width: 55px;">NILAI AKHIR (28-29 ATAU 28+30)</th>
            </tr>
            <tr>
                <!-- Spiritual (3) -->
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <!-- Ideologi (3) -->
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <!-- Kejuangan (4) -->
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <!-- Watak Pribadi (4) -->
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <!-- Kepemimpinan (8) -->
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
                <th>8</th>
                <!-- Tind Diluar Indikator -->
                <th>-</th>
                <th>+</th>
            </tr>
            <!-- BARIS NOMOR KOLOM (1 sampai 31) -->
            <tr style="background-color: #e6e6e6; font-size: 8pt;">
                <?php for ($i = 1; $i <= 31; $i++): ?>
                    <th><?= $i; ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($siswaList)): ?>
                <tr>
                    <td colspan="31">Tidak ada data siswa untuk pleton ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($siswaList as $s):
                    $n = $map_nilai[$s['id']] ?? [];

                    $sp1 = $n['spiritual_1'] ?? 0;
                    $sp2 = $n['spiritual_2'] ?? 0;
                    $sp3 = $n['spiritual_3'] ?? 0;

                    $id1 = $n['ideologi_1'] ?? 0;
                    $id2 = $n['ideologi_2'] ?? 0;
                    $id3 = $n['ideologi_3'] ?? 0;

                    $kj1 = $n['kejuangan_1'] ?? 0;
                    $kj2 = $n['kejuangan_2'] ?? 0;
                    $kj3 = $n['kejuangan_3'] ?? 0;
                    $kj4 = $n['kejuangan_4'] ?? 0;

                    $wt1 = $n['watak_1'] ?? 0;
                    $wt2 = $n['watak_2'] ?? 0;
                    $wt3 = $n['watak_3'] ?? 0;
                    $wt4 = $n['watak_4'] ?? 0;

                    $kp1 = $n['kepemimpinan_1'] ?? 0;
                    $kp2 = $n['kepemimpinan_2'] ?? 0;
                    $kp3 = $n['kepemimpinan_3'] ?? 0;
                    $kp4 = $n['kepemimpinan_4'] ?? 0;
                    $kp5 = $n['kepemimpinan_5'] ?? 0;
                    $kp6 = $n['kepemimpinan_6'] ?? 0;
                    $kp7 = $n['kepemimpinan_7'] ?? 0;
                    $kp8 = $n['kepemimpinan_8'] ?? 0;

                    $tind_neg = $n['tindakan_negatif'] ?? 0;
                    $tind_pos = $n['tindakan_positif'] ?? 0;

                    $jml_skor = $sp1 + $sp2 + $sp3 + $id1 + $id2 + $id3 + $kj1 + $kj2 + $kj3 + $kj4 + $wt1 + $wt2 + $wt3 + $wt4 + $kp1 + $kp2 + $kp3 + $kp4 + $kp5 + $kp6 + $kp7 + $kp8;
                    $jml_pengamatan = 26;
                    $nilai_konversi = $jml_pengamatan > 0 ? ($jml_skor / ($jml_pengamatan * 4)) * 100 : 0;
                    $nilai_akhir = $nilai_konversi - $tind_neg + $tind_pos;

                    // Format nosis dengan nol di depan (misal: 001, 024)
                    $formattedNosis = is_numeric($s['nosis']) ? str_pad($s['nosis'], 3, '0', STR_PAD_LEFT) : $s['nosis'];
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td class="text-left"><?= $s['nama']; ?></td>
                        <td style="mso-number-format:'\@';"><b><?= $formattedNosis; ?></b></td>
                        <!-- Spiritual -->
                        <td><?= $sp1 > 0 ? $sp1 : ''; ?></td>
                        <td><?= $sp2 > 0 ? $sp2 : ''; ?></td>
                        <td><?= $sp3 > 0 ? $sp3 : ''; ?></td>
                        <!-- Ideologi -->
                        <td><?= $id1 > 0 ? $id1 : ''; ?></td>
                        <td><?= $id2 > 0 ? $id2 : ''; ?></td>
                        <td><?= $id3 > 0 ? $id3 : ''; ?></td>
                        <!-- Kejuangan -->
                        <td><?= $kj1 > 0 ? $kj1 : ''; ?></td>
                        <td><?= $kj2 > 0 ? $kj2 : ''; ?></td>
                        <td><?= $kj3 > 0 ? $kj3 : ''; ?></td>
                        <td><?= $kj4 > 0 ? $kj4 : ''; ?></td>
                        <!-- Watak Pribadi -->
                        <td><?= $wt1 > 0 ? $wt1 : ''; ?></td>
                        <td><?= $wt2 > 0 ? $wt2 : ''; ?></td>
                        <td><?= $wt3 > 0 ? $wt3 : ''; ?></td>
                        <td><?= $wt4 > 0 ? $wt4 : ''; ?></td>
                        <!-- Kepemimpinan -->
                        <td><?= $kp1 > 0 ? $kp1 : ''; ?></td>
                        <td><?= $kp2 > 0 ? $kp2 : ''; ?></td>
                        <td><?= $kp3 > 0 ? $kp3 : ''; ?></td>
                        <td><?= $kp4 > 0 ? $kp4 : ''; ?></td>
                        <td><?= $kp5 > 0 ? $kp5 : ''; ?></td>
                        <td><?= $kp6 > 0 ? $kp6 : ''; ?></td>
                        <td><?= $kp7 > 0 ? $kp7 : ''; ?></td>
                        <td><?= $kp8 > 0 ? $kp8 : ''; ?></td>
                        <!-- Rekap -->
                        <td><b><?= $jml_skor > 0 ? $jml_skor : ''; ?></b></td>
                        <td><?= $jml_skor > 0 ? number_format($jml_skor / 26, 1) : ''; ?></td>
                        <td><?= $jml_skor > 0 ? number_format($nilai_konversi, 2) : ''; ?></td>
                        <td><?= $tind_neg > 0 ? $tind_neg : ''; ?></td>
                        <td><?= $tind_pos > 0 ? $tind_pos : ''; ?></td>
                        <td><b><?= $jml_skor > 0 ? number_format($nilai_akhir, 2) : ''; ?></b></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <br><br>

    <!-- TANDA TANGAN PEJABAT (PRESISI SESUAI FORMAT) -->
    <table style="border: none !important; width: 100%; margin-top: 20px;">
        <tr style="border: none !important;">
            <!-- Kolom Kiri: DANKI -->
            <td style="border: none !important; width: 33%; text-align: center; vertical-align: top;">
                <p><b>DANKI</b></p>
                <br><br><br><br>
                <p><b><u><?= $danki['nama'] ?? ($danki['nama_lengkap'] ?? ''); ?></u></b></p>
                <p><?= $danki['nama_pangkat'] ?? ''; ?> NRP <?= $danki['nomor_induk'] ?? ($danki['nrp'] ?? ''); ?></p>
            </td>

            <!-- Kolom Tengah: Kosong (Spacer) -->
            <td style="border: none !important; width: 34%; text-align: center; vertical-align: top;">
                <p>&nbsp;</p>
            </td>

            <!-- Kolom Kanan: DANTON -->
            <td style="border: none !important; width: 33%; text-align: center; vertical-align: top;">
                <p><b>DANTON</b></p>
                <br><br><br><br>
                <p><b><u><?= $danton['nama'] ?? ($danton['nama_lengkap'] ?? ''); ?></u></b></p>
                <p><?= $danton['nama_pangkat'] ?? ''; ?> NRP <?= $danton['nomor_induk'] ?? ($danton['nrp'] ?? ''); ?></p>
            </td>
        </tr>
        <tr style="border: none !important;">
            <!-- Baris Kedua Tengah: DANYON -->
            <td style="border: none !important;" colspan="3">
                <table style="border: none !important; width: 100%; margin-top: 15px;">
                    <tr style="border: none !important;">
                        <td style="border: none !important; text-align: center; vertical-align: top;">
                            <p><b>DANYON</b></p>
                            <br><br><br><br>
                            <p><b><u><?= $danyon['nama'] ?? ($danyon['nama_lengkap'] ?? ''); ?></u></b></p>
                            <p><?= $danyon['nama_pangkat'] ?? ''; ?> NRP <?= $danyon['nomor_induk'] ?? ($danyon['nrp'] ?? ''); ?></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>

</html>