<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuncInformation extends Model
{
    use HasFactory;

    protected $table = 'func_information';
    
    protected $fillable = [
        'id_func',
        'type',
        'return_type',
        'definition',
        'creation_date',
        'last_change_date'
    ];

    // Rename field for ORM matching if needed
    protected $casts = [
        'creation_date' => 'datetime',
        'last_change_date' => 'datetime'
    ];

    // S'assurer que Laravel reconnaît le champ id_func
    public function getIdFuncAttribute()
    {
        return $this->attributes['id_func'];
    }

    public function functionDescription()
    {
        return $this->belongsTo(FunctionDescription::class, 'id_func');
    }
}