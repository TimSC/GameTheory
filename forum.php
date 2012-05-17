<?php
require_once("state.php");
session_start();

$state = new GlobalState("game.db");
//echo $state->__isset('num');
//echo(isset($state['num']));
//$state['num'] = 5;
//unset($state['2']);
//print_r($state->GetKeys());

if(isset($_POST['name']))
{
	$_SESSION['name'] = $_POST['name'];
}

?>
<html>
<head>

</head>
<body>
<h1>Game Theory</h1>
<p>Welcome <?php echo $_SESSION['name']; ?>


</body>
</html>
