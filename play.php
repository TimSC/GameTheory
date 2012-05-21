<?php
require_once("state.php");
session_start();

//Get game state databases
$playerNameDb = new GlobalState(".private/playerName.db");
$playerActivityDb = new GlobalState(".private/playerActivity.db");
$nextGameDb = new GlobalState(".private/nextGame.db");
$gamesDb = new GlobalState(".private/games.db");

if(!isset($_SESSION['name'])) die("Player name not set");
if(!isset($playerActivityDb[session_id()]))
	die("Player not registered");

$playerActivityDb[session_id()] = time();

//Determine which player 
$nextGame = $nextGameDb[session_id()];
$playerNum = 0;
if($nextGame != Null)
{
	$game = $gamesDb[$nextGame];
	if(session_id() == $game['player1']) $playerNum = 1;
	if(session_id() == $game['player2']) $playerNum = 2;
}

//Process response
$nextGame = $nextGameDb[session_id()];
if($nextGame != Null and isset($_POST['player1']))
{
	$game = $gamesDb[$nextGame];
	
	if ($playerNum == 0) die("Can't determine which player you are.");

	//Update database with play
	if($playerNum == 1) $game['player1response'] = $_POST['player1'];
	if($playerNum == 2) $game['player2response'] = $_POST['player2'];
	$gamesDb[$nextGame] = $game;
}

//If game is finished, release this player to find another game
if($nextGame != Null)
{
	$game = $gamesDb[$nextGame];
	$gameFinishedButActive = False;
	if($game != Null and $game['player1response'] != Null and $game['player2response'] != Null and $playerNum != 0)
	{
		//echo "Player ".$playerNum." done";
		$gameFinishedButActive = True;
	}
}

//Determine next game
$nextGame = $nextGameDb[session_id()];
$game = Null;
if($nextGame == Null)
{
	//Get List of players
	$playerList = $playerNameDb->GetKeys();

	//Remove player from the list
	if(($key = array_search(session_id(), $playerList, True)) !== FALSE) {unset($playerList[$key]);}
		

	//Check for waiting opponents
	$waitingOpponents = array();
	foreach($playerList as $player)
	{
		$oppWaiting = $nextGameDb[$player];
		if ($oppWaiting == Null) array_push($waitingOpponents,$player); 
	}
	
	if (count($waitingOpponents)>0)
	{
		//Chose random opponent
		$nextOpponent = $waitingOpponents[rand(0,count($waitingOpponents)-1)];
	
		//Update database with arranged game
		$game = array('player1'=>session_id(),'player2'=>$nextOpponent,'player1response'=>Null,'player2response'=>Null);
		$gameId = $gamesDb->Pop($game);
		$nextGameDb[session_id()] = $gameId;
		$nextGameDb[$nextOpponent] = $gameId;
		$nextGame = $gameId;
	}
}
else
{
	$game = $gamesDb[$nextGame];
}

//Get details for current game
$player1 = Null;
$player2 = Null;
$pl1controls = False;
$pl2controls = False;
if($game != Null)
{
	$player1Name = $playerNameDb[$game['player1']];
	$player2Name = $playerNameDb[$game['player2']];
	$py1res = $game['player1response'];
	$py2res = $game['player2response'];
	//echo $py1res.",".$py2res;
	if($playerNum==1 and $py1res == Null) $pl1controls = True;
	if($playerNum==2 and $py2res == Null) $pl2controls = True;
}


?>
<html>
<head>
<title>Game Theory</title>
</head>
<body>
<h1>Game Theory</h1>
<?php
//echo $nextGame;
//print_r($game);
if($game != Null)
{
?>
<h2><span style="color: red;"><?php echo $player1Name;?></span> vs. <span style="color: blue;"><?php echo $player2Name;?></span></h2>
<form action="play.php" method="post">
<table border="1">
<tr>
<td></td>
<td style="color: red;"><input type="radio" name="player1" value="1" <?php if($py1res==1) echo 'checked="yes"';?> <?php if(!$pl1controls) echo "disabled";?>/>Cooperate</td>
<td style="color: red;"><input type="radio" name="player1" value="2" <?php if($py1res==2) echo 'checked="yes"';?> <?php if(!$pl1controls) echo "disabled";?>/>Defect</td>
</tr>
<tr>
<td style="color: blue;"><input type="radio" name="player2" value="1" <?php if($py2res==1) echo 'checked="yes"';?> <?php if(!$pl2controls) echo "disabled";?>/>Cooperate</td>
<td><span style="color: red;">+1</span>, <span style="color: blue;">+1</span></td>
<td><span style="color: red;">+2</span>, <span style="color: blue;">-3</span></td>
</tr>
<tr>
<td style="color: blue;"><input type="radio" name="player2" value="2" <?php if($py2res==2) echo 'checked="yes"';?> <?php if(!$pl2controls) echo "disabled";?>/>Defect</td>
<td><span style="color: red;">-3</span>, <span style="color: blue;">+2</span></td>
<td><span style="color: red;">-1</span>, <span style="color: blue;">-1</span></td>
</tr>
</table> 
<?php if($pl1controls or $pl2controls) echo '<input type="submit" value="Submit" />'; ?>
</form>
<?php
} //End of printing game to HTML

if($game == Null)
{
?>
<h2>Waiting for other players. <a href="play.php">Reload page</a>.</h2>
<?php
}
?>

<p><a href="forum.php">Back to Forum</a></p>
</body>
</html>
