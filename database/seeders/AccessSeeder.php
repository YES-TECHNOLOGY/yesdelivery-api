<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $access=\App\Models\Access::create([
            'name'=>'Display a listing of the users.',
            'endpoint'=>'/users',
            'method'=>'GET',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);

        $access= \App\Models\Access::create([
            'name'=>'Store a newly created user in storage.',
            'endpoint'=>'/users',
            'method'=>'POST',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);

        $access=  \App\Models\Access::create([
            'name'=>'Store a newly created user in storage.',
            'endpoint'=>'/users',
            'method'=>'POST',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);
        $access=  \App\Models\Access::create([
            'name'=>'Update the specified user in storage.',
            'endpoint'=>'/users/{id}',
            'method'=>'PUT',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);
        $access=  \App\Models\Access::create([
            'name'=>'Update the specified user in storage.',
            'endpoint'=>'/users/{id}',
            'method'=>'DELETE',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);

        /*ROLES*/
        $access=\App\Models\Access::create([
            'name'=>'Display a listing of the roles.',
            'endpoint'=>'/roles',
            'method'=>'GET',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);

        $access= \App\Models\Access::create([
            'name'=>'Store a newly created role in storage.',
            'endpoint'=>'/roles',
            'method'=>'POST',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);

        $access=  \App\Models\Access::create([
            'name'=>'Update the specified role in storage.',
            'endpoint'=>'/roles/{id}',
            'method'=>'PUT',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);

        $access=  \App\Models\Access::create([
            'name'=>'Remove the specified role from storage.',
            'endpoint'=>'/roles/{id}',
            'method'=>'DELETE',
        ]);

        \App\Models\AccessRole::create([
            'cod_rol'=>1,
            'cod_access'=>$access->cod_access
        ]);
    }
}
