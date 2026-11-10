<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    // Crea el usuario admin (o lo actualiza si ya existe) con ADMIN_EMAIL y ADMIN_PASSWORD del .env
    public function run()
    {
        $password = env('ADMIN_PASSWORD');

        // Sin contraseña no se crea: así no queda una contraseña fija escrita en el código
        if (!$password) {
            $this->command->warn('Falta ADMIN_PASSWORD en el .env, no se creó el admin');
            return;
        }

        // is_admin no está en $fillable, por eso se asigna campo por campo
        $admin = User::firstOrNew(['email' => env('ADMIN_EMAIL', 'admin@larosite.test')]);
        $admin->name = 'Administrador';
        $admin->password = Hash::make($password);
        $admin->is_admin = true;
        $admin->save();
    }
}
