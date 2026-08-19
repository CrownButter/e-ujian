<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<!-- CDN SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .text-wrap-custom {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>

<div class="content-wrapper">
    <!-- Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= esc($page_title ?? 'Daftar Kelas Ujian'); ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <!-- Tombol Tambah Kelas Ujian -->
                    <button type="button" class="btn btn-primary" onclick="tambahKelasUjian()">
                        <i class="fas fa-plus"></i> Tambah Kelas Ujian
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Tabel Data Kelas Ujian OBE</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableKelasUjian" class="table table-bordered table-striped">
                            <thead>
                                <tr class="text-center">
                                    <th width="5%">No</th>
                                    <th>Nama Kelas</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Jadwal Ujian</th>
                                    <th>Nama Gadik</th>
                                    <th>Jumlah Siswa</th>
                                    <th>Status</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Tambah / Edit Kelas Ujian -->
<div class="modal fade" id="modalKelasUjian" tabindex="-1" role="dialog" aria-labelledby="modalKelasUjianLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formKelasUjian">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" id="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalKelasUjianLabel">Tambah Kelas Ujian</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_kelas">Nama Kelas <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" placeholder="Contoh: Kelas A - Reguler" required>
                    </div>

                    <!-- Dropdown Mata Pelajaran -->
                    <div class="form-group">
                        <label>Mata Pelajaran <span class="text-danger">*</span></label>
                        <select name="mata_pelajaran_id" id="mata_pelajaran_id" class="form-control select2bs4" style="width: 100%;" required>
                            <option value="">-- Pilih Mata Pelajaran --</option>
                        </select>
                    </div>

                    <!-- Dropdown Pengampu / Gadik -->
                    <div class="form-group">
                        <label>Pengampu / Gadik</label>
                        <select name="penguji_id" id="penguji_id" class="form-control select2bs4" style="width: 100%;">
                            <option value="">-- Pilih Pengampu --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Ujian <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control" required>
                    </div>

                    <div class="row">
                        <!-- Jam Mulai -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" required>
                            </div>
                        </div>

                        <!-- Jam Selesai -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Jam Selesai <span class="text-danger">*</span></label>
                                <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" required>
                            </div>
                        </div>

                        <!-- Durasi Otomatis (Readonly) -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Durasi Ujian</label>
                                <div class="input-group">
                                    <input type="text" id="durasi_menit" class="form-control bg-light text-center font-weight-bold" readonly placeholder="-- Menit --">
                                    <div class="input-group-append">
                                        <span class="input-group-text">Menit</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAMBAHAN: FITUR PEMILIHAN SISWA -->
                    <hr>
                    <div class="form-group">
                        <label><b>Metode Pemilihan Siswa</b> <span class="text-danger">*</span></label>
                        <select name="metode_pilih" id="metodePilih" class="form-control" required>
                            <option value="">-- Pilih Metode Pemilihan Peserta --</option>
                            <option value="semua">Semua Siswa</option>
                            <option value="pleton">Berdasarkan Pleton</option>
                            <option value="satuan">Satuan (Pilih Siswa Tertentu)</option>
                        </select>
                    </div>

                    <!-- Opsi Pleton -->
                    <!-- Opsi Pleton (Dibuat Multiple) -->
                    <div class="form-group" id="wrapperPleton" style="display: none;">
                        <label>Pilih Pleton (Bisa Pilih Banyak) <span class="text-danger">*</span></label>
                        <select name="pleton_ids[]" id="pleton_id" class="form-control" multiple size="4">
                            <!-- Data pleton diload via JS -->
                        </select>
                        <small class="text-muted">Tahan tombol <b>Ctrl</b> (Windows) atau <b>Cmd</b> (Mac) untuk memilih lebih dari satu pleton.</small>
                    </div>

                    <div class="form-group" id="wrapperSatuan" style="display: none;">
                        <label>Pilih Siswa (Bisa Pilih Banyak) <span class="text-danger">*</span></label>
                        <select name="siswa_ids[]" id="siswa_ids" class="form-control" multiple="multiple" style="width: 100%;">
                            <!-- Data siswa akan di-load via JS -->
                        </select>
                        <small class="text-muted">Ketik nama atau nomor siswa untuk mencari.</small>
                    </div>
                    <hr>

                    <div class="form-group">
                        <label>Status Ujian</label>
                        <select name="status_ujian" id="status_ujian" class="form-control" required>
                            <option value="draf" selected>Draf</option>
                            <option value="publis">Publis</option>
                            <option value="sedang_ujian">Sedang Ujian</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>


<script>
    const CSRF_TOKEN = '<?= csrf_hash() ?>';
    const CSRF_NAME = '<?= csrf_token() ?>';
    let cacheNamaPleton = {};
    // let cacheKelasUjian = [];
    <?php
    $segments = explode('/', uri_string());
    $currentPrefix = $segments[0] ?? 'admin';
    ?>
    const BASE_URL = '<?= base_url() ?>';
    const PREFIX = '<?= $currentPrefix ?>';

    let cacheKelasUjian = [];

    function initSelect2Custom() {
        if ($.fn.select2) {
            // 1. Hancurkan instance lama yang aktif untuk mencegah duplikasi error
            $('#mata_pelajaran_id, #penguji_id, #pleton_id, #siswa_ids').each(function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).select2('destroy');
                }
            });

            // 2. Mata Pelajaran
            $('#mata_pelajaran_id').select2({
                width: '100%',
                placeholder: "-- Pilih Mata Pelajaran --",
                allowClear: true,
                dropdownParent: $('#modalKelasUjian')
            });

            // 3. Penguji / Gadik
            $('#penguji_id').select2({
                width: '100%',
                placeholder: "-- Pilih Pengampu --",
                allowClear: true,
                dropdownParent: $('#modalKelasUjian')
            });

            // 4. Pleton (Multi-select)
            $('#pleton_id').select2({
                width: '100%',
                placeholder: "Cari dan pilih pleton...",
                allowClear: true,
                multiple: true,
                dropdownParent: $('#modalKelasUjian')
            });

            // 5. Siswa (Multi-select) - INI YANG KEMARIN BELUM ADA DI DALAM INISIALISASI
            $('#siswa_ids').select2({
                width: '100%',
                placeholder: "Cari dan pilih siswa (Nama / Nosis)...",
                allowClear: true,
                multiple: true,
                dropdownParent: $('#modalKelasUjian')
            }).on('select2:open', function() {
                // Pengaman ekstra: Memaksa kotak dropdown selalu muncul di bawah saat dibuka
                let dropdown = $('.select2-container--open .select2-dropdown');
                if (dropdown.hasClass('select2-dropdown--above')) {
                    dropdown.removeClass('select2-dropdown--above').addClass('select2-dropdown--below');
                }
            });
        }
    }

    // Fungsi untuk membasmi Select2 yang nyasar ke input tanggal/jam
    function killStraySelect2() {
        $('#tanggal, #jam_mulai, #jam_selesai, input[type="date"], input[name="tanggal"]').each(function() {
            let $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.removeClass('select2-hidden-accessible');
            $el.removeAttr('data-select2-id');

            let $container = $el.next('.select2-container');
            if ($container.length) {
                $container.remove();
            }
            $el.show();
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        killStraySelect2();
        setTimeout(killStraySelect2, 200);
        setTimeout(killStraySelect2, 600);
        setTimeout(killStraySelect2, 1000);

        loadKelasUjian();
        loadDropdownMataPelajaran();
        loadDropdownPegawai();
        loadDropdownPleton();

        let metodePilihEl = document.getElementById('metodePilih');
        if (metodePilihEl) {
            metodePilihEl.addEventListener('change', function() {
                let value = this.value;
                let wrapPleton = document.getElementById('wrapperPleton');
                let wrapSatuan = document.getElementById('wrapperSatuan');

                if (wrapPleton) wrapPleton.style.display = (value === 'pleton') ? 'block' : 'none';
                if (wrapSatuan) wrapSatuan.style.display = (value === 'satuan') ? 'block' : 'none';

                // TAMBAHKAN KONDISI INI AGAR SELECT2 LANGSUNG TERINISIALISASI SAAT DIPILIH
                if (value === 'pleton') {
                    loadDropdownPleton();
                } else if (value === 'satuan') {
                    loadDropdownSiswa();
                }
            });
        }

        // EVENT HANDLER MODAL
        $('#modalKelasUjian').on('shown.bs.modal', function() {
            killStraySelect2();
            let metodePilih = $('#metodePilih').val();
            if (metodePilih === 'satuan') {
                loadDropdownSiswa();
            }
        });

        $('#modalKelasUjian').on('hidden.bs.modal', function() {
            $('#formKelasUjian')[0].reset();
            $('#kelas_id').val('');

            $('#pleton_id, #siswa_ids').each(function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).select2('destroy');
                }
            });

            $('#wrapperPleton, #wrapperSatuan').hide();
        });

        // HANDLE SUBMIT FORM KELAS UJIAN (STORE)
        const formKelasUjian = document.getElementById('formKelasUjian');
        if (formKelasUjian) {
            formKelasUjian.addEventListener('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                const csrfName = document.querySelector('meta[name="csrf-token-name"]').getAttribute('content');
                const csrfHash = document.querySelector('meta[name="csrf-token-hash"]').getAttribute('content');
                formData.append(csrfName, csrfHash);

                let url = `${BASE_URL}/${PREFIX}/obe/kelas-ujian/store`;

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.csrf_token) {
                            document.querySelector('meta[name="csrf-token-hash"]').setAttribute('content', res.csrf_token);
                        }

                        if (res.status) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: res.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $('#modalKelasUjian').modal('hide');
                            loadKelasUjian();
                        } else {
                            Swal.fire('Gagal!', res.message || 'Terjadi kesalahan validasi', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        Swal.fire('Error!', 'Terjadi kesalahan pada server.', 'error');
                    });
            });
        }
    });

    function loadDropdownMataPelajaran(selectedId = null) {
        let url = `${BASE_URL}/${PREFIX}/obe/mataPelajaranGetData`;
        if ($('#mata_pelajaran_id').hasClass('select2-hidden-accessible')) {
            $('#mata_pelajaran_id').select2('destroy');
        }

        fetch(url)
            .then(res => res.json())
            .then(res => {
                let select = document.querySelector('#mata_pelajaran_id');
                if (!select) return;

                select.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
                let dataMapel = Array.isArray(res) ? res : (res.data || res.mata_pelajaran || []);

                dataMapel.forEach(item => {
                    let idVal = item.id ?? item.mata_pelajaran_id;
                    let namaVal = item.nama_mapel ?? item.nama_pelajaran ?? item.nama;
                    select.innerHTML += `<option value="${idVal}">${namaVal}</option>`;
                });

                $('#mata_pelajaran_id').select2({
                    placeholder: '-- Pilih Mata Pelajaran --',
                    width: '100%',
                    allowClear: true,
                    dropdownParent: $('#modalKelasUjian')
                });

                if (selectedId) {
                    $('#mata_pelajaran_id').val(selectedId).trigger('change');
                }
            })
            .catch(err => console.error('Gagal memuat mata pelajaran:', err));
    }

    function loadDropdownPegawai(selectedId = null) {
        let url = `${BASE_URL}/${PREFIX}/obe/pegawaiGetData`;
        let $select = $('#penguji_id');

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        fetch(url)
            .then(res => res.json())
            .then(res => {
                let select = document.querySelector('#penguji_id');
                if (!select) return;

                select.innerHTML = '<option value="">-- Pilih Pengampu --</option>';
                let dataPegawai = Array.isArray(res) ? res : (res.data || res.pegawai || res.gadik || []);

                dataPegawai.forEach(item => {
                    let idVal = item.id ?? item.pegawai_id ?? item.gadik_id;
                    let namaVal = item.nama_pegawai ?? item.nama ?? item.nama_gadik;
                    select.innerHTML += `<option value="${idVal}">${namaVal}</option>`;
                });

                // Inisialisasi Select2 TANPA dropdownParent agar tidak langsung tertutup saat diklik
                $select.select2({
                    placeholder: '-- Pilih Pengampu --',
                    width: '100%',
                    allowClear: true,
                    minimumResultsForSearch: 0
                });

                if (selectedId) {
                    $select.val(selectedId).trigger('change');
                }
            })
            .catch(err => console.error('Gagal memuat pengampu:', err));
    }

    function loadDropdownPleton(selectedIds = []) {
        let cleanBase = (typeof BASE_URL !== 'undefined' ? BASE_URL : '').replace(/\/+$/, '');
        let cleanPrefix = (typeof PREFIX !== 'undefined' ? PREFIX : '').replace(/^\/+|\/+$/g, '');
        let url = `${cleanBase}/${cleanPrefix}/obe/pletonGetData`;

        const $select = $('#pleton_id');

        // Hancurkan select2 yang lama jika sudah pernah diinisialisasi agar tidak menumpuk
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(res => {
                $select.empty();

                // Tambahkan opsi default kosong agar placeholder berfungsi sempurna di multiple select
                $select.append(new Option('', ''));

                let dataPleton = Array.isArray(res) ? res : (res.data || []);

                // Masukkan option satu per satu ke dalam select
                dataPleton.forEach(item => {
                    let idVal = String(item.id ?? item.pleton_id);
                    let namaVal = item.nama_pleton ?? item.nama ?? `Pleton ${item.id}`;

                    if (typeof cacheNamaPleton !== 'undefined') {
                        cacheNamaPleton[idVal] = namaVal;
                    }

                    let option = document.createElement('option');
                    option.value = idVal;
                    option.textContent = namaVal;

                    // Cek apakah ID ini termasuk yang sebelumnya dipilih di form edit
                    if (selectedIds && selectedIds.length > 0) {
                        let stringSelectedIds = selectedIds.map(String);
                        if (stringSelectedIds.includes(idVal)) {
                            option.selected = true;
                        }
                    }
                    $select.append(option);
                });

                // INISIALISASI KEMBALI SELECT2 TANPA dropdownParent AGAR POSISI TIDAK MELAYANG
                $select.select2({
                    placeholder: "Cari dan pilih pleton...",
                    width: '100%',
                    allowClear: true,
                    multiple: true,
                    minimumResultsForSearch: 0 // Kotak pencarian langsung muncul otomatis
                });

                // Pastikan nilai terpilih ter-trigger dengan benar di Select2
                if (selectedIds && selectedIds.length > 0) {
                    let stringSelectedIds = selectedIds.map(String);
                    $select.val(stringSelectedIds).trigger('change');
                }
            })
            .catch(err => console.log('Gagal memuat data pleton:', err));
    }

    function loadDropdownSiswa(selectedIds = []) {
        const $select = $('#siswa_ids');

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        $select.empty();

        let url = `${BASE_URL}/${PREFIX}/api/siswa/all`;

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let dataSiswa = Array.isArray(response) ? response : (response.data || []);

                if (dataSiswa.length > 0) {
                    dataSiswa.forEach(siswa => {
                        const idSiswa = siswa.id ?? siswa.siswa_id;
                        const nosis = siswa.nosis || siswa.no_sis || '-';
                        const nama = siswa.nama || siswa.name || '-';

                        const option = new Option(`[${nosis}] ${nama}`, idSiswa, false, false);
                        $select.append(option);
                    });
                }

                // Inisialisasi Select2 TANPA dropdownParent agar posisinya normal di bawah input
                $select.select2({
                    placeholder: 'Cari dan pilih siswa (Nama / Nosis)...',
                    width: '100%',
                    multiple: true,
                    minimumResultsForSearch: 0
                });

                if (selectedIds && selectedIds.length > 0) {
                    $select.val(selectedIds.map(String)).trigger('change');
                }
            },
            error: function(xhr) {
                console.log("Error AJAX Siswa:", xhr.responseText);
            }
        });
    }

    function loadKelasUjian() {
        let url = `${BASE_URL}/${PREFIX}/obe/kelas-ujian/get-data`;
        fetch(url)
            .then(res => res.json())
            .then(res => {
                let tbody = document.querySelector('#tableKelasUjian tbody');
                if (!tbody) return;
                tbody.innerHTML = '';

                if (res.status && res.data.length > 0) {
                    cacheKelasUjian = res.data;
                    let no = 1;
                    res.data.forEach(item => {
                        let namaGadik = '<span class="badge badge-warning">Belum Ditentukan</span>';
                        if (item.nama_gadik) {
                            let pangkat = item.nama_pangkat ? item.nama_pangkat + ' - ' : '';
                            namaGadik = pangkat + item.nama_gadik;
                        }

                        // MODIFIKASI BAGIAN INI UNTUK MENAMPILKAN DETAIL PLETON / KETERANGAN
                        let jmlSiswa = item.jumlah_siswa ? item.jumlah_siswa + ' Siswa' : '0 Siswa';
                        let infoDetail = '';

                        // Jika backend mengirimkan data nama pleton (misal: item.nama_pleton atau item.deskripsi)
                        if (item.nama_pleton) {
                            infoDetail = `<br><small class="text-muted"><i class="fas fa-users"></i> Pleton: ${item.nama_pleton}</small>`;
                        } else if (item.deskripsi) {
                            infoDetail = `<br><small class="text-muted"><i class="fas fa-info-circle"></i> ${item.deskripsi}</small>`;
                        }

                        let tampilanJumlahDanDetail = `<strong>${jmlSiswa}</strong>${infoDetail}`;

                        let jadwalUjian = '<span class="badge badge-warning">Belum Ditentukan</span>';
                        if (item.tanggal) {
                            let options = {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            };
                            let tanggalFormatted = new Date(item.tanggal).toLocaleDateString('id-ID', options);
                            let jamMulai = item.jam_mulai ? item.jam_mulai.substring(0, 5) : '';
                            let jamSelesai = item.jam_selesai ? item.jam_selesai.substring(0, 5) : '';

                            jadwalUjian = `
                        <strong><i class="far fa-calendar-alt text-primary"></i> ${tanggalFormatted}</strong><br>
                        <small class="text-muted"><i class="far fa-clock"></i> ${jamMulai} - ${jamSelesai} WIB</small>
                    `;
                        }

                        tbody.innerHTML += `
                    <tr>
                        <td class="text-center">${no++}</td>
                        <td>${item.nama_kelas ?? '-'}</td>
                        <td>${item.mata_pelajaran ?? '-'}</td>
                        <td>${jadwalUjian}</td>
                        <td>${namaGadik}</td>
                        <td class="text-center">${tampilanJumlahDanDetail}</td>
                        <td class="text-center">
                            <span class="badge badge-${item.status_ujian == 'publis' ? 'success' : (item.status_ujian == 'sedang_ujian' ? 'primary' : 'secondary')}">
                                ${item.status_ujian ?? 'draf'}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="${BASE_URL}/${PREFIX}/obe/soal/${item.id}" class="btn btn-sm btn-info" title="Buat / Buka Soal">
                                <i class="fas fa-book-open"></i>
                            </a>
                            <button class="btn btn-sm btn-warning" onclick="editData('${item.id}')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="hapusData('${item.id}')" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                    });
                } else {
                    cacheKelasUjian = [];
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center">Belum ada data kelas ujian.</td></tr>`;
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function tambahKelasUjian() {
        // 1. Reset form (mengosongkan semua field input standar)
        let form = document.querySelector('#formKelasUjian');
        if (form) form.reset();

        // 2. Kosongkan input hidden ID agar sistem tahu ini mode "Tambah", bukan "Edit"
        let hiddenId = document.querySelector('#id');
        if (hiddenId) hiddenId.value = '';

        // 3. Update judul modal menjadi "Tambah Kelas Ujian"
        let modalTitle = document.querySelector('#modalKelasUjianLabel');
        if (modalTitle) modalTitle.innerText = 'Tambah Kelas Ujian';

        // 4. Reset pilihan metode pemilihan siswa dan sembunyikan wrapper opsionalnya
        let metodePilihEl = document.getElementById('metodePilih');
        if (metodePilihEl) metodePilihEl.value = '';

        let wrapperPleton = document.getElementById('wrapperPleton');
        let wrapperSatuan = document.getElementById('wrapperSatuan');
        if (wrapperPleton) wrapperPleton.style.display = 'none';
        if (wrapperSatuan) wrapperSatuan.style.display = 'none';

        // 5. Muat ulang data untuk dropdown utama
        if (typeof loadDropdownMataPelajaran === 'function') loadDropdownMataPelajaran();
        if (typeof loadDropdownPegawai === 'function') loadDropdownPegawai();
        if (typeof loadDropdownPleton === 'function') loadDropdownPleton();

        // 6. Reset nilai pilihan pada elemen Select2 serta pastikan dropdown terikat di dalam modal
        $('#mata_pelajaran_id').val(null).trigger('change');
        $('#penguji_id').val(null).trigger('change');
        $('#pleton_id').val(null).trigger('change');

        // Inisialisasi ulang Select2 khusus untuk siswa dengan dropdownParent agar posisinya pas di dalam modal
        if ($('#siswa_ids').hasClass('select2-hidden-accessible')) {
            $('#siswa_ids').val(null).trigger('change');
        } else {
            $('#siswa_ids').select2({
                theme: 'bootstrap4',
                dropdownParent: $('#modalKelasUjian'), // <-- Mengatasi masalah dropdown muncul di atas/keluar modal
                placeholder: 'Cari dan pilih siswa (Nama / Nosis)...',
                width: '100%'
            });
        }
        $('#siswa_ids').val(null).trigger('change');

        // 7. Tampilkan modal ke layar
        $('#modalKelasUjian').modal('show');
    }

    function editData(id) {
        let item = cacheKelasUjian.find(d => d.id == id);
        if (!item) {
            alert('Data tidak ditemukan!');
            return;
        }

        // 1. Isi field utama form
        $('#id').val(item.id);
        $('#nama_kelas').val(item.nama_kelas);
        $('#mata_pelajaran_id').val(item.mata_pelajaran_id).trigger('change');
        $('#penguji_id').val(item.penguji_id).trigger('change');

        // 2. Tanggal & Jam Ujian
        if (item.tanggal) $('#tanggal').val(item.tanggal);
        if (item.jam_mulai) $('#jam_mulai').val(item.jam_mulai.substring(0, 5));
        if (item.jam_selesai) $('#jam_selesai').val(item.jam_selesai.substring(0, 5)).trigger('change');

        // 3. Set Metode Pemilihan
        let metodeVal = item.metode_pilih || 'pleton';
        let $metodeEl = $('#metodePilih'); // Pastikan ID ini sesuai dengan HTML Anda
        $metodeEl.val(metodeVal);

        let wrapPleton = document.getElementById('wrapperPleton');
        let wrapSatuan = document.getElementById('wrapperSatuan');
        if (wrapPleton) wrapPleton.style.display = (metodeVal === 'pleton') ? 'block' : 'none';
        if (wrapSatuan) wrapSatuan.style.display = (metodeVal === 'satuan') ? 'block' : 'none';

        // 4. EKSTRAK ID PLETON & AMBIL DATA LANGSUNG DI DALAM EDIT DATA
        if (metodeVal === 'pleton') {
            let pletonIds = [];
            if (item.deskripsi) {
                let cleanStr = item.deskripsi.replace(/Pleton ID:/gi, '').trim();
                if (cleanStr) {
                    pletonIds = cleanStr.split(',').map(s => String(s).trim());
                }
            }

            let cleanBase = (typeof BASE_URL !== 'undefined' ? BASE_URL : '').replace(/\/+$/, '');
            let cleanPrefix = (typeof PREFIX !== 'undefined' ? PREFIX : '').replace(/^\/+|\/+$/g, '');
            let url = `${cleanBase}/${cleanPrefix}/obe/pletonGetData`;

            const $select = $('#pleton_id');

            // Pastikan plugin select2 tersedia sebelum dipanggil
            if (typeof $.fn.select2 === 'undefined') {
                console.error('Plugin Select2 belum dimuat di halaman ini!');
                return;
            }

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    $select.empty();
                    let dataPleton = Array.isArray(res) ? res : (res.data || []);

                    dataPleton.forEach(p => {
                        let idVal = String(p.id ?? p.pleton_id);
                        let namaVal = p.nama_pleton ?? p.nama ?? `Pleton ${p.id}`;

                        cacheNamaPleton[idVal] = namaVal;

                        let option = new Option(namaVal, idVal, false, false);
                        $select.append(option);
                    });

                    // Inisialisasi Select2
                    $select.select2({
                        placeholder: "Cari dan pilih pleton...",
                        width: '100%',
                        allowClear: true,
                        multiple: true,
                        dropdownParent: $('#modalKelasUjian')
                    });

                    // Set nilai yang terpilih
                    if (pletonIds.length > 0) {
                        $select.val(pletonIds).trigger('change');
                    }
                })
                .catch(err => console.log('Gagal memuat data pleton:', err));
        }

        // 5. Status Ujian
        $('#status_ujian').val(item.status_ujian).trigger('change');

        // 6. Tampilkan Modal
        $('#modalKelasUjian').modal('show');
        $('#modalTitle').text('Edit Kelas Ujian');
    }

    function hapusData(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data kelas ujian akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${BASE_URL}/${PREFIX}/obe/kelas-ujian/delete/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.status) {
                            Swal.fire({
                                title: 'Terhapus!',
                                text: res.message || 'Data berhasil dihapus',
                                icon: 'success',
                                timer: 1000,
                                showConfirmButton: false
                            });
                            loadKelasUjian();
                        } else {
                            Swal.fire('Gagal!', res.message || 'Terjadi kesalahan', 'error');
                        }
                    })
                    .catch(err => {
                        console.error("Error:", err);
                        Swal.fire('Gagal!', 'Terjadi kesalahan sistem', 'error');
                    });
            }
        });
    }

    $(document).ready(function() {
        function hitungDurasi() {
            const jamMulai = $('#jam_mulai').val();
            const jamSelesai = $('#jam_selesai').val();

            if (jamMulai && jamSelesai) {
                const [mulaiJam, mulaiMenit] = jamMulai.split(':').map(Number);
                const [selesaiJam, selesaiMenit] = jamSelesai.split(':').map(Number);

                const totalMenitMulai = (mulaiJam * 60) + mulaiMenit;
                const totalMenitSelesai = (selesaiJam * 60) + selesaiMenit;

                let selisihMenit = totalMenitSelesai - totalMenitMulai;

                if (selisihMenit < 0) {
                    $('#durasi_menit').val('Jam selesai tidak boleh kurang dari jam mulai!');
                } else {
                    $('#durasi_menit').val(selisihMenit + ' Menit');
                }
            } else {
                $('#durasi_menit').val('');
            }
        }

        $('#jam_mulai, #jam_selesai').on('change', function() {
            hitungDurasi();
        });
    });
</script>
<?= $this->endSection(); ?>