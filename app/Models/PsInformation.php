<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsInformation extends Model
{
    use HasFactory;

    protected $table = 'ps_information';
    
    protected $fillable = [
        'id_ps',
        'schema',
        'creation_date',
        'last_change_date',
        'definition'
    ];

    // Convertir les dates en instances Carbon
    protected $casts = [
        'creation_date' => 'datetime',
        'last_change_date' => 'datetime'
    ];

    // Pas de timestamps pour cette table
    public $timestamps = false;

    public function psDescription()
    {
        return $this->belongsTo(PsDescription::class, 'id_ps');
    }
}