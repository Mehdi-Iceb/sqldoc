<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableRelation  extends Model
{
    use HasFactory;

    protected $dateFormat = 'd-m-Y H:i:s';
   
   protected $table = 'table_relations';

   protected $fillable = [
    'id',
    'id_table',
    'constraints',
    'column',
    'referenced_table',
    'referenced_column',
    'action'
   ];

    public function tabledescription()
    {
        return $this->belongsTo(TableDescription::class, 'id_table', 'id');
    }
}