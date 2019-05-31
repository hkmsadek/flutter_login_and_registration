<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Americanoplayer extends Model
{
    protected $fillable = ['user_id', 'tournament_id'];
    public function user(){
        return $this->belongsTo('App\User');
    }
}
