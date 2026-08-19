<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Laporan Monitoring</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url($prefix . '/monitoringperiode') ?>">Daftar Periode</a></li>
                        <li class="breadcrumb-item active">Edit Laporan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <form action="<?= base_url($prefix . '/monitoringperiode/simpan_laporan') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="periode_id" value="<?= esc($periode_id) ?>">

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0" style="color: #fff;"><i class="fas fa-edit"></i> Formulir Edit Instrumen Monitoring Mingguan</h5>
                        <div class="d-flex align-items-center">
                            <span class="mr-2 font-weight-bold">Pleton Terdeteksi:</span>

                            <?php
                            $selectedPleton = $current_pleton ?? $pleton_id ?? '';
                            ?>

                            <select class="form-control form-control-sm d-inline-block w-auto font-weight-bold" disabled style="background-color: #e9ecef;">
                                <option value="">-- Pilih Pleton --</option>
                                <?php foreach ($list_pleton as $plt): ?>
                                    <option value="<?= esc($plt['id']) ?>" <?= ($selectedPleton == $plt['id'] || (isset($current_pleton_name) && $current_pleton_name == $plt['nama_pleton'])) ? 'selected' : '' ?>>
                                        <?= esc($plt['nama_pleton']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input type="hidden" name="pleton" value="<?= esc($selectedPleton) ?>">
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h5 class="font-weight-bold mb-1">LAPORAN MONITORING<br>
                                SISWA DIKTUK BINTARA POLWAN <?= esc($periode['nama_angkatan'] ?? '-') ?> TAHUN ANGGARAN <?= esc($periode['tahun_angkatan'] ?? '-') . '/' . ($periode['tahun_angkatan'] + 1) ?>
                            </h5>
                            <p class="mb-0 font-weight-bold">
                                PERIODE: <?= esc($periode['minggu_ke'] ?? '-') ?> (<?= isset($periode['periode_awal']) ? date('d M Y', strtotime($periode['periode_awal'])) : '-' ?> s/d <?= isset($periode['periode_akhir']) ? date('d M Y', strtotime($periode['periode_akhir'])) : '-' ?>)
                            </p>
                        </div>

                        <div id="container-bidang">

                            <?php if (!empty($laporan_data)): ?>
                                <?php $bIdx = 0; ?>
                                <?php foreach ($laporan_data as $bidangName => $subRows): ?>
                                    <div class="card card-outline card-secondary mb-4 p-3 bidang-item" data-bidang-idx="<?= $bIdx ?>">
                                        <div class="row mb-3 align-items-center">
                                            <div class="col-md-10">
                                                <label class="font-weight-bold text-primary">NAMA BIDANG UTAMA (INDUK) :</label>
                                                <textarea name="bidang[<?= $bIdx ?>]" class="form-control font-weight-bold" rows="2" placeholder="Contoh: MENTAL SPIRITUAL" required><?= esc($bidangName) ?></textarea>
                                            </div>
                                            <div class="col-md-2 text-right mt-4">
                                                <button type="button" class="btn btn-danger btn-sm btn-hapus-bidang">
                                                    <i class="fas fa-trash"></i> Hapus Bidang
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead class="table-secondary text-center">
                                                    <tr>
                                                        <th style="width: 20%;">SUB-BIDANG / ASPEK</th>
                                                        <th style="width: 23%;">INDIKATOR PENILAIAN</th>
                                                        <th style="width: 18%;">GIAT SERDIK</th>
                                                        <th style="width: 18%;">HASIL YANG DICAPAI</th>
                                                        <th style="width: 16%;">GIAT PENGASUH</th>
                                                        <th style="width: 5%;">AKSI</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="tbody-subbidang">
                                                    <?php $sIdx = 0; ?>
                                                    <?php foreach ($subRows as $row): ?>
                                                        <tr class="row-subbidang" data-sub-idx="<?= $sIdx ?>">
                                                            <td>
                                                                <textarea name="sub_bidang[<?= $bIdx ?>][<?= $sIdx ?>]" class="form-control" rows="4" placeholder="Contoh: 1. Religius & Beriman" required><?= esc($row['sub_bidang'] ?? '') ?></textarea>
                                                            </td>
                                                            <td>
                                                                <textarea name="indikator[<?= $bIdx ?>][<?= $sIdx ?>]" class="form-control" rows="4" placeholder="Masukkan indikator..." required><?= esc($row['indikator'] ?? '') ?></textarea>
                                                            </td>
                                                            <td>
                                                                <textarea name="giat_serdik[<?= $bIdx ?>][<?= $sIdx ?>]" class="form-control" rows="4" placeholder="Masukkan kegiatan serdik..." required><?= esc($row['giat_serdik'] ?? '') ?></textarea>
                                                            </td>
                                                            <td>
                                                                <textarea name="hasil_dicapai[<?= $bIdx ?>][<?= $sIdx ?>]" class="form-control" rows="4" placeholder="Masukkan hasil..." required><?= esc($row['hasil_dicapai'] ?? '') ?></textarea>
                                                            </td>
                                                            <td>
                                                                <textarea name="giat_pengasuh[<?= $bIdx ?>][<?= $sIdx ?>]" class="form-control" rows="4" placeholder="Masukkan giat pengasuh..." required><?= esc($row['giat_pengasuh'] ?? '') ?></textarea>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-danger btn-hapus-subbidang">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php $sIdx++; ?>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="text-left mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-info btn-tambah-subbidang">
                                                <i class="fas fa-plus"></i> Tambah Sub-Bidang Baru
                                            </button>
                                        </div>
                                    </div>
                                    <?php $bIdx++; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-primary" id="btn-tambah-bidang">
                                <i class="fas fa-folder-plus"></i> + Tambah Bidang Utama Baru
                            </button>
                        </div>

                    </div>

                    <div class="card-footer text-right">
                        <a href="<?= base_url($prefix . '/monitoringperiode') ?>" class="btn btn-secondary mr-2"><i class="fas fa-undo"></i> Batal</a>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save"></i> Perbarui Laporan</button>
                    </div>
                </div>
            </form>

        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const containerBidang = document.getElementById('container-bidang');
        const btnTambahBidang = document.getElementById('btn-tambah-bidang');

        let bidangCounter = containerBidang.querySelectorAll('.bidang-item').length;

        function updateHapusBidangButtons() {
            const bidangItems = containerBidang.querySelectorAll('.bidang-item');
            bidangItems.forEach(item => {
                const btnHapus = item.querySelector('.btn-hapus-bidang');
                if (bidangItems.length === 1) {
                    btnHapus.style.display = 'none';
                } else {
                    btnHapus.style.display = 'inline-block';
                }
            });
        }

        function updateHapusSubBidangButtons(tbody) {
            const rows = tbody.querySelectorAll('.row-subbidang');
            rows.forEach(row => {
                const btnHapusSub = row.querySelector('.btn-hapus-subbidang');
                if (rows.length === 1) {
                    btnHapusSub.setAttribute('disabled', 'disabled');
                } else {
                    btnHapusSub.removeAttribute('disabled');
                }
            });
        }

        updateHapusBidangButtons();
        containerBidang.querySelectorAll('.tbody-subbidang').forEach(tbody => {
            updateHapusSubBidangButtons(tbody);
        });

        btnTambahBidang.addEventListener('click', function() {
            const newBidang = document.createElement('div');
            newBidang.className = 'card card-outline card-secondary mb-4 p-3 bidang-item';
            newBidang.setAttribute('data-bidang-idx', bidangCounter);

            newBidang.innerHTML = `
            <div class="row mb-3 align-items-center">
                <div class="col-md-10">
                    <label class="font-weight-bold text-primary">NAMA BIDANG UTAMA (INDUK) :</label>
                    <textarea name="bidang[${bidangCounter}]" class="form-control font-weight-bold" rows="2" placeholder="Contoh: MENTAL SPIRITUAL" required></textarea>
                </div>
                <div class="col-md-2 text-right mt-4">
                    <button type="button" class="btn btn-danger btn-sm btn-hapus-bidang">
                        <i class="fas fa-trash"></i> Hapus Bidang
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-secondary text-center">
                        <tr>
                            <th style="width: 20%;">SUB-BIDANG / ASPEK</th>
                            <th style="width: 23%;">INDIKATOR PENILAIAN</th>
                            <th style="width: 18%;">GIAT SERDIK</th>
                            <th style="width: 18%;">HASIL YANG DICAPAI</th>
                            <th style="width: 16%;">GIAT PENGASUH</th>
                            <th style="width: 5%;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="tbody-subbidang">
                        <tr class="row-subbidang" data-sub-idx="0">
                            <td>
                                <textarea name="sub_bidang[${bidangCounter}][0]" class="form-control" rows="4" placeholder="Contoh: 1. Religius & Beriman" required></textarea>
                            </td>
                            <td>
                                <textarea name="indikator[${bidangCounter}][0]" class="form-control" rows="4" placeholder="Masukkan indikator..." required></textarea>
                            </td>
                            <td>
                                <textarea name="giat_serdik[${bidangCounter}][0]" class="form-control" rows="4" placeholder="Masukkan kegiatan serdik..." required></textarea>
                            </td>
                            <td>
                                <textarea name="hasil_dicapai[${bidangCounter}][0]" class="form-control" rows="4" placeholder="Masukkan hasil..." required></textarea>
                            </td>
                            <td>
                                <textarea name="giat_pengasuh[${bidangCounter}][0]" class="form-control" rows="4" placeholder="Masukkan giat pengasuh..." required></textarea>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger btn-hapus-subbidang" disabled>
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-left mt-2">
                <button type="button" class="btn btn-sm btn-outline-info btn-tambah-subbidang">
                    <i class="fas fa-plus"></i> Tambah Sub-Bidang Baru
                </button>
            </div>
        `;

            containerBidang.appendChild(newBidang);
            bidangCounter++;
            updateHapusBidangButtons();
        });

        containerBidang.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-tambah-subbidang') || e.target.closest('.btn-tambah-subbidang')) {
                const bidangItem = e.target.closest('.bidang-item');
                const bidangIdx = bidangItem.getAttribute('data-bidang-idx');
                const tbody = bidangItem.querySelector('.tbody-subbidang');

                const currentSubRows = tbody.querySelectorAll('.row-subbidang');
                const nextSubIdx = currentSubRows.length;

                const newSubRow = document.createElement('tr');
                newSubRow.className = 'row-subbidang';
                newSubRow.setAttribute('data-sub-idx', nextSubIdx);
                newSubRow.innerHTML = `
                <td>
                    <textarea name="sub_bidang[${bidangIdx}][${nextSubIdx}]" class="form-control" rows="4" placeholder="Contoh: 2. Jujur & Ikhlas" required></textarea>
                </td>
                <td>
                    <textarea name="indikator[${bidangIdx}][${nextSubIdx}]" class="form-control" rows="4" placeholder="Masukkan indikator..." required></textarea>
                </td>
                <td>
                    <textarea name="giat_serdik[${bidangIdx}][${nextSubIdx}]" class="form-control" rows="4" placeholder="Masukkan kegiatan serdik..." required></textarea>
                </td>
                <td>
                    <textarea name="hasil_dicapai[${bidangIdx}][${nextSubIdx}]" class="form-control" rows="4" placeholder="Masukkan hasil..." required></textarea>
                </td>
                <td>
                    <textarea name="giat_pengasuh[${bidangIdx}][${nextSubIdx}]" class="form-control" rows="4" placeholder="Masukkan giat pengasuh..." required></textarea>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger btn-hapus-subbidang">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;

                tbody.appendChild(newSubRow);
                updateHapusSubBidangButtons(tbody);
            }

            if (e.target.classList.contains('btn-hapus-subbidang') || e.target.closest('.btn-hapus-subbidang')) {
                const row = e.target.closest('.row-subbidang');
                const tbody = row.closest('.tbody-subbidang');
                row.remove();
                updateHapusSubBidangButtons(tbody);
            }

            if (e.target.classList.contains('btn-hapus-bidang') || e.target.closest('.btn-hapus-bidang')) {
                const bidangItem = e.target.closest('.bidang-item');
                bidangItem.remove();
                updateHapusBidangButtons();
            }
        });
    });
</script>

<?= $this->endsection(); ?>