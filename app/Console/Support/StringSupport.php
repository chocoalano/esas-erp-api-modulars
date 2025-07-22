<?php
namespace App\Console\Support;

use Illuminate\Support\Carbon;

class StringSupport
{
    public static function inisial(string $string, int $length = 3)
    {
        // Buang semua karakter selain huruf dan spasi
        $cleanString = preg_replace('/[^A-Za-z ]/', '', $string);
        $words = explode(' ', trim($cleanString));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= $length) {
                    break;
                }
            }
        }
        // Jika hasilnya kurang dari panjang minimal, tambahkan 'X' agar tidak kosong
        if (strlen($initials) < $length) {
            $initials = str_pad($initials, $length, 'X');
        }
        return substr($initials, 0, $length);
    }
    public static function generateDateLabels(Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $labels[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        return $labels;
    }
}
