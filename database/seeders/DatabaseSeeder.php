<?php

namespace Database\Seeders;

use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        Todo::firstOrCreate(
            ['user_id' => $member->id, 'title' => 'Mempelajari Arsitektur Laravel 13'],
            [
                'description' => 'Memahami fitur baru Laravel 13 dan implementasi feature-first approach.',
                'status' => 'completed',
                'due_date' => now()->addDays(2)->format('Y-m-d'),
            ]
        );

        Todo::firstOrCreate(
            ['user_id' => $member->id, 'title' => 'Mengembangkan Komponen Livewire'],
            [
                'description' => 'Membuat komponen interaktif untuk CRUD todolist.',
                'status' => 'in_progress',
                'due_date' => now()->addDays(5)->format('Y-m-d'),
            ]
        );

        Todo::firstOrCreate(
            ['user_id' => $member->id, 'title' => 'Integrasi OpenAI dan Excel Export/Import'],
            [
                'description' => 'Implementasi Phpspreadsheet dan integrasi OpenAI endpoint.',
                'status' => 'pending',
                'due_date' => now()->addDays(7)->format('Y-m-d'),
            ]
        );
    }
}
