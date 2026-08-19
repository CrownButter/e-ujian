<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<!-- CDN SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Custom Styling untuk Warna Dinamis & Efek Tab Active */
    .nav-tabs .nav-link {
        color: #495057;
        border-radius: 6px 6px 0 0;
        transition: all 0.2s ease-in-out;
    }

    /* C1 - Primary (Biru) */
    .tab-color-c1.active {
        background-color: #007bff !important;
        color: #fff !important;
    }

    /* C2 - Info (Cyan) */
    .tab-color-c2.active {
        background-color: #17a2b8 !important;
        color: #fff !important;
    }

    /* C3 - Success (Hijau) */
    .tab-color-c3.active {
        background-color: #28a745 !important;
        color: #fff !important;
    }

    /* C4 - Warning (Oranye/Kuning) */
    .tab-color-c4.active {
        background-color: #ffc107 !important;
        color: #1f2d3d !important;
    }

    /* C5 - Danger (Merah) */
    .tab-color-c5.active {
        background-color: #dc3545 !important;
        color: #fff !important;
    }

    /* C6 - Purple (Ungu) */
    .tab-color-c6.active {
        background-color: #6f42c1 !important;
        color: #fff !important;
    }

    .bg-purple {
        background-color: #6f42c1 !important;
        color: #fff !important;
    }

    .btn-purple {
        background-color: #6f42c1 !important;
        color: #fff !important;
        border-color: #6f42c1;
    }

    .btn-purple:hover {
        background-color: #59339d !important;
        color: #fff !important;
    }

    .text-purple {
        color: #6f42c1 !important;
    }

    .border-purple {
        border-color: #6f42c1 !important;
    }

    .text-wrap-custom {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>

<div class="content-wrapper">
    <!-- Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-cubes text-primary mr-2"></i>Kelola Soal (C1 - C6)</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active">Bank Soal Per-Level</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">

            <!-- INFORMASI MATERI / MATA PELAJARAN SESUAI GADIK YANG LOGIN -->
            <div class="card card-outline card-info mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <span class="badge badge-info mb-1"><i class="fas fa-book-reader mr-1"></i> Mata Pelajaran / Kelas Aktif</span>
                            <h3 class="font-weight-bold text-dark m-0" id="info-nama-matkul">
                                <?= esc($detail_ujian['mata_pelajaran'] ?? 'Hukum Kepolisian'); ?>
                            </h3>
                            <p class="text-muted m-0 small">
                                Kelas: <strong class="text-dark"><?= esc($detail_ujian['nama_kelas'] ?? 'Kelas A'); ?></strong>
                                &bull; Jadwal: <span class="text-primary"><?= esc($detail_ujian['jadwal_ujian'] ?? 'Rabu, 12 Agu 2026 (10:45 - 11:45 WIB)'); ?></span>
                                &bull; Gadik: <strong><?= esc($detail_ujian['pangkat_gadik'] ?? session()->get('pangkat') ?? ''); ?> <?= esc($detail_ujian['nama_gadik'] ?? session()->get('nama_user') ?? '-'); ?></strong>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-right mt-2 mt-md-0">
                            <a href="<?= base_url($role_prefix . '/obe/kelas-ujian'); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-exchange-alt mr-1"></i> Ganti Kelas / Mata Pelajaran
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB NAVIGASI LEVEL C1 S/D C6 -->
            <div class="card card-outline card-primary" id="main-card-wrapper">
                <div class="card-header p-0 pt-1 border-bottom-0">
                    <ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold tab-color-c1 active" id="tab-c1" data-toggle="pill" href="#content-main" role="tab" onclick="switchLevel('C1')">
                                <i class="fas fa-edit mr-1"></i> Level C1
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold tab-color-c2" id="tab-c2" data-toggle="pill" href="#content-main" role="tab" onclick="switchLevel('C2')">
                                <i class="fas fa-brain mr-1"></i> Level C2
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold tab-color-c3" id="tab-c3" data-toggle="pill" href="#content-main" role="tab" onclick="switchLevel('C3')">
                                <i class="fas fa-user-check mr-1"></i> Level C3
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold tab-color-c4" id="tab-c4" data-toggle="pill" href="#content-main" role="tab" onclick="switchLevel('C4')">
                                <i class="fas fa-chart-bar mr-1"></i> Level C4
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold tab-color-c5" id="tab-c5" data-toggle="pill" href="#content-main" role="tab" onclick="switchLevel('C5')">
                                <i class="fas fa-balance-scale mr-1"></i> Level C5
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold tab-color-c6" id="tab-c6" data-toggle="pill" href="#content-main" role="tab" onclick="switchLevel('C6')">
                                <i class="fas fa-tools mr-1"></i> Level C6
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="content-main" role="tabpanel">

                            <!-- HEADER INFORMASI LEVEL -->
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded border">
                                <div>
                                    <h4 class="m-0 font-weight-bold text-primary" id="title-level-active">LEVEL C1 - MENGINGAT (REMEMBER)</h4>
                                    <small class="text-muted" id="desc-level-active">Kelola butir soal tingkat mengingat, istilah, dan pengetahuan dasar.</small>
                                </div>
                                <!-- <button type="button" class="btn btn-primary font-weight-bold" id="btn-tambah-soal" onclick="resetAndOpenForm()">
                                    <i class="fas fa-plus-circle mr-1"></i> Tambah Soal Baru (<span id="badge-level-btn">C1</span>)
                                </button> -->
                            </div>

                            <!-- FORM INPUT/EDIT SOAL -->
                            <div class="card border-primary mb-4" id="box-form-input">
                                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center" id="header-form-input">
                                    <h5 class="card-title m-0 font-weight-bold" id="form-action-title">
                                        <i class="fas fa-pen-square mr-1"></i> Form Input Soal Baru (C1)
                                    </h5>
                                    <button type="button" class="btn btn-xs btn-light" onclick="toggleFormInput()">
                                        <i class="fas fa-minus" id="icon-toggle-form"></i>
                                    </button>
                                </div>
                                <div class="card-body" id="body-form-input">
                                    <form id="formSoal">
                                        <input type="hidden" name="<?= csrf_token() ?>" id="csrf_token_field" value="<?= csrf_hash() ?>">
                                        <input type="hidden" name="id_soal" id="id_soal" value="">
                                        <input type="hidden" name="level_soal" id="level_soal" value="C1">
                                        <input type="hidden" name="id_ujian" value="<?= esc($detail_ujian['id'] ?? ''); ?>">
                                        <!-- TAMBAHKAN INI -->
                                        <input type="hidden" name="mapel_id" id="mapel_id" value="<?= esc($detail_ujian['mapel_id'] ?? ''); ?>">
                                        <input type="hidden" name="bobot_soal" id="bobot_soal" value="0">
                                        <input type="hidden" name="kelas_ujian_id" id="kelas_ujian_id" value="<?= esc($detail_ujian['id'] ?? ''); ?>">

                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold text-dark">PERTANYAAN SOAL :</label>
                                            <textarea name="pertanyaan" id="pertanyaan" class="form-control" rows="3" placeholder="Masukkan pertanyaan soal di sini..." required></textarea>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold text-success">KUNCI JAWABAN / PENJELASAN LENGKAP :</label>
                                            <textarea name="jawaban" id="jawaban" class="form-control" rows="4" placeholder="Masukkan kunci jawaban lengkap di sini..." required></textarea>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-secondary mr-2" onclick="resetAndOpenForm()">
                                                <i class="fas fa-undo mr-1"></i> Batal / Reset
                                            </button>
                                            <button type="button" class="btn btn-primary px-4" id="btn-simpan-soal" onclick="simpanSoal()">
                                                <i class="fas fa-save mr-1"></i> Simpan Soal
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- DAFTAR KUMPULAN SOAL -->
                            <div class="card card-outline card-secondary">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title m-0 font-weight-bold">
                                        <i class="fas fa-list-alt mr-1"></i> Daftar Bank Soal (<span id="count-soal-active">0</span> Soal)
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped mb-0" id="table-bank-soal">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 5%" class="text-center">NO</th>
                                                    <th style="width: 50%">PERTANYAAN SOAL</th>
                                                    <th style="width: 35%">KUNCI JAWABAN</th>
                                                    <th style="width: 10%" class="text-center">AKSI</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody-soal">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?= $this->endSection(); ?>


<?= $this->section('script'); ?>
<script>
    const rolePrefix = '<?= $role_prefix; ?>';
    const baseUrl = '<?= rtrim(base_url(), '/'); ?>';
    let activeLevel = 'C1';
    let currentData = [];

    const levelConfig = {
        'C1': {
            name: 'C1 - MENGINGAT (REMEMBER)',
            desc: 'Kelola butir soal tingkat mengingat, istilah, dan pengetahuan dasar.',
            colorClass: 'primary',
            btnClass: 'btn-primary',
            textClass: 'text-primary',
            bgClass: 'bg-primary',
            borderClass: 'border-primary'
        },
        'C2': {
            name: 'C2 - MEMAHAMI (UNDERSTAND)',
            desc: 'Kelola butir soal penjelasan konsep, pemahaman, dan inferensi.',
            colorClass: 'info',
            btnClass: 'btn-info',
            textClass: 'text-info',
            bgClass: 'bg-info',
            borderClass: 'border-info'
        },
        'C3': {
            name: 'C3 - MENERAPKAN (APPLY)',
            desc: 'Kelola butir soal penerapan rumus, implementasi, dan kasus nyata.',
            colorClass: 'success',
            btnClass: 'btn-success',
            textClass: 'text-success',
            bgClass: 'bg-success',
            borderClass: 'border-success'
        },
        'C4': {
            name: 'C4 - MENGANALISIS (ANALYZE)',
            desc: 'Kelola butir soal analisis struktur, identifikasi masalah, dan logika.',
            colorClass: 'warning',
            btnClass: 'btn-warning',
            textClass: 'text-warning',
            bgClass: 'bg-warning',
            borderClass: 'border-warning'
        },
        'C5': {
            name: 'C5 - MENGEVALUASI (EVALUATE)',
            desc: 'Kelola butir soal penilaian, kritik, validasi, dan justifikasi.',
            colorClass: 'danger',
            btnClass: 'btn-danger',
            textClass: 'text-danger',
            bgClass: 'bg-danger',
            borderClass: 'border-danger'
        },
        'C6': {
            name: 'C6 - MENCIPTA (CREATE)',
            desc: 'Kelola butir soal perancangan, sintesis, dan pembuatan solusi baru.',
            colorClass: 'purple',
            btnClass: 'btn-purple',
            textClass: 'text-purple',
            bgClass: 'bg-purple',
            borderClass: 'border-purple'
        }
    };

    $(document).ready(function() {
        $.ajaxSetup({
            beforeSend: function(xhr, settings) {
                if (settings.type && settings.type.toUpperCase() !== 'GET') {
                    const csrfHash = $('meta[name="csrf-token"]').attr('content');
                    const csrfHeaderName = $('meta[name="csrf-header"]').attr('content') || 'X-CSRF-TOKEN';
                    xhr.setRequestHeader(csrfHeaderName, csrfHash);

                    const csrfName = $('#csrf_token_field').attr('name') || '<?= csrf_token() ?>';
                    if (settings.data) {
                        if (typeof settings.data === 'string') {
                            if (!settings.data.includes(csrfName)) {
                                settings.data += `&${csrfName}=${encodeURIComponent(csrfHash)}`;
                            }
                        } else if (typeof settings.data === 'object') {
                            settings.data[csrfName] = csrfHash;
                        }
                    } else {
                        settings.data = {};
                        settings.data[csrfName] = csrfHash;
                    }
                }
            }
        });

        switchLevel('C1');
    });

    function updateCsrfToken(newToken) {
        if (newToken) {
            $('meta[name="csrf-token"]').attr('content', newToken);
            $('#csrf_token_field').val(newToken);
        }
    }

    function switchLevel(level) {
        activeLevel = level;
        const config = levelConfig[level];

        $('#level_soal').val(level);
        $('#badge-level-btn').text(level);
        $('#title-level-active').text(config.name);
        $('#desc-level-active').text(config.desc);

        applyThemeColor(config);

        // Reset form ke mode tambah saat pindah tab agar bersih
        resetAndOpenForm(false);
        loadTableSoal();
    }

    function applyThemeColor(config) {
        const allBgColors = 'bg-primary bg-info bg-success bg-warning bg-danger bg-purple';
        const allTextColors = 'text-primary text-info text-success text-warning text-danger text-purple';
        const allBorderColors = 'border-primary border-info border-success border-warning border-danger border-purple';
        const allBtnColors = 'btn-primary btn-info btn-success btn-warning btn-danger btn-purple';

        $('#title-level-active').removeClass(allTextColors).addClass(config.textClass);
        $('#btn-tambah-soal').removeClass(allBtnColors).addClass(config.btnClass);
        $('#box-form-input').removeClass(allBorderColors).addClass(config.borderClass);
        $('#header-form-input').removeClass(allBgColors).addClass(config.bgClass);
        $('#btn-simpan-soal').removeClass(allBtnColors).addClass(config.btnClass);
    }

    function loadTableSoal() {
        const targetUrl = `${baseUrl}/${rolePrefix}/obe/bank-soal/get/${activeLevel}`;

        $.ajax({
            url: targetUrl,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    currentData = res.data;
                    renderTableSoal(currentData);
                } else {
                    renderTableSoal([]);
                }
                if (res.csrf_token) {
                    updateCsrfToken(res.csrf_token);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error Status:", status);
            }
        });
    }

    function renderTableSoal(list) {
        $('#count-soal-active').text(list.length);
        const tbody = $('#tbody-soal');
        tbody.empty();

        if (list.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        Belum ada data soal untuk ${activeLevel}. Silakan klik tombol "Tambah Soal Baru".
                    </td>
                </tr>
            `);
            return;
        }

        $.each(list, function(index, item) {
            const qFormatted = escapeHtml(item.pertanyaan).replace(/\n/g, '<br>');
            const textJawaban = item.rubrik_penilaian || item.jawaban || '-';
            const aFormatted = escapeHtml(textJawaban).replace(/\n/g, '<br>');

            tbody.append(`
                <tr>
                    <td class="text-center font-weight-bold">${index + 1}</td>
                    <td><div class="text-wrap-custom">${qFormatted}</div></td>
                    <td><div class="text-wrap-custom text-success font-weight-bold">${aFormatted}</div></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-warning mb-1" onclick="editSoal(${item.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="btn btn-xs btn-danger mb-1" onclick="hapusSoal(${item.id})">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    function simpanSoal() {
        if ($('#pertanyaan').val().trim() === '' || $('#jawaban').val().trim() === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan',
                text: 'Pertanyaan dan Kunci Jawaban wajib diisi!',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const csrfName = $('#csrf_token_field').attr('name') || '<?= csrf_token() ?>';
        const csrfHash = $('meta[name="csrf-token"]').attr('content') || $('#csrf_token_field').val();

        const postData = {
            id_soal: $('#id_soal').val(),
            level_soal: $('#level_soal').val(),
            pertanyaan: $('#pertanyaan').val(),
            jawaban: $('#jawaban').val(),
            mapel_id: $('#mapel_id').val() || '',
            bobot_soal: $('#bobot_soal').val() || 0,

            // PAKSA AMBIL LANGSUNG DARI PHP DI SINI (Dijamin tidak akan kosong/null)
            kelas_ujian_id: '<?= esc($detail_ujian['id'] ?? '') ?>'
        };
        postData[csrfName] = csrfHash;

        Swal.fire({
            title: 'Menyimpan Soal...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: `${baseUrl}/${rolePrefix}/obe/bank-soal/store`,
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(res) {
                if (res.csrf_token) {
                    updateCsrfToken(res.csrf_token);
                }

                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    resetAndOpenForm(true);
                    loadTableSoal();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res.message,
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Server',
                    text: 'Periksa kembali koneksi atau data.',
                    confirmButtonColor: '#d33'
                });
            }
        });
    }

    function editSoal(id) {
        const item = currentData.find(x => x.id == id);
        if (item) {
            $('#id_soal').val(item.id);
            $('#pertanyaan').val(item.pertanyaan);
            $('#jawaban').val(item.rubrik_penilaian || item.jawaban);

            $('#form-action-title').html(`<i class="fas fa-edit mr-1"></i> Edit Soal (${activeLevel})`);

            // Buka form jika tertutup
            $('#body-form-input').slideDown();
            $('#icon-toggle-form').removeClass('fa-plus').addClass('fa-minus');

            $('html, body').animate({
                scrollTop: $('#box-form-input').offset().top - 80
            }, 400);
        }
    }

    function hapusSoal(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: 'Data soal yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${baseUrl}/${rolePrefix}/obe/bank-soal/delete/${id}`,
                    type: 'POST',
                    dataType: 'json',
                    success: function(res) {
                        if (res.csrf_token) {
                            updateCsrfToken(res.csrf_token);
                        }
                        if (res.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            loadTableSoal();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: res.message
                            });
                        }
                    }
                });
            }
        });
    }

    // Parameter 'shouldCollapse' untuk mengatur apakah form langsung ditutup (minimize)
    function resetAndOpenForm(shouldCollapse = false) {
        const currentCsrf = $('meta[name="csrf-token"]').attr('content');
        const currentCsrfName = $('#csrf_token_field').attr('name');

        $('#formSoal')[0].reset();
        $('#csrf_token_field').attr('name', currentCsrfName);
        $('#csrf_token_field').val(currentCsrf);

        $('#id_soal').val('');
        $('#level_soal').val(activeLevel);
        $('#form-action-title').html(`<i class="fas fa-pen-square mr-1"></i> Form Input Soal Baru (${activeLevel})`);

        if (shouldCollapse) {
            $('#body-form-input').slideUp();
            $('#icon-toggle-form').removeClass('fa-minus').addClass('fa-plus');
        } else {
            $('#body-form-input').slideDown();
            $('#icon-toggle-form').removeClass('fa-plus').addClass('fa-minus');
        }
    }

    function toggleFormInput() {
        $('#body-form-input').slideToggle();
        $('#icon-toggle-form').toggleClass('fa-minus fa-plus');
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }
</script>
<?= $this->endSection(); ?>