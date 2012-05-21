<?php
require_once("state.php");
session_start();

//Get game state databases
$playerNameDb = new GlobalState(".private/playerName.db");
$playerActivityDb = new GlobalState(".private/playerActivity.db");
$nextGameDb = new GlobalState(".private/nextGame.db");

//Process form to set player name
if(isset($_POST['name']))
{
	$_SESSION['name'] = $_POST['name'];
	$playerNameDb[session_id()] = $_POST['name'];
	$nextGameDb[session_id()] = Null;
}
$playerActivityDb[session_id()] = time();
if(!isset($_SESSION['name'])) die("Player name not set");

if(!isset($playerActivityDb[session_id()]))
	die("Player not registered");

//Get List of players
$playerList = $playerNameDb->GetKeys();
?>
<html>
<head>
<title>Game Theory</title>
</head>
<body>
<h1>Game Theory</h1>
<p>Welcome <?php echo $_SESSION['name']; ?></p>

<p><a href="play.php">Play</a></p>

<table border="1">

<tr>
<td>Name</td>
<td>Last Active</td>
</tr>

<?php
//Print rows for players in the game
foreach($playerList as $sesId)
{
?>
<tr>
<td><?php echo $playerNameDb[$sesId];?></td>
<td><?php echo time() - $playerActivityDb[$sesId];?> seconds ago</td>
</tr>
<?php
}
?>

</table> 
<p><a href="index.php">Exit Session</a></p>
</body>
</html>
