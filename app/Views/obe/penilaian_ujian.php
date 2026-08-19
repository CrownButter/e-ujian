<?= $this->extend('layout/main'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content pt-4">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-info-circle text-primary mr-2"></i>
                        Daftar Peserta Ujian: <span class="text-info"><?= esc($ujian['nama_kelas']); ?></span>
                        (<?= esc($ujian['nama_mapel']); ?>)
                    </h3>
                    <div class="card-tools d-flex align-items-center justify-content-end">
                        <!-- Badge Tanggal & Jam -->
                        <?php if (!empty($ujian['tanggal'])): ?>
                            <span class="badge badge-light border px-2 py-1 mr-2">
                                <span class="text-muted">Ujian pada:</span>
                                <i class="far fa-calendar-alt text-danger mr-1 ml-1"></i>
                                <?= date('d-m-Y', strtotime($ujian['tanggal'])); ?>
                                <i class="far fa-clock text-success ml-2 mr-1"></i>
                                <?= esc($ujian['jam_mulai']); ?> - <?= esc($ujian['jam_selesai']); ?>
                            </span>
                        <?php endif; ?>

                        <!-- Tombol Kembali di sebelah kanan -->
                        <a href="<?= base_url('gadik/obe/penilaian/') ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex gap-2 align-items-center flex-wrap">
                        <?php
                        // Ambil nilai sort dari request agar bisa dipakai berulang kali
                        $currentSort = service('request')->getGet('sort') ?? 'nosis';
                        ?>

                        <!-- Export Excel -->
                        <a href="<?= base_url($role_prefix . '/obe/penilaian/exportExcel/' . $ujian['id'] . '?sort=' . $currentSort); ?>"
                            class="btn btn-success btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-excel mr-1"></i> Export Excel
                        </a>

                        <!-- Export Word -->
                        <a href="<?= base_url($role_prefix . '/obe/penilaian/exportWord/' . $ujian['id'] . '?sort=' . $currentSort); ?>"
                            class="btn btn-primary btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-word mr-1"></i> Export Word
                        </a>

                        <!-- Export PDF / Cetak -->
                        <a href="<?= base_url($role_prefix . '/obe/penilaian/exportPdf/' . $ujian['id'] . '?sort=' . $currentSort); ?>"
                            class="btn btn-danger btn-sm shadow-sm" target="_blank">
                            <i class="fas fa-file-pdf mr-1"></i> Export PDF
                        </a>

                        <!-- Pemisah -->
                        <div class="ml-auto d-flex gap-2">
                            <span class="align-self-center font-weight-bold mr-1">Urutkan:</span>
                            <a href="?sort=nosis" class="btn btn-sm <?= ($currentSort == 'nosis') ? 'btn-success' : 'btn-outline-success'; ?>">
                                Nosis
                            </a>
                            <a href="?sort=nilai" class="btn btn-sm <?= ($currentSort == 'nilai') ? 'btn-success' : 'btn-outline-success'; ?>">
                                Peringkat Nilai
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tablePeserta" style="width:100%">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nosis</th>
                                    <th>Nama Siswa</th>
                                    <th>Status / Nilai Akhir</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($peserta)): ?>
                                    <?php $no = 1;
                                    foreach ($peserta as $p): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= esc($p['nosis']); ?></td>
                                            <td><?= esc($p['nama']); ?></td>
                                            <td>
                                                <?php if (!empty($p['nilai_akhir'])): ?>
                                                    <span class="badge badge-success">Sudah Dinilai (Nilai: <?= $p['nilai_akhir']; ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Belum Dinilai / Belum Ada Nilai</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($p['nilai_akhir'])): ?>
                                                    <span class="badge badge-success p-2" style="font-size: 12px;">
                                                        <i class="fas fa-check-circle mr-1"></i> Selesai Dinilai (<?= $p['nilai_akhir'] ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <button type="button"
                                                        onclick="window.location.replace('<?= base_url($role_prefix . '/obe/penilaian/form/' . $ujian['id'] . '/' . $p['siswa_id']); ?>')"
                                                        class="btn btn-sm btn-info shadow-sm">
                                                        <i class="fas fa-search-plus mr-1"></i> Lihat Jawaban & Nilai
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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

<!-- Bagian Script -->
<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        // Inisialisasi DataTable saja tanpa mencampuri event klik link
        $('#tablePeserta').DataTable({
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
    });
</script>
<?= $this->endSection(); ?>