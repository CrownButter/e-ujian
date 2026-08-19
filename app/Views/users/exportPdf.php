<!DOCTYPE html>
<html>

<head>
    <style>
        h2 {
            line-height: 0.5;
            margin-bottom: 0
        }

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
    <center>
        <h2>Data Pengguna Aplikasi Binsis</h2>
        <h2 style="margin-bottom: 15px;">Sekolah Polisi Wanita Tahun <?= date('Y'); ?>
        </h2>
    </center>
    <table>
        <thead>
            <tr>
                <th style="width: 5px;">No</th>
                <th>Username</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($users as $user): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= $user['username']; ?></td>
                    <td><?= $user['nama_role']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>