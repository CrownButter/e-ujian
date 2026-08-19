<?php

if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal)
    {
        if (empty($tanggal) || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') {
            return '-';
        }

        $bulan = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];

        $split = explode('-', date('Y-m-d', strtotime($tanggal)));

        if (count($split) < 3) {
            return $tanggal;
        }

        return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    }
}

if (!function_exists('hari_indo')) {
    function hari_indo($tanggal)
    {
        if (empty($tanggal) || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') {
            return '-';
        }

        $daftar_hari = [
            'Sun' => 'Minggu',
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu'
        ];

        $namahari = date('D', strtotime($tanggal));

        return $daftar_hari[$namahari] ?? '-';
    }
}
