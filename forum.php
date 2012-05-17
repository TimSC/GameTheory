<?php
require_once("state.php");
session_start();

$playerNameDb = new GlobalState(".private/playerName.db");
$playerActivityDb = new GlobalState(".private/playerActivity.db");
//echo $state->__isset('num');
//echo(isset($state['num']));
//$state['num'] = array(5);
//echo $state['num'];
//unset($state['2']);
//print_r($state->GetKeys());

if(isset($_POST['name']))
{
	$_SESSION['name'] = $_POST['name'];
	$playerNameDb[session_id()] = $_POST['name'];
}
$playerActivityDb[session_id()] = time();

?>
<html>
<head>

</head>
<body>
<h1>Game Theory</h1>
<p>Welcome <?php echo $_SESSION['name']; ?>



<p><a href="index.php">Exit Session</a></p>
</body>
</html>
