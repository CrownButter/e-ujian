<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?= $page_title ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tabelRiwayat">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Kelas / Ujian</th>
                                <th>Mata Pelajaran</th>
                                <th>Penguji</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($riwayat)): ?>
                                <?php foreach ($riwayat as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= esc($item['nama_kelas']) ?></td>
                                        <td><?= esc($item['nama_mapel'] ?? '-') ?></td>
                                        <!-- Menggabungkan Pangkat dan Nama Pegawai/Gadik -->
                                        <td><?= esc(($item['pangkat'] ?? '') . ' ' . ($item['nama_pegawai'] ?? '-')) ?></td>
                                        <td>
                                            <?php if (strtolower($item['status']) == 'selesai'): ?>
                                                <span class="badge badge-success">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Belum Dikerjakan</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada data ujian.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>