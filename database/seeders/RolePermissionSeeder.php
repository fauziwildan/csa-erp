<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'view dashboard',

            // Auth & User Management
            'manage users',
            'manage roles',

            // Master Data
            'view master',
            'create master',
            'update master',
            'delete master',

            // Product
            'view product',
            'create product',
            'update product',
            'delete product',
            'print product label',
            'edit product stock',
            'create local stock entry',

            // Warehouse
            'view warehouse',
            'create warehouse stock',
            'view warehouse dashboard',

            // Shipping
            'view shipment',
            'create shipment',
            'update shipment',
            'print shipment',

            // Store
            'view store',
            'view catalog',
            'receive store shipment',
            'request store transfer',
            'approve store transfer',

            // Transfer
            'view transfer',
            'receive transfer',
            'print transfer',

            // POS
            'access pos',
            'view pos',
            'process sale',
            'apply discount',
            'open cash session',
            'close cash session',
            'view cash session',

            // Return
            'view customer return',
            'process customer return',
            'view store return',
            'create store return',
            'receive store return',
            'inspect return',

            // Stock Opname
            'view stock opname',
            'create stock opname',
            'submit stock opname',
            'approve stock opname',
            'delete stock opname',

            // Finance
            'view finance',

            // Reports
            'view report',
            'export report',

            // Expenses
            'view expenses',
            'create expenses',
            'update expenses',
            'delete expenses',

            // Settings (kredit global, dll)
            'manage settings',

            // Customers
            'view customers',
            'manage customers',

            // Credit approval (mode approval)
            'approve credit',

            // Pelunasan / pembayaran kredit
            'record payment',

            // Settlement toko → owner
            'view settlement',
            'manage settlement',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // SUPERADMIN — semua akses (bypass via Gate::before)
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $superadmin->syncPermissions(Permission::all());

        // OWNER
        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $owner->syncPermissions([
            'view dashboard',
            'view master',
            'view product',
            'view warehouse',
            'view warehouse dashboard',
            'view shipment',
            'print shipment',
            'view store',
            'view transfer',
            'request store transfer',
            'approve store transfer',
            'receive transfer',
            'print transfer',
            'view customer return',
            'view store return',
            'view stock opname',
            'view finance',
            'view report',
            'export report',
            'view expenses',
            'create expenses',
            'update expenses',
            'delete expenses',
            'create local stock entry',
            'manage settings',
            'view customers',
            'manage customers',
            'approve credit',
            'record payment',
            'view settlement',
            'manage settlement',
        ]);

        // FINANCE
        $finance = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        $finance->syncPermissions([
            'view dashboard',
            'view master',
            'view product',
            'view warehouse',
            'view shipment',
            'view store',
            'view transfer',
            'view customer return',
            'view store return',
            'view stock opname',
            'view finance',
            'view pos',
            'view cash session',
            'view report',
            'export report',
            'view customers',
            'record payment',
            'view settlement',
            'manage settlement',
        ]);

        // ADMIN GUDANG
        $adminWarehouse = Role::firstOrCreate(['name' => 'admin gudang', 'guard_name' => 'web']);
        $adminWarehouse->syncPermissions([
            'view dashboard',
            'view product',
            'view warehouse',
            'create warehouse stock',
            'view warehouse dashboard',
            'view shipment',
            'create shipment',
            'update shipment',
            'print shipment',
            'view transfer',
            'request store transfer',
            'approve store transfer',
            'receive transfer',
            'print transfer',
            'view store return',
            'receive store return',
            'inspect return',
            'view expenses',
            'create expenses',
            'update expenses',
            'delete expenses',
            'create local stock entry',
        ]);

        // OPERATOR GUDANG
        $operatorWarehouse = Role::firstOrCreate(['name' => 'operator gudang', 'guard_name' => 'web']);
        $operatorWarehouse->syncPermissions([
            'view dashboard',
            'view product',
            'view warehouse',
            'create warehouse stock',
            'view warehouse dashboard',
            'view shipment',
            'update shipment',
            'print shipment',
            'view store return',
            'receive store return',
            'view stock opname',
            'submit stock opname',
        ]);

        // KEPALA TOKO
        $storeHead = Role::firstOrCreate(['name' => 'kepala toko', 'guard_name' => 'web']);
        $storeHead->syncPermissions([
            'view dashboard',
            'view product',
            'view store',
            'view catalog',
            'access pos',
            'view pos',
            'process sale',
            'apply discount',
            'open cash session',
            'close cash session',
            'view cash session',
            'receive store shipment',
            'request store transfer',
            'approve store transfer',
            'view transfer',
            'receive transfer',
            'print transfer',
            'view customer return',
            'process customer return',
            'view store return',
            'create store return',
            'view stock opname',
            'create stock opname',
            'submit stock opname',
            'view report',
            'print product label',
            'view expenses',
            'create expenses',
            'update expenses',
            'delete expenses',
            'create local stock entry',
            'view customers',
            'manage customers',
            'approve credit',
            'record payment',
            'view settlement',
        ]);

        // KASIR
        $cashier = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
        $cashier->syncPermissions([
            'access pos',
            'view pos',
            'process sale',
            'apply discount',
            'open cash session',
            'close cash session',
            'view cash session',
            'view customers',
            'record payment',
        ]);
    }
}
