<?php

class Underworld{
	public $owner;
	public $data;
	public $haveIt = false;
	
	function __construct($owner = 0, $data = []){
		if(count($data) == 0)
			$data = Underworld::getData($owner);
		
		$this->data = $data;
		
		if(count($this->data) > 0)
			$this->haveIt = true;
	}
	
	public static function getData($owner){
		$qry = $GLOBALS["db"]->query("SELECT * FROM underworld WHERE owner = " . $owner);
		
		if($qry->rowCount() == 0)
			return [];
		
		$data = $qry->fetch(PDO::FETCH_ASSOC);
		
		return $data;
	}
	
	public function getSave(){
		// Underworld by Greg
		
		$ret = [];
		
		for($i = 0; $i < 29; $i++)
			$ret[] = 0;
		
		if(!$this->haveIt)
			return implode("/", $ret);
		
		$fetch = $this->data;
	
		// Building levels
		$ret[1] = $fetch["heart"];
		$ret[2] = $fetch["gate"];
		$ret[6] = $fetch["torture"];
		$ret[10] = $fetch["keeper"];
		$ret[4] = $fetch["extractor"];
		$ret[5] = $fetch["goblin"];
		$ret[7] = $fetch["gladiator"];
		$ret[8] = $fetch["troll"];
		$ret[3] = $fetch["gold"];
		$ret[9] = $fetch["time"];
		
		$ret[11] = $fetch["soul"];
		$ret[14] = $this->getMaxSouls(); // Max souls
		
		// Last gathered
		$ret[20] = max($fetch["gather1"], $fetch["gather2"], $fetch["gather3"]);
		
		// Soul extractor
		//$ret[12] = floor(($ret[20] - $fetch["gather2"]) * ($this->getSoulExtractorResources($fetch["extractor"])[0] / 3600)); // Current
		$ret[12] = floor(($GLOBALS["CURRTIME"] - $fetch["gather2"]) * ($this->getSoulExtractorResources($fetch["extractor"])[0] / 3600) * $GLOBALS['soulbonus']); // Current
		$ret[13] = $this->getSoulExtractorResources($fetch["extractor"])[1]; // Max
		$ret[16] = $this->getSoulExtractorResources($fetch["extractor"])[0] * $GLOBALS['soulbonus']; // Per hour
		
		// Battle
		$ret[24] = $fetch["battle_lvl"]; // Max. soul at this lvl
		$ret[25] = $fetch["lured"]; // Lured today
		
		// Time machine
		$ret[26] = $fetch["timeamount"]; // Current
		$ret[27] = $this->getTimeMachineThirst()[1]; // Max store
		$ret[28] = $this->getTimeMachineThirst()[0]; // Max per day
		
		if($ret[26] > $this->getTimeMachineThirst()[1]) {
			$ret[26] = $this->getTimeMachineThirst()[1];
		}	
		
		// Gold mine
		// Calculate goldbonus (settings.php bonus)
		$gbonus = floor($GLOBALS['goldbonus'] / 2);
		if ($gbonus < 1) $gbonus = 1;
		
		$ret[17] = floor(($GLOBALS["CURRTIME"] - $fetch["gather1"]) * ($fetch["claim_gold"] / 3600)) * $gbonus; // Current
		$ret[18] = floor($fetch["claim_gold"]) * 10; // Max
		$ret[19] = $fetch["claim_gold"] * $gbonus; // Per hour
		
		//Gold mine bonus - only for private, not coded in original sfgame
		if($fetch["gold"] >= 5 && $GLOBALS["goldbonus"] != 1) {
			$ret[17] *= ($fetch["gold"] - 3);
			$ret[19] *= ($fetch["gold"] - 3);
		}	
		
		$ret[15] = 1;
		
		//Building
		$ret[21] = $fetch["build_id"];
		$ret[22] = $fetch["build_end"];
		$ret[23] = $fetch["build_start"];
		
		// Fix, no produce when building lvl = 0
		if($ret[3] <= 0) {
			$ret[17] = 0;
			$ret[18] = 0;
			$ret[19] = 0;
		}
		
		if($ret[4] <= 0) {
			$ret[12] = 0;
			$ret[13] = 0;
			$ret[16] = 0;
		}
		
		if($ret[9] <= 0) {
			$ret[26] = 0;
			$ret[27] = 0;
			$ret[28] = 0;
		}
		
		return implode("/", $ret);
	}
	
	public function getUpgradePrice(){
		$ret = [];
		
		for($i = 1; $i < 11; $i++) {
			$b = $this->getBuilding($i);
			
			$lvl = $this->data[$b];
			$lvl++;
			
			$price = $this->getBuildingPrice($i, $lvl);
			
			$build_time = $this->getBuildingTime($lvl);
			
			$ret[] = $build_time;
			$ret[] = $price[0];
			$ret[] = $price[1];
			
		}

		return implode("/", $ret);
		
		
		//return "10260/700000/45930/10260/350000/4820/7200/600000/1880/10260/175000/40190/7200/240000/16120/10260/462000/41340/7200/420000/22570/7200/594000/25080/7200/6000000/22570/10260/1050000/45930";
	}
	
	public function getUnitUpgradePrice($unit = 0){
		$ret = [];
		
		for($i = 0; $i < 3; $i++) {
			switch($i) {
				case 0:
					$upgrade = $this->data['unit_goblins'];
					$upgrade ++;
					
					$ret[] = $upgrade;
			
					$gold = round(141 * $upgrade * (1 + 0.32 * $upgrade));
					$soul = round(14 * $upgrade * (1 + 0.29 * $upgrade));
					
					$gold = $this->checkUpgradePrice($gold, 1);
					$soul = $this->checkUpgradePrice($soul);
					
					if($this->getEntityStats(1)[0] >= 10000000 || $this->getEntityStats(1)[1] >= 10000 || $upgrade > 1000) {
						$gold = 0;
						$soul = 0;
					}
					
					$ret[] = $gold;
					$ret[] = $soul;
					
					if($unit == 1) {
						return [$gold, $soul];
					}	
					
				break;
				case 1:
					$upgrade = $this->data['unit_trolls'];
					$upgrade ++;
					
					$ret[] = $upgrade;
			
					$gold = round(168 * $upgrade * (1 + 0.51 * $upgrade));
					$soul = round(18 * $upgrade * (1 + 0.48 * $upgrade));
					
					$gold = $this->checkUpgradePrice($gold, 1);
					$soul = $this->checkUpgradePrice($soul);
					
					if($this->getEntityStats(2)[0] >= 10000000 || $this->getEntityStats(2)[1] >= 10000 || $upgrade > 1000) {
						$gold = 0;
						$soul = 0;
					}
					
					$ret[] = $gold;
					$ret[] = $soul;
					
					if($unit == 2) {
						return [$gold, $soul];
					}
					
				break;
				case 2:
					$upgrade = $this->data['unit_keeper'];
					$upgrade ++;
					
					$ret[] = $upgrade;
			
					$gold = round(239 * $upgrade * (1 + 0.76 * $upgrade));
					$soul = round(24 * $upgrade * (1 + 0.64 * $upgrade));
					
					$gold = $this->checkUpgradePrice($gold, 1);
					$soul = $this->checkUpgradePrice($soul);
					
					if($this->getEntityStats(3)[0] >= 10000000 || $this->getEntityStats(3)[1] >= 10000 || $upgrade > 1000) {
						$gold = 0;
						$soul = 0;
					}	
					
					$ret[] = $gold;
					$ret[] = $soul;
					
					if($unit == 3) {
						return [$gold, $soul];
					}
					
				break;
			
			}		
		}	
		
		return implode("/", $ret);
		
		//return "7/1900/950/8/2300/1150/26/13300/6650/";
	}
	
	public function getBuilding($id) {
		switch($id) {
			case 1:
				return "heart";
			break;
			case 2:
				return "gate";
			break;
			case 3:
				return "gold";
			break;
			case 4:
				return "extractor";
			break;
			case 5:
				return "goblin";
			break;
			case 6:
				return "torture";
			break;
			case 7:
				return "gladiator";
			break;
			case 8:
				return "troll";
			break;
			case 9:
				return "time";
			break;
			case 10:
				return "keeper";
			break;
		}	
	}

	public function startBuilding($id) {
		
		$unw = $this->data;
		
		if($id > 10) {
			exit('&Error:');
		}
		
		$b = $this->getBuilding($id);
		
		//Stop building, if its 15 lvl
		if($unw[$b] >= 15) {
			exit('&Error:');
		}
		
		$build_time = $this->getBuildingTime($unw[$b] + 1);
		
		$soul_price = $this->getBuildingPrice($id, $unw[$b] + 1);
		
		//Remove soul price
		if($unw['soul'] >= $soul_price[1]) {
			$unw['soul'] -= $soul_price[1];
			$GLOBALS['db']->exec("UPDATE underworld SET soul = ".$unw['soul']." WHERE owner = ".$unw['owner']);
		} else {
			exit('&Error:need more souls');
		}
		
		//Start building
		$unw['build_id'] = $id;
		$unw['build_start'] = $GLOBALS["CURRTIME"];
		$unw['build_end'] = $GLOBALS["CURRTIME"] + $build_time;
		
		$GLOBALS['db']->exec("UPDATE underworld SET build_id = ".$unw['build_id'].", build_start = ".$unw['build_start'].", 
			build_end = ".$unw['build_end']." WHERE owner = ".$unw['owner']);
			
		$GLOBALS["RENEW"] = true;	
	
	}
	
	public function finishBuilding($id) {
		$qry = [];
		$b = $this->getBuilding($id);
		$unw = $this->data;
		
		$unw['build_id'] = 0;
		$unw['build_start'] = 0;
		$unw['build_end'] = 0;
		
		$unw[$b] ++;
		
		$qry[] = "UPDATE underworld SET build_id = 0, build_start = 0, 
			build_end = 0 WHERE owner = ".$unw['owner'];
		
		$qry[] = "UPDATE underworld SET ".$b." = ".$unw[$b]." WHERE owner = ".$unw['owner'];
		
		$GLOBALS['db']->exec(join(';', $qry));
		
		$GLOBALS["RENEW"] = true;
	}

	public function stopBuilding() {
		$this->data['build_id'] = 0;
		$this->data['build_start'] = 0;
		$this->data['build_end'] = 0;
		
		$GLOBALS['db']->exec("UPDATE underworld SET build_id = 0, build_start = 0, 
			build_end = 0 WHERE owner = ".$this->data['owner']);
			
		$GLOBALS["RENEW"] = true;		
		
	}

	public function getMaxSouls() {
		$heart = $this->data['heart'];
		$extractor = $this->data['extractor'];
		
		$soul = 0;
		$soul += (15000 * $heart) + (round(round($heart / 0.5) * (14000 + $heart * 7100)));
		$soul += (20000 * $extractor) + (round(round($extractor / 0.4) * (16000 + $extractor * 9300)));
		$soul = ($soul - 37200) / 10;
		
		$max = $GLOBALS['uwMaxSoul'];
		
		if(!isset($max) || $max < 1)
			$max = 10;
		
		$soul = round($soul * $max);
		
		if($soul > 1000000 * $max)
			$soul = 1000000 * $max;
		
		if($soul < 0)
			$soul = 0;

		return $soul;
	}
	
	//Data from http://www.4m7.de/sammelalbum/unterwelt.php
	public function getBuildingPrice($id, $lvl) {
		switch ($id) {
			case 1:
			//return [gold, soul]
			switch($lvl) {
				//case 1: return [10000, 0];
				case 1: return [0, 0];
				case 2: return [200000, 616];
				case 3: return [300000, 1650];
				case 4: return [400000, 4220];
				case 5: return [500000, 11000];
				case 6: return [600000, 25080];
				case 7: return [700000, 45930];
				case 8: return [800000, 84150];
				case 9: return [900000, 198000];
				case 10: return [1000000, 439550];
				case 11: return [1100000, 902850];
				case 12: return [1200000, 2043300];
				case 13: return [1300000, 4118400];
				case 14: return [1400000, 7722000];
				case 15: return [1500000, 16632000];
			}
			case 2:
			switch($lvl) {
				case 1: return [50000, 0];
				case 2: return [100000, 55];
				case 3: return [150000, 149];
				case 4: return [200000, 380];
				case 5: return [250000, 990];
				case 6: return [300000, 2255];
				case 7: return [350000, 4820];
				case 8: return [400000, 10090];
				case 9: return [450000, 26730];
				case 10: return [500000, 65930];
				case 11: return [550000, 135400];
				case 12: return [600000, 306500];
				case 13: return [650000, 617750];
				case 14: return [700000, 1158300];
				case 15: return [750000, 2494800];
			}
			case 3:
			switch($lvl) {
				case 1: return [100000, 12];
				case 2: return [200000, 46];
				case 3: return [300000, 124];
				case 4: return [400000, 317];
				case 5: return [500000, 825];
				case 6: return [600000, 1880];
				case 7: return [700000, 4015];
				case 8: return [800000, 8415];
				case 9: return [900000, 19800];
				case 10: return [1000000, 43950];
				case 11: return [1100000, 90280];
				case 12: return [1200000, 204300];
				case 13: return [1300000, 411800];
				case 14: return [0, 0];
				case 15: return [0, 0];
			}
			case 4:
			switch($lvl) {
				case 1: return [25000, 0];
				case 2: return [50000, 462];
				case 3: return [75000, 1235];
				case 4: return [100000, 3165];
				case 5: return [125000, 8250];
				case 6: return [150000, 18810];
				case 7: return [175000, 40190];
				case 8: return [200000, 84150];
				case 9: return [225000, 198000];
				case 10: return [250000, 439550];
				case 11: return [275000, 902850];
				case 12: return [300000, 2043300];
				case 13: return [325000, 4118400];
				case 14: return [350000, 7722000];
				case 15: return [375000, 16632000];
			}
			case 5:
			switch($lvl) {
				case 1: return [40000, 0];
				case 2: return [80000, 396];
				case 3: return [120000, 1060];
				case 4: return [160000, 2715];
				case 5: return [200000, 7070];
				case 6: return [240000, 16120];
				case 7: return [280000, 34450];
				case 8: return [320000, 63110];
				case 9: return [360000, 148500];
				case 10: return [400000, 329650];
				case 11: return [440000, 677150];
				case 12: return [480000, 1532500];
				case 13: return [0, 0];
				case 14: return [0, 0];
				case 15: return [0, 0];
			}
			case 6:
			switch($lvl) {
				case 1: return [66000, 148];
				case 2: return [132000, 554];
				case 3: return [198000, 1485];
				case 4: return [264000, 3800];
				case 5: return [330000, 9900];
				case 6: return [396000, 22570];
				case 7: return [462000, 41340];
				case 8: return [528000, 75730];
				case 9: return [594000, 178200];
				case 10: return [660000, 395600];
				case 11: return [726000, 812550];
				case 12: return [792000, 1389000];
				case 13: return [0, 0];
				case 14: return [0, 0];
				case 15: return [0, 0];
			}
			case 7:
			switch($lvl) {
				case 1: return [70000, 148];
				case 2: return [140000, 554];
				case 3: return [210000, 1485];
				case 4: return [280000, 3800];
				case 5: return [350000, 9900];
				case 6: return [420000, 22570];
				case 7: return [490000, 48230];
				case 8: return [560000, 100950];
				case 9: return [630000, 237600];
				case 10: return [700000, 527450];
				case 11: return [770000, 1083400];
				case 12: return [840000, 2452000];
				case 13: return [0, 0];
				case 14: return [0, 0];
				case 15: return [0, 0];
			}
			case 8:
			switch($lvl) {
				case 1: return [99000, 165];
				case 2: return [198000, 616];
				case 3: return [297000, 1650];
				case 4: return [396000, 4220];
				case 5: return [495000, 11000];
				case 6: return [594000, 25080];
				case 7: return [693000, 45930];
				case 8: return [792000, 84150];
				case 9: return [891000, 198000];
				case 10: return [990000, 439550];
				case 11: return [0, 0];
				case 12: return [0, 0];
				case 13: return [0, 0];
				case 14: return [0, 0];
				case 15: return [0, 0];
			}
			case 9:
			switch($lvl) {
				case 1: return [1000000, 297];
				case 2: return [2000000, 1105];
				case 3: return [3000000, 2970];
				case 4: return [4000000, 5700];
				case 5: return [5000000, 11880];
				case 6: return [6000000, 22570];
				case 7: return [7000000, 41340];
				case 8: return [8000000, 75730];
				case 9: return [9000000, 178200];
				case 10: return [10000000, 395600];
				case 11: return [11000000, 812550];
				case 12: return [12000000, 1839000];
				case 13: return [0, 0];
				case 14: return [0, 0];
				case 15: return [0, 0];
			}
			case 10:
			switch($lvl) {
				case 1: return [150000, 198];
				case 2: return [300000, 739];
				case 3: return [450000, 1980];
				case 4: return [600000, 5065];
				case 5: return [750000, 13200];
				case 6: return [900000, 25080];
				case 7: return [1050000, 45930];
				case 8: return [1200000, 84150];
				case 9: return [1350000, 198000];
				case 10: return [1500000, 439550];
				case 11: return [1650000, 902850];
				case 12: return [1800000, 2043300];
				case 13: return [1950000, 4118400];
				case 14: return [2100000, 7722000];
				case 15: return [0, 0];
			}
		}	
		
	}
	
	public function getBuildingTime($lvl) {
		switch($lvl) {
			case 1: return 225;
			case 2: return 474;
			case 3: return 1000;
			case 4: return 2118;
			case 5: return 4500;
			case 6: return 7200;
			case 7: return 10260;
			case 8: return 16560;
			case 9: return 36000;
			case 10: return 78540;
			case 11: return 172800;
			case 12: return 288000;
			case 13: return 432000;
			case 14: return 554400;
			case 15: return 720000;
		}	
	}

	//Data from http://hu.4m7.de/sammelalbum/unterwelt.php
	public function getSoulExtractorResources($lvl) {
		switch($lvl) {
			//return [per hour, max]
			case 1: return [165, 412];
			case 2: return [231, 635];
			case 3: return [330, 990];
			case 4: return [528, 1716];
			case 5: return [825, 2887];
			case 6: return [1254, 4702];
			case 7: return [1914, 7656];
			case 8: return [2805, 14025];
			case 9: return [4125, 24750];
			case 10: return [6105, 48840];
			case 11: return [9405, 94050];
			case 12: return [14190, 170280];
			case 13: return [21450, 343200];
			case 14: return [32175, 643500];
			case 15: return [49500, 1000000];
			//case 15: return [49500, 0];
		}	
	}
	
	public function checkHourlyGold($gold) {
		if($gold > 90000000) {
			$gold = 90000000;
		}	
			
		return $gold;
	}
	
	public function checkUpgradePrice($price, $id = 0) {
		$max = 10000000;
		
		if($id == 1)
			$max *= 100; // Gold
		
		if($price > $max) {
			$price = $max;
		}
		
		$price = Account::getGold($price, 1000);
		
		return $price;
	}	

	public function newHourlyGold($lvl) {
		
		// Set gold claim
		// Gold lvl 1
		if($this->data["gold"] == 0) {
			//$this->data["claim_gold"] = Account::getQuestGold(rand(98, 100), $goldbonus) / 3;
				
			$this->data["claim_gold"] = Account::getQuestGold($lvl, $GLOBALS['goldbonus']) / 2;
			$this->data["claim_gold"] = $this->checkHourlyGold($this->data["claim_gold"]);
				
		} else { // Gold lvl above 1
			//$this->data["claim_gold"] = Account::getQuestGold(rand (98, 100), $goldbonus) * ($this->data["gold"]);
			
			$this->data["claim_gold"] = Account::getQuestGold($lvl, $GLOBALS['goldbonus']) * ($this->data["gold"] + 2);
			$this->data["claim_gold"] = $this->checkHourlyGold($this->data["claim_gold"]);	
			
		}

		$GLOBALS['db']->exec("UPDATE underworld SET claim_gold = ".$this->data["claim_gold"]." WHERE owner = ".$this->data['owner']);
	}

	public function getUnitSave($id = 0) {
		// New saving v2
		
		//return array [goblin upgrade, goblin lvl, goblin stats, troll up, troll lvl, troll stats, keeper up, keeper lvl, keeper stats]
		$ret = [];
	
		$goblinsUpgrade = $this->data['unit_goblins'];
		$trollsUpgrade = $this->data['unit_trolls'];
		$keeperUpgrade = $this->data['unit_keeper'];
		
		// Fix units null data
		if($goblinsUpgrade == null)
			$goblinsUpgrade = 0;
		
		if($trollsUpgrade == null)
			$trollsUpgrade = 0;
		
		if($keeperUpgrade == null)
			$keeperUpgrade = 0;
		
		$goblins = [$goblinsUpgrade, $this->getEntityStats(1)[1], $this->getEntityStats(1)[0]];
		$trolls = [$trollsUpgrade, $this->getEntityStats(2)[1], $this->getEntityStats(2)[0]];
		$keeper = [$keeperUpgrade, $this->getEntityStats(3)[1], $this->getEntityStats(3)[0]];
		
		//Add to ret
		foreach($goblins as $unit)
			$ret[] = $unit;
		foreach($trolls as $unit)
			$ret[] = $unit;
		foreach($keeper as $unit)
			$ret[] = $unit;	
			
		if($id == 0)
			return $ret;
		else if($id == 1)
			return $goblins;
		else if($id == 2)
			return $trolls;
		else if($id == 3)
			return $keeper;
	}
	
	public function getEntityStats($unit) {
		//return array [stats, lvl]
		
		$uw = $this->data;
		
		switch($unit) {
			case 1: // Goblins
				$goblin = $uw['goblin'];
				$upgraded = $uw['unit_goblins'];
				
				$stats = floor((8 * ($goblin * (10.36 + round($goblin / 3))) + round($upgraded * 2.74)) / 3);
				$lvl = round($goblin * round($goblin / 0.83) + ($upgraded * 2.45));
				
				$lvl += 9;
				
				return[$stats, $lvl];
			break;
			case 2: // Trolls
				
				$troll = $uw['troll'];
				$upgraded = $uw['unit_trolls'];
				
				$stats = floor((11 * ($troll * (14.36 + round($troll / 3))) + round($upgraded * 5.91)) / 2.4);
				$lvl = round($troll * round($troll / 0.73) + ($upgraded * 2.45));
				
				$lvl += 13;
				
				return[$stats, $lvl];
			break;
			case 3: // Keeper
				$keeper = $uw['keeper'];
				$upgraded = $uw['unit_keeper'];
				
				$stats = floor((14 * ($keeper * (17.36 + round($keeper / 3))) + round($upgraded * 9.28)) / 2);
				$lvl = round($keeper * round($keeper / 0.63) + ($upgraded * 2.45));
				
				$lvl += 27;
				
				return[$stats, $lvl];
			break;
			
		}
		
	}	
	
	public function getUnderworldUnits() {
		$uw = $this->data;
		$defenders = [];
		$units = [];
		
		$stat = $this->getUnitSave();
		
		for($i = 0; $i < 3; $i++) {
			//($lvl, $class, $str, $agi, $int, $wit, $luck, $dmg_min, $dmg_max, $hp, $armor, $id, $exp, $weapon_id, $shield_id = 0)
			//return array [goblin upgrade, goblin lvl, goblin stats, troll up, troll lvl, troll stats, keeper up, keeper lvl, keeper stats]
			
			//Check building lvl 0 = no unit
			if($i == 0 && $uw["goblin"] <= 0)
				continue;
			
			if($i == 1 && $uw["troll"] <= 0)
				continue;
			
			if($i == 2 && $uw["keeper"] <= 0)
				continue;
			
			$u = []; 
			
			$u[] = $stat[$i * 3];
			$u[] = $stat[($i * 3) + 1];
			$u[] = $stat[($i * 3) + 2];
			
			$id = $this->getUnitID($i);
			
			$luck = floor($u[2] / 2);
			
			$weaponMultiplier = 4.25;
			$lvl = $u[1];
			
			$hp = $u[2] * 2 * ($lvl + 1);
			
			$dmg = 1 + floor($weaponMultiplier * $lvl);
			$minmax = 2 + floor($lvl * 55 / 100);
			$dmg_min = ($dmg - $minmax) * floor(1 + $u[2] / 10);
			$dmg_max = ($dmg + $minmax) * floor(1 + $u[2] / 10);
			
			//TODO: add more weapons
			//goblin: 09 weapon; keeper: 08
			
			if($i == 0) // goblin
				$weapon = 29; 
			else if($i == 1) // troll
				$weapon = 22;
			else // keeper
				$weapon = 20;
			
			$defenders[$i] = new FortressMonster($u[1], 1, $u[2], $u[2], $u[2], $u[2], $luck, $dmg_min, $dmg_max, $hp, 0, $id[0], 0, $weapon, 0);
			
			
		}
		
		$goblins = $this->getUnitID(0)[1];
		$trolls = $this->getUnitID(1)[1];
		$keeper = $this->getUnitID(2)[1];

		// Add goblins
		if($goblins > 1) {
			
			for($i = 0; $i < $goblins; $i++)
			{
				$add = unserialize(serialize($defenders[0]));
			
				$add->currCount = $i + 1;
			
				$add->allCount = $goblins;
			
				$units[] = $add;
			}
			
		} else if ($goblins == 1){
			$units[] = $defenders[0];
		}
		
		
		// Add trolls
		if($trolls > 1) {
			
			for($i = 0; $i < $trolls; $i++)
			{
				$add = unserialize(serialize($defenders[1]));
			
				$add->currCount = $i + 1;
			
				$add->allCount = $trolls;
			
				$units[] = $add;
			}
			
		}  else if ($trolls == 1) {
			$units[] = $defenders[1];
		}
		
		// Add keeper
		if($keeper > 1) {
			
			for($i = 0; $i < $keeper; $i++)
			{
				$add = unserialize(serialize($defenders[2]));
			
				$add->currCount = $i + 1;
			
				$add->allCount = $keeper;
			
				$units[] = $add;
			}
			
		} else if ($keeper == 1) {
			$units[] = $defenders[2];
		}
		
		return $units;
		
	}

	public function getUnitID($id) {
		$uw = $this->data;
		
		//return array [id, count]
		
		switch($id) {
			case 0: // Goblins
				switch($uw["goblin"]){
					case 0: return [0, 0];
					case 1: return [900, 1];
					case 2: return [900, 2];
					case 3: return [900, 3];
					case 4: return [900, 4];
					case 5: return [900, 5];
					case 6: return [901, 5];
					case 7: return [902, 5];
					case 8: return [903, 5];
					case 9: return [904, 5];
					case 10: return [905, 5];
					case 11: return [906, 5];
					case 12: return [907, 5];
					case 13: return [908, 5];
					case 14: return [909, 5];
					case 15: return [910, 5];
				}	
			break;
			case 1: // Trolls
				switch($uw["troll"]) {
					//case 1: return [920, 1];
					case 0: return [0, 0];
					case 1: return [921, 1];
					case 2: return [922, 1];
					case 3: return [923, 1];
					case 4: return [924, 1];
					case 5: return [925, 1];
					case 6: return [926, 1];
					case 7: return [927, 1];
					case 8: return [928, 1];
					case 9: return [929, 2];
					case 10: return [930, 2];
					case 11: return [931, 2];
					case 12: return [932, 2];
					case 13: return [933, 3];
					case 14: return [934, 3];
					case 15: return [935, 4];
				}	
			break;
			case 2: // Keeper
				if($uw["keeper"] <= 0)
					return [0, 0];
				
				$id = 940 + ($uw["keeper"] - 1);
				return [$id, 1];
			break;
		}	
	}

	private function generateTimeEnemy($length){
		global $acc;
		
		$xpbonus = $GLOBALS['xpbonus'];
		$xpGenVersion = $GLOBALS['xpGenVersion'];
		
		$exp = Account::getQuestExp($acc->data["lvl"], $xpbonus, $xpGenVersion, ($length / 5));
		$exp = round($exp / 20);
		
		$realStats = $acc->getRealStats();
		
		$w = $realStats["wit"];
		$weap = $acc->getWeapon();
		
		if($weap == null) {
			$weap = $acc->getFist();
		}

		$weap_id = -1;
		//$weap_id = $weap->id;
		
		$prim = "dex";
		
		switch($acc->data["class"]){
			case 1: $prim = "str"; break;
			case 2: $prim = "intel"; break;
		}
		
		$prim = $realStats[$prim] * ((15 + $length) / 100);
		
		$dmg_min = round($weap->dmg_min * (1 + ($prim / 10)));
		$dmg_max = round($weap->dmg_max * (1 + ($prim / 10)));
		
		$hp = round($w * 4 * ($acc->data['lvl'] + 1) / 25);
		
		$monsterID = rand(1, 163);

		$monster = new Monster($acc->data['lvl'], 2, $realStats["str"], $realStats["dex"], $realStats["intel"], $realStats["wit"], $realStats["luck"], $dmg_min, $dmg_max, $hp, 1, -$monsterID, 1, $weap_id);
	
		return [$exp, $monster];
	}
	
	public function gatherTimeMachine(){
		global $acc, $ret, $db;
		
		if($this->data["timeamount"] == 0)
			exit();
		
		$allxp = 0;
		$monsters = [];
		
		$time = $this->data["timeamount"];
		
		while($time > 0 && count($monsters) < 10){
			$length = rand(2, 4) * 5;
			
			if($time < 20)
				$length = $time;
			
			$round = $this->generateTimeEnemy($length);
			
			$allxp += $round[0];
			$monsters[] = $round[1];
			
			$time -= $length;
		}
		
		$simulation = new GroupSimulation([$acc], $monsters);
		$simulation->simulate();
		
		for($i = 0; $i < count($simulation->simulations); $i++){
			$fight = $i+1;
			$ret[] = "fightheader".$fight.".fighters:1/0/0/22/2/".$simulation->fightHeaders[$i];
			$ret[] = "fight".$fight.".r:".$simulation->simulations[$i]->fightLog;
			$ret[] = "winnerid".$fight.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$ret[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		
		$qryArgs[] = "quest_dur3 = quest_dur3";

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		//rewarding
		if($simulation->win){
			
			//win true
			$rewardLog[0] = 1;
			//silver
			$rewardLog[2] = 0;
			//exp
			$rewardLog[3] = $allxp;

			$acc->addExp($rewardLog[3]);

			$qryArgs[] = "exp = ".$acc->data['exp'];
			$qryArgs[] = "lvl = ".$acc->data['lvl'];
		}
		
		$this->data["timeamount"] = $time;
		
		$db->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$acc->data['ID'] . ";UPDATE underworld SET timeamount = ".$this->data["timeamount"]." WHERE owner = ".$this->data['owner']);
		
		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
	}
	
	public function getTimeMachineThirst() {
		$time = $this->data['time'];
		
		//return [collect, max]
		
		switch($time) {
			case 0: return [0, 0];
			case 1: return [4, 100];
			case 2: return [8, 200];
			case 3: return [12, 300];
			case 4: return [16, 400];
			case 5: return [20, 500];
			case 6: return [24, 600];
			case 7: return [28, 700];
			case 8: return [32, 800];
			case 9: return [36, 900];
			case 10: return [40, 1000];
			case 11: return [48, 1200];
			case 12: return [56, 1400];
			case 13: return [64, 1600];
			case 14: return [72, 1800];
			case 15: return [80, 2000];
			
		}
	}
	
	public function getGladiatorBonus() {
		//data: http://hu.4m7.de/sammelalbum/unterwelt.php 
		//return crit damage bonus in %
		
		$dmg = floor($this->data['gladiator'] * 5);
		return $dmg;
		
	}	
}