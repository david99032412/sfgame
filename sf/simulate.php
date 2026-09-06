<?php

class Simulation{

	private $player;
	private $opponent;

	//winner id
	public $winner;

	public $fightLog;

	//db ID of winner
	public $winnerID;

	//the longer the fights go on, the higher the hits
	private $progressionMultiplier;

	//reference needed for group simulation
	function __construct(&$player, &$opponent){
		$this->player = $player;
		$this->opponent = $opponent;
		//exit("&Error:".$this->player->ID);
	}

	public function simulate(){
		//block = rand(1, 100), 0 = no block
		//hit types:
		// 0 = normal | 1 = crit | 2 = catapult | 3 = blok | 4 = dodge


		//log structure: hitter id/hit type/hp after hit

		$this->fightLog = [];

		//randomize who begins fight
		if(rand(0, 1) == 1){
			$first = "player";
			$second = "opponent";
		}else{
			$first = "opponent";
			$second = "player";
		}

		$blocks = ($this->player->class != 2 && $this->opponent->class != 2);
		$this->progressionMultiplier = 1.0;

		while($this->opponent->hp > 0 && $this->player->hp > 0){
			if($this->$first->hp > 0){
				$this->hit($first, $second, $blocks, false);
				$this->doubleHit($first, $second, $blocks);
			}

			if($this->$second->hp > 0){
				$this->hit($second, $first, $blocks, false);
				$this->doubleHit($second, $first, $blocks);
			}

			if($this->progressionMultiplier < 1.7)
				$this->progressionMultiplier += 0.1;
			else
				$this->progressionMultiplier += 0.05;
		}

		$this->fightLog = join(",", $this->fightLog);
		
		if($this->player->hp > 0)
			$this->winnerID = $this->player->ID;
		else{
			//monsters need negative value on this because fucking reasons
			$this->winnerID = $this->opponent->ID;
			if(get_class($this->opponent) == "Monster")
				$this->winnerID = abs($this->winnerID) * -1;
		}


	}


	//TODO: count dmg with armor and shit
	private function hit($hitter, $target, $block, $double){
		//make this a float later
		$crit = (rand(0,100) < $this->$hitter->crit);

		//if warrior has no shield / wmoved to player, if no shield block chance = 0
		// if($this->$target->class == 1 && !isset($this->$target->shield))
		// 	$block = false;

		//block/dodge
		if($block)
			$block = (rand(0, 100) < $this->$target->block);

		//log structure: hitter id/hit type/hp after hit
		$this->fightLog[] = $this->$hitter->ID;

		if($block)
			$hitType = ($this->$target->class == 1) ? 3 : 4;
		else if($crit)
			$hitType = 1;
		else
			$hitType = 0;
		
		if($double) { //2nd hit
			$hitType += 10;
		}	

		$this->fightLog[] = $hitType;

		//TODO: count the dmg
		
		$dmg = round(rand(intval($this->$hitter->dmg_min), intval($this->$hitter->dmg_max)) * $this->progressionMultiplier);

		if($double && isset($this->$hitter->sec_dmg_min) && isset($this->$hitter->sec_dmg_max)){
			$dmg = round(rand(intval($this->$hitter->sec_dmg_min), intval($this->$hitter->sec_dmg_max)) * $this->progressionMultiplier);
		}	
		
		if($this->$hitter->class == 4) {
			$dmg = round($dmg * 0.625);
		}	
		
		if($crit)
			$dmg *= 2;
		
		//Underworld trainer crit bonus
		if($crit && isset($this->$hitter->crit_bonus))
			$dmg += $this->getCritBonus($dmg, $this->$hitter->crit_bonus);	

		if(!$block)
			$this->$target->dmg($dmg);
		$this->fightLog[] = $this->$target->hp;
	}
	
	// Assassins 2nd hit
	private function doubleHit($attacker, $target, $blocks) {
		if($this->$attacker->class != 4)
			return;
		
		if($this->$attacker->hp > 0 && $this->$target->hp > 0) {
			$this->hit($attacker, $target, $blocks, true);
		}	
	
	}

	private function getCritBonus($dmg, $bonus) {
		$crit = 0;
		if(isset($bonus) && $bonus > 0)
			$crit = round($dmg * ($bonus / 100));
		
		return $crit;
	}	

}



class GroupSimulation{

	public $playerGroup;
	public $opponentGroup;

	//array of simulation objects to access fight logs and winners
	public $simulations = [];

	//array of fight headers, contain starting hp, so they need to be set before fight simulation
	public $fightHeaders = [];

	//boolean, true if playerGroup wins
	public $win;
	
	// For additional fighters
	public $lasts = [0, 0];

	function __construct($playerGroup, $opponentGroup){
		$this->playerGroup = $playerGroup;
		$this->opponentGroup = $opponentGroup;
	}

	public function simulate(){
		$playerLast = &$this->playerGroup[count($this->playerGroup) - 1];
		$opponentLast = &$this->opponentGroup[count($this->opponentGroup) - 1];

		//counters
		$pc = 0;
		$oc = 0;

		while($playerLast->hp > 0 && $opponentLast->hp > 0){
			//scene, background etc handled outside
			$this->fightHeaders[] = $this->playerGroup[$pc]->getFightHeader().$this->opponentGroup[$oc]->getFightHeader();

			$simulation = new Simulation($this->playerGroup[$pc], $this->opponentGroup[$oc]);
			$simulation->simulate();


			$this->simulations[] = $simulation;
			
			//increase counter depending on winner
			if($simulation->winnerID == $this->playerGroup[$pc]->ID)
				$oc++;
			else
				$pc++;
		}

		//set winner
		$this->win = $playerLast->hp > 0;
		
		// set lasts
		$this->lasts = [$pc, $oc];
	}

	public static function reverseGuildFightLog($log){

		return $log;

		$log = split('&', $log);

		foreach($log as $line){
			switch($line){

			}
		}
	}
	
	public function getAdditionals()
	{
		$c = $this->lasts[0];
		
		$meAddi = ["", "", "", "", ""];
		
		for($i = 0; $i <= 4; $i++)
		{
			if (!isset($this->playerGroup[$c + 1 + $i]))
				break;
			
			$p = $this->playerGroup[$c + 1 + $i];
			
			$temp = &$meAddi[$i];
			
			if (isset($p->data["name"]))
				$temp = $p->data["name"];
			else if (isset($p->ID2) && $p->ID2 < 0)
				$temp = $p->ID2;
			else
				$temp = $p->ID;
			
			if(property_exists($p, "currCount"))
				$temp .= "_" . $p->currCount . "_" . $p->allCount;
		}
		
		$c = $this->lasts[1];
		
		$youAddi = ["", "", "", "", ""];
		
		for($i = 0; $i <= 4; $i++)
		{
			if (!isset($this->opponentGroup[$c + 1 + $i]))
				break;
			
			$p = $this->opponentGroup[$c + 1 + $i];
			
			$temp = &$youAddi[$i];
			
			if (isset($p->data["name"]))
				$temp = $p->data["name"];
			else if (isset($p->ID2) && $p->ID2 < 0)
				$temp = $p->ID2;
			else
				$temp = $p->ID;
			
			if(property_exists($p, "currCount"))
				$temp .= "_" . $p->currCount . "_" . $p->allCount;
		}
		
		return implode(",", $meAddi) . "," . implode(",", $youAddi) . ",";
	}
}

?>