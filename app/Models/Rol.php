<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;
    protected $table = 'rollen';
    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
