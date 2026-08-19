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
                        <li class="breadcrumb-item active">Profile</li>
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
                            <div class="text-center">
                                <img class="profile-user-img img-fluid img"
                                    src="<?= (isset($user['foto']) && !empty($user['foto'])) ? base_url('assets/dist/img/' . $user['foto']) : base_url('assets/dist/img/avatar.png') ?>"
                                    alt="User profile picture">
                            </div>
                            <h3 class="profile-username text-center"><?= $user['nama'] ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Profil</h3>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editProfilModal">
                                    Edit Profil
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="<?= base_url('admin/users/simpanProfil') ?>" method="post" enctype="multipart/form-data" id="formProfil">
                                <?= csrf_field() ?>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Foto Profil</label>
                                    <div class="col-sm-10">
                                        <input type="file" name="foto" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">
                                        <?php
                                        if (session()->get('role_id') == 7) {
                                            echo "NOSIS";
                                        } else {
                                            echo "NRP | NIP";
                                        }
                                        ?>
                                    </label>

                                    <div class="col-sm-10">
                                        <input type="text" name="identitas" class="form-control"
                                            value="<?= (session()->get('role_id') == 7) ? ($user['nosis'] ?? '') : ($user['nomor_induk'] ?? '') ?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Nama</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" value="<?= $user['nama'] ?>" readonly>

                                        <input type="hidden" name="nama" value="<?= $user['nama'] ?>">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <div class="modal fade" id="editProfilModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/users/simpanProfil') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profil</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control" value="<?= $user['nama'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label><?= (session()->get('role_id') == 7) ? 'NOSIS' : 'NRP | NIP' ?></label>
                            <input type="text" name="identitas" class="form-control" value="<?= (session()->get('role_id') == 7) ? ($user['nosis'] ?? '') : ($user['nomor_induk'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Foto Baru</label>
                            <input type="file" name="foto" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /.content -->
</div>
<?= $this->endsection(); ?>