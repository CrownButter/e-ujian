<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Profile</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">User Profile</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="col-6">
            <div class="container-fluid">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Ubah Password</h3>
                    </div>
                    <?php if (session()->getFlashdata('validation')): ?>
                        <div class="alert alert-danger">
                            <?= session()->getFlashdata('validation')->listErrors() ?>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                    <?php endif; ?>
                    <div class="card-body">
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                        <?php endif; ?>
                        <?php
                        $roleId = session()->get('role_id');

                        // Mapping yang sesuai dengan rute yang kita buat sebelumnya
                        $roleMap = [
                            1 => 'admin',
                            2 => 'admin',
                            3 => 'pengasuh',
                            4 => 'danton',
                            5 => 'danki',
                            6 => 'danyon',
                            7 => 'siswa'
                        ];

                        $prefix = $roleMap[$roleId] ?? 'admin';
                        ?>
                        <form action="<?= base_url($prefix . '/users/changePassword') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label>Password Lama</label>
                                <input type="password" name="password_lama" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="password_baru" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Konfirmasi Password</label>
                                <input type="password" name="konfirmasi_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection(); ?>