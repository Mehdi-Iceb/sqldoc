<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableStructure  extends Model
{
    use HasFactory;

    protected $dateFormat = 'd-m-Y H:i:s';
   
   protected $table = 'table_structure';

   protected $fillable = [
    'id',
    'id_table',
    'column',
    'type',
    'nullable',
    'key',
    'description',
    'rangevalues'
   ];

   public function tableDescription()
    {
        return $this->belongsTo(TableDescription::class, 'id_table', 'id');
    }
}