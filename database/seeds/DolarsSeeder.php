<?php

use Illuminate\Database\Seeder;
use App\Dolar;

class DolarsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      DB::table('dolars')->delete();
      DB::table('dolars')->insert([
          [
              'price' => '1000000',              
              'created_at' => now()
          ],
      ]);
    }
}
