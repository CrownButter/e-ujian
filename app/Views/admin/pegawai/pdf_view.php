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
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2>Data Pengguna</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NRP | Username</th>
                <th>Nama</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($pegawai as $p): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $p['username']; ?></td>
                    <td>
                        <!-- Menampilkan Pangkat dan Nama Lengkap -->
                        <?= esc($p['nama_pangkat'] ?? ''); ?>. <?= esc($p['nama'] ?? ''); ?>

                    </td>
                    <td>
                        <!-- Keterangan Jabatan & Satuan -->
                        <small class="text-muted">
                            <?php if (!empty($p['nama_batalyon'])): ?>
                                Danyon - <?= esc($p['nama_batalyon']); ?>
                            <?php elseif (!empty($p['nama_kompi'])): ?>
                                Danki - <?= esc($p['nama_kompi']); ?>
                            <?php elseif (!empty($p['nama_pleton'])): ?>
                                Danton - <?= esc($p['nama_pleton']); ?>
                            <?php else: ?>
                                <?= esc($p['nama_role']); ?>
                            <?php endif; ?>
                        </small>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>