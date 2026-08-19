<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

class UserController extends BaseController
{
    protected $userModel;
    protected $siswaModel;
    protected $pegawaiModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $roleModel = new RoleModel();
    }

    public function index()
    {
        $userModel = new \App\Models\UserModel();
        $roleModel = new \App\Models\RoleModel();
        $data = [
            'title' => 'Dashboard',
            'users' => $userModel->getUserWithRole(),
            'roles' => $roleModel->findAll()
        ];

        return view('users/index', $data);
    }

    public function create()
    {
        $roleModel = new \App\Models\RoleModel();
        $data = [
            'roles' => $roleModel->findAll()
        ];
        return view('admin/users/create', $data);
    }

    public function store()
    {
        // 1. Validasi Input
        $rules = [
            'username' => 'required|is_unique[users.username]|min_length[3]',
            'password' => 'required|min_length[6]',
            'role_id'  => 'required'
        ];

        if (!$this->validate($rules)) {
            // Jika validasi gagal, kembali ke halaman sebelumnya dengan pesan error
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // 2. Jika lolos validasi, proses penyimpanan
        $role = $this->request->getPost('role_id');

        $userData = [
            'username' => $this->request->getPost('username'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role_id'  => $role
        ];

        // Persiapkan data profil (sesuaikan dengan field di DB)
        $profileData = ($role == 7) ? [
            'nama'  => $this->request->getPost('nama'),
            'nosis' => $this->request->getPost('nosis')
        ] : [
            'nama'        => $this->request->getPost('nama'),
            'nomor_induk' => $this->request->getPost('nomor_induk')
        ];

        // 3. Simpan ke Model
        if ($this->userModel->createUserWithProfile($userData, $profileData, $role)) {
            return redirect()->to('/admin/users')->with('message', 'User berhasil ditambahkan!');
        } else {
            return redirect()->back()->with('error', 'Gagal menyimpan ke database.');
        }
    }

    public function delete($id)
    {
        $userModel = new \App\Models\UserModel();

        // Keamanan: Cek keberadaan user
        $user = $userModel->find($id);
        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User tidak ditemukan.');
        }

        // Proses hapus
        if ($userModel->delete($id)) {
            return redirect()->to('/admin/users')->with('success', 'User berhasil dihapus.');
        } else {
            return redirect()->to('/admin/users')->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    public function deleteMassal()
    {
        $ids = $this->request->getPost('id_users'); // Sesuai name di input checkbox

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Pilih minimal satu data.');
        }

        $userModel = new \App\Models\UserModel();

        // Hapus satu per satu untuk memastikan trigger/model logic berjalan
        foreach ($ids as $id) {
            // Cek agar Admin tidak terhapus jika tidak sengaja tercentang
            $user = $userModel->find($id);
            if ($user && $user['role_id'] != 1) { // Asumsikan role_id 1 = Admin
                $userModel->delete($id);
            }
        }

        return redirect()->to('admin/users')->with('success', 'Data berhasil dihapus.');
    }

    public function exportExcel()
    {
        $users = $this->userModel->getUserWithRole();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Username');
        $sheet->setCellValue('C1', 'Role');

        // Data
        $column = 2;
        foreach ($users as $key => $user) { // Sekarang variabel $users sudah ada
            $sheet->setCellValue('A' . $column, $key + 1);
            $sheet->setCellValue('B' . $column, $user['username']);
            $sheet->setCellValue('C' . $column, $user['nama_role']);
            $column++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Data_User_' . date('Ymd') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        $writer->save('php://output');
        exit;
    }

    // --- Export PDF ---
    public function exportPdf()
    {
        // PERBAIKAN: Gunakan getUserWithRole() tanpa 's' agar sesuai dengan Model
        $data['users'] = $this->userModel->getUserWithRole();

        // Pastikan view 'admin/users/pdf_view' sudah Anda buat
        $html = view('users/exportPdf', $data);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Mengirim file ke browser
        $dompdf->stream('Data_User_' . date('Ymd') . '.pdf', [
            "Attachment" => true
        ]);
    }


    public function ubahPasswordView()
    {
        // Cukup arahkan ke file view-nya
        return view('users/ubah_password');
    }

    public function changePassword()
    {
        // 1. Validasi Input
        $rules = [
            'password_lama'       => 'required',
            'password_baru'       => 'required|min_length[6]',
            'konfirmasi_password' => 'required|matches[password_baru]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $userId = session()->get('user_id');
        $db     = \Config\Database::connect();
        $user   = $db->table('users')->where('id', $userId)->get()->getRowArray();

        // 2. Cek apakah user ada
        if (!$user) {
            return redirect()->to('/login')->with('error', 'Sesi Anda telah berakhir.');
        }

        // 3. Verifikasi Password Lama
        if (!password_verify($this->request->getPost('password_lama'), $user['password'])) {
            return redirect()->back()->with('error', 'Password lama tidak sesuai.');
        }

        // 4. Update Password
        $dataUpdate = [
            'password' => password_hash($this->request->getPost('password_baru'), PASSWORD_DEFAULT)
        ];

        $updated = $db->table('users')->where('id', $userId)->update($dataUpdate);

        if ($updated) {
            // Mapping role ke prefix URL (Harus sinkron dengan routes.php)
            $roleMap = [
                1 => 'admin',
                2 => 'operator',
                3 => 'gadik',
                4 => 'danton',
                5 => 'danki',
                6 => 'danyon',
                7 => 'siswa'
            ];

            $roleId = session()->get('role_id');
            $prefix = $roleMap[$roleId] ?? 'admin';

            return redirect()->to(base_url($prefix . '/users/profil'))->with('success', 'Password berhasil diubah!');
        }

        return redirect()->back()->with('error', 'Terjadi kesalahan sistem, silakan coba lagi.');
    }


    public function profil()
    {
        $userId = session()->get('user_id');
        $roleId = session()->get('role_id');
        $db = \Config\Database::connect();

        $data['user'] = null;

        if ($roleId == 7) {
            // Query untuk Siswa dengan JOIN ke tabel relasi & pengondisian ID/Nomor Induk yang aman
            $data['user'] = $db->table('siswa s')
                ->select('
                    s.*, 
                    p.nama_pleton, 
                    kompi.id as kompi_id, 
                    kompi.nama_kompi, 
                    batalyon.id as batalyon_id, 
                    batalyon.nama_batalyon,
                    
                    dt.nama as danton_nama,
                    pg_dt.nama_pangkat as danton_pangkat,
                    
                    dk.nama as danki_nama,
                    pg_dk.nama_pangkat as danki_pangkat,
                    
                    dy.nama as danyon_nama,
                    pg_dy.nama_pangkat as danyon_pangkat
                ')
                ->join('pleton p', 'p.id = s.pleton_id', 'left')
                ->join('kompi', 'kompi.id = p.kompi_id', 'left')
                ->join('batalyon', 'batalyon.id = kompi.batalyon_id', 'left')

                // Join Pegawai Danton (Mendukung ID atau Nomor Induk)
                ->join('pegawai dt', 'dt.id = p.danton_id OR dt.nomor_induk = p.danton_id', 'left')
                ->join('pangkat pg_dt', 'pg_dt.id = dt.pangkat_id', 'left')

                // Join Pegawai Danki (Mendukung ID atau Nomor Induk)
                ->join('pegawai dk', 'dk.id = kompi.danki_id OR dk.nomor_induk = kompi.danki_id', 'left')
                ->join('pangkat pg_dk', 'pg_dk.id = dk.pangkat_id', 'left')

                // Join Pegawai Danyon (Mendukung ID atau Nomor Induk)
                ->join('pegawai dy', 'dy.id = batalyon.danyon_id OR dy.nomor_induk = batalyon.danyon_id', 'left')
                ->join('pangkat pg_dy', 'pg_dy.id = dy.pangkat_id', 'left')

                ->where('s.user_id', $userId)
                ->get()->getRowArray();
        } else {
            // Query untuk Pegawai / Role Pengasuh / Pejabat (Ditambah JOIN ke tabel pangkat)
            $data['user'] = $db->table('pegawai peg')
                ->select('peg.*, pg.nama_pangkat, p.nama_pleton, k.nama_kompi, b.nama_batalyon')
                ->join('pangkat pg', 'pg.id = peg.pangkat_id', 'left')
                ->join('pleton p', 'p.danton_id = peg.id OR p.danton_id = peg.nomor_induk', 'left')
                ->join('kompi k', 'k.id = p.kompi_id OR k.danki_id = peg.id OR k.danki_id = peg.nomor_induk', 'left')
                ->join('batalyon b', 'b.id = k.batalyon_id OR b.danyon_id = peg.id OR b.danyon_id = peg.nomor_induk', 'left')
                ->where('peg.user_id', $userId)
                ->get()->getRowArray();

            // Fallback jika tidak ada di tabel pegawai
            if (!$data['user']) {
                $data['user'] = $db->table('profiles')->where('user_id', $userId)->get()->getRowArray();
            }
        }

        // Jika data tetap kosong, beri default agar view tidak error
        if (!$data['user']) {
            $data['user'] = [
                'nama' => 'User',
                'nomor_induk' => '-',
                'nosis' => '-',
                'foto' => 'default.png'
            ];
        }

        $data['list_pangkat'] = $db->table('pangkat')->get()->getResultArray();
        $data['roleId'] = $roleId;

        return view('users/profil', $data);
    }

    public function simpanProfil()
    {
        $userId = session()->get('user_id');
        $roleId = session()->get('role_id');
        $db = \Config\Database::connect();

        // 1. Persiapkan data yang akan disimpan
        // Menggunakan array agar mudah dikelola
        $dataUpdate = [
            'nama' => $this->request->getPost('nama')
        ];

        // Tentukan Tabel dan kolom identitas berdasarkan Role
        if ($roleId == 7) { // Siswa
            $table = 'siswa';
            $dataUpdate['nosis'] = $this->request->getPost('identitas');
        } elseif ($roleId == 2) { // Pegawai/Operator
            $table = 'pegawai';
            $dataUpdate['nomor_induk'] = $this->request->getPost('identitas');
        } else { // Admin (Role 1)
            // Admin biasanya tidak punya nip/nosis di tabel khusus, 
            // tapi jika ingin simpan di tabel pegawai/profiles, sesuaikan di sini:
            $table = 'pegawai';
            $dataUpdate['nomor_induk'] = $this->request->getPost('identitas');
        }

        // 2. Eksekusi Simpan ke Tabel Utama
        $builder = $db->table($table);

        // Cek apakah user sudah punya data di tabel tersebut
        $cekData = $builder->where('user_id', $userId)->get()->getRow();

        if ($cekData) {
            // UPDATE jika data ditemukan
            $builder->where('user_id', $userId)->update($dataUpdate);
        } else {
            // INSERT jika data belum ada
            $dataUpdate['user_id'] = $userId;
            $builder->insert($dataUpdate);
        }

        // 3. Proses Upload Foto (Selalu simpan ke tabel profiles)
        $fileFoto = $this->request->getFile('foto');
        if ($fileFoto && $fileFoto->isValid() && !$fileFoto->hasMoved()) {
            $newName = $fileFoto->getRandomName();
            $fileFoto->move('assets/dist/img/', $newName);

            $cekProfil = $db->table('profiles')->where('user_id', $userId)->get()->getRow();

            $dataFoto = [
                'user_id' => $userId,
                'foto'    => $newName,
                'nama'    => $this->request->getPost('nama')
            ];

            if ($cekProfil) {
                $db->table('profiles')->where('user_id', $userId)->update(['foto' => $newName]);
            } else {
                $db->table('profiles')->insert($dataFoto);
            }
        }

        // 4. Cek Error Database
        if ($db->error()['code'] !== 0) {
            return redirect()->back()->with('error', 'Terjadi kesalahan database: ' . $db->error()['message']);
        }

        return redirect()->to('admin/users/profil')->with('success', 'Profil berhasil diperbarui!');
    }

    public function template_exel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Header
        $sheet->setCellValue('A1', 'Username');
        $sheet->setCellValue('B1', 'Password');
        $sheet->setCellValue('C1', 'Role'); // Isi: "Siswa" atau "Pegawai"
        $sheet->setCellValue('D1', 'Nama');
        $sheet->setCellValue('E1', 'Nomor Induk'); // NIS untuk Siswa, NIP/NRP untuk Pegawai

        // Opsional: Beri styling dasar
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        // Set nama file
        $writer = new Xlsx($spreadsheet);
        $filename = 'Template_Import_User.xlsx';

        // Header agar browser men-trigger download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    // Tambahkan parameter $id di sini
    public function edit($id = null)
    {
        if (!$id) {
            return redirect()->back()->with('error', 'ID tidak ditemukan');
        }

        $userModel = new \App\Models\UserModel();

        // Panggil fungsi model yang baru dibuat
        $userData = $userModel->getUserById($id);

        if (!$userData) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }
        $tipe = ($userData['role_id'] == 7) ? 'siswa' : 'pegawai';

        $data = [
            'title' => 'Edit Data',
            'user'  => $userData,
            'tipe'  => $tipe
        ];
        dd($data['user']);
        return view('users/edit', $data);
    }

    public function update($id_user)
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        // 1. Ambil data dari form
        $nama = $request->getPost('nama');
        $identitas = $request->getPost('identitas'); // NOSIS atau NOMOR_INDUK
        $roleId = session()->get('role_id');

        $isSiswa = ($roleId == 7);
        $tabel = $isSiswa ? 'siswa' : 'pegawai';
        $kolomIdentitas = $isSiswa ? 'nosis' : 'nomor_induk';

        // Cek ketersediaan data lama berdasarkan user_id
        $dataLama = $db->table($tabel)->where('user_id', $id_user)->get()->getRowArray();

        // 2. Siapkan array data untuk disimpan/diupdate
        $dataSimpan = [
            'nama'          => $nama,
            $kolomIdentitas => $identitas
        ];

        // Tambahkan user_id jika ternyata datanya belum ada (untuk keperluan INSERT)
        if (!$dataLama) {
            $dataSimpan['user_id'] = $id_user;
            if (!$isSiswa) {
                $dataSimpan['role_id'] = $roleId;
            }
        }

        // Tambahkan pangkat_id khusus untuk pegawai
        if (!$isSiswa) {
            $dataSimpan['pangkat_id'] = $request->getPost('pangkat_id');
        }

        // 3. Logika Upload Foto Profil
        $fotoBaru = $request->getFile('foto');
        if ($fotoBaru && $fotoBaru->isValid() && !$fotoBaru->hasMoved()) {
            // Hapus foto lama jika ada dan bukan default
            if (!empty($dataLama['foto']) && file_exists('assets/dist/img/users/' . $dataLama['foto']) && $dataLama['foto'] != 'default.png') {
                @unlink('assets/dist/img/users/' . $dataLama['foto']);
            }

            // Simpan foto baru
            $namaFotoBaru = $fotoBaru->getRandomName();
            $fotoBaru->move('assets/dist/img/users/', $namaFotoBaru);
            $dataSimpan['foto'] = $namaFotoBaru;
        }

        // 4. Logika Upload Tanda Tangan (Khusus Pegawai)
        if (!$isSiswa) {
            $ttdBaru = $request->getFile('ttd');
            if ($ttdBaru && $ttdBaru->isValid() && !$ttdBaru->hasMoved()) {
                // Hapus TTD lama jika ada
                if (!empty($dataLama['ttd']) && file_exists('assets/dist/img/ttd/' . $dataLama['ttd'])) {
                    @unlink('assets/dist/img/ttd/' . $dataLama['ttd']);
                }

                // Simpan TTD baru
                $namaTtdBaru = $ttdBaru->getRandomName();
                $ttdBaru->move('assets/dist/img/ttd/', $namaTtdBaru);
                $dataSimpan['ttd'] = $namaTtdBaru;
            }
        }

        // 5. Eksekusi Menggunakan Cek Kondisi (INSERT atau UPDATE)
        if ($dataLama) {
            // Jika data sudah ada, lakukan UPDATE
            $sukses = $db->table($tabel)->where('user_id', $id_user)->update($dataSimpan);
        } else {
            // Jika data BELUM ada di tabel pegawai/siswa, lakukan INSERT
            $sukses = $db->table($tabel)->insert($dataSimpan);
        }

        if ($sukses) {
            return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
        } else {
            return redirect()->back()->with('error', 'Gagal memproses perubahan pada database.');
        }
    }
}
