<?php
session_start();
session_destroy();
?>
<html>
<head>
<title>Game Theory</title>
</head>
<body>
<h1>Game Theory</h1>
<form action="forum.php" method="post">
Please enter a player name or alias: <input type="text" name="name" /><br />
<input type="submit" value="Submit" />
</form>

</body>
</html>
