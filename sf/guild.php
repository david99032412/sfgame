<?php

class Guild{

	//guild data
	public $data;

	//members
	public $players;

	//invites players (grey)
	public $invites, $slots;

	//guild fights
	public $fights;
public $skill;
	// public function Guild($guildData, $players){
	// 	$this->data = $guildData;
	// 	$this->players = $players;
	// }

	function __construct($guildID){
		$guildData = $GLOBALS['db']->query("SELECT * FROM guilds WHERE ID = $guildID");
		$this->data = $guildData->fetch(PDO::FETCH_ASSOC);
		
		$this->data['base'] = 50;
		
		$players = $GLOBALS['db']->query("SELECT players.ID, players.skill_treasure, players.skill_instructor, players.skill_pet, fortress.hok AS hok, players.donatesilver, players.donatemush, players.name, players.lvl, players.poll, players.guild_rank, players.gportal_time, players.guild_fight, players.potion_type1, players.potion_type2, players.potion_type3, players.potion_dur1, players.potion_dur2, players.potion_dur3 
			FROM players LEFT JOIN fortress ON fortress.owner = players.ID WHERE players.guild = $guildID ORDER BY players.guild_rank ASC, players.lvl DESC");
		$this->players = $players->fetchAll(PDO::FETCH_ASSOC);

		$invited = $GLOBALS['db']->query("SELECT players.ID, players.name, players.lvl FROM guildinvites LEFT JOIN players ON guildinvites.playerID = players.ID WHERE guildinvites.guildID = $guildID ORDER BY players.lvl DESC");
		$this->invites = $invited->fetchAll(PDO::FETCH_ASSOC);

		$gfights = $GLOBALS['db']->query("SELECT guildfights.guildAttacker, g1.name attacker, guildfights.guildDefender, g2.name as defender, guildfights.time FROM guildfights LEFT JOIN guilds AS g1 ON g1.ID = guildfights.guildAttacker LEFT JOIN guilds AS g2 ON g2.ID = guildfights.guildDefender WHERE guildfights.guildAttacker = $guildID OR guildfights.guildDefender = $guildID;");
		$this->fights = $gfights->fetchAll(PDO::FETCH_ASSOC);
		
		$slots = $GLOBALS['db']->query("SELECT * FROM guildslots WHERE guildID = $guildID ORDER BY ID DESC");
		$this->slots = $slots->fetchAll(PDO::FETCH_ASSOC);
		
		$skills = $GLOBALS['db']->query("SELECT SUM(skill_treasure) AS guild_skill_treasure, SUM(skill_instructor) as guild_skill_instructor, SUM(skill_pet) AS guild_skill_pet, SUM(players.lvl) AS total_lvl FROM players WHERE guild = $guildID");
		$this->skill = $skills->fetch(PDO::FETCH_ASSOC);
		
		if ($this->skill['guild_skill_treasure'] > 500) $this->skill['guild_skill_treasure'] = 500;
		if ($this->skill['guild_skill_instructor'] > 500) $this->skill['guild_skill_instructor'] = 500;
		if ($this->skill['guild_skill_pet'] > 500) $this->skill['guild_skill_pet'] = 500;
	}

	public function getUpgradeSkillPrice($level) {
		switch ($level) {
			case 0: return [300, 0];
			case 1: return [300, 0];
			case 2: return [500, 0];
			case 3: return [1000, 0];
			case 4: return [2500, 0];
			case 5: return [10000, 0];
			case 6: return [50000, 2];
			case 7: return [100000, 0];
			case 8: return [150000, 4];
			case 9: return [200000, 6];
			case 10: return [250000, 8];
			case 11: return [500000, 10];
			case 12: return [600000, 10];
			case 13: return [700000, 10];
			case 14: return [800000, 10];
			case 15: return [900000, 10];
			case 16: return [1000000, 10];
			default: return [1000000, 10]; 
		}
	}

	public function getUpgradeSkillPriceSave() {
		global $db, $playerID;
		
		$qry = $db->query("SELECT skill_treasure, skill_instructor, skill_pet FROM players WHERE ID = $playerID");
		$player = $qry->fetch(PDO::FETCH_ASSOC);
		
		$ret = [];
		
		foreach (['skill_treasure', 'skill_instructor', 'skill_pet'] as $skill) {
			$price = $this->getUpgradeSkillPrice($player[$skill]);
			$ret[] = $price[0];
			$ret[] = $price[1];
		}
		
		return join('/', $ret);
	}

	public function getGroupSave(){
		$ret = [];

		for($i = 0; $i <= 488; $i++)
			$ret[] = 0;

		$ret[0] = $this->data['ID'];
		$ret[1] = $this->data['silver'];
		$ret[2] = $this->data['mush'];

		$ret[3] = count($this->players) + count($this->invites) + count($this->slots);
		
		// Mushroom catapult
		$ret[4] = $this->data['catapult'];

		$portalHP = $this->data['portal'] >= 50 ? 0 : round($this->data['portal_hp'] / Monster::getGuildPortalMonster($this->data['portal'])->hp * 100);


		$ret[5] = 50; //$this->data['base'];
		$ret[6] = $this->skill['guild_skill_treasure'] + 65536 * $portalHP;
		$ret[7] = $this->skill['guild_skill_instructor'] + $this->data['portal'] * 65536;

		$ret[8] = $this->data['dungeon'];


		//attack declarer
		$ret[10] = $this->data['attack_init'];

		//leader last login
		$ret[12] = $this->players[0]['poll'];
		
		$ret[13] = $this->data['honor'];

		//eventtriggerbullcrap, window popup about last guild fight, triggers the act to sim fight
		$ret[368] = $this->data['event_trigger_count'];


		foreach($this->fights as $fight){
			if($fight['guildAttacker'] == $this->data['ID']){
				$i = 364;
				if($fight['guildDefender'] == 1000000)
					$ret[9] = $this->data['dungeon'] + 1;
			} else
				$i = 366;

			$ret[$i] = $fight['guildAttacker'];
			$ret[$i + 1] = $fight['time'];

			if($fight['time'] <= $GLOBALS["CURRTIME"])
				$ret[368]++;
		}


		// for($i = 0; $i < $ret[3]; $i++){
		$i = 0;
		foreach($this->players as $player){
			$ret[14 + $i] = $player['ID'];
			$ret[64 + $i] = $player['lvl'] + ($player['guild_fight'] * 1000);
			$ret[114 + $i] = $player['poll'];
			
			// Fix by Greg
			if ($this->data['portal'] >= 50) {
				$ret[164 + $i] = $GLOBALS["CURRTIME"];
			}else{
				$ret[164 + $i] = $player['gportal_time'];
			}
			//donates
			$ret[214 + $i] = $player['skill_treasure'];
			$ret[264 + $i] = $player['skill_instructor'];
			
			// guild rank
			$ret[314 + $i] = $player['guild_rank'];
			
			$i++;
		}


		//iterate through invites too
		foreach($this->invites as $invite){
			$ret[14 + $i] = $invite['ID'];
			$ret[64 + $i] = $invite['lvl'];

			$ret[314 + $i] = 4;

			$i++;
		}
		
		foreach($this->slots as $slot){
			$ret[14 + $i] = -1;
			$ret[64 + $i] = $slot['minLvl'];

			$ret[114 + $i] = $slot['country']; 
			$i++;
		}
		
		$ret[370] = Account::getAllHok($this->data['ID']); // All hall of knights level
		
		// $fn = "";
		// foreach($this->fights as $fight)
		// 	if($fight['guildAttacker'] == $this->data['ID'] && $fight['guildDefender'] != 1000000)
		// 		$fn .= "&owngroupattack.r:$fight[name]";
		// 	else
		// 		$fn .= "&owngroupdefend.r:$fight[name]";

		return join('/', $ret);
	}

	public function getOwnGroupAttack(){
		if(count($this->fights) == 0)
			return false;

		$ret = [];

		foreach($this->fights as $fight){
			if($fight['guildAttacker'] == $this->data['ID'] && $fight['guildDefender'] != 1000000)
				$ret[] = "owngroupattack.r:$fight[defender]";
			else if($fight['guildDefender'] == $this->data['ID'])
				$ret[] = "owngroupdefense.r:$fight[attacker]";
		}

		return join('&', $ret);
	}

	public function getOtherGroupAttack(){
		if(count($this->fights) == 0)
			return false;

		$ret = [];

		foreach($this->fights as $fight){
			if($fight['guildAttacker'] == $this->data['ID'] && $fight['guildDefender'] != 1000000)
				$ret[] = "othergroupattack.r:$fight[defender]";
			else if($fight['guildDefender'] == $this->data['ID'])
				$ret[] = "othergroupdefense.r:$fight[attacker]";
		}

		return join('&', $ret);
	}

	// public function getOwnGroupAttack(){
	// 	if(count($this->fights) == 0)
	// 		return false;
	// 	$ret = "";

	// 	$att = $this->fights[0]['name'];
	// 	$def = "dupaki";

	// 	foreach($this->fights as $fight){
	// 		if($fight['guildAttacker'] == $this->data['ID'] && $fight['guildDefender'] != 1000000)
	// 			$ret .= "&owngroupattack.r:$fight[name]";
	// 		else if($fight['guildDefender'] == $this->data['ID'])
	// 			$ret .= "&owngroupdefense.r:$fight[name]";
	// 	}

	// 	if(strlen($ret) > 1)
	// 		return $ret;
	// 	return false;

	// }

	// public function getOtherGroupAttack(){
	// 	if(count($this->fights) == 0)
	// 		return false;
	// 	$ret = "";

	// 	$att = $this->fights[0]['name'];
	// 	$def = "dupaki";

	// 	foreach($this->fights as $fight){
	// 		if($fight['guildAttacker'] == $this->data['ID'] && $fight['guildDefender'] != 1000000)
	// 			$ret .= "&othergroupattack.r:$fight[name]";
	// 		else if($fight['guildDefender'] == $this->data['ID'])
	// 			$ret .= "&othergroupdefense.r:$fight[name]";
	// 	}

	// 	if(strlen($ret) > 1)
	// 		return $ret;
	// 	return false;
	// }

	public function getPotionData(){
		$ret = [];

		$time = $GLOBALS["CURRTIME"];
		//type,power,type,power....
		foreach($this->players as $player) {

			for($i = 1; $i <= 3; $i++) {
				if($player["potion_dur$i"] > $time) {
					$type = $player["potion_type$i"];
					
					$ret[] = $type;
					if ($type <= 5) {
						$ret[] = 10;
					} elseif ($type > 5 && $type <= 10) {
						$ret[] = 15;
					} else {
						$ret[] = 25;
					}
				} else {
					$ret[] = 0;
					$ret[] = 0;
				}
			}
		}

		return join(',', $ret).',';
	}
	
	public function getHokData() {
		$ret = [];
		
		foreach($this->players as $player) {
			$ret[] = $player['hok'];
		}
		
		return implode(',', $ret).',';
	}

	public function getMemberList(){
		$ret = '';

		foreach($this->players as $player)
			$ret .= $player['name'].',';
		foreach($this->invites as $invite)
			$ret .= $invite['name'].',';

		return $ret;
	}

	//make this function add monster for every player that has album
	//also add every monster before that, wether its portal or dungeon
	public function addAlbumMonster($monsterID){
		
	}

	public function hasFreePlace(){
		return count($this->players) < 50;
	}

	public function hasFreeInvitePlace(){
		return (count($this->players) + count($this->invites) + count($this->slots)) < 50;
	}


	//sets portal time display data of ones own character for the response
	public function guildPortalCD($pid){
		foreach($this->players as &$player){
			if($player['ID'] == $pid){
				$player['gportal_time'] = $GLOBALS["CURRTIME"];
				break;
			}
		}
	}

	public function declareFight($target, $declarer){
		
		$qry = $GLOBALS['db']->query("SELECT mush, silver FROM players WHERE ID = {$declarer}");
		$player = $qry->fetch(PDO::FETCH_ASSOC);
		
		foreach($this->fights as $fight){
			if($fight['guildAttacker'] == $this->data['ID'])
				exit();
		}
		
		if(is_numeric($target) == false)
			exit();

		//2 hour wait time
		$fightTime = $GLOBALS["CURRTIME"] + 150*2;
		//$fightTime = $GLOBALS["CURRTIME"] + 30;

		if($target != 1000000){
			$db = $GLOBALS['db'];
			
			//$targetGuild = $GLOBALS['db']->query("SELECT base FROM guilds WHERE ID = $target");
			
			$targetGuild = $db->prepare("SELECT base FROM guilds WHERE ID = :guild");
			$targetGuild->bindParam(':guild', $target);
			$targetGuild->execute();

			if($targetGuild->rowCount() == 0 || ($target == $this->data['ID']))
				exit('&Error:group not found');

			$targetGuild = $targetGuild->fetch(PDO::FETCH_ASSOC);
			
			$attack = $db->prepare("SELECT ID FROM guildfights WHERE guildDefender = :guildattacker");
			$attack->bindParam(':guildattacker', $target);
			$attack->execute();
			
			//$attack = $GLOBALS['db']->query("SELECT ID FROM guildfights WHERE guildDefender = $target");

			if($attack->rowCount() > 0)
				exit('&Error:');

			if(($cost = Guild::getAttackCost(50)) > $player['silver'])
				exit('&Error:need more gold');

		}else{
			if(($cost = Guild::getRaidCost($this->data['dungeon'])) > $player['silver'])
				exit('&Error:need more gold');
			//just in case
			if($this->data['dungeon'] >= 150)
				exit();
		}



		//set declarer attack state
		foreach($this->players as &$player){
			if($player['ID'] == $declarer){
				$player['guild_fight']++;
				break;
			}
		}

		$GLOBALS['db']->exec("UPDATE players SET guild_fight = guild_fight + 1 WHERE ID = $declarer");


		$this->fights[] = ['guildAttacker' => $this->data['ID'], 'guildDefender' => $target, 'time' => $fightTime];
		$this->data['attack_init'] = $declarer;
	//	$this->data['silver'] -= $cost;

		//$GLOBALS['db']->exec("INSERT INTO guildfights(guildAttacker, guildDefender, time) VALUES(".$this->data['ID'].", $target, $fightTime)");
		$fightlog = $GLOBALS['db']->prepare("INSERT INTO guildfights(guildAttacker, guildDefender, time) VALUES(:attacker, :defender, :time)");
		$fightlog->bindParam(':attacker', $this->data['ID']);
		$fightlog->bindParam(':defender', $target);
		$fightlog->bindParam(':time', $fightTime);
		$fightlog->execute();
		$GLOBALS['db']->exec("UPDATE guilds SET attack_init = $declarer WHERE ID = ".$this->data['ID']);
		$GLOBALS['db']->exec("UPDATE players SET silver = silver - $cost WHERE ID = ".$player['ID']);
	}

	public function getRank() {
		$sql = "SELECT Count(*) AS c FROM guilds WHERE honor > ".$this->data['honor'];
		$qry = $GLOBALS['db']->query($sql);
		return $qry->fetchAll()[0]['c'] + 1;
	}
	
	public static function getCreateGroupSave($guildID, $playerID, $playerLvl){

		$ret = [];

		for($i = 0; $i <= 488; $i++)
			$ret[] = 0;

		$ret[0] = $guildID;
		$ret[1] = 1000;
		$ret[3] = 1;
		$ret[5] = 10;
		$ret[6] = 6553600;
		$ret[12] = $GLOBALS["CURRTIME"];
		$ret[13] = 100;
		$ret[14] = $playerID;
		$ret[64] = $playerLvl;
		$ret[114] = $GLOBALS["CURRTIME"];
		$ret[214] = 1000;
		$ret[314] = 1;




		return join('/', $ret);
	}

	public static function getGuildBuildingCost($current_lvl) {
		$current_lvl++;
		$ret = ['mushroom' => max(0, ($current_lvl - 25) * 5), 'silver' => 0];

		switch ($current_lvl) {
			case 1:
				$ret['silver'] = 500;
				break;
			case 2:
				$ret['silver'] = 900;
				break;
			case 3:
				$ret['silver'] = 1500;
				break;
			case 4:
				$ret['silver'] = 2200;
				break;
			case 5:
				$ret['silver'] = 3200;
				break;
			case 6:
				$ret['silver'] = 4500;
				break;
			case 7:
				$ret['silver'] = 6000;
				break;
			case 8:
				$ret['silver'] = 7800;
				break;
			case 9:
				$ret['silver'] = 10100;
				break;
			case 10:
				$ret['silver'] = 12800;
				break;
			case 11:
				$ret['silver'] = 16000;
				break;
			case 12:
				$ret['silver'] = 19700;
				break;
			case 13:
				$ret['silver'] = 24000;
				break;
			case 14:
				$ret['silver'] = 29100;
				break;
			case 15:
				$ret['silver'] = 34800;
				break;
			case 16:
				$ret['silver'] = 41200;
				break;
			case 17:
				$ret['silver'] = 48700;
				break;
			case 18:
				$ret['silver'] = 57000;
				break;
			case 19:
				$ret['silver'] = 66400;
				break;
			case 20:
				$ret['silver'] = 77000;
				break;
			case 21:
				$ret['silver'] = 88800;
				break;
			case 22:
				$ret['silver'] = 101800;
				break;
			case 23:
				$ret['silver'] = 116400;
				break;
			case 24:
				$ret['silver'] = 132500;
				break;
			case 25:
				$ret['silver'] = 150200;
				break;
			case 26:
				$ret['silver'] = 169900;
				break;
			case 27:
				$ret['silver'] = 191400;
				break;
			case 28:
				$ret['silver'] = 214900;
				break;
			case 29:
				$ret['silver'] = 240800;
				break;
			case 30:
				$ret['silver'] = 269000;
				break;
			case 31:
				$ret['silver'] = 299600;
				break;
			case 32:
				$ret['silver'] = 333000;
				break;
			case 33:
				$ret['silver'] = 369200;
				break;
			case 34:
				$ret['silver'] = 408300;
				break;
			case 35:
				$ret['silver'] = 450900;
				break;
			case 36:
				$ret['silver'] = 496800;
				break;
			case 37:
				$ret['silver'] = 546100;
				break;
			case 38:
				$ret['silver'] = 599600;
				break;
			case 39:
				$ret['silver'] = 656900;
				break;
			case 40:
				$ret['silver'] = 718400;
				break;
			case 41:
				$ret['silver'] = 784700;
				break;
			case 42:
				$ret['silver'] = 855700;
				break;
			case 43:
				$ret['silver'] = 931500;
				break;
			case 44:
				$ret['silver'] = 1012900;
				break;
			case 45:
				$ret['silver'] = 1099700;
				break;
			case 46:
				$ret['silver'] = 1192200;
				break;
			case 47:
				$ret['silver'] = 1291200;
				break;
			case 48:
				$ret['silver'] = 1396500;
				break;
			case 49:
				$ret['silver'] = 1508200;
				break;
			case 50:
				$ret['silver'] = 1627700;
				break;
		}
		return $ret;
	}

	public static function getAttackCost($baseLvl){
		return $baseLvl * 1000;
	}
	public static function getRaidCost($currentLvl){
		return 1000000;
	}
	
	// Group Battle XP by Jack
	public function getGuildBattleExp($type, $lvl) {
		$xpret = 0;
		
		switch($type) {
			case 0: // Raid Battle
				$buff = 0;
		
				if($lvl > 50 && $lvl < 100) {
					$lvl -= 50;
					$buff = 1;
				
				}else if($lvl > 100) {
					$lvl -=100;
					$buff = 2;
				}
		
				$lvl--;
		
				// Get boss, new method
				$objects = Monster::getGuildRaid($lvl);
				$monster = end($objects); //boss
		
				if($buff == 1) {
					$monster->raidbuff(true);
				} else if($buff == 2) {
					$monster->raidbuff2(true);
				}
		
				$stats = $monster->getTotalStats();
				$xpret = $stats['wit'] * 100;
				$xpret = floor($xpret / 12);
				$xpret = floor($xpret * $GLOBALS['xpbonus']);
				
				$xpret += rand(3, 6) * rand(90000, 100000);
				
			break;
			case 1: // Group attack
			break;
		
			
		}

		return $xpret;
		
	}
}

?>