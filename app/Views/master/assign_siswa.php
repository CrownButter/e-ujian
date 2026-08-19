<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $title; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard'); ?>">Home</a></li>
                        <li class="breadcrumb-item active"><?= $title; ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <!-- Tabel Siswa -->
                <div class="col-md-6">
                    <table class="table table-bordered table-striped" id="tabelSiswaAsal">
                        <thead>
                            <tr>
                                <th width="10">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th width="10">No</th>
                                <th width="100">Nosis</th>
                                <th>Nama Siswa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($siswa_list as $s): ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="checkSiswa"
                                            value="<?= $s['id']; ?>">
                                    </td>
                                    <!-- Menampilkan Nosis -->
                                    <td><?= $no++; ?></td>
                                    <td><?= esc($s['nosis']); ?></td>
                                    <!-- Menampilkan Nama Siswa -->
                                    <td><?= esc($s['nama']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button
                        type="button"
                        class="btn btn-primary mt-2"
                        onclick="pindahkanKeKanan()">
                        Pindahkan >>
                    </button>

                </div>

                <!-- Tujuan Pleton -->
                <div class="col-md-6">

                    <form id="formPleton"
                        action="<?= base_url('admin/siswa/saveAssignSiswa') ?>"
                        method="post">

                        <?= csrf_field() ?>

                        <div class="form-group">

                            <label>Pleton Tujuan</label>

                            <select
                                name="pleton_id"
                                id="pleton_id"
                                class="form-control"
                                required>

                                <option value="">-- Pilih Pleton --</option>

                                <?php foreach ($pleton_list as $p): ?>

                                    <option value="<?= $p['id']; ?>">
                                        <?= esc($p['nama_pleton']); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <table class="table table-bordered mt-3" id="tabelPletonTujuan">
                            <thead>
                                <tr>
                                    <th>Nama Siswa</th>
                                </tr>
                            </thead>

                            <tbody>

                            </tbody>

                        </table>

                        <button type="button" id="btnSimpan" class="btn btn-success">
                            Simpan
                        </button>

                    </form>

                </div>

            </div>
        </div>
    </section>
</div>


<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    $(function() {
        // Inisialisasi DataTables dengan limit 25 data per halaman
        $("#tabelSiswaAsal").DataTable({
            "paging": true,
            "lengthChange": true,
            "pageLength": 25,
            "lengthMenu": [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });

        $("#tabelPletonTujuan").DataTable({
            "paging": true,
            "lengthChange": false,
            "pageLength": 25,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });
    });

    function toggleFields() {
        let role = document.getElementById('role_id').value;
        document.getElementById('siswaFields').style.display = (role == 7) ? 'block' : 'none';
        document.getElementById('pegawaiFields').style.display = (role != 7 && role != "") ? 'block' : 'none';
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        // PERUBAHAN: Select All hanya mencentang baris pada halaman aktif (current page) saja
        document.getElementById("selectAll").addEventListener("change", function() {
            let isChecked = this.checked;
            let table = $('#tabelSiswaAsal').DataTable();

            // Hanya ambil nodes/baris yang sedang aktif terlihat di halaman saat ini
            table.rows({
                page: 'current'
            }).nodes().to$().find('.checkSiswa').each(function() {
                this.checked = isChecked;
            });
        });

    });

    function pindahkanKeKanan() {
        let table = $('#tabelSiswaAsal').DataTable();

        // Ambil semua checkbox yang dicentang di seluruh tabel
        let checked = table.$('.checkSiswa:checked');

        if (checked.length == 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Pilih minimal satu siswa.'
            });
            return;
        }

        let tbody = document.querySelector("#tabelPletonTujuan tbody");

        checked.each(function() {
            let cb = this;
            let row = $(cb).closest("tr");
            let dataRow = table.row(row);

            // Sesuaikan indeks kolom: 
            // 0 = Checkbox, 1 = No, 2 = Nosis, 3 = Nama Siswa
            let nosis = $(row).find('td:eq(2)').text();
            let nama = $(row).find('td:eq(3)').text();
            let id = cb.value;

            // Masukkan ke tabel tujuan (menampilkan Nosis dan Nama agar jelas)
            tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>
                        [${nosis}] ${nama}
                        <input type="hidden" name="siswa_ids[]" value="${id}">
                    </td>
                </tr>
            `);

            // Hapus baris dari DataTables asal secara dinamis
            dataRow.remove();
        });

        // Redraw tabel asal agar urutan/paging memperbarui diri
        table.draw();

        // Reset checkbox "Select All"
        document.getElementById("selectAll").checked = false;
    }

    document.getElementById("btnSimpan").addEventListener("click", function() {
        let pleton = document.getElementById("pleton_id").value;
        let jumlah = document.querySelectorAll("input[name='siswa_ids[]']").length;

        if (pleton == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Pleton terlebih dahulu'
            });
            return;
        }

        if (jumlah == 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Belum ada siswa dipilih'
            });
            return;
        }

        document.getElementById("formPleton").submit();
    });
</script>
<?= $this->endsection(); ?>