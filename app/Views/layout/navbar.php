<nav>
    <a href="/dashboard">Dashboard</a>

    <?php if (session()->get('role_id') == 1): ?>
        <a href="/admin/users">Manajemen User</a>
    <?php endif; ?>

    <?php if (in_array(session()->get('role_id'), [3, 4, 5, 6])): ?>
        <a href="/akademik/nilai">Input Nilai</a>
    <?php endif; ?>

    <?php if (session()->get('role_id') == 7): ?>
        <a href="/nilai-saya">Lihat Nilai Saya</a>
    <?php endif; ?>

    <a href="/logout">Logout</a>
</nav>

