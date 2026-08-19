<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
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
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $total_siswa; ?></h3>

                            <p>SISWA</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-bag"></i>
                        </div>
                        <a href="<?= base_url('admin/siswa/nominatif'); ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $total_pegawai; ?><sup style="font-size: 20px"></sup></h3>
                            <p>POLRI</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                        <a href="<?= base_url('admin/pegawai'); ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $total_danton; ?></h3>
                            <p>DANTON</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-person-add"></i>
                        </div>
                        <!-- Tambahkan parameter ?filter=Danton di ujung URL -->
                        <a href="<?= base_url('admin/pegawai?filter=Danton'); ?>" class="small-box-footer">
                            More info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $total_danki; ?></h3>

                            <p>DANKI</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-pie-graph"></i>
                        </div>
                        <a href="<?= base_url('admin/pegawai?filter=Danki'); ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
        <div class="container-fluid">
            <div class="row">
                <!-- Kotak Kalender (Lebar 8 Kolom) -->
                <div class="col-lg-8">
                    <div class="card bg-gradient-light">
                        <div class="card-header border-0 bg-primary">
                            <h3 class="card-title">
                                <i class="far fa-calendar-alt"></i> Calendar
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-light btn-sm" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body pt-0 bg-light btn btn-outline-warning">
                            <div id="calendar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>

                <!-- Kotak Jam Digital & Animasi di Sebelah Kanan Kalender -->
                <div class="col-lg-4">
                    <div class="card shadow-sm text-center border-0">
                        <div class="card-header bg-dark text-white">
                            <h3 class="card-title w-100 m-0"><i class="fas fa-clock"></i> Waktu / Jam Server</h3>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-4 bg-light">
                            <!-- Tampilan Jam Digital Besar -->
                            <div id="digital-clock" class="fw-bold text-dark display-4" style="letter-spacing: 2px;">
                                00:00:00
                            </div>
                            <div id="digital-date" class="text-muted mt-1 fs-6 fw-semibold mb-3">
                                Memuat tanggal...
                            </div>

                            <!-- Area Animasi Polisi Hormat (Lokal File) -->
                            <div class="police-animation-container mt-2">
                                <!-- <img src="<?= base_url('public/img/polisi.gif'); ?>" alt="Polisi Hormat" class="img-fluid rounded shadow-sm" style="max-height: 120px; object-fit: contain;"> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
<style>
    /* 1. Background dan teks pada header hari FullCalendar */
    .fc-theme-bootstrap .fc-col-header-cell,
    .fc .fc-col-header-cell {
        background-color: rgb(19, 104, 232) !important;
        color: #ffffff !important;
        padding: 8px 0 !important;
    }

    .fc .fc-col-header-cell-cushion {
        color: #ffffff !important;
        text-decoration: none !important;
        display: block;
    }

    /* 2. Tombol navigasi FullCalendar (month, week, day, today, panah) menjadi hijau */
    .fc .fc-button.fc-button-primary {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
        color: #ffffff !important;
    }

    /* Efek hover dan saat tombol aktif (dipilih) */
    .fc .fc-button.fc-button-primary:hover,
    .fc .fc-button.fc-button-primary:focus,
    .fc .fc-button.fc-button-primary:active,
    .fc .fc-button.fc-button-primary.fc-button-active {
        background-color: #218838 !important;
        border-color: #1e7e34 !important;
        box-shadow: none !important;
    }

    /* 3. Styling tambahan untuk kotak jam digital di sebelah kanan */
    #digital-clock {
        font-family: 'Courier New', Courier, monospace;
        letter-spacing: 2px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('script'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Inisialisasi FullCalendar
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'id',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            themeSystem: 'bootstrap',
            initialView: 'dayGridMonth',
            editable: true,
            droppable: true,
        });
        calendar.render();

        // 2. Fungsi Jam & Tanggal Berjalan Real-Time
        function updateLiveClock() {
            const now = new Date();

            // Format Jam (HH:MM:SS)
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('digital-clock').innerText = timeString;

            // Format Tanggal (Hari, Tanggal Bulan Tahun)
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const dateString = now.toLocaleDateString('id-ID', options);
            document.getElementById('digital-date').innerText = dateString;
        }

        // Jalankan fungsi jam setiap 1 detik
        setInterval(updateLiveClock, 1000);
        updateLiveClock(); // Panggil langsung agar tidak ada jeda
    });
</script>
<?= $this->endSection(); ?>