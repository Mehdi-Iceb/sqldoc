<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DbDescription  extends Model
{
   use HasFactory;

   protected $dateFormat = 'd-m-Y H:i:s';
   
   protected $table = 'db_description';

   protected $fillable = [
       'user_id',
       'language',
       'dbname',
       'project_id',
       'description', 
   ];

   public function user()
   {
       return $this->belongsTo(User::class);
   }

   public function project()
   {
    return $this->belongsTo(Project::class);
   }

   public function functiondescription()
   {
        return $this->hasMany(FunctionDescription::class);
   }
}