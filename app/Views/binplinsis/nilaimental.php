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
                        <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap">

                            <!-- Dropdown Pilih Minggu, Pilih Hari, & Tanggal Otomatis -->
                            <?php
                            // Mendapatkan tanggal aktual hari ini atau tanggal berdasarkan minggu & hari yang dipilih
                            // (Contoh sederhana menampilkan tanggal real-time hari ini)
                            $tanggal_sekarang = date('Y-m-d');
                            ?>

                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Pilihan Minggu -->
                                    <div class="col-md-3">
                                        <label>Pilih Minggu:</label>
                                        <select name="minggu" id="mingguFilter" class="form-control" onchange="filterNilai()">
                                            <?php for ($i = 1; $i <= 16; $i++): ?>
                                                <option value="<?= $i; ?>" <?= ($minggu_aktif == $i) ? 'selected' : ''; ?>>
                                                    Minggu Ke-<?= $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <!-- Pilihan Hari -->
                                    <div class="col-md-3">
                                        <label>Pilih Hari:</label>
                                        <select name="hari" id="hariFilter" class="form-control" onchange="filterNilai()">
                                            <?php
                                            $list_hari = [
                                                1 => 'Senin',
                                                2 => 'Selasa',
                                                3 => 'Rabu',
                                                4 => 'Kamis',
                                                5 => 'Jumat',
                                                6 => 'Sabtu',
                                                7 => 'Minggu'
                                            ];
                                            foreach ($list_hari as $num => $nama_h):
                                            ?>
                                                <option value="<?= $num; ?>" <?= ($hari_aktif == $num) ? 'selected' : ''; ?>>
                                                    <?= $nama_h; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Kotak Informasi Tanggal Otomatis -->
                                    <div class="col-md-5 mt-4">
                                        <div class="alert alert-info py-2 mb-0" style="background-style: none;">
                                            <i class="fa fa-calendar"></i> Tanggal:
                                            <strong><?= hari_indo($tanggal_terhitung); ?>, <?= tgl_indo($tanggal_terhitung); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Letakkan JS ini di bagian bawah view Anda -->
                            <script>
                                function filterNilai() {
                                    let minggu = document.getElementById('mingguFilter').value;
                                    let hari = document.getElementById('hariFilter').value;
                                    let pleton = "<?= $nama_pleton_aktif ?? 'All'; ?>";

                                    window.location.href = `?minggu=${minggu}&hari=${hari}&pleton=${pleton}`;
                                }
                            </script>

                            <!-- JavaScript untuk menangani perubahan pilihan dropdown -->
                            <script>
                                function filterNilai() {
                                    let minggu = document.querySelector('select[name="minggu"]').value;
                                    let hari = document.querySelector('select[name="hari"]').value;
                                    let pleton = "<?= $nama_pleton_aktif ?? 'All'; ?>";

                                    // Refresh halaman dengan membawa parameter filter yang baru
                                    window.location.href = `?minggu=${minggu}&hari=${hari}&pleton=${pleton}`;
                                }
                            </script>

                            <script>
                                function changeFilter() {
                                    let minggu = document.getElementById('selectMinggu').value;
                                    let hari = document.getElementById('selectHari').value;
                                    let urlParams = new URLSearchParams(window.location.search);
                                    urlParams.set('minggu', minggu);
                                    urlParams.set('hari', hari);
                                    window.location.href = window.location.pathname + '?' + urlParams.toString();
                                }
                            </script>

                            <!-- Tombol Export PDF -->
                            <div class="mb-2">
                                <?php
                                $roleId = session()->get('role_id');

                                $rolePrefixes = [
                                    1 => 'admin',
                                    2 => 'operator',
                                    3 => 'pengasuh',
                                    4 => 'danton',
                                    5 => 'danki',
                                    6 => 'danyon',
                                    7 => 'siswa'
                                ];

                                $prefix = $rolePrefixes[$roleId] ?? 'admin';

                                if ($roleId == 4) {
                                    $exportUrl = base_url($prefix . '/binplinsis/pleton/' . (session()->get('pleton_id') ?? 1));
                                } else {
                                    $exportUrl = base_url($prefix . '/binplinsis/role/' . $roleId);
                                }
                                ?>
                                <?php if ($roleId != 7): ?>
                                    <div class="mb-3 text-right">
                                        <a href="<?= base_url($prefix . '/binplinsis/nilaimental/exportExcel?minggu=' . $minggu_aktif . '&hari=' . $hari_aktif . '&pleton=' . $nama_pleton_aktif); ?>" class="btn btn-success">
                                            <i class="fa fa-file-excel"></i> Export Excel
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Navigasi Pleton (LENGKAP DIKEMBALIKAN) -->
                        <div class="card-header p-2 border-top">
                            <ul class="nav nav-pills">
                                <?php $role = session()->get('level'); ?>

                                <?php if ($role === 'admin' || $role === 'staf' || session()->get('role_id') <= 3): ?>
                                    <li class="nav-item">
                                        <a class="nav-link btn btn-sm btn-outline-primary px-2 py-1 mr-1 mb-1 <?= (($nama_pleton_aktif ?? 'All') == 'All') ? 'active' : '' ?>"
                                            href="<?= site_url($prefix . '/binplinsis/nilaimental?pleton=All&pleton_id=All&minggu=' . $minggu_aktif . '&hari=' . $hari_aktif) ?>">All</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (!empty($pleton_list)): ?>
                                    <?php foreach ($pleton_list as $p): ?>
                                        <li class="nav-item mb-1">
                                            <a class="nav-link btn btn-sm btn-outline-primary px-2 py-1 mr-1 <?= ($nama_pleton_aktif == $p['nama_pleton']) ? 'active' : '' ?>"
                                                href="<?= site_url($prefix . '/binplinsis/nilaimental?pleton=' . urlencode($p['nama_pleton']) . '&minggu=' . $minggu_aktif . '&hari=' . $hari_aktif) ?>">
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
                            <input type="hidden" name="hari_ke" value="<?= $hari_aktif ?? 1 ?>">

                            <div class="table-responsive" style="background-color:rgb(250, 216, 149);">
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
                                            $isReadonlyInitially = ($n !== null) || ($role == 'siswa' || $role == '7');

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

                                                <?php for ($i = 0; $i < 22; $i++): ?>
                                                    <td>
                                                        <input type="number"
                                                            class="form-control form-control-sm nilai-input-<?= $s['id'] ?>"
                                                            value="<?= $skor_all[$i] ?? 0 ?>"
                                                            min="0" max="5"
                                                            oninput="updateNilai(this, <?= $s['id'] ?>)"
                                                            style="width: 50px;"
                                                            <?= in_array($prefix, ['danki', 'danyon']) || $isReadonlyInitially ? 'readonly' : '' ?>>
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
                                                        <?= in_array($prefix, ['danki', 'danyon']) || $isReadonlyInitially ? 'readonly' : '' ?>>
                                                </td>
                                                <td>
                                                    <input type="number" id="plus_<?= $s['id'] ?>"
                                                        value="<?= $n['tind_diluar_plus'] ?? 0 ?>"
                                                        class="form-control form-control-sm"
                                                        oninput="hitungTotal(<?= $s['id'] ?>)" style="width: 50px;"
                                                        <?= ($prefix === 'danki' || $prefix === 'danyon') ? 'readonly' : ($isReadonlyInitially ? 'readonly' : '') ?>>
                                                </td>
                                                <td>
                                                    <b id="akhir_<?= $s['id'] ?>"><?= $n['nilai_akhir'] ?? 0 ?></b>
                                                </td>

                                                <td data-status-n="<?= ($n !== null) ? 'locked' : 'open' ?>">
                                                    <?php
                                                    $idSiswa       = is_array($s) ? $s['id'] : $s->id;
                                                    $dataNilaiSiswa = $map_nilai[$idSiswa] ?? [];
                                                    $statusDanki   = $dataNilaiSiswa['status_danki'] ?? '0';
                                                    $statusDanyon  = $dataNilaiSiswa['status_danyon'] ?? '0';
                                                    $catatanDanki  = $dataNilaiSiswa['catatan_danki'] ?? '';
                                                    $catatanDanyon = $dataNilaiSiswa['catatan_danyon'] ?? '';
                                                    ?>

                                                    <?php if ($role == 4): ?>
                                                        <?php if ($statusDanki == '2'): ?>
                                                            <div class="alert alert-danger p-1 mb-1 text-left" style="font-size: 11px;">
                                                                <i class="fa fa-exclamation-circle"></i> <strong>Ditolak Danki:</strong> <?= esc($catatanDanki) ?>
                                                            </div>
                                                        <?php elseif ($statusDanyon == '2'): ?>
                                                            <div class="alert alert-danger p-1 mb-1 text-left" style="font-size: 11px;">
                                                                <i class="fa fa-exclamation-circle"></i> <strong>Ditolak Danyon:</strong> <?= esc($catatanDanyon) ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if ($n !== null): ?>
                                                            <button type="button" id="btn_edit_<?= $idSiswa ?>" class="btn btn-xs btn-warning" onclick="bukaEdit(<?= $idSiswa ?>)"><i class="fa fa-edit"></i> Edit</button>
                                                            <button type="button" id="btn_simpan_<?= $idSiswa ?>" class="btn btn-xs btn-primary" style="display:none" onclick="updateData(<?= $idSiswa ?>)"><i class="fa fa-save"></i> Simpan</button>
                                                        <?php else: ?>
                                                            <button type="button" id="btn_simpan_<?= $idSiswa ?>" class="btn btn-xs btn-primary btn-aksi-<?= $idSiswa ?>" onclick="simpanData(<?= $idSiswa ?>)" disabled><i class="fa fa-save"></i> Simpan</button>
                                                        <?php endif; ?>

                                                    <?php elseif ($role == 5): ?>
                                                        <?php if ($statusDanyon == '2'): ?>
                                                            <div class="alert alert-danger p-1 mb-1 text-left" style="font-size: 11px;">
                                                                <i class="fa fa-exclamation-circle"></i> <strong>Ditolak Danyon:</strong> <?= esc($catatanDanyon) ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if ($statusDanki == '1'): ?>
                                                            <button type="button" class="btn btn-xs btn-secondary" disabled><i class="fa fa-check"></i> Approved Danki</button>
                                                        <?php elseif ($statusDanki == '2'): ?>
                                                            <button type="button" class="btn btn-xs btn-dark" disabled><i class="fa fa-times"></i> Ditolak Danki</button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-xs btn-success" onclick="verifikasiData(<?= $idSiswa ?>, 'approve_danki', <?= $minggu_aktif ?>)"><i class="fa fa-check"></i> Approve</button>
                                                            <button type="button" class="btn btn-xs btn-danger" onclick="tolakData(<?= $idSiswa ?>, <?= $minggu_aktif ?>)"><i class="fa fa-times"></i> Tolak</button>
                                                        <?php endif; ?>

                                                    <?php elseif ($role == 6): ?>
                                                        <?php if ($statusDanyon == '1'): ?>
                                                            <button type="button" class="btn btn-xs btn-secondary" disabled><i class="fa fa-check-double"></i> Approved Danyon</button>
                                                        <?php elseif ($statusDanyon == '2'): ?>
                                                            <button type="button" class="btn btn-xs btn-dark" disabled><i class="fa fa-times"></i> Ditolak Danyon</button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-xs btn-success" onclick="verifikasiData(<?= $idSiswa ?>, 'approve_danyon', <?= $minggu_aktif ?>)"><i class="fa fa-check"></i> Approve</button>
                                                            <button type="button" class="btn btn-xs btn-danger" onclick="tolakData(<?= $idSiswa ?>, <?= $minggu_aktif ?>)"><i class="fa fa-times"></i> Tolak</button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center mt-3">
                                <?= $pager->only(array_keys($_GET))->links('default', 'bootstrap_full') ?>
                            </div>

                            <!-- Bagian Tanda Tangan -->
                            <div class="mt-5 mb-4 d-flex justify-content-around text-center flex-wrap">
                                <div class="ttd-box mb-3">
                                    <p class="mb-0 font-weight-bold">Komandan Peleton</p>
                                    <div id="container-ttd-danton" style="height: 100px;" class="d-flex align-items-center justify-content-center">
                                        <?php if (!empty($danton['ttd'])): ?>
                                            <img src="<?= base_url('assets/dist/img/ttd/' . $danton['ttd']) ?>" alt="TTD Danton" style="max-height: 80px; width:200px">
                                        <?php else: ?>
                                            <span class="text-muted small-text italic">Belum dikirim</span>
                                        <?php endif; ?>
                                    </div>
                                    <p id="nama-danton-text" class="mb-0 border-bottom font-weight-bold">
                                        <?= (!empty($danton['nama_pangkat']) ? $danton['nama_pangkat'] . '. ' : '') . ($danton['nama'] ?? '( Nama Danton )') ?>
                                    </p>
                                    <p class="text-muted">NRP: <span id="nrp-danton-text"><?= $danton['nomor_induk'] ?? '---------' ?></span></p>
                                </div>

                                <div class="ttd-box mb-3">
                                    <p class="mb-0 font-weight-bold">Komandan Kompi</p>
                                    <div id="container-ttd-danki" style="height: 100px;" class="d-flex align-items-center justify-content-center">
                                        <?php if (!empty($danki['ttd']) && isset($status_approval['status_danki']) && $status_approval['status_danki'] == '1'): ?>
                                            <img src="<?= base_url('assets/dist/img/ttd/' . $danki['ttd']) ?>" alt="TTD Danki" style="max-height: 80px; width:200px">
                                        <?php else: ?>
                                            <span class="text-muted small-text italic">Belum disetujui</span>
                                        <?php endif; ?>
                                    </div>
                                    <p id="nama-danki-text" class="mb-0 border-bottom font-weight-bold">
                                        <?= (!empty($danki['nama_pangkat']) ? $danki['nama_pangkat'] . '. ' : '') . ($danki['nama'] ?? '( Nama Danki )') ?>
                                    </p>
                                    <p class="text-muted">NRP: <span id="nrp-danki-text"><?= $danki['nomor_induk'] ?? '---------' ?></span></p>
                                </div>

                                <div class="ttd-box mb-3">
                                    <p class="mb-0 font-weight-bold">Komandan Batalyon</p>
                                    <div id="container-ttd-danyon" style="height: 100px;" class="d-flex align-items-center justify-content-center">
                                        <?php if (!empty($danyon['ttd']) && isset($status_approval['status_danyon']) && $status_approval['status_danyon'] == '1'): ?>
                                            <img src="<?= base_url('assets/dist/img/ttd/' . $danyon['ttd']) ?>" alt="TTD Danyon" style="max-height: 80px; width:200px">
                                        <?php else: ?>
                                            <span class="text-muted small-text italic">Belum diverifikasi</span>
                                        <?php endif; ?>
                                    </div>
                                    <p id="nama-danyon-text" class="mb-0 border-bottom font-weight-bold">
                                        <?= (!empty($danyon['nama_pangkat']) ? $danyon['nama_pangkat'] . '. ' : '') . ($danyon['nama'] ?? '( Nama Danyon )') ?>
                                    </p>
                                    <p class="text-muted">NRP: <span id="nrp-danyon-text"><?= $danyon['nomor_induk'] ?? '---------' ?></span></p>
                                </div>
                            </div>

                            <div class="mb-3 mt-3 d-flex justify-content-center">
                                <?php
                                $role = session()->get('role_id');
                                $mingguKe = $minggu_aktif ?? '1';
                                $prefixRole = service('uri')->getSegment(1);

                                if ($role == 4): ?>
                                    <button type="button" class="btn btn-primary" id="btn-kirim-massal"
                                        data-minggu="<?= $mingguKe ?>"
                                        data-pleton="<?= $nama_pleton_aktif; ?>"
                                        data-url="<?= site_url($prefixRole . '/binplinsis/kirimMassalKeDanki'); ?>"
                                        onclick="eksekusiKirimMassal(this)">
                                        <i class="fa fa-paper-plane"></i> Kirim Semua ke Danki
                                    </button>
                                <?php elseif ($role == 5): ?>
                                    <button type="button" class="btn btn-success" id="btn-kirim-massal"
                                        data-minggu="<?= $mingguKe ?>"
                                        data-pleton="<?= $nama_pleton_aktif ?>"
                                        data-aksi="kirim_danyon"
                                        data-url="<?= site_url($prefixRole . '/binplinsis/kirimMassalKeDanyon'); ?>"
                                        onclick="eksekusiKirimMassal(this)">
                                        <i class="fa fa-check"></i> Kirim ke Danyon
                                    </button>
                                <?php elseif ($role == 6): ?>
                                    <button type="button" class="btn btn-warning" id="btn-kirim-massal"
                                        data-minggu="<?= $mingguKe ?>"
                                        data-url="<?= site_url($prefixRole . '/binplinsis/verifikasiMassalDanyon'); ?>"
                                        onclick="eksekusiKirimMassal(this)">
                                        <i class="fa fa-shield-alt"></i> Verifikasi Danyon
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
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
        document.querySelectorAll('.btn-pleton-filter').forEach(btn => {
            btn.classList.remove('active');
        });

        if (element) {
            element.classList.add('active');
        }

        let searchPleton = namaPleton ? namaPleton.toString().trim().toLowerCase() : "";
        let rows = document.querySelectorAll('.siswa-row');
        let counter = 1;

        rows.forEach(row => {
            let pletonRow = row.getAttribute('data-pleton');
            let checkPleton = pletonRow ? pletonRow.toString().trim().toLowerCase() : "";

            if (searchPleton === "" || checkPleton === searchPleton) {
                row.style.setProperty('display', '', 'important');
                let noCell = row.querySelector('.nomor-urut');
                if (noCell) noCell.innerText = counter++;
            } else {
                row.style.setProperty('display', 'none', 'important');
            }
        });
    }

    // Jalankan otomatis pertama kali saat DOM siap
    document.addEventListener('DOMContentLoaded', function() {
        let activeTab = document.querySelector('.btn-pleton-filter.active');
        if (activeTab) {
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
    // 2. FUNGSI SIMPAN DATA (AJAX)
    // ==========================================
    function simpanData(siswaId) {
        let btn = document.getElementById('btn_simpan_' + siswaId);
        let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        let inputs = document.querySelectorAll('.nilai-input-' + siswaId);
        let tMinus = document.getElementById('minus_' + siswaId);
        let tPlus = document.getElementById('plus_' + siswaId);

        if (!btn) {
            console.error("Tombol Simpan tidak ditemukan!");
            return;
        }

        let dataNilai = {};
        inputs.forEach((input, index) => {
            dataNilai[index] = input.value;
        });

        let rowSiswa = document.getElementById('row_' + siswaId);
        let payload = {
            [csrfInput.name]: csrfInput.value,
            siswa_id: siswaId,
            minggu_ke: '<?= $minggu_aktif ?>',
            angkatan_id: rowSiswa ? rowSiswa.dataset.angkatan : '',
            nilai: dataNilai,
            tind_minus: tMinus ? tMinus.value : 0,
            tind_plus: tPlus ? tPlus.value : 0,
            jml_skor: document.getElementById('jml_' + siswaId)?.innerText || 0,
            jml_hsl_pengamatan: document.getElementById('pengamatan_' + siswaId)?.innerText || 0,
            nilai_konversi: document.getElementById('konversi_' + siswaId)?.innerText || 0,
            nilai_akhir_fix: document.getElementById('akhir_' + siswaId)?.innerText || 0,
            status_danton: '1'
        };

        btn.innerText = "Menyimpan...";
        btn.disabled = true;

        fetch("<?= site_url(uri_string() . '/../simpanNilaiMental'); ?>", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    if (data.token && csrfInput) csrfInput.value = data.token;

                    // SweetAlert sukses dengan tambahan reload halaman setelah alert selesai
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Data nilai telah disimpan.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Otomatis refresh halaman secara bersih
                    });

                } else {
                    btn.innerText = "Simpan";
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || 'Gagal menyimpan data.'
                    });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerText = "Simpan";
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan sistem.'
                });
                console.error(err);
            });
    }
    // ==========================================
    // 3. FUNGSI BUKA EDIT DATA
    // ==========================================
    function bukaEdit(siswaId) {
        let inputs = document.querySelectorAll('.nilai-input-' + siswaId);
        let tMinus = document.getElementById('minus_' + siswaId);
        let tPlus = document.getElementById('plus_' + siswaId);
        let btn = document.getElementById('btn_edit_' + siswaId); // atau btn_simpan tergantung ID tombol Anda

        // Buka kunci input agar bisa diedit kembali
        inputs.forEach(input => input.readOnly = false);
        if (tMinus) tMinus.readOnly = false;
        if (tPlus) tPlus.readOnly = false;

        // Ubah tampilan tombol kembali ke mode Simpan
        if (btn) {
            btn.innerText = "Simpan";
            btn.classList.remove('btn-success', 'btn-warning');
            btn.classList.add('btn-primary');

            btn.setAttribute('onclick', 'simpanData(' + siswaId + ')');
            btn.id = 'btn_simpan_' + siswaId;
        }
    }

    // ==========================================
    // FUNGSI KIRIM MASSAAL KE DANKI
    // ==========================================
    function eksekusiKirimMassal(button) {
        let mingguKe = button.getAttribute('data-minggu');
        let namaPleton = button.getAttribute('data-pleton') || 'All';
        let aksi = button.getAttribute('data-aksi') || 'kirim_danyon';
        let targetUrl = button.getAttribute('data-url');

        console.log("Minggu Ke:", mingguKe);
        console.log("Nama Pleton:", namaPleton);
        console.log("Aksi:", aksi);
        console.log("Target URL:", targetUrl);

        if (!mingguKe || !targetUrl) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Data tidak lengkap! (Minggu: ' + mingguKe + ', URL: ' + targetUrl + ')'
            });
            return;
        }

        Swal.fire({
            title: 'Kirim Data Massal?',
            text: "Data nilai mental yang sudah diisi akan dikirimkan secara massal.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Kirim Sekarang!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                let originalText = button.innerHTML;
                button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengirim...';
                button.disabled = true;

                let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
                let payload = {
                    minggu_ke: mingguKe,
                    nama_pleton: namaPleton,
                    aksi: aksi
                };

                if (csrfInput) {
                    payload[csrfInput.name] = csrfInput.value;
                }

                fetch(targetUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        button.innerHTML = originalText;
                        button.disabled = false;

                        if (data.token && csrfInput) {
                            csrfInput.value = data.token;
                        }

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message || 'Data berhasil dikirim.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: data.message || 'Gagal mengirim data.'
                            });
                        }
                    })
                    .catch(err => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Sistem',
                            text: 'Terjadi kesalahan saat menghubungi server.'
                        });
                        console.error(err);
                    });
            }
        });
    }

    function verifikasiData(siswaId, aksi, mingguKe) {
        // Ambil prefix role aktif dari URL browser (misal: 'danki' atau 'danyon')
        let prefixRole = "<?= service('uri')->getSegment(1) ?>";
        let targetUrl = "<?= site_url(); ?>" + prefixRole + "/binplinsis/verifikasi";

        Swal.fire({
            title: 'Konfirmasi Approval',
            text: "Apakah Anda yakin ingin menyetujui (Approve) data siswa ini?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Approve!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
                let payload = {
                    siswa_id: siswaId,
                    aksi: aksi,
                    minggu_ke: mingguKe
                };

                if (csrfInput) {
                    payload[csrfInput.name] = csrfInput.value;
                }

                fetch(targetUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.token && csrfInput) {
                            csrfInput.value = data.token;
                        }

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message || 'Data berhasil disetujui.',
                                timer: 1200,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: data.message || 'Gagal memproses verifikasi.'
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Sistem',
                            text: 'Terjadi kesalahan saat menghubungi server.'
                        });
                    });
            }
        });
    }

    function tolakData(idSiswa, mingguKe) {
        Swal.fire({
            title: 'Masukkan Alasan Penolakan',
            input: 'textarea',
            inputPlaceholder: 'Tuliskan alasan mengapa nilai ini ditolak...',
            showCancelButton: true,
            confirmButtonText: 'Kirim Penolakan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan penolakan wajib diisi!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Kirim data via AJAX ke Controller
                $.ajax({
                    url: "<?= base_url($prefix . '/binplinsis/proses_tolak_danki') ?>",
                    type: "POST",
                    data: {
                        siswa_id: idSiswa,
                        minggu_ke: mingguKe,
                        catatan: result.value,
                        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Penolakan dan catatan berhasil dikirim.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan pada server.', 'error');
                    }
                });
            }
        });
    }
</script>
<?= $this->endsection(); ?>