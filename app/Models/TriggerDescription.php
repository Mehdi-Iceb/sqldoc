<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriggerDescription extends Model
{
    use HasFactory;

    protected $table = 'trigger_description';
    
    protected $fillable = [
        'dbid',
        'triggername',
        'language',
        'description'
    ];

    public function dbDescription()
    {
        return $this->belongsTo(DbDescription::class, 'dbid');
    }

    public function information()
    {
        return $this->hasOne(TriggerInformation::class, 'id_trigger');
    }
}