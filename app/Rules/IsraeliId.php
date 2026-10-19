<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsraeliId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid((string) $value)) {
            $fail('מספר תעודת הזהות אינו תקין (ספרת ביקורת שגויה)');
        }
    }

    /**
     * Israeli ID check digit (Luhn variant over 9 digits, left-padded with zeros).
     */
    public static function isValid(string $id): bool
    {
        $id = trim($id);

        if (! preg_match('/^\d{5,9}$/', $id)) {
            return false;
        }

        $id = str_pad($id, 9, '0', STR_PAD_LEFT);
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $n = (int) $id[$i] * (($i % 2) + 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }

        return $sum % 10 === 0;
    }
}
