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
                                    <?php for ($m = 1; $m <= 22; $m++): ?>
                                        <option value="<?= $m ?>" <?= ($minggu_aktif == $m) ? 'selected' : '' ?>>Minggu Ke-<?= $m ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="mt-4">
                                <?php
                                $roleId = session()->get('role_id');

                                // Mapping prefix role sesuai array $roles Anda
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

                                // Tentukan URL tujuan berdasarkan role
                                if ($roleId == 4) {
                                    $exportUrl = base_url($prefix . '/binplinsis/pleton/' . (session()->get('pleton_id') ?? 1));
                                } else {
                                    $exportUrl = base_url($prefix . '/binplinsis/role/' . $roleId);
                                }
                                ?>

                                <!-- Tombol Export PDF hanya akan dirender jika roleId adalah 7 (siswa) -->
                                <?php if ($roleId == 7): ?>
                                    <div class="mt-4">
                                        <a href="<?= $exportUrl; ?>" class="btn btn-danger" target="_blank">
                                            <i class="fas fa-file-pdf"></i> Export PDF
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <?php $role = session()->get('level'); ?>

                                <?php if ($role === 'admin' || $role === 'staf'): ?>
                                    <li class="nav-item">
                                        <a class="nav-link btn btn-sm btn-outline-primary px-1 py-1 mr-1 <?= (($nama_pleton_aktif ?? 'All') == 'All') ? 'active' : '' ?>"
                                            href="<?= site_url($prefix . '/binplinsis/nilaimental?pleton=All&pleton_id=All') ?>">All</a>
                                    </li>
                                <?php endif; ?>

                                <?php if (!empty($pleton_list)): ?>
                                    <?php foreach ($pleton_list as $p): ?>
                                        <li class="nav-item mb-1">
                                            <a class="nav-link btn btn-sm btn-outline-primary px-1 py-1 mr-1 <?= ($nama_pleton_aktif == $p['nama_pleton']) ? 'active' : '' ?>"
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
                                                    <?php
                                                    $mingguKe = service('request')->getGet('minggu') ?? 1;
                                                    $idSiswa  = is_array($s) ? $s['id'] : $s->id;
                                                    $dataNilaiSiswa = $map_nilai[$idSiswa] ?? [];
                                                    $statusDanki    = $dataNilaiSiswa['status_danki'] ?? '0';
                                                    $statusDanyon   = $dataNilaiSiswa['status_danyon'] ?? '0';
                                                    ?>

                                                    <!-- =================ROLE 4: DANTON================ -->
                                                    <?php if ($role == 4): ?>
                                                        <?php if ($n !== null): ?>
                                                            <button type="button" id="btn_edit_<?= $idSiswa ?>" class="btn btn-xs btn-warning" onclick="bukaEdit(<?= $idSiswa ?>)">Edit</button>
                                                            <button type="button" id="btn_simpan_<?= $idSiswa ?>" class="btn btn-xs btn-primary" style="display:none" onclick="updateData(<?= $idSiswa ?>)">Simpan</button>
                                                        <?php else: ?>
                                                            <button type="button" id="btn_simpan_<?= $idSiswa ?>" class="btn btn-xs btn-primary" onclick="simpanData(<?= $idSiswa ?>, 'simpan')" disabled>Simpan</button>
                                                        <?php endif; ?>

                                                        <!-- =================ROLE 5: DANKI================ -->
                                                    <?php elseif ($role == 5): ?>
                                                        <?php if ($statusDanki == '1'): ?>
                                                            <button type="button" class="btn btn-xs btn-secondary" disabled><i class="fa fa-check"></i> Approved Danki</button>
                                                        <?php elseif ($statusDanki == '2'): ?>
                                                            <button type="button" class="btn btn-xs btn-dark" disabled><i class="fa fa-times"></i> Ditolak Danki</button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-xs btn-success" onclick="verifikasiData(<?= $idSiswa ?>, 'approve_danki', <?= $mingguKe ?>)">Approve</button>
                                                            <button type="button" class="btn btn-xs btn-danger" onclick="verifikasiData(<?= $idSiswa ?>, 'tolak_danki', <?= $mingguKe ?>)">Tolak</button>
                                                        <?php endif; ?>

                                                        <!-- =================ROLE 6: DANYON================ -->
                                                    <?php elseif ($role == 6): ?>
                                                        <?php if ($statusDanyon == '1'): ?>
                                                            <button type="button" class="btn btn-xs btn-secondary" disabled><i class="fa fa-check-double"></i> Approved Danyon</button>
                                                        <?php elseif ($statusDanyon == '2'): ?>
                                                            <button type="button" class="btn btn-xs btn-dark" disabled><i class="fa fa-times"></i> Ditolak Danyon</button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-xs btn-success" onclick="verifikasiData(<?= $idSiswa ?>, 'approve_danyon', <?= $mingguKe ?>)">Approve</button>
                                                            <button type="button" class="btn btn-xs btn-danger" onclick="verifikasiData(<?= $idSiswa ?>, 'tolak_danyon', <?= $mingguKe ?>)">Tolak</button>
                                                        <?php endif; ?>

                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                <?= $pager->links('default', 'bootstrap_full') ?>
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
                                    <!-- Perbaikan Nama & Pangkat Danton -->
                                    <p id="nama-danton-text" class="mb-0 border-bottom font-weight-bold">
                                        <?= (!empty($danton['nama_pangkat']) ? $danton['nama_pangkat'] . '. ' : '') . ($danton['nama'] ?? '( Nama Danton )') ?>
                                    </p>
                                    <p class="text-muted">NRP: <span id="nrp-danton-text"><?= $danton['nomor_induk'] ?? '---------' ?></span></p>
                                </div>

                                <!-- BOX DANKI -->
                                <div class="ttd-box">
                                    <p class="mb-0 font-weight-bold">Komandan Kompi</p>
                                    <div id="container-ttd-danki" style="height: 100px;" class="d-flex align-items-center justify-content-center">
                                        <?php
                                        $status_danki = $status_approval['status_danki'] ?? '0';
                                        if ($status_danki === '1' && !empty($danki['ttd'])): ?>
                                            <img src="<?= base_url('assets/dist/img/ttd/' . $danki['ttd']) ?>" alt="TTD Danki" style="max-height: 80px;">
                                        <?php else: ?>
                                            <span class="text-muted small-text italic">Belum disetujui</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Menampilkan Pangkat dan Nama Danki -->
                                    <p id="nama-danki-text" class="mb-0 border-bottom font-weight-bold">
                                        <?= (!empty($danki['nama_pangkat']) ? $danki['nama_pangkat'] . '. ' : '') . ($danki['nama'] ?? '( Nama Danki )') ?>
                                    </p>
                                    <p class="text-muted">NRP: <span id="nrp-danki-text"><?= $danki['nomor_induk'] ?? '---------' ?></span></p>
                                </div>

                                <!-- BOX DANYON -->
                                <div class="ttd-box">
                                    <p class="mb-0 font-weight-bold">Komandan Batalyon</p>
                                    <div id="container-ttd-danyon" style="height: 100px;" class="d-flex align-items-center justify-content-center">
                                        <?php
                                        $status_danyon = $status_approval['status_danyon'] ?? '0';
                                        if ($status_danyon === '1' && !empty($danyon['ttd'])): ?>
                                            <img src="<?= base_url('assets/dist/img/ttd/' . $danyon['ttd']) ?>" alt="TTD Danyon" style="max-height: 80px;">
                                        <?php else: ?>
                                            <span class="text-muted small-text italic">Belum diverifikasi</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Perbaikan Nama & Pangkat Danyon -->
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

                                if ($role == 4): ?>
                                    <button type="button" class="btn btn-primary" id="btn-kirim-massal" data-minggu="<?= $mingguKe ?>" data-aksi="kirimMassalKeDanki" onclick="eksekusiKirimMassal(this)">
                                        <i class="fa fa-paper-plane"></i> Kirim Semua ke Danki
                                    </button>
                                <?php elseif ($role == 5): ?>
                                    <button type="button" class="btn btn-success" id="btn-kirim-massal" data-minggu="<?= $mingguKe ?>" data-aksi="kirimMassalKeDanyon" onclick="eksekusiKirimMassal(this)">
                                        <i class="fa fa-check"></i> Kirim ke Danyon
                                    </button>
                                <?php elseif ($role == 6): ?>
                                    <button type="button" class="btn btn-warning" id="btn-kirim-massal" data-minggu="<?= $mingguKe ?>" data-aksi="verifikasiMassalDanyon" onclick="eksekusiKirimMassal(this)">
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
    // 1. Fungsi Validasi & Update Nilai
    function updateNilai(inputElement, siswaId) {
        let val = parseFloat(inputElement.value);
        if (inputElement.value !== '') {
            if (val > 4) inputElement.value = 4;
            else if (val < 0) inputElement.value = 0;
        }
        hitungTotal(siswaId);
    }

    // 2. Fungsi Hitung Total
    function hitungTotal(siswaId) {
        let inputs = document.querySelectorAll('.nilai-input-' + siswaId);
        let total = 0;
        inputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        let hasilPengamatan = Math.round((total / 22) * 10) / 10;
        let nilaiKonversi = (hasilPengamatan * 5) + 55;
        let tMinus = parseFloat(document.getElementById('minus_' + siswaId)?.value) || 0;
        let tPlus = parseFloat(document.getElementById('plus_' + siswaId)?.value) || 0;
        let nilaiAkhir = nilaiKonversi - tMinus + tPlus;

        document.getElementById('jml_' + siswaId).innerText = total;
        document.getElementById('pengamatan_' + siswaId).innerText = hasilPengamatan.toFixed(1);
        document.getElementById('konversi_' + siswaId).innerText = nilaiKonversi.toFixed(2);
        document.getElementById('akhir_' + siswaId).innerText = nilaiAkhir.toFixed(2);
    }

    /**
     * Fungsi Utama Handle Aksi
     * Menentukan apakah tombol berfungsi sebagai Edit (membuka form)
     * atau Simpan (mengirim data ke server)
     */
    function handleAction(siswaId) {
        let btn = document.querySelector('.btn-aksi-' + siswaId);
        let inputs = document.querySelectorAll('.nilai-input-' + siswaId);
        let tMinus = document.getElementById('minus_' + siswaId);
        let tPlus = document.getElementById('plus_' + siswaId);

        if (btn.innerText === "Edit") {
            // Mode Edit: Buka kunci input
            inputs.forEach(input => input.readOnly = false);
            if (tMinus) tMinus.readOnly = false;
            if (tPlus) tPlus.readOnly = false;

            btn.innerText = "Simpan";
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-primary');
        } else {
            // Mode Simpan: Eksekusi fungsi simpan ke server
            simpanData(siswaId);
        }
    }

    /**
     * Fungsi Simpan Data (AJAX)
     */
    function simpanData(siswaId) {
        let btn = document.querySelector('.btn-aksi-' + siswaId);
        let csrfInput = document.getElementById('csrf_token');
        let inputs = document.querySelectorAll('.nilai-input-' + siswaId);
        let tMinus = document.getElementById('minus_' + siswaId);
        let tPlus = document.getElementById('plus_' + siswaId);

        // Persiapan data
        let dataNilai = {};
        inputs.forEach((input, index) => {
            dataNilai[index] = input.value;
        });

        let payload = {
            [csrfInput.name]: csrfInput.value,
            siswa_id: siswaId,
            minggu_ke: '<?= $minggu_aktif ?>',
            angkatan_id: document.getElementById('row_' + siswaId).dataset.angkatan,
            nilai: dataNilai,
            tind_minus: tMinus?.value || 0,
            tind_plus: tPlus?.value || 0,
            jml_skor: document.getElementById('jml_' + siswaId).innerText,
            jml_hsl_pengamatan: document.getElementById('pengamatan_' + siswaId).innerText,
            nilai_konversi: document.getElementById('konversi_' + siswaId).innerText,
            nilai_akhir_fix: document.getElementById('akhir_' + siswaId).innerText,
            status_danton: '1'
        };

        btn.innerText = "Menyimpan...";
        btn.disabled = true; // Mencegah klik ganda saat proses

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
                    if (data.token) csrfInput.value = data.token;

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Data nilai telah disimpan.',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Kunci kembali input setelah sukses
                    inputs.forEach(input => input.readOnly = true);
                    if (tMinus) tMinus.readOnly = true;
                    if (tPlus) tPlus.readOnly = true;

                    // Ubah tombol kembali ke Edit
                    btn.innerText = "Edit";
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-warning');
                } else {
                    btn.innerText = "Simpan";
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message
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
            });
    }

    function kirimPletonKeDanki(namaPleton) {
        Swal.fire({
            title: 'Konfirmasi Kirim',
            text: "Anda yakin ingin mengirim seluruh nilai Pleton " + namaPleton + " ke Danki?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Kirim!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tampilkan loading agar user tahu proses sedang berjalan
                Swal.fire({
                    title: 'Memproses...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("<?= base_url() . '/' . service('uri')->getSegment(1) . '/binplinsis/kirimMassalKeDanki'; ?>", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.getElementById('csrf_token').value // Kirim token via header
                        },
                        body: JSON.stringify({
                            nama_pleton: namaPleton,
                            minggu_ke: '<?= $minggu_aktif ?>'
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Berhasil!', 'Nilai pleton telah dikirim ke Danki.', 'success')
                                .then(() => location.reload()); // Refresh halaman
                        } else {
                            Swal.fire('Gagal', data.message || 'Terjadi kesalahan sistem.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Gagal', 'Tidak dapat terhubung ke server.', 'error');
                    });
            }
        });
    }
</script>

<script>
    function verifikasiData(idSiswa, aksi, mingguKe) {
        // Menggunakan base_url yang digabung langsung dengan endpoint routes CodeIgniter 4
        let targetUrl = '<?= base_url('danki/binplinsis/verifikasiNilaiMental') ?>';

        $.ajax({
            url: targetUrl,
            type: 'POST',
            data: {
                id_siswa: idSiswa,
                aksi: aksi,
                minggu_ke: mingguKe,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Sistem Error!',
                    text: 'Terjadi kesalahan pada server.'
                });
                console.error('AJAX Error:', status, error);
                console.log(xhr.responseText);
            }
        });
    }
</script>
<?= $this->endsection(); ?>