<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // \App\Models\User::factory(10)->create();
        DB::table('files')->truncate();
        DB::table('users')->truncate();
        DB::table('rols')->truncate();
        DB::table('access')->truncate();
        DB::table('access_roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->call(RolSeeder::class);
        $this->call(AccessSeeder::class);
        $this->call(UserSeeder::class);
    }
}
