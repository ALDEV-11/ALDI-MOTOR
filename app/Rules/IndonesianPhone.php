<?php

namespace App\Rules;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class IndonesianPhone implements ValidationRule
{
    /**
     * Jalankan aturan validasi.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Phone::isValid($value)) {
            $fail('Nomor :attribute harus berupa nomor seluler Indonesia yang valid (contoh: 081234567890).');
        }
    }
}
