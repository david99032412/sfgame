<?php

//players own account
class Account extends Player {

	// public $fortress;

	//order by class
	public $copycats = [];

	public $backpack = [];
	public $fortressBackpack = [];
	public $shops = [];
	//display based on slot
	public $copycatEquip = [[], [], []];

	public $album;
	
	public $questItems = [[], [], []];
	
	public $witch = [];
	
	public $achievments;
	
	function __construct($playerData = null, $items = null, $copycats = false, $loadAlbum = false) {

		if($playerData === null){
			$qry = $GLOBALS['db']->query('SELECT players.*, fortress.*, guilds.portal AS guild_portal, guilds.instructor, guilds.treasure, guilds.dungeon as raid, guilds.name AS gname FROM players LEFT JOIN fortress ON players.ID = fortress.owner LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.ID = '.$GLOBALS['playerID'].' ORDER BY honor DESC');
			// $qry->bindParam(':ssid', $GLOBALS['ssid']);
			// $qry->execute();
			$playerData = $qry->fetch ( PDO::FETCH_ASSOC );
		}
		
		// Fix by Greg
		$sql = "SELECT ID FROM guilds WHERE ID = '{$playerData['guild']}'";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() == 0)
			$playerData['guild'] = 0;

		if($items === null){
			$items = $GLOBALS['db']->query("SELECT * FROM items WHERE owner = ".$playerData['ID']." ORDER BY slot ASC");
			$items = $items->fetchAll(PDO::FETCH_ASSOC);
		}

		if($copycats === true){
			$copycats = $GLOBALS['db']->query("SELECT * FROM copycats WHERE owner = ".$playerData['ID']." ORDER BY class ASC");
			$copycats = $copycats->fetchAll(PDO::FETCH_ASSOC);
		}

		parent::setPlayerData($playerData, $items);

		if($copycats)
			for($i = 0; $i < 3; $i++){
				$this->copycats[] = new Copycat($copycats[$i], $this->copycatEquip[$i]);
			}

		//fortress
		$lvls = [];
		for($i = 0; $i < 12; $i++)
			$lvls[] = $playerData['b'.$i] ?? 0;

		// $this->fortress = new Fortress($lvls);

		// $this->tower = new Tower();

		//album
		if($loadAlbum && $playerData['album'] >= 0)
			$this->album = new Album($playerData['album_data'], $playerData['album']);
		
		// Quest items
		$sql = "SELECT * FROM items WHERE slot > 1000 AND slot < 1004 AND owner = '{$this->data['ID']}'";
		$qry = $GLOBALS['db']->query($sql);
		
		foreach($qry->fetchAll() as $row) {
			$slot = $row['slot'] - 1000;
			
			// Because if someone hacks the database
			switch($slot) {
				case 1 : $this->questItems[0] = $row; break;
				case 2 : $this->questItems[1] = $row; break;
				case 3 : $this->questItems[2] = $row; break;
			}
		}
		
		// Witch
		$sql = "SELECT * FROM witch";
		$wd = $GLOBALS['db']->query($sql)->fetchAll(); // Witch data
		
		if(count($wd) == 0) {
			Account::createWitch();
			
			$wd = $GLOBALS['db']->query($sql)->fetchAll();
		}
		
		$wd = $wd[0];
		
		if(($now = Misc::getNow()) != $wd['eventtime']) {
			$wd['eventtime'] = $now;
			$wd['event'] += $wd['event'] == 10 ? -9 : 1;
			$wd['event'] += $wd['event'] == 2 ? 1 : 0;
			$sql = "UPDATE witch SET event = '{$wd['event']}',  eventtime = '{$wd['eventtime']}'";
			$GLOBALS['db']->exec($sql);
		}
		
		if($wd['fill'] >= $wd['max'])
			$wd['max'] = -1;
		
		$this->witch = $wd;
		
		$this->achievments = new Achievments($playerData["achiData"]);
	}

	public function unlockFeature() {
		global $db;
		
		$u_f = [];
		
		$qry = $db->query('SELECT unlockmirror, mirror, pets, petsDung, petsunlock, petnest FROM players WHERE ID = '.$this->data['ID']);
		$player = $qry->fetch(PDO::FETCH_ASSOC);
		
		if(($player['unlockmirror'] > 0) && ($player['mirror'] < 13)) {
			for($i = 0; $i < $player['unlockmirror']; $i++) {
				$unlock_mirror = $player['mirror'] + ($i + 1);
				if($unlock_mirror > 13) break;
				$u_f[] = "1/$unlock_mirror";
			}
		}
		
		if((empty($player['pets'])) && ($player['petnest'] == 1)) {
			$u_f[] = "20/1";
		}
		
		$pet_data = explode('/', $player['pets']);
		if(empty($pet_data)) $pet_data = array_fill(0, 100, 0);
		
		$pets_dung = explode('/', $player['petsDung']);
		if(empty($pets_dung)) $pets_dung = array_fill(0, 5, 0);
		
		$pets_unlock = json_decode($player['petsunlock'], true);
		
		for($i = 1; $i <= 5; $i++) {
			for($j = 1; $j <= 20; $j++) {
				$pet_index = (($i - 1) * 20) + $j;
				if($pets_unlock[$pet_index - 1] == 0) continue;
				if($pet_data[$pet_index - 1] > 0) continue;
				if(($j > 3) && ($pets_dung[$i - 1] < $j)) break;
				$u_f[] = "21/$pet_index";
			}
		}
		
		return (count($u_f) > 0) ? join('/', $u_f) : '';
	}

	public function getPlayerSave() {
		$ret = array_fill(0, 758, 0);
		
		$calT = Misc::getNow() == ((int) $this->data['calenderNext'] ?? 0) ? strtotime('tomorrow') : time();
		$ret[648] = 65536 * ((int) $this->data['calenderDay'] ?? 0);
		$ret[649] = $calT;

		//player ID
		$ret[0] = 1545570852;
		$ret[1] = $this->data['ID'];
		$ret[2] = $GLOBALS["CURRTIME"];
		$ret[3] = 1311714336;
		$ret[4] = 1126959955;
		
		//message count
		$ret[5] = 0;


		//exp
		$ret[8] = $this->data['exp'];
		$ret[9] = Player::getExp($this->data['lvl'] - 1);
		
		//Hourglass count
		//$ret[542] = 3;
		
		//exptype by Greg
		if($this->data['expType'] == '1')
			$ret[8] *= 1000000000;
		
		// $SF_STATUS = 45; // 2 quest /1work /0none
		$ret[45] = $this->data['status'];
		
		$ret[46] = $this->data['status_extra'];
		$ret[47] = $this->data['status_time'];

		//guild bonus
		$ret[461] = self::getGuildBonusNew($this->data['guild'], 'guild_skill_instructor');
		$ret[462] = self::getGuildBonusNew($this->data['guild'], 'guild_skill_treasure');
		
		$ret[521] = $this->data["noinv"];

		$expBonus = 1 + $ret[461] / 100;
		$goldBonus = 1 + $ret[462] / 100;

		// $SF_QUEST_DURATION_1 = 241;
		$ret[241] = $this->data['quest_dur1'];
		$ret[242] = $this->data['quest_dur2'];
		$ret[243] = $this->data['quest_dur3'];

		
		$ret[229] = 1; # id 1
		$ret[230] = 1; # id 2
		$ret[231] = 1; # id 3

		$ret[232] = 1; # type 1
		$ret[233] = 1; # type 2
		$ret[234] = 1; # type 3

		$ret[235] = 1; # mob 1
		$ret[236] = 1; # mob 2
		$ret[237] = 1; # mob 3
		
		// Set max honor by Greg
		if($this->data['honor'] > $this->data['maxhonor']) {
			$this->data['maxhonor'] = $this->data['honor'];
			$sql = "UPDATE players SET maxhonor = '{$this->data['maxhonor']}' WHERE ID = '{$this->data['ID']}'";
			$GLOBALS['db']->exec($sql);
		}

		//quest background - by Greg
		$ret[238] = $this->questBackground($this->data['quest_exp1']);
		$ret[239] = $this->questBackground($this->data['quest_exp2']);
		$ret[240] = $this->questBackground($this->data['quest_exp3']);

		// $SF_QUEST_EXP_1 = 280;
		$alb = $this->getAlbumMultiplier();
		$ret[280] = round($this->data['quest_exp1'] * $expBonus * $alb);
		$ret[281] = round($this->data['quest_exp2'] * $expBonus * $alb);
		$ret[282] = round($this->data['quest_exp3'] * $expBonus * $alb);

		// $SF_QUEST_GOLD_1 = 283;
		$twr = 1 + ($this->data['tower'] / 100);
		$ret[283] = round($this->data['quest_silver1'] * $goldBonus * $twr);
		$ret[284] = round($this->data['quest_silver2'] * $goldBonus * $twr);
		$ret[285] = round($this->data['quest_silver3'] * $goldBonus * $twr);

		// $SF_QUEST_REWARD_1_TYPE = 244;
		// $SF_QUEST_REWARD_2_TYPE = 256;
		// $SF_QUEST_REWARD_3_TYPE = 268;
		
		// Quest rewards
		for($i = 0; $i < 3; $i++) {
			if(count($this->questItems[$i]) == 0) {
				continue;
			}
			
			$item = $this->questItems[$i];
			
			$base = 244 + ($i * 12);
			
			$ret[$base] = $item['type'];
			$ret[$base + 1] = $item['item_id'];
			$ret[$base + 2] = $item['dmg_min'];
			$ret[$base + 3] = $item['dmg_max'];
			$ret[$base + 4] = $item['a1'];
			$ret[$base + 5] = $item['a2'];
			$ret[$base + 6] = $item['a3'];
			$ret[$base + 7] = $item['a4'];
			$ret[$base + 8] = $item['a5'];
			$ret[$base + 9] = $item['a6'];
			$ret[$base + 10] = $item['value_silver'];
			$ret[$base + 11] = $item['value_mush'];
		}
		
		$ret[543] = $this->data['quest_start']; // Quest timer by Jack (blue bar at travel)

		$face = explode(",", $this->data['face']);	
		for($i = 0; $i < 9; $i++)
			$ret[17 + $i] = $face[$i];
		
		$ret[26] = $this->data['portrait'] * -1;

		$ret[27] = $this->data['race'] + 50 * 65536;
		$ret[28] = $this->data['gender'];
		$ret[28] += $this->data['mirror'] == 13 ? 256 : self::getMirror($this->data['mirror']);

		$ret[29] = $this->data['class'] + (($this->data['portal_time'] - 1) * 65536);

		$ret[7] = $this->data['lvl'];

		//portal hp
		if($this->data['portal'] < 50){
			$portalMaxHp = Monster::getPortalMonster($this->data['portal'] + 1)->hp;

			//class rank?? + portal hp percent
			$ret[12] = 0 + round($this->data['portal_hp'] / $portalMaxHp * 100)  * 65536;
		}

		//silver
		$ret[13] = $this->data['silver'];

		//mush
		$ret[14] = $this->data['mush'];

		//shrooms may donate
		$ret[437] = $this->data['mush'];

		//honor
		$ret[10] = $this->data['honor'];
		
		// Armor by Greg
		$armor = 0;
		foreach($this->equip as $item)
		{
			if ($item->type <= 2 || $item->type >= 8)
				continue;
			
			$armor += $item->dmg_min;
		}
		
		$ret[447] = $armor;

		//rank - Greg
		$rank = $GLOBALS['db']->query("SELECT Count(*) as `rank` FROM players WHERE honor > {$this->data['honor']} OR (honor = {$this->data['honor']} AND ID > {$this->data["ID"]})");
		$rank = $rank->fetch(PDO::FETCH_ASSOC)['rank'] + 1;
		$ret[11] = $rank;
		
		//album + 10 000
		if($this->hasAlbum())
			$ret[438] = 10000 + $this->data['album'];


		$stats = $this->baseStats;
		$equipStats = $this->getEquipStats();

		$ret[30] = $stats['str'];
		$ret[31] = $stats['dex'];
		$ret[32] = $stats['int'];
		$ret[33] = $stats['wit'];
		$ret[34] = $stats['luck'];
		$ret[35] = $equipStats['str'];
		$ret[36] = $equipStats['dex'];
		$ret[37] = $equipStats['int'];
		$ret[38] = $equipStats['wit'];
		$ret[39] = $equipStats['luck'];
		$ret[40] = $stats['str'] - 10;
		$ret[41] = $stats['dex'] - 10;
		$ret[42] = $stats['int'] - 10;
		$ret[43] = $stats['wit'] - 10;
		$ret[44] = $stats['luck'] - 10;

		//backpack 168
		foreach($this->backpack as $item){
			$save = $item->getSave();
			$slot = ($item->slot * 12) + 168;
			for($i = 0; $i < 12; $i++){
				$ret[$slot + $i] = $save[$i];
			}
		}

		//equip 48
		foreach($this->equip as $item){
			$save = $item->getSave();
			$slot = ($item->slot * 12) + 48;
			for($i = 0; $i < 12; $i++){
				$ret[$slot + $i] = $save[$i];
			}
		}

		//dmg min max, clients counts shit
		if($weapon = $this->getWeapon()){
			$ret[448] = $weapon->raw['dmg_min'];
			$ret[449] = $weapon->raw['dmg_max'];
		}else{
			$ret[448] = 1;
			$ret[449] = 2;
		}

		//shops
		foreach($this->shops as $item){
			$save = $item->getSave();
			$slot = ($item->slot * 12) + 288;
			//360 is what?? skip
			if($slot >= 360)
				$slot++;
			for($i = 0; $i < 12; $i++){
				$ret[$slot + $i] = $save[$i];
			}
		}

		//dungeon end time
		$ret[459] = $this->data['dungeon_time'];

		//ARENA END TIME 460
		$ret[460] = $this->data['arena_time'];


		//thirst
		$ret[456] = $this->data['thirst'];

		$ret[457] = $this->data['beers'];

		//potions
		for($i = 1; $i <= 3; $i++){
			if($this->data['potion_dur'.$i] > $GLOBALS["CURRTIME"]){
				$ret[492 + $i] = $this->data['potion_type'.$i];
				$ret[495 + $i] = $this->data['potion_dur'.$i];
				
				// Potions fix by Jack
				if($this->data['potion_type'.$i] <= 5){
					$ret[498 + $i] = 10;
				}else if ($this->data['potion_type'.$i] > 5 && $this->data['potion_type'.$i] <= 10){
					$ret[498 + $i] = 15;
				}else if ($this->data['potion_type'.$i] > 10 && $this->data['potion_type'.$i] <= 15){
					$ret[498 + $i] = 25;
				}
				
				if($this->data['potion_type'.$i] < 16){
					$stat = ($ret[492 + $i] - 1) % 5;
					//$ret[35 + $stat] += ceil(($ret[30 + $stat] + $ret[35 + $stat]) * 0.25) + 1;
					$ret[35 + $stat] += ceil(($ret[30 + $stat] + $ret[35 + $stat]) * $ret[498 + $i] / 100) + 1;
				}
			}else{
				$sql = "UPDATE `players` SET `" . 'potion_type' . $i . "`='0' WHERE ID='{$this->data['ID']}'";
				$GLOBALS['db']->exec($sql);
			}
		}

		$ret[287] = $GLOBALS["CURRTIME"] - (3600 * 24 * 3);

		//mount
		if($this->data['mount_time'] > $GLOBALS["CURRTIME"] && $this->data['mount'] > 0){
			$ret[286] = $this->data['mount'];
			$ret[451] = $this->data['mount_time'];

			$mountMultiplier = [0.9, 0.8, 0.7, 0.5][$this->data['mount'] - 1];

			//adjust quest duration
			$ret[241] = ceil($this->data['quest_dur1'] * $mountMultiplier);
			$ret[242] = ceil($this->data['quest_dur2'] * $mountMultiplier);
			$ret[243] = ceil($this->data['quest_dur3'] * $mountMultiplier);
		}

		for($i = 0; $i < 12; $i++)
			$ret[524 + $i] = $this->data['b'.$i];

		#$ret[493] = 9;
		//fortress backpack size
		$ret[516] = $this->data['b9'];

		//hourglass by Jack
		$ret[542] = $this->data['hourglass'];
		
		//required lvl for upgrades fuck knows why
		for($i = 1; $i <= 3; $i++)
			$ret[587 + $i] = $this->data["ul$i"] - 1;

		//stone & wood
		$ret[544] = $this->data['wood'];
		$ret[545] = $this->data['stone'];

		//army -> warrior, scout, mage
		$ret[547] = $this->data['u1'] + $this->data['u2'] * 65536;
		$ret[548] = $this->data['u3'] + 65536 * $this->data['ut1'];
		$ret[549] = $this->data['ut2'] + 65536 * $this->data['ut3'];

		for($i = 1; $i <= 3; $i++){
			if($this->data["ut$i"] > 0){
				$ret[549 + $i] = $this->data["uttime$i"];
				$ret[552 + $i] = $this->data["uttime$i"] + 600;
			}
		}

		// // ----fortressunitsbuildstarttime(3)
		// $ret[550] = $this->data['uttime1'];
		// $ret[551] = $this->data['uttime2'];
		// $ret[552] = $this->data['uttime3'];
		// // ----fortressunitsbuildfinishtime(3)
		// $ret[553] = $this->data['uttime1'];
		// $ret[554] = $this->data['uttime2'];
		// $ret[555] = $this->data['uttime3'];

		//UNITS BUILD = floor(time elapsed / build time), then modulo to get current progress
		
		//max resources in buildings
		$ret[565] = Fortress::getMaxResources(1, $this->data['b2']);
		$ret[566] = Fortress::getMaxResources(2, $this->data['b3']);
		$ret[567] = Fortress::getMaxResources(3, $this->data['b5'], $this->data['lvl']);
		
		//max resources globaly
		$ret[568] = Fortress::getGlobalMaxResources(1, $this->data['b0']);
		$ret[569] = Fortress::getGlobalMaxResources(2, $this->data['b0']);

		//budowanie, building id, endtime, starttime
		$ret[571] = $this->data['build_id'];
		$ret[572] = $this->data['build_end'];
		$ret[573] = $this->data['build_start'];

		//resources per hour
		$ret[574] = Fortress::getResourcesPerHour(1, $this->data['b2']);
		$ret[575] = Fortress::getResourcesPerHour(2, $this->data['b3']);
		$ret[576] = Fortress::getResourcesPerHour(3, $this->data['b5']);

		//last gather time
		$ret[577] = max($this->data['gather1'], $this->data['gather2'], $this->data['gather3']);

		//current resources in buildings -> wood, stone, exp, works together with 577
		//count resources gathered since ret[577]
		$ret[562] = floor(($ret[577] - $this->data['gather1']) * (Fortress::getResourcesPerHour(1, $this->data['b2']) / 3600));
		$ret[563] = floor(($ret[577] - $this->data['gather2']) * (Fortress::getResourcesPerHour(2, $this->data['b3']) / 3600));
		$ret[564] = floor(($ret[577] - $this->data['gather3']) * (Fortress::getResourcesPerHour(3, $this->data['b5']) / 3600));

		// $ret[562] = floor(( $this->data['gather1'] - $ret[577] ) * (Fortress::getResourcesPerHour(1, $this->data['b2']) / 3600));
		// $ret[563] = floor(( $this->data['gather2'] - $ret[577] ) * (Fortress::getResourcesPerHour(2, $this->data['b3']) / 3600));
		// $ret[564] = floor(( $this->data['gather3'] - $ret[577] ) * (Fortress::getResourcesPerHour(3, $this->data['b5']) / 3600));


		// Wheel of fortune by Greg
		$wof = self::getWheelData($this->data['ID']);
		$ret[579] = $wof[0];
		$ret[580] = $wof[1];
		
		// Wheel type
		$ret[476] = $this->data['wheel'];
		
		// Golden frame
		$ret[444] = 32 * $this->data['gframe'];


		//kopanie klejnotow, 13, endtime, starttime
		if($this->data['dig_start'] > 0){
			$ret[594] = 13;
			$ret[595] = $this->data['dig_end'];
			$ret[596] = $this->data['dig_start'];
		}

		$ret[578] = $this->data['luckycoin']; // Wheel coin by Jack

		//email valid
		$ret[463] = 1;

		//dafuq is this
		// $ret[464] = 1;

		//witch cost
		$ret[519] = $this->fortressRerollPrice();

		//portal lvls and bonuses | (portal * 256 + guild_portal) * 65536
		// $portal = is_numeric($this->data['guild_portal']) ? $this->data['guild_portal'] : 0;
		$ret[445] = (((int)$this->data['portal']) * 256 + (int)$this->data['guild_portal']) * 65536;


		//guild and rank
		$ret[435] = $this->data['guild'];
		if($this->data['guild'] > 0)
			$ret[436] = $this->data['guild_rank'];


		//questrerolltime
		// $ret[228] = 1435802478;

		
		//new messags temp 
		$ret[434] = isset($this->data['new_msg']) ? $this->data['new_msg'] : 0;

		//guild attacks
		$ret[508] = $this->data['guild_fight'];
		$ret[509] = $this->data['event_trigger_count'];


		//tutorial skip
		//$ret[597] = 16777215;
		$ret[597] = $this->data['tutorial'];
		
		if($this->data['tutorial'] == -1 || !$GLOBALS['tutorial']) { // No wasting space - Jack 
			$ret[597] = 16777215;
		}	
			
		
		
		//hall of knights
		$ret[598] = $this->data['hok'];
		
		//arena enemies
		$ret[599] = $this->data['arena_nme1'];
		$ret[600] = $this->data['arena_nme2'];
		$ret[601] = $this->data['arena_nme3'];
		//REROLL TIME FOR ARENA, client calls for reroll
		$ret[602] = 1956232814;


		//WC
		$ret[491] = $this->data['wcaura'];
		$ret[492] = $this->data['wcexp'];
		$ret[515] = Account::getWcRequiredExp($this->data['wcaura']);
		
		$ret[623] = $this->data['skill_treasure'];
		$ret[624] = $this->data['skill_instructor'];	
		
		//fortress enemy time
		$ret[586] = strtotime("tomorrow");

		//fortress enemy id
		$ret[587] = $this->data['enemyid'];

		$ret[588] = 0;
		
		return join("/", $ret);
	}
	
	public function getTowerSave($underworld = null) {
		$ret = $this->data['ID']."/0/".$this->data['tower']."/";

		foreach($this->copycats as $copycat){
			$ret .= $copycat->getSave()."/";
		}
		
		if($underworld === null){
			$underworld = new Underworld($this->data["ID"]);
		}
		
		$ret .= $underworld->getSave();
		
		$ret = explode("/", $ret);

		if($underworld->haveIt) {
			
			$unit = $underworld->getUnitSave();
			
			$ret[146] = $unit[0];
			$ret[149] = $unit[1];
			$ret[148] = $unit[2];
			$ret[294] = $unit[3];
			$ret[297] = $unit[4];
			$ret[296] = $unit[5];
			$ret[442] = $unit[6];
			$ret[445] = $unit[7];
			$ret[444] = $unit[8];
		
		}

		$ret = implode("/", $ret);		

		return $ret;
	}
	
	public static function getAllHok($gid) {
		// Getting all hall of knights level by Greg
		$qry = $GLOBALS['db']->prepare("SELECT players.ID, fortress.hok FROM players LEFT JOIN fortress ON players.ID = fortress.owner WHERE players.guild = '$gid'");
		$qry->execute();
		
		$all = 0;
		while($row = $qry->fetch(PDO::FETCH_ASSOC)) {
			$all += $row['hok'];
		}
		return $all;
	}

	public function getFortressPriceSave(){
		$ret = [];

		//upgrade prices, dig for gems price last
		//time/gold/wood/stone
		for($i = 0; $i < 12; $i++)
			foreach(Fortress::getUpgradePrice($i, $this->data['b'.$i], $this->data['b1']) as $s)
				$ret[] = $s;

		// for($i = 0; $i < 12; $i++){
		// 	$price = Fortress::getUpgradePriceNew($i, $this->data['b'.$i]);
		// 	$ret[] = round($price[0] * (1.0 - $this->data['b1'] * 0.05));
		// 	$ret[] = $price[1];
		// 	$ret[] = $price[2];
		// 	$ret[] = $price[3];
		// }

		$gemSearchPrice = Fortress::getSearchGemPrice($this->data['b4']);
		
		$shorter = 1 - ($this->data['b1'] * 5 / 100);
		$gemSearchPrice[0] *= $shorter;
		
		foreach ($gemSearchPrice as $x) {
			$ret[] = $x;
		}

		return join("/", $ret);
	}

	public function getTrainUnitsPrice(){
		//time/silver/wood/stone
		$ret = [];

		for($i = 1; $i <= 3; $i++)
			foreach(Fortress::getUnitTrainPrice($i, $this->data["ul$i"]) as $a)
				$ret[] = $a;

		return join('/', $ret);
		// return "600/0/15/5/900/0/11/6/600/0/19/4/";
	}

	public function getUpgradeUnitsPrice(){
		//next lvl, wood, stone
		$ret = [];

		for($i = 1; $i <= 3; $i++){
			$ret[] = Fortress::getUnitLvl($this->data["ul$i"] + 1);
			$price = Fortress::getUnitUpgradePrice($i, $this->data["ul$i"]);
			$ret[] = $price[0];
			$ret[] = $price[1];
		}
		
		return join('/', $ret);
	}

	public function getUnitLvls(){
		$ret = [];

		$ret[] = Fortress::getUnitLvl($this->data['b11']);

		for($i = 1; $i <= 3; $i++)
			$ret[] = Fortress::getUnitLvl($this->data["ul$i"]);

		return join('/', $ret);
	}
	
	public function getHallOfKnightsPriceSave(){
		return join('/', Fortress::getHallOfKnightsPrice($this->data['hok']));
	}

	public function rerollShop($shop){

		if($this->data['mush'] < 1)
			exit("&Error:need more coins");

		$GLOBALS['db']->exec("UPDATE players SET mush = mush - 1 WHERE ID = ".$this->data['ID']);
		$this->data['mush'] --;

		$qry = [];
		
		$is_album = false;
		
		$shop1 = ($shop - 1) * 6;
		for($i = $shop1; $i < $shop1 + 6; $i++){
			//Weapon shop
			if($shop == "1") {
				
				$type = rand(1, 7);
			//Magic shop	
			} else {
				$type = rand(8, 11);
			
				//hourglass
				if(rand(1, 20) == 10) {
					$type = 17;
				}
				
				//album
				if (!$this->albumInInv() && !$is_album && $this->data['album'] == -1) {
					if(rand(1, 20) == 20) {
						$type = 13; 
						$is_album = true;
					}	
				}
				
				
			}

			//potion
			if($type == 11)
				$type = 12;

			$item = Item::genItem($type, $this->lvl, $this->class, 10);
			$slot = 20 + $i;

			$qry[] = "UPDATE items SET type = ".$item['type'].", item_id = ".$item['item_id'].", dmg_min = ".$item['dmg_min'].", dmg_max = ".$item['dmg_max'].", a1 = ".$item['a1'].", a2 = ".$item['a2'].", a3 = ".$item['a3'].", a4 = ".$item['a4'].", a5 = ".$item['a5'].", a6 = ".$item['a6'].", value_silver = ".$item['value_silver'].", value_mush = ".$item['value_mush']." WHERE owner = ".$this->data['ID']." AND slot = $slot";

			//this is enought to display the items correctly
			$this->shops[$i]->raw = $item;
		}

		$GLOBALS['db']->exec(join(';', $qry));
	}

	//TODO: check fortress backpack size, if not exceeding
	public function moveItem($args){
		$sourceSlot = Item::getItemSlot($args[0], $args[1]);
		$sourceItem = $this->getItemAtSlot($args[0], $sourceSlot);
		
		if(!is_object($sourceItem)) {
			exit();
		}
		
		$class = $this->class;
		$slot = $args[2];
		$moveEquipped = false;
		
		//if there are any items in that slot already, type if target slot -1	
		$targetSlot = Item::getItemSlot($args[2], $args[3], $sourceItem->type);
		$targetItem = $this->getItemAtSlot($args[2], $targetSlot);

		//moving between equip, backpack and fortress backpack
		if(($args[0] == 2 || $args[0] == 5 || $args[0] == 1) && ($args[2] == 2 || $args[2] == 5 || $args[2] == 1)){
			//on equiping/using items 
			if($args[2] == 1 || $args[0] == 1){
				
				//BM moveitem
				if($this->class == 5) $class = (($targetItem && $targetItem->type == 1) || (!$targetItem && $sourceItem->type == 1)) ? 1 : 2;	
				if($this->class == 6) $class = 1;
				if($this->class == 7) $class = (($targetItem && $targetItem->type == 1) || (!$targetItem && $sourceItem->type == 1)) ? 3 : 1;	
				if($this->class == 8) $class = (($targetItem && $targetItem->type == 1) || (!$targetItem && $sourceItem->type == 1)) ? 2 : 3;	
				if($this->class == 9) $class = (($targetItem && $targetItem->type == 1) || (!$targetItem && $sourceItem->type == 1)) ? 2 : 3;	
				
				//Assassins
				if($class == 4) {
					if(($targetItem && $targetItem->type == 1) || (!$targetItem && $sourceItem->type == 1))
						$class = 1;
					else
						$class = 3;	
					
					// Equip 2nd weapon
					if($args[3] == 10 && ($args[0] == 2 || $args[0] == 5) && $sourceItem->type == 1) {
						$sourceItem->type = 2;
						$targetSlot += 1;
						$targetItem = $this->getItemAtSlot($args[2], $targetSlot);
						if(isset($targetItem->type))
							$targetItem->type = 2;
					}
					
					// Assassins
					// Move equipped weapons; Switch swords
					if($args[0] == 1 && $args[2] == 1) {
						$moveEquipped = true;
						
						if($args[1] == 9 && $args[3] == 10) {
							$sourceItem->type = 1;
							$targetSlot += 1;
							$targetItem = $this->getItemAtSlot($args[2], $targetSlot);
							if(isset($targetItem->type))
								$targetItem->type = 1;
								
							$slot = 2;
						}else if($args[1] == 10 && $args[3] == 9) {
							//$sourceItem->type = 2;
							$targetItem = $this->getItemAtSlot($args[2], $targetSlot);
							if(isset($targetItem->type))
								$targetItem->type = 2;
						}else{
							exit('Success:');
							
						}	
						
					}	
				}	
				
				//check if equiping items that are not equipable
				if($targetItem){
					if($sourceItem->type <= 10 && $targetItem->type > 10)
						exit("&Error:class cannot use this item");
					/*
					if($sourceItem->type <= 10 && $sourceItem->type != $targetItem->type)
						exit("&Error:item does not belong here");
					*/
					if($sourceItem->type <= 10 && $sourceItem->type != $targetItem->type && $moveEquipped == false)
						exit("&Error:item does not belong here");
					if($targetItem->type <= 7 && $targetItem->forClass != $class)
						exit("&Error:class cannot use this item");
				}

				//check if item for your class
				if($sourceItem->type <= 7){
					if($sourceItem->forClass != $class)
						exit("&Error:class cannot use this item");
				}else if($sourceItem->type > 10){ 
					$this->useItem($args, $sourceItem, $targetItem);
					return;
				}
			}

			//see if not moving to an additional slot that player does not have yet
			if($args[2] == 5 && $args[3] > $this->getFortressBackpackSize())
				exit('&Error:target item not found');

			$sourceItem->move($slot, $args[3], $targetSlot);
			//$sourceItem->move($args[2], $args[3], $targetSlot);
			//remove item from array, add to target array
			$this->removeItem($args[0], $sourceItem);
			$this->addItem($args[2], $sourceItem);
			//$this->addItem($slot, $sourceItem);

			if($targetItem){
				//$GLOBALS['db']->exec("UPDATE items SET slot = ".$sourceSlot." WHERE ID = ".$targetItem->getID());
				$targetItem->move($args[0], $args[1], $sourceSlot);
				$this->removeItem($slot, $targetItem);
				//$this->removeItem($args[2], $targetItem);
				$this->addItem($args[0], $targetItem);
			}
			
			$this->equipHourglass($sourceItem, $args);
			
		}else if($args[0] == 3 || $args[0] == 4){ 
			//buying items, if source = shops

			//check if not putting item in non active fortress backpack slot
			if($args[2] == 5 && $args[3] > $this->getFortressBackpackSize())
				exit('&Error:target item not found');

			//check price
			if($sourceItem->cost > $this->data['silver'])
				exit("&Error:need more gold");
			if($sourceItem->costMush > $this->data['mush'])
				exit("&Error:need more coins");
			
			//Assassins, equip 2nd weapon at shop
			if($this->class == 4) {
				if($args[3] == 10 && $args[0] == 3 && $sourceItem->type == 1) {
					$sourceItem->type = 2;
					$targetSlot += 1;
					$targetItem = $this->getItemAtSlot($args[2], $targetSlot);
					if(isset($targetItem->type))
						$targetItem->type = 2;
				}	
			}	

			if($sourceItem->type > 10 && $args[2] == 1)
				$this->useItem($args, $sourceItem, $targetItem);
			else{
				if($targetItem){
					if(($freeslot = $this->getFreeBackpackSlot()) === false)
						exit('&Error:no backspace free');
					// exit("&Error:$freeslot");
					$this->removeItem($args[2], $targetItem);
					if($freeslot >= 100){
						$targetItem->move(5, $freeslot - 99, $freeslot);
						$this->addItem(5, $targetItem);
					}else{
						$targetItem->move(2, $freeslot + 1, $freeslot);
						$this->addItem(2, $targetItem);
					}
				}

				//item->buy, updates the item
				$sourceItem->buy($args[2], $args[3], $targetSlot);
				$this->removeItem($args[0], $sourceItem);
				$this->addItem($args[2], $sourceItem);
			}
			
			$this->equipHourglass($sourceItem, $args);
			
				//generate new item, insert into db
			if($args[0] == "3"){
				$type = rand(1, 7);
			}else{
				$type = rand(8, 11);
				//potion
				if($type == 11)
					$type = 12;
			}


			$newItem = Item::genItem($type, $this->lvl, $this->class);
			$newItem['slot'] = 20 + (($args[0] - 3) * 6) + $args[1] - 1;
			$newItemObj = new Item($newItem);
			$this->addItem($args[0], $newItemObj);


			$GLOBALS['db']->exec("INSERT INTO items(owner, slot, type, item_id, dmg_min, dmg_max, a1, a2, a3, a4, a5, a6, value_silver, value_mush) 
				VALUES(".$this->data['ID'].", $newItem[slot], $newItem[type], $newItem[item_id], $newItem[dmg_min], $newItem[dmg_max], 
					$newItem[a1], $newItem[a2], $newItem[a3], $newItem[a4], $newItem[a5], $newItem[a6], $newItem[value_silver], $newItem[value_mush])");

			//pay up bitch
			$GLOBALS['db']->exec("UPDATE players SET silver = silver - $sourceItem->cost, mush = mush - $sourceItem->costMush WHERE ID = ".$this->data['ID']);

			$this->data['silver'] -= $sourceItem->cost;
			$this->data['mush'] -= $sourceItem->costMush;

			//album add
			if($this->hasAlbum() && $sourceItem->type <= 10){
				$this->album = new Album($this->data['album_data'], $this->data['album']);
				if($this->album->addItem($sourceItem)){
					$this->album->encode();
					$GLOBALS['db']->exec("UPDATE players SET album_data = '".$this->album->data."', album = ".$this->album->count." WHERE ID = ".$this->data['ID']);
					$GLOBALS['ret'][] = "scrapbook.r:".$this->album->data;
					$this->data['album'] = $this->album->count;
				}
			}
		}else if($args[2] == 3 || $args[2] == 4){
			//selling items

			// $valS = $sourceItem->cost;


			//DELETE item from db
			$GLOBALS['db']->exec("DELETE FROM items WHERE ID = ".$sourceItem->raw['ID']);

			//give value to player
			$GLOBALS['db']->exec("UPDATE players SET silver = silver + ".$sourceItem->cost.", mush = mush + ".$sourceItem->costMush." WHERE ID = ".$this->data['ID']);

			//for display data
			$this->data['silver'] +=  $sourceItem->cost;
			$this->data['mush'] += $sourceItem->costMush;

			$this->removeItem($args[0], $sourceItem);
		}else if(($args[2] >= 200 && $args[2] <= 204) || $args[2] == 212 || $args[2] == 213){
			// Blacksmith by Greg
			
			$bs = (new blacksmith($sourceItem))->getData();
			
			$spec = false;
			
			if (floor($args[2] / 210) == 1)
			{
				$spec = true;
				$args[2] -= 10;
			}
			
			$what = [
				204 => "upgradeitem",
				201 => "dismantle",
				203 => "removestone",
				202 => "addsocket"
			];
			
			//$act = $what[$args[2]] ?? "";
			$act = ($what[$args[2]] != null) ? $what[$args[2]] : "";
			
			if ($act == "")
				exit();
			
			$prize = $bs[$act];
			
			if ($this->data["blacksmith"] == 0)
				$this->data["blacksmith"] = "0/0/5/0";
			
			//$ts = explode("/", $this->data["blacksmith"]); // temporary blacksmith
			$ts = array_map('intval', explode("/", $this->data["blacksmith"]));

			switch($act)
			{
				case "upgradeitem":
					$sourceItem->raw["value_mush"] += 256;
					
					$upr = $sourceItem->raw["value_mush"] % 65536;
					$upr = floor($upr / 256);
					
					if ($upr == 10)
					{
						// Blacksmith achievment
					}
					
					$qryArgs = $sourceItem->upgradeItem();
					
					$ts[0] -= $prize[0];
					$ts[1] -= $prize[1];
					
					if ($ts[0] < 0 || $ts[1] < 0)
						exit();
					
					$GLOBALS["db"]->exec("UPDATE items SET value_mush = value_mush + 256, $qryArgs WHERE ID = " . $sourceItem->raw["ID"]);
					break;
				case "dismantle":
					$this->removeItem($args[0], $sourceItem);
					
					$ts[0] += $prize[0];
					$ts[1] += $prize[1];
					
					$now = Misc::getNow();
					
					if ($ts[3] != $now)
						$ts[2] = 5;
					
					$ts[2]--;
					$ts[3] = $now;
					
					$GLOBALS['db']->exec("DELETE FROM items WHERE ID = ".$sourceItem->raw['ID']);
					break;
				case "removestone":
					if(($freeSlot = $this->getFreeBackpackSlot()) === false)
						exit('&Error:no backspace free');
				
					// Add stone to inventory
					$stone = [
					"type" => 15,
					"item_id" => $sourceItem->gem["type"],
					"dmg_min" => 0,
					"dmg_max" => 0,
					"a1" => 0,
					"a2" => 0,
					"a3" => 0,
					"a4" => 0,
					"a5" => 0,
					"a6" => 0,
					"value_silver" => 2500,
					"value_mush" => $sourceItem->gem["val"] * 65536,
					"slot" => $freeSlot];
					
					$this->insertItem($stone, $freeSlot);
					
					// Remove stone from item
					$sourceItem->raw['type'] = $sourceItem->type + ($sourceItem->enchant['type'] * 16777216) + ($sourceItem->gemSlot ? 65536 : 0);
					$sourceItem->raw['value_mush'] = $sourceItem->raw['value_mush'] % 65536;
					
					$sourceItem->gemSlot = false;
					$sourceItem->gem["type"] = 0;
					$sourceItem->gem["val"] = 0;
					
					if(!$spec)
					{
						$ts[0] -= $prize[0];
						$ts[1] -= $prize[1];
						
						if ($ts[0] < 0 || $ts[1] < 0)
							exit();
					}
					else
					{
						$this->data["mush"] -= 10;
						
						if ($this->data["mush"] < 0)
							exit("&Error:need more coins");
						
						$GLOBALS["db"]->exec("UPDATE players SET mush = mush - 10 WHERE ID = " . $this->data["ID"]);
					}
					
					$GLOBALS["db"]->exec("UPDATE items SET value_mush = " . $sourceItem->raw['value_mush'] . ", type = " . $sourceItem->raw['type'] . " WHERE ID = ".$sourceItem->raw['ID']);
					break;
				case "addsocket":
					$sourceItem->raw["type"] += 65536;
					
					if(!$spec)
					{
						$ts[0] -= $prize[0];
						$ts[1] -= $prize[1];
						
						if ($ts[0] < 0 || $ts[1] < 0)
							exit();
					}
					else
					{
						$this->data["mush"] -= 25;
						
						if ($this->data["mush"] < 0)
							exit("&Error:need more coins");
						
						$GLOBALS["db"]->exec("UPDATE players SET mush = mush - 25 WHERE ID = " . $this->data["ID"]);
					}
					
					$GLOBALS["db"]->exec("UPDATE items SET type = type + 65536 WHERE ID = ".$sourceItem->raw['ID']);
					break;
			}
			
			$this->data["blacksmith"] = implode("/", $ts); // new blacksmith
			
			$GLOBALS["db"]->exec("UPDATE players SET blacksmith = '" . $this->data["blacksmith"] . "' WHERE ID = " . $this->data["ID"]);
			
			$pD = new Pets($this->data["pets"], $this->data["petsFed"], $this->data["petsDung"], $this->data["petsPvP"], $this->data["petsBest2"], null, $this->data["blacksmith"], $this->data["pethonor"]);
			
			$GLOBALS['ret'][] = "ownpets.petsSave:" . $pD->getPetsSave();
		}else if($args[0] > 100 || $args[2] > 100){ 
			//equiping/unequiping on copycats

			//if both items are set, check they are for the same class and of same type
			// var_dump($targetSlot);
			if($targetItem){
				if($sourceItem->type == 15){
					if($targetItem->gemSlot){
						$targetItem->setGem($sourceItem);
						$this->removeItem($args[0], $sourceItem);
						$GLOBALS['ret'][] = "owntower.towerSave:".$this->getTowerSave();
						return;
					}else
						exit('&Error:item has no socket');
				}
				if($sourceItem->type != $targetItem->type)
					exit("&Error:target item not found");
				if($sourceItem->type <= 7 && $targetItem->type <= 7 && $sourceItem->forClass != $targetItem->forClass)
					exit("&Error:class cannot use this item");
			}

			//if not equipable, shields included, copycat can't have shield
			if($sourceItem->type > 10 || $sourceItem->type == 2)
				exit("&Error:class cannot use this item");

			//if target to equip the item, if not for target class
			if($args[2] > 100 && $sourceItem->type <= 7 && $sourceItem->forClass != $this->copycats[$args[2]-101]->class)
				exit("&Error:class cannot use this item");


			$sourceItem->move($args[2], $args[3], $targetSlot);
			//remove item from array, add to target array
			$this->removeItem($args[0], $sourceItem);
			$this->addItem($args[2], $sourceItem);

			if($targetItem){
				//$GLOBALS['db']->exec("UPDATE items SET slot = ".$sourceSlot." WHERE ID = ".$targetItem->getID());
				$targetItem->move($args[0], $args[1], $sourceSlot);
				$this->removeItem($args[2], $targetItem);
				$this->addItem($args[0], $targetItem);
			}


			$GLOBALS['ret'][] = "owntower.towerSave:".$this->getTowerSave();
		}else if($args[2] == 12){
			//spooling items in the toilet
			
			// Eggs are fucked up? (Greg) - Edit: No, they're not.
			//if($sourceItem->raw['type'] == 16)
				//exit("&Error:");
			
			//only from equip
			if($args[0] != 2 && $args[0] != 5)
				exit('&Error:');

			if($sourceItem->raw['value_silver'] <= 0)
				exit("&Error:items cannot be flushed twice");
			
			if($sourceItem->type <= 10)
				$sourceItem->flush($this->data['lvl']);
			else{
				
				// Just keep the item, flushed of course
				$sourceItem->raw['value_silver'] = 0;
				$GLOBALS['db']->exec("UPDATE items SET value_silver = 0 WHERE ID = ".$sourceItem->raw['ID']);
			}

			if(!$this->toiletFullToday()){
				$reqExp = Account::getWcRequiredExp($this->data['wcaura']);
				$this->data['wcexp'] += 25;
				
				if($this->data['wcexp'] > $reqExp)
					$this->data['wcexp'] = $reqExp;
				$GLOBALS['db']->exec("UPDATE players SET wcexp = ".$this->data['wcexp'].", wcdate = ".$GLOBALS["CURRTIME"]." WHERE ID = ".$this->data['ID']);
				$GLOBALS['ret'][] = "toilettfull:1";
			}
		}else if($args[2] == 13) {
			// Moving item into pets by Greg (Feeding / Unlocking a pet)
			
			$now = Misc::getNow();
			
			//only from equip
			if($args[0] != 2 && $args[0] != 5) {
				exit('&Error:');
			}
			
			if($sourceItem->type != 16) { // Invalid item
				exit('&Error:this is not an egg');
			}
			
			// First, load pets' data
			$pD = new Pets($this->data["pets"], $this->data["petsFed"], $this->data["petsDung"], $this->data["petsPvP"], $this->data["petsBest2"], null, $this->data["blacksmith"], $this->data["pethonor"]);
			$pet = $pD->getPetStats($args[3]);
			
			if($sourceItem->id > 30 && $sourceItem->id < 36) {
				// Feeding pet
				
				if($pet[0] == 0) { // Don't have pet
					exit('&Error:this pet is not in your collection');
				}
				
				if ($pet[0] >= 100) // Pet max 100
					exit("&Error:pet is maxed out");
				
				$petFed = isset($pD->fedData[$args[3] - 1]) ? $pD->fedData[$args[3] - 1] : [0, 0];
				
				if($petFed[0] >= 3 && $petFed[1] == $now) { // Pet fed max today
					exit('&Error:pet is not hungry');
				}
				
				$it_c = $sourceItem->id - 30;
				
				if($pet[1] != $it_c) { // Wrong class? IDK
					exit('&Error:');
				}
				
				$pD->petData[$args[3] - 1] += 1; // Add level to pet
				
				// Fed++
				if($petFed[1] != $now)
				{
					$petFed[0] = 1;
					$petFed[1] = $now;
				}
				else
					$petFed[0]++;
				
				$pD->fedData[$args[3] - 1] = $petFed;
				$unlock = $args[3] - 1;
			}else if($sourceItem->id > 0 && $sourceItem->id < 16) {
				// Unlocking a pet with normal/rare egg
				
				$rare = false;
				$item = $sourceItem->id;
				
				if($item > 10) {
					$rare = true;
					$item -= 10;
				}
				
				$base = ($item - 1) * 20;
				
				$unlock = 0;
				
				$unlockable = $pD->petsUnlockable($item);
				
				if(count($unlockable) == 0)
					exit("Success:");
				
				if($pD->petData[$base] == 0 || $pD->petData[$base + 1] == 0 || $pD->petData[$base + 2] == 0)
				{
					$rand = rand(0, 2);
					while($pD->petData[$base + $rand] != 0)
						$rand = rand(0, 2);
					
					$unlock = $base + $rand;
				}
				else if(!$rare)
				{
					$unlockable = $pD->petsUnlockable($item, 0, 13);
					
					if(count($unlockable) == 0)
						exit("Success:");
					
					$unlock = $unlockable[rand(0, (count($unlockable) - 1))];
				}
				else if($rare)
				{
					$unlockable = $pD->petsUnlockable($item, 14, 17);
					
					if(count($unlockable) == 0)
						$unlockable = $pD->petsUnlockable($item, 0, 17);
					
					if(count($unlockable) == 0)
						exit("Success:");
					
					$unlock = $unlockable[rand(0, (count($unlockable) - 1))];
				}
				
				$pD->petData[$unlock] = 1;
			}else if($sourceItem->id == 21) {
				$unlockable = [];
				
				for($i = 0; $i <= 4; $i++)
				{
					$base = $i * 20;
					
					if ($pD->petData[$base + 18] == 0)
						$unlockable[] = $base + 18;
					else if($pD->petData[$base + 19] == 0)
						$unlockable[] = $base + 19;
				}
				
				if(count($unlockable) == 0)
					exit("Success:");
				
				$unlock = $unlockable[rand(0, (count($unlockable) - 1))];
				
				$pD->petData[$unlock] = 1;
			}else{
				exit("&Error:item does not belong here");
			}
			
			$petData = implode("/", $pD->petData);
			$fedData = json_encode($pD->fedData);
			
			$GLOBALS['db']->exec("UPDATE players SET pets = '$petData', petsFed = '$fedData' WHERE ID = {$this->data["ID"]};DELETE FROM items WHERE ID = ".$sourceItem->raw['ID']);
			
			$this->removeItem($args[0], $sourceItem); // Remove item
			
			$pD->recountBest(); // Recount best pet
			
			$GLOBALS['ret'][] = "petsHatchIndex:".($unlock + 1);
			$GLOBALS['ret'][] = "petsdefensetype:" . $pD->pvpData[0][1];
			$GLOBALS['ret'][] = "ownpets.petsSave:" . $pD->getPetsSave();
		}else if($args[2] == 11) {
			// Donating to witch by Greg
			
			if($sourceItem->type != $this->witch['event'])
				exit("Success:"); // Not the type
			
			// Set fill lvl
			$this->witch['fill']++;
			$sql = "UPDATE witch SET fill = fill + 1";
			$GLOBALS['db']->exec($sql);

			// Add gold
			$gold = $sourceItem->raw['value_silver'] * 2;
			$this->data['silver'] += $gold;
			$sql = "UPDATE players SET silver = '{$this->data['silver']}' WHERE ID = '{$this->data['ID']}'";
			$GLOBALS['db']->exec($sql);
			
			$GLOBALS['ret'][] = "witch.witchData:9/{$this->getWitchData()}/1452384000/0/1402139157/9/6/51/1387968268/0/61/1389353441/5/31/1390907951/8/101/1392626428/1/71/1394196822/2/41/1396169319/4/81/1398237044/7/11/1400137421/3/91/1402139201/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/";
			
			$this->removeItem($args[0], $sourceItem); // Remove item
			$GLOBALS['db']->exec("DELETE FROM items WHERE ID = ".$sourceItem->raw['ID']);
			
		}
	}

	public function useItem($args, $sourceItem, $targetItem){
		switch($sourceItem->type){
			case 11:
				if($sourceItem->raw['item_id'] > 29 && $sourceItem->raw['item_id'] < 43) {
					// Mirror
					
					if($this->data['mirror'] >= 13) {
						break; // Mirror full
					}
					
					// Update mirror
					$sql = "UPDATE players SET mirror = mirror + 1 WHERE ID = '{$this->data['ID']}'";
					$GLOBALS['db']->exec($sql);
					
					// Renew account data because it'll recheck mirror lvl
					//$GLOBALS['RENEW'] = true;
					
					$this->data['mirror']++;

					// Remove item
					$GLOBALS['db']->exec('DELETE FROM items WHERE ID = '.$sourceItem->raw['ID']);
					$this->removeItem($args[0], $sourceItem);
				}
				else if($sourceItem->raw["item_id"] == 20) {
					// Remove item
					$GLOBALS['db']->exec('DELETE FROM items WHERE ID = '.$sourceItem->raw['ID']);
					$this->removeItem($args[0], $sourceItem);
					
					if($this->data['wcaura'] > 0) {
						$ret[] = "ownplayersave.playerSave:".$this->getPlayerSave();
						break;
					}	
					
					$this->data['wcaura'] = 1;
					
					// Update WC
					$sql = "UPDATE players SET wcaura = 1 WHERE ID = '{$this->data['ID']}'";
					$GLOBALS['db']->exec($sql);
					
					$GLOBALS['RENEW'] = true;
				}
				
				break;
			case 12:
				//elixirs
				
				for($i = 1; $i <= 3; $i++) {
					if($this->data["potion_type$i"] == $sourceItem->raw['item_id'] && $this->data["potion_dur$i"] >= $GLOBALS["CURRTIME"]){
						$this->data["potion_dur$i"] += $sourceItem->raw['a4'] * 3600;

						$GLOBALS['db']->exec("UPDATE players SET potion_dur$i = ".$this->data["potion_dur$i"]." WHERE ID = ".$this->data['ID']);

						$GLOBALS['db']->exec('DELETE FROM items WHERE ID = '.$sourceItem->raw['ID']);
						$this->removeItem($args[0], $sourceItem);

						break 2;
					}
				}
				
				for($i = 1; $i <= 3; $i++){
					if($this->data["potion_dur$i"] < $GLOBALS["CURRTIME"]){
						//if there is no potion in this slot (if expired basicaly)
						
						$this->data["potion_dur$i"] = $sourceItem->raw['a4'] * 3600 + $GLOBALS["CURRTIME"];
						$this->data["potion_type$i"] = $sourceItem->raw['item_id'];

						$GLOBALS['db']->exec("UPDATE players SET potion_dur$i = ".$this->data["potion_dur$i"].", potion_type$i = ".$this->data["potion_type$i"]." WHERE ID = ".$this->data['ID']);

						$GLOBALS['db']->exec('DELETE FROM items WHERE ID = '.$sourceItem->raw['ID']);
						$this->removeItem($args[0], $sourceItem);

						break 2;
					}
				}

				exit("&Error:no potion downgrade");

				break;
			case 13:
				//klaser
				$this->album = new Album(Album::getDefaultData(), 0);

				foreach($this->equip as $item)
					$this->album->addItem($item);

				foreach($this->backpack as $item)
					$this->album->addItem($item);

				foreach($this->fortressBackpack as $item)
					$this->album->addItem($item);

				foreach($this->copycatEquip as $cceq)
					foreach($cceq as $item)
						$this->album->addItem($item);

				$this->album->encode();
				$this->data['album'] = $this->album->count;
				$this->removeItem($args[0], $sourceItem);

				$GLOBALS['ret'][] = 'scrapbook.r:'.$this->album->data;

				$GLOBALS['db']->exec('DELETE FROM items WHERE ID = '.$sourceItem->raw['ID']);
				$GLOBALS['db']->exec('UPDATE players SET album = '.$this->album->count.', album_data = "'.$this->album->data.'" WHERE ID = '.$this->data['ID']);
				break;
			case 15:
				//gems
				if($targetItem){
					if($targetItem->gemSlot){
						$targetItem->setGem($sourceItem);
						$this->removeItem($args[0], $sourceItem);
					}else{
						exit('&Error:item has no socket');
					}
				}
				break;
			case 16 :
				// Eggs by Greg
				
				$pD = new Pets();
				
				if($sourceItem->id == 22) {
					// Generate random pets
					$pD->petData[rand(0, 2)] = 1;
					$pD->petData[rand(20, 22)] = 1;
					$pD->petData[rand(40, 42)] = 1;
					$pD->petData[rand(60, 62)] = 1;
					$pD->petData[rand(80, 82)] = 1;
					
					$this->removeItem($args[0], $sourceItem); // Remove source item
					
					$petData = implode("/", $pD->petData);
					
					$rand = rand(1, 5);
					
					$pD->recountBest(); // Recount best pet
					
					$GLOBALS['db']->exec("UPDATE players SET pets = '$petData' WHERE ID = {$this->data["ID"]};DELETE FROM items WHERE ID = ".$sourceItem->raw['ID']);
					
					$GLOBALS['ret'][] = "ownpets.petsSave:" . $pD->getPetsSave(); // Echo data
				}else if($sourceItem->id > 0 && $sourceItem->id < 16) {
					$this->moveItem([$args[0], $args[1], 13, 1]); // Do it at move item thx :)
				}
			default:
				break;
			case 18: // Heart of Darkness (Underworld key)
				// Remove item
				$GLOBALS['db']->exec('DELETE FROM items WHERE ID = '.$sourceItem->raw['ID']);
				$this->removeItem($args[0], $sourceItem);
				
				$GLOBALS['RENEW'] = true;
				
				$qry = $GLOBALS['db']->prepare('SELECT * FROM underworld WHERE owner = :owner');
				$qry->bindParam(':owner', $this->data['ID']);
				$qry->execute();

				if($qry->rowCount() > 0) {
					$ret[] = "ownplayersave.playerSave:".$this->getPlayerSave();
					exit('&Error:class cannot use this item');
				}
				
				//Unlock Underworld
				$sql = "INSERT INTO underworld(owner, soul) VALUES(".$this->data['ID'].", ".$GLOBALS["uwStartingSoul"].")";
				$GLOBALS['db']->exec($sql);
					
				$GLOBALS['RENEW'] = true;
				
				$uw = new Underworld($this->data["ID"]);
				
				$GLOBALS['ret'][] = "owntower.towerSave:".$this->getTowerSave();
				$GLOBALS['ret'][] = "underworldprice.underworldPrice(10):".$uw->getPrice();
				$GLOBALS['ret'][] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUpgradePrice();
				$GLOBALS['ret'][] = "underworldmaxsouls:".$uw->getMaxSouls();
				break;
			case 19: // Wheel v2
				// Remove item
				$GLOBALS['db']->exec('DELETE FROM items WHERE ID = '.$sourceItem->raw['ID']);
				$this->removeItem($args[0], $sourceItem);
				
				$GLOBALS['RENEW'] = true;
				
				if($this->data['wheel'] == 1) { // Wheel v2 already unlocked
					$ret[] = "ownplayersave.playerSave:".$this->getPlayerSave();
					break;
				}

				// Unlock wheel v2
				$this->data['wheel'] = 1;
				
				$GLOBALS['db']->exec("UPDATE players SET wheel = ".$this->data['wheel']." WHERE ID = ".$this->data['ID']);
					
				$GLOBALS['RENEW'] = true;
				break;
		}
	}

	public function getItemAtSlot($source, $rawSlot){
		//sources
		//equip = 1 ?
		//backpack = 2, 
		//fortress backpack = 5;
		//shops = 3/4
		switch($source){
			case 1:
				//moved to Item::getItemSlot
				//if($type > 0)
				//	$rawSlot = Item::getItemSlot($source, $rawSlot, $type);

				foreach($this->equip as $item)
					if($rawSlot == $item->raw['slot'])
						return $item;
				break;
			case 2:
				foreach($this->backpack as $item)
					if($rawSlot == $item->raw['slot'])
						return $item;
				break;
			case 3:
			case 4:
				foreach($this->shops as $item)
					if($rawSlot == $item->raw['slot'])
						return $item;
				break;
			case 5:
				foreach($this->fortressBackpack as $item)
					if($rawSlot == $item->raw['slot'])
						return $item;
				break;
			case 101:
			case 102:
			case 103:
				$source-=101;
				foreach($this->copycats[$source]->equip as $item)
					if($rawSlot == $item->raw['slot'])
						return $item;
				break;
		}

		return null;
	}

	//used in moveItem
	private function addItem($target, $item){
		switch($target){
			case 1:
				$this->equip[] = $item;
				break;
			case 2:
				$this->backpack[] = $item;
				break;
			case 3:
			case 4:
				$this->shops[] = $item;
				break;
			case 5:
				$this->fortressBackpack[] = $item;
				break;
			case 101:
			case 102:
			case 103:
				$target-=101;
				$this->copycatEquip[$target][] = $item;
				$this->copycats[$target]->equip[] = $item;
				break;
		}
	}

	//used in moveItem
	public function removeItem($source, $item){
		switch($source){
			case 1:
				for($i = 0; $i < count($this->equip); $i++){
					if($this->equip[$i]->raw['ID'] == $item->raw['ID'])
						unset($this->equip[$i]);
				}
				break;
			case 2:
				for($i = 0; $i < count($this->backpack); $i++){
					if($this->backpack[$i]->raw['ID'] == $item->raw['ID']){
						unset($this->backpack[$i]);
						$this->backpack = array_values($this->backpack);
					}
				}
				break;
			case 3:
			case 4:
				for($i = 0; $i < count($this->shops); $i++){
					if($this->shops[$i]->raw['ID'] == $item->raw['ID']){
						unset($this->shops[$i]);
						$this->shops = array_values($this->shops);
					}
				}
				break;
			case 5:
				for($i = 0; $i < count($this->fortressBackpack); $i++){
					if($this->fortressBackpack[$i]->raw['ID'] == $item->raw['ID']){
						unset($this->fortressBackpack[$i]);
						$this->fortressBackpack = array_values($this->fortressBackpack);
					}
				}
				break;
			case 101:
			case 102:
			case 103:
				$source-=101;
				for($i = 0; $i < count($this->copycats[$source]->equip); $i++){
					if($this->copycats[$source]->equip[$i]->raw['ID'] == $item->raw['ID']){
						// unset($this->copycatEquip[$source][$i]);
						// $this->copycatEquip[$source] = array_values($this->copycatEquip);

						unset($this->copycats[$source]->equip[$i]);
						$this->copycats[$source]->equip = array_values($this->copycats[$source]->equip);
					}
				}

				break;
		}
	}

	//inserts item into a free slot, adds to db
	//params: items - raw values for db, 
	//slot - if have target slot, if you checked wether or not there is a free slot before using this function 
	//returns item object
	public function insertItem($item, $slot = false){

		if($slot === false)
			$item['slot'] = $this->getFreeBackpackSlot();
		else
			$item['slot'] = $slot;

		$itemObject = new Item($item);

		if($item['slot'] < 5)
			$this->backpack[] = $itemObject;
		else{
			$this->fortressBackpack[] = $itemObject;
			$GLOBALS['ret'][] = "fortresschest.item(".$this->getFortressBackpackSize()."):".$this->getFortressBackpackSave();
		}

		$GLOBALS['db']->exec("INSERT INTO items(owner, slot, type, item_id, dmg_max, dmg_min, a1, a2, a3, a4, a5, a6, value_silver, value_mush)
			VALUES(".$this->data['ID'].", $item[slot], $item[type], $item[item_id], $item[dmg_max], $item[dmg_min], $item[a1], $item[a2], $item[a3], $item[a4], $item[a5], $item[a6], $item[value_silver], $item[value_mush])");


		return $itemObject;
	}


	//returns raw backpack slot
	public function getFreeBackpackSlot(){

		$bool = [true, true, true, true, true];

		//look at backpack first
		$size = count($this->backpack);
		if($size < 5){
			foreach($this->backpack as $item)
				$bool[$item->slot] = false;
			for($i = 0; $i < 5; $i++)
				if($bool[$i] == true)
					return $i;
		}

		//if nothing found, look at fortress backpack
		$size = $this->getFortressBackpackSize();
		
		$arSize = count($this->fortressBackpack);
		
		if($size > 0 && $arSize < $size){
			$bool = [];
			for($i = 0; $i < $size; $i++)
				$bool[] = true;
			foreach($this->fortressBackpack as $item)
				$bool[$item->slot] = false;
			for($i = 0; $i < $size; $i++)
				if($bool[$i] == true)
					return $i + 100;
		}

		return false;
	}



	//lvling up displaying and all that shit
	//Exptype By Greg (for more than bigint limit i guess)
	public function addExp($exp){
		// Achievment check
		
		
		if($this->data['expType'] == '1')
			$this->data['exp'] *= 1000000000;
		
		$this->data['exp'] = intval($this->data['exp']);
		$this->data['exp'] += $exp;
		while($this->data['exp'] > Player::getExp($this->data['lvl'] - 1)){
			$this->data['exp'] -= Player::getExp($this->data['lvl'] - 1);
			$this->data['lvl']++;
		}
		
		if($this->data['expType'] == '1')
		{
			$exp = &$this->data['exp'];
			
			$exp /= 1000000000;
			
			$exp = explode(".", $exp)[0];
		}
		else if($this->data["exp"] > 18446744073709000000)
		{
			$exp = &$this->data["exp"];
			
			$exp /= 1000000000;
			
			$exp = explode(".", $exp)[0];
			
			$GLOBALS["db"]->exec("UPDATE players SET expType = 1 WHERE ID = " . $this->data["ID"]);
		}
	}

	public function questStart($quest){

		// TODO: Warn about not recieving item // Done by Jack in req.php 
	
		if($this->data['mount_time'] > $GLOBALS["CURRTIME"])
			$mountMultiplier = [0.9, 0.8, 0.7, 0.5][$this->data['mount'] - 1];
		else
			$mountMultiplier = 1;

		if($this->data["quest_dur$quest"] * $mountMultiplier > $this->data['thirst'])
			exit('&Error:need more adventurelust');

		
		if($this->data["status"] != 0)
			exit();
		
		$this->data['status'] = 2;
		$this->data['status_extra'] = $quest;
		$this->data['status_time'] = $GLOBALS["CURRTIME"] + ceil($this->data['quest_dur'.$quest] * $mountMultiplier);
		
		// By Jack
		// Set it when player start quest
		$this->data['quest_start'] = $GLOBALS["CURRTIME"];

		$GLOBALS['db']->exec("UPDATE players SET status = 2, status_extra = ".$quest.", status_time = ".$this->data['status_time'].", quest_start = ".$this->data['quest_start']." WHERE ID = ".$this->data['ID']);
	}

	public function questStop(){

		$this->data['status'] = 0;
		$this->data['status_extra'] = 0;
		$this->data['status_time'] = 0;
		
		$this->data['quest_start'] = 0; // Reset timer

		$GLOBALS['db']->exec("UPDATE players SET status = 0, status_extra = 0, status_time = 0, quest_start = 0 WHERE ID = ".$this->data['ID']);
	}

	public function questFinish($win, $monsterID, $type){

		$ret = [];
		for($i = 0; $i < 21; $i++)
			$ret[] = 0;

		$qryArgs = [];

		//picked quest
		$pickedQuest = $this->data['status_extra'];

		//thirst
		$mountMultiplier = $this->data['mount_time'] > $GLOBALS["CURRTIME"] ? [0.9, 0.8, 0.7, 0.5][$this->data['mount'] - 1] : 1;
		$this->data['thirst'] -= ceil($this->data['quest_dur'.$pickedQuest] * $mountMultiplier);
		$qryArgs[] = "thirst = ".$this->data['thirst'];

		//skip cost
		if($this->data['status_time'] > $GLOBALS["CURRTIME"] && $type != "2"){
			$this->data['mush']--;
			$qryArgs[] = 'mush = mush - 1';
		}
		else if($this->data['status_time'] > $GLOBALS["CURRTIME"] && $type == 2)
		{
			// 2nd type skip by Greg
			$this->data['hourglass']--;
			$qryArgs[] = 'hourglass = hourglass - 1';
		}

		//status reset
		$this->data['status'] = 0;
		$this->data['status_extra'] = 0;
		$this->data['status_time'] = 0;

		$qryArgs[] = "status = 0, status_extra = 0, status_time = 0";
		
		// By Jack
		// Reset bar (quest travel, blue timer thing)
		$this->data['quest_start'] = 0;
		$qryArgs[] = "quest_start = 0";

		if($win){
			$ret[0] = 1;

			//silver
			$ret[2] = round($this->data['quest_silver'.$pickedQuest] * (1 + $this->data['tower'] / 100) * (1 + self::getGuildBonusNew($this->data['guild'], 'guild_skill_treasure') / 100));
			$this->data['silver'] += $ret[2];

			//exp
			//$ret[3] = $this->data['quest_exp'.$pickedQuest];
			$ret[3] = round($this->data['quest_exp'.$pickedQuest] * $this->getAlbumMultiplier() * (1 + self::getGuildBonusNew($this->data['guild'], 'guild_skill_instructor') / 100));
			$this->addExp($ret[3]);

			//honor
			$ret[5] = 10;
			$this->data['honor'] += 10;
			
			//achievment
			$this->data['questsdone']++;
			$qryArgs[] = "questsdone = questsdone + 1";

			$mushMax = $GLOBALS['mushbonus'];
			if(Misc::getEvent()[3] == 1) {
				$mushMax = $GLOBALS['event_mushbonus'];
			}
			
			//shrooms gained
			if(rand(0, $mushMax) == $mushMax){
				$this->data['mush']++;
				$qryArgs[] = 'mush = mush + 1';
				$ret[4] = 1;
			}

			$qryArgs[] = 'silver = '.$this->data['silver'];
			$qryArgs[] = 'exp = '.$this->data['exp'];
			$qryArgs[] = 'lvl = '.$this->data['lvl'];
			$qryArgs[] = 'honor = honor + 10';

			//add monster to scrapbook
			if($this->hasAlbum() && $this->album->addMonster($monsterID)){
				$this->album->encode();
				$this->data['album']++;
				$GLOBALS['ret'][] = "scrapbook.r:".$this->album->data;
				$qryArgs[] = "album = ".$this->data['album'];
				$qryArgs[] = "album_data = '".$this->album->data."'";
			}
			
			// Get item
			$getItem = false;
			if(count($this->questItems[$pickedQuest - 1]) != 0) {
				if(($freeSlot = $this->getFreeBackpackSlot()) !== false) {
					$getItem = true;
				}
			}
			
			$isSpecialItemReward = false;
			
			if($getItem) {
				$displayItm = true;
				// Add item
				$slot = 1000 + $pickedQuest;
				
				// Show item
				$item = $this->questItems[$pickedQuest - 1];
				
				if (($item['type'] == 16) && ($item['item_id'] > 30)) { # Pet fruits
					switch ($item['item_id'] - 30) {
						default: $GLOBALS['db']->exec("UPDATE players SET food_black = food_black + 1 WHERE ID = '{$this->data['ID']}'"); break;
						case 1: $GLOBALS['db']->exec("UPDATE players SET food_black = food_black + 1 WHERE ID = '{$this->data['ID']}'"); break;
						case 2: $GLOBALS['db']->exec("UPDATE players SET food_orange = food_orange + 1 WHERE ID = '{$this->data['ID']}'"); break;
						case 3: $GLOBALS['db']->exec("UPDATE players SET food_green = food_green + 1 WHERE ID = '{$this->data['ID']}'"); break;
						case 4: $GLOBALS['db']->exec("UPDATE players SET food_red = food_red + 1 WHERE ID = '{$this->data['ID']}'"); break;
						case 5: $GLOBALS['db']->exec("UPDATE players SET food_blue = food_blue + 1 WHERE ID = '{$this->data['ID']}'"); break;
					}
				} elseif (($item['type'] == 16) && ($item['item_id'] <= 15)) { # Pet egg (feature) [common / rare]
					$pets = explode('/', $this->data['pets']);
					$petsDung = explode('/', $this->data['petsDung']);
					$petsUnlock = json_decode($this->data['petsunlock'], true);
					
					$itemId = $item['item_id'];
					list ($from, $to) = [1, 14];
					
					if ($itemId > 10) {
						$itemId -= 10;
						list ($from, $to) = [15, 18];
					}
					
					$env = $itemId;
					$unlockedIndex = 0;
					for ($slot = $from; $slot <= $to; $slot++) {
						$base = (($env - 1) * 20) + $slot;
						if ($petsUnlock[$base - 1] > 0) continue; # Already in unlockfeature
						if ($pets[$base - 1] > 0) continue; # Already unlocked pet
						if (($slot > 3) && ($petsDung[$env - 1] < $slot)) break; # No unlocked slot yet
						$unlockedIndex = $base;
					}

					if ($unlockedIndex > 0) {
						$petsUnlock[$unlockedIndex - 1] = 1;
						$qry = $GLOBALS['db']->prepare("UPDATE players SET petsunlock = ? WHERE ID = ?");
						$qry->execute([json_encode(array_values($petsUnlock)), $this->data['ID']]);
						$ret[] = 'unlockfeature:'.$this->unlockFeature();
					}
					
					$displayItm = false;
				} elseif (($item['type'] == 16) && ($item['item_id'] == 21)) { # Pet egg (feature) [gold]
					$pets = explode('/', $this->data['pets']);
					$petsDung = explode('/', $this->data['petsDung']);
					$petsUnlock = json_decode($this->data['petsunlock'], true);
					
					$itemId = 21;
					list($from, $to) = [19, 20];
					$unlockedIndex = 0;
					
					for ($env = 1; $env <= 5; $env++) {
						for ($slot = $from; $slot <= $to; $slot++) {
							$base = (($env - 1) * 20) + $slot;
							if ($petsUnlock[$base - 1] > 0) continue; # Already in unlockfeature
							if ($pets[$base - 1] > 0) continue; # Already unlocked pet
							if (($slot > 3) && ($petsDung[$env - 1] < $slot)) break; # No unlocked slot yet
							$unlockedIndex = $base;
						}
					}
					
					if ($unlockedIndex > 0) {
						$petsUnlock[$unlockedIndex - 1] = 1;
						$qry = $GLOBALS['db']->prepare("UPDATE players SET petsunlock = ? WHERE ID = ?");
						$qry->execute([json_encode(array_values($petsUnlock)), $this->data['ID']]);
						$ret[] = 'unlockfeature:'.$this->unlockFeature();
					}
					
					$displayItm = false;
				} elseif (($item['type'] == 16) && ($item['item_id'] == 22)) { # Pet nest (feature)
					$GLOBALS['db']->exec("UPDATE players SET petnest = 1 WHERE ID = '{$this->data['ID']}'");
					$ret[] = 'unlockfeature:'.$this->unlockFeature();
					
					$displayItm = false;
				} elseif (($item['type'] == 11) && ($item['item_id'] >= 30) && ($item['item_id'] <= 42)) { # Mirror (feature)
					$GLOBALS['db']->exec("UPDATE players SET unlockmirror = unlockmirror + 1 WHERE ID = '{$this->data['ID']}'");
					$ret[] = 'unlockfeature:'.$this->unlockFeature();
					
					$displayItm = false;
				} else {
					$sql = "UPDATE items SET slot = $freeSlot WHERE slot = $slot AND owner = '{$this->data['ID']}'";
					$GLOBALS['db']->exec($sql);
					$GLOBALS['RENEW'] = true;
				}
				
				$isSpecialItemReward = $item['type'] == 11;
				
				if ($displayItm === true) {
					$base = 9;
					$ret[$base] = $item['type'];
					$ret[$base + 1] = $item['item_id'];
					$ret[$base + 2] = $item['dmg_min'];
					$ret[$base + 3] = $item['dmg_max'];
					$ret[$base + 4] = $item['a1'];
					$ret[$base + 5] = $item['a2'];
					$ret[$base + 6] = $item['a3'];
					$ret[$base + 7] = $item['a4'];
					$ret[$base + 8] = $item['a5'];
					$ret[$base + 9] = $item['a6'];
					$ret[$base + 10] = $item['value_silver'];
					$ret[$base + 11] = $item['value_mush'];
				}
			}
		}

		// Remove old quest items
		$sql = "DELETE FROM items WHERE owner = '{$this->data['ID']}' AND slot > 1000 AND slot < 1004";
		$GLOBALS['db']->exec($sql);
		$this->questItems = [[], [], []]; // null
		
		
		//generate new quests
		
		for($i = 1; $i <= 3; $i++){
			$quest = self::generateQuest($this->data['lvl'], $this->data['perm']);

			//not sure about this
			// if($this->data['thirst'] > 0 && $quest['duration'] > $this->data['thirst'])
			// 	$quest['duration'] = $this->data['thirst'];

			//$quest['exp'] = round($quest['exp'] * $this->getAlbumMultiplier());
		
			$qryArgs[] = "quest_exp$i = ".$quest['exp'];
			$qryArgs[] = "quest_silver$i = ".$quest['silver'];
			$qryArgs[] = "quest_dur$i = ".$quest['duration'];

			$this->data["quest_exp$i"] = $quest['exp'];
			$this->data["quest_silver$i"] = $quest['silver'];
			$this->data["quest_dur$i"] = $quest['duration'];
			// var_dump($quest);
			
			// item by Greg
			if(rand(1, 3) != 3 && $win) {
				// item
				$this->newQuestItem($i, $isSpecialItemReward);
			}
		}



		$GLOBALS['db']->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$this->data['ID']);

		return join("/", $ret)."/";
	}
	
	// Hourglass equip by Jack
	public function equipHourglass($sourceItem, $args) {
		
		if ($sourceItem->raw['type'] == 17 && $sourceItem->raw['item_id'] == 1) {
					
			// Update hourglass
			$sql = "UPDATE players SET hourglass = hourglass + 1 WHERE ID = '{$this->data['ID']}'";
			$GLOBALS['db']->exec($sql);
					
			$GLOBALS['RENEW'] = true;
					
			$this->data['hourglass']++;

			// Remove item
			$GLOBALS['db']->exec('DELETE FROM items WHERE ID = '.$sourceItem->raw['ID']);
			$this->removeItem($args[0], $sourceItem);
			$this->removeItem($args[2], $sourceItem);
		}	

	}

	public function fortressBuild($id){

		if($id == '9' || $id == '7' || $id == '1' || $id == '6' || $id == '8') {
			if($this->data['b'.$id] > 14) {
				exit('&Error:not implented');
			}
		}
	
		//time/silver/wood/stone
		$price = Fortress::getUpgradePriceNew($id, $this->data['b'.$id]);

		//check if enough resources, if not exit(), no errors, client doesnt allow to build if not enough anyway
		if($price[0] > $this->data['silver'] || $price[1] > $this->data['wood'] || $price[2] > $this->data['stone'])
			exit();
		
		// -- by Greg
		$shorter = 1 - ($this->data['b1'] * 5 / 100);
		
		$this->data['silver'] -= $price[0];
		$this->data['wood'] -= $price[1];
		$this->data['stone'] -= $price[2];

		$GLOBALS['db']->exec("UPDATE players SET silver = silver - $price[0] WHERE ID = ".$this->data['ID'].";UPDATE fortress SET wood = wood - $price[1], stone = stone - $price[2] WHERE owner = ".$this->data['ID']);

		// by Jack
		$time = Fortress::getUpgradeTime($id, $this->data['b'.$id]);
		
		//update db and display variables
		$this->data['build_id'] = $id + 1;
		$this->data['build_start'] = $GLOBALS["CURRTIME"];
		$this->data['build_end'] = $this->data['build_start'] + ($time * $shorter);

		//TODO: decrease resources in this query, another query for gold
		$GLOBALS['db']->query("UPDATE fortress SET build_id = ".$this->data['build_id'].", build_start = ".$this->data['build_start'].", build_end = ".$this->data['build_end']." WHERE owner = ".$this->data['ID']);
	}

	public function fortressBuildStop(){

		$id = $this->data['build_id'] - 1;

		//decrease the returned resources
		$price = Fortress::getUpgradePrice($id, $this->data['b'.$id], $this->data['b1']);


		$this->data['build_id'] = 0;
		$this->data['build_start'] = 0;
		$this->data['build_end'] = 0;


		$GLOBALS['db']->query("UPDATE fortress SET build_id = 0, build_start = 0, build_end = 0 WHERE owner = ".$this->data['owner']);
	}

	public function fortressDigStart(){

		$price = Fortress::getSearchGemPrice($this->data['b4']);

		if($price[1] > $this->data['silver'] || $price[2] > $this->data['wood'] || $price[3] > $this->data['stone'])
			exit();
		
		$this->data['silver'] -= $price[1];
		$this->data['wood'] -= $price[2];
		$this->data['stone'] -= $price[3];
		
		$shorter = 1 - ($this->data['b1'] * 5 / 100);
		$price[0] *= $shorter;

		$GLOBALS['db']->exec("UPDATE players SET silver = silver - $price[1] WHERE ID = ".$this->data['ID'].";UPDATE fortress SET wood = wood - $price[2], stone = stone - $price[3] WHERE owner = ".$this->data['ID']);

		$this->data['dig_start'] = $GLOBALS["CURRTIME"];
		$this->data['dig_end'] = $GLOBALS["CURRTIME"] + $price[0];

		$GLOBALS['db']->exec("UPDATE fortress SET dig_start = ".$this->data['dig_start'].", dig_end = ".$this->data['dig_end']." WHERE owner = ".$this->data['ID']);
	}

	public function fortressDigStop(){
		$this->data['dig_start'] = 0;
		$this->data['dig_end'] = 0;

		$GLOBALS['db']->exec("UPDATE fortress SET dig_start = 0, dig_end = 0 WHERE owner = ".$this->data['ID']);
	}

	public function fortressDigFinish(){

	
		if($this->data['dig_end'] == 0 || $this->data['dig_start'] == 0)
			exit('&Error:status wtf');
	
		$mushcost = 0;
		if($this->data['dig_end'] > $GLOBALS["CURRTIME"])
			$mushcost = ceil(($this->data['dig_end'] - $GLOBALS["CURRTIME"]) / 600);

		if($mushcost > $this->data['mush'])
			exit("&Error:need more coins");

		$this->data['dig_start'] = 0;
		$this->data['dig_end'] = 0;

		$GLOBALS['db']->exec("UPDATE fortress SET dig_start = 0, dig_end = 0 WHERE owner = ".$this->data['ID']);

		if($mushcost > 0){
			$this->data['mush'] -= $mushcost;
			$GLOBALS['db']->exec("UPDATE players SET mush = mush - ".$mushcost." WHERE ID = ".$this->data['ID']);
		}
		
	}

	public function fortressBuildFinish(){
		try
		{
			$mushcost = 0;
			if($this->data['build_end'] > $GLOBALS["CURRTIME"])
				$mushcost = ceil(($this->data['build_end'] - $GLOBALS["CURRTIME"]) / 600);

			if($mushcost > $this->data['mush'])
				exit("&Error:need more coins");

			$id = $this->data['build_id'] - 1;
			$this->data['b'.$id] ++;

			$this->data['build_id'] = 0;
			$this->data['build_end'] = 0;
			$this->data['build_start'] = 0;

			$GLOBALS['db']->exec("UPDATE fortress SET b$id = ".$this->data["b$id"].", build_id = 0, build_end = 0, build_start = 0 WHERE owner = ".$this->data['ID']);

			//if finishing first lvl of mines, set gather time to timestamp
			if($id == 2 || $id == 3 || $id == 5){
				if($this->data["b$id"] == 1){
					$gather = $id < 4 ? 'gather'.($id - 1) : 'gather3';

					$this->data[$gather] = $GLOBALS["CURRTIME"];

					// var_dump("UPDATE fortress SET $gather = ".$this->data[$gather]." WHERE owner = ".$this->data['ID']);

					$GLOBALS['db']->exec("UPDATE fortress SET $gather = ".$this->data[$gather]." WHERE owner = ".$this->data['ID']);
				}
			}

			if($mushcost > 0){
				$this->data['mush'] -= $mushcost;
				$GLOBALS['db']->exec("UPDATE players SET mush = mush - ".$mushcost." WHERE ID = ".$this->data['ID']);
			}
			
			//Honor system add 10 honor
			$this->data['forthonor'] += 10;
			$GLOBALS['db']->exec("UPDATE fortress SET forthonor = ".$this->data['forthonor']." WHERE owner = ".$this->data['ID']);
		}
		catch(Exception $e)
		{
		}
	}

	public function fortressGather($building){

		if($building == 3){
			$time = $GLOBALS["CURRTIME"];

			$gain = floor(($time - $this->data['gather'.$building]) * (Fortress::getResourcesPerHour(3, $this->data['b5']) / 3600));

			$this->data['gather3'] = $time;
			$this->addExp($gain);

			$GLOBALS['db']->exec("UPDATE players SET exp = ".$this->data['exp'].", lvl = ".$this->data['lvl']." WHERE ID = ".$this->data['ID']);
			$GLOBALS['db']->exec("UPDATE fortress SET gather3 = $time WHERE owner = ".$this->data['ID']);
		}else{
			$resource = $building == 1 ? "wood" : "stone";

			$time = $GLOBALS["CURRTIME"];

			//count gain
			$gainMax = Fortress::getMaxResources($building, $this->data['b'.($building + 1)]);
			$gain = min($gainMax, floor(($time - $this->data['gather'.$building]) * (Fortress::getResourcesPerHour($building, $this->data['b'.($building + 1)]) / 3600)));

			//see if not going over fortress cap
			$gainMaxGlobal = Fortress::getGlobalMaxResources($building, $this->data['b0']);
			if($this->data[$resource] + $gain > $gainMaxGlobal){
				// $gain = $gainMaxGlobal - $this->data[$resource];

				//count out the gather time, time - leftovers / res/min 
				$time -= round((($this->data[$resource] + $gain) - $gainMaxGlobal) / (Fortress::getResourcesPerHour($building, $this->data['b'.($building + 1)]) / 3600));

				$this->data[$resource] = $gainMaxGlobal;
				$this->data['gather'.$building] =  $time;
			}else{
				$this->data['gather'.$building] = $time;
				$this->data[$resource] += $gain;
			}



			$GLOBALS['db']->exec("UPDATE fortress SET gather$building = $time, $resource = ".$this->data[$resource]." WHERE owner = ".$this->data['ID']);
			
		}
	}

	public function fortressUnitUpgrade($unit){
		$price = Fortress::getUnitUpgradePrice($unit, $this->data["ul$unit"]);

		if($price[0] > $this->data['wood'] || $price[1] > $this->data['stone'])
			exit();

		$this->data['wood'] -= $price[0];
		$this->data['stone'] -= $price[1];

		$this->data["ul$unit"] ++;
		
		if($this->data["ul$unit"] > 20) {
			exit("Success:");
		}

		$GLOBALS['db']->exec("UPDATE fortress SET wood = wood - $price[0], stone = stone - $price[1], ul$unit = ul$unit + 1 WHERE owner = ".$this->data['ID']);
	}

	public function fortressUnitTrain($unit, $quantity){
		//time/gold(0)/wood/stone
		$price = Fortress::getUnitTrainPrice($unit, $this->data["ul$unit"]);

		$price[2] *= $quantity;
		$price[3] *= $quantity;

		if($price[2] > $this->data['wood'] || $price[3] > $this->data['stone'])
			exit();

		$this->data['wood'] -= $price[2];
		$this->data['stone'] -= $price[3];
		//$this->data["ut$unit"] += $quantity;
		
		// Yo wat Krexxon, it's bug
		/*if($this->data["uttime$unit"] == 0)
			$this->data["uttime$unit"] = $GLOBALS["CURRTIME"];*/
		
		// Fix v1, bug too, it resets time
		//$this->data["uttime$unit"] = $GLOBALS["CURRTIME"];
		
		// Fix v2, final
		if($this->data["ut$unit"] == 0) {
			$this->data["uttime$unit"] = $GLOBALS["CURRTIME"];
		}
		
		// Moved this here to make this work
		$this->data["ut$unit"] += $quantity;

		$GLOBALS['db']->exec("UPDATE fortress SET wood = wood - $price[2], stone = stone - $price[3], ut$unit = ut$unit + $quantity, uttime$unit = ".$this->data["uttime$unit"]." WHERE owner = ".$this->data['ID']);
		
	}

	//saveData for shadow world dungeon
/*	private function getShadowDungSave($d1, $d2, $d3, $d4){
		return  ($d4 * 16843008) + ($d1)  + (256 * ($d2 - $d4)) + (65536 * ($d3 - $d4));
	}*/

	public function getFortressBackpackSize(){
		return $this->data['b9'];
	}

	public function getFortressBackpackSave(){
		$ret = [];

		for($i = 0; $i < $this->getFortressBackpackSize() * 12; $i++){
				$ret[] = 0;
		}

		foreach($this->fortressBackpack as $item){
			$save = $item->getSave();
			$slot = $item->slot * 12;
			for($i = 0; $i < 12; $i++)
				$ret[$slot + $i] = $save[$i];
		}
		
		return join("/", $ret);
	}

	public function hasTower(){
		return $this->data['tower'] >= 0;
	}

	public function hasGuild(){
		return $this->data['guild'] > 0;
	}

	private function getAlbumMultiplier(){
		if($this->data['album'] > 0)
			return 1 + round($this->data['album'] / 2022, 2);
		return 1;
	}

	public static function getGuildBonusNew($guildID, $skill) {
		global $db;
		
		if($guildID <= 0) return 0;

		$qry = $db->query('SELECT sum(skill_instructor) as guild_skill_instructor, sum(skill_treasure) as guild_skill_treasure, guilds.dungeon FROM players LEFT JOIN guilds ON guilds.ID = players.guild WHERE guild = '.$guildID);
		$guild_bonus = $qry->fetch (PDO::FETCH_ASSOC);

		return min(200, round(0.2 * ($guild_bonus[$skill])) + ($guild_bonus['dungeon'] * 2));
	}

	public function toiletFullToday(){
		//if not same day or been more than 24 hours
		if(($GLOBALS["CURRTIME"] / 86400) % 365 != ($this->data['wcdate'] / 86400) % 365 || $GLOBALS["CURRTIME"] - $this->data['wcdate'] > 86400)
			return 0;
		return 1;
	}

	public function toiletFull(){
		return Account::getWcRequiredExp($this->data['wcaura']) > $this->data['wcexp'];
	}

	public static function getWagesPerHour($lvl){
		return 9 + round(pow($lvl, 2.9));
	}
	
	public static function getQuestGold($lvl, $goldBonus) {
		$e =(rand(intval( $lvl * $lvl * ($lvl * 3 + 7) ), intval( $lvl * $lvl * ($lvl * 2 + 7) )) / 9 * 1 + rand(intval( $lvl * 1 + 3 ), intval( $lvl * 1 + 2 ))) * $goldBonus;
		
		while($e < 0)
			$e = self::getQuestGold($lvl, $goldBonus);
		
		return $e;
	}
	
	//returns an array with 3 values: exp, silver, duration
	public static function generateQuest($lvl, $perm = 0, $loo = 0){
		
		$xpbonus = $GLOBALS['xpbonus'];
		$goldbonus = $GLOBALS['goldbonus'];
		$xpGenVersion = $GLOBALS['xpGenVersion'];
		
		$gE = Misc::getEvent();
		
		if($gE[1] == 1) {
			$xpbonus = $GLOBALS['event_xpbonus'];
		}

		if($gE[2] == 1) {
			$goldbonus = $GLOBALS['event_goldbonus'];
		}
		
		//random duration 5, 10, 15, 20 min
		if($loo != 0) {
			// Wheel of Fortune duration
			$duration = $loo == 1 ? 2 : 4;
		}else{
			$duration = rand(1,4);
		}
		
		//TODO: Normal quest time 0.05 = 15 sec, 0.2 = 1 minute
		
		$exp = self::getQuestExp($lvl, $xpbonus, $xpGenVersion, $duration);
		
		//Lvl up system by Zsolt
if ($lvl > 0) {$exp = $exp / 1.3;}
if ($lvl > 1) {$exp = $exp / 1.1;}
if ($lvl > 2) {$exp = $exp / 1.1;}
if ($lvl > 3) {$exp = $exp / 1.07;}
if ($lvl > 4) {$exp = $exp / 1.06;}
if ($lvl > 5) {$exp = $exp / 1.05;}
if ($lvl > 6) {$exp = $exp / 1.04;}
if ($lvl > 7) {$exp = $exp / 1.03;}
if ($lvl > 8) {$exp = $exp / 1.05;}
if ($lvl > 9) {$exp = $exp / 1.02;}
if ($lvl > 10) {$exp = $exp / 1.06;}
if ($lvl > 11) {$exp = $exp / 1.01;}
if ($lvl > 12) {$exp = $exp / 1.01;}
if ($lvl > 13) {$exp = $exp / 1.01;}
if ($lvl > 14) {$exp = $exp / 1.01;}
if ($lvl > 15) {$exp = $exp / 1.01;}
if ($lvl > 16) {$exp = $exp / 1.02;}
if ($lvl > 17) {$exp = $exp / 1.01;}
if ($lvl > 18) {$exp = $exp / 1.01;}
if ($lvl > 19) {$exp = $exp / 1.01;}
if ($lvl > 20) {$exp = $exp / 1.01;}
if ($lvl > 21) {$exp = $exp / 1.02;}
if ($lvl > 22) {$exp = $exp / 1.02;}
if ($lvl > 23) {$exp = $exp / 1.01;}
if ($lvl > 24) {$exp = $exp / 1.01;}
if ($lvl > 25) {$exp = $exp / 1.01;}
if ($lvl > 26) {$exp = $exp / 1.01;}
if ($lvl > 27) {$exp = $exp / 1.01;}
if ($lvl > 28) {$exp = $exp / 1.01;}
if ($lvl > 29) {$exp = $exp / 1.01;}
if ($lvl > 30) {$exp = $exp / 1.01;}
if ($lvl > 31) {$exp = $exp / 1.01;}
if ($lvl > 32) {$exp = $exp / 1.01;}
if ($lvl > 33) {$exp = $exp / 1.02;}
if ($lvl > 34) {$exp = $exp / 1.01;}
if ($lvl > 35) {$exp = $exp / 1.01;}
if ($lvl > 36) {$exp = $exp / 1.01;}
if ($lvl > 37) {$exp = $exp / 1.01;}
if ($lvl > 38) {$exp = $exp / 1.01;}
if ($lvl > 39) {$exp = $exp / 1.01;}
if ($lvl > 40) {$exp = $exp / 1.02;}
if ($lvl > 41) {$exp = $exp / 1.01;}
if ($lvl > 42) {$exp = $exp / 1.01;}
if ($lvl > 43) {$exp = $exp / 1.01;}
if ($lvl > 44) {$exp = $exp / 1.01;}
if ($lvl > 45) {$exp = $exp / 1.01;}
if ($lvl > 46) {$exp = $exp / 1.01;}
if ($lvl > 47) {$exp = $exp / 1.01;}
if ($lvl > 48) {$exp = $exp / 1.01;}
if ($lvl > 49) {$exp = $exp / 1.01;}
if ($lvl > 50) {$exp = $exp / 1.01;}
if ($lvl > 51) {$exp = $exp / 1.01;}
if ($lvl > 52) {$exp = $exp / 1.02;}
if ($lvl > 53) {$exp = $exp / 1.01;}
if ($lvl > 54) {$exp = $exp / 1.01;}
if ($lvl > 55) {$exp = $exp / 1.01;}
if ($lvl > 56) {$exp = $exp / 1.01;}
if ($lvl > 57) {$exp = $exp / 1.01;}
if ($lvl > 58) {$exp = $exp / 1.01;}
if ($lvl > 59) {$exp = $exp / 1.01;}
if ($lvl > 60) {$exp = $exp / 1.01;}
if ($lvl > 61) {$exp = $exp / 1.01;}
if ($lvl > 62) {$exp = $exp / 1.01;}
if ($lvl > 63) {$exp = $exp / 1.01;}
if ($lvl > 64) {$exp = $exp / 1.01;}
if ($lvl > 65) {$exp = $exp / 1.001;}
if ($lvl > 66) {$exp = $exp / 1.01;}
if ($lvl > 67) {$exp = $exp / 1.01;}
if ($lvl > 68) {$exp = $exp / 1.01;}
if ($lvl > 69) {$exp = $exp / 1.01;}
if ($lvl > 70) {$exp = $exp / 1.01;}
if ($lvl > 71) {$exp = $exp / 1.01;}
if ($lvl > 72) {$exp = $exp / 1.01;}
if ($lvl > 73) {$exp = $exp / 1.01;}
if ($lvl > 74) {$exp = $exp / 1.01;}
if ($lvl > 75) {$exp = $exp / 1.01;}
if ($lvl > 76) {$exp = $exp / 1.01;}
if ($lvl > 77) {$exp = $exp / 1.01;}
if ($lvl > 78) {$exp = $exp / 1.01;}
if ($lvl > 79) {$exp = $exp / 1.02;}
if ($lvl > 80) {$exp = $exp / 1.01;}
if ($lvl > 81) {$exp = $exp / 1.01;}
if ($lvl > 82) {$exp = $exp / 1.01;}
if ($lvl > 83) {$exp = $exp / 1.01;}
if ($lvl > 84) {$exp = $exp / 1.01;}
if ($lvl > 85) {$exp = $exp / 1.01;}
if ($lvl > 86) {$exp = $exp / 1.01;}
if ($lvl > 87) {$exp = $exp / 1.01;}
if ($lvl > 88) {$exp = $exp / 1.01;}
if ($lvl > 89) {$exp = $exp / 1.01;}
if ($lvl > 90) {$exp = $exp / 1.01;}
if ($lvl > 91) {$exp = $exp / 1.01;}
if ($lvl > 92) {$exp = $exp / 1.001;}
if ($lvl > 93) {$exp = $exp / 1.01;}
if ($lvl > 94) {$exp = $exp / 1.01;}
if ($lvl > 95) {$exp = $exp / 1.01;}
if ($lvl > 96) {$exp = $exp / 1.01;}
if ($lvl > 97) {$exp = $exp / 1.015;}
if ($lvl > 98) {$exp = $exp / 1.01;}
if ($lvl > 99) {$exp = $exp / 1.01;}
if ($lvl > 100) {$exp = $exp / 1.0065;}
if ($lvl > 101) {$exp = $exp / 1.0065;}
if ($lvl > 102) {$exp = $exp / 1.0065;}
if ($lvl > 103) {$exp = $exp / 1.0065;}
if ($lvl > 104) {$exp = $exp / 1.0065;}
if ($lvl > 105) {$exp = $exp / 1.0065;}
if ($lvl > 106) {$exp = $exp / 1.0065;}
if ($lvl > 107) {$exp = $exp / 1.0065;}
if ($lvl > 108) {$exp = $exp / 1.0065;}
if ($lvl > 109) {$exp = $exp / 1.0065;}
if ($lvl > 110) {$exp = $exp / 1.0065;}
if ($lvl > 111) {$exp = $exp / 1.0065;}
if ($lvl > 112) {$exp = $exp / 1.0065;}
if ($lvl > 113) {$exp = $exp / 1.0065;}
if ($lvl > 114) {$exp = $exp / 1.0065;}
if ($lvl > 115) {$exp = $exp / 1.0065;}
if ($lvl > 116) {$exp = $exp / 1.0065;}
if ($lvl > 117) {$exp = $exp / 1.0065;}
if ($lvl > 118) {$exp = $exp / 1.0065;}
if ($lvl > 119) {$exp = $exp / 1.0065;}
if ($lvl > 120) {$exp = $exp / 1.0065;}
if ($lvl > 121) {$exp = $exp / 1.0065;}
if ($lvl > 122) {$exp = $exp / 1.0065;}
if ($lvl > 123) {$exp = $exp / 1.0065;}
if ($lvl > 124) {$exp = $exp / 1.0065;}
if ($lvl > 125) {$exp = $exp / 1.0065;}
if ($lvl > 126) {$exp = $exp / 1.0065;}
if ($lvl > 127) {$exp = $exp / 1.0065;}
if ($lvl > 128) {$exp = $exp / 1.0065;}
if ($lvl > 129) {$exp = $exp / 1.0065;}
if ($lvl > 130) {$exp = $exp / 1.0065;}
if ($lvl > 131) {$exp = $exp / 1.0065;}
if ($lvl > 132) {$exp = $exp / 1.0065;}
if ($lvl > 133) {$exp = $exp / 1.0065;}
if ($lvl > 134) {$exp = $exp / 1.0065;}
if ($lvl > 135) {$exp = $exp / 1.0065;}
if ($lvl > 136) {$exp = $exp / 1.0065;}
if ($lvl > 137) {$exp = $exp / 1.0065;}
if ($lvl > 138) {$exp = $exp / 1.0065;}
if ($lvl > 139) {$exp = $exp / 1.0065;}
if ($lvl > 140) {$exp = $exp / 1.0065;}
if ($lvl > 141) {$exp = $exp / 1.0065;}
if ($lvl > 142) {$exp = $exp / 1.0065;}
if ($lvl > 143) {$exp = $exp / 1.0065;}
if ($lvl > 144) {$exp = $exp / 1.0065;}
if ($lvl > 145) {$exp = $exp / 1.0065;}
if ($lvl > 146) {$exp = $exp / 1.0065;}
if ($lvl > 147) {$exp = $exp / 1.0065;}
if ($lvl > 148) {$exp = $exp / 1.0065;}
if ($lvl > 149) {$exp = $exp / 1.0005;}
if ($lvl > 150) {$exp = $exp / 1.0005;}
if ($lvl > 151) {$exp = $exp / 1.0005;}
if ($lvl > 152) {$exp = $exp / 1.0005;}
if ($lvl > 153) {$exp = $exp / 1.0005;}
if ($lvl > 154) {$exp = $exp / 1.0005;}
if ($lvl > 155) {$exp = $exp / 1.0005;}
if ($lvl > 156) {$exp = $exp / 1.0005;}
if ($lvl > 157) {$exp = $exp / 1.0005;}
if ($lvl > 158) {$exp = $exp / 1.0005;}
if ($lvl > 159) {$exp = $exp / 1.0005;}
if ($lvl > 160) {$exp = $exp / 1.0005;}
if ($lvl > 161) {$exp = $exp / 1.0005;}
if ($lvl > 162) {$exp = $exp / 1.0005;}
if ($lvl > 163) {$exp = $exp / 1.0005;}
if ($lvl > 164) {$exp = $exp / 1.0005;}
if ($lvl > 165) {$exp = $exp / 1.0005;}
if ($lvl > 166) {$exp = $exp / 1.0005;}
if ($lvl > 167) {$exp = $exp / 1.0005;}
if ($lvl > 168) {$exp = $exp / 1.0005;}
if ($lvl > 169) {$exp = $exp / 1.0005;}
if ($lvl > 170) {$exp = $exp / 1.0005;}
if ($lvl > 171) {$exp = $exp / 1.0005;}
if ($lvl > 172) {$exp = $exp / 1.0005;}
if ($lvl > 173) {$exp = $exp / 1.0005;}
if ($lvl > 174) {$exp = $exp / 1.0005;}
if ($lvl > 175) {$exp = $exp / 1.0006;}
if ($lvl > 176) {$exp = $exp / 1.0006;}
if ($lvl > 177) {$exp = $exp / 1.0006;}
if ($lvl > 178) {$exp = $exp / 1.0006;}
if ($lvl > 179) {$exp = $exp / 1.0006;}
if ($lvl > 180) {$exp = $exp / 1.0006;}
if ($lvl > 181) {$exp = $exp / 1.0006;}
if ($lvl > 182) {$exp = $exp / 1.0006;}
if ($lvl > 183) {$exp = $exp / 1.0007;}
if ($lvl > 184) {$exp = $exp / 1.0006;}
if ($lvl > 185) {$exp = $exp / 1.0006;}
if ($lvl > 186) {$exp = $exp / 1.0006;}
if ($lvl > 187) {$exp = $exp / 1.0006;}
if ($lvl > 188) {$exp = $exp / 1.0006;}
if ($lvl > 189) {$exp = $exp / 1.0006;}
if ($lvl > 190) {$exp = $exp / 1.0006;}
if ($lvl > 191) {$exp = $exp / 1.0006;}
if ($lvl > 192) {$exp = $exp / 1.0006;}
if ($lvl > 193) {$exp = $exp / 1.0006;}
if ($lvl > 194) {$exp = $exp / 1.0006;}
if ($lvl > 195) {$exp = $exp / 1.0006;}
if ($lvl > 196) {$exp = $exp / 1.0006;}
if ($lvl > 197) {$exp = $exp / 1.0006;}
if ($lvl > 198) {$exp = $exp / 1.0006;}
if ($lvl > 199) {$exp = $exp / 1.0006;}
if ($lvl > 200) {$exp = $exp / 1.0065;}
if ($lvl > 201) {$exp = $exp / 1.0065;}
if ($lvl > 202) {$exp = $exp / 1.0065;}
if ($lvl > 203) {$exp = $exp / 1.0065;}
if ($lvl > 204) {$exp = $exp / 1.0065;}
if ($lvl > 205) {$exp = $exp / 1.0065;}
if ($lvl > 206) {$exp = $exp / 1.0065;}
if ($lvl > 207) {$exp = $exp / 1.0065;}
if ($lvl > 208) {$exp = $exp / 1.0065;}
if ($lvl > 209) {$exp = $exp / 1.0065;}
if ($lvl > 210) {$exp = $exp / 1.0065;}
if ($lvl > 211) {$exp = $exp / 1.0065;}
if ($lvl > 212) {$exp = $exp / 1.0065;}
if ($lvl > 213) {$exp = $exp / 1.0005;}
if ($lvl > 214) {$exp = $exp / 1.0065;}
if ($lvl > 215) {$exp = $exp / 1.0065;}
if ($lvl > 216) {$exp = $exp / 1.0065;}
if ($lvl > 217) {$exp = $exp / 1.0065;}
if ($lvl > 218) {$exp = $exp / 1.0065;}
if ($lvl > 219) {$exp = $exp / 1.0065;}
if ($lvl > 220) {$exp = $exp / 1.0065;}
if ($lvl > 221) {$exp = $exp / 1.0065;}
if ($lvl > 222) {$exp = $exp / 1.0065;}
if ($lvl > 223) {$exp = $exp / 1.0065;}
if ($lvl > 224) {$exp = $exp / 1.0065;}
if ($lvl > 225) {$exp = $exp / 1.0065;}
if ($lvl > 226) {$exp = $exp / 1.0065;}
if ($lvl > 227) {$exp = $exp / 1.0065;}
if ($lvl > 228) {$exp = $exp / 1.0065;}
if ($lvl > 229) {$exp = $exp / 1.0065;}
if ($lvl > 230) {$exp = $exp / 1.0065;}
if ($lvl > 231) {$exp = $exp / 1.0065;}
if ($lvl > 232) {$exp = $exp / 1.0065;}
if ($lvl > 233) {$exp = $exp / 1.0065;}
if ($lvl > 234) {$exp = $exp / 1.0065;}
if ($lvl > 235) {$exp = $exp / 1.0065;}
if ($lvl > 236) {$exp = $exp / 1.0065;}
if ($lvl > 237) {$exp = $exp / 1.0065;}
if ($lvl > 238) {$exp = $exp / 1.0065;}
if ($lvl > 239) {$exp = $exp / 1.0065;}
if ($lvl > 240) {$exp = $exp / 1.0065;}
if ($lvl > 241) {$exp = $exp / 1.0065;}
if ($lvl > 242) {$exp = $exp / 1.0065;}
if ($lvl > 243) {$exp = $exp / 1.0065;}
if ($lvl > 244) {$exp = $exp / 1.0065;}
if ($lvl > 245) {$exp = $exp / 1.0065;}
if ($lvl > 246) {$exp = $exp / 1.0065;}
if ($lvl > 247) {$exp = $exp / 1.0065;}
if ($lvl > 248) {$exp = $exp / 1.0065;}
if ($lvl > 249) {$exp = $exp / 1.0072;}
if ($lvl > 250) {$exp = $exp / 1.0072;}
if ($lvl > 251) {$exp = $exp / 1.0072;}
if ($lvl > 252) {$exp = $exp / 1.0072;}
if ($lvl > 253) {$exp = $exp / 1.0072;}
if ($lvl > 254) {$exp = $exp / 1.0072;}
if ($lvl > 255) {$exp = $exp / 1.0072;}
if ($lvl > 256) {$exp = $exp / 1.0072;}
if ($lvl > 257) {$exp = $exp / 1.0072;}
if ($lvl > 258) {$exp = $exp / 1.0072;}
if ($lvl > 259) {$exp = $exp / 1.0072;}
if ($lvl > 260) {$exp = $exp / 1.0072;}
if ($lvl > 261) {$exp = $exp / 1.0072;}
if ($lvl > 262) {$exp = $exp / 1.0072;}
if ($lvl > 263) {$exp = $exp / 1.0072;}
if ($lvl > 264) {$exp = $exp / 1.0072;}
if ($lvl > 265) {$exp = $exp / 1.0072;}
if ($lvl > 266) {$exp = $exp / 1.0072;}
if ($lvl > 267) {$exp = $exp / 1.0072;}
if ($lvl > 268) {$exp = $exp / 1.0072;}
if ($lvl > 269) {$exp = $exp / 1.0072;}
if ($lvl > 270) {$exp = $exp / 1.0072;}
if ($lvl > 271) {$exp = $exp / 1.0072;}
if ($lvl > 272) {$exp = $exp / 1.0072;}
if ($lvl > 273) {$exp = $exp / 1.0072;}
if ($lvl > 274) {$exp = $exp / 1.0072;}
if ($lvl > 275) {$exp = $exp / 1.0072;}
if ($lvl > 276) {$exp = $exp / 1.0072;}
if ($lvl > 277) {$exp = $exp / 1.0072;}
if ($lvl > 278) {$exp = $exp / 1.0072;}
if ($lvl > 279) {$exp = $exp / 1.0072;}
if ($lvl > 280) {$exp = $exp / 1.0072;}
if ($lvl > 281) {$exp = $exp / 1.0072;}
if ($lvl > 282) {$exp = $exp / 1.0072;}
if ($lvl > 283) {$exp = $exp / 1.0072;}
if ($lvl > 284) {$exp = $exp / 1.0072;}
if ($lvl > 285) {$exp = $exp / 1.0072;}
if ($lvl > 286) {$exp = $exp / 1.0072;}
if ($lvl > 287) {$exp = $exp / 1.0072;}
if ($lvl > 288) {$exp = $exp / 1.0072;}
if ($lvl > 289) {$exp = $exp / 1.0072;}
if ($lvl > 290) {$exp = $exp / 1.0072;}
if ($lvl > 291) {$exp = $exp / 1.0072;}
if ($lvl > 292) {$exp = $exp / 1.0072;}
if ($lvl > 293) {$exp = $exp / 1.0072;}
if ($lvl > 294) {$exp = $exp / 1.0072;}
if ($lvl > 295) {$exp = $exp / 1.0072;}
if ($lvl > 296) {$exp = $exp / 1.0072;}
if ($lvl > 297) {$exp = $exp / 1.0072;}
if ($lvl > 298) {$exp = $exp / 1.0072;}
if ($lvl > 299) {$exp = $exp / 1.0025;}
if ($lvl > 300) {$exp = $exp / 1.004;}
if ($lvl > 301) {$exp = $exp / 1.002;}
if ($lvl > 302) {$exp = $exp / 1.002;}
if ($lvl > 303) {$exp = $exp / 1.002;}
if ($lvl > 304) {$exp = $exp / 1.002;}
if ($lvl > 305) {$exp = $exp / 1.002;}
if ($lvl > 306) {$exp = $exp / 1.002;}
if ($lvl > 307) {$exp = $exp / 1.002;}
if ($lvl > 308) {$exp = $exp / 1.002;}
if ($lvl > 309) {$exp = $exp / 1.002;}
if ($lvl > 310) {$exp = $exp / 1.003;}
if ($lvl > 311) {$exp = $exp / 1.002;}
if ($lvl > 312) {$exp = $exp / 1.002;}
if ($lvl > 313) {$exp = $exp / 1.002;}
if ($lvl > 314) {$exp = $exp / 1.002;}
if ($lvl > 315) {$exp = $exp / 1.002;}
if ($lvl > 316) {$exp = $exp / 1.002;}
if ($lvl > 317) {$exp = $exp / 1.002;}
if ($lvl > 318) {$exp = $exp / 1.002;}
if ($lvl > 319) {$exp = $exp / 1.002;}
if ($lvl > 320) {$exp = $exp / 1.0002;}
if ($lvl > 321) {$exp = $exp / 1.002;}
if ($lvl > 322) {$exp = $exp / 1.002;}
if ($lvl > 323) {$exp = $exp / 1.002;}
if ($lvl > 324) {$exp = $exp / 1.002;}
if ($lvl > 325) {$exp = $exp / 1.002;}
if ($lvl > 326) {$exp = $exp / 1.0002;}
if ($lvl > 327) {$exp = $exp / 1.002;}
if ($lvl > 328) {$exp = $exp / 1.002;}
if ($lvl > 329) {$exp = $exp / 1.004;}
if ($lvl > 330) {$exp = $exp / 1.002;}
if ($lvl > 331) {$exp = $exp / 1.002;}
if ($lvl > 332) {$exp = $exp / 1.002;}
if ($lvl > 333) {$exp = $exp / 1.002;}
if ($lvl > 334) {$exp = $exp / 1.002;}
if ($lvl > 335) {$exp = $exp / 1.0002;}
if ($lvl > 336) {$exp = $exp / 1.002;}
if ($lvl > 337) {$exp = $exp / 1.002;}
if ($lvl > 338) {$exp = $exp / 1.0002;}
if ($lvl > 339) {$exp = $exp / 1.002;}
if ($lvl > 340) {$exp = $exp / 1.001;}
if ($lvl > 341) {$exp = $exp / 1.0002;}
if ($lvl > 342) {$exp = $exp / 1.002;}
if ($lvl > 343) {$exp = $exp / 1.0001;}
if ($lvl > 344) {$exp = $exp / 1.002;}
if ($lvl > 345) {$exp = $exp / 1.0005;}
if ($lvl > 346) {$exp = $exp / 1.001;}
if ($lvl > 347) {$exp = $exp / 1.0002;}
if ($lvl > 348) {$exp = $exp / 1.0002;}
if ($lvl > 349) {$exp = $exp / 1.0001;}
if ($lvl > 350) {$exp = $exp / 1.0001;}
if ($lvl > 351) {$exp = $exp / 1.0001;}
if ($lvl > 352) {$exp = $exp / 1.0001;}
if ($lvl > 353) {$exp = $exp / 1.0001;}
if ($lvl > 354) {$exp = $exp / 1.0001;}
if ($lvl > 355) {$exp = $exp / 1.0001;}
if ($lvl > 356) {$exp = $exp / 1.0001;}
if ($lvl > 357) {$exp = $exp / 1.0001;}
if ($lvl > 358) {$exp = $exp / 1.0001;}
if ($lvl > 359) {$exp = $exp / 1.0001;}
if ($lvl > 360) {$exp = $exp / 1.0001;}
if ($lvl > 361) {$exp = $exp / 1.0001;}
if ($lvl > 362) {$exp = $exp / 1.0001;}
if ($lvl > 363) {$exp = $exp / 1.0001;}
if ($lvl > 364) {$exp = $exp / 1.0001;}
if ($lvl > 365) {$exp = $exp / 1.0001;}
if ($lvl > 366) {$exp = $exp / 1.0001;}
if ($lvl > 367) {$exp = $exp / 1.0001;}
if ($lvl > 368) {$exp = $exp / 1.0001;}
if ($lvl > 369) {$exp = $exp / 1.0001;}
if ($lvl > 370) {$exp = $exp / 1.0001;}
if ($lvl > 371) {$exp = $exp / 1.0001;}
if ($lvl > 372) {$exp = $exp / 1.0001;}
if ($lvl > 373) {$exp = $exp / 1.0001;}
if ($lvl > 374) {$exp = $exp / 1.0001;}
if ($lvl > 375) {$exp = $exp / 1.0001;}
if ($lvl > 376) {$exp = $exp / 1.0001;}
if ($lvl > 377) {$exp = $exp / 1.0001;}
if ($lvl > 378) {$exp = $exp / 1.0001;}
if ($lvl > 379) {$exp = $exp / 1.0001;}
if ($lvl > 380) {$exp = $exp / 1.0001;}
if ($lvl > 381) {$exp = $exp / 1.0001;}
if ($lvl > 382) {$exp = $exp / 1.0001;}
if ($lvl > 383) {$exp = $exp / 1.0001;}
if ($lvl > 384) {$exp = $exp / 1.0001;}
if ($lvl > 385) {$exp = $exp / 1.0001;}
if ($lvl > 386) {$exp = $exp / 1.0001;}
if ($lvl > 387) {$exp = $exp / 1.0001;}
if ($lvl > 388) {$exp = $exp / 1.0001;}
if ($lvl > 389) {$exp = $exp / 1.0001;}
if ($lvl > 390) {$exp = $exp / 1.0001;}
if ($lvl > 391) {$exp = $exp / 1.0001;}
if ($lvl > 392) {$exp = $exp / 1.0001;}
if ($lvl > 393) {$exp = $exp / 1.0001;}
if ($lvl > 394) {$exp = $exp / 1.0001;}
if ($lvl > 395) {$exp = $exp / 1.0001;}
if ($lvl > 396) {$exp = $exp / 1.0001;}
if ($lvl > 397) {$exp = $exp / 1.0001;}
if ($lvl > 398) {$exp = $exp / 1.0001;}
if ($lvl > 399) {$exp = $exp / 1.0001;}
		//Max level by Greg
		if($lvl >= $GLOBALS["levelLimit"]) {
			$exp = 0;
		}
		
		$silver = self::getQuestGold($lvl, $goldbonus) * $duration;
		
		/*if($perm == '13' && $lvl < 750) {
			$exp *= 8;
		}*/
		
		//By Jack
		//No travel (0:00 duration) setting
		if(isset($GLOBALS['skipAdventureTravel']) && $GLOBALS['skipAdventureTravel'] == true) {
			$duration = 0;
		}	
		
		return ['exp' => $exp, 'silver' => $silver, 'duration' => $duration * 300];
	}
	
	public static function getQuestExp($lvl, $xpbonus, $ver, $duration) {
		switch($ver) {
			case 1 :
				// V1: Easy servers, more xp
				
				$exp = floor(Player::getExp($lvl - 1) / (2 + ($lvl * 0.05)) * (0.85 + mt_rand() / mt_getrandmax() * 0.3)) * $duration;
				
				$exp = $exp * $xpbonus;
				
				return $exp;
			case 2 :
				// V2: Real xp, for hard and medium servers
				$gexp = $lvl * $lvl / 5 * 16;
				
				$m = mt_rand(900, 1100) / 1000; // Multiplier
				
				$exp = rand($lvl * 7 + 5, $lvl * 35 + 300) + $gexp * $m;
				
				$exp = $exp * $xpbonus;
				
				return $exp;
			default:
				return 0;
		}
	}
	
	public static function getStatPrice($stat){
		if($stat > 3151)
			return 1000000000;
		$stats = [25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 75, 80, 85, 90, 95, 95, 100, 105, 110, 115, 120, 125, 135, 140, 150, 155, 160, 170, 175, 185, 190, 200, 205, 215, 225, 230, 240, 250, 260, 270, 280, 290, 300, 310, 325, 335, 345, 355, 365, 380, 390, 405, 420, 435, 450, 460, 475, 490, 505, 520, 535, 550, 565, 580, 600, 615, 630, 650, 665, 685, 705, 725, 745, 765, 785, 805, 825, 845, 865, 885, 910, 935, 960, 985, 1010, 1035, 1060, 1085, 1110, 1135, 1160, 1190, 1220, 1250, 1280, 1310, 1340, 1370, 1400, 1430, 1460, 1495, 1530, 1565, 1600, 1630, 1665, 1700, 1735, 1770, 1810, 1850, 1890, 1930, 1975, 2015, 2055, 2095, 2135, 2180, 2225, 2270, 2315, 2360, 2405, 2450, 2500, 2545, 2595, 2645, 2695, 2750, 2800, 2855, 2910, 2960, 3015, 3065, 3120, 3175, 3235, 3295, 3355, 3415, 3480, 3540, 3600, 3660, 3720, 3785, 3850, 3920, 3985, 4055, 4125, 4195, 4265, 4335, 4405, 4480, 4555, 4635, 4710, 4790, 4870, 4945, 5025, 5100, 5180, 5260, 5345, 5435, 5525, 5615, 5705, 5790, 5880, 5970, 6060, 6150, 6245, 6345, 6440, 6540, 6640, 6740, 6840, 6940, 7040, 7145, 7255, 7365, 7475, 7585, 7700, 7810, 7920, 8030, 8140, 8255, 8380, 8505, 8630, 8755, 8880, 9005, 9130, 9255, 9380, 9505, 9640, 9775, 9910, 10045, 10185, 10325, 10465, 10605, 10745, 10885, 11035, 11185, 11340, 11490, 11645, 11795, 11945, 12100, 12250, 12405, 12575, 12745, 12915, 13085, 13255, 13425, 13595, 13765, 13935, 14105, 14285, 14470, 14655, 14840, 15025, 15210, 15400, 15590, 15780, 15970, 16170, 16375, 16580, 16785, 16990, 17190, 17395, 17600, 17805, 18010, 18235, 18460, 18685, 18910, 19140, 19365, 19590, 19815, 20040, 20270, 20510, 20755, 21000, 21245, 21490, 21740, 21990, 22240, 22490, 22740, 23005, 23275, 23540, 23810, 24080, 24345, 24615, 24880, 25150, 25420, 25715, 26010, 26305, 26600, 26900, 27195, 27490, 27785, 28080, 28380, 28695, 29010, 29325, 29640, 29960, 30280, 30605, 30925, 31250, 31575, 31920, 32265, 32610, 32955, 33305, 33650, 33995, 34340, 34685, 35035, 35410, 35790, 36165, 36545, 36925, 37300, 37680, 38055, 38435, 38815, 39215, 39620, 40025, 40430, 40835, 41245, 41660, 42070, 42485, 42900, 43335, 43775, 44215, 44655, 45095, 45530, 45970, 46410, 46850, 47290, 47765, 48245, 48720, 49200, 49680, 50155, 50635, 51110, 51590, 52070, 52575, 53085, 53590, 54100, 54610, 55125, 55645, 56165, 56685, 57205, 57755, 58305, 58855, 59405, 59960, 60510, 61060, 61610, 62160, 62715, 63310, 63905, 64500, 65095, 65695, 66290, 66885, 67480, 68075, 68675, 69305, 69940, 70570, 71205, 71840, 72485, 73130, 73775, 74420, 75065, 75745, 76425, 77110, 77790, 78475, 79155, 79835, 80520, 81200, 81885, 82620, 83355, 84095, 84830, 85570, 86305, 87040, 87780, 88515, 89255, 90030, 90810, 91590, 92370, 93150, 93940, 94730, 95525, 96315, 97110, 97945, 98780, 99620, 100455, 101295, 102130, 102965, 103805, 104640, 105480, 106375, 107275, 108175, 109075, 109975, 110870, 111770, 112670, 113570, 114470, 115420, 116370, 117320, 118270, 119220, 120185, 121150, 122115, 123080, 124045, 125060, 126075, 127090, 128105, 129125, 130140, 131155, 132170, 133185, 134205, 135290, 136380, 137470, 138560, 139650, 140735, 141825, 142915, 144005, 145095, 146240, 147385, 148530, 149675, 150825, 151985, 153150, 154315, 155480, 156645, 157870, 159095, 160320, 161545, 162770, 163995, 165220, 166445, 167670, 168895, 170200, 171510, 172815, 174125, 175435, 176740, 178050, 179355, 180665, 181975, 183350, 184725, 186100, 187475, 188855, 190250, 191645, 193040, 194435, 195830, 197290, 198755, 200215, 201680, 203145, 204605, 206070, 207530, 208995, 210460, 212020, 213580, 215140, 216700, 218265, 219825, 221385, 222945, 224505, 226070, 227705, 229340, 230975, 232610, 234250, 235905, 237565, 239220, 240880, 242540, 244280, 246020, 247760, 249500, 251240, 252980, 254720, 256460, 258200, 259940, 261785, 263635, 265480, 267330, 269180, 271025, 272875, 274720, 276570, 278420, 280355, 282290, 284230, 286165, 288105, 290065, 292025, 293990, 295950, 297915, 299965, 302015, 304065, 306115, 308170, 310220, 312270, 314320, 316370, 318425, 320600, 322780, 324955, 327135, 329315, 331490, 333670, 335845, 338025, 340205, 342480, 344755, 347035, 349310, 351590, 353890, 356195, 358500, 360805, 363110, 365515, 367925, 370335, 372745, 375155, 377560, 379970, 382380, 384790, 387200, 389745, 392295, 394845, 397395, 399945, 402490, 405040, 407590, 410140, 412690, 415350, 418015, 420680, 423345, 426010, 428705, 431400, 434095, 436790, 439485, 442295, 445105, 447915, 450725, 453535, 456345, 459155, 461965, 464775, 467585, 470555, 473525, 476495, 479465, 482435, 485405, 488375, 491345, 494315, 497285, 500380, 503475, 506570, 509665, 512765, 515895, 519025, 522155, 525285, 528420, 531685, 534950, 538215, 541480, 544750, 548015, 551280, 554545, 557810, 561080, 564520, 567960, 571405, 574845, 578290, 581730, 585170, 588615, 592055, 595500, 599085, 602675, 606260, 609850, 613440, 617065, 620690, 624315, 627940, 631570, 635340, 639110, 642885, 646655, 650430, 654200, 657970, 661745, 665515, 669290, 673260, 677235, 681210, 685185, 689160, 693130, 697105, 701080, 705055, 709030, 713160, 717295, 721425, 725560, 729695, 733870, 738045, 742220, 746395, 750575, 754920, 759265, 763610, 767955, 772300, 776645, 780990, 785335, 789680, 794025, 798585, 803150, 807715, 812280, 816845, 821405, 825970, 830535, 835100, 839665, 844410, 849160, 853905, 858655, 863405, 868200, 872995, 877790, 882585, 887380, 892355, 897335, 902315, 907295, 912275, 917250, 922230, 927210, 932190, 937170, 942395, 947625, 952855, 958085, 963315, 968540, 973770, 979000, 984230, 989460, 994885, 1000310, 1005740, 1011165, 1016595, 1022070, 1027550, 1033025, 1038505, 1043985, 1049670, 1055360, 1061050, 1066740, 1072430, 1078115, 1083805, 1089495, 1095185, 1100875, 1106835, 1112795, 1118755, 1124715, 1130680, 1136640, 1142600, 1148560, 1154520, 1160485, 1166670, 1172860, 1179045, 1185235, 1191425, 1197670, 1203915, 1210160, 1216405, 1222650, 1229120, 1235590, 1242065, 1248535, 1255010, 1261480, 1267950, 1274425, 1280895, 1287370, 1294145, 1300925, 1307705, 1314485, 1321265, 1328040, 1334820, 1341600, 1348380, 1355160, 1362180, 1369205, 1376230, 1383255, 1390280, 1397365, 1404450, 1411540, 1418625, 1425715, 1433060, 1440405, 1447755, 1455100, 1462450, 1469795, 1477140, 1484490, 1491835, 1499185, 1506860, 1514540, 1522215, 1529895, 1537575, 1545250, 1552930, 1560605, 1568285, 1575965, 1583920, 1591875, 1599835, 1607790, 1615750, 1623770, 1631795, 1639820, 1647845, 1655870, 1664170, 1672475, 1680775, 1689080, 1697385, 1705685, 1713990, 1722290, 1730595, 1738900, 1747575, 1756250, 1764925, 1773600, 1782280, 1790955, 1799630, 1808305, 1816980, 1825660, 1834635, 1843610, 1852585, 1861560, 1870540, 1879590, 1888640, 1897690, 1906740, 1915790, 1925155, 1934520, 1943890, 1953255, 1962625, 1971990, 1981355, 1990725, 2000090, 2009460, 2019225, 2028990, 2038760, 2048525, 2058295, 2068060, 2077825, 2087595, 2097360, 2107130, 2117235, 2127340, 2137445, 2147550, 2157660, 2167845, 2178030, 2188215, 2198400, 2208590, 2219115, 2229640, 2240165, 2250690, 2261215, 2271740, 2282265, 2292790, 2303315, 2313840, 2324815, 2335790, 2346765, 2357740, 2368715, 2379690, 2390665, 2401640, 2412615, 2423590, 2434925, 2446265, 2457600, 2468940, 2480280, 2491700, 2503125, 2514550, 2525975, 2537400, 2549205, 2561010, 2572815, 2584620, 2596430, 2608235, 2620040, 2631845, 2643650, 2655460, 2667745, 2680035, 2692325, 2704615, 2716905, 2729190, 2741480, 2753770, 2766060, 2778350, 2791045, 2803740, 2816440, 2829135, 2841835, 2854625, 2867415, 2880205, 2892995, 2905790, 2918990, 2932190, 2945390, 2958590, 2971790, 2984990, 2998190, 3011390, 3024590, 3037790, 3051525, 3065260, 3079000, 3092735, 3106475, 3120210, 3133945, 3147685, 3161420, 3175160, 3189330, 3203505, 3217675, 3231850, 3246025, 3260300, 3274575, 3288850, 3303125, 3317405, 3332140, 3346875, 3361610, 3376345, 3391080, 3405815, 3420550, 3435285, 3450020, 3464755, 3480060, 3495370, 3510680, 3525990, 3541300, 3556605, 3571915, 3587225, 3602535, 3617845, 3633640, 3649435, 3665235, 3681030, 3696830, 3712735, 3728640, 3744550, 3760455, 3776365, 3792760, 3809155, 3825550, 3841945, 3858340, 3874735, 3891130, 3907525, 3923920, 3940315, 3957350, 3974385, 3991420, 4008455, 4025490, 4042525, 4059560, 4076595, 4093630, 4110665, 4128220, 4145775, 4163330, 4180885, 4198440, 4216110, 4233785, 4251460, 4269135, 4286810, 4305030, 4323250, 4341470, 4359690, 4377910, 4396130, 4414350, 4432570, 4450790, 4469010, 4487905, 4506805, 4525705, 4544605, 4563505, 4582400, 4601300, 4620200, 4639100, 4658000, 4677475, 4696955, 4716435, 4735915, 4755395, 4775000, 4794610, 4814220, 4833830, 4853440, 4873625, 4893815, 4914005, 4934195, 4954385, 4974570, 4994760, 5014950, 5035140, 5055330, 5076270, 5097215, 5118155, 5139100, 5160045, 5180985, 5201930, 5222870, 5243815, 5264760, 5286315, 5307875, 5329435, 5350995, 5372555, 5394250, 5415945, 5437640, 5459335, 5481035, 5503375, 5525715, 5548060, 5570400, 5592745, 5615085, 5637425, 5659770, 5682110, 5704455, 5727600, 5750745, 5773890, 5797035, 5820180, 5843325, 5866470, 5889615, 5912760, 5935905, 5959730, 5983560, 6007390, 6031220, 6055050, 6079025, 6103005, 6126980, 6150960, 6174940, 6199600, 6224260, 6248920, 6273580, 6298245, 6322905, 6347565, 6372225, 6396885, 6421550, 6447095, 6472640, 6498185, 6523730, 6549280, 6574825, 6600370, 6625915, 6651460, 6677010, 6703280, 6729550, 6755825, 6782095, 6808370, 6834800, 6861235, 6887665, 6914100, 6940535, 6967725, 6994915, 7022110, 7049300, 7076495, 7103685, 7130875, 7158070, 7185260, 7212455, 7240585, 7268715, 7296845, 7324975, 7353105, 7381235, 7409365, 7437495, 7465625, 7493755, 7522685, 7551620, 7580550, 7609485, 7638420, 7667520, 7696625, 7725730, 7754835, 7783940, 7813845, 7843750, 7873660, 7903565, 7933475, 7963380, 7993285, 8023195, 8053100, 8083010, 8113950, 8144890, 8175830, 8206770, 8237715, 8268655, 8299595, 8330535, 8361475, 8392420, 8424210, 8456000, 8487790, 8519580, 8551375, 8583350, 8615325, 8647300, 8679275, 8711250, 8744110, 8776975, 8809835, 8842700, 8875565, 8908425, 8941290, 8974150, 9007015, 9039880, 9073835, 9107790, 9141745, 9175700, 9209660, 9243615, 9277570, 9311525, 9345480, 9379440, 9414330, 9449225, 9484115, 9519010, 9553905, 9588995, 9624085, 9659175, 9694265, 9729360, 9765385, 9801410, 9837440, 9873465, 9909495, 9945520, 9981545, 10017575, 10053600, 10089630, 10126855, 10164080, 10201305, 10238530, 10275760, 10312985, 10350210, 10387435, 10424660, 10461890, 10500105, 10538320, 10576535, 10614750, 10652970, 10691395, 10729820, 10768245, 10806670, 10845100, 10884555, 10924015, 10963470, 11002930, 11042390, 11081845, 11121305, 11160760, 11200220, 11239680, 11280405, 11321130, 11361855, 11402580, 11443310, 11484035, 11524760, 11565485, 11606210, 11646940, 11688755, 11730570, 11772385, 11814200, 11856015, 11898055, 11940095, 11982135, 12024175, 12066215, 12109340, 12152470, 12195595, 12238725, 12281855, 12324980, 12368110, 12411235, 12454365, 12497495, 12542005, 12586520, 12631030, 12675545, 12720060, 12764570, 12809085, 12853595, 12898110, 12942625, 12988285, 13033945, 13079605, 13125265, 13170930, 13216830, 13262730, 13308635, 13354535, 13400440, 13447535, 13494635, 13541730, 13588830, 13635930, 13683025, 13730125, 13777220, 13824320, 13871420, 13919980, 13968540, 14017100, 14065660, 14114220, 14162780, 14211340, 14259900, 14308460, 14357020, 14406835, 14456655, 14506470, 14556290, 14606110, 14656180, 14706250, 14756325, 14806395, 14856470, 14907795, 14959125, 15010455, 15061785, 15113115, 15164440, 15215770, 15267100, 15318430, 15369760, 15422685, 15475610, 15528535, 15581460, 15634390, 15687315, 15740240, 15793165, 15846090, 15899020, 15953270, 16007520, 16061770, 16116020, 16170275, 16224795, 16279320, 16333840, 16388365, 16442890, 16498790, 16554690, 16610590, 16666490, 16722390, 16778290, 16834190, 16890090, 16945990, 17001890, 17059465, 17117045, 17174625, 17232205, 17289785, 17347360, 17404940, 17462520, 17520100, 17577680, 17636705, 17695730, 17754760, 17813785, 17872815, 17932130, 17991450, 18050765, 18110085, 18169405, 18230170, 18290935, 18351705, 18412470, 18473240, 18534005, 18594770, 18655540, 18716305, 18777075, 18839665, 18902260, 18964855, 19027450, 19090045, 19152635, 19215230, 19277825, 19340420, 19403015, 19467130, 19531245, 19595360, 19659475, 19723595, 19788015, 19852435, 19916860, 19981280, 20045705, 20111705, 20177710, 20243715, 20309720, 20375725, 20441725, 20507730, 20573735, 20639740, 20705745, 20773675, 20841605, 20909535, 20977465, 21045395, 21113325, 21181255, 21249185, 21317115, 21385045, 21454630, 21524220, 21593810, 21663400, 21732990, 21802905, 21872820, 21942735, 22012650, 22082570, 22154145, 22225720, 22297295, 22368870, 22440450, 22512025, 22583600, 22655175, 22726750, 22798330, 22871990, 22945655, 23019315, 23092980, 23166645, 23240305, 23313970, 23387630, 23461295, 23534960, 23610365, 23685770, 23761175, 23836580, 23911990, 23987745, 24063500, 24139255, 24215010, 24290765, 24368325, 24445890, 24523455, 24601020, 24678585, 24756145, 24833710, 24911275, 24988840, 25066405, 25146160, 25225915, 25305670, 25385425, 25465185, 25544940, 25624695, 25704450, 25784205, 25863965, 25945615, 26027265, 26108920, 26190570, 26272225, 26354240, 26436260, 26518275, 26600295, 26682315, 26766225, 26850140, 26934055, 27017970, 27101885, 27185795, 27269710, 27353625, 27437540, 27521455, 27607745, 27694035, 27780325, 27866615, 27952910, 28039200, 28125490, 28211780, 28298070, 28384365, 28472640, 28560920, 28649195, 28737475, 28825755, 28914420, 29003090, 29091760, 29180430, 29269100, 29359830, 29450560, 29541290, 29632020, 29722755, 29813485, 29904215, 29994945, 30085675, 30176410, 30269630, 30362850, 30456075, 30549295, 30642520, 30735740, 30828960, 30922185, 31015405, 31108630, 31204005, 31299385, 31394765, 31490145, 31585525, 31681315, 31777110, 31872905, 31968700, 32064495, 32162445, 32260395, 32358345, 32456295, 32554250, 32652200, 32750150, 32848100, 32946050, 33044005, 33144650, 33245295, 33345940, 33446585, 33547235, 33647880, 33748525, 33849170, 33949815, 34050465, 34153370, 34256275, 34359180, 34462085, 34564995, 34668335, 34771675, 34875015, 34978355, 35081700, 35187380, 35293060, 35398745, 35504425, 35610110, 35715790, 35821470, 35927155, 36032835, 36138520, 36247025, 36355530, 36464035, 36572540, 36681050, 36789555, 36898060, 37006565, 37115070, 37223580, 37334535, 37445490, 37556445, 37667400, 37778355, 37889770, 38001185, 38112605, 38224020, 38335440, 38449305, 38563170, 38677035, 38790900, 38904770, 39018635, 39132500, 39246365, 39360230, 39474100, 39591010, 39707920, 39824830, 39941740, 40058650, 40175560, 40292470, 40409380, 40526290, 40643200, 40762665, 40882135, 41001605, 41121075, 41240545, 41360500, 41480460, 41600420, 41720380, 41840340, 41962950, 42085560, 42208175, 42330785, 42453400, 42576010, 42698620, 42821235, 42943845, 43066460, 43192255, 43318055, 43443855, 43569655, 43695455, 43821250, 43947050, 44072850, 44198650, 44324450, 44453015, 44581580, 44710150, 44838715, 44967285, 45096365, 45225445, 45354525, 45483605, 45612685, 45744530, 45876375, 46008225, 46140070, 46271920, 46403765, 46535610, 46667460, 46799305, 46931155, 47066435, 47201720, 47337005, 47472290, 47607575, 47742855, 47878140, 48013425, 48148710, 48283995, 48422170, 48560345, 48698520, 48836695, 48974875, 49113595, 49252315, 49391035, 49529755, 49668475, 49810185, 49951895, 50093610, 50235320, 50377035, 50518745, 50660455, 50802170, 50943880, 51085595, 51230890, 51376190, 51521485, 51666785, 51812085, 51957380, 52102680, 52247975, 52393275, 52538575, 52686995, 52835415, 52983835, 53132255, 53280680, 53429675, 53578670, 53727670, 53876665, 54025665, 54177780, 54329900, 54482020, 54634140, 54786260, 54938375, 55090495, 55242615, 55394735, 55546855, 55702830, 55858805, 56014785, 56170760, 56326740, 56482715, 56638690, 56794670, 56950645, 57106625, 57265855, 57425085, 57584320, 57743550, 57902785, 58062620, 58222455, 58382290, 58542125, 58701960, 58865160, 59028365, 59191570, 59354775, 59517980, 59681180, 59844385, 60007590, 60170795, 60334000, 60501235, 60668470, 60835705, 61002940, 61170180, 61337415, 61504650, 61671885, 61839120, 62006360, 62177100, 62347845, 62518590, 62689335, 62860080, 63031460, 63202840, 63374225, 63545605, 63716990, 63891880, 64066770, 64241660, 64416550, 64591440, 64766330, 64941220, 65116110, 65291000, 65465890, 65645100, 65824315, 66003530, 66182745, 66361960, 66541170, 66720385, 66899600, 67078815, 67258030, 67440900, 67623775, 67806645, 67989520, 68172395, 68355940, 68539485, 68723030, 68906575, 69090125, 69277450, 69464775, 69652100, 69839425, 70026755, 70214080, 70401405, 70588730, 70776055, 70963385, 71155220, 71347055, 71538890, 71730725, 71922565, 72114400, 72306235, 72498070, 72689905, 72881745, 73077515, 73273285, 73469055, 73664825, 73860600, 74057070, 74253545, 74450020, 74646495, 74842970, 75043375, 75243785, 75444195, 75644605, 75845015, 76045420, 76245830, 76446240, 76646650, 76847060, 77052305, 77257555, 77462800, 77668050, 77873300, 78078545, 78283795, 78489040, 78694290, 78899540, 79108880, 79318225, 79527570, 79736915, 79946260, 80156345, 80366430, 80576520, 80786605, 80996695, 81211010, 81425330, 81639650, 81853970, 82068290, 82282605, 82496925, 82711245, 82925565, 83139885, 83359240, 83578595, 83797955, 84017310, 84236670, 84456025, 84675380, 84894740, 85114095, 85333455, 85557210, 85780965, 86004725, 86228480, 86452240, 86676780, 86901320, 87125860, 87350400, 87574940, 87803880, 88032820, 88261760, 88490700, 88719640, 88948580, 89177520, 89406460, 89635400, 89864340, 90098675, 90333010, 90567345, 90801680, 91036015, 91270350, 91504685, 91739020, 91973355, 92207690, 92446600, 92685515, 92924430, 93163345, 93402260, 93641990, 93881720, 94121450, 94361180, 94600915, 94845370, 95089825, 95334280, 95578735, 95823190, 96067645, 96312100, 96556555, 96801010, 97045465, 97295535, 97545610, 97795685, 98045760, 98295835, 98545905, 98795980, 99046055, 99296130, 99546205, 99801190, 100056175, 100311160, 100566145, 100821135, 101076980, 101332825, 101588675, 101844520, 102100370, 102361125, 102621885, 102882640, 103143400, 103404160, 103664915, 103925675, 104186430, 104447190, 104707950, 104974705, 105241465, 105508220, 105774980, 106041740, 106308495, 106575255, 106842010, 107108770, 107375530, 107647390, 107919250, 108191110, 108462970, 108734835, 109007600, 109280370, 109553135, 109825905, 110098675, 110376705, 110654740, 110932770, 111210805, 111488840, 111766870, 112044905, 112322935, 112600970, 112879005, 113163280, 113447560, 113731840, 114016120, 114300400, 114584675, 114868955, 115153235, 115437515, 115721795, 116011535, 116301280, 116591025, 116880770, 117170515, 117461200, 117751885, 118042575, 118333260, 118623950, 118920100, 119216250, 119512405, 119808555, 120104710, 120400860, 120697010, 120993165, 121289315, 121585470, 121888290, 122191115, 122493940, 122796765, 123099590, 123402410, 123705235, 124008060, 124310885, 124613710, 124922210, 125230710, 125539215, 125847715, 126156220, 126465715, 126775210, 127084705, 127394200, 127703695, 128019040, 128334385, 128649735, 128965080, 129280430, 129595775, 129911120, 130226470, 130541815, 130857165, 131179435, 131501705, 131823975, 132146245, 132468520, 132790790, 133113060, 133435330, 133757600, 134079875, 134408220, 134736565, 135064910, 135393255, 135721600, 136050985, 136380375, 136709765, 137039155, 137368545, 137704005, 138039465, 138374930, 138710390, 139045855, 139381315, 139716775, 140052240, 140387700, 140723165, 141066010, 141408860, 141751705, 142094555, 142437405, 142780250, 143123100, 143465945, 143808795, 144151645, 144500790, 144849940, 145199085, 145548235, 145897385, 146247615, 146597850, 146948085, 147298320, 147648555, 148005280, 148362005, 148718735, 149075460, 149432190, 149788915, 150145640, 150502370, 150859095, 151215825, 151580225, 151944630, 152309035, 152673440, 153037845, 153402245, 153766650, 154131055, 154495460, 154859865, 155230995, 155602130, 155973265, 156344400, 156715535, 157087810, 157460085, 157832360, 158204635, 158576910, 158955915, 159334920, 159713925, 160092930, 160471935, 160850940, 161229945, 161608950, 161987955, 162366960, 162754130, 163141305, 163528475, 163915650, 164302825, 164689995, 165077170, 165464340, 165851515, 166238690, 166632840, 167026995, 167421150, 167815305, 168209460, 168604810, 169000165, 169395515, 169790870, 170186225, 170588760, 170991300, 171393840, 171796380, 172198920, 172601455, 173003995, 173406535, 173809075, 174211615, 174622630, 175033645, 175444660, 175855675, 176266690, 176677705, 177088720, 177499735, 177910750, 178321765, 178740225, 179158690, 179577150, 179995615, 180414080, 180833785, 181253495, 181673200, 182092910, 182512620, 182939775, 183366930, 183794085, 184221240, 184648395, 185075550, 185502705, 185929860, 186357015, 186784170, 187220345, 187656520, 188092700, 188528875, 188965055, 189401230, 189837405, 190273585, 190709760, 191145940, 191589830, 192033720, 192477615, 192921505, 193365400, 193810600, 194255800, 194701000, 195146200, 195591400, 196044540, 196497685, 196950825, 197403970, 197857115, 198310255, 198763400, 199216540, 199669685, 200122830, 200585320, 201047810, 201510305, 201972795, 202435290, 202897780, 203360270, 203822765, 204285255, 204747750, 205218460, 205689170, 206159885, 206630595, 207101310, 207573390, 208045470, 208517555, 208989635, 209461720, 209942020, 210422320, 210902625, 211382925, 211863230, 212343530, 212823830, 213304135, 213784435, 214264740, 214754980, 215245220, 215735460, 216225700, 216715940, 217206180, 217696420, 218186660, 218676900, 219167140, 219665895, 220164650, 220663405, 221162160, 221660915, 222161090, 222661265, 223161445, 223661620, 224161800, 224670730, 225179665, 225688600, 226197535, 226706470, 227215400, 227724335, 228233270, 228742205, 229251140, 229770375, 230289610, 230808845, 231328080, 231847320, 232366555, 232885790, 233405025, 233924260, 234443500, 234971800, 235500100, 236028400, 236556700, 237085000, 237614790, 238144580, 238674370, 239204160, 239733950, 240272800, 240811655, 241350510, 241889365, 242428220, 242967070, 243505925, 244044780, 244583635, 245122490, 245672265, 246222045, 246771820, 247321600, 247871380, 248421155, 248970935, 249520710, 250070490, 250620270, 251179420, 251738575, 252297730, 252856885, 253416040, 253976755, 254537470, 255098185, 255658900, 256219620, 256789980, 257360340, 257930700, 258501060, 259071420, 259641780, 260212140, 260782500, 261352860, 261923220, 262504895, 263086575, 263668255, 264249935, 264831615, 265413290, 265994970, 266576650, 267158330, 267740010, 268331655, 268923305, 269514950, 270106600, 270698250, 271291515, 271884780, 272478045, 273071310, 273664580, 274267815, 274871050, 275474285, 276077520, 276680755, 277283990, 277887225, 278490460, 279093695, 279696930, 280312170, 280927415, 281542660, 282157905, 282773150, 283388390, 284003635, 284618880, 285234125, 285849370, 286474925, 287100480, 287726040, 288351595, 288977155, 289604405, 290231655, 290858910, 291486160, 292113415, 292751260, 293389110, 294026960, 294664810, 295302660, 295940505, 296578355, 297216205, 297854055, 298491905, 299142165, 299792430, 300442695, 301092960, 301743225, 302393485, 303043750, 303694015, 304344280, 304994545, 305655760, 306316980, 306978195, 307639415, 308300635, 308963620, 309626610, 310289600, 310952590, 311615580, 312289520, 312963465, 313637410, 314311355, 314985300, 315659240, 316333185, 317007130, 317681075, 318355020, 319042115, 319729215, 320416315, 321103415, 321790515, 322477610, 323164710, 323851810, 324538910, 325226010, 325924425, 326622845, 327321260, 328019680, 328718100, 329418355, 330118610, 330818865, 331519120, 332219375, 332931260, 333643145, 334355030, 335066915, 335778805, 336490690, 337202575, 337914460, 338626345, 339338235, 340063735, 340789235, 341514740, 342240240, 342965745, 343691245, 344416745, 345142250, 345867750, 346593255, 347330765, 348068275, 348805785, 349543295, 350280810, 351020240, 351759670, 352499100, 353238530, 353977965, 354729405, 355480845, 356232285, 356983725, 357735165, 358486605, 359238045, 359989485, 360740925, 361492365, 362258200, 363024040, 363789875, 364555715, 365321555, 366087390, 366853230, 367619065, 368384905, 369150745, 369928990, 370707235, 371485480, 372263725, 373041975, 373822225, 374602475, 375382730, 376162980, 376943235, 377736225, 378529215, 379322205, 380115195, 380908190, 381701180, 382494170, 383287160, 384080150, 384873145, 385681020, 386488895, 387296770, 388104645, 388912520, 389720395, 390528270, 391336145, 392144020, 392951895, 393772920, 394593950, 395414975, 396236005, 397057035, 397880135, 398703240, 399526345, 400349450, 401172555, 402008810, 402845065, 403681325, 404517580, 405353840, 406190095, 407026350, 407862610, 408698865, 409535125, 410387125, 411239125, 412091125, 412943125, 413795130, 414647130, 415499130, 416351130, 417203130, 418055135, 418920710, 419786285, 420651860, 421517435, 422383015, 423250760, 424118505, 424986250, 425853995, 426721740, 427603420, 428485105, 429366790, 430248475, 431130160, 432011840, 432893525, 433775210, 434656895, 435538580, 436436520, 437334460, 438232400, 439130340, 440028285, 440926225, 441824165, 442722105, 443620045, 444517990, 445430305, 446342620, 447254940, 448167255, 449079575, 449994150, 450908730, 451823310, 452737890, 453652470, 454581425, 455510380, 456439335, 457368290, 458297245, 459226200, 460155155, 461084110, 462013065, 462942020, 463888155, 464834290, 465780425, 466726560, 467672695, 468618830, 469564965, 470511100, 471457235, 472403370, 473364340, 474325310, 475286285, 476247255, 477208230, 478171540, 479134855, 480098170, 481061485, 482024800, 483003335, 483981870, 484960405, 485938940, 486917475, 487896010, 488874545, 489853080, 490831615, 491810150, 492806425, 493802700, 494798980, 495795255, 496791535, 497787810, 498784085, 499780365, 500776640, 501772920, 502784895, 503796870, 504808845, 505820820, 506832800, 507847215, 508861635, 509876050, 510890470, 511904890, 512935005, 513965120, 514995240, 516025355, 517055475, 518085590, 519115705, 520145825, 521175940, 522206060, 523254890, 524303720, 525352550, 526401380, 527450215, 528499045, 529547875, 530596705, 531645535, 532694370, 533759385, 534824400, 535889420, 536954435, 538019455, 539087015, 540154580, 541222145, 542289710, 543357275, 544441445, 545525615, 546609785, 547693955, 548778125, 549862295, 550946465, 552030635, 553114805, 554198975, 555302465, 556405955, 557509445, 558612935, 559716430, 560819920, 561923410, 563026900, 564130390, 565233885, 566354485, 567475085, 568595685, 569716285, 570836890, 571960120, 573083355, 574206585, 575329820, 576453055, 577593395, 578733735, 579874080, 581014420, 582154765, 583295105, 584435445, 585575790, 586716130, 587856475, 589017200, 590177930, 591338655, 592499385, 593660115, 594820840, 595981570, 597142295, 598303025, 599463755, 600642125, 601820495, 602998865, 604177235, 605355605, 606536715, 607717830, 608898945, 610080060, 611261175, 612460370, 613659565, 614858765, 616057960, 617257160, 618456355, 619655550, 620854750, 622053945, 623253145, 624473350, 625693555, 626913760, 628133965, 629354175, 630574380, 631794585, 633014790, 634234995, 635455205, 636694045, 637932885, 639171725, 640410565, 641649405, 642891100, 644132795, 645374490, 646616185, 647857885, 649118210, 650378540, 651638870, 652899200, 654159530, 655419855, 656680185, 657940515, 659200845, 660461175, 661743645, 663026120, 664308590, 665591065, 666873540, 668156010, 669438485, 670720955, 672003430, 673285905, 674587570, 675889235, 677190900, 678492565, 679794235, 681098850, 682403470, 683708085, 685012705, 686317325, 687641615, 688965910, 690290205, 691614500, 692938795, 694263085, 695587380, 696911675, 698235970, 699560265, 700907400, 702254540, 703601675, 704948815, 706295955, 707643090, 708990230, 710337365, 711684505, 713031645, 714399035, 715766430, 717133825, 718501220, 719868615, 721239080, 722609545, 723980010, 725350475, 726720945, 728111665, 729502385, 730893110, 732283830, 733674555, 735065275, 736455995, 737846720, 739237440, 740628165, 742042925, 743457685, 744872445, 746287205, 747701970, 749116730, 750531490, 751946250, 753361010, 754775775, 756211400, 757647025, 759082650, 760518275, 761953900, 763392720, 764831545, 766270365, 767709190, 769148015, 770608210, 772068405, 773528600, 774988795, 776448990, 777909185, 779369380, 780829575, 782289770, 783749965, 785234930, 786719900, 788204870, 789689840, 791174810, 792659775, 794144745, 795629715, 797114685, 798599655, 800106625, 801613595, 803120565, 804627535, 806134510, 807644780, 809155050, 810665320, 812175590, 813685865, 815218135, 816750405, 818282680, 819814950, 821347225, 822879495, 824411765, 825944040, 827476310, 829008585, 830566930, 832125280, 833683630, 835241980, 836800330, 838358675, 839917025, 841475375, 843033725, 844592075, 846173065, 847754060, 849335050, 850916045, 852497040, 854081465, 855665890, 857250320, 858834745, 860419175, 862026795, 863634420, 865242045, 866849670, 868457295, 870064915, 871672540, 873280165, 874887790, 876495415, 878129885, 879764355, 881398830, 883033300, 884667775, 886302245, 887936715, 889571190, 891205660, 892840135, 894498465, 896156795, 897815125, 899473455, 901131790, 902793690, 904455590, 906117490, 907779390, 909441290, 911127045, 912812805, 914498560, 916184320, 917870080, 919555835, 921241595, 922927350, 924613110, 926298870, 928012865, 929726860, 931440860, 933154855, 934868855, 936582850, 938296845, 940010845, 941724840, 943438840, 945177390, 946915940, 948654490, 950393040, 952131595, 953873825, 955616060, 957358295, 959100530, 960842765, 962610135, 964377505, 966144875, 967912245, 969679615, 971446985, 973214355, 974981725, 976749095, 978516465, 980312910, 982109360, 983905810, 985702260, 987498710, 989295155, 991091605, 992888055, 994684505, 996480955, 998303255];
		return $stats[$stat];
	}

	public static function getWcRequiredExp($lvl){
		return $lvl * 50;
	}
	
	public static function getMirror($mirrlvl) {
		// By Greg - but got the idea from the old game
		
		
		if($mirrlvl == 0)
			return 0;
		
		$base = '0000000000000000000000000000000'; // Basic mirror data

		$text = '';

		for($i = 0; $i < $mirrlvl; $i++) {
			$text .= '1';
		}

		$mirror = $text . substr($base, strlen($text));
		
		$mirror = bindec($mirror);
		
		return $mirror;
	}
	
	public static function getWheelData($id) {
		// By Greg
		global $db;
		$qry = $db->prepare("SELECT wheelcounts, newwheel FROM players WHERE ID = '$id'");
		$qry->execute();
		$wod = $qry->fetch(PDO::FETCH_ASSOC);
		$newe = $wod['newwheel'];
		$whc = $wod['wheelcounts'];
		if($newe <= $GLOBALS["CURRTIME"] && $whc != '0') {
			$whc = 0;
			$newe = 0;
			$db->exec("UPDATE players SET wheelcounts = '0', newwheel = '$newe' WHERE ID = '$id'");
		}
		
		return array($whc, $newe);
	}
	
	public function newQuestItem($in, $lastSpec) {
		// $slot = 1-3
		$slot = $in + 1000; // Item slot
		
		$isSpec = rand(1, 3) == 1; // Special?
		
		if($lastSpec) {
			$isSpec = false; // Last reward was special
		}
		
		if($isSpec) {
			// Special items
			
			// Example item
			$newItem = [
			"type" => 0,
			"item_id" => 0,
			"dmg_min" => 0,
			"dmg_max" => 0,
			"a1" => 0,
			"a2" => 0,
			"a3" => 0,
			"a4" => 0,
			"a5" => 0,
			"a6" => 0,
			"value_silver" => 2500,
			"value_mush" => 0];
			
			$specType = rand(1, 3); // Mirror, pet, key
			
			switch($specType) { // Switch: Special type
				case 1 :
					// Magic mirror
					if($this->data['mirror'] == 13 || $this->mirrorInInv() || $this->data['lvl'] < 50) {
						$newItem = $this->genQuestItem();
					} else {
						$newItem['type'] = 11;
						$newItem['item_id'] = min(12, $this->data['mirror'] + $this->data['unlockmirror']) + 30;
					}
				break;
				
				case 2 :
					// Pet item
					if($this->data['lvl'] < 75 || $this->data['petnest'] == 1) {
						$newItem = $this->genQuestItem();
						break;
					}
				
					$newItem['type'] = 16;
					
					# Pet nest
					if(empty($this->data['pets'])) {
						$newItem['item_id'] = 22;
						break;
					}
					
					$r = rand(1, 2); // 1 = Egg, 2 = Fruit
					
					switch ($r) {
						case 1:
							$eggType = 1; # Common
							if (rand(1, 100) <= 10) $eggType = 2; # Rare
							if (rand(1, 100) <= 5) $eggType = 3; # Gold
							
							$petDung = explode('/', $this->data['petsDung']);
							$petData = explode('/', $this->data['pets']);
							
							switch ($eggType) {
								case 1: list ($from, $to) = [1, 14]; break;
								case 2: list ($from, $to) = [15, 18]; break;
								case 3: list ($from, $to) = [19, 20]; break;
							}
							
							$availableEggs = [];
							for ($env = 1; $env <= 5; $env++) {
								for ($slotPet = $from; $slotPet <= $to; $slotPet++) {
									$base = (($env - 1) * 20) + $slotPet;
									if ($petsUnlock[$base - 1] > 0) continue; # Already in unlockfeature
									if ($pets[$base - 1] > 0) continue; # Already unlocked pet
									if (($slotPet > 3) && ($petsDung[$env - 1] < $slotPet)) break; # No unlocked slot yet
									$itemId = $env;
									if ($eggType == 2) $itemId += 10;
									if ($eggType == 3) $itemId = 21;
									$availableEggs[] = $itemId;
								}
							}
							
							if (count($availableEggs) > 0) {
								$selectEggKey = array_rand($availableEggs);
								$newItem['item_id'] = $availableEggs[$selectEggKey];
								break;
							} else {
								$newItem = $this->genQuestItem();
								break;
							}
						break;
						
						case 2:
							$newItem['item_id'] = 30 + rand(1, 5);
						break;
					}
					
					if ($newItem['item_id'] == 0) {
						$newItem = $this->genQuestItem();
						break;
					}
				break;
				
				case 3 :
					// Dungeon keys, WC key, Wheel v2 item
					
					// WC Key
					if($this->data["lvl"] >= 100 && $this->data["wcaura"] < 1) {
						if($this->WCKeyInInv()) {
							$newItem = $this->genQuestItem();
							break;
						}	
						
						$newItem["type"] = 11;
						$newItem["item_id"] = 20;
						break;
					}
					
					// Wheel v2
					/*if($this->data["lvl"] >= 95 && $this->data["wheel"] == 0 && $this->data["pets"] != null) {
						$uw = new Underworld($this->data["ID"]);
						
						if(!$uw->haveIt || $this->wheelInInv()) {
							$newItem = $this->genQuestItem();
							break;
						}	
						
						$newItem["type"] = 19;
						$newItem["item_id"] = 1;
						break;
					}*/
					
					$newItem = $this->genQuestItem();
				break;
			}
		}else{
			$newItem = $this->genQuestItem();
		}
		
		if ($newItem['item_id'] != 0) {
		$this->questItems[$in - 1] = $newItem;
		
		$newItem['slot'] = $slot;
		
		
		$GLOBALS['db']->exec("INSERT INTO items(owner, slot, type, item_id, dmg_min, dmg_max, a1, a2, a3, a4, a5, a6, value_silver, value_mush) 
			VALUES(".$this->data['ID'].", $newItem[slot], $newItem[type], $newItem[item_id], $newItem[dmg_min], $newItem[dmg_max], 
				$newItem[a1], $newItem[a2], $newItem[a3], $newItem[a4], $newItem[a5], $newItem[a6], $newItem[value_silver], $newItem[value_mush])");
		}
		
		// Nothing to return
	}
	
	public function genQuestItem() {
		// Greg
		$type = rand(1, 10);
		$newItem = Item::genItem((1000000 + $type), $this->lvl, $this->class);
		$newItem['value_silver'] = floor($newItem['value_silver'] / 3);
		return $newItem;
	}
	
	public function mirrorInInv() {
		// Greg
		// Do we have mirror in inventory?
		$sql = "SELECT * FROM items WHERE item_id > 29 AND item_id < 43 AND type = '11' AND owner = '{$this->data['ID']}'";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() != 0) {
			return true;
		}
		return false;
		
	}

	public function nestInInv() {
		// Greg
		// Do we have nest in inventory?
		// Copy of mirrorInInv()
		$sql = "SELECT * FROM items WHERE item_id = '22' AND type = '16' AND owner = '{$this->data['ID']}'";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() != 0) {
			return true;
		}
		return false;
		
	}
	
	public function WCKeyInInv() {
		// Greg
		// Do we have WC key in inventory?
		// Copy of mirrorInInv()
		$sql = "SELECT * FROM items WHERE item_id = '20' AND type = '11' AND owner = '{$this->data['ID']}'";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() != 0) {
			return true;
		}
		return false;
		
	}
	
	public function heartInInv() {
		// Greg
		// Do we have heart key in inventory?
		// Copy of mirrorInInv()
		$sql = "SELECT * FROM items WHERE item_id = '1' AND type = '18' AND owner = '{$this->data['ID']}'";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() != 0) {
			return true;
		}
		return false;
		
	}
	
	public function albumInInv() {
		// Greg
		// Do we have album in inventory?
		// Copy of mirrorInInv()
		$sql = "SELECT * FROM items WHERE item_id = '1' AND type = '13' AND owner = '{$this->data['ID']}'";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() != 0) {
			return true;
		}
		return false;
		
	}
	
	public function wheelInInv() {
		// Greg
		// Do we have wheel key in inventory?
		// Copy of mirrorInInv()
		$sql = "SELECT * FROM items WHERE item_id = '1' AND type = '19' AND owner = '{$this->data['ID']}'";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() != 0) {
			return true;
		}
		return false;
		
	}
	
	public function fortressRerollPrice() {
		return ($this->data['lvl'] * 100 + $this->data['lvl']) * 100 + $this->data['lvl'];
	}
	
	public function genFortressEnemy() {
		// Generates a fortess enemy by Greg
		
		// Random
		$rand = rand(1, 10);
		
		// Try exact level
		$sql = "SELECT owner FROM fortress WHERE b11 <> 0 AND b0 = {$this->data['b0']} AND owner <> '{$this->data['ID']}' ORDER BY owner DESC LIMIT $rand";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() > 0)
			return $qry->fetchAll()[rand(0, ($qry->rowCount() - 1))]['owner'];
		
		// Try smaller level
		$sql = "SELECT owner FROM fortress WHERE b11 <> 0 AND b0 < {$this->data['b0']} AND owner <> '{$this->data['ID']}' ORDER BY b0 DESC, owner DESC LIMIT $rand";
		$qry = $GLOBALS['db']->query($sql);
		if($qry->rowCount() > 0)
			return $qry->fetchAll()[rand(0, ($qry->rowCount() - 1))]['owner'];
		
		return $GLOBALS['db']->query("SELECT owner FROM fortress LIMIT 1")->fetchAll()[0]['owner']; // Not found anything lel
	}
	
	public function newFortressEnemy($money = true) {
		// Sets the new fortress enemy by Greg
		$new = $this->genFortressEnemy();
		
		$this->data['enemyid'] = $new;
		
		if($money)
			$this->data['silver'] -= $this->fortressRerollPrice();
		
		$sql = "UPDATE fortress SET enemyid = '$new' WHERE owner = '{$this->data['ID']}'";
		$GLOBALS['db']->exec($sql);
		$sql = "UPDATE players SET silver = '{$this->data['silver']}' WHERE ID = '{$this->data['ID']}'";
		$GLOBALS['db']->exec($sql);
	}
	
	public function getWitchData() {
		$witchData = [
			$this->witch['fill'], // Witch progress
			$this->witch['max'], // Max progress
			$this->witch['event'] // Donation ID
		];
		return implode('/', $witchData);
	}
	
	public static function formatUser($name) {
		$exp = explode('@', $name);
		$exp = $exp[count($exp) - 1];
		$exp = explode(']', $exp);
		$exp = $exp[count($exp) - 1];
		return $exp;
	}
	
	public function friendList() {
		// Friends by Greg - v2
		// v2: Changed to JSON from text, optimized the code a lot (much faster than before)
		
		$friends = $this->data['friends'];
		
		if($friends == '') {
			return '';
		}
		
		$friends = json_decode($friends, true);
		
		$fList = [];
		$dList = [];
		
		foreach($friends as $id => $status) {
			$sql = "SELECT players.name, guilds.name as gname, players.lvl FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.ID = '{$id}'";
			$qry = $GLOBALS['db']->query($sql);
			
			if($qry->rowCount() == 0)
				continue;
			
			$pdata = $qry->fetchAll()[0];
			
			$cret = [$id, $pdata['name'], $pdata['gname'], $pdata['lvl'], $status]; // Current ret
			
			if($status == '1')
				$fList[] = implode(',', $cret);
			else
				$dList[] = implode(',', $cret);
		}
		
		if(count($fList) != 0)
			$fList[] = '';
		
		if(count($dList) != 0)
			$dList[] = '';
		
		$all = implode(';', $fList) . implode(';', $dList);
		
		return $all;
	}
	
	public function otherPlayerFriendStatus($id) {
		// Are we friends or denided? by Greg - v2
		
		$friends = $this->data['friends'];
		
		if($friends == '') {
			return 0;
		}
		
		$friends = json_decode($friends, true);
		
		if(isset($friends[$id]))
			return $friends[$id]; // Return status
		
		return 0;
	}
	
	public function setFriendStatus($id, $status) {
		// Set friend status v2 by Greg
		
		$id = intval($id);
		$status = intval($status);
		
		$friends = $this->data['friends'];
		
		if($friends == '')
			$friends = [];
		else
			$friends = json_decode($friends, true);
		
		if($status == 0) {
			// Remove
			
			unset($friends[$id]);
		}else{
			// Add friend
			
			$zh = explode(';', $friends);
			
			$friends[$id] = $status;
		}
		
		$this->data["friends"] = json_encode($friends);
		
		$sql = "UPDATE players SET friends = '{$this->data['friends']}' WHERE ID = '{$this->data['ID']}'";
		$GLOBALS['db']->exec($sql);
	}
	
	public static function isUserIgnored($flist, $id) {
		// If user in friendlist as ignored v2 by Greg
		
		if($flist == '')
			return false;
		
		$flist = json_decode($flist, true);
		
		if(isset($flist[$id]) && $flist[$id] == "-1")
			return true;
		
		return false;
	}
	
	public function questBackground($xp) {
		// Rework by Greg
		
		$xp = str_split(sha1($xp));
		
		$bg = "";
		
		for($i = 0; $i <= 39; $i++)
		{
			if(strlen($bg) == 2)
				break;
			
			if(is_numeric($xp[$i]) && ($xp[$i] != '0' || strlen($bg) != 0))
				$bg .= $xp[$i];
		}
		
		$bg = intval($bg);
	
		if($bg < 11)
			$bg = 11 + round($bg / 2);
		
		if($bg > 21)
			$bg = intval($bg / 3);
		
		if($bg > 21)
			$bg = 21;
		
		if($bg < 11)
			$bg = 11 + round($bg / 2);
		
		if($bg < 11)
			return 11;
		
		if($bg > 21)
			return 21;
		
		return $bg;
	}
	
	public function questDesc($xp) {
		// Rework by Greg
		
		$xp = str_split(sha1($xp));
		
		$bg = "";
		
		for($i = 0; $i <= 39; $i++)
		{
			if(strlen($bg) == 2)
				break;
			
			if(is_numeric($xp[$i]) && ($xp[$i] != '0' || strlen($bg) != 0))
				$bg .= $xp[$i];
		}
		
		$bg = intval($bg);
		
		if($bg > 15)
			$bg = intval($bg / 4);
		
		if($bg > 15)
			$bg = intval($bg / 10);
		
		if($bg < 1)
			$bg = 1;
		
		if($bg == 4)
			$bg = 1;
		
		return $bg;
	}
	
	public function fortressAttack($q)
	{
		exit('&Error:error occured, fix soon');
		
		// v2 - Greg, mixed with v1 cuz you know I'm lazy af
		
		if($q > $this->data["u1"])
			exit("&Error:more"); // Pls kys.. Trying to attack with more soldy
		
		if($this->data['ID'] == $this->data["enemyid"]) // Don't attack yourself
			exit();
		
		// Select enemy data
		$sql = "SELECT * FROM fortress WHERE owner='{$this->data["enemyid"]}'";
		$enemyData = $GLOBALS['db']->query($sql)->fetch(PDO::FETCH_ASSOC);
		
		$unit = [
			Fortress::unitStats(1, $this->data['ul1']), // Soldiers
			Fortress::unitStats(3, $enemyData['ul3']), // Archers
			Fortress::unitStats(2, $enemyData['ul2']), // Mages
			Fortress::unitStats(4, $enemyData['b11']), // Fortress fence
		];
		
		//$unit[0] = Fortress::unitStats(1, $this->data['ul1']); // My unit stats (soldiers)
		
		//$unit[1] = Fortress::unitStats(3, $enemyData['ul3']); // Enemy unit stats (archers)
		
		//$unit[2] = Fortress::unitStats(2, $enemyData['ul2']); // Enemy unit stats (mages)
		
		//$unit[3] = Fortress::unitStats(4, $enemyData['b11']); // Enemy unit stats (fortress)
		
		// Generate classes
		for($i = 0; $i < 4; $i++)
		{
			$curr = $unit[$i];
			
			$class = $curr[1];
			
			if($class == 1)
			{
				$weaponMultiplier = 4.25;
				$statToUse = $curr[3];
			}
			else if($class == 2)
			{
				$weaponMultiplier = 5.5;
				$statToUse = $curr[5];
			}
			else
			{
				$weaponMultiplier = 3;
				$statToUse = $curr[4];
			}
			
			$dmg = 1 + floor($weaponMultiplier * $curr[2]);
			$minmax = 2 + floor($curr[2] * 55 / 100);
			$dmg_min = ($dmg - $minmax) * floor(1 + $statToUse / 10);
			$dmg_max = ($dmg + $minmax) * floor(1 + $statToUse / 10);
			
			if ($class == 0)
				$class = 3;
			
			//$lvl = $curr[9] ?? 5;
			$lvl = 5;
			
			if(!isset($curr[9])){
				$lvl = 5;
			}else{
				$lvl = $curr[9];
			}	
			
			$add = 3;
			
			if ($lvl >= 5)
				$add = 10;
			
			if ($lvl >= 15)
				$add = 50;
			
			$weapon = ($class - 1) * 1000 + $add;
			
			if ($weapon < 0)
				$weapon = $add;
			
			$shield = $class == 1 ? $add : 0;
			
			$unit[$i] = new FortressMonster($curr[2], $class, $curr[3], $curr[4], $curr[5], $curr[6], $curr[7], $dmg_min, $dmg_max, $curr[8], 0, $curr[0], 0, $weapon, $shield);
		}
		
		$myUnits = [];
		$enemyUnits = [];
		
		// Add my soldiers
		for($i = 0; $i < $q; $i++)
		{
			$add = unserialize(serialize($unit[0]));
			
			$add->currCount = $i + 1;
			
			$add->allCount = $q;
			
			$myUnits[] = $add;
		}
		
		// Add enemy archers
		for($i = 0; $i < $enemyData['u3']; $i++)
		{
			$add = unserialize(serialize($unit[1]));
			
			$add->currCount = $i + 1;
			
			$add->allCount = $enemyData['u3'];
			
			$enemyUnits[] = $add;
		}

		// Add enemy mages
		for($i = 0; $i < $enemyData['u2']; $i++)
		{
			$add = unserialize(serialize($unit[2]));
			
			$add->currCount = $i + 1;
			
			$add->allCount = $enemyData['u2'];
			
			$enemyUnits[] = $add;
		}
		
		$enemyUnits[] = unserialize(serialize($unit[3])); // Fortress defense
		
		//simulate fight
		$simulation = new GroupSimulation($myUnits, $enemyUnits);
		$simulation->simulate();
		
		//output logs
		for($i = 0; $i < count($simulation->simulations); $i++)
		{
			$fightn = $i+1;
			$fightLog[] = "fightheader".$fightn.".fighters:8/0/0/0/1/".$simulation->fightHeaders[$i];
			$fightLog[] = "fight".$fightn.".r:".$simulation->simulations[$i]->fightLog;
			$fightLog[] = "winnerid".$fightn.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$enemyName = $GLOBALS["db"]->query("SELECT name FROM players WHERE ID = " . $this->data["enemyid"])->fetchAll()[0]["name"];
		
		$fightLog[] = 'fightgroups.r:100000,100001,' . $this->data["name"] . ',' . $enemyName;
		
		$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
		
		if($simulation->win)
		{
			$battleReward[0] = 1;
			
			// Honor system add 1
			$this->data['forthonor'] ++;
			$GLOBALS['db']->exec("UPDATE fortress SET forthonor = ".$this->data['forthonor']." WHERE owner = ".$this->data['ID']);
		} else {
			// Honor system loss 3
			$this->data['forthonor'] -= 3;
			if($this->data['forthonor'] < 0)
				$this->data['forthonor'] = 0;
			$GLOBALS['db']->exec("UPDATE fortress SET forthonor = ".$this->data['forthonor']." WHERE owner = ".$this->data['ID']);
		}	
		
		$this->newFortressEnemy(false);
		
		$ret = [];
		
		$ret[] = implode("&", $fightLog);
		$ret[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		$ret[] = 'fightresult.battlereward:'.join('/', $battleReward);
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		
		return $ret;
	}
	
	
	public static function createWitch()
	{
		// Creates a witch if not exists (Greg)
		
		$GLOBALS["db"]->exec("INSERT INTO witch () VALUES ()");
	}
	
	public function getCombatLog(){
		// By Greg
		
		// Format: COMBATID,ENEMYNAME,WIN/LOSS,TYPE,TIME,CHECKED;
		
		// Types
		// 0 - Arena
		// 1 - Quest
		// 2 - Guild fight
		// 3 - Guild raid
		// 4 - Dungeon
		// 5 - Tower
		// 6 - Portal
		// 7 - Guild portal
		// 8 - Fortress attack
		// 9 - Fortress defend
		// 10 - Shadow world
		
		$times = [];
		
		$combats = [];
		
		// Group battles
		
		$rows = [];
		
		// Group battles - as attacker
		$qry = $GLOBALS["db"]->query("SELECT guildfightlogs.ID, guildfightlogs.endLog, guildfightlogs.time, guilds.name AS gname FROM guildfightlogs LEFT JOIN guilds ON guilds.ID = guildfightlogs.guildDefender WHERE guildAttacker = {$this->data["guild"]} AND guildDefender <> 1000000");
		
		foreach($qry->fetchAll() as $row){
			$rows[] = $row;
		}

		// Group battles - as defender
		$qry = $GLOBALS["db"]->query("SELECT guildfightlogs.ID, guildfightlogs.endLog, guildfightlogs.time, guilds.name AS gname FROM guildfightlogs LEFT JOIN guilds ON guilds.ID = guildfightlogs.guildAttacker WHERE guildDefender = {$this->data["guild"]}");
		
		foreach($qry->fetchAll() as $row){
			$rows[] = $row;
		}
		
		// Complete result
		foreach($rows as $row){
			$endLog = explode("/", $row["endLog"]);
			
			$combats[("2" . $row["ID"])] = [("2" . $row["ID"]), $row["gname"], ($this->data["guild"] == $endLog[1] ? 1 : 0), 2, $row["time"]];
		
			$times[("2" . $row["ID"])] = $row["time"];
		}

		// Raids
		$qry = $GLOBALS["db"]->query("SELECT ID, endLog, time FROM guildfightlogs WHERE guildDefender = 1000000 AND guildAttacker = " . $this->data["guild"]);
		
		foreach($qry->fetchAll() as $row){
			$endLog = explode("/", $row["endLog"]);
			
			$combats[("3" . $row["ID"])] = [("3" . $row["ID"]), $endLog[2], $endLog[1], 3, $row["time"]];
		
			$times[("3" . $row["ID"])] = $row["time"];
		}
		
		arsort($times);
		
		$format = [];
		
		foreach($times as $key => $val){
			$combat = $combats[$key];
			
			$format[] = implode(",", $combat) . ",0";
		}	
		
		return implode(";", $format) . ";";
	}

	public function getRealStats() {
		$ret = [];
		$ret["str"] = $this->data["str"];
		$ret["dex"] = $this->data["dex"];
		$ret["intel"] = $this->data["intel"];
		$ret["wit"] = $this->data["wit"];
		$ret["luck"] = $this->data["luck"];
		
		return $ret;
	}

	public static function addSecondWep($pid, $class) {
		if($class != 4)
			return;
		
		$weapon = Item::genItem(1000001, 1, $class);
		$weapon['value_silver'] = 0;
		$weapon['item_id'] = 1;
		
		// No stats
		for($i = 1; $i < 7; $i++)
			$weapon['a'.$i] = 0;
		
		$GLOBALS['db']->exec('INSERT INTO items(owner, slot, type, item_id, dmg_min, dmg_max, a1, a2, a3, a4, a5, a6, value_silver, value_mush) VALUES('.$pid.', 19, '.join(', ', $weapon).')');
	}

	// By Jack
	public static function getAccountID($name) {
		if($name == null || $name == "")
			return null;
		
		$db = $GLOBALS["db"];
		
		$qry = $db->prepare("SELECT ID FROM players WHERE name = ?");
		$qry->execute([$name]);
		
		if($qry->rowCount() == 0)
			return null;
		
		$id = $qry->fetch(PDO::FETCH_ASSOC)['ID'];
		
		return $id;
	}
	
	// By Jack
	public static function getAccountName($id) {
		if($id == null || $id == "" || $id < 0)
			return null;
		
		$db = $GLOBALS["db"];
		
		$qry = $db->prepare("SELECT name FROM players WHERE ID = ?");
		$qry->execute([$id]);
		
		if($qry->rowCount() == 0)
			return null;
		
		$name = $qry->fetch(PDO::FETCH_ASSOC)['name'];
		
		return $name;
	}
	
	//By Jack
	//Remove silver only return gold
	public static function getGold($gold, $limit) {
		$limit *= 100; // Limit in gold
		
		if($gold > $limit) {
			
			$gold = round($gold / 100);
			$gold *= 100;
		
		}	
		return $gold;
	}
	
	public function getDungeonResponse() {
		$ret = [];
		
		$ret[] = 'dungeonprogresslight(30):'.$this->getDungeonsLevel();
		$ret[] = 'dungeonprogressshadow(30):'.$this->getDungeonsLevel(true);
		
		$d_enemies = $this->dungeonEnemies();
		$ret[] = 'dungeonenemieslight('.$d_enemies['count'].'):'.$d_enemies['data'];
		
		$c_enemies = $this->currentDungeonEnemies();
		$ret[] = 'currentdungeonenemieslight('.$c_enemies['count'].'):'.$c_enemies['data'];
		
		$d_enemies = $this->dungeonEnemies(true);
		$ret[] = 'dungeonenemiesshadow('.$d_enemies['count'].'):'.$d_enemies['data'];
		
		$c_enemies = $this->currentDungeonEnemies(true);
		$ret[] = 'currentdungeonenemiesshadow('.$c_enemies['count'].'):'.$c_enemies['data'];

		$ret[] = 'portalprogress(3):'.$this->portalProgress();
		
		return join('&', $ret);
	}
	
	public function getDungeonsLevel($shadow = false) {
		global $db;
		
		$ret = array_fill(0, 30, 0);
		
		$qry = $db->query('SELECT lightdungeons, shadowdungeons, twister, tower, portal, idols FROM players WHERE ID = '.$this->data['ID']);
		$dungeon = $qry->fetch(PDO::FETCH_ASSOC);
		
		$dungeonLight = json_decode($dungeon['lightdungeons'], true);
		$dungeonShadow = json_decode($dungeon['shadowdungeons'], true);

		$ret[0] = ($shadow) ? $dungeonShadow[1] : $dungeonLight[1]; # 1. Desecrated catacombs
		$ret[1] = ($shadow) ? $dungeonShadow[2] : $dungeonLight[2]; # 2. The mines of gloria
		$ret[2] = ($shadow) ? $dungeonShadow[3] : $dungeonLight[3]; # 3. The ruins of gnark
		$ret[3] = ($shadow) ? $dungeonShadow[4] : $dungeonLight[4]; # 4. The cutthroat grotto
		$ret[4] = ($shadow) ? $dungeonShadow[5] : $dungeonLight[5]; # 5. The emerald scale altar
		$ret[5] = ($shadow) ? $dungeonShadow[6] : $dungeonLight[6]; # 6. The toxic tree
		$ret[6] = ($shadow) ? $dungeonShadow[7] : $dungeonLight[7]; # 7. The magma stream
		$ret[7] = ($shadow) ? $dungeonShadow[8] : $dungeonLight[8]; # 8. The frost blood temple
		$ret[8] = ($shadow) ? $dungeonShadow[9] : $dungeonLight[9]; # 9. The pyramids of madness
		$ret[9] = ($shadow) ? $dungeonShadow[10] : $dungeonLight[10]; # 10. Black skull fortress
		$ret[10] = ($shadow) ? $dungeonShadow[11] : $dungeonLight[11]; # 11. Circus of terror
		$ret[11] = ($shadow) ? $dungeonShadow[12] : $dungeonLight[12]; # 12. Hell
		$ret[12] = ($shadow) ? $dungeonShadow[15] : $dungeonLight[15]; # 15. 13th floor
		$ret[13] = ($shadow) ? $dungeonShadow[19] : $dungeonLight[19]; # 19. Easteros
		$ret[14] = ($shadow) ? $dungeon['twister'] : $dungeon['tower']; # Twister | Tower
		$ret[15] = ($shadow) ? $dungeonShadow[17] : $dungeonLight[17]; # 17. Time-honored school of magic
		$ret[16] = ($shadow) ? $dungeonShadow[18] : $dungeonLight[18]; # 18. Hemorridor
		$ret[17] = ($shadow) ? $dungeon['idols'] : $dungeon['portal']; # Continous loop of idols / Portal
		$ret[18] = ($shadow) ? $dungeonShadow[24] : $dungeonLight[24]; # Nordic gods
		$ret[19] = ($shadow) ? $dungeonShadow[27] : $dungeonLight[27]; # Mount olympus
		$ret[20] = ($shadow) ? $dungeonShadow[21] : $dungeonLight[21]; # Tavern of the dark doppelgangers
		$ret[21] = ($shadow) ? $dungeonShadow[13] : $dungeonLight[13]; # Dragon's hoard
		$ret[22] = ($shadow) ? $dungeonShadow[14] : $dungeonLight[14]; # House of horrors
		$ret[23] = ($shadow) ? $dungeonShadow[16] : $dungeonLight[16]; # The 3rd league of superheroses
		$ret[24] = ($shadow) ? $dungeonShadow[20] : $dungeonLight[20]; # Dojo of childhood heroes
		$ret[25] = ($shadow) ? $dungeonShadow[28] : $dungeonLight[28]; # Monster grotto
		$ret[26] = ($shadow) ? $dungeonShadow[22] : $dungeonLight[22]; # City of intrigues
		$ret[27] = ($shadow) ? $dungeonShadow[23] : $dungeonLight[23]; # School of magic express
		$ret[28] = ($shadow) ? $dungeonShadow[25] : $dungeonLight[25]; # Ash mountain
		$ret[29] = ($shadow) ? $dungeonShadow[26] : $dungeonLight[26]; # Playa HQ
		
		return join('/', $ret);
	}

	public function dungeonEnemies($shadow = false) {
		global $db;
		
		$qry = $db->query('SELECT lightdungeons, shadowdungeons, twister, tower, portal, idols FROM players WHERE ID = '.$this->data['ID']);
		$dungeon = $qry->fetch(PDO::FETCH_ASSOC);
				
		$dungeonLight = json_decode($dungeon['lightdungeons'], true);
		$dungeonShadow = json_decode($dungeon['shadowdungeons'], true);
		
		$response = ['count' => 0, 'data' => []];
		
		# Light | Shadow dungeons
		for ($i = 1; $i <= 30; $i++) 
		{
			$dungeonFloor = Misc::dungNumber($i);
			$dungeonLvl = ($shadow) ? $dungeonShadow[$dungeonFloor] : $dungeonLight[$dungeonFloor];
			if ($dungeonLvl < 0) continue;
			
			for ($m = 0; $m < 5; $m++)
			{
				$dungeonLvlSlider = $dungeonLvl + ($m - 1);				

				$monsterCls = ($shadow) ? Monster::getShadowMonster($dungeonFloor, $dungeonLvlSlider) : Monster::getDungeonMonster($dungeonFloor, $dungeonLvlSlider);
				if ($monsterCls === null) continue;

				if ($monsterCls->ID == -1) 
				{
					$monsterCls = clone($this);
					$monsterCls->ID = -1;
				}
				
				$response['data'][] = ($monsterCls->ID == -1) ? -1 : abs($monsterCls->ID);

				$d = $i;
				if($i == 15) $d = 13;
				if($i == 18) $d = 17;
				if($i == 15 && !$shadow) $d = 2;
				
				$response['data'][] = $d;
				$response['data'][] = (($shadow) || (!$shadow && ($dungeonLvlSlider == 5 || $dungeonLvlSlider == 10))) ? 2 : 0;

				$response['count']++;
			}
		}

		# Tower
		if (!$shadow)
		{
			if($dungeon['tower'] >= 0) 
			{
				for($m = 0; $m < 5; $m++) 
				{
					$dungeonLvl = $m;
					if($dungeon['tower'] == 1) $dungeonLvl -= 1;
					if($dungeon['tower'] >= 2) $dungeonLvl -= 2;
					
					$monsterCls = Monster::getTowerMonster($dungeon['tower'] + $dungeonLvl);
					if ($monsterCls === null) continue;

					$response['data'][] = abs($monsterCls->ID);
					$response['data'][] = 15;
					$response['data'][] = 2;
					
					$response['count']++;
				}
			}
		}
		
		# Twister
		if($shadow) 
		{
			if($dungeon['twister'] >= 0) 
			{
				for($m = 0; $m < 5; $m++) 
				{
					$dungeonLvl = $m;
					if($dungeon['twister'] == 0) $dungeonLvl += 1;
					if($dungeon['twister'] == 1) $dungeonLvl += 0;
					if($dungeon['twister'] >= 2) $dungeonLvl -= 1;
					
					$monsterCls = Monster::getTwisterMonster($dungeon['twister'] + $dungeonLvl);
					if ($monsterCls === null) continue;
					
					$response['data'][] = abs($monsterCls->ID);
					$response['data'][] = 15;
					$response['data'][] = 0;
					
					$response['count']++;
				}
			}
		}
		
		# Portal
		if(!$shadow) 
		{
			for($m = 0; $m < 5; $m++) 
			{
				$dungeonLvl = $m;
				if($dungeon['portal'] == 0) $dungeonLvl += 1;
				if($dungeon['portal'] == 1) $dungeonLvl += 0;
				if($dungeon['portal'] >= 2) $dungeonLvl -= 1;
				
				$monsterCls = Monster::getPortalMonster($dungeon['portal'] + $dungeonLvl);
				if ($monsterCls === null) continue;
				
				$response['data'][] = abs($monsterCls->ID);
				$response['data'][] = 18;
				$response['data'][] = 0;
			}
		}

		# Idols
		if ($shadow)
		{
			if($dungeon['idols'] >= 0) 
			{
				for($m = 0; $m < 5; $m++) 
				{
					$dungeonLvl = $m;
					if($dungeon['idols'] == 0) $dungeonLvl += 1;
					if($dungeon['idols'] == 1) $dungeonLvl += 0;
					if($dungeon['idols'] >= 2) $dungeonLvl -= 1;
					
					$monsterCls = Monster::getLoopMonsters($dungeon['idols'] + $dungeonLvl);
					if ($monsterCls === null) continue;

					$response['data'][] = abs($monsterCls->ID);
					$response['data'][] = 18;
					$response['data'][] = 2;
					
					$response['count']++;
				}
			}
		}
		
		$response['data'] = join('/', $response['data']);
		return $response;
	}
	
	public function currentDungeonEnemies($shadow = false) {
		global $db;
		
		$qry = $db->query('SELECT lightdungeons, shadowdungeons, twister, tower, portal, idols FROM players WHERE ID = '.$this->data['ID']);
		$dungeon = $qry->fetch(PDO::FETCH_ASSOC);
		
		$dungeonLight = json_decode($dungeon['lightdungeons'], true);
		$dungeonShadow = json_decode($dungeon['shadowdungeons'], true);
		
		$response = ['count' => 0, 'data' => []];
		
		# Light | Shadow dungeons
		for ($i = 1; $i <= 30; $i++)
		{
			$dungeonFloor = Misc::dungNumber($i);
			
			$dungeonLvl = ($shadow) ? $dungeonShadow[$dungeonFloor] : $dungeonLight[$dungeonFloor];
			if ($dungeonLvl < 0) continue;
			
			$monsterCls = ($shadow) ? Monster::getShadowMonster($dungeonFloor, $dungeonLvl + 1) : Monster::getDungeonMonster($dungeonFloor, $dungeonLvl + 1);
			if($monsterCls == null) continue;

			if($monsterCls->ID == -1) 
			{
				$monsterCls = clone($this);
				$monsterCls->ID = -1;
			}
			
			$response['data'][] = ($monsterCls->ID == -1) ? -1 : abs($monsterCls->ID);

			if(!$shadow) {
				$response['data'][] = (($i == 15 && $dungeonLight[2] >= 10) ? 2 : ($i == 18 ? 17 : $i));
			} else {
				$response['data'][] = $i;
			}
			
			$response['data'][] = $monsterCls->lvl;
			$response['data'][] = $monsterCls->class;
			$response['data'][] = 0;
			
			$response['count']++;
		}

		# Twister
		if($shadow) {
			$monsterCls = Monster::getTwisterMonster($dungeon['twister'] + 1);
			if ($monsterCls !== null) {
				$response['data'][] = abs($monsterCls->ID);
				$response['data'][] = 15;
				$response['data'][] = $monsterCls->lvl;
				$response['data'][] = $monsterCls->class;
				$response['data'][] = 0;
				
				$response['count']++;
			}
		}
		
		# Tower
		if (!$shadow) {
			$monsterCls = Monster::getTowerMonster($dungeon['tower']);
			if ($monsterCls !== null) {
				$response['data'][] = abs($monsterCls->ID);
				$response['data'][] = 15;
				$response['data'][] = $monsterCls->lvl;
				$response['data'][] = $monsterCls->class;
				$response['data'][] = 0;

				$response['count']++;
			}
		}
		
		# Idols
		if ($shadow) {
			$monsterCls = Monster::getLoopMonsters($dungeon['idols'] + 1);
			if ($monsterCls !== null) {
				$response['data'][] = abs($monsterCls->ID);
				$response['data'][] = 18;
				$response['data'][] = $monsterCls->lvl;
				$response['data'][] = $monsterCls->class;
				$response['data'][] = 0;

				$response['count']++;
			}
		}
		
		# Portal
		if (!$shadow) {
			$lvl = min($dungeon['portal'] + 1, 50);
			$monsterCls = Monster::getPortalMonster($lvl);
			if ($monsterCls !== null) {
				$response['data'][] = abs($monsterCls->ID);
				$response['data'][] = 18;
				$response['data'][] = $monsterCls->lvl;
				$response['data'][] = $monsterCls->class;
				$response['data'][] = 0;

				$response['count']++;
			}
		}

		$response['data'] = join('/', $response['data']);
		return $response;
	}
	
	public function portalProgress() {
		global $db;
		
		$qry = $db->query('SELECT portal, portal_hp, portal_time FROM players WHERE ID = '.$this->data['ID']);
		$dungeon = $qry->fetch(PDO::FETCH_ASSOC);
		
		$lvl = ($dungeon['portal'] + 1 > 50) ? 50 : $dungeon['portal'] + 1;
		$monster = Monster::getPortalMonster($lvl);
		
		$ret[] = $dungeon['portal'];
		$ret[] = intval(($dungeon['portal_hp'] / $monster->hp) * 100);
		$p_time = (Misc::getNow() == $dungeon['portal_time']) ? strtotime('tomorrow') : time();
		$ret[] = date('z', $p_time);
		
		return join('/', $ret);
	}

}
?>