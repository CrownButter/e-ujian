<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-calendar-alt text-primary mr-2"></i>Jadwal & Waktu Ujian OBE</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active">Jadwal Ujian</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Daftar Jadwal Ujian OBE</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm font-weight-bold" onclick="tambahJadwal()">
                            <i class="fas fa-plus mr-1"></i> Tambah Jadwal Ujian
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="tableJadwal">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th>Mata Pelajaran / Modul</th>
                                    <th>Tingkat Kognitif</th>
                                    <th>Tanggal Ujian</th>
                                    <th>Waktu Ujian</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th style="width: 12%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyJadwal">
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data jadwal...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- MODAL FORM JADWAL UJIAN -->
<div class="modal fade" id="modalJadwal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title font-weight-bold" id="modalJadwalTitle">Tambah Jadwal Ujian OBE</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- PASTIKAN TAG FORM INI ADA -->
            <form id="formJadwal">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="csrf_token" id="csrf_token_field" value="<?= csrf_hash() ?>">
                    <input type="hidden" name="id" id="jadwal_id">

                    <div class="row">
                        <div class="col-md-7 form-group">
                            <label class="font-weight-bold">Mata Pelajaran / Modul <span class="text-danger">*</span></label>
                            <select class="form-control" name="mata_pelajaran_id" id="id_mapel" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php foreach ($mata_pelajaran as $mapel): ?>
                                    <option value="<?= $mapel['id'] ?>"><?= $mapel['nama_mapel'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5 form-group">
                            <label class="font-weight-bold">Tingkat Kognitif (Bloom) <span class="text-danger">*</span></label>
                            <select class="form-control" name="tingkat_kognitif" id="tingkat_kognitif" required>
                                <option value="Semua (C1-C6)">Semua Level (C1 - C6)</option>
                                <option value="LOTS (C1-C3)">LOTS (C1 Pengetahuan - C3 Aplikasi)</option>
                                <option value="HOTS (C4-C6)">HOTS (C4 Analisis - C6 Kreasi)</option>
                                <option value="C1">C1 - Pengetahuan (Remembering)</option>
                                <option value="C2">C2 - Pemahaman (Understanding)</option>
                                <option value="C3">C3 - Penerapan (Applying)</option>
                                <option value="C4">C4 - Analisis (Analyzing)</option>
                                <option value="C5">C5 - Evaluasi (Evaluating)</option>
                                <option value="C6">C6 - Kreasi (Creating)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Tanggal Ujian <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_ujian" id="tanggal_ujian" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="waktu_mulai" id="waktu_mulai" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="waktu_selesai" id="waktu_selesai" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Durasi Ujian (Menit) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="durasi_menit" id="durasi_menit" placeholder="Contoh: 90" min="1" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Status Ujian <span class="text-danger">*</span></label>
                            <select class="form-control" name="status" id="status" required>
                                <option value="Belum Dimulai">Belum Dimulai</option>
                                <option value="Berlangsung">Berlangsung</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm font-weight-bold" onclick="simpanJadwal()">
                        <i class="fas fa-save mr-1"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    const rolePrefix = '<?= $role_prefix; ?>';
    const baseUrl = '<?= site_url(); ?>';

    $(document).ready(function() {
        loadDataJadwal();
    });

    // 1. FUNGSI LOAD DATA
    function loadDataJadwal() {
        $.ajax({
            url: `${baseUrl}${rolePrefix}/obe/jadwal-ujian/get-data`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                let html = '';
                if (res.status && res.data.length > 0) {
                    res.data.forEach((item, index) => {
                        let badgeStatus = 'badge-secondary';
                        if (item.status === 'Berlangsung') badgeStatus = 'badge-success';
                        else if (item.status === 'Selesai') badgeStatus = 'badge-dark';

                        html += `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td><strong>${item.mapel}</strong></td>
                                <td class="text-center"><span class="badge badge-info p-2">${item.tingkat_kognitif ?? 'Semua (C1-C6)'}</span></td>
                                <td class="text-center">${item.tanggal_ujian}</td>
                                <td class="text-center">${item.waktu_mulai} - ${item.waktu_selesai} WIB</td>
                                <td class="text-center">${item.durasi_menit} Menit</td>
                                <td class="text-center"><span class="badge ${badgeStatus} p-2">${item.status}</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info" onclick="editJadwal(${item.id})"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="hapusJadwal(${item.id})"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = `<tr><td colspan="8" class="text-center text-muted">Belum ada data jadwal ujian.</td></tr>`;
                }
                $('#tbodyJadwal').html(html);
            },
            error: function(xhr) {
                console.log(xhr.responseText);
                $('#tbodyJadwal').html(`<tr><td colspan="8" class="text-center text-danger">Gagal memuat data (Error 404/500).</td></tr>`);
            }
        });
    }

    // 2. FUNGSI TAMBAH (Tampilkan Modal)
    function tambahJadwal() {
        $('#formJadwal')[0].reset();
        $('#jadwal_id').val('');
        $('#modalJadwalTitle').text('Tambah Jadwal Ujian OBE');
        $('#modalJadwal').modal('show');
    }

    // 3. FUNGSI SIMPAN DATA
    function simpanJadwal() {
        let mapelId = $('#id_mapel').val();

        // Validasi ketat di sisi client sebelum AJAX dikirim
        if (!mapelId || mapelId === "") {
            Swal.fire('Peringatan', 'Mata Pelajaran wajib dipilih!', 'warning');
            $('#id_mapel').focus();
            return;
        }

        let dataKirim = {
            id: $('#jadwal_id').val(),
            mata_pelajaran_id: mapelId,
            tingkat_kognitif: $('#tingkat_kognitif').val(),
            tanggal_ujian: $('#tanggal_ujian').val(),
            waktu_mulai: $('#waktu_mulai').val(),
            waktu_selesai: $('#waktu_selesai').val(),
            durasi_menit: $('#durasi_menit').val(),
            status: $('#status').val()
        };

        // Ambil token CSRF CodeIgniter secara dinamis
        let csrfName = '<?= csrf_token() ?>';
        let csrfHash = $('#csrf_token_field').val();
        dataKirim[csrfName] = csrfHash;

        Swal.fire({
            title: 'Menyimpan Data...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: `${baseUrl}${rolePrefix}/obe/jadwal-ujian/store`,
            type: 'POST',
            data: dataKirim,
            dataType: 'json',
            success: function(res) {
                // Perbarui token CSRF baru dari server
                if (res.csrf_token) {
                    $('#csrf_token_field').val(res.csrf_token);
                }

                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#modalJadwal').modal('hide');
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res.message
                    });
                }
            },
            error: function(xhr) {
                let errMessage = 'Terjadi kesalahan pada server.';
                try {
                    let res = JSON.parse(xhr.responseText);
                    if (res.message) errMessage = res.message;
                } catch (e) {}

                Swal.fire({
                    icon: 'error',
                    title: 'Error Server',
                    text: errMessage
                });
            }
        });
    }
    // 4. FUNGSI EDIT (Get Detail)
    function editJadwal(id) {
        $.ajax({
            url: `${baseUrl}${rolePrefix}/obe/jadwal-ujian/get/${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    $('#jadwal_id').val(res.data.id);
                    $('#id_mapel').val(res.data.id_mapel); // Diperbarui dari #mapel ke #id_mapel
                    $('#tingkat_kognitif').val(res.data.tingkat_kognitif);
                    $('#tanggal_ujian').val(res.data.tanggal_ujian);
                    $('#waktu_mulai').val(res.data.waktu_mulai);
                    $('#waktu_selesai').val(res.data.waktu_selesai);
                    $('#durasi_menit').val(res.data.durasi_menit);
                    $('#status').val(res.data.status);
                    $('#modalJadwalTitle').text('Edit Jadwal Ujian OBE');
                    $('#modalJadwal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Data detail jadwal tidak ditemukan.'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal mengambil data dari server.'
                });
            }
        });
    }

    // 5. FUNGSI HAPUS
    function hapusJadwal(id) {
        Swal.fire({
            title: 'Apakah Anda Yakin?',
            text: 'Data ini akan dihapus permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${baseUrl}${rolePrefix}/obe/jadwal-ujian/delete/${id}`,
                    type: 'POST',
                    data: {
                        '<?= csrf_token() ?>': $('#csrf_token_field').val()
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.csrf_token) $('#csrf_token_field').val(res.csrf_token);
                        if (res.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                timer: 1500
                            });
                            loadDataJadwal();
                        }
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection(); ?>