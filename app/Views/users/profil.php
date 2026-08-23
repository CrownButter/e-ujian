<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>
<!-- <div class="card">
    <div class="card-header">Profil Pengguna</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 text-center">
                <img src="<?= base_url('assets/img/user-default.png') ?>" class="img-circle" width="150">
            </div>
            <div class="col-md-8">
                <table class="table table-borderless">
                    <tr>
                        <th>Nama Lengkap</th>
                        <td>: <?= $user['nama'] ?></td>
                    </tr>
                    <tr>
                        <th>Nomor Induk</th>
                        <td>: <?= isset($user['nosis']) ? $user['nosis'] : $user['nomor_induk'] ?></td>
                    </tr>
                    <tr>
                        <th>Username</th>
                        <td>: <?= session()->get('username') ?></td>
                    </tr>
                </table>
                <a href="<?= base_url('admin/users/ubah-password') ?>" class="btn btn-primary">Ubah Password</a>
            </div>
        </div>
    </div>
</div> -->

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
                        <li class="breadcrumb-item active">Profil</li>
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
                                <!-- Mengatur rasio pasfoto formal (Contoh: Lebar 160px, Tinggi 210px) -->
                                <img class="img-fluid img-thumbnail rounded"
                                    style="width: 160px; height: 210px; object-fit: cover; border: 2px solid #007bff;"
                                    src="<?= (isset($user['foto']) && !empty($user['foto'])) ? base_url('assets/dist/img/users/' . $user['foto']) : base_url('assets/dist/img/avatar.png') ?>"
                                    alt="User profile picture">
                            </div>
                            <h3 class="profile-username text-center font-weight-bold"><?= esc($user['nama']) ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card card-primary card-outline">
                        <div class="card-header p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">
                                    <?php
                                    if (session()->get('role_id') == 7) {
                                        echo "Profil Siswa";
                                    } else {

                                        $tipe = isset($user['tipe_pegawai']) ? strtoupper($user['tipe_pegawai']) : 'PEGAWAI';
                                        echo "Profil " . $tipe;
                                    }
                                    ?>
                                </h3>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editProfilModal">
                                    Edit Profil
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="<?= base_url('admin/users/simpanProfil') ?>" method="post" enctype="multipart/form-data" id="formProfil">
                                <?= csrf_field() ?>

                                <!-- Ttd & Data Utama di dalam tabel -->
                                <table class="table table-borderless mb-4">
                                    <tbody class="bg-light">
                                        <tr>
                                            <td style="width: 20%;" class="font-weight-bold text-muted">Nama</td>
                                            <td>:</td>
                                            <td>
                                                <?= esc($user['nama']) ?>
                                                <input type="hidden" name="nama" value="<?= $user['nama'] ?>">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold text-muted">
                                                <?= (session()->get('role_id') == 7) ? "NOSIS" : "NRP | NIP" ?>
                                            </td>
                                            <td>:</td>
                                            <td>
                                                <?= esc((session()->get('role_id') == 7) ? ($user['nosis'] ?? '-') : ($user['nomor_induk'] ?? '-')) ?>
                                                <input type="hidden" name="identitas" value="<?= (session()->get('role_id') == 7) ? ($user['nosis'] ?? '') : ($user['nomor_induk'] ?? '') ?>">
                                            </td>
                                        </tr>
                                        <!-- JIKA PEGAWAI: Tampilkan baris TTD Digital -->
                                        <?php if (session()->get('role_id') != 7): ?>
                                            <tr>
                                                <td class="font-weight-bold text-muted">Tanda Tangan Digital</td>
                                                <td>
                                                    <?php if (!empty($user['ttd'])): ?>
                                                        <img src="<?= base_url('assets/dist/img/ttd/' . $user['ttd']) ?>" alt="TTD Pegawai" style="max-height: 70px; border: 1px dashed #ccc; padding: 4px;">
                                                    <?php else: ?>
                                                        <span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> TTD Belum Diunggah</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <!-- KONDISI 1: JIKA USER ADALAH SISWA -->
                                <div class="card">
                                    <?php if (session()->get('role_id') == 7): ?>
                                        <div class="card-header bg-info">
                                            <h3 class="card-title"> <i class="fas fa-users mr-2"></i> Data Kelompok Siswa</h3>
                                        </div>

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
                                                    <td><?= esc($user['nama_batalyon'] ?? '-') ?></td>
                                                    <td><?= (!empty($user['danyon_pangkat']) && !empty($user['danyon_nama'])) ? esc($user['danyon_pangkat'] . '. ' . $user['danyon_nama']) : '-' ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold text-muted">Kompi</td>
                                                    <td><?= esc($user['nama_kompi'] ?? '-') ?></td>
                                                    <td><?= (!empty($user['danki_pangkat']) && !empty($user['danki_nama'])) ? esc($user['danki_pangkat'] . '. ' . $user['danki_nama']) : '-' ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold text-muted">Pleton</td>
                                                    <td><?= esc($user['nama_pleton'] ?? '-') ?></td>
                                                    <td><?= (!empty($user['danton_pangkat']) && !empty($user['danton_nama'])) ? esc($user['danton_pangkat'] . '. ' . $user['danton_nama']) : '-' ?></td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!-- KONDISI 2: JIKA USER ADALAH PEGAWAI (Danton/Danki/Danyon/Pengasuh) -->
                                    <?php else: ?>
                                        <h5 class="text-success mb-3 mt-4">
                                            <i class="fas fa-briefcase mr-2"></i> Data Kelompok & Penugasan Struktural
                                        </h5>

                                        <table class="table table-bordered table-striped custom-profile-table">
                                            <thead>
                                                <tr class="bg-light">
                                                    <th style="width: 30%;">Struktur / Unit Kerja</th>
                                                    <th style="width: 40%;">Nama Kelompok Binaan</th>
                                                    <th style="width: 30%;">Jabatan / Sebagai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="font-weight-bold text-muted">Batalyon Binaan</td>
                                                    <td><?= esc($user['nama_batalyon'] ?? '-') ?></td>
                                                    <td>
                                                        <?php if (session()->get('role_id') == 6): ?>
                                                            <span class="badge badge-danger p-2"><i class="fas fa-star"></i> DANYON</span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Batalyon Penugasan</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold text-muted">Kompi Binaan</td>
                                                    <td><?= esc($user['nama_kompi'] ?? '-') ?></td>
                                                    <td>
                                                        <?php if (session()->get('role_id') == 5): ?>
                                                            <span class="badge badge-info p-2"><i class="fas fa-shield-alt"></i> DANKI</span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold text-muted">Pleton Binaan</td>
                                                    <td><?= esc($user['nama_pleton'] ?? '-') ?></td>
                                                    <td>
                                                        <?php if (session()->get('role_id') == 4): ?>
                                                            <span class="badge badge-success p-2"><i class="fas fa-user-shield"></i> DANTON</span>
                                                        <?php elseif (session()->get('role_id') == 3): ?>
                                                            <span class="badge badge-warning p-2"><i class="fas fa-user-check"></i> PENGASUH</span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
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
                            <label><?= ($roleId == 7) ? 'NOSIS' : 'NRP | NIP' ?></label>
                            <input type="text" name="identitas" class="form-control" value="<?= ($roleId == 7) ? ($user['nosis'] ?? '') : ($user['nomor_induk'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control" value="<?= $user['nama'] ?? '' ?>" required>
                        </div>
                        <?php if (session()->get('role_id') != 7): ?>
                            <div class="form-group">
                                <label>Pangkat</label>
                                <select name="pangkat_id" class="form-control" required>
                                    <option value="">-- Pilih Pangkat --</option>
                                    <?php foreach ($list_pangkat as $pangkat): ?>
                                        <option value="<?= $pangkat['id']; ?>" <?= (isset($user['pangkat_id']) && $user['pangkat_id'] == $pangkat['id']) ? 'selected' : ''; ?>>
                                            <?= esc($pangkat['nama_pangkat']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

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