<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Perpustakaan',
                'password' => 'password', // otomatis di-hash oleh cast 'hashed'
            ],
        );

        if (Book::query()->doesntExist()) {
            Book::factory(20)->create();
        }
    }
}
