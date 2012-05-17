<?php

class GlobalState implements arrayaccess
{
	private $fi = False;
	private $db = Null;

	function __construct($fina)
	{
		//Get game state
		$this->fi = fopen($fina,"r+");
		flock($this->fi, LOCK_EX);

		$this->db = new SQLite3($fina);
	}

	function __destruct()
	{
		//Finish With State
		unset($this->db);
		fclose($this->fi);
	}

	public function offsetSet($k, $v)
	{
		if ($this->offsetExists($k))
			$sql = 'UPDATE state SET val=\''.$this->db->escapeString($v).'\' WHERE key=\''.$this->db->escapeString($k)."'";
		else
			$sql = "INSERT INTO state (key, val) VALUES ('".$this->db->escapeString($k)."', '".$this->db->escapeString($v)."')";	
		$this->db->exec($sql) or die("SQL Failed");
	}

	public function offsetGet($k)
	{
		$results = $this->db->query('SELECT * FROM state WHERE key=\''.$this->db->escapeString($k)."'");
		$row = $results->fetchArray();
		return $row['val'];
	}

	public function offsetExists($k)
	{
		//Check if key exists
		$results = $this->db->query('SELECT COUNT(*) FROM state WHERE key=\''.$this->db->escapeString($k)."'");
		$row = $results->fetchArray();
		return ($row[0] > 0);
	}

	public function offsetUnset($k)
	{
		$sql = "DELETE FROM state WHERE key='".$this->db->escapeString($k)."'";
		$this->db->exec($sql) or die("SQL Failed");
	}

	public function GetKeys()
	{
		$out = array();
		$results = $this->db->query('SELECT key FROM state');
		while ($row = $results->fetchArray()) {
		    array_push($out,$row['key']);
		}
		return $out;
	}

}

?>
