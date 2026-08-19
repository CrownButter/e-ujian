<?= $this->extend('layout/main'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold">
                        <i class="fas fa-list-alt text-primary mr-2"></i> <?= esc($page_title ?? 'Pilih Kelas Ujian'); ?>
                    </h1>
                </div>
                <div class="col-sm-6 text-right">
                    <ol class="breadcrumb float-sm-right bg-transparent p-0 m-0">
                        <li class="breadcrumb-item"><a href="<?= base_url($role_prefix . '/dashboard'); ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Penilaian Ujian</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold pt-1">Daftar Kelas Ujian yang Tersedia</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered align-middle" id="tableKelasUjian" style="width:100%">
                            <thead class="thead-dark text-center">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama Kelas</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Judul Ujian</th>
                                    <th>Waktu Ujian</th>
                                    <th>Gadik Pengampu</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($kelas_ujian)): ?>
                                    <?php $no = 1;
                                    foreach ($kelas_ujian as $k): ?>
                                        <tr>
                                            <td class="text-center font-weight-bold"><?= $no++; ?></td>
                                            <td class="text-center font-weight-bold text-info"><?= esc($k['nama_kelas']); ?></td>
                                            <td><?= esc($k['nama_mapel']); ?></td>
                                            <td><?= esc($k['judul'] ?? 'Ujian OBE'); ?></td>
                                            <td class="text-center">
                                                <small class="d-block"><i class="far fa-calendar-alt mr-1"></i> <?= date('d-m-Y', strtotime($k['tanggal'])); ?></small>
                                                <small class="text-muted"><i class="far fa-clock mr-1"></i> <?= $k['jam_mulai'] . ' - ' . $k['jam_selesai']; ?></small>
                                            </td>
                                            <td>
                                                <?= esc(($k['pangkat'] ?? '') . ' ' . ($k['nama_gadik'] ?? $k['nama_pegawai'] ?? '-')); ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url($role_prefix . '/obe/penilaian/kelas/' . $k['id']); ?>" class="btn btn-sm btn-success shadow-sm">
                                                    <i class="fas fa-clipboard-check mr-1"></i> Nilai Kelas
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada data kelas ujian yang tersedia.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection(); ?>

<!-- Tambahkan script untuk mengaktifkan DataTable karena CDN sudah ada di file main.php / layout utama -->
<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        $('#tableKelasUjian').DataTable({
            "responsive": true,
            "autoWidth": false,
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Data tidak ditemukan",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                "infoEmpty": "Tidak ada data yang tersedia",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    });
</script>
<?= $this->endSection(); ?>