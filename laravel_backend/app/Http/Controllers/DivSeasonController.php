<?php
namespace App\Http\Controllers;
use App\Team;
use App\User;
use App\Event;
use App\Group;
use App\League;
use App\Tournament;
use App\Fixtureandresult;
use App\Classes\CommonClass;
use Illuminate\Http\Request;
use App\Americanoaddedplayer;
use App\Classes\DivMatchAndRank;
use Illuminate\Support\Facades\DB;

class DivSeasonController extends Controller
{
    public function getSeasonWithDiv($trId){
        $tr = Tournament::where('league_id', $trId)->orderBy('id', 'desc')->get();
        return response()->json([
            'tr' => $tr
        ]);
    }
}
