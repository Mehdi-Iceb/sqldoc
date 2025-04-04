<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FunctionDescription extends Model
{
    use HasFactory;

    protected $table = 'function_description';
    
    protected $fillable = [
        'dbid',
        'functionname',
        'language',
        'description'
    ];

    public function dbDescription()
    {
        return $this->belongsTo(DbDescription::class, 'dbid');
    }

    public function information()
    {
        return $this->hasOne(FuncInformation::class, 'id_func');
    }

    public function parameters()
    {
        return $this->hasMany(FuncParameter::class, 'id_func');
    }
}