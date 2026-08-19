<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Data Gadik</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active">Data Gadik</li>
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
                        <a href="<?= base_url('admin/users/exportExcel'); ?>" class="btn btn-warning">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="<?= base_url('admin/pegawai/exportPdf'); ?>" id="btnExportPdf" class="btn btn-danger">Export PDF</a>
                        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#pegawaiModal">
                            <i class="fas fa-plus"></i> Gadik
                        </button>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#importpegawaiModal">
                            <i class="fas fa-plus"></i> Import Gadik
                        </button>
                        <a href="<?= base_url('admin/pegawai/downloadTemplate') ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Download Template Excel
                        </a>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Data Gadik</h3>
                        </div>
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">

                                <!-- SEMUA -->
                                <li class="nav-item">
                                    <a class="nav-link active pleton-tab btn btn-sm btn-outline-primary px-1 py-1 mr-1"
                                        href="javascript:void(0)"
                                        data-id="all"
                                        data-nama="All"
                                        onclick="filterPleton('', this)">
                                        All
                                        <span class="badge badge-light ml-1">
                                            <?= $total_all ?? array_sum($counts ?? []) ?>
                                        </span>
                                    </a>
                                </li>

                                <!-- LIST FILTER (DANYON, DANKI, DANTON) -->
                                <?php foreach ($pleton_list as $p): ?>
                                    <li class="nav-item">
                                        <a class="nav-link pleton-tab btn btn-sm btn-outline-primary px-1 py-1 mr-1"
                                            href="javascript:void(0)"
                                            data-id="<?= $p['id'] ?? ''; ?>"
                                            data-nama="<?= esc($p['nama'] ?? $p['nama_role']); ?>"
                                            onclick="filterPleton('<?= esc($p['nama'] ?? $p['nama_role']); ?>', this)">

                                            <?= esc($p['nama'] ?? $p['nama_role']); ?>

                                            <span class="badge badge-light ml-1">
                                                <?= $counts[$p['nama'] ?? $p['nama_role']] ?? 0 ?>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>

                            </ul>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <form action="<?= base_url('admin/pegawai/deleteMassal') ?>" method="post" id="formHapusMassal">
                                <?= csrf_field() ?>
                                <button type="button" id="btnHapusMassal" class="btn btn-danger mb-3" style="display: none;">
                                    Hapus Terpilih
                                </button>

                                <table id="example1" class="table table-bordered table-striped">
                                    <thead class="bg-success">
                                        <tr>
                                            <th style="width: 20px; text-align: center;"><input type="checkbox" id="checkAll"></th>
                                            <th>No</th>
                                            <th style="white-space: nowrap;">NRP | NIP</th>
                                            <th>Nama</th>
                                            <th>Role</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1;
                                        foreach ($pegawai as $p): ?>
                                            <tr>
                                                <td style="width: 20px; text-align: center;">
                                                    <?php if ($p['nama_role'] !== 'Admin'): ?>
                                                        <input type="checkbox" class="check-item" name="id_pegawai[]" value="<?= $p['id'] ?>">
                                                    <?php endif; ?>
                                                </td>
                                                <td style="width: 2px; text-align: center;"><?= $no++; ?></td>
                                                <td><?= $p['nomor_induk']; ?></td>
                                                <td><?= !empty($p['nama_pangkat']) ? $p['nama_pangkat'] . ' - ' : ''; ?>
                                                    <?= $p['nama']; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($p['nama_role'])): ?>
                                                        <?php
                                                        $badgeColor = 'badge-secondary';
                                                        if ($p['nama_role'] === 'Admin') {
                                                            $badgeColor = 'badge-danger';
                                                        } elseif ($p['nama_role'] === 'Pengasuh') {
                                                            $badgeColor = 'badge-primary';
                                                        } elseif ($p['nama_role'] === 'Danki') {
                                                            $badgeColor = 'badge-warning';
                                                        } elseif ($p['nama_role'] === 'Danton') {
                                                            $badgeColor = 'badge-success';
                                                        }
                                                        ?>
                                                        <div>
                                                            <span class="badge <?= $badgeColor; ?>" style="font-size: 13px; padding: 6px 10px;"><?= $p['nama_role']; ?></span>
                                                        </div>

                                                        <!-- Keterangan Satuan / Jabatan Organik -->
                                                        <div style="font-size: 13px; color:rgb(39, 40, 41); margin-top: 4px;">
                                                            <?php if (!empty($p['nama_pleton'])): ?>
                                                                <span class="text-success font-weight-bold">Danton <?= $p['nama_pleton']; ?></span>
                                                            <?php elseif (!empty($p['nama_kompi'])): ?>
                                                                <span class="text-dark font-weight-bold"> <?= $p['nama_kompi']; ?></span>
                                                            <?php elseif (!empty($p['nama_batalyon'])): ?>
                                                                <span class="text-danger font-weight-bold">Danyon <?= $p['nama_batalyon']; ?></span>
                                                            <?php else: ?>
                                                                <span>-</span>
                                                            <?php endif; ?>
                                                        </div>

                                                    <?php else: ?>
                                                        <span class="badge badge-secondary" style="font-size: 13px; padding: 6px 10px;">Belum di set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($p['nama_role'] !== 'Admin'): ?>
                                                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#editpegawaiModal<?= $p['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="#" class="btn btn-danger btn-sm" data-url="<?= base_url('admin/pegawai/deletePegawai/' . $p['id']); ?>">
                                                            <i class=" fas fa-trash"></i>
                                                        </a>
                                                        <button type="button" onclick="confirmReset('<?= base_url('admin/pegawai/resetPassword/' . $p['id']); ?>')" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-key"></i> Reset Password
                                                        </button>
                                                    <?php else: ?>
                                                        <i class="fas fa-lock text-muted" title="Admin tidak dapat dihapus"></i>
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



    <!-- TambahPegawai -->
    <div class="modal fade" id="pegawaiModal" tabindex="-1" role="dialog" aria-labelledby="pegawaiModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/pegawai/tambahPegawai') ?>" method="post">
                    <?= csrf_field() ?>
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
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Nomor Induk (NIP/NRP)</label>
                            <input type="text" name="nomor_induk" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Pangkat</label>
                            <select name="pangkat_id" class="form-control">
                                <?php foreach ($pangkat as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= $p['nama_pangkat'] ?></option>
                                <?php endforeach; ?>
                            </select>
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

    <div class="modal fade" id="importpegawaiModal" tabindex="-1" role="dialog" aria-labelledby="pegawaiModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/pegawai/importPegawai') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Pilih File Excel (.xlsx)</label>
                            <input type="file" name="file_excel" id="file_excel" class="form-control" accept=".xlsx, .xls" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" id="btnUpload" class="btn btn-primary">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EditPegawai -->
    <?php foreach ($pegawai as $p): ?>
        <div class="modal fade" id="editpegawaiModal<?= $p['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="pegawaiModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="<?= base_url('admin/pegawai/updatePegawai/' . $p['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <!-- Kirim ID Pegawai secara tersembunyi untuk proses update -->
                        <input type="hidden" name="id" value="<?= $p['id']; ?>">

                        <div class="modal-body">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" value="<?= $p['username']; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                                <small class="text-muted">Biarkan kosong jika tetap menggunakan password lama.</small>
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" value="<?= $p['nama']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Nomor Induk (NIP/NRP)</label>
                                <input type="text" name="nomor_induk" class="form-control" value="<?= $p['nomor_induk']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Pangkat</label>
                                <select name="pangkat_id" class="form-control">
                                    <?php foreach ($pangkat as $pgk): ?>
                                        <option value="<?= $pgk['id'] ?>" <?= ($p['pangkat_id'] == $pgk['id']) ? 'selected' : '' ?>>
                                            <?= $pgk['nama_pangkat'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Role / Hak Akses</label>
                                <select name="role_id" class="form-control" required>
                                    <option value="">-- Pilih Role --</option>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r['id']; ?>" <?= (isset($p['role_id']) && $p['role_id'] == $r['id']) ? 'selected' : ''; ?>>
                                            <?= $r['nama_role']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
    <?php endforeach; ?>
</div>


<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    // 1. FUNGSI GLOBAL (Diletakkan di luar $(function) agar terbaca oleh onclick HTML)
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
        xhr.open('POST', '<?= base_url('admin/pegawai/importPegawai') ?>', true);

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message || 'Data telah diimpor.',
                            showConfirmButton: true
                        }).then(() => {
                            window.location.href = response.redirect_url || '<?= base_url('admin/pegawai') ?>';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message || 'Terjadi kesalahan sistem.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Respon server tidak valid.'
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error ' + xhr.status,
                    text: 'Terjadi kesalahan pada server.'
                });
            }
        };

        xhr.onerror = function() {
            Swal.fire({
                icon: 'error',
                title: 'Koneksi',
                text: 'Gagal menghubungi server.'
            });
        };

        xhr.send(formData);
    }

    // 2. SCRIPT INISIALISASI (Berjalan saat halaman siap)
    $(function() {
        // Inisialisasi DataTable
        $("#example1").DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
        });
        $('#example2').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true
        });

        // Fungsi Checkbox Hapus Massal
        function toggleDeleteButton() {
            $('#btnHapusMassal').toggle($('.check-item:checked').length > 0);
        }

        $(document).on('change', '.check-item', toggleDeleteButton);
        $('#checkAll').on('change', function() {
            $('.check-item').prop('checked', $(this).is(':checked'));
            toggleDeleteButton();
        });

        // AJAX Hapus Massal
        $('#btnHapusMassal').on('click', function() {
            var btn = $(this);
            Swal.fire({
                title: 'Hapus Data Terpilih?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.prop('disabled', true).text('Menghapus...');
                    $.ajax({
                        url: $('#formHapusMassal').attr('action'),
                        type: 'POST',
                        data: $('#formHapusMassal').serialize(),
                        dataType: 'json',
                        success: function(response) {
                            location.reload();
                        },
                        error: function() {
                            Swal.fire('Error!', 'Gagal menghapus data.', 'error');
                            btn.prop('disabled', false).text('Hapus Terpilih');
                        }
                    });
                }
            });
        });

        // 4. DELETE SWAL
        // 4. DELETE SWAL (Diubah agar hanya mentargetkan tombol/link hapus khusus)
        $(document).on('click', '.btn-delete, a[data-url]', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            Swal.fire({
                title: 'Yakin?',
                text: "Data akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = url;
            });
        });
    });
</script>
<script>
    function toggleFields() {
        let role = document.getElementById('role_id').value;
        document.getElementById('siswaFields').style.display = (role == 7) ? 'block' : 'none';
        document.getElementById('pegawaiFields').style.display = (role != 7) ? 'block' : 'none';
    }

    function toggleFields() {
        let role = document.getElementById('role_id').value;
        // Tampilkan field Nosis jika Siswa (ID 7), tampilkan NIP/NRP jika lainnya
        document.getElementById('siswaFields').style.display = (role == 7) ? 'block' : 'none';
        document.getElementById('pegawaiFields').style.display = (role != 7 && role != "") ? 'block' : 'none';
    }
</script>

<script>
    $(document).ready(function() {
        $('#importForm').on('submit', function(e) {
            e.preventDefault(); // Mencegah reload halaman

            var formData = new FormData(this);

            Swal.fire({
                title: 'Sedang Mengunggah...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    window.location.reload();
                },
                error: function(xhr) {
                    Swal.fire('Gagal!', 'Terjadi kesalahan sistem.', 'error');
                }
            });
        });

        // ==========================================
        // TAMBAHKAN KODE INI DI SINI
        // ==========================================
        const urlParams = new URLSearchParams(window.location.search);
        const filterParam = urlParams.get('filter');

        if (filterParam) {
            const targetTab = $(`.pleton-tab[data-nama="${filterParam}"]`);
            if (targetTab.length > 0) {
                filterPleton(filterParam, targetTab[0]);
            } else if (filterParam.toLowerCase() === 'all') {
                filterPleton('', $('.pleton-tab[data-id="all"]')[0]);
            }
        }
        // ==========================================
    });

    function filterPleton(namaJabatan, element) {
        $('.pleton-tab').removeClass('active');
        $(element).addClass('active');

        var table = $('#example1').DataTable();
        if (namaJabatan === '') {
            table.column(4).search('').draw();
        } else {
            table.column(4).search(namaJabatan, true, false).draw();
        }

        // UPDATE URL TOMBOL EXPORT PDF
        let exportUrl = "<?= base_url('admin/pegawai/exportPdf'); ?>";
        if (namaJabatan !== '') {
            exportUrl += "?jabatan=" + encodeURIComponent(namaJabatan);
        }

        // Pastikan ID '#btnExportPdf' sama persis dengan HTML Anda
        $('#btnExportPdf').attr('href', exportUrl);
    }

    function confirmReset(url) {
        Swal.fire({
            title: 'Reset Password?',
            text: "Password pegawai ini akan dikembalikan menjadi Nomor Induk (NRP/NIP) mereka.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12', // Warna tombol konfirmasi (kuning/orange)
            cancelButtonColor: '#d33', // Warna tombol batal (merah)
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika diklik "Ya", arahkan ke URL reset password
                window.location.href = url;
            }
        });
    }
</script>
<?= $this->endsection(); ?>