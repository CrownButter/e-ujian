<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?= $page_title ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Informasi Lengkap Kelas Ujian -->
            <div class="card card-primary card-outline mb-3">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-info-circle"></i> Informasi Detail Ujian</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nama Kelas / Ujian:</strong> <?= esc($ujian['nama_kelas']) ?></p>
                            <p><strong>Mata Pelajaran:</strong> <?= esc($ujian['nama_mapel'] ?? '-') ?></p>
                            <p><strong>Penguji (Gadik):</strong> <?= esc(($ujian['pangkat'] ?? '') . ' ' . ($ujian['nama_pegawai'] ?? '-')) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal Ujian:</strong> <?= !empty($ujian['tanggal']) ? date('d-m-Y', strtotime($ujian['tanggal'])) : '-' ?></p>
                            <p><strong>Waktu:</strong>
                                <?= !empty($ujian['jam_mulai']) ? date('H:i', strtotime($ujian['jam_mulai'])) : '00:00' ?>
                                s.d.
                                <?= !empty($ujian['jam_selesai']) ? date('H:i', strtotime($ujian['jam_selesai'])) : '00:00' ?> WIB
                            </p>
                            <p><strong>Deskripsi:</strong> <?= esc($ujian['deskripsi'] ?? '-') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Siswa Peserta -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-users"></i> Daftar Siswa Peserta</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tabelPeserta">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Siswa</th>
                                <th>Nosis</th>
                                <th>Status Pengerjaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($peserta as $p): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= esc($p['nama_siswa']) ?></td>
                                    <td><?= esc($p['nosis'] ?? '-') ?></td>
                                    <td>
                                        <?php if (strtolower($p['status']) == 'selesai'): ?>
                                            <span class="badge badge-success">Selesai</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Belum Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="<?= base_url(service('uri')->getSegment(1) . '/obe/riwayatUjian') ?>" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </section>
</div>

<!-- DataTables JS & jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tabelPeserta').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            }
        });
    });
</script>
<?= $this->endSection() ?>