<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    protected $fillable = [
        'tournament_id', 'courtName'
    ];
    protected $casts = [
        'tournament_id' => 'integer',
        
        
        
    ];
}
