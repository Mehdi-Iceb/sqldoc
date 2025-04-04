<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsParameter extends Model
{
    use HasFactory;

    protected $table = 'ps_parameter';
    
    protected $fillable = [
        'id_ps',
        'name',
        'type',
        'output',
        'definition',
        'default_value',
        'description'
    ];

    // Pas de timestamps pour cette table
    public $timestamps = false;

    public function psDescription()
    {
        return $this->belongsTo(PsDescription::class, 'id_ps');
    }
}