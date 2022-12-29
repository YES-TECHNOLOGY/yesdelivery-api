<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::create([
            'type_identification'=>'cedula',
            'identification'=>'0000000000',
            'name'=>'YES',
            'lastname'=>'BOT',
            'email'=>'boot@yesdelivery.com',
            'password'=>bcrypt('123456'),
            'cod_rol'=>1,
            'active'=>1,
            'cod_nationality'=>57,
            'date_birth'=>'2022/01/01',
            'cod_dpa'=>'1',
            'address'=>'s/d'
        ]);
        \App\Models\User::create([
            'type_identification'=>'cedula',
            'identification'=>'0000000001',
            'name'=>'User',
            'lastname'=>'Administrator',
            'email'=>'admin@yesdelivery.com',
            'password'=>bcrypt('123456'),
            'cod_rol'=>1,
            'active'=>1,
            'cod_nationality'=>57,
            'date_birth'=>'2022/01/01',
            'cod_dpa'=>'1',
            'address'=>'s/d'
        ]);
    }
}
