<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '12345678-9',
            'birth_date' => '1985-05-15',
            'address' => 'Av. Providencia 1234, Santiago',
            'phone' => '+56912345678',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Lawyers
        User::create([
            'name' => 'María Fernández',
            'email' => 'maria.fernandez@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '23456789-0',
            'birth_date' => '1982-08-22',
            'address' => 'Av. Apoquindo 3000, Las Condes',
            'phone' => '+56923456789',
            'role' => 'lawyer',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Carlos Rojas',
            'email' => 'carlos.rojas@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '34567890-1',
            'birth_date' => '1978-03-10',
            'address' => 'Av. Vitacura 5500, Vitacura',
            'phone' => '+56934567890',
            'role' => 'lawyer',
            'is_active' => true,
        ]);

        // Investors
        User::create([
            'name' => 'Patricia González',
            'email' => 'patricia.gonzalez@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '45678901-2',
            'birth_date' => '1990-11-05',
            'address' => 'Av. Isidora Goyenechea 2800, Las Condes',
            'phone' => '+56945678901',
            'role' => 'investor',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Roberto Silva',
            'email' => 'roberto.silva@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '56789012-3',
            'birth_date' => '1987-06-18',
            'address' => 'Av. El Bosque Norte 500, Las Condes',
            'phone' => '+56956789012',
            'role' => 'investor',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Ana Martínez',
            'email' => 'ana.martinez@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '67890123-4',
            'birth_date' => '1992-09-25',
            'address' => 'Av. Santa María 2020, Providencia',
            'phone' => '+56967890123',
            'role' => 'investor',
            'is_active' => true,
        ]);

        // Victims
        User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '78901234-5',
            'birth_date' => '1995-02-14',
            'address' => 'Av. Grecia 1500, Ñuñoa',
            'phone' => '+56978901234',
            'role' => 'victim',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Carmen López',
            'email' => 'carmen.lopez@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '89012345-6',
            'birth_date' => '1988-12-30',
            'address' => 'Av. Vicuña Mackenna 3000, Macul',
            'phone' => '+56989012345',
            'role' => 'victim',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Luis Torres',
            'email' => 'luis.torres@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '90123456-7',
            'birth_date' => '1993-07-08',
            'address' => 'Av. Américo Vespucio 1500, La Florida',
            'phone' => '+56990123456',
            'role' => 'victim',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Sofía Ramírez',
            'email' => 'sofia.ramirez@testigo.cl',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rut' => '01234567-8',
            'birth_date' => '1991-04-20',
            'address' => 'Av. Gran Avenida 8000, San Miguel',
            'phone' => '+56901234567',
            'role' => 'victim',
            'is_active' => true,
        ]);
    }
}
