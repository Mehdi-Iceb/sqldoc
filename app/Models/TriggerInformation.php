<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriggerInformation extends Model
{
    use HasFactory;

    protected $table = 'trigger_information';
    
    protected $fillable = [
        'id_trigger',
        'table',
        'schema',
        'type',
        'event',
        'state',
        'creation_date',
        'definition'
    ];

    // Convertir les dates en instances Carbon
    protected $casts = [
        'creation_date' => 'datetime'
    ];

    // Pas de timestamps pour cette table
    public $timestamps = false;

    public function triggerDescription()
    {
        return $this->belongsTo(TriggerDescription::class, 'id_trigger');
    }
}