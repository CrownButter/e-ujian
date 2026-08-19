<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper" style="background-color: bisque;">
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
                <div class="col-12">
                    <div class="card" style="background-color: antiquewhite;">
                        <div class="card-header">
                            <div class="mb-3 row">
                                <label>Pilih Minggu:</label>
                                <select onchange="window.location.href='?minggu='+this.value" class="form-control mx-2" style="width: 200px;">
                                    <?php for ($m = 1; $m <= 16; $m++): ?>
                                        <option value="<?= $m ?>" <?= ($minggu_aktif == $m) ? 'selected' : '' ?>>Minggu Ke-<?= $m ?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="mt-0 ml-1">
                                    <a href="<?= base_url('admin/binplinsis/nilaimental/exportPdf'); ?>" class="btn btn-danger">
                                        <i class="fas fa-file-pdf"></i> Export PDF
                                    </a>
                                </div>
                            </div>
                            <div class="card-header p-2">
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($pleton_id_aktif == 'All') ? 'active' : '' ?>"
                                            href="<?= site_url($baseUrl . '?minggu=' . $minggu_aktif . '&pleton=All&pleton_id=All') ?>">All</a>
                                    </li>
                                    <?php foreach ($pleton_list as $p): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?= ($pleton_id_aktif == $p['id']) ? 'active' : '' ?>"
                                                href="<?= site_url($baseUrl . '?minggu=' . $minggu_aktif . '&pleton=' . urlencode($p['nama_pleton']) . '&pleton_id=' . $p['id']) ?>">
                                                <?= esc($p['nama_pleton']) ?>
                                                <span class="badge badge-info ml-1"><?= $counts[$p['id']] ?? 0 ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <form action="<?= site_url('binplinsis/prosesVerifikasi/') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="minggu_ke" value="<?= $minggu_aktif ?>">

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center" style="font-size: 12px;">
                                    <!-- Ganti bagian thead Anda dengan ini -->
                                    <thead class="bg-info">
                                        <tr>
                                            <th rowspan="2" style="vertical-align: middle;">NO</th>
                                            <th rowspan="2" style="vertical-align: middle;">NOSIS</th>
                                            <th colspan="3">SPIRITUAL</th>
                                            <th colspan="3">IDEOLOGI</th>
                                            <th colspan="4">KEJUANGAN</th>
                                            <th colspan="4">WATAK</th>
                                            <th colspan="8">KEPEMIMPINAN</th> <!-- Pastikan total colspan sesuai jumlah kolom -->
                                            <th rowspan="2">JML SKOR</th>
                                            <th rowspan="2">JML HSL PENGAMATAN</th>
                                            <th rowspan="2">NILAI KONVERSI</th>
                                            <th colspan="2">TIND DILUAR INDIKATOR</th>
                                            <th rowspan="2">NILAI AKHIR</th>
                                            <th rowspan="2">AKSI</th>
                                        </tr>
                                        <tr>
                                            <!-- Baris kedua ini harus berjumlah 22 untuk mencakup input skor -->
                                            <?php for ($i = 1; $i <= 22; $i++): ?>
                                                <th><?= $i ?></th>
                                            <?php endfor; ?>
                                            <th>-</th>
                                            <th>+</th>
                                        </tr>
                                    </thead>

                                    <tbody class="bg-light">
                                        <?php $no = 1;
                                        foreach ($list_penilaian as $index => $s): ?>
                                            <?php
                                            $n = $nilai_map[$s['id']] ?? [];
                                            $skor = array_merge(
                                                json_decode($n['skor_spiritual'] ?? '[]', true) ?: [],
                                                json_decode($n['skor_ideologi'] ?? '[]', true) ?: [],
                                                json_decode($n['skor_kejuangan'] ?? '[]', true) ?: [],
                                                json_decode($n['skor_watak'] ?? '[]', true) ?: [],
                                                json_decode($n['skor_kepemimpinan'] ?? '[]', true) ?: []
                                            );
                                            ?>
                                            <tr data-pleton="<?= esc($s['nama_pleton'] ?? '') ?>">
                                                <td style="vertical-align: middle;"><?= $index + 1 ?></td>
                                                <td style="font-size: 15px; vertical-align: middle;"><?= esc($s['nosis']) ?></td>

                                                <?php for ($i = 0; $i < 22; $i++): ?>
                                                    <td style="vertical-align: middle;">
                                                        <input type="number" class="form-control form-control-sm mx-auto text-center"
                                                            value="<?= $skor[$i] ?? 0 ?>" readonly style="width: 45px;">
                                                    </td>
                                                <?php endfor; ?>

                                                <td style="vertical-align: middle;"><b id="jml_<?= $s['id'] ?>"><?= $n['jml_skor'] ?? 0 ?></b></td>
                                                <td style="vertical-align: middle;"><b id="pengamatan_<?= $s['id'] ?>"><?= $n['jml_hsl_pengamatan'] ?? 0 ?></b></td>
                                                <td style="vertical-align: middle;"><b id="konversi_<?= $s['id'] ?>"><?= $n['nilai_konversi'] ?? 0 ?></b></td>

                                                <td style="vertical-align: middle;">
                                                    <input type="number" id="minus_<?= $s['id'] ?>"
                                                        value="<?= $n['tind_diluar_minus'] ?? 0 ?>"
                                                        class="form-control form-control-sm mx-auto text-center"
                                                        oninput="hitungTotal(<?= $s['id'] ?>)" style="width: 50px;" readonly>
                                                </td>
                                                <td style="vertical-align: middle;">
                                                    <input type="number"
                                                        id="plus_<?= $s['id'] ?>"
                                                        value="<?= $n['tind_diluar_plus'] ?? 0 ?>"
                                                        class="form-control form-control-sm mx-auto text-center <?= (($n['status_danki'] ?? '0') == '1') ? 'bg-secondary' : '' ?>"
                                                        oninput="hitungTotal(<?= $s['id'] ?>)"
                                                        style="width: 50px;"
                                                        <?= (($n['status_danki'] ?? '0') == '1') ? 'disabled' : '' ?> readonly>
                                                </td>
                                                <td style="vertical-align: middle;"><b id="akhir_<?= $s['id'] ?>"><?= $n['nilai_akhir'] ?? 0 ?></b></td>
                                                <td style="vertical-align: middle;">
                                                    <?php
                                                    $role_id = session()->get('role_id');
                                                    echo "Role: " . $role_id . " | ";
                                                    echo "ID: " . ($n['id'] ?? 'NULL');
                                                    ?>

                                                    <?php if ($role_id == 4 || $role_id == 5): ?>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-primary btn-verif"
                                                                data-id="<?= $n['id'] ?? 0 ?>"
                                                                data-role="<?= ($role_id == 5) ? 'danyon' : 'danki' ?>">Verif</button>
                                                            <button type="button" class="btn btn-sm btn-danger btn-tolak"
                                                                data-id="<?= $n['id'] ?? 0 ?>"
                                                                data-role="<?= ($role_id == 5) ? 'danyon' : 'danki' ?>">Tolak</button>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-danger">Role Salah</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Box -->
                            <!-- Pagination Box -->
                            <div class="d-flex justify-content-center mt-3">
                                <?php if (isset($pager)) : ?>
                                    <nav aria-label="Page navigation">
                                        <?= $pager->links('default', 'bootstrap_full') ?>
                                    </nav>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer bg-white text-center">
                                <?php if ($role_id == 5): // Jika Danki 
                                ?>
                                    <button type="button" class="btn btn-success"
                                        onclick="kirimData('danyon', '<?= $pleton_id_aktif ?>', '<?= $minggu_aktif ?>')">
                                        Kirim ke Danyon
                                    </button>
                                <?php elseif ($role_id == 4): // Jika Danton 
                                ?>
                                    <button type="button" class="btn btn-success"
                                        onclick="kirimData('danki', '<?= $pleton_id_aktif ?>', '<?= $minggu_aktif ?>')">
                                        Kirim ke Danki
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    // FUNGSI GLOBAL
    function kirimData(target, pleton_id, minggu) {
        if (!confirm('Apakah Anda yakin ingin melanjutkan proses pengiriman ini?')) return;

        var url = (target === 'danyon') ?
            '<?= site_url('danki/binplinsis/nilaimental/kirimMassalKeDanyon') ?>' :
            '<?= site_url('pleton/binplinsis/nilaimental/kirimkeDanki') ?>';

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json', // Memastikan response dianggap sebagai JSON
            data: {
                pleton_id: pleton_id,
                minggu: minggu,
                "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
            },
            success: function(response) {
                // Memeriksa apakah status sukses ada dalam response
                if (response.success) {
                    alert(response.message || 'Data berhasil dikirim.');
                    location.reload();
                } else {
                    alert('Gagal: ' + (response.message || 'Terjadi kesalahan sistem.'));
                }
            },
            error: function(xhr) {
                // Mencoba menangani error JSON jika ada, jika tidak tampilkan status
                var errorMessage = "Terjadi kesalahan pada server.";
                try {
                    var res = JSON.parse(xhr.responseText);
                    errorMessage = res.message || errorMessage;
                } catch (e) {
                    errorMessage = "Status: " + xhr.status + " - " + xhr.statusText;
                }
                alert('Gagal: ' + errorMessage);
            }
        });
    }

    // FUNGSI LOKAL
    $(document).ready(function() {

        $('#tablePenilaian').DataTable({
            "paging": false,
            "lengthChange": false,
            "searching": false,
            "ordering": false,
            "info": false,
            "autoWidth": false,
            "responsive": false,
            "scrollX": true
        });

        // 2. Fungsi Verifikasi dengan SweetAlert2
        function kirimStatus(id, status) {
            var targetUrl = "<?= site_url('danki/binplinsis/dankiVerifikasi') ?>";
            var actionText = (status === '1') ? 'Verifikasi' : 'Tolak';

            Swal.fire({
                title: 'Konfirmasi',
                text: "Anda yakin ingin " + actionText + " data ini?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Lanjutkan!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: targetUrl,
                        type: "POST",
                        data: {
                            id: id,
                            status: status,
                            "<?= csrf_token() ?>": "<?= csrf_hash() ?>"
                        },
                        success: function(response) {
                            Swal.fire('Berhasil!', 'Status berhasil diupdate.', 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal!', 'Terjadi kesalahan sistem.', 'error');
                            console.log(xhr.responseText);
                        }
                    });
                }
            });
        }


        // 3. Event Listener
        $(document).on('click', '.btn-verif', function() {
            if ($(this).data('id') !== 0) {
                kirimStatus($(this).data('id'), '1');
            } else {
                alert("Data nilai belum ada, tidak bisa diverifikasi.");
            }
        });

        $(document).on('click', '.btn-tolak', function() {
            if ($(this).data('id') !== 0) {
                kirimStatus($(this).data('id'), '0');
            } else {
                alert("Data nilai belum ada, tidak bisa ditolak.");
            }
        });
    });
</script>

<script>
    function filterPleton(pletonNama, element) {
        // 1. UI Tab Active
        document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
        element.classList.add('active');

        // 2. Filter Tabel
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const rowPleton = row.getAttribute('data-pleton');
            row.style.display = (pletonNama === '' || rowPleton === pletonNama) ? '' : 'none';
        });

        // 3. Simpan pilihan ke server via AJAX agar PDF tahu Pleton apa yang dipilih
        fetch("<?= base_url('admin/binplinsis/savePletonSession'); ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "pleton=" + encodeURIComponent(pletonNama)
        });
    }
</script>
<?= $this->endsection(); ?>