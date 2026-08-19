<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $title; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active"><?= $title; ?></li>
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
                                <img class="profile-user-img img-fluid img-circle"
                                    src="<?= (isset($user['foto']) && !empty($user['foto'])) ? base_url('assets/dist/img/' . $user['foto']) : base_url('assets/dist/img/avatar.png') ?>"
                                    alt="User profile picture">
                            </div>
                            <h3 class="profile-username text-center">
                                <?php
                                // Jika ada data nama_siswa, tampilkan itu. Jika tidak, tampilkan nama_pegawai
                                echo $user['nama_siswa'] ?? $user['nama_pegawai'] ?? 'Nama Tidak Ditemukan';
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title"><?= $title; ?></h3>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editProfilModal">
                                    Edit Data
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
                                        echo ($tipe == 'siswa') ? "NOSIS" : "NRP | NIP";
                                        ?>
                                    </label>

                                    <div class="col-sm-10">
                                        <input type="text" name="identitas" class="form-control"
                                            value="<?= ($tipe == 'siswa') ? ($user['nosis'] ?? '') : ($user['nomor_induk'] ?? '') ?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">Nama</label>
                                    <div class="col-sm-10">
                                        <?php
                                        $namaTampil = $user['nama_siswa'] ?? $user['nama_pegawai'] ?? 'Tidak ada nama';
                                        ?>

                                        <input type="text" class="form-control" value="<?= $namaTampil ?>" readonly>
                                        <input type="hidden" name="nama" value="<?= $namaTampil ?>">
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
                <form action="<?= base_url('admin/users/update/' . $user['id']) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                    <input type="hidden" name="tipe" value="<?= $tipe ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profil</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control"
                                value="<?= $user['nama_siswa'] ?? $user['nama_pegawai'] ?? $user['nama'] ?? '' ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label>
                                <?= ($tipe == 'siswa') ? "NOSIS" : "NRP | NIP" ?>
                            </label>
                            <input type="text" name="identitas" class="form-control"
                                value="<?= ($tipe == 'siswa') ? ($user['nosis'] ?? '') : ($user['nomor_induk'] ?? '') ?>"
                                readonly>
                        </div>

                        <div class="form-group">
                            <label>Foto Profil Baru</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto.</small>
                        </div>

                        <hr>
                        <div class="form-group">
                            <label class="font-weight-bold">Keamanan</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="reset_password" class="custom-control-input" id="resetPassword">
                                <label class="custom-control-label" for="resetPassword">
                                    Reset Password ke:
                                    <code><?= ($tipe == 'siswa') ? 'siswa123' : 'polri123' ?></code>
                                </label>
                            </div>
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

<?= $this->section('script'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // 6. SWEETALERT SUCCESS
        <?php if (session()->getFlashdata('success')): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= session()->getFlashdata('success'); ?>',
                showConfirmButton: false,
                timer: 2000
            });
        <?php endif; ?>

        // 7. SWEETALERT ERROR
        <?php if (session()->getFlashdata('error')): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '<?= session()->getFlashdata('error'); ?>',
                confirmButtonColor: '#3085d6'
            });
        <?php endif; ?>

        // 8. DELETE CONFIRM
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            const url = $(this).attr('href'); // Mengambil link dari href

            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Data ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
</script>
<?= $this->endsection(); ?>