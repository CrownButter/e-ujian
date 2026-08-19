<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= $title; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active"><?= $title; ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><?= esc($page_title) ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info"></i> Informasi Ujian</h5>
                                <p><strong>Mata Pelajaran:</strong> <?= esc($ujian['nama_mapel'] ?? '-') ?></p>
                                <p><strong>Penguji:</strong>
                                    <?= (!empty($ujian['nama_pangkat']) ? esc($ujian['nama_pangkat']) . ' - ' : '') . esc($ujian['nama_pegawai'] ?? '-') ?>
                                </p>
                            </div>

                            <hr>

                            <h4 class="mb-3">Daftar Soal</h4>
                            <?php if (!empty($listSoal)): ?>
                                <form id="formUjian" action="<?= base_url('siswa/ujian/selesai/' . $ujian['id']) ?>" method="POST">
                                    <?= csrf_field() ?>

                                    <?php
                                    // Standar urutan taksonomi C1 sampai C6
                                    $allowedLevels = ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'];
                                    $soalByKlasifikasi = [];

                                    // Kelompokkan soal berdasarkan kolom 'tingkat_taksonomi' dari database
                                    foreach ($listSoal as $s) {
                                        $rawLevel = $s['tingkat_taksonomi'] ?? 'UMUM';
                                        $klasifikasi = strtoupper(trim($rawLevel));

                                        if (empty($klasifikasi)) {
                                            $klasifikasi = 'UMUM';
                                        }

                                        $soalByKlasifikasi[$klasifikasi][] = $s;
                                    }

                                    // Urutkan key sesuai standar C1-C6 jika ada
                                    $sortedSoalByKlasifikasi = [];
                                    foreach ($allowedLevels as $lvl) {
                                        if (isset($soalByKlasifikasi[$lvl])) {
                                            $sortedSoalByKlasifikasi[$lvl] = $soalByKlasifikasi[$lvl];
                                            unset($soalByKlasifikasi[$lvl]);
                                        }
                                    }
                                    // Masukkan sisa kategori lain jika ada di luar C1-C6
                                    foreach ($soalByKlasifikasi as $lvl => $arrSoal) {
                                        $sortedSoalByKlasifikasi[$lvl] = $arrSoal;
                                    }

                                    // Inisialisasi nomor soal global agar terus berlanjut antar kategori
                                    $nomorSoal = 1;
                                    ?>

                                    <?php foreach ($sortedSoalByKlasifikasi as $klasifikasi => $daftar_soal): ?>
                                        <div class="card card-outline card-primary mb-4 shadow-sm">
                                            <div class="card-header bg-light">
                                                <h3 class="card-title font-weight-bold text-primary">
                                                    <i class="fas fa-layer-group mr-1"></i> Tingkat Taksonomi: <?= esc($klasifikasi) ?>
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($daftar_soal as $soal): ?>
                                                    <div class="form-group mb-4 pb-3 border-bottom">
                                                        <label class="font-weight-bold text-dark">Soal No. <?= $nomorSoal++ ?></label>

                                                        <!-- Informasi tambahan opsional (CPMK / Bobot) jika diperlukan -->
                                                        <?php if (!empty($soal['cpmk'])): ?>
                                                            <div class="text-muted small mb-1"><strong>CPMK:</strong> <?= esc($soal['cpmk']) ?></div>
                                                        <?php endif; ?>

                                                        <div class="mb-2 text-secondary" style="font-size: 15px;">
                                                            <?= $soal['pertanyaan'] ?>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="small text-muted font-italic">Jawaban / Tanggapan Anda:</label>
                                                            <textarea name="jawaban[<?= $soal['id'] ?>]"
                                                                class="form-control"
                                                                rows="4"
                                                                placeholder="Tulis jawaban Anda di sini..."
                                                                oncopy="return false;"
                                                                onpaste="return false;"
                                                                oncut="return false;"></textarea>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <button type="button" onclick="konfirmasiSelesai()" class="btn btn-success btn-lg">Selesai Ujian</button>
                                </form>

                                <!-- SweetAlert2 Script -->
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script>
                                    function konfirmasiSelesai() {
                                        Swal.fire({
                                            title: 'Akhiri Ujian?',
                                            text: "Apakah Anda yakin ingin menyelesaikan ujian ini? Pastikan semua jawaban sudah terisi.",
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#28a745',
                                            cancelButtonColor: '#d33',
                                            confirmButtonText: 'Ya, Selesai!',
                                            cancelButtonText: 'Batal'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                const kelasUjianId = "<?= $ujian['id'] ?? '' ?>";
                                                const formUjian = document.getElementById('formUjian');
                                                const formData = new FormData(formUjian);

                                                fetch('<?= base_url('siswa/ujian/selesai/') ?>' + kelasUjianId, {
                                                        method: 'POST',
                                                        headers: {
                                                            'X-Requested-With': 'XMLHttpRequest'
                                                        },
                                                        body: formData
                                                    })
                                                    .then(response => response.json())
                                                    .then(result => {
                                                        if (result.status) {
                                                            Swal.fire(
                                                                'Berhasil!',
                                                                result.message || 'Ujian telah diselesaikan.',
                                                                'success'
                                                            ).then(() => {
                                                                window.location.href = result.redirect;
                                                            });
                                                        } else {
                                                            Swal.fire('Gagal!', result.message || 'Terjadi kesalahan.', 'error');
                                                        }
                                                    })
                                                    .catch(error => {
                                                        console.error('Error:', error);
                                                        Swal.fire('Error!', 'Terjadi kesalahan koneksi atau server.', 'error');
                                                    });
                                            }
                                        });
                                    }
                                </script>

                            <?php else: ?>
                                <div class="alert alert-warning">Belum ada soal untuk ujian ini.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<!-- Timer Melayang (Floating Timer) -->
<div id="floating-timer" class="shadow-lg rounded px-3 py-2 bg-warning text-white" style="position: fixed; top: 70px; right: 20px; z-index: 9999; font-weight: bold; display: flex; align-items: center; gap: 8px;">
    <i class="fas fa-clock"></i>
    <span>Sisa Waktu: <strong id="countdown-display">00:00:00</strong></span>
</div>
<style>
    #floating-timer {
        animation: pulse-timer 2s infinite;
        font-size: 14px;
        border: 2px solid #fff;
    }

    @keyframes pulse-timer {
        0% {
            box-shadow: 0 0 0 0 rgba(6, 132, 149, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('script'); ?>
<script>
    // Target waktu selesai ujian dari backend
    let waktuSelesai = new Date("<?= $waktuSelesaiUjian; ?>").getTime();

    let x = setInterval(function() {
        let sekarang = new Date().getTime();
        let selisih = waktuSelesai - sekarang;

        if (selisih < 0) {
            clearInterval(x);
            document.getElementById("countdown-display").innerHTML = "WAKTU HABIS";

            // 1. Tampilkan pesan kepada siswa
            alert("Waktu ujian telah habis! Jawaban Anda akan disimpan otomatis.");

            // 2. Panggil fungsi konfirmasiSelesai() agar jawaban tersimpan ke database
            // Pastikan fungsi konfirmasiSelesai() ini ada di file JS/View Anda
            if (typeof konfirmasiSelesai === 'function') {
                konfirmasiSelesai();
            } else {
                // Jika tidak ada fungsi, setidaknya paksa submit form jika ada
                let form = document.getElementById('formUjian');
                if (form) {
                    form.submit();
                } else {
                    // Fallback jika tidak ada form, langsung pindah halaman
                    window.location.href = "<?= base_url('siswa/daftar-ujian'); ?>";
                }
            }
        } else {
            // Logika perhitungan waktu
            let jam = Math.floor((selisih % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let menit = Math.floor((selisih % (1000 * 60 * 60)) / (1000 * 60));
            let detik = Math.floor((selisih % (1000 * 60)) / 1000);

            // Format agar selalu 2 digit (00)
            jam = jam < 10 ? "0" + jam : jam;
            menit = menit < 10 ? "0" + menit : menit;
            detik = detik < 10 ? "0" + detik : detik;

            document.getElementById("countdown-display").innerHTML = jam + ":" + menit + ":" + detik;
        }
    }, 1000);
</script>
<?= $this->endsection(); ?>