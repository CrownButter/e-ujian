<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">MANAJEMEN MONITORING</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Daftar Periode</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <?php
                    // Ambil role_id untuk pengecekan hak akses di view ini
                    $roleId = session()->get('role_id');
                    ?>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0" style="color: #fff;"><i class="fas fa-calendar-alt"></i> Daftar Periode Monitoring</h5>

                            <!-- Tombol Tambah Periode disembunyikan untuk Siswa (role 7) -->
                            <?php if ($roleId != 7): ?>
                                <div class="card-tools">
                                    <a href="<?= base_url($prefix . '/monitoringperiode/create') ?>" class="btn btn-sm btn-light text-primary fw-bold"><i class="fas fa-plus"></i> Tambah Periode</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">

                            <?php if (session()->getFlashdata('success')): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <?php if (session()->getFlashdata('error')): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-1"></i> <?= session()->getFlashdata('error') ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">No</th>
                                            <th>Nama Angkatan</th>
                                            <th class="text-center">Minggu Ke</th>
                                            <th class="text-center">Rentang Tanggal</th>
                                            <th class="text-center">Status</th>
                                            <?php if ($roleId != 7): ?>
                                                <th style="width: 300px;" class="text-center">Aksi</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($list_periode)): ?>
                                            <tr>
                                                <td colspan="<?= ($roleId == 7) ? 5 : 6 ?>" class="text-center text-muted py-4">Belum ada data periode monitoring yang diinput.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $no = 1; ?>
                                            <?php foreach ($list_periode as $per): ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++ ?></td>
                                                    <td>
                                                        <strong><?= esc($per['nama_angkatan']) ?></strong> <br>
                                                        <small class="text-muted">T.A. <?= esc($per['tahun_angkatan'] . '/' . $per['tahun_angkatan'] + 1) ?></small>
                                                    </td>
                                                    <td class="text-center fw-bold">Minggu Ke-<?= esc($per['minggu_ke']) ?></td>
                                                    <td class="text-center">
                                                        <span class="badge badge-secondary p-2"><?= date('d M Y', strtotime($per['periode_awal'])) ?></span>
                                                        <span class="mx-1">s/d</span>
                                                        <span class="badge badge-secondary p-2"><?= date('d M Y', strtotime($per['periode_akhir'])) ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($per['status'] == 'Final'): ?>
                                                            <span class="badge badge-success px-3 py-2">Final</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning px-3 py-2 text-dark">Draft</span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Sel Aksi hanya dirender jika BUKAN siswa -->
                                                    <?php if ($roleId != 7): ?>
                                                        <td class="text-center">
                                                            <?php if ($per['status'] !== 'Final'): ?>
                                                                <a href="<?= base_url($prefix . '/monitoringperiode/buat_laporan?periode_id=' . $per['id']) ?>" class="btn btn-sm btn-primary mr-1" title="Isi / Edit Data Laporan">
                                                                    <i class="fas fa-edit"></i> Isi / Edit Laporan
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-secondary mr-1" disabled title="Laporan Telah Dikunci (Final)">
                                                                    <i class="fas fa-lock"></i> Terkunci
                                                                </button>
                                                            <?php endif; ?>

                                                            <a href="<?= base_url($prefix . '/monitoringperiode/lihat_laporan?periode_id=' . $per['id']) ?>" class="btn btn-sm btn-info text-white" title="Lihat Rekapitulasi Laporan">
                                                                <i class="fas fa-eye"></i> Lihat Rekap
                                                            </a>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<?= $this->endsection(); ?>