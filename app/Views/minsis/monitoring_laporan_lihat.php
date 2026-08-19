<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Hasil Laporan Monitoring</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url(service('request')->getUri()->getSegment(1) . '/monitoringperiode') ?>">Daftar Periode</a></li>
                        <li class="breadcrumb-item active">Detail Laporan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0" style="color:#fff;"><i class="fas fa-file-alt"></i> Detail Rekapitulasi Laporan</h5>
                    <div>
                        <button onclick="window.print();" class="btn btn-sm btn-info mr-2">
                            <i class="fas fa-print"></i> Cetak Laporan
                        </button>
                        <a href="<?= base_url(service('request')->getUri()->getSegment(1) . '/monitoringperiode') ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <h5 class="font-weight-bold mb-1">LAPORAN MONITORING<br>
                            SISWA DIKTUK BINTARA POLWAN <?= esc($periode['nama_angkatan'] ?? '-') ?> TAHUN ANGGARAN <?= esc($periode['tahun_angkatan'] ?? '-') . '/' . $periode['tahun_angkatan'] + 1 ?>
                        </h5>
                        <p class="mb-0 font-weight-bold">
                            PERIODE: <?= esc($periode['minggu_ke'] ?? '-') ?> (<?= isset($periode['periode_awal']) ? date('d M Y', strtotime($periode['periode_awal'])) : '-' ?> s/d <?= isset($periode['periode_akhir']) ? date('d M Y', strtotime($periode['periode_akhir'])) : '-' ?>)
                        </p>
                    </div>

                    <?php if (empty($laporan_data)): ?>
                        <div class="alert alert-warning text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                            Belum ada laporan monitoring yang diisi untuk periode ini.
                        </div>
                    <?php else: ?>

                        <ul class="nav nav-pills mb-3 d-print-none" id="pills-tab" role="tablist">
                            <?php $isFirst = true; ?>
                            <?php foreach (array_keys($laporan_data) as $pletonName): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $isFirst ? 'active' : '' ?> btn-tab-pleton font-weight-bold mr-2"
                                        id="pill-<?= url_title($pletonName, '-', true) ?>-tab"
                                        data-toggle="pill"
                                        data-target="#pill-<?= url_title($pletonName, '-', true) ?>"
                                        data-pleton-name="<?= esc($pletonName) ?>"
                                        type="button"
                                        role="tab">
                                        <i class="fas fa-users"></i> <?= esc($pletonName) ?>
                                    </button>
                                </li>
                                <?php $isFirst = false; ?>
                            <?php endforeach; ?>
                        </ul>

                        <hr>

                        <ul class="nav nav-pills mb-3 d-print-none" id="pills-tab-action" role="tablist">
                            <li class="nav-item">
                                <a id="btn-export-pdf" href="<?= base_url($prefix . '/monitoringperiode/export_pdf?periode_id=' . ($periode['id'] ?? '')) ?>" class="btn btn-danger font-weight-bold mr-2 text-white">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </a>
                            </li>

                            <?php
                            // Mengambil prefix aktif secara dinamis dari segment URL pertama
                            $currentPrefix = service('request')->getUri()->getSegment(1);
                            ?>
                            <li class="nav-item">
                                <a href="<?= base_url($currentPrefix . '/monitoringperiode/export_word?periode_id=' . ($periode['id'] ?? '')) ?>" class="btn btn-info font-weight-bold text-white">
                                    <i class="fas fa-file-word"></i> Export MSWord
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="pills-tabContent">
                            <?php $isFirst = true; ?>
                            <?php foreach ($laporan_data as $pletonName => $bidangGroup): ?>
                                <div class="tab-pane fade <?= $isFirst ? 'show active' : '' ?> print-section-break"
                                    id="pill-<?= url_title($pletonName, '-', true) ?>"
                                    role="tabpanel">

                                    <div class="bg-secondary text-white px-3 py-2 rounded mb-3 d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 font-weight-bold"><i class="fas fa-id-card"></i> LAPORAN UNTUK: <?= esc($pletonName) ?></h5>
                                        <div>
                                            <?php if (($periode['status'] ?? 'Draft') !== 'Final'): ?>
                                                <a href="<?= base_url($currentPrefix . '/monitoringperiode/edit_laporan?periode_id=' . ($periode['id'] ?? '') . '&pleton_name=' . urlencode($pletonName)) ?>" class="btn btn-sm btn-warning font-weight-bold text-dark mr-2 d-print-none">
                                                    <i class="fas fa-edit"></i> Edit <?= esc($pletonName) ?>
                                                </a>
                                            <?php endif; ?>
                                            <span class="badge badge-light">Minggu Ke-<?= esc($periode['minggu_ke'] ?? '-') ?></span>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead class="table-dark text-center">
                                                <tr>
                                                    <th style="width: 25%;">BIDANG UTAMA & SUB-BIDANG</th>
                                                    <th style="width: 25%;">INDIKATOR PENILAIAN</th>
                                                    <th style="width: 16%;">GIAT SERDIK</th>
                                                    <th style="width: 17%;">HASIL YANG DICAPAI</th>
                                                    <th style="width: 17%;">GIAT PENGASUH</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bidangGroup as $bidangName => $subRows): ?>
                                                    <?php
                                                    // Set flag agar nama bidang utama hanya tampil sekali per grup bidang
                                                    $isBidangTampil = true;
                                                    ?>
                                                    <?php foreach ($subRows as $row): ?>
                                                        <tr>
                                                            <td class="bg-light align-middle">
                                                                <?php if ($isBidangTampil): ?>
                                                                    <div class="font-weight-bold text-primary mb-1" style="font-size: 1.05rem; text-transform: uppercase;">
                                                                        <?= nl2br(esc($row['bidang'])) ?>
                                                                    </div>
                                                                    <?php
                                                                    // Sembunyikan untuk iterasi sub-bidang selanjutnya di bawah bidang ini
                                                                    $isBidangTampil = false;
                                                                    ?>
                                                                <?php endif; ?>

                                                                <div class="text-dark font-weight-normal pl-2 border-left border-secondary" style="font-style: italic;">
                                                                    <?= nl2br(esc($row['sub_bidang'])) ?>
                                                                </div>
                                                            </td>

                                                            <td><?= nl2br(esc($row['indikator'])) ?></td>
                                                            <td><?= nl2br(esc($row['giat_serdik'])) ?></td>
                                                            <td><?= nl2br(esc($row['hasil_dicapai'])) ?></td>
                                                            <td><?= nl2br(esc($row['giat_pengasuh'])) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                                <?php $isFirst = false; ?>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>
</div>

<style>
    @media print {

        .main-sidebar,
        .main-header,
        .main-footer,
        .d-print-none,
        .breadcrumb,
        .btn {
            display: none !important;
        }

        .content-wrapper {
            margin-left: 0 !important;
            padding-top: 0 !important;
        }

        .tab-content>.tab-pane {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .print-section-break {
            page-break-after: always;
        }

        .table-responsive {
            overflow: visible !important;
        }

        table {
            page-break-inside: avoid;
        }
    }
</style>

<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnExportPdf = document.getElementById('btn-export-pdf');
        const tabButtons = document.querySelectorAll('.btn-tab-pleton');

        // Simpan base URL bawaan awal (?periode_id=xx)
        const basePdfUrl = btnExportPdf.getAttribute('href');

        // Fungsi update parameter pleton_name ke link URL PDF
        function updatePdfLink(buttonEl) {
            const pletonName = buttonEl.getAttribute('data-pleton-name');
            if (pletonName) {
                // Encode URI Component agar spasi/karakter khusus aman dikirim via URL
                btnExportPdf.setAttribute('href', basePdfUrl + '&pleton_name=' + encodeURIComponent(pletonName));
            }
        }

        // Jalankan pertama kali saat load page untuk menangkap Pleton yang aktif bawaan awal (TON A)
        const activeTab = document.querySelector('.btn-tab-pleton.active');
        if (activeTab) {
            updatePdfLink(activeTab);
        }

        // Update URL secara realtime setiap kali tombol tab Pleton diklik oleh user
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                updatePdfLink(this);
            });
        });
    });
</script>
<?= $this->endsection(); ?>