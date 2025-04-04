<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $dateFormat = 'd-m-Y H:i:s';

    protected $table = 'projects';

    protected $fillable = [
        'name',
        'db_type',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dbdescripion()
    {
        return $this->Hasmany(DbDescription::class);
    }
}
