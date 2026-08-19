<!DOCTYPE html>
<html>

<head>
    <style>
        h2 {
            line-height: 0.5;
            margin-bottom: 0;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .top {
            text-align: center;
        }

        .top p {
            margin-top: -4px;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="top">
        <h2>Data Nominatif Siswa</h2>
        <p><strong>Angkatan:</strong> <?= esc($nama_angkatan); ?></p>
    </div>
    <div style="margin-bottom: 20px; border-bottom: 1px solid #000; padding-bottom: 10px;">

        <p style="margin: 3px 0;"><strong>Pleton:</strong> <?= esc($nama_pleton); ?></p>
        <p style="margin: 3px 0;"><strong>Tanggal Cetak:</strong> <?= $tanggal; ?></p>
    </div>

    <table border="1" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="width: 30px; padding: 5px;">No</th>
                <th style="padding: 5px;">NOSIS</th>
                <th style="padding: 5px;">NAMA</th>
                <th style="padding: 5px;">Pleton</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($siswa)):
                $no = 1;
                foreach ($siswa as $s): ?>
                    <tr>
                        <td style="text-align: center; padding: 5px;"><?= $no++; ?></td>
                        <td style="padding: 5px;"><?= esc($s['nosis'] ?? '-'); ?></td>
                        <td style="padding: 5px;"><?= esc($s['nama'] ?? '-'); ?></td>
                        <td style="padding: 5px;"><?= esc($s['nama_pleton'] ?? 'Belum di set'); ?></td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 10px;">Data tidak ditemukan</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>