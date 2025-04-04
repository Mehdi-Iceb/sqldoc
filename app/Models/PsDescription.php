<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsDescription extends Model
{
    use HasFactory;

    protected $table = 'ps_description';
    
    protected $fillable = [
        'dbid',
        'psname',
        'language',
        'description'
    ];

    public function dbDescription()
    {
        return $this->belongsTo(DbDescription::class, 'dbid');
    }

    public function information()
    {
        return $this->hasOne(PsInformation::class, 'id_ps');
    }

    public function parameters()
    {
        return $this->hasMany(PsParameter::class, 'id_ps');
    }
}