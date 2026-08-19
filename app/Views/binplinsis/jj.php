<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

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
                <div class="card w-100">
                    <div class="card-header">
                        <div class="mb-3 d-flex align-items-center">
                            <div class="mr-3">
                                <label class="mb-1 d-block">Pilih Minggu:</label>
                                <select onchange="window.location.href='?minggu='+this.value" class="form-control" style="width: 200px;">
                                    <?php for ($m = 1; $m <= 16; $m++): ?>
                                        <option value="<?= $m ?>" <?= ($minggu_aktif == $m) ? 'selected' : '' ?>>Minggu Ke-<?= $m ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="mt-4">
                                <a href="<?= base_url('admin/users/exportPdf'); ?>" class="btn btn-danger">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </a>
                            </div>
                        </div>
                        <div class="card-header p-2">

                            <ul class="nav nav-pills">
                                <?php $role = session()->get('level'); ?>

                                <?php if ($role === 'admin' || $role === 'staf'): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= (($nama_pleton_aktif ?? 'All') == 'All') ? 'active' : '' ?>"
                                            href="<?= site_url($prefix . '/binplinsis/nilaimental?pleton=All&pleton_id=All') ?>">All</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (!empty($pleton_list)): ?>
                                    <?php foreach ($pleton_list as $p): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?= ($nama_pleton_aktif == $p['nama_pleton']) ? 'active' : '' ?>"
                                                href="<?= site_url($prefix . '/binplinsis/nilaimental?pleton=' . urlencode($p['nama_pleton'])) ?>">
                                                <?= esc($p['nama_pleton']) ?>
                                                <span class="badge badge-info ml-1"><?= $counts[$p['nama_pleton']] ?? 0 ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="nav-item p-2 text-muted">Tidak ada data pleton tersedia.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <form action="<?= base_url('binplinsis/simpanNilaiMental') ?>" method="post" id="formNilai">
                            <?= csrf_field() ?>
                            <input type="hidden" name="minggu_ke" value="<?= $minggu_aktif ?>">

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center text-nowrap" style="font-size: 12px;" id="tableSiswa">
                                    <thead class="bg-success">
                                        <tr>
                                            <th rowspan="2" style="vertical-align: middle;">NO</th>
                                            <th rowspan="2" style="vertical-align: middle;">NOSIS</th>
                                            <th colspan="3">SPIRITUAL</th>
                                            <th colspan="3">IDEOLOGI</th>
                                            <th colspan="4">KEJUANGAN</th>
                                            <th colspan="4">WATAK</th>
                                            <th colspan="8">KEPEMIMPINAN</th>
                                            <th rowspan="2" style="vertical-align: middle;">JML SKOR</th>
                                            <th rowspan="2" style="vertical-align: middle;">JML HSL PENGAMATAN</th>
                                            <th rowspan="2" style="vertical-align: middle;">NILAI KONVERSI</th>
                                            <th colspan="2">TIND DILUAR INDIKATOR</th>
                                            <th rowspan="2" style="vertical-align: middle;">NILAI AKHIR</th>
                                            <th rowspan="2" style="vertical-align: middle;">AKSI</th>
                                        </tr>
                                        <tr>
                                            <?php for ($i = 1; $i <= 22; $i++): ?>
                                                <th><?= ($i <= 3) ? $i : (($i <= 6) ? $i - 3 : (($i <= 10) ? $i - 6 : (($i <= 14) ? $i - 10 : $i - 14))) ?></th>
                                            <?php endfor; ?>
                                            <th>-</th>
                                            <th>+</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($siswa as $index => $s): ?>
                                            <?php
                                            $n = $map_nilai[$s['id']] ?? null;
                                            $status = $n['status'] ?? 'draft';
                                            $role = session()->get('role_id');

                                            $isReadonlyInitially = ($n !== null);

                                            $jsonToArray = function ($jsonString) {
                                                if (empty($jsonString)) return [];
                                                $res = json_decode($jsonString, true);
                                                return is_array($res) ? array_values($res) : [];
                                            };

                                            $spiritual    = $jsonToArray($n['skor_spiritual'] ?? '[]');
                                            $ideologi     = $jsonToArray($n['skor_ideologi'] ?? '[]');
                                            $kejuangan    = $jsonToArray($n['skor_kejuangan'] ?? '[]');
                                            $watak        = $jsonToArray($n['skor_watak'] ?? '[]');
                                            $kepemimpinan = $jsonToArray($n['skor_kepemimpinan'] ?? '[]');

                                            $skor_all = array_merge($spiritual, $ideologi, $kejuangan, $watak, $kepemimpinan);
                                            ?>
                                            <tr id="row_<?= $s['id'] ?>" data-angkatan="<?= $s['angkatan_id'] ?>" data-pleton="<?= esc($s['nama_pleton'] ?? '') ?>" class="siswa-row">
                                                <td class="nomor-urut"><?= $index + 1 ?></td>
                                                <td><?= $s['nosis'] ?></td>

                                                <!-- 22 Indikator Nilai -->
                                                <?php for ($i = 0; $i < 22; $i++): ?>
                                                    <td>
                                                        <input type="number"
                                                            class="form-control form-control-sm nilai-input-<?= $s['id'] ?>"
                                                            value="<?= $skor_all[$i] ?? 0 ?>"
                                                            min="0" max="5"
                                                            oninput="updateNilai(this, <?= $s['id'] ?>)"
                                                            style="width: 50px;"
                                                            <?= $isReadonlyInitially ? 'readonly' : '' ?>>
                                                    </td>
                                                <?php endfor; ?>

                                                <td><b id="jml_<?= $s['id'] ?>"><?= $n['jml_skor'] ?? 0 ?></b></td>
                                                <td><b id="pengamatan_<?= $s['id'] ?>"><?= $n['jml_hsl_pengamatan'] ?? 0 ?></b></td>
                                                <td><b id="konversi_<?= $s['id'] ?>"><?= $n['nilai_konversi'] ?? 0 ?></b></td>

                                                <td>
                                                    <input type="number" id="minus_<?= $s['id'] ?>"
                                                        value="<?= $n['tind_diluar_minus'] ?? 0 ?>"
                                                        class="form-control form-control-sm"
                                                        oninput="hitungTotal(<?= $s['id'] ?>)" style="width: 50px;"
                                                        <?= $isReadonlyInitially ? 'readonly' : '' ?>>
                                                </td>
                                                <td>
                                                    <input type="number" id="plus_<?= $s['id'] ?>"
                                                        value="<?= $n['tind_diluar_plus'] ?? 0 ?>"
                                                        class="form-control form-control-sm"
                                                        oninput="hitungTotal(<?= $s['id'] ?>)" style="width: 50px;"
                                                        <?= $isReadonlyInitially ? 'readonly' : '' ?>>
                                                </td>
                                                <td>
                                                    <b id="akhir_<?= $s['id'] ?>"><?= $n['nilai_akhir'] ?? 0 ?></b>
                                                </td>

                                                <td data-status-n="<?= ($n !== null) ? 'locked' : 'open' ?>">
                                                    <?php if ($role == 4): ?>
                                                        <?php if ($n !== null): ?>
                                                            <button type="button" id="btn_edit_<?= $s['id'] ?>" class="btn btn-xs btn-warning" onclick="bukaEdit(<?= $s['id'] ?>)">Edit</button>
                                                            <button type="button" id="btn_simpan_<?= $s['id'] ?>" class="btn btn-xs btn-primary" style="display:none" onclick="updateData(<?= $s['id'] ?>)">Simpan</button>
                                                        <?php else: ?>
                                                            <button type="button" id="btn_simpan_<?= $s['id'] ?>" class="btn btn-xs btn-primary" onclick="simpanData(<?= $s['id'] ?>, 'simpan')" disabled>Simpan</button>
                                                        <?php endif; ?>

                                                        <?php if ($status == 'ditolak_danki' || $status == 'ditolak_danyon'): ?>
                                                            <span class="text-danger font-weight-bold d-block">Ditolak</span>
                                                        <?php endif; ?>

                                                    <?php elseif ($role == 5 && $status == 'menunggu_danki'): ?>
                                                        <button type="button" class="btn btn-xs btn-success" onclick="simpanData(<?= $s['id'] ?>, 'approve_danki')">Approve</button>
                                                        <button type="button" class="btn btn-xs btn-danger" onclick="simpanData(<?= $s['id'] ?>, 'tolak_danki')">Tolak</button>

                                                    <?php elseif ($role == 6 && $status == 'menunggu_danyon'): ?>
                                                        <button type="button" class="btn btn-xs btn-success" onclick="simpanData(<?= $s['id'] ?>, 'approve_danyon')">Approve</button>
                                                        <button type="button" class="btn btn-xs btn-danger" onclick="simpanData(<?= $s['id'] ?>, 'tolak_danyon')">Tolak</button>

                                                    <?php else: ?>
                                                        <span class="badge badge-<?= ($status == 'approved') ? 'success' : 'secondary' ?>"><?= $status ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-5 mb-4 d-flex justify-content-around text-center">
                                <!-- BOX DANTON -->
                                <div class="ttd-box">
                                    <p class="mb-0 font-weight-bold">Komandan Peleton</p>
                                    <div id="container-ttd-danton" style="height: 100px;" class="d-flex align-items-center justify-content-center">
                                        <?php
                                        $status_danton = $status_approval['status_danton'] ?? '0';
                                        if ($status_danton === '1' && !empty($danton['ttd'])): ?>
                                            <img src="<?= base_url('assets/dist/img/ttd/' . $danton['ttd']) ?>" alt="TTD Danton" style="max-height: 80px; width:200px">
                                        <?php else: ?>
                                            <span class="text-muted small-text italic">Belum dikirim</span>
                                        <?php endif; ?>
                                    </div>
                                    <p id="nama-danton-text" class="mb-0 border-top font-weight-bold"><?= $danton['nama'] ?? '( Nama Danton )' ?></p>
                                    <small class="text-muted">NRP. <span id="nrp-danton-text"><?= $danton['nomor_induk'] ?? '---------' ?></span></small>
                                </div>

                                <!-- BOX DANKI -->
                                <div class="ttd-box">
                                    <p class="mb-0 font-weight-bold">Komandan Kompi</p>
                                    <div id="container-ttd-danki" style="height: 100px;" class="d-flex align-items-center justify-content-center">
                                        <?php
                                        $status_danki = $status_approval['status_danki'] ?? '0';
                                        if ($status_danki === '1' && !empty($danki['ttd'])): ?>
                                            <img src="<?= base_url('assets/dist/img/' . $danki['ttd']) ?>" alt="TTD Danki" style="max-height: 80px;">
                                        <?php else: ?>
                                            <span class="text-muted small-text italic">Belum disetujui</span>
                                        <?php endif; ?>
                                    </div>
                                    <p id="nama-danki-text" class="mb-0 border-top font-weight-bold"><?= $danki['nama'] ?? '( Nama Danki )' ?></p>
                                    <small class="text-muted">NRP. <span id="nrp-danki-text"><?= $danki['nomor_induk'] ?? '---------' ?></span></small>
                                </div>

                                <!-- BOX DANYON -->
                                <div class="ttd-box">
                                    <p class="mb-0 font-weight-bold">Komandan Batalyon</p>
                                    <div id="container-ttd-danyon" style="height: 100px;" class="d-flex align-items-center justify-content-center">
                                        <?php
                                        $status_danyon = $status_approval['status_danyon'] ?? '0';
                                        if ($status_danyon === '1' && !empty($danyon['ttd'])): ?>
                                            <img src="<?= base_url('assets/dist/img/' . $danyon['ttd']) ?>" alt="TTD Danyon" style="max-height: 80px;">
                                        <?php else: ?>
                                            <span class="text-muted small-text italic">Belum diverifikasi</span>
                                        <?php endif; ?>
                                    </div>
                                    <p id="nama-danyon-text" class="mb-0 border-top font-weight-bold"><?= $danyon['nama'] ?? '( Nama Danyon )' ?></p>
                                    <small class="text-muted">NRP. <span id="nrp-danyon-text"><?= $danyon['nomor_induk'] ?? '---------' ?></span></small>
                                </div>
                            </div>
                        </form>
                        <div class="d-flex justify-content-center mt-3">
                            <?= $pager->links('default', 'bootstrap_full') ?>
                        </div>
                        <div class="mb-2">
                            <?php
                            $level = session()->get('level');
                            ?>

                            <?php if ($level === 'danton'): ?>
                                <button type="button" class="btn btn-success" onclick="kirimPletonKeDanki('<?= $nama_pleton_aktif ?>')">
                                    <i class="fa fa-paper-plane"></i> Kirim Semua Nilai Pleton ke Danki
                                </button>

                            <?php elseif ($level === 'danki'): ?>
                                <button type="button" class="btn btn-primary" onclick="kirimKeDanyon('<?= $nama_pleton_aktif ?>')">
                                    <i class="fa fa-paper-plane"></i> Kirim Semua Nilai Pleton ke Danyon
                                </button>

                                <?php /* Jika role siswa, tidak ada tombol yang ditampilkan (kosong) */ ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    // ==========================================
    // FUNGSI FILTERING PLETON (VERSI KUAT & TOLERAN STRING)
    // ==========================================
    function filterPleton(namaPleton, element) {
        // 1. Atur class active pada tombol menu tab
        document.querySelectorAll('.btn-pleton-filter').forEach(btn => {
            btn.classList.remove('active');
        });

        if (element) {
            element.classList.add('active');
        }

        // PEMBERSIHAN TOTAL: Ubah ke huruf kecil dan hapus spasi kosong di depan/belakang
        let searchPleton = namaPleton ? namaPleton.toString().trim().toLowerCase() : "";

        // 2. Saring baris tabel siswa
        let rows = document.querySelectorAll('.siswa-row');
        let counter = 1;

        rows.forEach(row => {
            let pletonRow = row.getAttribute('data-pleton');
            // Pembersihan string data dari baris tabel siswa
            let checkPleton = pletonRow ? pletonRow.toString().trim().toLowerCase() : "";

            // JIKA memilih tombol 'All' ATAU string nama peleton cocok setelah dibersihkan
            if (searchPleton === "" || checkPleton === searchPleton) {
                row.style.setProperty('display', '', 'important');
                let noCell = row.querySelector('.nomor-urut');
                if (noCell) noCell.innerText = counter++;
            } else {
                row.style.setProperty('display', 'none', 'important');
            }
        });

        // 3. SEMATKAN DATA STRUKTUR PEJABAT SECARA DINAMIS
        if (element) {
            let dantonNama = element.getAttribute('data-danton-nama') || '( Nama Danton )';
            let dantonNrp = element.getAttribute('data-danton-nrp') || '---------';
            let dantonTtd = element.getAttribute('data-danton-ttd');

            let dankiNama = element.getAttribute('data-danki-nama') || '( Nama Danki )';
            let dankiNrp = element.getAttribute('data-danki-nrp') || '---------';
            let dankiTtd = element.getAttribute('data-danki-ttd');

            let danyonNama = element.getAttribute('data-danyon-nama') || '( Nama Danyon )';
            let danyonNrp = element.getAttribute('data-danyon-nrp') || '---------';
            let danyonTtd = element.getAttribute('data-danyon-ttd');

            // Update teks nama & nrp di element tujuan
            if (document.getElementById('nama-danton-text')) document.getElementById('nama-danton-text').innerText = dantonNama;
            if (document.getElementById('nrp-danton-text')) document.getElementById('nrp-danton-text').innerText = dantonNrp;

            if (document.getElementById('nama-danki-text')) document.getElementById('nama-danki-text').innerText = dankiNama;
            if (document.getElementById('nrp-danki-text')) document.getElementById('nrp-danki-text').innerText = dankiNrp;

            if (document.getElementById('nama-danyon-text')) document.getElementById('nama-danyon-text').innerText = danyonNama;
            if (document.getElementById('nrp-danyon-text')) document.getElementById('nrp-danyon-text').innerText = danyonNrp;

            let baseUrl = "<?= base_url(); ?>";

            // Update TTD Danton
            let containerDanton = document.getElementById('container-ttd-danton');
            if (containerDanton) {
                if (dantonTtd && dantonTtd.trim() !== '') {
                    containerDanton.innerHTML = `<img src="${baseUrl}/assets/dist/img/ttd/${dantonTtd}" alt="TTD Danton" style="max-height: 80px; width:200px">`;
                } else {
                    containerDanton.innerHTML = `<span class="text-muted small-text italic">Belum dikirim</span>`;
                }
            }

            // Update TTD Danki
            let containerDanki = document.getElementById('container-ttd-danki');
            if (containerDanki) {
                if (dankiTtd && dankiTtd.trim() !== '') {
                    containerDanki.innerHTML = `<img src="${baseUrl}/assets/dist/img/${dankiTtd}" alt="TTD Danki" style="max-height: 80px;">`;
                } else {
                    containerDanki.innerHTML = `<span class="text-muted small-text italic">Belum disetujui</span>`;
                }
            }

            // Update TTD Danyon
            let containerDanyon = document.getElementById('container-ttd-danyon');
            if (containerDanyon) {
                if (danyonTtd && danyonTtd.trim() !== '') {
                    containerDanyon.innerHTML = `<img src="${baseUrl}/assets/dist/img/${danyonTtd}" alt="TTD Danyon" style="max-height: 80px;">`;
                } else {
                    containerDanyon.innerHTML = `<span class="text-muted small-text italic">Belum diverifikasi</span>`;
                }
            }
        }
    }

    // Jalankan otomatis pertama kali saat DOM siap
    document.addEventListener('DOMContentLoaded', function() {
        let activeTab = document.querySelector('.btn-pleton-filter.active');
        if (activeTab) {
            // Ambil string mentah dari data-pleton tombol yang aktif
            let namaPletonAktif = activeTab.getAttribute('data-pleton') || '';
            filterPleton(namaPletonAktif, activeTab);
        }
    });

    // Jalankan filter otomatis pertama kali saat halaman selesai dimuat secara menyeluruh
    document.addEventListener('DOMContentLoaded', function() {
        // Cari tab peleton yang memiliki class 'active' bawaan dari server side
        let activeTab = document.querySelector('.btn-pleton-filter.active');
        if (activeTab) {
            // Ambil langsung nilai dari atribut data-pleton untuk menghindari bug regex string onclick
            let namaPletonAktif = activeTab.getAttribute('data-pleton') || '';
            filterPleton(namaPletonAktif, activeTab);
        }
    });

    // ==========================================
    // 1. MANAJEMEN INPUT & KALKULASI NILAI
    // ==========================================
    function updateNilai(inputElement, siswaId) {
        let val = inputElement.value;
        if (val !== '') {
            let numericVal = parseFloat(val);
            if (numericVal > 5) {
                inputElement.value = 5;
            } else if (numericVal < 0) {
                inputElement.value = 0;
            }
        }
        hitungTotal(siswaId);
    }

    function hitungTotal(siswaId) {
        let total = 0;
        let adaAngkaLebihDariNol = false;
        let inputs = document.querySelectorAll('.nilai-input-' + siswaId);

        inputs.forEach(function(input) {
            let val = parseFloat(input.value) || 0;
            total += val;
            if (val > 0) adaAngkaLebihDariNol = true;
        });

        let jmlSkor = document.getElementById('jml_' + siswaId);
        if (jmlSkor) jmlSkor.innerText = total;

        let pengamatan = total / 22;
        let pengamatanEl = document.getElementById('pengamatan_' + siswaId);
        if (pengamatanEl) pengamatanEl.innerText = pengamatan.toFixed(1);

        let konversi = (pengamatan * 5) + 55;
        let konversiEl = document.getElementById('konversi_' + siswaId);
        if (konversiEl) konversiEl.innerText = konversi.toFixed(2);

        let minus = parseFloat(document.getElementById('minus_' + siswaId)?.value) || 0;
        let plus = parseFloat(document.getElementById('plus_' + siswaId)?.value) || 0;

        let akhir = konversi - minus + plus;
        let akhirEl = document.getElementById('akhir_' + siswaId);
        if (akhirEl) akhirEl.innerText = akhir.toFixed(2);

        let btnSimpan = document.getElementById('btn_simpan_' + siswaId);
        if (btnSimpan) {
            btnSimpan.disabled = !adaAngkaLebihDariNol;
        }
    }

    // ==========================================
    // 2. AKSI EDIT & UBAH DATA (INTERFACE)
    // ==========================================
    function bukaEdit(siswaId) {
        let inputs = document.querySelectorAll('.nilai-input-' + siswaId);
        inputs.forEach(input => {
            input.readOnly = false;
        });

        let tMinus = document.getElementById('minus_' + siswaId);
        let tPlus = document.getElementById('plus_' + siswaId);
        if (tMinus) tMinus.readOnly = false;
        if (tPlus) tPlus.readOnly = false;

        let btnEdit = document.getElementById('btn_edit_' + siswaId);
        let btnSimpan = document.getElementById('btn_simpan_' + siswaId);
        if (btnEdit) btnEdit.style.display = 'none';
        if (btnSimpan) btnSimpan.style.display = 'inline-block';
    }

    function updateData(siswaId) {
        simpanData(siswaId, 'update');
    }

    // ==========================================
    // 3. AJAX CORE SUBMIT DATA (FETCH API)
    // ==========================================
    function simpanData(siswaId, aksiType = 'simpan') {
        let btnSimpan = document.getElementById('btn_simpan_' + siswaId);
        let csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[type="hidden"]');
        let csrfName = csrfInput ? csrfInput.name : 'csrf_test_name';
        let csrfValue = csrfInput ? csrfInput.value : '';

        let inputs = document.querySelectorAll('.nilai-input-' + siswaId);
        let tMinus = document.getElementById('minus_' + siswaId);
        let tPlus = document.getElementById('plus_' + siswaId);

        let dataNilai = {};
        inputs.forEach((input, index) => {
            dataNilai[index] = input.value;
        });

        let statusDanton = '1';
        if (aksiType === 'approve_danki') statusDanton = '2';
        if (aksiType === 'tolak_danki') statusDanton = '3';
        if (aksiType === 'approve_danyon') statusDanton = '4';
        if (aksiType === 'tolak_danyon') statusDanton = '5';

        let jmlSkor = document.getElementById('jml_' + siswaId)?.innerText || '0';
        let jmlHslPengamatan = document.getElementById('pengamatan_' + siswaId)?.innerText || '0';
        let nilaiKonversi = document.getElementById('konversi_' + siswaId)?.innerText || '0';
        let nilaiAkhirFix = document.getElementById('akhir_' + siswaId)?.innerText || '0';

        let payload = {
            [csrfName]: csrfValue,
            siswa_id: siswaId,
            minggu_ke: '<?= $minggu_aktif ?? 1 ?>',
            angkatan_id: document.getElementById('row_' + siswaId)?.dataset.angkatan || '',
            nilai: dataNilai,
            tind_minus: tMinus?.value || 0,
            tind_plus: tPlus?.value || 0,
            jml_skor: jmlSkor,
            jml_hsl_pengamatan: jmlHslPengamatan,
            nilai_konversi: nilaiKonversi,
            nilai_akhir_fix: nilaiAkhirFix,
            status_danton: statusDanton,
            aksi_type: aksiType
        };

        if (btnSimpan) {
            btnSimpan.innerText = "Menyimpan...";
            btnSimpan.disabled = true;
        }

        fetch("<?= site_url('binplinsis/simpanNilaiMental'); ?>", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP error');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.token && csrfInput) csrfInput.value = data.token;
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message || 'Data telah disimpan.',
                        timer: 1300,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    if (btnSimpan) {
                        btnSimpan.innerText = "Simpan";
                        btnSimpan.disabled = false;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message
                    });
                }
            })
            .catch(err => {
                if (btnSimpan) {
                    btnSimpan.innerText = "Simpan";
                    btnSimpan.disabled = false;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan sistem.'
                });
            });
    }
</script>
<?= $this->endsection(); ?>