<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= $title; ?></h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active"><?= $title; ?></li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list-alt"></i> <?= $page_title; ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info"></i> Informasi!</h5>
                                Silakan pilih ujian yang tersedia di bawah ini untuk mulai mengerjakan.
                            </div>

                            <div class="table-responsive">
                                <table id="tabelUjianSiswa" class="table table-bordered table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%;">No</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Pengampu</th>
                                            <th>Jadwal</th>
                                            <th style="width: 25%;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($listUjian) && is_array($listUjian)): ?>
                                            <?php $no = 1;
                                            foreach ($listUjian as $ujian): ?>
                                                <?php
                                                // Ambil data tanggal dan jam, format string agar aman dibaca JavaScript (ganti - jadi /)
                                                $tgl = $ujian['tanggal'] ?? '';
                                                $jamMulai = $ujian['jam_mulai'] ?? '00:00:00';
                                                $jamSelesai = $ujian['jam_selesai'] ?? '00:00:00';

                                                $waktuMulaiJs = str_replace('-', '/', $tgl . ' ' . $jamMulai);
                                                $waktuSelesaiJs = str_replace('-', '/', $tgl . ' ' . $jamSelesai);
                                                ?>
                                                <tr>
                                                    <td><?= $no++; ?></td>
                                                    <td><?= esc($ujian['nama_mapel']); ?></td>
                                                    <td><?= esc(($ujian['nama_pangkat'] ?? '') . ' ' . ($ujian['nama_pegawai'] ?? '-')); ?></td>
                                                    <!-- Menampilkan Tanggal dan Jam Mulai s/d Jam Selesai -->
                                                    <td><?= esc($tgl . ' ' . $jamMulai); ?> s/d <?= esc($jamSelesai); ?></td>
                                                    <td class="text-center">
                                                        <!-- Wrapper Countdown Timer & Tombol -->
                                                        <div class="countdown-container"
                                                            data-mulai="<?= $waktuMulaiJs; ?>"
                                                            data-selesai="<?= $waktuSelesaiJs; ?>"
                                                            data-url="<?= base_url('siswa/kerjakan-ujian/' . $ujian['id']); ?>">

                                                            <span class="badge badge-warning timer-teks mb-1 d-block" style="font-size: 85%;">Memuat timer...</span>

                                                            <a href="#" class="btn btn-sm btn-primary tombol-kerjakan disabled" style="pointer-events: none; opacity: 0.6;">
                                                                <i class="fas fa-edit"></i> Kerjakan
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Belum ada ujian aktif saat ini.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<?= $this->endSection() ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        // Inisialisasi DataTable jika digunakan
        $('#tabelUjianSiswa').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Data tidak ditemukan",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                "infoEmpty": "Tidak ada data yang tersedia",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });

        // Fungsi Countdown Timer Real-time
        function updateTimers() {
            const sekarang = new Date().getTime();

            $('.countdown-container').each(function() {
                const container = $(this);
                const waktuMulai = new Date(container.attr('data-mulai')).getTime();
                const waktuSelesai = new Date(container.attr('data-selesai')).getTime();

                const timerTeks = container.find('.timer-teks');
                const tombol = container.find('.tombol-kerjakan');
                const urlUjian = container.attr('data-url');

                const selisihMulai = waktuMulai - sekarang;
                const selisihSelesai = waktuSelesai - sekarang;

                if (selisihMulai > 0) {
                    // 1. Ujian Belum Mulai (Tampilkan Hitung Mundur)
                    let jam = Math.floor((selisihMulai % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    let menit = Math.floor((selisihMulai % (1000 * 60 * 60)) / (1000 * 60));
                    let detik = Math.floor((selisihMulai % (1000 * 60)) / 1000);

                    jam = String(jam).padStart(2, '0');
                    menit = String(menit).padStart(2, '0');
                    detik = String(detik).padStart(2, '0');

                    timerTeks.text('Mulai dalam: ' + jam + ':' + menit + ':' + detik);
                    timerTeks.removeClass('badge-danger badge-success').addClass('badge-warning');

                    // Nonaktifkan Tombol
                    tombol.addClass('disabled').attr('href', '#').css({
                        'pointer-events': 'none',
                        'opacity': '0.6'
                    });
                    tombol.removeClass('btn-primary').addClass('btn-secondary');

                } else if (selisihMulai <= 0 && selisihSelesai > 0) {
                    // 2. Ujian Sedang Berlangsung
                    timerTeks.text('Ujian Sedang Berlangsung');
                    timerTeks.removeClass('badge-warning badge-danger').addClass('badge-success');

                    // Aktifkan Tombol
                    tombol.removeClass('disabled').attr('href', urlUjian).css({
                        'pointer-events': 'auto',
                        'opacity': '1'
                    });
                    tombol.removeClass('btn-secondary').addClass('btn-primary');

                } else {
                    // 3. Waktu Ujian Telah Berakhir / Habis
                    timerTeks.text('Waktu Ujian Habis');
                    timerTeks.removeClass('badge-warning badge-success').addClass('badge-danger');

                    // Nonaktifkan Tombol
                    tombol.addClass('disabled').attr('href', '#').css({
                        'pointer-events': 'none',
                        'opacity': '0.6'
                    });
                    tombol.removeClass('btn-primary').addClass('btn-secondary');
                }
            });
        }

        // Jalankan timer setiap 1 detik
        setInterval(updateTimers, 1000);
        updateTimers(); // Eksekusi pertama kali tanpa jeda
    });
</script>
<?= $this->endSection(); ?>