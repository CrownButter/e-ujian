<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-calculator text-primary mr-2"></i>Pengaturan Dimensi Rubrik & Penskoran</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active">Bobot Rubrik Nilai</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <!-- FORM PENGATURAN BOBOT DIMENSI -->
                <div class="col-md-6">
                    <div class="card card-primary card-outline">
                        <div class="card-header py-2">
                            <h5 class="card-title m-0 font-weight-bold">
                                <i class="fas fa-sliders-h mr-1"></i> Pengaturan Bobot Dimensi Rubrik
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="formBobotDimensi">
                                <input type="hidden" name="<?= csrf_token() ?>" id="csrf_token_field" value="<?= csrf_hash() ?>">

                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>NAMA DIMENSI EVALUASI</th>
                                                <th style="width: 35%;" class="text-center">BOBOT (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="text" class="form-control form-control-sm" name="dimensi_1_nama" value="Ketepatan Substansi & Konsep" required></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="1" min="0" max="100" class="form-control input-bobot text-center" name="dimensi_1_bobot" value="30" required>
                                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><input type="text" class="form-control form-control-sm" name="dimensi_2_nama" value="Kedalaman Analisis" required></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="1" min="0" max="100" class="form-control input-bobot text-center" name="dimensi_2_bobot" value="30" required>
                                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><input type="text" class="form-control form-control-sm" name="dimensi_3_nama" value="Argumentasi & Justifikasi" required></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="1" min="0" max="100" class="form-control input-bobot text-center" name="dimensi_3_bobot" value="25" required>
                                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><input type="text" class="form-control form-control-sm" name="dimensi_4_nama" value="Sistematika & Kejelasan" required></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="1" min="0" max="100" class="form-control input-bobot text-center" name="dimensi_4_bobot" value="15" required>
                                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-light font-weight-bold">
                                            <tr>
                                                <td class="text-right">TOTAL BOBOT DIMENSI:</td>
                                                <td class="text-center h6 m-0" id="total-bobot-dimensi">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <?php if (session()->get('role') == '1'): ?>
                                    <button type="button" class="btn btn-primary btn-block font-weight-bold mt-2" onclick="simpanPengaturanBobot()">
                                        <i class="fas fa-save mr-1"></i> Simpan Pengaturan Dimensi
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- SIMULASI KALKULATOR PENSKORAN RUBRIK -->
                <div class="col-md-6">
                    <div class="card card-success card-outline">
                        <div class="card-header py-2">
                            <h5 class="card-title m-0 font-weight-bold">
                                <i class="fas fa-table mr-1"></i> Simulasi Penskoran Soal (Skala 1 - 4)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>DIMENSI (BOBOT)</th>
                                        <th style="width: 25%;" class="text-center">SKOR (1-4)</th>
                                        <th style="width: 25%;" class="text-center">NILAI DIMENSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Ketepatan Substansi & Konsep (30)</td>
                                        <td>
                                            <select class="form-control form-control-sm select-skor" data-bobot="30" id="skor_1">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3" selected>3</option>
                                                <option value="4">4</option>
                                            </select>
                                        </td>
                                        <td class="text-center font-weight-bold text-primary" id="nilai_dimensi_1">22.50</td>
                                    </tr>
                                    <tr>
                                        <td>Kedalaman Analisis (30)</td>
                                        <td>
                                            <select class="form-control form-control-sm select-skor" data-bobot="30" id="skor_2">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3" selected>3</option>
                                                <option value="4">4</option>
                                            </select>
                                        </td>
                                        <td class="text-center font-weight-bold text-primary" id="nilai_dimensi_2">22.50</td>
                                    </tr>
                                    <tr>
                                        <td>Argumentasi & Justifikasi (25)</td>
                                        <td>
                                            <select class="form-control form-control-sm select-skor" data-bobot="25" id="skor_3">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3" selected>3</option>
                                                <option value="4">4</option>
                                            </select>
                                        </td>
                                        <td class="text-center font-weight-bold text-primary" id="nilai_dimensi_3">18.75</td>
                                    </tr>
                                    <tr>
                                        <td>Sistematika & Kejelasan (15)</td>
                                        <td>
                                            <select class="form-control form-control-sm select-skor" data-bobot="15" id="skor_4">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4" selected>4</option>
                                            </select>
                                        </td>
                                        <td class="text-center font-weight-bold text-primary" id="nilai_dimensi_4">15.00</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="2" class="text-right">NILAI BUTIR HOTS (0-100):</th>
                                        <th class="text-center h5 m-0 font-weight-bold text-success" id="total_nilai_butir">78.75</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="text-right">PREDIKAT:</th>
                                        <th class="text-center font-weight-bold" id="predikat_butir">Setara predikat B</th>
                                    </tr>
                                </tfoot>
                            </table>
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

    $(document).ready(function() {
        hitungSimulasiPenskoran();

        $('.select-skor').on('change', function() {
            hitungSimulasiPenskoran();
        });

        $('.input-bobot').on('input change', function() {
            validateTotalBobot();
        });
    });

    function validateTotalBobot() {
        let total = 0;
        $('.input-bobot').each(function() {
            total += parseFloat($(this).val()) || 0;
        });

        const badge = $('#total-bobot-dimensi');
        badge.text(`${total}%`);
        if (total === 100) {
            badge.removeClass('text-danger').addClass('text-success');
        } else {
            badge.removeClass('text-success').addClass('text-danger');
        }
    }

    function hitungSimulasiPenskoran() {
        let totalNilai = 0;

        for (let i = 1; i <= 4; i++) {
            const skorSelect = $(`#skor_${i}`);
            const skor = parseFloat(skorSelect.val()) || 0;
            const bobot = parseFloat(skorSelect.data('bobot')) || 0;

            // Rumus: (Skor / 4) * Bobot
            const nilaiDimensi = (skor / 4) * bobot;
            $(`#nilai_dimensi_${i}`).text(nilaiDimensi.toFixed(2));

            totalNilai += nilaiDimensi;
        }

        $('#total_nilai_butir').text(totalNilai.toFixed(2));

        // Penentuan Predikat
        let predikat = 'Setara predikat E';
        if (totalNilai >= 85) predikat = 'Setara predikat A';
        else if (totalNilai >= 75) predikat = 'Setara predikat B';
        else if (totalNilai >= 65) predikat = 'Setara predikat C';
        else if (totalNilai >= 50) predikat = 'Setara predikat D';

        $('#predikat_butir').text(predikat);
    }

    function simpanPengaturanBobot() {
        const formData = $('#formBobotDimensi').serialize();

        Swal.fire({
            title: 'Menyimpan Pengaturan...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: `${baseUrl}/${rolePrefix}/obe/bobot-nilai/store`,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.csrf_token) {
                    $('#csrf_token_field').val(res.csrf_token);
                }

                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: res.message,
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal menyimpan pengaturan bobot.',
                    confirmButtonColor: '#d33'
                });
            }
        });
    }
</script>
<?= $this->endSection(); ?>