<?= $this->extend('layout/main'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tambah Periode</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url($prefix . '/monitoringperiode') ?>">Daftar Periode</a></li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0" style="color: #fff;"><i class="fas fa-calendar-plus"></i> Buat Periode Baru</h5>
                            <div class="card-tools">
                                <a href="<?= base_url($prefix . '/monitoringperiode') ?>" class="btn btn-sm btn-light text-primary"><i class="fas fa-arrow-left"></i> Kembali</a>
                            </div>
                        </div>
                        <div class="card-body p-4">

                            <?php if (session()->getFlashdata('errors')): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                            <li><?= esc($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form action="<?= base_url($prefix . '/monitoringperiode/store') ?>" method="post">
                                <?= csrf_field() ?>

                                <div class="form-group mb-3">
                                    <label for="angkatan_id" class="form-label fw-bold font-weight-bold">Pilih Angkatan <span class="text-danger">*</span></label>
                                    <select name="angkatan_id" id="angkatan_id" class="form-control select2" required>
                                        <option value="">-- Pilih Angkatan --</option>
                                        <?php foreach ($list_angkatan as $ang): ?>
                                            <option value="<?= $ang['id'] ?>" <?= old('angkatan_id') == $ang['id'] ? 'selected' : '' ?>>
                                                <?= esc($ang['nama_angkatan']) ?> (T.A. <?= esc($ang['tahun_angkatan']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="minggu_ke" class="form-label fw-bold font-weight-bold">Minggu Ke- <span class="text-danger">*</span></label>
                                    <input type="number" name="minggu_ke" id="minggu_ke" class="form-control" min="1" placeholder="Contoh: 1, 2, atau 3" value="<?= old('minggu_ke') ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="periode_awal" class="form-label fw-bold font-weight-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                                            <input type="date" name="periode_awal" id="periode_awal" class="form-control" value="<?= old('periode_awal') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="periode_akhir" class="form-label fw-bold font-weight-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                                            <input type="date" name="periode_akhir" id="periode_akhir" class="form-control" value="<?= old('periode_akhir') ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="status" class="form-label fw-bold font-weight-bold">Status Periode <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="Draft" <?= old('status') == 'Draft' ? 'selected' : '' ?>>Draft (Masih dalam pengisian)</option>
                                        <option value="Final" <?= old('status') == 'Final' ? 'selected' : '' ?>>Final (Terkunci / Siap Cetak)</option>
                                    </select>
                                </div>

                                <hr>

                                <div class="text-right">
                                    <button type="reset" class="btn btn-secondary mr-2"><i class="fas fa-undo"></i> Reset</button>
                                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save"></i> Simpan Periode</button>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<?= $this->endsection(); ?>