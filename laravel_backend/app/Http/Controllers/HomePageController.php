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

class HomePageController extends Controller
{
   protected $helper;
   protected $divMatches;
   public function __construct(CommonClass $customhelper, DivMatchAndRank $divRank){
      $this->helper = $customhelper;
      $this->divMatches = $divRank;
   }
    public function getUpcomingMatches(Request $reqest){
        $user = User::getCurrentUser($reqest);
        $uid = $user->id; 
        
        
        // get the team id 
        $team = Team::where('playerOneId', $uid)->orWhere('playerTwoId', $uid)->first();
        if(!$team){
           return response()->json([
              'success' => false, 
              'msg' => 'You are not a part of any team. Once you have team and matches you will see them here.'
           ]);
        }
        // get the tournaments lists from the group. it is a way to find all the clubs data if the user is
        // some how playing in multiple clubs and in torunaments. this way it is effeicient.... 
        $tr = Group::where('team_id', $team->id)->where('isRunning', 0)->groupBy('tournament_id')->orderBy('id', 'desc')->get(['division_id']); 
        if(count($tr) == 0){
         return response()->json([
            'success' => false, 
            'msg' => 'There is no matches for you. Once there are matches, you will see them here!'
         ]);
        }
        $todaysDate = date("Y-m-d");
        // UPCOMING MATCHES BY DIVISION ID 
        $fixtures = [];
        $currentDivs = [];



        foreach($tr as $divId){
           
          // future matches...
          $matches = Fixtureandresult::where('playingDate', '>=', $todaysDate)->where('division_id', $divId['division_id'])
                                    ->with(['home'=> function($q){
                                        $q->select('id', 'teamName');
                                     }])
                                     ->with(['away'=> function($q){
                                        $q->select('id', 'teamName');
                                     }])
                                     ->with(['home.player1'=> function($q){
                                        $q->select('id', 'profilePic','firstName','lastName','mobile','phone', 'email');
                                     }]) 
                                     ->with(['home.player2'=> function($q){
                                        $q->select('id', 'profilePic','firstName','lastName', 'mobile','phone', 'email');
                                     }])
                                     ->with(['away.player1'=> function($q){
                                        $q->select('id', 'profilePic','firstName','lastName', 'mobile', 'phone', 'email');
                                     }]) 
                                     ->with(['away.player2'=> function($q){
                                        $q->select('id', 'profilePic','firstName','lastName', 'mobile', 'phone', 'email');
                                     }])
                                     ->with(['div'=> function($q){
                                        $q->select('id', 'divisionName','isPlayOff');
                                     }])
                                     ->with(['court'=> function($q){
                                        $q->select('id', 'courtName');
                                     }])
                                     ->with(['tr'=> function($q){
                                        $q->select('id', 'tournamentName');
                                     }])


                                    ->orderBy('playingDate', 'asc')->get();

         
      foreach($matches as $d){
         $date = $d['playingDate']; 
         $d['originalDate'] = $d['playingDate'];
         $d['playingDate'] = $this->customMonthName($date);
      }
      
      $divs = $this->helper->singleDivData($divId['division_id']);
      $ranks = $divs['rank'];
      $currentRank=null; 
      $i = 1;
      foreach($ranks as $r){
         if($r['team_id']===$team->id){
            $currentRank['isDefault'] = false;
            $currentRank['divisionName'] = $divs['divisionName'];
            $currentRank['teamName'] = $team['teamName'];
            $currentRank['points'] = $r['points'];
            $currentRank['matchPlayed'] = $r['matchePlayed'];
            $currentRank['position'] = $i;
            $currentRank['status'] = $r['status'];
            $currentRank['trId'] = $divs['tournament_id'];
            $currentRank['divId'] = $divId['division_id'];
            break;
         }
         $i++;
      }


      // PUSH THE FORMATED DATA
      foreach($matches as $m){
         array_push($fixtures, $m); // add matches in the array 
      }
     



      if(count($ranks)===0){
         // make a custom object... 
         $currentRank['position'] = 0;
         $currentRank['isDefault'] = true;
         $currentRank['divisionName'] = $divs['divisionName'];
         $currentRank['teamName'] = $team['teamName'];
         $currentRank['points'] = 0;
         $currentRank['matchPlayed'] = 0;
         $currentRank['status'] = 'no';
         $currentRank['trId'] = $divs['tournament_id'];
         $currentRank['divId'] = $divId['division_id'];
      }

      array_push($currentDivs, $currentRank); // add current divisoins in the array 



   
   } // END OF FOREACH LOOP FOR ALL DATA... 
   if(count($fixtures) == 0){
      return response()->json([
         'success' => false, 
         'msg' => 'There is no matches for you. Once there are matches, you will see them here!'
      ]);
   }

   // SORT THE MATCHES BY DATE... 
   $fixtures = array_values(array_sort($fixtures, function ($value) {
      return $value['originalDate'];
    }));
    

   return response()->json([
      'success' => true, 
      'matches' => $fixtures, 
      'currentDivs' => $currentDivs,
   ]);
     

      //   return $user;
    }

    public function customMonthName($date){
      $d = date_parse_from_format("Y-m-d", $date);
     // return $d;
      $number =  $d["month"];
      $month = ['Jan', "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      return $d['day'].' '.$month[$number-1];
   }
   public function getStandings($id){
      $div = $this->helper->singleDivData($id);
      // get the tournament name 
      $tr = $this->helper->tournamentName($div['tournament_id']);
      return response()->json([
         'ranks' => $div['rank'], 
         'divisionName' => $div['divisionName'],
         'tournamentName' => $tr['tournamentName']
      ],200);
      //return respinse
      
   }
   
   public function getTrandDiv(Request $reqest, $trId, $divId){
      $user = User::getCurrentUser($reqest);
      $uid = $user->id; 
      $team = Team::where('playerOneId', $uid)->orWhere('playerTwoId', $uid)->first();
      $data =  Tournament::where('id', $trId)->with(['div'=>function($q){
          $q->select('id', 'tournament_id', 'divisionName');
      }])->first(['id', 'tournamentName', 'tournamentType']);

      if(count($data['div'])==0){
         return response()->json([
            'success' => false, 
            'msg' => 'Sorry there is no data to show in this tournament'
         ]);
      }
      
      // mark my division as selected... 
      $index = 0;
      $myDiv = null;
      if($divId==0){ // if other player/user is watching
         $divId = $data['div'][0]['id'];
      }
      foreach($data['div'] as $k=> $d){
         if($d['id']==$divId){
            $d['isSelected'] = true;
            $index = $k;
            $myDiv = $d;
         }else{
            $d['isSelected'] = false;
         }
      }
      
      $matches = $this->matches($divId);

      $myMatches = [];
      
      foreach($matches as $m){
         if($m['homeTeam']==$team->id || $m['awayTeam']==$team->id){
            array_push($myMatches, $m);
         }
      }
      $divRank = [];
      $divRank['isFull'] = true; 
      $divRank['ranks'] = $matches[0]['div']['rank']; 
      $myMatches = collect($myMatches);
      
      return response()->json([
         'success' => true, 
         'matches' => $matches->groupBy('round'),
         'divRanking' => $divRank,
         'divisionsLists' => $data, 
         'index' => $index, 
         'myDiv' => $myDiv,
         'myMatches' => $myMatches->groupBy('round'),
      ]);

      
     


   }
   public function matches($id){
      return $matches = $this->divMatches->getMatchesAndRank($id);
   }

   public function getMatchesWithRank($divId){
      $matches = $this->matches($divId);
      $divRank = [];
      $divRank['isFull'] = true; 
      $divRank['ranks'] = $matches[0]['div']['rank']; 
     
      return response()->json([
         'success' => true, 
         'matches' => $matches->groupBy('round'),
         'divRanking' => $divRank,
      ]);
   }
   public function teamStats($id){
      $team = Team::where('id', $id)->with(['player1' => function($q){
                  $q->select('id', 'firstName', 'lastName', 'email', 'phone', 'profilePic');
               }])->with(['player2' => function($q){
                  $q->select('id', 'firstName', 'lastName', 'email', 'phone', 'profilePic');
               }])->first();
      $lastFives = $this->lastFives($id);
      $currentDiv = $this->getCurrentDiv($id);
      $totalRecords = $this->getTotalRecords($id);
      // // get the payment status of a team 
      // $paymentHistory = $this->query->teamPaymentHistory($id);
      $totalWon = 0; 
      $totalLoss= 0; 
      $totalDraw = 0; 
      $totalMatches = count($totalRecords);
      foreach($totalRecords as $r){
          if($r->result==='W'){
              $totalWon++;
          }
          if($r->result==='L'){
              $totalLoss++;
          }
          if($r->result==='D'){
              $totalDraw++;
          }
      }
      return response()->json([
          'lastFives' => $lastFives,
          'currentDiv' => $currentDiv,
          'totalWon' => $totalWon,
          'totalLoss' => $totalLoss,
          'totalDraw' => $totalDraw,
          'totalMatches' => $totalMatches,
          'team' => $team,
          
      ]);
   }
   public function lastFives($id){
      return DB::select("
      SELECT 
          CASE 
          WHEN homeTeamPoint = awayTeamPoint 
              THEN 'D'
          WHEN `homeTeam` = $id AND homeTeamPoint > awayTeamPoint 
              THEN 'W'
          WHEN `homeTeam` = $id AND homeTeamPoint < awayTeamPoint 
              THEN 'L'
          WHEN `awayTeam` = $id AND homeTeamPoint < awayTeamPoint 
              THEN 'W'
          WHEN `awayTeam` = $id AND homeTeamPoint > awayTeamPoint 
              THEN 'L'
          END AS result   
      FROM fixtureandresults 
      WHERE (`homeTeam` = $id OR awayTeam=$id) 
          AND over = 1 
      ORDER BY id DESC LIMIT 5
      
      ");
  }
public function getCurrentDiv($id){
   return Group::where('team_id', $id)->with('div')->orderBy('id', 'desc')->get();
}
public function getTotalRecords($id){ // it expects a team id
   return DB::select("
   SELECT 
       CASE 
       WHEN homeTeamPoint = awayTeamPoint 
           THEN 'D'
       WHEN `homeTeam` = $id AND homeTeamPoint > awayTeamPoint 
           THEN 'W'
       WHEN `homeTeam` = $id AND homeTeamPoint < awayTeamPoint 
           THEN 'L'
       WHEN `awayTeam` = $id AND homeTeamPoint < awayTeamPoint 
           THEN 'W'
       WHEN `awayTeam` = $id AND homeTeamPoint > awayTeamPoint 
           THEN 'L'
       END AS result   
   FROM fixtureandresults 
   WHERE (`homeTeam` = $id OR awayTeam=$id) 
       AND over = 1 
   ORDER BY id DESC
   
   ");
}
public function playerStats($id){
   // GET THE OVERALL LIFE TIME STATS FOR A PLAYER USING PLAYER'S TEAM ID....
   $totalWon = 0; 
   $totalLoss= 0; 
   $totalDraw = 0; 
   $totalMatches = 0;
   $team = Team::where('playerOneId', $id)->orWhere('playerTwoId', $id)
               ->with(['player1' => function($q){
                  $q->select('id', 'firstName', 'lastName', 'email', 'phone', 'profilePic');
               }])
               ->with(['player2' => function($q){
                  $q->select('id', 'firstName', 'lastName', 'email', 'phone', 'profilePic');
               }])->get();
   
   $isFound = false;
   $isDivPlaying = false;
   $isAmericanoPlaying = false;
   foreach($team as $t){
      $totalRecords = $this->getTotalRecords($t['id']);
      foreach($totalRecords as $r){
         if($r->result==='W'){
            $totalWon++;
         }
         if($r->result==='L'){
            $totalLoss++;
         }
         if($r->result==='D'){
            $totalDraw++;
         }
      }
      $totalMatches += count($totalRecords);

      // SEARCH IF THE TEAM IS PLAYING IN ANY DIVISION... 
      if($totalMatches<0){
         $isDivPlaying = true;
         $isFound = true;
      }else{
         if(!$isFound){
            $groupCount = Group::where('team_id', $t['id'])->count();
            if($groupCount > 0){
               $isDivPlaying = true;
               $isFound = true;
            }
            
         }
      }
      // SEARCH IF THE PLAYER IS PLAYING ANY AMERICANO GAMES 

      $americanoCount = Americanoaddedplayer::where('player_id', $id)->count();
      if($americanoCount > 0){
         $isAmericanoPlaying = true;
      }


   }
   // GET PLAYER INFORMATION

   $player = User::where('id', $id)->first(['id', 'firstName', 'lastName', 'profilePic', 'phone', 'email', 'racket', 'playingHand', 'state']);

   



   return response()->json([
      'totalWon' => $totalWon,
      'totalLoss' => $totalLoss,
      'totalDraw' => $totalDraw,
      'totalMatches' => $totalMatches,
      'player' => $player,
      'team' => $team,
      'isAmericanoPlaying' => $isAmericanoPlaying,
      'isDivPlaying' => $isDivPlaying,
   ]);

}
public function tournamentListingAndInvitations(Request $request){
   $user = User::getCurrentUser($request);
   $uid = $user->id;
   
   // $uid = 347;
   $runningLeagues = $this->getLeagueListing('running');
   $completedLeagues = $this->getLeagueListing('completed');
   $events = Event::whereHas('invitation', function($q) use ($uid){
                  $q->where('user_id', $uid);
            })->orderBy('id', 'desc')->get();
  
   return response()->json([
       'runningTr' => $runningLeagues,
       'completedTr' => $completedLeagues,
       'events' => $events
   ]);
}
public function getLeagueListing($type){
   return League::where('status', $type)
                     ->with('image')
                     ->with('managers.user')
                     ->with('club')
                     ->orderBy('id', 'desc')
                     ->get();
 }
 public function eventInfo($id){
    $event = Event::where('id', $id)
                  ->withCount(['singedup' => function($q){
                     $q->where('status', 'accepted');
                  }])
                  ->with('acceptedUsers.user')
               ->first();
   return response()->json([
      'event' => $event,
      'success' => true
   ]);
   
 }






}
