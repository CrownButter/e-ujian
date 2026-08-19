<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Data Users</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active">Data Users</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <a href="<?= base_url('admin/users/exportExcel'); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="<?= base_url('admin/users/exportPdf'); ?>" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#userModal">
                            <i class="fas fa-plus"></i> Tambah Pengguna
                        </button>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Data Pengguna</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <form action="<?= base_url('admin/users/deleteMassal') ?>" method="post" id="formHapusMassal">
                                <?= csrf_field() ?>
                                <button type="submit" id="btnHapusMassal" class="btn btn-danger mb-3" style="display: none;">
                                    Hapus Terpilih
                                </button>

                                <table id="example1" class="table table-bordered table-striped">
                                    <thead class="bg-success">
                                        <tr>
                                            <th><input type="checkbox" id="checkAll"></th>
                                            <th>No</th>
                                            <th>Username</th>
                                            <th>Nama</th>
                                            <th>Role</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1;
                                        foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($user['nama_role'] !== 'Admin'): ?>
                                                        <input type="checkbox" class="check-item" name="id_users[]" value="<?= $user['id'] ?>">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $no++; ?></td>
                                                <td><?= $user['username']; ?></td>
                                                <td><?= (!empty($user['nama_pegawai'])) ? $user['nama_pegawai'] : (!empty($user['nama_siswa']) ? $user['nama_siswa'] : '-') ?></td>
                                                <td><?= $user['nama_role']; ?></td>
                                                <td>
                                                    <a href="#"
                                                        class="btn-edit"
                                                        data-url="<?= base_url('admin/users/edit/' . $user['id']); ?>">
                                                        <i class="fas fa-eye text-info"></i>
                                                    </a>
                                                    <?php if (in_array(session()->get('nama_role'), ['Admin', 'Operator'])): ?>
                                                        <a href="<?= base_url('admin/users/edit/' . $user['id']); ?>"
                                                            class="btn-edit">
                                                            <i class="fas fa-edit text-success"></i>
                                                        </a>
                                                        <a href="#"
                                                            class="btn-delete"
                                                            data-url="<?= base_url('admin/users/delete/' . $user['id']); ?>">
                                                            <i class="fas fa-trash text-danger"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </form>


                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

    <!-- add -->

    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/users/store') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalLabel">Tambah Pengguna Baru</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Role</label>
                            <select name="role_id" id="role_id" class="form-control" onchange="toggleFields()" required>
                                <option value="">-- Pilih Role --</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= $role['nama_role'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>

                        <div id="siswaFields" style="display:none;">
                            <div class="form-group">
                                <label>Nomor Induk Siswa (Nosis)</label>
                                <input type="text" name="nosis" class="form-control">
                            </div>
                        </div>

                        <div id="pegawaiFields" style="display:none;">
                            <div class="form-group">
                                <label>Nomor Induk (NIP/NRP)</label>
                                <input type="text" name="nomor_induk" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- inportUser -->


    <div class="modal fade" id="modalImport" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/users/import') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Import Data User</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Pilih File Excel (.xlsx)</label>
                            <input type="file" name="file_excel" class="form-control" accept=".xlsx, .xls" required>
                        </div>
                        <small class="text-muted">Pastikan format kolom: Username, Password, Role_id</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {

        /*************************************************
         * 1. DATA TABLE INIT
         *************************************************/
        var table = $("#example1").DataTable({
            responsive: true,
            autoWidth: false
        });


        /*************************************************
         * 2. TOGGLE BUTTON HAPUS MASSAL
         *************************************************/
        function toggleBtn() {
            var count = table.$('.check-item:checked').length;
            $('#btnHapusMassal').toggle(count > 0);
        }


        /*************************************************
         * 3. CHECK ALL (MASTER CHECKBOX)
         *************************************************/
        $('#checkAll').on('click', function() {
            var rows = table.rows({
                search: 'applied'
            }).nodes();

            $('input[type="checkbox"]', rows).prop('checked', this.checked);

            toggleBtn();
        });


        /*************************************************
         * 4. CHECK ITEM CHANGE (INDIVIDUAL CHECKBOX)
         *************************************************/
        $('#example1 tbody').on('change', '.check-item', function() {
            toggleBtn();
        });


        /*************************************************
         * 5. ROLE TOGGLE FORM (SISWA / PEGAWAI)
         *************************************************/
        window.toggleFields = function() {
            let role = $('#role_id').val();

            if (role == 7) {
                $('#siswaFields').show();
                $('#pegawaiFields').hide();
            } else if (role != "") {
                $('#siswaFields').hide();
                $('#pegawaiFields').show();
            } else {
                $('#siswaFields, #pegawaiFields').hide();
            }
        };


        /*************************************************
         * 6. SWEETALERT SUCCESS (FLASHDATA)
         *************************************************/
        <?php if (session()->getFlashdata('success')): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= session()->getFlashdata('success'); ?>',
                showConfirmButton: false,
                timer: 2000
            });
        <?php endif; ?>


        /*************************************************
         * 7. SWEETALERT ERROR (FLASHDATA)
         *************************************************/
        <?php if (session()->getFlashdata('error')): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= session()->getFlashdata('error'); ?>',
            });
        <?php endif; ?>


        /*************************************************
         * 8. DELETE CONFIRM (SWEETALERT)
         *************************************************/
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();

            const url = $(this).data('url');

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