<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table='menu';
    protected $primaryKey="cod_menu";

    protected $fillable=[
        'name',
        'order',
        'icon',
        'path',
        'cod_menu_parent',
        'create_at',
        'update_at'
    ];
    protected $hidden=[
        'create_at',
        'update_at'
    ];

    public function menuParent(){
        return $this->belongsTo(Menu::class,'cod_menu_parent');
    }

    public function subMenu(){
        return $this->hasMany(Menu::class,'cod_menu_parent');
    }

    public function access(){
        return $this->hasMany(Access::class,'cod_menu');
    }
}
