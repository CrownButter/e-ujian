<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class MataPelajaran extends BaseController
{

    public function mataPelajaran()
    {
        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $roleId = session()->get('role_id'); // 1 untuk Admin, lainnya untuk Gadik

        // Query utama menggunakan Query Builder CodeIgniter
        $builder = $db->table('mata_pelajaran');

        $builder->select('mata_pelajaran.*, pegawai.nama as nama_gadik, pangkat.nama_pangkat');
        $builder->join('kelas_ujian', 'kelas_ujian.mata_pelajaran_id = mata_pelajaran.id', 'left');
        $builder->join('pegawai', 'pegawai.id = kelas_ujian.penguji_id', 'left');
        $builder->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left');

        // Jika yang login BUKAN Admin (Gadik), filter mapel miliknya saja
        if ($roleId != 1) {
            $pegawai = $db->table('pegawai')->where('user_id', $userId)->get()->getRow();

            if ($pegawai) {
                $builder->groupStart()
                    ->where('kelas_ujian.penguji_id', $pegawai->id)
                    ->orWhere('kelas_ujian.penguji_id', $pegawai->nomor_induk)
                    ->groupEnd();
            } else {
                $builder->where('mata_pelajaran.id', 0);
            }
        }

        // Gunakan group by unik berdasarkan mapel dan pengujinya agar tidak duplikat
        $builder->groupBy('mata_pelajaran.id, kelas_ujian.penguji_id');

        $data['mata_pelajaran'] = $builder->get()->getResultArray();

        $data['title'] = 'Daftar Mata Pelajaran';
        $data['page_title'] = 'Manajemen Mata Pelajaran';

        return view('mata_pelajaran/index', $data);
    }

    public function tambahMapel()
    {
        $db = \Config\Database::connect();

        $data = [
            'kode_mapel' => $this->request->getPost('kode_mapel'),
            'nama_mapel' => $this->request->getPost('nama_mapel'),
        ];

        $db->table('mata_pelajaran')->insert($data);

        return redirect()->back()->with('success', 'Mata pelajaran berhasil ditambahkan!');
    }

    public function updateMapel($id)
    {
        $db = \Config\Database::connect();

        $data = [
            'kode_mapel' => $this->request->getPost('kode_mapel'),
            'nama_mapel' => $this->request->getPost('nama_mapel'),
        ];

        $db->table('mata_pelajaran')->where('id', $id)->update($data);

        return redirect()->back()->with('success', 'Mata pelajaran berhasil diperbarui!');
    }

    public function deleteMapel($id)
    {
        $db = \Config\Database::connect();

        $db->table('mata_pelajaran')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Mata pelajaran berhasil dihapus!');
    }
}
