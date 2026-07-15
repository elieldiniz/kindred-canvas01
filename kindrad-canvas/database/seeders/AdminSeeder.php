<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.admin_email', 'admin@kindred.local');
        $password = config('app.admin_password', 'password');

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'is_admin' => true,
            ],
        );

        $admin->forceFill(['is_admin' => true])->save();

        $this->command?->info("Admin user: {$admin->email}");
    }
}
