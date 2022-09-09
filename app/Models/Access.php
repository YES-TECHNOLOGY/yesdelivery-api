<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    use HasFactory;
    protected $primaryKey='cod_access';
    protected $table='access';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable=[
        "name",
        "endpoint",
        "method",
        "detail"
    ];

    protected $hidden=[
        "created_at",
        "updated_at",
        'cod_menu'
    ];

    /**
     *returns the menu to which the access belongs
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function menu(){
        return $this->belongsTo(Menu::class,'cod_menu');
    }

    /**
     *
     * returns the roles in which the access is found
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(){
        return $this->belongsToMany(Role::class,'access_role','cod_access','cod_rol');
    }
}
