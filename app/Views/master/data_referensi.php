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
        <div class="col-12">
            <div class="col-md">

                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab">Angkatan</a></li>
                            <li class="nav-item"><a class="nav-link" href="#batalyon" data-toggle="tab">Batalyon</a></li>
                            <li class="nav-item"><a class="nav-link" href="#kompi" data-toggle="tab">Kompi</a></li>
                            <li class="nav-item"><a class="nav-link" href="#pleton" data-toggle="tab">Pleton</a></li>
                        </ul>
                    </div><!-- /.card-header -->
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="active tab-pane" id="activity">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#angkatanModal">
                                        <i class="fas fa-plus"></i> Tambah Angkatan
                                    </button>
                                </div>
                                <div class="card">
                                    <div class="card-header bg-primary">
                                        <h3 class="card-title">Angkatan</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body">
                                        <table id="tabelAngkatan" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Nama Angkatan</th>
                                                    <th>Mulai</th>
                                                    <th>Beakhir</th>
                                                    <th>Tahun Peddikan</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($angkatan as $ak): ?>
                                                    <tr>
                                                        <td><?= $ak['nama_angkatan']; ?></td>
                                                        <td><?= tgl_indo($ak['tanggal_mulai']); ?></td>
                                                        <td><?= tgl_indo($ak['tanggal_berakhir']); ?></td>
                                                        <td><?= $ak['tahun_angkatan']; ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-success btn-sm btn-edit-item" data-toggle="modal" data-target="#editangkatanModal<?= $ak['id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-danger btn-sm btn-delete-item"
                                                                data-url="<?= base_url('admin/master/deleteAngkatan/' . $ak['id']) ?>"
                                                                data-label="Angkatan">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-pane -->
                            <div class="tab-pane" id="batalyon">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalBatalyon">
                                        <i class="fas fa-plus"></i> Tambah Batalyon
                                    </button>
                                </div>
                                <div class="card">
                                    <div class="card-header bg-success">
                                        <h3 class="card-title">Batalyon</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body">
                                        <table id="tabelBatalyon" class="table table-bordered table-striped">
                                            <!-- Bagian Header Tabel (Tambahkan <th> Pangkat) -->
                                            <thead>
                                                <tr>
                                                    <th>Nama Batalyon</th>
                                                    <th>Nama Danyon</th>
                                                    <th>Pangkat</th> <!-- Kolom Baru -->
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>

                                            <!-- Bagian Body Tabel -->
                                            <tbody>
                                                <?php if (!empty($batalyon)): ?>
                                                    <?php foreach ($batalyon as $b): ?>
                                                        <tr>
                                                            <td><?= $b['nama_batalyon'] ?></td>

                                                            <!-- Nama Danyon -->
                                                            <td> <?= !empty($b['nama_pangkat']) ? $b['nama_pangkat'] : '<span class="text-muted">-</span>' ?>. <?= $b['nama_danyon'] ?? '<span class="text-muted">Belum ada</span>' ?></td>

                                                            <!-- Pangkat Danyon (Baru) -->
                                                            <td>
                                                                <?= !empty($b['nama_pangkat']) ? $b['nama_pangkat'] : '<span class="text-muted">-</span>' ?>
                                                            </td>

                                                            <td>
                                                                <button type="button" class="btn btn-success btn-sm btn-edit-item" data-toggle="modal" data-target="#modalEditBatalyon<?= $b['id']; ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm btn-delete-item"
                                                                    data-url="<?= base_url('admin/master/deleteBatalyon/' . $b['id']) ?>"
                                                                    data-label="Batalyon">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4">Data Batalyon belum tersedia.</td> <!-- Ubah colspan jadi 4 karena ada 4 kolom -->
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.card-body -->
                                </div>
                            </div>
                            <!-- /.tab-pane -->

                            <div class="tab-pane" id="kompi">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalKompi">
                                        <i class="fas fa-plus"></i> Tambah Kompi
                                    </button>
                                </div>
                                <div class="card card-danger">
                                    <div class="card-header">Data Kompi</div>
                                    <div class="card-body">
                                        <table id="tabelKompi" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Nama Kompi</th>
                                                    <th>Danki</th>
                                                    <th>Batalyon</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($kompi)): ?>
                                                    <?php foreach ($kompi as $k): ?>
                                                        <tr>
                                                            <td><?= ($k['nama_kompi']) ?></td>
                                                            <td><?= ($k['nama_pangkat']) ?>. <?= ($k['nama_danki'] ?? 'Belum diset') ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                // Jika nama_batalyon ada, tampilkan langsung
                                                                if (!empty($k['nama_batalyon'])):
                                                                ?>
                                                                    <?= htmlspecialchars($k['nama_batalyon']) ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Belum ada batalyon</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-success btn-sm btn-edit-item" data-toggle="modal" data-target="#editmodalKompi<?= $k['id']; ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm btn-delete-item"
                                                                    data-url="<?= base_url('admin/master/deleteKompi/' . $k['id']) ?>"
                                                                    data-label="Kompi">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Data Kompi belum tersedia.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-pane -->
                            <div class="tab-pane" id="pleton">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalPleton">
                                        <i class="fas fa-plus"></i> Tambah Pleton
                                    </button>
                                </div>
                                <div class="card card-info">
                                    <div class="card-header">Pleton</div>
                                    <div class="card-body">
                                        <table id="tabelPleton" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Pleton</th>
                                                    <th>Danton</th>
                                                    <th>Kompi</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($pleton)): ?>
                                                    <?php foreach ($pleton as $p): ?>
                                                        <tr>
                                                            <td class="fw-bold text-wrap"><?= $p['nama_pleton'] ?>
                                                            </td>
                                                            <td><?= $p['nama_pangkat'] ?>. <?= $p['nama_danton'] ?>
                                                            </td>
                                                            <td><?= $p['nama_kompi'] ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-success btn-sm btn-edit-item" data-toggle="modal" data-target="#editPleton<?= $p['id']; ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm btn-delete-pleton"
                                                                    data-url="<?= base_url('admin/master/deletePleton/' . $p['id']) ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Data pleton belum tersedia.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.tab-content -->
                    </div><!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
    </section>
    <!-- /.content -->

    <!-- Modaladd -->

    <div class="modal fade" id="angkatanModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/master/storeAngkatan') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalLabel">Tambah Angkatan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Angkatan</label>
                            <input type="text" name="nama_angkatan" class="form-control" placeholder="Contoh: ANGKATAN 59" required>
                        </div>
                        <div class="form-group">
                            <label>Tahun Angkatan</label>
                            <input type="text" name="tahun_angkatan" class="form-control" value="<?= date('Y'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Mulai Pendidikan</label>
                            <input type="date" name="tanggal_mulai" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Berakhir Pendidikan</label>
                            <input type="date" name="tanggal_berakhir" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php foreach ($angkatan as $ak): ?>
        <div class="modal fade" id="editangkatanModal<?= $ak['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="<?= base_url('admin/master/updateAngkatan/') . $ak['id']; ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h5 class="modal-title" id="userModalLabel">Edit Angkatan</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Angkatan</label>
                                <input type="text" name="nama_angkatan" class="form-control" value="<?= $ak['nama_angkatan']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Tahun Angkatan</label>
                                <input type="text" name="tahun_angkatan" class="form-control" value="<?= $ak['tahun_angkatan']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Mulai Pendidikan</label>
                                <input type="date" name="tanggal_mulai" class="form-control" value="<?= $ak['tanggal_mulai']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tanggal Berakhir Pendidikan</label>
                                <input type="date" name="tanggal_berakhir" class="form-control" value="<?= $ak['tanggal_berakhir']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="1" <?= ($ak['status'] == '1') ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?= ($ak['status'] == '0') ? 'selected' : ''; ?>>Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="modal fade" id="modalPleton" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/master/tambahPleton') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Pleton</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Pilih Kompi</label>
                            <select name="kompi_id" class="form-control" required>
                                <?php foreach ($kompi as $k): ?>
                                    <option value="<?= $k['id'] ?>"><?= $k['nama_kompi'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nama Pleton</label>
                            <input type="text" name="nama_pleton" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Pilih Danton</label>
                            <select name="danton_id" class="form-control">
                                <option value="">-- Pilih Danton --</option>
                                <?php foreach ($danton as $d): ?>
                                    <option value="<?= $d['nomor_induk'] ?>"><?= $d['nama'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- editPleton -->
    <?php foreach ($pleton as $p): ?>
        <div class="modal fade" id="editPleton<?= $p['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="<?= base_url('admin/master/updatePleton/') . $p['id'] ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Pleton</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Pilih Kompi</label>
                                <select name="kompi_id" class="form-control" required>
                                    <?php foreach ($kompi as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= ($k['id'] == $p['kompi_id']) ? 'selected' : '' ?>>
                                            <?= $k['nama_kompi'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nama Pleton</label>
                                <input type="text" name="nama_pleton" class="form-control" value="<?= $p['nama_pleton'] ?>" required>
                            </div>
                            <div class="form-group">
                                <select name="danton_id" class="form-control">
                                    <option value="">-- Pilih Danton --</option>
                                    <?php foreach ($pegawaiAll as $d): ?>
                                        <?php

                                        $selected = ($d['nomor_induk'] == $p['danton_id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= $d['nomor_induk'] ?>" <?= $selected ?>>
                                            <?= $d['nomor_induk'] ?> - <?= $d['nama'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- kompi -->
    <div class="modal fade" id="modalKompi" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/master/storeKompi') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Kompi</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Pilih Batalyon</label>
                            <select name="batalyon_id" class="form-control" required>
                                <?php foreach ($batalyonSedia as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= $b['nama_batalyon'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nama Kompi</label>
                            <input type="text" name="nama_kompi" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Pilih Danki</label>
                            <select name="danki_id" class="form-control select2bs5" style="width: 100%;" required>
                                <option value="">-- Cari Nama Danki --</option>
                                <?php foreach ($danki as $d): ?>
                                    <option value="<?= $d['nomor_induk'] ?>"><?= $d['nama'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Kompi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php foreach ($kompi as $k): ?>
        <div class="modal fade" id="editmodalKompi<?= $k['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="<?= base_url('admin/master/updateKompi/' . $k['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Kompi</h5>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Pilih Batalyon</label>
                                <select name="batalyon_id" class="form-control" required>
                                    <?php foreach ($batalyonSedia as $b): ?>
                                        <option value="<?= $b['id'] ?>"><?= $b['nama_batalyon'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nama Kompi</label>
                                <input type="text" name="nama_kompi" class="form-control" value="<?= $k['nama_kompi']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Pilih Danki</label>
                                <select name="danki_id" class="form-control select2bs5" required>
                                    <option value="">-- Cari Nama Danki --</option>
                                    <?php foreach ($pegawaiAll as $d): ?>
                                        <option value="<?= $d['nomor_induk']; ?>"
                                            <?= ($d['nomor_induk'] == $k['danki_id']) ? 'selected' : ''; ?>>
                                            <?= $d['nomor_induk'] ?> - <?= $d['nama']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan Kompi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- batalyon -->
    <div class="modal fade" id="modalBatalyon" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="<?= base_url('admin/master/storeBatalyon') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Batalyon</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Batalyon</label>
                            <input type="text" name="nama_batalyon" class="form-control" placeholder="Contoh: Batalyon Taruna 1" required>
                        </div>
                        <div class="form-group">
                            <label>Pilih Danyon</label>
                            <select name="danyon_id" class="form-control select2bs4" style="width: 100%;" required>
                                <option value="">-- Cari Nama Danyon --</option>
                                <?php foreach ($danyon as $d): ?>
                                    <option value="<?= $d['nomor_induk'] ?>"><?= $d['nama'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Batalyon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit batalyon -->
    <?php foreach ($batalyon as $b): ?>
        <div class="modal fade" id="modalEditBatalyon<?= $b['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="<?= base_url('admin/master/updateBatalyon/' . $b['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Batalyon</h5>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Batalyon</label>
                                <input type="text" name="nama_batalyon" class="form-control" value="<?= $b['nama_batalyon'] ?>">
                            </div>
                            <div class="form-group">
                                <label>Pilih Danyon</label>
                                <select name="danyon_id" class="form-control select2bs4b" style="width: 100%;" required>
                                    <option value="">-- Cari Nama Danyon --</option>
                                    <?php foreach ($pegawaiAll as $d): ?>
                                        <option value="<?= $d['nomor_induk'] ?>" <?= ($d['nomor_induk'] == $b['danyon_id']) ? 'selected' : '' ?>>
                                            <?= $d['nomor_induk'] ?> - <?= htmlspecialchars($d['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Simpan Batalyon</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>


<?= $this->endsection(); ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        // 1. Inisialisasi DataTable & Select2
        const table = $('#tabelSiswa').DataTable({
            responsive: true,
            lengthChange: false,
            autoWidth: false,
            pageLength: 100
        });

        $('.select2bs4').select2({
            theme: 'bootstrap5',
            dropdownParent: $('#modalBatalyon')
        });
        $('.modal').on('shown.bs.modal', function() {
            $(this).find('.select2bs5').select2({
                theme: 'bootstrap5',
                dropdownParent: $(this)
            });
        });

        // 2. Notifikasi Sukses (Hapus Pleton / Hapus Siswa)
        <?php if (session()->getFlashdata('pesan')): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '<?= session()->getFlashdata('pesan') ?>',
                timer: 2000,
                showConfirmButton: false
            });
        <?php endif; ?>

        // 3. Logic Hapus Pleton (SweetAlert)
        $(document).on('click', '.btn-delete-pleton', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            Swal.fire({
                title: 'Hapus Pleton?',
                text: "Data ini tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    window.location.href = url;
                }
            });
        });
        // --- GENERIC DELETE (Angkatan, Batalyon, Kompi) ---
        $(document).on('click', '.btn-delete-item', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const label = $(this).data('label') || 'Data';

            Swal.fire({
                title: 'Hapus ' + label + '?',
                text: "Data ini tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    window.location.href = url;
                }
            });
        });

        // --- EDIT MODAL HANDLER (Opsional: Jika ingin loading saat edit) ---
        $(document).on('click', '.btn-edit', function() {
            // Jika Anda menggunakan modal untuk edit, Anda bisa memicu loading di sini
            console.log("Edit button clicked");
        });
        // 6. Logic Loading Form Submit
        $('form').on('submit', function() {
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Loading...');
        });
    });
</script>

<?= $this->endsection(); ?>