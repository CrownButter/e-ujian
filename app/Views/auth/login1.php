<!DOCTYPE html>
<html lang="id">

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title><?= $title; ?></title>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <?php if (session()->has('temp_errors')): ?>
                            <div class="alert alert-danger">
                                <?= session()->get('temp_errors') ?>
                            </div>
                            <?php session()->remove('temp_errors'); ?>
                        <?php endif; ?>
                        <h3 class="text-center">Login</h3>
                        <form action="<?= base_url('auth') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" value="<?= set_value('username'); ?>">
                                <span class="text-danger"><?= validation_show_error('username'); ?></span>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" value="<?= set_value('password'); ?>">
                                <span class="text-danger"><?= validation_show_error('password'); ?></span>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Masuk</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>