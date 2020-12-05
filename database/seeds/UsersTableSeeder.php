<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Piso_venta;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $usuario = User::create([
        		'name' => 'abastoi',
        		'email' => 'abastoi@gmail.com',
        		'password' => bcrypt("abastoi")
        		]);

        $piso_Venta = Piso_venta::create([
        					'nombre' => 'Abasto I',
        					'ubicacion' => 'Centro de Cagua',
        					'dinero' => 0,
        					'user_id' => $usuario->id
        					]);


        $usuario = User::create([
        		'name' => 'mipuchitoca',
        		'email' => 'mipuchitoca@gmail.com',
        		'password' => bcrypt("mipuchito")
        		]);

        $piso_Venta = Piso_venta::create([
        					'nombre' => 'Mi Puchito C.A.',
        					'ubicacion' => 'La Segundera',
        					'dinero' => 0,
        					'user_id' => $usuario->id
        					]);

        $usuario = User::create([
        		'name' => 'abastoiii',
        		'email' => 'abastoiii@gmail.com',
        		'password' => bcrypt("abastoiii")
        		]);

        $piso_Venta = Piso_venta::create([
        					'nombre' => 'Abasto III',
        					'ubicacion' => 'Cagua - La Villa',
        					'dinero' => 0,
        					'user_id' => $usuario->id
        					]);
    }
}
