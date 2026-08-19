<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Profil</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Edit Siswa</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center mb-3">
                                <img class="img-fluid img-thumbnail rounded"
                                    style="width: 160px; height: 210px; object-fit: cover; border: 2px solid #007bff;"
                                    src="<?= (isset($siswa['foto']) && !empty($siswa['foto'])) ? base_url('assets/dist/img/users/' . $siswa['foto']) : base_url('assets/dist/img/avatar.png') ?>"
                                    alt="User profile picture">
                            </div>
                            <h3 class="profile-username text-center font-weight-bold"><?= esc($siswa['nama']) ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-primary card-outline">
                        <div class="card-header p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Profil Siswa</h3>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editProfilModal">
                                    Edit Siswa
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="<?= base_url('admin/users/simpanProfil') ?>" method="post" enctype="multipart/form-data" id="formProfil">
                                <?= csrf_field() ?>

                                <!-- Ttd & Data Utama di dalam tabel -->
                                <table class="table table-striped table-valign-middle mb-4">
                                    <tbody>
                                        <tr>
                                            <td style="width: 20%;" class="font-weight-bold text-muted">Nama</td>
                                            <td>
                                                <?= esc($siswa['nama']) ?>
                                                <input type="hidden" name="nama" value="<?= $siswa['nama'] ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold text-muted">
                                                NOSIS
                                            </td>
                                            <td><?= $siswa['nosis']; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <h5 class="text-primary mb-3 mt-4">
                                    <i class="fas fa-users mr-2"></i> Data Kelompok Siswa
                                </h5>

                                <table class="table table-bordered table-striped custom-profile-table">
                                    <thead>
                                        <tr class="bg-light">
                                            <th style="width: 20%;">Tingkat Kelompok</th>
                                            <th style="width: 20%;">Nama Kelompok</th>
                                            <th style="width: 40%;">Nama Pembina / Pengasuh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="font-weight-bold text-muted">Batalyon</td>
                                            <td><?= esc($siswa['nama_batalyon'] ?? '-') ?></td>
                                            <td><?= esc($siswa['danyon_pangkat'] . '. ' . $siswa['danyon_nama'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold text-muted">Kompi</td>
                                            <td><?= esc($siswa['nama_kompi'] ?? '-') ?></td>
                                            <td><?= esc($siswa['danki_nama'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold text-muted">Pleton</td>
                                            <td><?= esc($siswa['nama_pleton'] ?? '-') ?></td>
                                            <td><?= esc($siswa['danton_nama'] ?? '-') ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <?php
    // Tentukan daftar role untuk mengambil prefix
    $roles = [1 => 'admin', 2 => 'operator', 3 => 'pengasuh', 4 => 'danton', 5 => 'danki', 6 => 'danyon', 7 => 'siswa'];
    $roleId = session()->get('role_id');
    $prefix = isset($roles[$roleId]) ? $roles[$roleId] : 'user';
    ?>

    <div class="modal fade" id="editProfilModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- UBAH BAGIAN ACTION FORM INI -->
                <form action="<?= base_url($prefix . '/users/update/' . session()->get('user_id')); ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profil</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>NOSIS</label>
                            <input type="text" name="nosis" class="form-control" value="<?= $siswa['nosis']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control" value="<?= $siswa['nama'] ?? '' ?>" required>
                        </div>


                        <div class="form-group">
                            <label>Foto Baru</label>
                            <input type="file" name="foto" class="form-control">
                            <small class="text-muted">Format: jpg, jpeg, png. Max: 2MB</small>
                        </div>
                        <!-- Masukkan ini di dalam modal-body file view Anda -->
                        <?php if (session()->get('role_id') != 7): ?>
                            <div class="form-group">
                                <label>Tanda Tangan</label>
                                <input type="file" name="ttd" class="form-control">
                                <small class="text-muted">Format: jpg, jpeg, png. Max: 2MB (Khusus Danton/Danki/Danyon)</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endsection(); ?>