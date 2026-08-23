<?= $this->extend('layout/main'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content pt-4">
        <div class="container-fluid">

            <!-- Notifikasi Flashdata jika ada -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="icon fas fa-ban"></i> <?= session()->getFlashdata('error'); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card card-primary card-outline">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-edit text-primary mr-2"></i>
                        Form Penilaian Siswa: <span class="text-info"><?= esc($detail['nama_siswa']); ?></span>
                        (Nosis: <?= esc($detail['nosis']); ?>)
                    </h3>
                    <div class="card-tools">
                        <a href="<?= base_url('gadik/obe/penilaian/kelas/' . $kelas_ujian_id); ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    <!-- Box Informasi Detail Peserta & Gadik -->
                    <div class="callout callout-info bg-light">
                        <h5><i class="fas fa-info-circle text-info"></i> Informasi Ujian & Penguji</h5>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <span class="text-muted d-block">Mata Pelajaran</span>
                                <strong><?= esc($detail['nama_mapel']); ?></strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Kelas Ujian</span>
                                <strong><?= esc($detail['nama_kelas']); ?></strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Pleton Siswa</span>
                                <strong><?= esc($detail['nama_pleton'] ?? '-'); ?></strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Gadik Penilai</span>
                                <strong class="text-success"><?= esc($detail['nama_gadik'] ?? 'Belum Ditentukan'); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Form Input Penilaian Per Soal & Akumulasi Per C -->
                    <form id="formSimpanNilai" class="mt-4">
                        <?= csrf_field(); ?>

                        <?php if (!empty($soal)):
                            $soalByKlasifikasi = [];
                            foreach ($soal as $s) {
                                $level = strtoupper(trim($s['tingkat_taksonomi'] ?? 'UMUM'));
                                $soalByKlasifikasi[$level][] = $s;
                            }
                            ksort($soalByKlasifikasi);
                            $nomorSoal = 1;
                        ?>

                            <?php foreach ($soalByKlasifikasi as $klasifikasi => $daftar_soal):
                                $katLower = strtolower($klasifikasi);

                                $maxPerSoal = 100;
                                if ($katLower === 'c1') {
                                    $maxPerSoal = 2.5;
                                } elseif ($katLower === 'c2' || $katLower === 'c3') {
                                    $maxPerSoal = 5;
                                } elseif (in_array($katLower, ['c4', 'c5', 'c6'])) {
                                    $maxPerSoal = 100;
                                }
                            ?>
                                <div class="card card-outline card-info mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 class="card-title font-weight-bold">
                                            Tingkat Taksonomi: <?= esc($klasifikasi) ?>
                                            <small class="text-muted font-weight-normal ml-2">(Maksimal Nilai per Soal: <?= $maxPerSoal ?>)</small>
                                        </h3>
                                        <div class="card-tools ml-auto">
                                            <span class="badge badge-info p-2" style="font-size: 14px;">
                                                Total <?= esc($klasifikasi) ?>: <strong id="total_<?= $katLower ?>">0.00</strong>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($daftar_soal as $s): ?>
                                            <?php
                                            $teksJawaban = '';
                                            if (!empty($jawaban_peserta)) {
                                                foreach ($jawaban_peserta as $jp) {
                                                    if (isset($jp['soal_id']) && $jp['soal_id'] == $s['id']) {
                                                        $teksJawaban = $jp['jawaban_teks'] ?? $jp['jawaban'] ?? $jp['isi_jawaban'] ?? '';
                                                        break;
                                                    }
                                                }
                                            }
                                            $nilaiSoalSebelumnya = $s['nilai_diberikan'] ?? '';
                                            $isHots = in_array($katLower, ['c4', 'c5', 'c6']);
                                            ?>
                                            <div class="p-3 mb-3 bg-light border rounded">
                                                <div class="row">
                                                    <!-- Bagian Soal & Jawaban -->
                                                    <div class="<?= $isHots ? 'col-md-7' : 'col-md-9' ?>">
                                                        <div class="mb-2">
                                                            <strong class="text-primary">Soal <?= $nomorSoal ?>:</strong>
                                                            <div class="mt-1"><?= esc($s['pertanyaan']); ?></div>
                                                            <?php if (!empty($s['rubrik_penilaian'])): ?>
                                                                <small class="text-muted d-block mt-1">Rubrik Penilaian: <?= esc($s['rubrik_penilaian']); ?></small>
                                                            <?php endif; ?>
                                                        </div>

                                                        <hr>

                                                        <div class="mt-2">
                                                            <strong class="text-secondary">Jawaban Siswa:</strong>
                                                            <div class="p-3 mt-1 bg-white border rounded" style="min-height: 60px;">
                                                                <?= !empty($teksJawaban) ? $teksJawaban : '<span class="text-muted font-italic">Siswa belum mengisi jawaban.</span>'; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Bagian Input Nilai Per Soal / Rubrik C4-C6 -->
                                                    <div class="<?= $isHots ? 'col-md-5' : 'col-md-3' ?> d-flex flex-column justify-content-center border-left">
                                                        <?php if ($isHots): ?>
                                                            <!-- Tabel Rubrik / Simulasi Penskoran untuk C4, C5, C6 -->
                                                            <div class="card card-outline card-secondary mb-0">
                                                                <div class="card-header py-1 bg-success">
                                                                    <h6 class="card-title font-weight-bold mb-0" style="font-size: 13px;">
                                                                        <i class="fas fa-table mr-1"></i> Penskoran Rubrik (Skala 1-4)
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body p-2">
                                                                    <table class="table table-sm table-bordered mb-2" style="font-size: 11px;">
                                                                        <thead class="bg-light text-center">
                                                                            <tr>
                                                                                <th>Dimensi (Bobot)</th>
                                                                                <th style="width: 55px;">Skor</th>
                                                                                <th style="width: 55px;">Nilai</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>Ketepatan Substansi & Konsep (30)</td>
                                                                                <td>
                                                                                    <select class="form-control form-control-sm hots-select soal-<?= $s['id'] ?>" data-soal="<?= $s['id'] ?>" data-bobot="30">
                                                                                        <option value="1">1</option>
                                                                                        <option value="2">2</option>
                                                                                        <option value="3" selected>3</option>
                                                                                        <option value="4">4</option>
                                                                                    </select>
                                                                                </td>
                                                                                <td class="text-center font-weight-bold text-primary val-dim" id="dim1_<?= $s['id'] ?>">22.50</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Kedalaman Analisis (30)</td>
                                                                                <td>
                                                                                    <select class="form-control form-control-sm hots-select soal-<?= $s['id'] ?>" data-soal="<?= $s['id'] ?>" data-bobot="30">
                                                                                        <option value="1">1</option>
                                                                                        <option value="2">2</option>
                                                                                        <option value="3" selected>3</option>
                                                                                        <option value="4">4</option>
                                                                                    </select>
                                                                                </td>
                                                                                <td class="text-center font-weight-bold text-primary val-dim" id="dim2_<?= $s['id'] ?>">22.50</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Argumentasi & Justifikasi (25)</td>
                                                                                <td>
                                                                                    <select class="form-control form-control-sm hots-select soal-<?= $s['id'] ?>" data-soal="<?= $s['id'] ?>" data-bobot="25">
                                                                                        <option value="1">1</option>
                                                                                        <option value="2">2</option>
                                                                                        <option value="3" selected>3</option>
                                                                                        <option value="4">4</option>
                                                                                    </select>
                                                                                </td>
                                                                                <td class="text-center font-weight-bold text-primary val-dim" id="dim3_<?= $s['id'] ?>">18.75</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Sistemotika & Kejelasan (15)</td>
                                                                                <td>
                                                                                    <select class="form-control form-control-sm hots-select soal-<?= $s['id'] ?>" data-soal="<?= $s['id'] ?>" data-bobot="15">
                                                                                        <option value="1">1</option>
                                                                                        <option value="2">2</option>
                                                                                        <option value="3">3</option>
                                                                                        <option value="4" selected>4</option>
                                                                                    </select>
                                                                                </td>
                                                                                <td class="text-center font-weight-bold text-primary val-dim" id="dim4_<?= $s['id'] ?>">15.00</td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                    <div class="d-flex justify-content-between align-items-center bg-light p-1 rounded">
                                                                        <span class="font-weight-bold" style="font-size: 11px;">NILAI BUTIR (0-100):</span>
                                                                        <span class="font-weight-bold text-success" id="total_butir_<?= $s['id'] ?>" style="font-size: 13px;">78.75</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Input Hidden untuk menampung nilai akhir butir soal agar tersimpan ke database -->
                                                            <input type="hidden"
                                                                class="form-control input-nilai-soal kategori-<?= $katLower ?>"
                                                                data-kategori="<?= $katLower ?>"
                                                                name="nilai_soal[<?= $s['id'] ?>]"
                                                                id="nilai_soal_<?= $s['id'] ?>"
                                                                value="<?= esc($nilaiSoalSebelumnya ?: '78.75'); ?>">
                                                        <?php else: ?>
                                                            <!-- Input Nilai Biasa untuk C1, C2, C3 -->
                                                            <div class="form-group mb-0">
                                                                <label for="nilai_soal_<?= $s['id'] ?>"><strong>Nilai Soal <?= $nomorSoal ?></strong></label>
                                                                <input type="number" step="any" min="0" max="<?= $maxPerSoal ?>"
                                                                    class="form-control input-nilai-soal kategori-<?= $katLower ?>"
                                                                    data-kategori="<?= $katLower ?>"
                                                                    name="nilai_soal[<?= $s['id'] ?>]"
                                                                    id="nilai_soal_<?= $s['id'] ?>"
                                                                    value="<?= esc($nilaiSoalSebelumnya); ?>"
                                                                    placeholder="Maks: <?= $maxPerSoal ?>" required>
                                                                <small class="text-muted">Maksimal: <?= $maxPerSoal ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php $nomorSoal++; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <p class="text-muted font-italic m-0">Tidak ada data soal terkait untuk mapel ini.</p>
                        <?php endif; ?>

                        <!-- Ringkasan Nilai Akhir & Tombol Simpan -->
                        <?php
                        $sudahDinilai = isset($detail['nilai_akhir']) && $detail['nilai_akhir'] !== '' && $detail['nilai_akhir'] !== null;
                        ?>
                        <input type="hidden" id="status_penilaian" name="status_penilaian" value="<?= $sudahDinilai ? 'sudah' : 'belum'; ?>">

                        <div class="card card-success card-outline mt-4">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h4 class="font-weight-bold mb-0">Nilai Akhir (Skala 100%):</h4>
                                        <small class="text-muted">Akumulasi proporsional keseluruhan kategori C1 sampai C6.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" step="any" class="form-control form-control-lg font-weight-bold text-success" id="nilai_akhir" name="nilai_akhir"
                                            value="<?= esc($detail['nilai_akhir'] ?? ''); ?>" readonly placeholder="0.00">
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <button type="submit" class="btn btn-lg <?= $sudahDinilai ? 'btn-success' : 'btn-warning text-dark'; ?> btn-block" id="btnSimpan">
                                            <i class="fas <?= $sudahDinilai ? 'fa-check-circle' : 'fa-clock'; ?> mr-1"></i>
                                            <span id="teksTombol"><?= $sudahDinilai ? 'Sudah Dinilai' : 'Belum Dinilai'; ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    const kelasUjianId = "<?= $kelas_ujian_id ?>";
    const siswaId = "<?= $siswa_id ?>";

    // Fungsi menghitung total nilai per butir soal berdasarkan rubrik (skala 1-4)
    function hitungNilaiRubrik(soalId) {
        let selects = document.querySelectorAll('.soal-' + soalId);
        let totalButir = 0;

        selects.forEach((select, index) => {
            let skor = parseFloat(select.value) || 0;
            let bobot = parseFloat(select.getAttribute('data-bobot')) || 0;

            // Rumus dimensi: (Skor / 4) * Bobot
            let nilaiDimensi = (skor / 4) * bobot;

            // Update teks nilai dimensi di tabel
            let dimEl = document.getElementById(`dim${index + 1}_${soalId}`);
            if (dimEl) {
                dimEl.innerText = nilaiDimensi.toFixed(2);
            }

            totalButir += nilaiDimensi;
        });

        // Update total butir di bawah tabel
        let totalButirEl = document.getElementById('total_butir_' + soalId);
        if (totalButirEl) {
            totalButirEl.innerText = totalButir.toFixed(2);
        }

        // Masukkan ke input hidden nilai soal utama
        let inputSoal = document.getElementById('nilai_soal_' + soalId);
        if (inputSoal) {
            inputSoal.value = totalButir.toFixed(2);
        }

        hitungAkumulasiNilai();
    }

    function hitungAkumulasiNilai() {
        let totalNilaiC1C3 = 0;
        let countC1C3 = 0;

        let totalNilaiC4C6 = 0;
        let countC4C6 = 0;

        let kategoriList = [];

        document.querySelectorAll('.input-nilai-soal').forEach(input => {
            let kat = input.getAttribute('data-kategori');
            if (!kategoriList.includes(kat)) {
                kategoriList.push(kat);
            }
        });

        // Hitung total masing-masing kategori
        kategoriList.forEach(kat => {
            let inputsPerKat = document.querySelectorAll('.kategori-' + kat);
            let sumKat = 0;
            let maxTotalKat = 0;

            inputsPerKat.forEach(input => {
                let val = parseFloat(input.value) || 0;
                let maxVal = parseFloat(input.getAttribute('max')) || 100;

                if (val > maxVal) {
                    val = maxVal;
                    input.value = maxVal;
                }
                if (val < 0) {
                    val = 0;
                    input.value = 0;
                }

                sumKat += val;
                maxTotalKat += maxVal;
            });

            let badgeEl = document.getElementById('total_' + kat);
            if (badgeEl) {
                badgeEl.innerText = sumKat.toFixed(2);
            }

            // Hitung persentase per kategori (0 s.d 1)
            let persentaseKat = maxTotalKat > 0 ? (sumKat / maxTotalKat) : 0;

            // Kelompokkan ke LOTS (C1, C2, C3) atau HOTS (C4, C5, C6)
            let katUpper = kat.toUpperCase();
            if (['C1', 'C2', 'C3'].includes(katUpper)) {
                totalNilaiC1C3 += persentaseKat;
                countC1C3++;
            } else if (['C4', 'C5', 'C6'].includes(katUpper)) {
                totalNilaiC4C6 += persentaseKat;
                countC4C6++;
            }
        });

        // Rata-rata persentase kelompok
        let avgC1C3 = countC1C3 > 0 ? (totalNilaiC1C3 / countC1C3) : 0;
        let avgC4C6 = countC4C6 > 0 ? (totalNilaiC4C6 / countC4C6) : 0;

        // Terapkan Bobot: C1-C3 (60%) dan C4-C6 (40%)
        let nilaiAkhir = (avgC1C3 * 60) + (avgC4C6 * 40);

        let inputNilaiAkhir = document.getElementById('nilai_akhir');
        let inputStatus = document.getElementById('status_penilaian');
        let btnSimpan = document.getElementById('btnSimpan');

        if (nilaiAkhir > 100) {
            nilaiAkhir = 100;
        }

        inputNilaiAkhir.value = nilaiAkhir.toFixed(2);

        if (nilaiAkhir > 0) {
            btnSimpan.className = 'btn btn-lg btn-success btn-block';
            btnSimpan.innerHTML = '<i class="fas fa-check-circle mr-1"></i> <span id="teksTombol">Sudah Dinilai</span>';
            inputStatus.value = 'sudah';
        } else {
            btnSimpan.className = 'btn btn-lg btn-warning text-dark btn-block';
            btnSimpan.innerHTML = '<i class="fas fa-clock mr-1"></i> <span id="teksTombol">Belum Dinilai</span>';
            inputStatus.value = 'belum';
        }
    }
    // Event listener untuk dropdown rubrik HOTS (C4, C5, C6)
    document.querySelectorAll('.hots-select').forEach(select => {
        select.addEventListener('change', function() {
            let soalId = this.getAttribute('data-soal');
            hitungNilaiRubrik(soalId);
        });
    });

    // Event listener untuk input nilai biasa (C1, C2, C3)
    document.querySelectorAll('.input-nilai-soal').forEach(input => {
        input.addEventListener('input', hitungAkumulasiNilai);
    });

    // Inisialisasi hitung saat halaman pertama kali dimuat
    document.addEventListener("DOMContentLoaded", function() {
        // Hitung ulang semua rubrik hots yang ada di halaman
        let processedSoal = [];
        document.querySelectorAll('.hots-select').forEach(select => {
            let soalId = select.getAttribute('data-soal');
            if (!processedSoal.includes(soalId)) {
                processedSoal.push(soalId);
                hitungNilaiRubrik(soalId);
            }
        });
        hitungAkumulasiNilai();
    });

    function resetButton(btn, isSudah) {
        if (isSudah) {
            btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> <span id="teksTombol">Sudah Dinilai</span>').removeClass().addClass('btn btn-lg btn-success btn-block');
        } else {
            btn.prop('disabled', false).html('<i class="fas fa-clock mr-1"></i> <span id="teksTombol">Belum Dinilai</span>').removeClass().addClass('btn btn-lg btn-warning text-dark btn-block');
        }
    }

    // Proses AJAX Simpan Nilai
    // Proses AJAX Simpan Nilai
    $('#formSimpanNilai').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#btnSimpan');
        const isSudah = $('#status_penilaian').val() === 'sudah';

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        const formData = new URLSearchParams(new FormData(this));
        formData.append('kelas_ujian_id', kelasUjianId);
        formData.append('siswa_id', siswaId);
        formData.append('status_pengerjaan', 'selesai');

        // Mengambil segment pertama URL aktif (misal: admin, gadik, dll) secara otomatis
        const currentPrefix = window.location.pathname.split('/')[1];
        const dynamicUrl = `/${currentPrefix}/obe/penilaian/simpan`;

        fetch(dynamicUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(res => {
                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = `/${currentPrefix}/obe/penilaian/kelas/` + kelasUjianId;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.message
                    });
                    resetButton(btn, isSudah);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Server',
                    text: 'Terjadi kesalahan pada sistem.'
                });
                resetButton(btn, isSudah);
            });
    });
</script>
<?= $this->endSection(); ?>