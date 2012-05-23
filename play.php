<?php
require_once("state.php");
session_start();

$gameTimeout = (5 * 60); //How long players have to respond

//Get game state databases
$playerNameDb = new GlobalState(".private/playerName.db");
$playerActivityDb = new GlobalState(".private/playerActivity.db");
$nextGameDb = new GlobalState(".private/nextGame.db");
$gamesDb = new GlobalState(".private/games.db");
$scoresDb = new GlobalState(".private/scores.db");

if(!isset($_SESSION['name'])) die('<a href="index.php">Player name not set.</a>');
if(!isset($playerActivityDb[session_id()]))
	die('<a href="index.php">Player not registered</a>');

$playerActivityDb[session_id()] = time();


$gameid = Null;
if (isset($_GET['gameid'])) $gameid = $_GET['gameid'];

//Process next game request
$nextGame = $nextGameDb[session_id()];
if(isset($_GET['nextgame']) and $nextGame != Null)
{
	$game = $gamesDb[$nextGame];
	$py1res = $game['player1response'];
	$py2res = $game['player2response'];
	//Check game is over
	if($py1res != Null and $py2res != Null)
	{
		//Free player from this game
		$nextGameDb[session_id()] = Null;
		$nextGame = Null;
		$game = Null;
	}

}

//Determine which player 
if($gameid == Null)
	$gameid = $nextGameDb[session_id()];
$playerNum = 0;
$game = Null;
if($gameid != Null)
{
	$game = $gamesDb[$gameid];
	if(session_id() == $game['player1']) $playerNum = 1;
	if(session_id() == $game['player2']) $playerNum = 2;
}

//A player game try to timeout a game after a certain period
if($game != Null && isset($_GET['timeout']))
{
	$createdTime = $game['created'];
	$timeRemaining = $createdTime + $gameTimeout - time();
	if ($timeRemaining <= 0)
	{
		//echo "timeout";
		//Release players from expired game
		$nextGameDb[$game['player1']] = Null;
		$nextGameDb[$game['player2']] = Null;
		$game['timedout'] = True;
		$gamesDb[$gameid] = $game;
	}
}

//Process gamer game response
$updateScore = False;
$nextGame = $nextGameDb[session_id()];
if($nextGame != Null and isset($_POST['player1']) and $playerNum == 1 and !isset($game['player1response']))
{
	$game = $gamesDb[$nextGame];
	
	//Update database with play
	$game['player1response'] = $_POST['player1'];
	$gamesDb[$nextGame] = $game;

	if(isset($game['player2response']) and !$game['timedout'])
	{
		//Update score
		$updateScore = True;
	}
}

if($nextGame != Null and isset($_POST['player2']) and $playerNum == 2 and !isset($game['player2response']))
{
	$game = $gamesDb[$nextGame];
	
	//Update database with play
	$game['player2response'] = $_POST['player2'];
	$gamesDb[$nextGame] = $game;

	if(isset($game['player1response']) and !$game['timedout'])
	{
		//Update score
		$updateScore = True;
	}
}

//If game is over, show score change
$p1GameScore = Null;
$p2GameScore = Null;
$gameOver = False;
if($gameid != Null)
{
	$game = $gamesDb[$gameid];
	if($game['timedout']) $gameOver = True;
	$py1res = $game['player1response'];
	$py2res = $game['player2response'];
	//Check game is over
	if($py1res != Null and $py2res != Null)
	{
		$gameOver = True;
		if($py1res == 1 and $py2res == 1) {$p1GameScore = 1; $p2GameScore = 1;}
		if($py1res == 2 and $py2res == 1) {$p1GameScore = 2; $p2GameScore = -3;}
		if($py1res == 1 and $py2res == 2) {$p1GameScore = -3; $p2GameScore = 2;}
		if($py1res == 2 and $py2res == 2) {$p1GameScore = -1; $p2GameScore = -1;}
	}

}

//If game is over, update total scores
if($updateScore)
{
	$p1Score = $scoresDb[$game['player1']];
	$p1Score += $p1GameScore;
	$scoresDb[$game['player1']] = $p1Score;

	$p2Score = $scoresDb[$game['player2']];
	$p2Score += $p2GameScore;
	$scoresDb[$game['player2']] = $p2Score;
}

//Determine next game
$nextGame = $nextGameDb[session_id()];
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
		$gameNew = array('player1'=>session_id(),'player2'=>$nextOpponent,
			'player1response'=>Null,'player2response'=>Null, 
			'created' => time(), 'timedout' => False);
		$gameidNew = $gamesDb->Pop($gameNew);
		$nextGameDb[session_id()] = $gameidNew;
		$nextGameDb[$nextOpponent] = $gameidNew;
		$playerNum = 1;
	}
}

//Get details for current game
$game = $gamesDb[$gameid];
$player1 = Null;
$player2 = Null;
$createdTime = Null;
$player1Name = Null; $player2Name = Null;
$pl1controls = False;
$pl2controls = False;
if($game != Null)
{
	$player1Name = $playerNameDb[$game['player1']];
	$player2Name = $playerNameDb[$game['player2']];
	$py1res = Null;
	$py2res = Null;
	$createdTime = $game['created'];
	//If both players have set their answer, make it publicly viewable
	if(isset($game['player1response']) and isset($game['player2response']))
	{
		$py1res = $game['player1response'];
		$py2res = $game['player2response'];
	}
	//If player already responded to game, set privately visible selection
	if($playerNum==1) $py1res = $game['player1response'];
	if($playerNum==2) $py2res = $game['player2response'];

	//echo $py1res.",".$py2res;
	if($playerNum==1 and $py1res == Null and !$gameOver) $pl1controls = True;
	if($playerNum==2 and $py2res == Null and !$gameOver) $pl2controls = True;
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
<?php if($pl1controls or $pl2controls) //Option to submit a choice
{
?>
<input type="submit" value="Submit" />
<?php }
elseif(!$gameOver)
{
$timeRemaining = $createdTime + $gameTimeout - time();
?>
<p>Waiting for other player. <a href="play.php?gameid=<?php echo $gameid; ?>">Reload</a></p>
<?php 
if ($timeRemaining > 0)
{
?>
<p>Time remaining for other player to respond: <?php echo $timeRemaining; ?> seconds.</p>
<?php
}
else
{
?>
<p>The other player has not responded in time. You may <a href="play.php?gameid=<?php echo $gameid; ?>&timeout">end this game</a>.</p>
<?php
}
}

if($playerNum != 0 and $gameOver) //Prompt for next game
{
?>
<p><a href="play.php?nextgame">Next Game</a></p>
<?php } ?>
</form>
<?php
} //End of printing game to HTML

if($game == Null) //Waiting for other players HTML
{
?>
<h2>Waiting for other players. <a href="play.php">Reload page</a>.</h2>
<?php

} //End of waiting for other players

if($p1GameScore != Null and $p2GameScore != Null)
{
?>
<p>For this game, <?php echo $player1Name.": ".$p1GameScore; ?>, <?php echo $player2Name.": ".$p2GameScore; ?></p>
<?php
}

if($game != Null)
{
	$p1TotalScore = $scoresDb[$game['player1']];
	$p2TotalScore = $scoresDb[$game['player2']];
?>
<p>Total score, <?php echo $player1Name.": ".$p1TotalScore; ?>, <?php echo $player2Name.": ".$p2TotalScore; ?></p>

<p><a href="play.php?gameid=<?php echo $gameid; ?>">Permalink</a></p>

<?php

if($game['timedout'])
{
?>
<p>This game timed out.</p>
<?php
}
}
?>

<p><a href="forum.php">Back to Forum</a></p>
</body>
</html>
