<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewDescription extends Model
{
    use HasFactory;

    protected $table = 'view_description';
    
    protected $fillable = [
        'dbid',
        'viewname',
        'language',
        'description'
    ];

    public function dbDescription()
    {
        return $this->belongsTo(DbDescription::class, 'dbid');
    }

    public function information()
    {
        return $this->hasOne(ViewInformation::class, 'id_view');
    }

    public function columns()
    {
        return $this->hasMany(ViewColumn::class, 'id_view');
    }
}