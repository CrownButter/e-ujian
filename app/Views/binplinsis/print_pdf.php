<!DOCTYPE html>
<html>

<head>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h3>Laporan Nilai Mental Siswa</h3>
    </div>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
        }
    </style>

    <h3><?= $title ?></h3>

    <table border="1">
        <thead>
            <tr>
                <th rowspan="2">NO</th>
                <th rowspan="2">NOSIS</th>
                <th colspan="3">SPIRITUAL</th>
                <th colspan="3">IDEOLOGI</th>
                <th colspan="4">KEJUANGAN</th>
                <th colspan="4">WATAK</th>
                <th colspan="8">KEPEMIMPINAN</th>
                <th rowspan="2">JML SKOR</th>
                <th rowspan="2">JML HSL PENGAMATAN</th>
                <th rowspan="2">NILAI KONVERSI</th>
                <th colspan="2">TIND DILUAR INDIKATOR</th>
                <th rowspan="2">NILAI AKHIR</th>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= 22; $i++): ?><th><?= $i ?></th><?php endfor; ?>
                <th>-</th>
                <th>+</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($siswa as $index => $s):
                $n = $map_nilai[$s['id']] ?? null;
                $skor = [];
                if ($n) {
                    $skor = array_merge(
                        json_decode($n['skor_spiritual'] ?? '[]', true),
                        json_decode($n['skor_ideologi'] ?? '[]', true),
                        json_decode($n['skor_kejuangan'] ?? '[]', true),
                        json_decode($n['skor_watak'] ?? '[]', true),
                        json_decode($n['skor_kepemimpinan'] ?? '[]', true)
                    );
                }
            ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= $s['nosis'] ?></td>
                    <?php for ($i = 0; $i < 22; $i++): ?>
                        <td><?= $skor[$i] ?? 0 ?></td>
                    <?php endfor; ?>
                    <td><?= $n['jml_skor'] ?? 0 ?></td>
                    <td><?= $n['jml_hsl_pengamatan'] ?? 0 ?></td>
                    <td><?= $n['nilai_konversi'] ?? 0 ?></td>
                    <td><?= $n['tind_diluar_minus'] ?? 0 ?></td>
                    <td><?= $n['tind_diluar_plus'] ?? 0 ?></td>
                    <td><?= $n['nilai_akhir'] ?? 0 ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>