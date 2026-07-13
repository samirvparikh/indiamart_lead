<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'phone' => '9999999991',
                'designation' => 'Super Administrator',
                'role' => RoleName::SuperAdmin->value,
            ],
            [
                'name' => 'System Admin',
                'email' => 'admin@gmail.com',
                'phone' => '9999999992',
                'designation' => 'Administrator',
                'role' => RoleName::Admin->value,
            ],
            [
                'name' => 'Sales Manager',
                'email' => 'manager@gmail.com',
                'phone' => '9999999993',
                'designation' => 'Sales Manager',
                'role' => RoleName::SalesManager->value,
            ],
            [
                'name' => 'Sales Executive',
                'email' => 'executive@gmail.com',
                'phone' => '9999999994',
                'designation' => 'Sales Executive',
                'role' => RoleName::SalesExecutive->value,
            ],
            [
                'name' => 'Tele Caller',
                'email' => 'telecaller@gmail.com',
                'phone' => '9999999995',
                'designation' => 'Tele Caller',
                'role' => RoleName::TeleCaller->value,
            ],
            [
                'name' => 'Marketing User',
                'email' => 'marketing@gmail.com',
                'phone' => '9999999996',
                'designation' => 'Marketing Executive',
                'role' => RoleName::Marketing->value,
            ],
            [
                'name' => 'Viewer User',
                'email' => 'viewer@gmail.com',
                'phone' => '9999999997',
                'designation' => 'Viewer',
                'role' => RoleName::Viewer->value,
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    ...$data,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$role]);
        }

        $this->command?->info('Default users seeded. Password for all: password');
    }
}
