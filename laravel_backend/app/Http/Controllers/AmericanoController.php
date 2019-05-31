<?php
namespace App\Http\Controllers;
use App\Team;
use App\User;
use App\Event;
use App\Group;
use App\League;
use App\Tournament;
use App\Americanopoint;
use App\Americanomatches;
use App\Fixtureandresult;
use App\Classes\CommonClass;
use Illuminate\Http\Request;
use App\Americanoaddedplayer;
use App\Classes\DivMatchAndRank;
use Illuminate\Support\Facades\DB;

class AmericanoController extends Controller
{
    public function getMatches($trId, $group){
        $matches = Americanomatches::where('tournament_id', $trId)->where('groupName', $group)
                ->with(['player1', 'player2', 'player3', 'player4', 'court'])
                ->get();
        $matches = $matches->groupBy('round');

        $matchesData = []; 

        foreach($matches as $round){
           array_push($matchesData, $round);
        }
        $points = Americanopoint::where('tournament_id', $trId)->where('groupName', $group)
        ->with('player')
        ->selectRaw('id, tournament_id, player_id, round1, round2, round3, round4,round5,round6, round7, ( sum(round1)+sum(round2)+sum(round3)+sum(round4)+sum(round5)+sum(round6)+sum(round7) ) as total')
        ->orderBy('total', 'desc')
        ->groupBy('player_id')
        ->get();
        $rank = [];
        $rank['isFull'] = true; 
        $rank['ranks'] = $points; 
        
        
        return response()->json([
            'success' => true, 
            'matches' => $matchesData,
            'points' => $rank,
        ]);
    }
}
