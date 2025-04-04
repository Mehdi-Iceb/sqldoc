<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableIndex  extends Model
{
    use HasFactory;

    protected $dateFormat = 'd-m-Y H:i:s';
   
   protected $table = 'table_index';

   protected $fillable = [
    'id',
    'id_table',
    'name',
    'type',
    'column',
    'properties'
   ];

    public function tabledescription()
    {
        return $this->belongsTo(TableDescription::class, 'id_table', 'id');
    }
}