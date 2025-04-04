<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewInformation extends Model
{
    use HasFactory;

    protected $table = 'view_information';
    
    protected $fillable = [
        'id_view',
        'schema_name',
        'definition',
        'creation_date',
        'last_change_date'
    ];

    // Dates à traiter comme Carbon
    protected $dates = [
        'creation_date',
        'last_change_date'
    ];

    // Pas de timestamps automatiques pour cette table
    public $timestamps = false;

    public function viewDescription()
    {
        return $this->belongsTo(ViewDescription::class, 'id_view');
    }
}