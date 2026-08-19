<?php

namespace App\Controllers;

// 1. Pastikan semua model di-import di bagian atas
use App\Models\PegawaiModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\PangkatModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

class PegawaiController extends BaseController
{
    // 2. Deklarasikan properti kelas agar bisa diakses di semua fungsi
    protected $pegawaiModel;
    protected $userModel;
    protected $roleModel;
    protected $pangkatModel;

    public function __construct()
    {
        // 3. Inisialisasi model menggunakan $this->
        $this->pegawaiModel = new PegawaiModel();
        $this->userModel    = new UserModel();
        $this->roleModel    = new RoleModel();
        $this->pangkatModel = new PangkatModel();
    }
    public function index()
    {
        // Ambil data pegawai beserta relasinya
        $pegawai = $this->pegawaiModel->getPegawaiWithRelations();
        $filter_jabatan = [
            ['id' => 'danyon', 'nama' => 'Danyon', 'role_id' => 6],
            ['id' => 'danki',  'nama' => 'Danki',  'role_id' => 5],
            ['id' => 'danton', 'nama' => 'Danton', 'role_id' => 4],
        ];

        $counts = [];
        $total_all = count($pegawai);

        // Hitung jumlah pegawai untuk masing-masing filter jabatan
        foreach ($filter_jabatan as $f) {
            $count = count(array_filter($pegawai, function ($p) use ($f) {
                return isset($p['role_id']) && $p['role_id'] == $f['role_id'];
            }));

            $counts[$f['nama']] = $count;
        }

        $data = [
            'title'           => 'Data Personel',
            'pangkat'         => $this->pangkatModel->findAll(),
            'roles'           => $this->roleModel->findAll(),
            'pegawai'         => $pegawai,
            'pleton_list'     => $filter_jabatan,
            'counts'          => $counts,
            'total_all'       => $total_all
        ];

        return view('admin/pegawai/index', $data);
    }

    // --- TAMBAH PEGAWAI ---
    public function tambahPegawai()
    {
        // 1. Inisialisasi Database
        $db = \Config\Database::connect();

        // 2. Mulai Transaksi (untuk memastikan kedua tabel terisi atau gagal bersamaan)
        $db->transStart();

        // 3. Simpan ke tabel 'users' terlebih dahulu
        $userModel = new \App\Models\UserModel();
        $userData = [
            'username' => $this->request->getPost('username'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role_id'  => null
        ];
        $userModel->save($userData);
        $userId = $userModel->insertID();
        $pegawaiModel = new \App\Models\PegawaiModel();
        $pegawaiData = [
            'user_id'      => $userId, // Foreign key ke tabel users
            'nama'         => $this->request->getPost('nama'),
            'nomor_induk'  => $this->request->getPost('nomor_induk'),
            'pangkat_id'   => $this->request->getPost('pangkat_id'),
            'tipe_pegawai' => 'polri' // Default sesuai skema
        ];
        $pegawaiModel->save($pegawaiData);

        // 6. Selesaikan transaksi
        $db->transComplete();

        // 7. Cek apakah transaksi berhasil
        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan data.');
        }

        return redirect()->to('/admin/pegawai')->with('success', 'Data berhasil disimpan.');
    }


    public function importPegawai()
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $file = $this->request->getFile('file_excel');
        if (!$file || !$file->isValid()) return redirect()->back()->with('error', 'File tidak valid!');

        $userModel    = new \App\Models\UserModel();
        $pegawaiModel = new \App\Models\PegawaiModel();
        $db           = \Config\Database::connect();

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
        $data        = $spreadsheet->getActiveSheet()->toArray();
        $roleMapping = ['Operator' => 2, 'Pengasuh' => 3, 'Danton' => 4, 'Danki' => 5, 'Danyon' => 6];

        $userBatch    = [];
        $skippedData  = [];

        foreach ($data as $index => $row) {
            if ($index == 0) continue;

            $username    = trim($row[0]);

            // Cek duplikat agar tidak error di DB
            if ($userModel->where('username', $username)->first()) {
                $skippedData[] = ['username' => $username];
                continue;
            }

            $roleId = $roleMapping[trim($row[2])] ?? 3; // Default 3

            $userBatch[] = [
                'username' => $username,
                'password' => password_hash((string)$row[1], PASSWORD_BCRYPT),
                'role_id'  => $roleId
            ];
        }

        $db->transStart();

        if (!empty($userBatch)) {
            $userModel->insertBatch($userBatch);
            $pegawaiBatch = [];

            // Ambil kembali user yang baru di-insert untuk mendapatkan ID-nya
            foreach ($userBatch as $u) {
                $user = $userModel->where('username', $u['username'])->first();

                // Cari row asli di $data untuk mendapatkan nama, nomor induk, dan tipe pegawai
                foreach ($data as $row) {
                    if (trim($row[0]) == $u['username']) {
                        $tipeInput = strtolower(trim($row[5] ?? 'polri'));
                        $tipePegawai = in_array($tipeInput, ['pns', 'polri']) ? $tipeInput : 'polri';

                        $pegawaiBatch[] = [
                            'user_id'      => $user['id'],
                            'nama'         => $row[3],
                            'tipe_pegawai' => $tipePegawai,
                            'nomor_induk'  => (string)$row[4],
                            'role_id'      => $u['role_id']
                        ];
                        break;
                    }
                }
            }

            if (!empty($pegawaiBatch)) {
                $pegawaiModel->insertBatch($pegawaiBatch);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Import gagal, periksa format data.');
        }

        return redirect()->to('/admin/pegawai')->with('success', "Import berhasil.");
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Username');
        $sheet->setCellValue('B1', 'Password');
        $sheet->setCellValue('C1', 'Role (Operator/Pengasuh/Danton/Danki/Danyon)');
        $sheet->setCellValue('D1', 'Nama');
        $sheet->setCellValue('E1', 'Nomor Induk');
        $sheet->setCellValue('F1', 'Polri | PNS');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Template_Import_Pegawai.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
    // --- EXPORT EXCEL ---
    public function exportExcel()
    {
        $data = $this->pegawaiModel->getPegawaiWithRelations();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Nama');
        $sheet->setCellValue('B1', 'NIP/NRP');
        $sheet->setCellValue('C1', 'Pangkat');

        $row = 2;
        foreach ($data as $p) {
            $sheet->setCellValue('A' . $row, $p['nama']);
            $sheet->setCellValue('B' . $row, $p['nomor_induk']);
            $sheet->setCellValue('C' . $row, $p['nama_pangkat']);
            $row++;
        }
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Data_Pegawai.xlsx"');
        $writer->save('php://output');
        exit;
    }

    // --- EXPORT PDF ---
    public function exportPdf()
    {
        // 1. Tangkap parameter filter dari URL (contoh: ?jabatan=Danton)
        $jabatan = $this->request->getGet('jabatan');
        // $data['pegawai'] = $this->pegawaiModel->getPegawaiWithRelations();

        // dd($data['pegawai']);

        // 2. Cek apakah parameter jabatan ada isinya atau tidak
        if (!empty($jabatan)) {
            // Panggil method khusus di model untuk mengambil data yang sudah difilter
            $data['pegawai'] = $this->pegawaiModel->getPegawaiByJabatan($jabatan);
        } else {
            // Jika tidak ada filter (All), ambil semua data seperti biasa
            $data['pegawai'] = $this->pegawaiModel->getPegawaiWithRelations();
        }

        $html = view('admin/pegawai/pdf_view', $data);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Data_Pegawai.pdf');
    }
    public function deleteMassal()
    {
        $ids = $this->request->getPost('id_pegawai');

        if (empty($ids)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih data!']);
        }

        $db = \Config\Database::connect();
        $pegawaiModel = new \App\Models\PegawaiModel();
        $userModel    = new \App\Models\UserModel();

        // Memulai Transaksi agar aman
        $db->transStart();

        // 1. Ambil data pegawai berdasarkan ID yang dicentang
        $pegawaiList = $pegawaiModel->whereIn('id', $ids)->findAll();

        $idsPegawaiToDelete = [];
        $idsUserToDelete = [];

        foreach ($pegawaiList as $p) {
            // Cek jika bukan Admin (role_id 1)
            if ($p['role_id'] != 1) {
                $idsPegawaiToDelete[] = $p['id'];

                // Simpan user_id untuk dihapus di tabel users
                if (!empty($p['user_id'])) {
                    $idsUserToDelete[] = $p['user_id'];
                }
            }
        }

        // 2. Eksekusi hapus di tabel users terlebih dahulu
        if (!empty($idsUserToDelete)) {
            $userModel->delete($idsUserToDelete);
        }

        // 3. Eksekusi hapus di tabel pegawai
        if (!empty($idsPegawaiToDelete)) {
            $pegawaiModel->delete($idsPegawaiToDelete);
        }

        // Cek apakah semua sukses
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menghapus data.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Data pegawai & akun login berhasil dihapus.']);
    }

    public function deletePegawai($id)
    {
        // 1. Inisialisasi Model & Database
        $pegawaiModel = new \App\Models\PegawaiModel(); // Pastikan nama model sesuai
        $userModel  = new \App\Models\UserModel();
        $db         = \Config\Database::connect();

        // 2. Ambil data siswa untuk mendapatkan user_id sebelum dihapus
        $pegawai = $pegawaiModel->find($id);

        if (!$pegawai) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        // 3. Mulai Transaksi Database
        $db->transStart();

        // 4. Hapus dari tabel SISWA
        $pegawaiModel->delete($id);

        // 5. Hapus dari tabel USERS menggunakan user_id yang didapat dari data siswa
        if (!empty($pegawai['user_id'])) {
            $userModel->delete($pegawai['user_id']);
        }

        // 6. Selesaikan Transaksi
        $db->transComplete();

        // 7. Cek apakah transaksi sukses
        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menghapus data.');
        }

        return redirect()->to('/admin/pegawai')->with('success', 'Data pegawai dan akun user berhasil dihapus.');
    }

    public function updatePegawai($id = null)
    {
        if ($id === null) {
            return redirect()->back()->with('error', 'ID Pegawai tidak ditemukan.');
        }

        // 1. Ambil data pegawai untuk mendapatkan user_id
        $pegawai = $this->pegawaiModel->find($id);
        if (!$pegawai) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        // 2. Mulai Transaksi Database
        $db = \Config\Database::connect();
        $db->transStart();

        // 3. Ambil data dari form POST
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $role_id  = $this->request->getPost('role_id'); // Nilai role_id dari form

        $userId = $pegawai['user_id'];

        // 4. UPDATE KE TABEL USERS
        if (!empty($userId)) {
            $userData = [
                'role_id' => $role_id
            ];

            if (!empty($username)) {
                $userData['username'] = $username;
            }
            if (!empty($password)) {
                $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $this->userModel->update($userId, $userData);
        } else {
            $userData = [
                'username' => !empty($username) ? $username : $this->request->getPost('nomor_induk'),
                'password' => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : password_hash('123456', PASSWORD_DEFAULT),
                'role_id'  => $role_id
            ];

            $this->userModel->insert($userData);
            $userId = $this->userModel->insertID();
        }

        // 5. UPDATE KE TABEL PEGAWAI (ROLE_ID DISERTAKAN DI SINI AGAR BERUBAH DI TABEL PEGAWAI)
        $pegawaiData = [
            'user_id'     => $userId,
            'nama'        => $this->request->getPost('nama'),
            'nomor_induk' => $this->request->getPost('nomor_induk'),
            'pangkat_id'  => $this->request->getPost('pangkat_id'),
            'role_id'     => $role_id  // <-- INI WAJIB ADA AGAR TABEL PEGAWAI IKUT TERUPDATE
        ];

        // Eksekusi update ke tabel pegawai
        $this->pegawaiModel->update($id, $pegawaiData);

        // 6. Selesaikan Transaksi
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal memperbarui data.');
        }

        return redirect()->to('/admin/pegawai')->with('success', 'Data pegawai dan role berhasil diperbarui.');
    }

    public function resetPassword($pegawaiId)
    {
        // 1. Ambil data pegawai beserta relasi user_id dan nomor_induk-nya
        $pegawai = $this->pegawaiModel->find($pegawaiId);

        if (!$pegawai) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        $userId = $pegawai['user_id'];
        $nomorInduk = $pegawai['nomor_induk']; // Ini akan dijadikan password baru

        // 2. Load User Model (atau gunakan database builder)
        $userModel = new \App\Models\UserModel(); // Sesuaikan dengan model user Anda

        // 3. Update password dengan menghash nomor induk
        $userModel->update($userId, [
            'password' => password_hash($nomorInduk, PASSWORD_DEFAULT)
        ]);

        return redirect()->back()->with('success', 'Password berhasil direset menjadi nomor induk (' . $nomorInduk . ').');
    }
}
