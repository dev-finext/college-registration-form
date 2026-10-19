<?php

namespace Tests\Unit;

use App\Rules\IsraeliId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IsraeliIdTest extends TestCase
{
    public static function validIds(): array
    {
        return [
            'nine digits' => ['123456782'],
            'leading zeros' => ['000000018'],
            'short form is left-padded' => ['00018'],
        ];
    }

    public static function invalidIds(): array
    {
        return [
            'bad check digit (from the sample form)' => ['011112222'],
            'off by one' => ['123456789'],
            'letters' => ['12345678a'],
            'too long' => ['1234567890'],
            'empty' => [''],
        ];
    }

    #[DataProvider('validIds')]
    public function test_accepts_valid_ids(string $id): void
    {
        $this->assertTrue(IsraeliId::isValid($id));
    }

    #[DataProvider('invalidIds')]
    public function test_rejects_invalid_ids(string $id): void
    {
        $this->assertFalse(IsraeliId::isValid($id));
    }
}
