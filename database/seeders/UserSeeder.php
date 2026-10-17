<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'مدير النظام الرئيسي',
                'phone' => '0501112233',
                'password' => Hash::make('password'),
                'user_type' => \App\Enums\UserType::SUPER_ADMIN,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        if (\Spatie\Permission\Models\Role::where('name', 'Super Admin')->exists()) {
            $superAdmin->assignRole('Super Admin');
        }
    }
}
