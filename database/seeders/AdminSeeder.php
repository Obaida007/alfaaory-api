<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the super_admin account from ADMIN_EMAIL / ADMIN_PASSWORD. No credentials live in the code.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('store.admin.email');
        $password = config('store.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD غير محددين في .env — تم تخطي إنشاء حساب super_admin.');

            return;
        }

        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->fill([
            'first_name' => 'الإدارة',
            'last_name' => 'العامة',
            'username' => $admin->username ?? config('store.admin.username', 'admin'),
            'password' => $password,
        ]);
        $admin->level_id ??= Level::query()->orderBy('min_total_purchases')->value('id');
        $admin->email_verified_at ??= now();
        $admin->save();

        $admin->syncRoles(['super_admin']);

        $this->command?->info("super_admin: {$email}");
    }
}
