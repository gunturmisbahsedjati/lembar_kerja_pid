<?php
function Indonesia2Tgl($tanggal)
{
    if (empty($tanggal)) return '-';

    // Array singkatan bulan Indonesia dengan key integer (1-12)
    $bulan = array(
        1 => "Januari",
        2 => "Februari",
        3 => "Maret",
        4 => "April",
        5 => "Mei",
        6 => "Juni",
        7 => "Juli",
        8 => "Agustus",
        9 => "September",
        10 => "Oktober",
        11 => "November",
        12 => "Desember"
    );

    $time = strtotime($tanggal);
    $d = date('d', $time);
    $m = (int)date('n', $time); // Menghasilkan integer 1-12
    $Y = date('Y', $time);
    $H_i = date('H:i', $time);

    return "{$d} {$bulan[$m]} {$Y} {$H_i}";
}
