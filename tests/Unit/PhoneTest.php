<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    /**
     * Uji normalisasi nomor dengan berbagai format awal.
     */
    public function test_normalize_converts_various_prefixes_to_628(): void
    {
        // 1. Format standar awalan 08
        $this->assertEquals('6281234567890', Phone::normalize('081234567890'));

        // 2. Format awalan 8 tanpa 0
        $this->assertEquals('6281234567890', Phone::normalize('81234567890'));

        // 3. Format internasional dengan tanda tambah +628
        $this->assertEquals('6281234567890', Phone::normalize('+6281234567890'));

        // 4. Format sudah 628
        $this->assertEquals('6281234567890', Phone::normalize('6281234567890'));

        // 5. Format dengan spasi pemisah
        $this->assertEquals('6281234567890', Phone::normalize('0812 3456 7890'));

        // 6. Format dengan tanda strip (dash)
        $this->assertEquals('6281234567890', Phone::normalize('0812-3456-7890'));

        // 7. Format dengan tanda kurung
        $this->assertEquals('6281234567890', Phone::normalize('(0812) 34567890'));
    }

    /**
     * Uji validitas nomor seluler Indonesia yang benar.
     */
    public function test_is_valid_accepts_valid_indonesian_mobile_numbers(): void
    {
        // 8. Nomor seluler 11 digit (0812345678 -> 62812345678)
        $this->assertTrue(Phone::isValid('0812345678'));

        // 9. Nomor seluler 12 digit
        $this->assertTrue(Phone::isValid('+628123456789'));

        // 10. Nomor seluler 13 digit dengan strip
        $this->assertTrue(Phone::isValid('0812-3456-78901'));

        // 11. Nomor fiktif aman untuk seeder (6280000000001)
        $this->assertTrue(Phone::isValid('6280000000001'));
    }

    /**
     * Uji penolakan nomor yang tidak valid.
     */
    public function test_is_valid_rejects_invalid_numbers(): void
    {
        // 12. Nomor telepon rumah / PSTN (awalan 021)
        $this->assertFalse(Phone::isValid('0217654321'));

        // 13. Nomor terlalu pendek
        $this->assertFalse(Phone::isValid('0812'));

        // 14. Nomor terlalu panjang (>15 digit)
        $this->assertFalse(Phone::isValid('081234567890123456789'));

        // 15. Mengandung huruf/alfabet
        $this->assertFalse(Phone::isValid('0812abc3456'));

        // 16. String kosong
        $this->assertFalse(Phone::isValid(''));

        // 17. Null value
        $this->assertFalse(Phone::isValid(null));
    }
}
