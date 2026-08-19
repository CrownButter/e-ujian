<?= $this->extend('layout/main'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content pt-4">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-history text-primary mr-2"></i>
                        <?= esc($page_title); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tabelRiwayat" style="width:100%">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Tanggal Ujian</th>
                                    <th>Nama Kelas / Ujian</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Penguji (Gadik)</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($riwayat)): ?>
                                    <?php $no = 1;
                                    foreach ($riwayat as $item): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= !empty($item['tanggal']) ? date('d-m-Y', strtotime($item['tanggal'])) : '-'; ?></td>
                                            <td><?= esc($item['nama_kelas']); ?></td>
                                            <td><?= esc($item['nama_mapel'] ?? '-'); ?></td>
                                            <td><?= esc(($item['pangkat'] ?? '') . ' ' . ($item['nama_pegawai'] ?? '-')); ?></td>
                                            <td>
                                                <a href="<?= base_url(current_url(true)->getSegment(1) . '/obe/detail/' . $item['id']); ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
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

<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        $('#tabelRiwayat').DataTable({
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