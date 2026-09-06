<?php

// Pets v2 by Greg

// Fully reworked system => much faster, more beauty code
// Took some fucking time to recode, but worth it for the future :P

// Shadow, Light, Earth, Fire, Water

class Pets
{
	public $petData;
	public $fedData;
	public $dungData;
	public $pvpData;
	public $bestLvlData;
	public $honor;
	
	public $blacksmithData;
	
	function __construct($petData = null, $fedData = null, $dungData = null, $pvpData = null, $bestLvlData = null, $selectID = null, $blacksmithData = null, $pethonor = null)
	{
		$select = [];
		
		if($petData === null)
			$select[] = "pets";
		
		if($fedData === null)
			$select[] = "petsFed";
		
		if($dungData === null)
			$select[] = "petsDung";
		
		if($pvpData === null)
			$select[] = "petsPvP";
		
		if($bestLvlData === null)
			$select[] = "petsBest2";
		
		if ($blacksmithData === null)
			$select[] = "blacksmith";
		
		if ($pethonor === null)
			$select[] = "pethonor";
		
		if(count($select) > 0)
		{
			$select = implode(", ", $select);
			if($selectID !== null)
			{
				$qry = $GLOBALS['db']->prepare("SELECT $select FROM players WHERE ID = :id");
				$qry->bindParam(':id', $selectID);
			}
			else
			{
				$qry = $GLOBALS['db']->prepare("SELECT $select FROM players WHERE ssid = :ssid");
				$qry->bindParam(':ssid', $GLOBALS['ssid']);
			}
			$qry->execute();
			$pD = $qry->fetchAll()[0];
			
			foreach($pD as $key => $data)
			{
				switch($key)
				{
					case "pets": $petData = $data; break;
					case "petsFed": $fedData = $data; break;
					case "petsDung": $dungData = $data; break;
					case "petsPvP": $pvpData = $data; break;
					case "petsBest2": $bestLvlData = $data; break;
					case "blacksmith": $blacksmithData = $data; break;
					case "pethonor": $pethonor = $data; break;
				}
			}
		}
		
		if($petData == "")
			$this->petData = explode("/", "0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0");
		else
			$this->petData = explode("/", $petData);
		
		if($fedData == "")
			$this->fedData = [];
		else
			$this->fedData = json_decode($fedData, true);
		
		if($dungData == "")
			$this->dungData = [0, 0, 0, 0, 0, 0];
		else
			$this->dungData = explode("/", $dungData);
		
		if($pvpData == "")
			$this->pvpData = [[0, 0, 0, 0, 0], [0, 0, 0, 0, 0]]; // [[enemy id, defense pet type, defense pet count, defense pet all lvl, reroll time], [fights (5)]]
		else
			$this->pvpData = json_decode($pvpData, true);
		
		$this->bestLvlData = $bestLvlData;
		
		if($blacksmithData == "")
			//$this->blacksmithData = explode("/", "4999999/4999999/5/0");
			$this->blacksmithData = explode("/", "0/0/5/0");
		else
			$this->blacksmithData = explode("/", $blacksmithData);
		
		$this->honor = $pethonor;
		
		$now = Misc::getNow();
		
		if ($this->pvpData[0][4] != $now)
			$this->newPvPEnemy();
	}
	
	public function havePets()
	{
		return ($this->petData[0] == 0 && $this->petData[1] == 0 && $this->petData[2] == 0) == false;
	}
	
	public function getPetsSave()
	{
		$now = Misc::getNow();
		
		// $bs = blacksmith data
		$cbs = $this->blacksmithData; // clean blacksmith
		
		$bs["stone"] = $cbs[0];
		$bs["crystal"] = $cbs[1];
		$bs["drop"] = $cbs[3] == $now ? $cbs[2] : 5;
		
		$pda = implode("/", $this->petData); // All pets lvl
		
		$dpa = implode("/", array_slice($this->dungData, 0, 5)); // Dungeon levels
		
		for($i = 0; $i <= 4; $i++)
			$dl[$i] = 1 + (2 * $this->dungData[$i]);
		
		$dla = implode("/", $dl); // Lvl of dungeon enemies
		
		$pvpEnemy = $this->pvpData[0];
		
		$fought = [0, 0, 0, 0, 0];
		
		$now = Misc::getNow();
		
		for($i = 0; $i < 5; $i++)
		{
			if ($this->pvpData[1][$i] == $now)
				$fought[$i] = 1;
		}
		
		$fought = implode("/", $fought);
		
		$fed = [];
		
		for($i = 0; $i < 100; $i++)
			$fed[] = 0;
		
		foreach($this->fedData as $key => $val)
		{
			$fed[$key] = $val[0];
		}
		
		$fed = implode("/", $fed);
		
		$qry = $GLOBALS['db']->prepare("SELECT * FROM players WHERE ssid = :ssid");
		$qry->bindParam(':ssid', $GLOBALS['ssid']);
		$qry->execute();
		$playerD = $qry->fetchAll(PDO::FETCH_ASSOC)[0];
		
		// rank
		$rank = $GLOBALS['db']->query("SELECT Count(ID) as `rank` FROM players WHERE pethonor > {$this->honor} OR (pethonor = {$this->honor} AND ID > {$GLOBALS["playerID"]})");
		$rank = $rank->fetch(PDO::FETCH_ASSOC)['rank'] + 1;
		
		/*$itm = $GLOBALS['db']->query("SELECT Count(ID) as c FROM items WHERE type = 16 AND item_id < 22 AND owner = {$GLOBALS["playerID"]}");
		$itmc = $itm->fetch(PDO::FETCH_ASSOC)['c'];

		if (($itmc > 0) && ($playerD['pets'] != "")) {
		$pdax = explode('/', $pda);
		$pdax[0] = -1;
		$pdax[15] = -1;
		$pdax[19] = -1;
		$pda = implode("/", $pdax);
		}*/
		
		return "1626/1857028/{$pda}/0/5/1/1/1/1/1/0/{$fed}/{$dpa}/0/0/0/0/0/0/0/0/{$fought}/0/0/0/{$pvpEnemy[0]}/0/{$rank}/{$this->honor}/{$pvpEnemy[2]}/{$pvpEnemy[3]}/1461697891/{$dla}/0/0/0/0/0/0/0/0/0/0/0/0/{$bs["stone"]}/{$bs["crystal"]}/{$bs["drop"]}/1487811639/{$playerD["food_black"]}/{$playerD["food_orange"]}/{$playerD["food_green"]}/{$playerD["food_red"]}/{$playerD["food_blue"]}/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/";
	}
	
	public function getPetStats($id, $lvl = 0)
	{
		if($lvl == 0)
			$lvl = $this->petData[$id - 1]; // My own pet
		
		$petWhere = intval($id / 20) + 1;
		
		switch($id) {
			case 20 :
			case 40 :
			case 60 :
			case 80 :
			case 100 :
				$petWhere -= 1;
			break;
		}
		
		$class = $this->getPetClass($id);
		
		// Pet stats - balanced in v2
		switch($class) {
			case 1 :
				$ps[0] = 11 + ( 1.3 * ($lvl - 1) );
				$ps[1] = 11 + ( 1.1 * ($lvl - 1) );
				$ps[2] = 11 + ( 1.1 * ($lvl - 1) );
				$ps[3] = 18 + ( 1.3 * ($lvl - 1) );
				$ps[4] = 15 + ( 1.3 * ($lvl - 1) );
			break;
			case 2 :
				$ps[0] = 11 + ( 1.1 * ($lvl - 1) );
				$ps[1] = 11 + ( 1.1 * ($lvl - 1) );
				$ps[2] = 11 + ( 1.3 * ($lvl - 1) );
				$ps[3] = 18 + ( 1.3 * ($lvl - 1) );
				$ps[4] = 15 + ( 1.3 * ($lvl - 1) );
			break;
			case 3 :
				$ps[0] = 11 + ( 1.1 * ($lvl - 1) );
				$ps[1] = 11 + ( 1.3 * ($lvl - 1) );
				$ps[2] = 11 + ( 1.1 * ($lvl - 1) );
				$ps[3] = 18 + ( 1.3 * ($lvl - 1) );
				$ps[4] = 15 + ( 1.3 * ($lvl - 1) );
			break;
		}
		
		// Round it so we'll get a normal int
		for($i = 0; $i <= 4; $i++)
			$ps[$i] = round($ps[$i]);
		
		return [$lvl, $petWhere, $class, $ps, $id]; // Lvl, type, class, stats[<0-4>], id
	}
	public function getPetClass($id)
	{
		$petClasses = "31122233312233112113"; // Shadow
		$petClasses .= "11223321122332211123"; // Light
		$petClasses .= "11331322113133222111"; // Earth
		$petClasses .= "33122332122333332121"; // Fire
		$petClasses .= "21111313312221221213"; // Water
		
		$petClasses = str_split($petClasses);
		
		return $petClasses[$id - 1];
	}
	
	public function petToMonster($data)
	{
		// dmg min, dmg max, health
		if($data[2] == 1)
		{
			$weaponMultiplier = 2.3;
			$healthMultiplier = 5;
			$statToUse = $data[3][0];
		}
		else if($data[2] == 2)
		{
			$weaponMultiplier = 5.5;
			$healthMultiplier = 2;
			$statToUse = $data[3][2];
		}
		else
		{
			$weaponMultiplier = 3;
			$healthMultiplier = 4;
			$statToUse = $data[3][1];
		}
		$dmg = 1 + floor($weaponMultiplier * $data[0]);
		$minmax = 2 + floor($data[0] * 55 / 100);
		$dmg_min = ($dmg - $minmax) * (1 + $statToUse / 10);
		$dmg_max = ($dmg + $minmax) * (1 + $statToUse / 10);
		
		$dmg_min = floor($dmg_min);
		$dmg_max = floor($dmg_max);
		
		$hp = $data[3][3] * $healthMultiplier * (($data[0] * 0.3) + 1);
		
		$shield = $data[2] == 1 ? 1 : 0;
		
		return new Monster($data[0], $data[2], $data[3][0], $data[3][1], $data[3][2], $data[3][3], $data[3][4], round($dmg_min), round($dmg_max), round($hp), 0, (799 + $data[4]), 0, 65535, $shield);
	}
	
	public function newPvPEnemy()
	{
		// Matchmaking lmao
		
		global $db, $playerID;
		
		$honor = $this->honor;
		
		// First select enemy
		$sqls = [
			"pethonor = $honor AND ID <> $playerID",
			"pethonor > $honor AND ID <> $playerID ORDER BY pethonor ASC",
			"pethonor < $honor AND ID <> $playerID ORDER BY pethonor DESC"
		];
		
		$enemies = []; // Enemies
		
		for($i = 0; $i < count($sqls); $i++)
		{
			$qry = $db->query("SELECT ID FROM players WHERE pets <> '' AND " . $sqls[$i] . " LIMIT 5");
			
			if ($qry->rowCount() > 0)
			{
				$enemies = $qry->fetchAll();
				break;
			}
		}
		
		if (count($enemies) == 0)
			$enemy = $playerID;
		else
			$enemy = $enemies[rand(0, (count($enemies) - 1))]["ID"];
		
		// Select further data about enemy
		$qry = $db->query("SELECT petsBest1, petsBest2, petsBest3 FROM players WHERE ID = $enemy");
		
		$data = $qry->fetchAll()[0];
		
		// [enemy id, defense pet type, defense pet count, defense pet all lvl, reroll time]
		
		$now = Misc::getNow();
		
		$this->pvpData[0] = [$enemy, $data["petsBest1"], $data["petsBest3"], $data["petsBest2"], $now];
	
		$petsPvP = json_encode($this->pvpData);
	
		$qry = $db->prepare("UPDATE players SET petsPvp = :pvp WHERE ID = $playerID");
		$qry->bindParam(":pvp", $petsPvP);
		$qry->execute();
	}
	
	public function recountBest()
	{
		global $db, $playerID;
		
		$allCount = $allLvl = [0, 0, 0, 0, 0];
		
		for ($i = 0; $i < 100; $i++)
		{
			$id = $i + 1;
			
			$petWhere = intval($id / 20);
			
			switch($id) {
				case 20 :
				case 40 :
				case 60 :
				case 80 :
				case 100 :
					$petWhere -= 1;
				break;
			}
			
			$pet = &$this->petData[$i];
			
			if ($pet > 0)
			{
				$allLvl[$petWhere] += $pet;
				$allCount[$petWhere]++;
			}
		}
		
		arsort($allLvl);
		
		$best1 = array_keys($allLvl)[0];
		
		$best2 = $allLvl[$best1];
		
		$best3 = $allCount[$best1];
		
		$best1++;
		
		
		
		// Get all lvl
		$this->dungData[5] = 0;
		
		for($i = 0; $i < 5; $i++)
			$this->dungData[5] += $allCount[$i];
		
		$dungData = implode("/", $this->dungData);
		
		// Update in DB
		$db->exec("UPDATE players SET petsBest1 = $best1, petsBest2 = $best2, petsBest3 = $best3, petsDung = '$dungData' WHERE ID = $playerID");
	}
	
	public function petsUnlockable($class, $start = 0, $limit = 19)
	{
		$base = ($class - 1) * 20;
		
		$ret = [];
		
		for($i = $start; $i <= $limit; $i++)
		{
			if($this->petData[$base + $i] == 0)
				$ret[] = $base + $i;
		}
		
		return $ret;
	}
}	