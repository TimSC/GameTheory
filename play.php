<?php
require_once("state.php");
session_start();

//Get game state databases
$playerNameDb = new GlobalState(".private/playerName.db");
$playerActivityDb = new GlobalState(".private/playerActivity.db");
$nextGameDb = new GlobalState(".private/nextGame.db");

$playerActivityDb[session_id()] = time();


?>
<html>
<head>
<title>Game Theory</title>
</head>
<body>
<h1>Game Theory</h1>



<p><a href="forum.php">Back to Forum</a></p>
</body>
</html>
