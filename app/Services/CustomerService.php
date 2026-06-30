<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    /**
     * Temukan-atau-buat customer dari data nota (nama + telp).
     * Identitas: nomor telp bila ada, selain itu nama. Mengembalikan null untuk walk-in tanpa identitas.
     */
    public static function resolveFromSale(?string $name, ?string $phone): ?Customer
    {
        $name  = trim((string) $name);
        $phone = trim((string) $phone);

        if ($name === '' && $phone === '') {
            return null;
        }

        if ($phone !== '') {
            return Customer::firstOrCreate(
                ['phone' => $phone],
                ['name' => $name !== '' ? $name : $phone, 'is_active' => true]
            );
        }

        return Customer::firstOrCreate(
            ['name' => $name, 'phone' => null],
            ['is_active' => true]
        );
    }
}
