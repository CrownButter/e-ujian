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
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active"><?= $title; ?></li>
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
                        <a href="<?= base_url('admin/siswa/exportExcel'); ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="javascript:void(0)"
                            onclick="jalankanExport()"
                            class="btn btn-danger">

                            <i class="fas fa-file-pdf"></i> Export PDF

                        </a>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#userModal">
                            <i class="fas fa-plus"></i> Tambah Siswa
                        </button>
                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalImport">
                            <i class="fas fa-file-excel"></i> Import Siswa
                        </button>
                        <a href="<?= base_url('admin/siswa/downloadTemplate') ?>" class="btn btn-warning">
                            <i class="fas fa-file-excel"></i> Download Template Excel
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-header p-2">

                            <ul class="nav nav-pills">

                                <!-- SEMUA -->
                                <li class="nav-item">
                                    <a class="nav-link active pleton-tab nav-link btn btn-sm btn-outline-primary px-1 py-1 mr-1"
                                        href="javascript:void(0)"
                                        data-id="all"
                                        data-nama="All"
                                        onclick="filterPleton('', this)">

                                        All

                                        <span class="badge badge-light ml-1">
                                            <?= array_sum($counts ?? []) ?>
                                        </span>

                                    </a>
                                </li>


                                <!-- LIST PLETON -->
                                <?php foreach ($pleton_list as $p): ?>

                                    <li class="nav-item">

                                        <a class="nav-link pleton-tab nav-link btn btn-sm btn-outline-primary px-1 py-1 mr-1"
                                            href="javascript:void(0)"
                                            data-id="<?= $p['id']; ?>"
                                            data-nama="<?= esc($p['nama_pleton']); ?>"
                                            onclick="filterPleton('<?= esc($p['nama_pleton']); ?>', this)">

                                            <?= esc($p['nama_pleton']); ?>

                                            <span class="badge badge-light ml-1">
                                                <?= $counts[$p['nama_pleton']] ?? 0 ?>
                                            </span>

                                        </a>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </div>

                        <div class="card-body">
                            <form action="<?= base_url('admin/siswa/deleteBatch') ?>" method="post" id="formHapusMasal">
                                <?= csrf_field() ?>

                                <button type="button" id="btnHapusMasal" class="btn btn-danger" style="display:none;">
                                    <i class="fas fa-trash"></i> Hapus Terpilih
                                </button>

                                <table id="tabelSiswa" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th width="20"><input type="checkbox" id="selectAll"></th>
                                            <th width="20">No</th>
                                            <th>Nosis</th>
                                            <th>Nama</th>
                                            <th>Pleton</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1;
                                        foreach ($siswa as $sw): ?>
                                            <tr>
                                                <td><input type="checkbox" name="ids[]" class="checkItem" value="<?= $sw['id'] ?>"></td>
                                                <td><?= $no++; ?></td>
                                                <td><?= esc($sw['nosis']); ?></td>
                                                <td><?= esc($sw['nama']); ?></td>

                                                <td><?= ($sw['nama_pleton']) ? esc($sw['nama_pleton']) : '<span class="badge badge-warning">Belum di set</span>'; ?>
                                                </td>
                                                <td>
                                                    <a href="#"
                                                        class="btn-edit"
                                                        data-url="<?= base_url('admin/users/edit/' . $sw['id']); ?>">
                                                        <i class="fas fa-eye text-info"></i>
                                                    </a>
                                                    <?php if (in_array(session()->get('nama_role'), ['Admin', 'Operator'])): ?>
                                                        <a href="<?= base_url('admin/siswa/edit/' . $sw['id']); ?>"
                                                            class="btn-edit">
                                                            <i class="fas fa-edit text-success"></i>
                                                        </a>
                                                        <a href="#"
                                                            class="btn-delete"
                                                            data-url="<?= base_url('admin/siswa/deleteSiswa/' . $sw['id']); ?>">
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
                    </div>
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
                <form action="<?= base_url('admin/siswa/importSiswa') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Import Data User</h5>
                    </div>
                    <div class="modal-body">

                        <?php if (session()->getFlashdata('skipped_data')): ?>
                            <div class="alert alert-warning">
                                <strong>Perhatian!</strong> Beberapa siswa sudah ada di database dan dilewati:
                                <ul style="max-height: 150px; overflow-y: auto; margin-top:10px;">
                                    <?php foreach (session()->getFlashdata('skipped_data') as $s): ?>
                                        <li><?= $s['nosis'] ?> - <?= $s['nama'] ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Pilih File Excel (.xlsx)</label>
                            <input type="file" name="file_excel" id="file_excel" class="form-control" accept=".xlsx, .xls" required>
                        </div>

                        <div class="progress" style="display:none; height: 20px; margin-top: 10px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%;">0%</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="button" id="btnUpload" class="btn btn-primary" onclick="uploadExcel()">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('skipped_data')): ?>
        <script>
            $(document).ready(function() {
                $('#modalImport').modal('show');
            });
        </script>
    <?php endif; ?>
</div>


<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    let pletonTerpilih = 'all';

    $(document).ready(function() {
        const table = $('#tabelSiswa').DataTable({
            responsive: true,
            lengthChange: false,
            autoWidth: false,
            pageLength: 50
        });

        // 1. FUNGSI SELECT ALL CHECKBOX (Mendukung pencarian/filter aktif DataTables)
        $('#selectAll').on('click', function() {
            var rows = table.rows({
                'search': 'applied'
            }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
            toggleHapusButton();
        });

        // 2. DETEKSI KETIKA CHECKBOX ITEM DI-KLIK
        $('#tabelSiswa').on('change', '.checkItem', function() {
            toggleHapusButton();

            // Jika ada satu saja yang tidak dicentang, matikan centang pada "selectAll"
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            }
        });

        // 3. FUNGSI UNTUK MENAMPILKAN/MENYEMBUNYIKAN TOMBOL HAPUS MASSAL
        function toggleHapusButton() {
            var checkedCount = $('.checkItem:checked').length;
            if (checkedCount > 0) {
                $('#btnHapusMasal').fadeIn();
            } else {
                $('#btnHapusMasal').fadeOut();
            }
        }

        // 4. EVENT KETIKA TOMBOL HAPUS MASAL DIKLIK (DENGAN SWEETALERT2)
        $('#btnHapusMasal').on('click', function(e) {
            e.preventDefault();

            var checkedCount = $('.checkItem:checked').length;
            if (checkedCount === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Pilih minimal satu data siswa yang ingin dihapus.'
                });
                return;
            }

            // Dialog Konfirmasi SweetAlert2
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Anda akan menghapus " + checkedCount + " data siswa yang dipilih secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form secara otomatis jika dikonfirmasi
                    $('#formHapusMasal').submit();
                }
            });
        });

        window.filterPleton = function(pletonName, el = null) {
            $('.pleton-tab').removeClass('active');
            if (el) {
                $(el).addClass('active');
                pletonTerpilih = $(el).data('id');
            }

            if (!pletonName) {
                table.column(4).search('').draw();
                pletonTerpilih = 'all';
            } else {
                table.column(4).search('^' + pletonName + '$', true, false).draw();
            }

            // Reset checkbox "selectAll" saat ganti tab filter
            $('#selectAll').prop('checked', false);
            toggleHapusButton();
        };
    });

    // 5. EKSPOR PDF
    function jalankanExport() {
        let angkatanId = "<?= $current_angkatan ?? '' ?>";

        if (!angkatanId) {
            angkatanId = "<?= $angkatanAktif['id'] ?? '' ?>";
        }

        if (!angkatanId) {
            const urlParams = new URLSearchParams(window.location.search);
            angkatanId = urlParams.get('angkatan_id');
        }

        if (!angkatanId) {
            console.error("Gagal menjalankan export: ID Angkatan tidak ditemukan.");
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Sistem tidak menemukan ID Angkatan yang aktif.'
            });
            return;
        }

        let pletonId = (typeof pletonTerpilih !== 'undefined') ? pletonTerpilih : 'all';
        let baseUrl = "<?= base_url('admin/siswa/export_pdf'); ?>";
        let finalUrl = `${baseUrl}?angkatan_id=${angkatanId}&pleton_id=${pletonId}`;

        window.location.href = finalUrl;
    }

    // 6. UPLOAD EXCEL
    function uploadExcel() {
        var fileInput = document.getElementById('file_excel');
        var file = fileInput.files[0];

        if (!file) {
            Swal.fire({
                icon: 'warning',
                title: 'Oops...',
                text: 'Pilih file terlebih dahulu!'
            });
            return;
        }

        Swal.fire({
            title: 'Sedang Mengunggah...',
            text: 'Mohon tunggu, sistem sedang memproses data.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        var formData = new FormData();
        formData.append('file_excel', file);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?= base_url('admin/siswa/importSiswa') ?>', true);

        xhr.onload = function() {
            if (xhr.status === 200) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data siswa telah diimpor.',
                    showConfirmButton: true
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan saat mengunggah file.'
                });
            }
        };

        xhr.onerror = function() {
            Swal.fire({
                icon: 'error',
                title: 'Koneksi Terputus',
                text: 'Gagal menghubungi server.'
            });
        };

        xhr.send(formData);
    }
</script>

<script>
    $(window).on('load', function() {
        // Cek apakah ada flashdata 'success' atau 'pesan' dari controller setelah hapus data
        <?php if (session()->getFlashdata('success') || session()->getFlashdata('message') || session()->getFlashdata('pesan')): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= session()->getFlashdata('success') ?? session()->getFlashdata('message') ?? session()->getFlashdata('pesan') ?>',
                timer: 2000,
                showConfirmButton: false
            });
        <?php endif; ?>

        <?php if (session()->getFlashdata('skipped_data')): ?>
            console.log("Membuka modal otomatis...");
            $('#modalImport').modal('show');
        <?php endif; ?>
    });
</script>
<?= $this->endsection(); ?>