<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableDescription  extends Model
{
    use HasFactory;

    protected $dateFormat = 'd-m-Y H:i:s';
   
   protected $table = 'table_description';

   protected $fillable = [
    'id',
    'dbid',
    'tablename',
    'language',
    'description',
    'created_at',
    'updated_at',
   ];

   protected $hidden =[
    'created_at', 
    'updated_at'
    ];

    public function dbdescription()
    {
        return $this->belongsTo(DbDescription::class);
    }

    public function structures()
    {
        return $this->hasMany(TableStructure::class, 'id_table');
    }
}