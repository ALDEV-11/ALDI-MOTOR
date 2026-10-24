<?php

namespace App\Support;

class Phone
{
    /**
     * Normalisasi nomor telepon ke format standar Indonesia (628xxx).
     * Mengubah format:
     * - 0812... -> 62812...
     * - 812...  -> 62812...
     * - +62812... -> 62812...
     * Menghapus karakter spasi, tanda hubung, kurung, dan simbol lainnya.
     */
    public static function normalize(?string $phone): string
    {
        if ($phone === null) {
            return '';
        }

        // Ambil hanya karakter angka
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return '';
        }

        // 08xxxx -> 628xxxx
        if (str_starts_with($digits, '08')) {
            return '62'.substr($digits, 1);
        }

        // 8xxxx -> 628xxxx
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        // 628xxxx -> sudah berformat 628
        if (str_starts_with($digits, '628')) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Periksa apakah nomor merupakan nomor seluler Indonesia yang valid.
     * Kriteria:
     * - Berawalan 628 setelah dinormalisasi
     * - Total panjang digit antara 11 hingga 15 digit (standar seluler RI & E.164)
     */
    public static function isValid(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        // Jika input asli mengandung karakter selain angka, spasi, plus, minus, tanda kurung
        if (preg_match('/[a-zA-Z]/', $phone)) {
            return false;
        }

        $normalized = self::normalize($phone);

        // Regex nomor seluler Indonesia: 628 dilanjutkan 8 sampai 12 digit angka (total 11-15 digit)
        return (bool) preg_match('/^628[0-9]{8,12}$/', $normalized);
    }
}
