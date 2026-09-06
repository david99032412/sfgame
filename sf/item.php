<?php

class Item{


	public $raw;

	public $forClass;

	public $slot;

	//public $color;
	public $type;
	public $id;
	public $dmg_min;
	public $dmg_max;
	
	public $stats;

	public $cost;
	public $costMush;

	public $isEpic;

	public $gemSlot;
	public $gem = array(
		"type" => 0,
		"val" => 0);

	public $enchanted;
	public $enchant = array(
		"type" => 0,
		"power" => 0);
		
	public $upgrades;
	
	function __construct($data){
		$this->raw = $data;

		//bad planning on the fly... 
		//fortress backpack 100 offset
		//equip offset 10
		//shop offset 20 and 30
		//copycat equip offset 50, 60, 70
		if($data['slot'] >= 100)
			$this->slot = $data['slot'] - 100;
		else if($data['slot']>= 20 && $data['slot'] <=31)
			$this->slot = $data['slot'] % 20;
		else 
			$this->slot = $data['slot'] % 10;
		
		// if type > 65536 -> has slot
		if ($data['type'] % 16777216 > 65535) {
			$this->gemSlot = true;

			$this->gem['type'] = floor($data['type'] % 16777216 / 65536);
			$this->gem['val'] = floor($data['value_mush'] / 65536);
		}


		//if id/65536>1 -> enchanted
		if($data['type'] > 16777216){
			$this->enchanted = true;

			$this->enchant['type'] = floor($data['type'] / 16777216);
			$this->enchant['power'] = floor($data['item_id'] / 65536);
		}



		$this->type = $data['type'] % 65536;
		$this->id = $data['item_id'] % 65536;
		$this->dmg_min = $data['dmg_min'];
		$this->dmg_max = $data['dmg_max'];

		//stats
		$this->setStats();

		$this->cost = $data['value_silver'];
		$this->costMush = $data['value_mush'] % 65536;
		
		// Get upgrades then remove it from mush cost - Greg's fix
		
		$this->upgrades = floor($this->costMush / 256);
		
		$this->costMush %= 256;

		// if not accessory, set forclass
		if ($this->type <= 7){
			$this->forClass = floor($this->id / 1000) + 1;
			$this->id = $this->id % 1000;
		}else{
			$this->forClass = 0;
		}

		if ($this->id >= 50) {
			$this->isEpic = true;
		}
		
		//if you want to this, just make a getter
		//#if(!isEpic && $this->type < 10)
		//	$this->color = (dmg_min + dmg_max + attr_t1 + attr_t2 + attr_t3 + attr_v1 + attr_v2 + attr_v3) % 5;
	}

	public function getColor(){
		return ($this->raw['dmg_min'] + $this->raw['dmg_max'] + $this->raw['a1'] + $this->raw['a2'] + $this->raw['a3'] + $this->raw['a4'] + $this->raw['a5'] + $this->raw['a6']) % 5;
	}

	//set stats in a function for setting gem, incase there was already a gem, delete the stats it gave (for response)
	private function setStats(){

		$this->stats = 
		["str" => "0",
		"dex" => "0",
		"int" => "0",
		"wit" => "0",
		"luck" => "0"];

		//regular stats
		for($i = 1; $i <= 3; $i++){
			$attrIndex = $this->raw['a'.$i];
			if($attrIndex == 6){
				$this->stats["str"] = $this->raw['a4'];
				$this->stats["dex"] = $this->raw['a4'];
				$this->stats["int"] = $this->raw['a4'];
				$this->stats["wit"] = $this->raw['a4'];
				$this->stats["luck"] = $this->raw['a4'];
			}else if($attrIndex > 0)
				$this->stats[Entity::getStatName($attrIndex)] = $this->raw['a'.(3+$i)];
		}

		//gem stats
		if($this->gemSlot && $this->gem['val'] > 0){
			// var_dump($this->gem );
			if($this->gem['type'] > 1 && ($this->gem['type']+1) % 10 == 6){
				$this->stats["str"] += $this->gem['val'];
				$this->stats["dex"] += $this->gem['val'];
				$this->stats["int"] += $this->gem['val'];
				$this->stats["wit"] += $this->gem['val'];
				$this->stats["luck"] += $this->gem['val'];
			}else{
				$this->stats[Entity::getStatName( ($this->gem['type']+1) % 10 )] += $this->gem['val'];
			}
		}
	}

	public function getSave(){
		//return explode("/", "2097159/1010/856/0/3/2/5/672/0/0/31002671/19922944");
		return [$this->raw['type'], 
			$this->raw['item_id'],
			$this->raw['dmg_min'],
			$this->raw['dmg_max'], 
			$this->raw['a1'], 
			$this->raw['a2'], 
			$this->raw['a3'], 
			$this->raw['a4'], 
			$this->raw['a5'], 
			$this->raw['a6'], 
			$this->raw['value_silver'], 
			$this->raw['value_mush']];
	}
	
	public function enchant(){
		//update $this->raw only - for getSave()


		$this->enchant['type'] = ($this->type * 10 + 1);
		$this->enchant['power'] = 10;
		
		// TODO: better enchants

		$this->raw['type'] = $this->type + ($this->gem['type'] * 65536) + ($this->enchant['type'] * 16777216);


		//enchant power 
		$this->raw['item_id'] += $this->enchant['power'] * 65536;

		$GLOBALS['db']->exec('UPDATE items SET type = '.$this->raw['type'].', item_id = '.$this->raw['item_id'].' WHERE ID = '.$this->raw['ID']);
	}

	public function setGem($gem){
		//here: modify this item with gem
		//remove gem from db, update this item db

		$this->gem['type'] = $gem->id;
		$this->gem['val'] = floor($gem->raw['value_mush'] / 65536);

		//raw = type + (gem*65536) + (ench*16777216)

		$this->raw['type'] = $this->type + ($this->gem['type'] * 65536) + ($this->enchant['type'] * 16777216);
		$this->raw['value_mush'] = ($this->gem['val'] * 65536) + ($this->upgrades * 256); // Greg's fix for BS

		//set stats, used for displaying after setting gem?
		$this->setStats();

		//update item
		$GLOBALS['db']->exec("UPDATE items SET type = ".$this->raw['type'].", value_mush = ".$this->raw['value_mush']." WHERE ID = ".$this->raw['ID']);

		//delete gem
		$GLOBALS['db']->exec("DELETE FROM items WHERE ID = ".$gem->raw['ID']);
	}

	public function move($target, $slot, $rawTargetSlot){
		$slot--;
		if($target == 1 || $target > 100)
			$this->slot = Item::getEquipSlot($this->type);
		else
			$this->slot = $slot;

		
		$GLOBALS['db']->exec("UPDATE items SET slot = $rawTargetSlot WHERE ID = ".$this->raw['ID']);
	}

	public function buy($target, $slot, $rawTargetSlot){
		$slot--;
		if($target == 1)
			$this->slot = Item::getEquipSlot($this->type);
		else
			$this->slot = $slot;

		// $this->raw['slot'] = $rawTargetSlot;
		// var_dump($slot);


		//decrease value
		$this->cost = floor($this->cost / 3);
		
		$this->raw['value_silver'] = $this->cost;
		$this->raw['value_mush'] = 0;

		//NOTICE: IF YOU SET GEMS IN SHOPS, THEIR STAT VALUES WILL NULLIFY BECOUSE OF MUSH VALUE HERE
		$GLOBALS['db']->exec("UPDATE items SET slot = $rawTargetSlot, value_mush = 0, value_silver = $this->cost WHERE ID = ".$this->raw['ID']);
	}

	//flush down the toilet
	public function flush($lvl){
		$qry = ['value_silver = 0'];
		$this->raw['value_silver'] = 0;
		$oldClass = $this->forClass;
		$newClass = rand(1, 3);
		
		//switch class
		if($this->type <= 7 && $this->type != 2){
			$this->raw['item_id'] -= ($this->forClass -1) * 1000;
			$this->forClass = $newClass;
			$this->raw['item_id'] += ($this->forClass - 1) * 1000;
			
			//armor
			// Armor system by Greg
			$this->raw['dmg_min'] = 1;
			if ($newClass == 1){ // Warrior
				if ($this->type == 3){
					$this->raw['dmg_min'] = $lvl * 15 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 4){
					$this->raw['dmg_min'] = $lvl * 7 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 5){
					$this->raw['dmg_min'] = $lvl * 12 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 6){
						$this->raw['dmg_min'] = $lvl * 13 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 7){
					$this->raw['dmg_min'] = $lvl * 12 * rand(1.5, 1.7) + rand(1, 6);
				}
			}
			if ($newClass == 2){ // Mage
				if ($this->type == 3){
					$this->raw['dmg_min'] = $lvl * 3 * rand(1.5, 1.7)+ rand(1, 6);
				}
				if ($this->type == 4){
					$this->raw['dmg_min'] = round($lvl * 2 * rand(1.5, 1.7) + rand(1, 6));
				}
				if ($this->type == 5){
					$this->raw['dmg_min'] = $lvl * 3 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 6){
					$this->raw['dmg_min'] = $lvl * 2 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 7){
					$this->raw['dmg_min'] = $lvl * 2 * rand(1.5, 1.7) + rand(1, 6);
				}
			}
			if ($newClass == 3){ // Hunter
				if ($this->type == 3){
					$this->raw['dmg_min'] = $lvl * 6 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 4){
					$this->raw['dmg_min'] = round($lvl * 5 * rand(1.5, 1.7) + rand(1, 6));
				}
				if ($this->type == 5){
					$this->raw['dmg_min'] = $lvl * 3 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 6){
					$this->raw['dmg_min'] = $lvl * 4 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 7){
					$this->raw['dmg_min'] = $lvl * 5 * rand(1.5, 1.7) + rand(1, 6);
				}
			}
			
			if($newClass == 4) { // Assassin
				if ($this->type == 3){
					$this->raw['dmg_min'] = $lvl * 6 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 4){
					$this->raw['dmg_min'] = round($lvl * 5 * rand(1.5, 1.7) + rand(1, 6));
				}
				if ($this->type == 5){
					$this->raw['dmg_min'] = $lvl * 3 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 6){
					$this->raw['dmg_min'] = $lvl * 4 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($this->type == 7){
					$this->raw['dmg_min'] = $lvl * 5 * rand(1.5, 1.7) + rand(1, 6);
				}
			}
		}
		
		$qry[] = 'dmg_min = '.$this->raw['dmg_min'];

		$this->raw['item_id'] -= $this->id;
		if($this->isEpic){
			$this->id = rand(50, 58);

			if($this->raw['a1'] <  6){
				if($this->type < 8)
					$this->raw['a1'] = [1, 3, 2][$this->forClass - 1];
				else
					$this->raw['a1'] = rand(1, 3);
				
				$qry[] = 'a1 = '.$this->raw['a1'];
			}

		}else{
			$this->id = rand(1, 10);


			//TODO: check if double stat
			$this->raw['a1'] = rand(1, 5);
			$qry[] = 'a1 = '.$this->raw['a1'];
				
		}
		$this->raw['item_id'] += $this->id;
		$qry[] = 'item_id = '.$this->raw['item_id'];

		//adjust weapon dmg range
		//weapon multiplier
		if($this->type == 1){
			$weaponMultipliers = $GLOBALS["weaponMultipliers"];

			$wmOld = $weaponMultipliers[$oldClass - 1];
			$wmNew = $weaponMultipliers[$this->forClass - 1];

			$this->raw['dmg_min'] = round($this->raw['dmg_min'] / $wmOld * $wmNew);
			$this->raw['dmg_max'] = round($this->raw['dmg_max'] / $wmOld * $wmNew);

			$qry[] = 'dmg_min = '.$this->raw['dmg_min'];
			$qry[] = 'dmg_max = '.$this->raw['dmg_max'];

		}

		$GLOBALS['db']->exec('UPDATE items SET '.join(',', $qry).' WHERE ID = '.$this->raw['ID']);
	}
	
	// upgrades item by Greg
	public function upgradeItem()
	{
		$qryArr = [];
		
		if ($this->type <= 7)
		{
			// Damage or armor upgrade
			if ($this->type == 1)
			{
				$this->raw["dmg_max"] = floor($this->raw["dmg_max"] * 1.05);
				$qryArr[] = "dmg_max = " . $this->raw["dmg_max"];
			}
			else if ($this->type > 1)
			{
				$this->raw["dmg_min"] = floor($this->raw["dmg_min"] * 1.05);
				$qryArr[] = "dmg_min = " . $this->raw["dmg_min"];
			}
		}
		
		for($i = 1; $i <= 3; $i++)
		{
			if ($this->raw["a$i"] != 0)
			{
				$this->raw["a" . ($i + 3)] = floor($this->raw["a" . ($i + 3)] * 1.03);
				$qryArr[] = "a" . ($i + 3) . " = " . $this->raw["a" . ($i + 3)];
			}
		}
		
		return implode(", ", $qryArr);
	}

	//returns raw/source slot, for moving items
	public static function getItemSlot($target, $slot, $type = 0){
		$slot--;

		switch($target){
			case 1:
				//equip, return slot for item, new func

				//if slot -1, equiping, target from type
				if($type == 0 || $type >= 14 )
					$slot += 10;
				else
					$slot = 10 + Item::getEquipSlot($type);

				//var_dump($slot);
				//exit();
				break;
			case 2:
				//do nothing, same as given -1
				break;
			case 3:
				$slot += 20;
				break;
			case 4:
				$slot += 26;
				break;
			case 5:
				$slot += 100;
				break;
			case 101:
			case 102:
			case 103:
				$target-=101;
				if($type == 0 || $type >= 14 )
					$slot += ($target*10) + 50;
				else
					$slot = ($target*10) + 50 + Item::getEquipSlot($type);

				break;
		}

		return $slot;
	}

	public static function getEquipSlot($type){
		$type = $type;
		switch($type){
			// weapon
	        case 1:
	            return 8;
	        // shield
	        case 2:
	            return 9;
	        // chest
	        case 3:
	            return 1;
	        // boots
	        case 4:
	            return 3;
	        // gloves
	        case 5:
	            return 2;
	        // helmet
	        case 6:
	            return 0;
	        // belt
	        case 7:
	            return 5;        
	        // necklace
	        case 8:            
	            return 4;        
	        // ring
	        case 9:            
	            return 6;        
	        // relic
	        case 10:
	            return 7;
        }
    }

    //NEW GEN ITEM FUNCTION
    public static function genItem($type, $lvl, $class, $epicCa = 0, $mineLvl = 0, $place = 'shop'){
		if($type >= 1000000){
			$type -= 1000000;
			$place = "other";
		}
		
    	$epicChance = $GLOBALS['epicbonus'];
		
		$gE = Misc::getEvent();
		
		if ($gE[4] == 1) {
			$epicChance = $GLOBALS['event_epicbonus'];
		}
		
		if ($place == 'tower')
			$epicChance = 100;
		
		if ($place == 'dungeon')
			$epicChance = $epicCa;

    	//sometimes when passing random args 1 through 10 in type, it can end up being shield for a mage/rouge
    	//so just make sure here that it isnt a shield for a mage/rouge, just roll another type
    	while($type == 2 && $class != 1)
    	{	
			$type = rand(1, 7);
			
			if($class == 4) // Assassin, more chance to get weapon
				$type = 1;
		}
		
		$itmClass = $class;
		if ($class == 4) $itmClass = ($type == 1) ? 1 : 3;
		if ($class == 5) $itmClass = ($type == 1) ? 1 : 2;
		if ($class == 6) $itmClass = 1;
		if ($class == 7) $itmClass = ($type == 1) ? 3 : 1;
		if ($class == 8) $itmClass = ($type == 1) ? 2 : 3;
		if ($class == 9) $itmClass = ($type == 1) ? 2 : 3;
		
    	$item = [
		"type" => (rand(0, 1) * 65536) + $type,
		"item_id" => rand(1, 10),
		"dmg_min" => 0,
		"dmg_max" => 0,
		"a1" => 0,
		"a2" => 0,
		"a3" => 0,
		"a4" => 0,
		"a5" => 0,
		"a6" => 0,
		//"value_silver" => rand(intval( $lvl * $lvl * 300 * 0.9 ), intval( $lvl * $lvl * 300 * 1.1 )), // v2 by Greg
		"value_silver" => 0,
		"value_mush" => 0
		];
		
		if($lvl < 25) {
			$item['type'] = $type; // No gem slot etc. without fortress
		}

		if($lvl < 50 && rand(1, 2) == 1) {
			$item['type'] = $type;
		}	
		
		// Money gen v3 by Greg
		$silv = &$item["value_silver"];
		
		$silvgen = Account::getQuestGold($lvl, $GLOBALS["goldbonus"]) * 5;
		
		$randmulti = rand(10000, 11000) / 10000;
		
		$silv = intval(explode(".", $silvgen * $randmulti)[0]);
		
		if($silv < 0)
			$silv = abs($silv);
	
		if($type <= 10){
			$isEpic = rand(0, 99) < $epicChance && $lvl > 25;
			if($isEpic){
				//set stats here
				//25% for allstat
				if(rand(1, 4) > 2){
					$statVal = 2 + round($lvl * 1.5);
					$z = round(3 + $lvl * 0.05);
					$statVal += round(rand($z / -2, $z / 2));

					$item['a1'] = [1, 3, 2, 2, 1, 1, 2, 3, 3][$class - 1];
					$item['a2'] = 4;
					$item['a3'] = 5;
					$item['a4'] = $statVal;
					$item['a5'] = $statVal;
					$item['a6'] = $statVal;
				}else{
					$statVal = 4 + round($lvl * 1.27);
					$z = round(3 + $lvl * 0.05);
					$statVal += round(rand($z / -2, $z / 2));

					$item['a1'] = 6;
					$item['a4'] = $statVal;
				}
				
				$item["value_mush"] = 25;
			}else{
				if(rand(1, 3) == 3){
					$statVal = round($lvl * 2.33);
					$z = round(3 + $lvl * 0.05);
					$statVal += rand($z / -2, $z / 2);
					
					$statType = rand(1,5);
					
					$item['a1'] = $statType;
					$item['a4'] = $statVal;
					
					$statVal = round($lvl * 2.33);
					$z = round(3 + $lvl * 0.05);
					$statVal += rand($z / -2, $z / 2);
					
					$statType2 = $statType;
					
					while($statType2 == $statType)
						$statType2 = rand(1,5);
					
					$item['a2'] = $statType2;
					$item['a5'] = $statVal;
					
					$item["value_mush"] = 10;
				}
				else{
					$statVal = round($lvl * 2.33);
					$z = round(3 + $lvl * 0.05);
					$statVal += rand($z / -2, $z / 2);
					
					$item['a1'] = rand(1,5);
					$item['a4'] = $statVal;
				}

			}
		}

		//armor
		if($type > 2 && $type < 8) {
			// Armor system by Greg
			$item['dmg_min'] = 1;
			if ($itmClass == 1){ // Warrior
				if ($type == 3){
					$item ['dmg_min'] = $lvl * 15 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($type == 4){
					$item ['dmg_min'] = $lvl * 7 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($type == 5){
					$item ['dmg_min'] = $lvl * 12 * rand(1.5, 1.7) + rand(1, 6);
				}
					if ($type == 6){
						$item ['dmg_min'] = $lvl * 13 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($type == 7){
					$item ['dmg_min'] = $lvl * 12 * rand(1.5, 1.7) + rand(1, 6);
				}
			}
			if ($itmClass == 2){ // Mage
				if ($type == 3){
					$item ['dmg_min'] = $lvl * 3 * rand(1.5, 1.7)+ rand(1, 6);
				}
				if ($type == 4){
					$item ['dmg_min'] = round($lvl * 2 * rand(1.5, 1.7) + rand(1, 6));
				}
				if ($type == 5){
					$item ['dmg_min'] = $lvl * 3 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($type == 6){
					$item ['dmg_min'] = $lvl * 2 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($type == 7){
					$item ['dmg_min'] = $lvl * 2 * rand(1.5, 1.7) + rand(1, 6);
				}
			}
			if ($itmClass == 3){ // Hunter
				if ($type == 3){
					$item ['dmg_min'] = $lvl * 6 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($type == 4){
					$item ['dmg_min'] = round($lvl * 5 * rand(1.5, 1.7) + rand(1, 6));
				}
				if ($type == 5){
					$item ['dmg_min'] = $lvl * 3 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($type == 6){
					$item ['dmg_min'] = $lvl * 4 * rand(1.5, 1.7) + rand(1, 6);
				}
				if ($type == 7){
					$item ['dmg_min'] = $lvl * 5 * rand(1.5, 1.7) + rand(1, 6);
				}
			}
		}

		//set class
		
		
		// Event workaround by Greg
		
		// Epic types
		$itemz1 = array(50, 51, 52, 53, 54, 55, 56, 57, 58);
		$itemz2 = array(50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60);
		
		if ($lvl >= 200)
		{
			// New Epics above lvl 200
			$itemz1 = array(50, 51, 52, 53, 54, 55, 56, 57, 58, 64, 65, 66);
			$itemz2 = array(50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 64, 65, 66);
		}
		
		// Event epic
		if ($gE[0] == 6) {
			// Christmas
			$itemz1 = array(63);
			$itemz2 = array(63, 63);
		}elseif ($gE[0] == 7) {
			// Easter
			$itemz1 = array(61);
			$itemz2 = array(61, 61);
		}elseif ($gE[0] == 8) {
			// Halloween
			$itemz1 = array(62);
			$itemz2 = array(62, 62);
		}

		switch($type){
			case 1:

				if($isEpic)
					$item['item_id'] = $itemz2[rand(0, (count($itemz2) - 1))];
				else if($itmClass == 1) // Warrior
					$item['item_id'] = rand(1,30);
				else
					$item['item_id'] = rand(1,10);

				//weapon multiplier
				if($itmClass == 1)
					$weaponMultiplier = $GLOBALS["weaponMultipliers"][0];
				else if($itmClass == 3)
					$weaponMultiplier = $GLOBALS["weaponMultipliers"][2];
				else 
					$weaponMultiplier = $GLOBALS["weaponMultipliers"][1];

				$dmg = 1 + floor($weaponMultiplier * $lvl);
				$minmax = 2 + floor($lvl * rand(5, 100) / 100);

				$item['dmg_min'] = $dmg - $minmax;
				$item['dmg_max'] = $dmg + $minmax;

				break;
			case 2:
				if($isEpic)
					$item['item_id'] = $itemz2[rand(0, (count($itemz2) - 1))];
				else
					$item['item_id'] = rand(1, 10);

				//block
				$item['dmg_min'] = 25;
				break;
			case 3:
				if($isEpic)
					$item['item_id'] = $itemz1[rand(0, (count($itemz1) - 1))];
				else
					$item['item_id'] = rand(1, 10);

				break;
			case 4:
				if($isEpic)
					$item['item_id'] = $itemz1[rand(0, (count($itemz1) - 1))];
				else
					$item['item_id'] = rand(1, 10);

				break;
			case 5:
				if($isEpic)
					$item['item_id'] = $itemz1[rand(0, (count($itemz1) - 1))];
				else
					$item['item_id'] = rand(1, 10);
				break;
			case 6:
				if($isEpic)
					$item['item_id'] = $itemz1[rand(0, (count($itemz1) - 1))];
				else
					$item['item_id'] = rand(1, 10);
				break;
			case 7:
				if($isEpic)
					$item['item_id'] = $itemz1[rand(0, (count($itemz1) - 1))];
				else
					$item['item_id'] = rand(1, 10);
				break;
			case 8:
				if($isEpic)
					$item['item_id'] = $itemz2[rand(0, (count($itemz2) - 1))];
				else
					$item['item_id'] = rand(1, 21);
				break;
			case 9:
				if($isEpic)
					$item['item_id'] = $itemz2[rand(0, (count($itemz2) - 1))];
				else
					$item['item_id'] = rand(1, 16);
				break;
			case 10:
				if($isEpic)
					$item['item_id'] = $itemz2[rand(0, (count($itemz2) - 1))];
				else
					$item['item_id'] = rand(1, 37);
				break;
			case 12: //potion
				//type is always type value, no gems slots etc
				$item['type'] = $type;

				//hp pot 25%
				if(rand(0, 99) > 25){
					
					if($GLOBALS["justBestPots"])
						$potType = 2;
					else
						$potType = rand(0, 2);
					
					if($place == "wheel") {
						$potType = 2; // Just best pots if wheel
					}	
	
					
					$item['item_id'] = ($potType * 5) + rand(1, 5);

					//duration
					$item['a1'] = 11;
					$item['a4'] = ((2 - $potType) * 48) + 72;

					//value %
					$item['a2'] = $item['item_id'] - ($potType * 5);
					//$item['a5'] = 25;
					switch($potType) { // Potions fix by Jack
						case 0:
							$item['a5'] = 10;
						break;
						case 1:
							$item['a5'] = 15;
						break;
						case 2:
							$item['a5'] = 25;
						break;
					}	
					

					//always no mush cost
					$item['value_mush'] = 0;
				}else{
					$item['item_id'] = 16;

					//duration
					$item['a1'] = 11;
					$item['a4'] = 168;

					//health 
					$item['a2'] = 12;
					$item['a5'] = 25;

					$item['value_mush'] = 15;
				}
				break;
			case 13: //album
				//type is always type value, no gems slots etc
				$item['type'] = $type;
				
				$item['item_id'] = 1;
				$item['value_mush'] = 0;
				$item['value_silver'] = 2500;
				$item['a2'] = 0;
				$item['a3'] = 0;
				$item['a4'] = 0;
				$item['a5'] = 0;
				$item['a6'] = 0;
			
				break;	
			case 15: //gem v2
				//type is always type value, no gems slots etc
				$item['type'] = $type;

				$rand = rand(1, 10);
				
				if (in_array($rand, [9, 10]))
					$quality = 3;
				else if(in_array($rand, [5, 6, 7, 9]))
					$quality = 2;
				else
					$quality = 1;
				
				$quality_mt = [0.2, 0.22, 0.23][$quality - 1];
				
				// Better stat function, so better chance if higher lvl mine
				// By the way, not a really hard workaround, because I'm lazy (Greg)
				if ($mineLvl < 10) {
					$stat = rand(0, 5);
				}elseif ($mineLvl < 16) {
					$stat = rand(0, 5) < 2 ? 5 : rand(0, 4);
				}elseif ($mineLvl < 20) {
					$stat = rand(0, 4) < 2 ? 5 : rand(0, 4);
				}else{
					$stat = rand(0, 3) < 2 ? 5 : rand(0, 4);
				}
				
				// value v2 - more fair
				$value = rand(2000, 2203); // Base
				
				$value += 3.8 * $epicCa; // Guild HOK lvl
				
				$value *= $quality_mt; // Quality multiplier
				// value end
				
				if ($stat == 5)
					$value *= 0.7;
				
				$value = floor($value);

				$item['item_id'] = $quality * 10 + $stat;
				$item['value_mush'] = $value * 65536;
				break;
			// Hourglass by Jack	
			case 17:
				//type is always type value, no gems slots etc
				$item['type'] = $type;
				
				$item['item_id'] = 1;
				$item['value_mush'] = 0;
				$item['a2'] = 0;
				$item['a3'] = 0;
				$item['a4'] = 0;
				$item['a5'] = 0;
				$item['a6'] = 0;
				
				break;
			case 18: // Heart of Darkness (Underworld key)
				$item['type'] = $type;
				
				$item['item_id'] = 1;
				$item['value_mush'] = 0;
				$item['value_silver'] = 1000;
				$item['a2'] = 0;
				$item['a3'] = 0;
				$item['a4'] = 0;
				$item['a5'] = 0;
				$item['a6'] = 0;
				break;
			case 19: //Wheel v2
				$item['type'] = $type;
				
				$item['item_id'] = 1;
				$item['value_mush'] = 0;
				$item['value_silver'] *= 2;
				$item['a2'] = 0;
				$item['a3'] = 0;
				$item['a4'] = 0;
				$item['a5'] = 0;
				$item['a6'] = 0;
				break;
		}

		//set class + Greg's "EPIC+" (aka new epics) workaround for better items (can be removed if you want to)
		if($type <= 7)
		{
			$item['item_id'] += 1000 * ($itmClass - 1);
			
			if ($type == 1)
			{
				//if($class == 4) $item['item_id'] -= 1000 * ($class - 1);
				//if($class == 5) $item['item_id'] -= 1000 * ($class - 1);
				//if($class == 6) $item['item_id'] -= 1000 * ($class - 1);
				//if($class == 9) $item['item_id'] -= 6000 * ($class - 1);
				
				$item["dmg_min"] = round($item["dmg_min"] * 1.01);
				$item["dmg_max"] = round($item["dmg_max"] * 1.01);
			}
			
			if ($type >= 3)
			{
				//if($class == 4) $item['item_id'] -= 1000;
				//if($class == 5) $item['item_id'] -= 3000;
				//if($class == 9) $item['item_id'] -= 6000;
				
				$item["dmg_min"] = round($item["dmg_min"] * 1.01);
			}	
		}
		
		if($place != "shop")
			$item["value_mush"] = 0;
		
		return $item;
	}
}
?>