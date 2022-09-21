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
            'identification'=>'0000000000',
            'ruc'=>null,
            'name'=>'User',
            'lastname'=>'Administrator',
            'email'=>'admin@mail.com',
            'password'=>bcrypt('123456'),
            'cod_rol'=>1
        ]);
    }
}
