<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuncParameter extends Model
{
    use HasFactory;

    protected $table = 'func_parameter';
    
    protected $fillable = [
        'id_func',
        'name',
        'type',
        'output',
        'definition',
        'description'
    ];

    // Pas de timestamps pour cette table si votre BD n'en a pas
    public $timestamps = false;

    public function functionDescription()
    {
        return $this->belongsTo(FunctionDescription::class, 'id_func');
    }
}