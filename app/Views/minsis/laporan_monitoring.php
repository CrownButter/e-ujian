<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="card mb-4 shadow-sm no-print">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-filter"></i> Filter Laporan Monitoring</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?= site_url('monitoringlaporan') ?>" class="row g-3">
                        <div class="col-md-5">
                            <label for="periode_id" class="form-label fw-bold">Periode Minggu</label>
                            <select name="periode_id" id="periode_id" class="form-select select2">
                                <?php foreach ($list_periode as $per): ?>
                                    <option value="<?= $per['id'] ?>" <?= ($per['id'] == $periode_id_aktif) ? 'selected' : '' ?>>
                                        <?= esc($per['nama_angkatan']) ?> - Minggu ke-<?= esc($per['minggu_ke']) ?> (<?= date('d M Y', strtotime($per['periode_awal'])) ?> s/d <?= date('d M Y', strtotime($per['periode_akhir'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pleton_id" class="form-label fw-bold">Pleton</label>
                            <select name="pleton_id" id="pleton_id" class="form-select">
                                <option value="All" <?= ($pleton_id_aktif == 'All') ? 'selected' : '' ?>>Semua Pleton</option>
                                <?php foreach ($list_pleton as $plt): ?>
                                    <option value="<?= $plt['id'] ?>" <?= ($plt['id'] == $pleton_id_aktif) ? 'selected' : '' ?>>
                                        <?= esc($plt['nama_pleton']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 me-2"><i class="fas fa-search"></i> Tampilkan</button>
                            <button type="button" onclick="window.print()" class="btn btn-success"><i class="fas fa-print"></i> Cetak</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm print-area">
                <div class="card-body p-4">

                    <div class="text-center mb-4">
                        <h4 class="text-uppercase fw-bold mb-1">
                            LAPORAN MONITORING <br>
                            SISWA DIKTUK BINTARA POLWAN <?= !empty($periode_aktif) ? esc($periode_aktif['nama_angkatan']) : 'ANGKATAN -' ?> TAHUN ANGGARAN <?= !empty($periode_aktif) ? esc($periode_aktif['tahun_angkatan']) : '-' ?>
                        </h4>
                        <?php if (!empty($periode_aktif)): ?>
                            <p class="text-muted mb-0">
                                MINGGU KE-<?= esc($periode_aktif['minggu_ke']) ?>
                                (PERIODE: <?= date('d F Y', strtotime($periode_aktif['periode_awal'])) ?> s/d <?= date('d F Y', strtotime($periode_aktif['periode_akhir'])) ?>)
                            </p>
                        <?php endif; ?>
                        <hr class="my-3 border-2 border-dark">
                    </div>

                    <?php if (empty($siswa_list)): ?>
                        <div class="alert alert-warning text-center">
                            Tidak ada data siswa atau hasil penilaian untuk filter yang dipilih.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle" style="font-size: 11px;">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th rowspan="2" style="width: 40px; vertical-align: middle;">No</th>
                                        <th rowspan="2" style="width: 150px; vertical-align: middle;">Nama Siswa</th>
                                        <th rowspan="2" style="width: 80px; vertical-align: middle;">No. Akad</th>

                                        <?php foreach ($bidang_dinamis as $bidang): ?>
                                            <th colspan="<?= $bidang['count'] ?>" class="text-uppercase">
                                                <?= esc($bidang['nama_bidang']) ?> (<?= esc($bidang['kode_bidang']) ?>)
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr>
                                        <?php foreach ($bidang_dinamis as $bidang): ?>
                                            <?php foreach ($bidang['indikator'] as $ind): ?>
                                                <th style="width: 60px;" title="<?= esc($ind['judul']) ?>: <?= esc($ind['indikator']) ?>">
                                                    <?= esc($ind['nomor']) ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php foreach ($siswa_list as $siswa): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td class="fw-bold"><?= esc($siswa['nama']) ?></td>
                                            <td class="text-center"><?= esc($siswa['no_akademik'] ?? '-') ?></td>

                                            <?php foreach ($bidang_dinamis as $bidang): ?>
                                                <?php foreach ($bidang['indikator'] as $ind): ?>
                                                    <?php
                                                    $siswaId = $siswa['id'];
                                                    $indId = $ind['id'];
                                                    $cell = $matriks_hasil[$siswaId][$indId] ?? null;

                                                    $bgColor = '';
                                                    $textValue = '-';

                                                    if ($cell) {
                                                        $textValue = $cell['status'];
                                                        if ($cell['status'] == 'Baik') {
                                                            $bgColor = 'bg-success text-white';
                                                        } elseif ($cell['status'] == 'Cukup') {
                                                            $bgColor = 'bg-warning text-dark';
                                                        } elseif ($cell['status'] == 'Kurang') {
                                                            $bgColor = 'bg-danger text-white';
                                                        }
                                                    }
                                                    ?>
                                                    <td class="text-center <?= $bgColor ?>"
                                                        title="<?= $cell ? 'Hasil: ' . esc($cell['hasil']) . "\nCatatan: " . esc($cell['catatan']) : 'Belum diisi' ?>">
                                                        <strong><?= esc($textValue) ?></strong>
                                                    </td>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4" style="font-size: 10px;">
                            <div class="col-12">
                                <h6 class="fw-bold mb-1">Keterangan Indikator:</h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach ($bidang_dinamis as $bidang): ?>
                                        <?php foreach ($bidang['indikator'] as $ind): ?>
                                            <span><strong><?= esc($ind['nomor']) ?></strong>: <?= esc($ind['judul']) ?></span>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5 pt-3" style="font-size: 12px;">
                            <div class="col-4 text-center">
                                <p class="mb-0">Mengetahui,</p>
                                <p class="fw-bold mb-5">Komandan Pleton</p>
                                <span class="text-decoration-underline fw-bold d-block">
                                    <?= esc($pengesahan['nama_danton'] ?? '............................................') ?>
                                </span>
                                <span>NRP/NIP: <?= esc($pengesahan['danton_id'] ?? '....................................') ?></span>
                            </div>

                            <div class="col-4 text-center">
                                <p class="mb-0">Menyetujui,</p>
                                <p class="fw-bold mb-5">Komandan Kompi (Danpi)</p>
                                <span class="text-decoration-underline fw-bold d-block">
                                    <?= esc($pengesahan['nama_danpi'] ?? '............................................') ?>
                                </span>
                                <span>NRP/NIP: <?= esc($pengesahan['danpi_id'] ?? '....................................') ?></span>
                            </div>

                            <div class="col-4 text-center">
                                <p class="mb-0">Mengesahkan,</p>
                                <p class="fw-bold mb-5">Komandan Batalyon (Danyon)</p>
                                <span class="text-decoration-underline fw-bold d-block">
                                    <?= esc($pengesahan['nama_danyon'] ?? '............................................') ?>
                                </span>
                                <span>NRP/NIP: <?= esc($pengesahan['danyon_id'] ?? '....................................') ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background-color: #fff !important;
            color: #000 !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .print-area {
            width: 100%;
            padding: 0 !important;
        }

        table {
            border-collapse: collapse !important;
            width: 100% !important;
        }

        .table-dark {
            background-color: #e9ecef !important;
            color: #000 !important;
        }

        .table-dark th {
            color: #000 !important;
            border: 1px solid #000 !important;
        }

        .bg-success {
            background-color: #d1e7dd !important;
            color: #0f5132 !important;
        }

        .bg-warning {
            background-color: #fff3cd !important;
            color: #664d03 !important;
        }

        .bg-danger {
            background-color: #f8d7da !important;
            color: #842029 !important;
        }
    }
</style>

<?= $this->endSection() ?>