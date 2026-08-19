<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Buat Laporan Monitoring</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url($prefix . '/monitoringperiode') ?>">Daftar Periode</a></li>
                        <li class="breadcrumb-item active">Buat Laporan</li>
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
                        <h5 class="card-title mb-0" style="color: #fff;"><i class="fas fa-file-invoice"></i> Formulir Instrumen Monitoring Mingguan</h5>
                        <div class="d-flex align-items-center">
                            <span class="mr-2 font-weight-bold">Pilih Pleton:</span>
                            <select name="pleton" class="form-control form-control-sm d-inline-block w-auto font-weight-bold" required>
                                <option value="">-- Pilih Pleton --</option>
                                <?php foreach ($list_pleton as $plt): ?>
                                    <option value="<?= esc($plt['id']) ?>"><?= esc($plt['nama_pleton']) ?></option>
                                <?php endforeach; ?>
                            </select>
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

                        <div id="container-bidang">

                            <div class="card card-outline card-secondary mb-4 p-3 bidang-item" data-bidang-idx="0">
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-10">
                                        <label class="font-weight-bold text-primary">NAMA BIDANG UTAMA (INDUK) :</label>
                                        <textarea name="bidang[0]" class="form-control font-weight-bold" rows="2" placeholder="Contoh: MENTAL SPIRITUAL" required></textarea>
                                    </div>
                                    <div class="col-md-2 text-right mt-4">
                                        <button type="button" class="btn btn-danger btn-sm btn-hapus-bidang" style="display:none;">
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
                                                    <textarea name="sub_bidang[0][0]" class="form-control" rows="4" placeholder="Contoh: 1. Religius & Beriman" required></textarea>
                                                </td>
                                                <td>
                                                    <textarea name="indikator[0][0]" class="form-control" rows="4" placeholder="Masukkan indikator..." required></textarea>
                                                </td>
                                                <td>
                                                    <textarea name="giat_serdik[0][0]" class="form-control" rows="4" placeholder="Masukkan kegiatan serdik..." required></textarea>
                                                </td>
                                                <td>
                                                    <textarea name="hasil_dicapai[0][0]" class="form-control" rows="4" placeholder="Masukkan hasil..." required></textarea>
                                                </td>
                                                <td>
                                                    <textarea name="giat_pengasuh[0][0]" class="form-control" rows="4" placeholder="Masukkan giat pengasuh..." required></textarea>
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
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-primary" id="btn-tambah-bidang">
                                <i class="fas fa-folder-plus"></i> + Tambah Bidang Utama Baru
                            </button>
                        </div>

                    </div>

                    <div class="card-footer text-right">
                        <a href="<?= base_url($prefix . '/monitoringperiode') ?>" class="btn btn-secondary mr-2"><i class="fas fa-undo"></i> Batal</a>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save"></i> Simpan Laporan</button>
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

        // Counter untuk indeks Bidang Utama (Induk)
        let bidangCounter = 1;

        // Fungsi untuk memperbarui status tombol hapus Bidang Utama
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

        // Fungsi untuk memperbarui status tombol hapus Sub-Bidang di dalam suatu Bidang Utama
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

        // Event: Tambah Bidang Utama Baru
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

        // Event Delegation untuk fungsionalitas di dalam container bidang
        containerBidang.addEventListener('click', function(e) {

            // 1. Aksi Tambah Sub-Bidang Baru (pada bidang yang bersangkutan)
            if (e.target.classList.contains('btn-tambah-subbidang') || e.target.closest('.btn-tambah-subbidang')) {
                const bidangItem = e.target.closest('.bidang-item');
                const bidangIdx = bidangItem.getAttribute('data-bidang-idx');
                const tbody = bidangItem.querySelector('.tbody-subbidang');

                // Hitung index sub-bidang berdasarkan jumlah baris saat ini
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

            // 2. Aksi Hapus Sub-Bidang
            if (e.target.classList.contains('btn-hapus-subbidang') || e.target.closest('.btn-hapus-subbidang')) {
                const row = e.target.closest('.row-subbidang');
                const tbody = row.closest('.tbody-subbidang');
                row.remove();
                updateHapusSubBidangButtons(tbody);
            }

            // 3. Aksi Hapus Bidang Utama
            if (e.target.classList.contains('btn-hapus-bidang') || e.target.closest('.btn-hapus-bidang')) {
                const bidangItem = e.target.closest('.bidang-item');
                bidangItem.remove();
                updateHapusBidangButtons();
            }
        });
    });
</script>

<?= $this->endsection(); ?>