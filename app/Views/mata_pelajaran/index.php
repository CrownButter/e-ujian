<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $page_title ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <!-- Tombol Pemicu Modal Tambah -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambahMapel">
                        <i class="fas fa-plus"></i> Tambah Mata Pelajaran
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table id="tabelMapel" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">No</th>
                                <th>Kode Mapel</th>
                                <th>Nama Mata Pelajaran</th>
                                <th>Gadik Pengampu</th>
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($mata_pelajaran)): ?>
                                <?php foreach ($mata_pelajaran as $index => $row): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><span class="badge badge-info"><?= esc($row['kode_mapel']) ?></span></td>
                                        <td><?= esc($row['nama_mapel']) ?></td>
                                        <td>
                                            <?php if (!empty($row['nama_gadik'])): ?>
                                                <?= (!empty($row['nama_pangkat']) ? esc($row['nama_pangkat']) . ' - ' : '') . esc($row['nama_gadik']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Belum ditentukan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Tombol Edit (Memicu Modal Edit) -->
                                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditMapel<?= $row['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Tombol Delete -->
                                            <button type="button" class="btn btn-danger btn-sm" onclick="hapusMapel('<?= $row['id'] ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Tambah Mata Pelajaran -->
<div class="modal fade" id="modalTambahMapel" tabindex="-1" role="dialog" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="<?= base_url('admin/mata_pelajaran/tambah_mapel') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Mata Pelajaran Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="kode_mapel">Kode Mata Pelajaran</label>
                        <input type="text" name="kode_mapel" id="kode_mapel" class="form-control" placeholder="Contoh: MTK" required>
                    </div>
                    <div class="form-group">
                        <label for="nama_mapel">Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" id="nama_mapel" class="form-control" placeholder="Contoh: Matematika" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Mata Pelajaran (Dilooping sesuai data) -->
<?php if (!empty($mata_pelajaran)): ?>
    <?php foreach ($mata_pelajaran as $row): ?>
        <div class="modal fade" id="modalEditMapel<?= $row['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel<?= $row['id'] ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="<?= base_url('admin/mata_pelajaran/update_mapel/' . $row['id']) ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalEditLabel<?= $row['id'] ?>">Edit Mata Pelajaran</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Kode Mata Pelajaran</label>
                                <input type="text" name="kode_mapel" class="form-control" value="<?= esc($row['kode_mapel']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Nama Mata Pelajaran</label>
                                <input type="text" name="nama_mapel" class="form-control" value="<?= esc($row['nama_mapel']) ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>

<!-- Tambahan Script DataTables & SweetAlert Delete -->
<?= $this->section('script') ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function() {
        $("#tabelMapel").DataTable({
            "responsive": true,
            "autoWidth": false,
        });
    });

    function hapusMapel(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data mata pelajaran yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?= base_url('admin/mata_pelajaran/delete_mapel/') ?>" + id;
            }
        });
    }
</script>
<?= $this->endSection() ?>