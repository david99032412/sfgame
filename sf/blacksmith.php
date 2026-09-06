<?php

// Blacksmith calculation by Greg
// 100%

// Gets exact same result as swf

class blacksmith
{
	private $item;

	function __construct($item)
	{
		// Set item
		$this->item = $item;
	}
	
	private function random($d)
	{
		$ret = 0;
		
		// Do random things
		$ret += $this->item->raw["type"] * 37;
		$ret += $this->item->raw["item_id"] * 83;
		$ret += $this->item->raw["dmg_min"] * 1731;
		$ret += $this->item->raw["dmg_max"] * 162;
		
		// divison rest
		$ret %= $d + 1;
		
		return $ret;
	}

	private function quality()
	{
		if($this->item->raw["a4"] > 0 && $this->item->raw["a5"] > 0 && $this->item->raw["a6"] > 0)
		{
            return 3;
		}
		if($this->item->raw["a1"] != 6)
		{
			if(!($this->item->raw["a4"] > 0 && $this->item->raw["a5"] > 0))
			{
				return 1;
			}
			return 2;
		}
		return 3;
	}
	
	// Actions returning [dismantle, removestone, addsocket, upgradeitem]
	
	public function getData()
	{
		// _loc3_ = q;
		// _loc4_ = num;
		// _loc5_, _loc6_ = rnd1, rnd2;
		
		// Declare variables
		$ret = [];
		$ret["dismantle"] = [0, 0];
		$ret["removestone"] = [0, 0];
		$ret["addsocket"] = [0, 0];
		$ret["upgradeitem"] = [0, 0];
		
		$item = $this->item->raw;
		
		// Start the things ayy
		//if($item["type"] < 1 || $item["type"] > 7)
			//return $ret;
		
		$q = $this->quality();
		$num = $item["a4"];
		
		if($item["a1"] == 6)
			$num *= 1.2;
		
		if($item["type"] == 1 && $this->item->forClass != 1)
			$num /= 2;
		
		if(($this->item->id % 1000) == 52 && $item["a1"] == 5 && $item["a2"] == 0)
			$num /= 5;
		else if($q == 1 && $num > 66)
			$num *= 0.75;
		
		$num = floor(pow($num, 1.2));
		
		// Declare rands
		$rnd1 = 0;
		$rnd2 = 0;
		
		// <dismantle>
		if($q == 1)
		{
			$rnd1 = 75 + $this->random(25);
			$rnd2 = $this->random(1);
		}
		else if($q == 2)
		{
			$rnd1 = 50 + $this->random(30);
			$rnd2 = 5 + $this->random(5);
		}
		else if($q == 3)
		{
			$rnd1 = 25 + $this->random(25);
			$rnd2 = 50 + $this->random(50);
		}
		
		if($item["type"] == 1 && $this->item->forClass != 1)
		{
			$rnd1 *= 2;
			$rnd2 *= 2;
		}
		
		$ret["dismantle"][0] = floor($num * $rnd1 / 100);
		$ret["dismantle"][1] = floor($num * $rnd2 / 100);
		// </dismantle>
		
		if($item["type"] != 2)
		{
			// <removestone>
			$rnd1 = 25;
			$rnd2 = 0;
			
			if($q == 1)
				$rnd2 = 1;
			else if($q == 2)
				$rnd2 = 5;
			else if($q == 3)
				$rnd2 = 50;
			
			$ret["removestone"][0] = Misc::biggest(floor($num * $rnd1 / 100) * 10, 10);
			//$ret["removestone"][1] = Misc::biggest(floor($num * $rnd2 / 100) * 10, 10);
			$ret["removestone"][1] = Misc::biggest(floor($num * $rnd2 / 100) * 5, 5);
			// </removestone>
			
			// <addsocket>
			$rnd1 = 500;
			$rnd2 = 0;
			
			if($q == 1)
				$rnd2 = 25;
			else if($q == 2)
				$rnd2 = 50;
			else if($q == 3)
				$rnd2 = 100;
			
			$ret["addsocket"][0] = $num * $rnd1 / 100;
			$ret["addsocket"][1] = Misc::biggest(floor($num * $rnd2 / 100) * 10, 10);
			// </addsocket>
		}
		
		// <upgradeitem>
		$rnd1 = 50;
		$rnd2 = 0;
		
		if($q == 1)
			$rnd2 = 25;
		else if($q == 2)
			$rnd2 = 50;
		else if($q == 3)
			$rnd2 = 75;
		
		$upgr = $item["value_mush"] % 65536;
		$upgr = floor($upgr / 256);
		
		switch($upgr)
		{
			case 0:
				$rnd1 *= 3;
				$rnd2 = 0;
				break;
			case 0:
				$rnd1 *= 4;
				$rnd2 = 1;
				break;
			case 2:
			case 3:
			case 4:
			case 5:
			case 6:
			case 7:
				$rnd1 *= $upgr + 3;
				$rnd2 *= $upgr - 1;
				break;
			case 8:
				$rnd1 *= 12;
				$rnd2 *= 8;
				break;
			case 9:
				$rnd1 *= 15;
				$rnd2 *= 10;
				break;
			default:
				$rnd1 = 0;
				$rnd2 = 0;
		}
		
		$rnd1 = floor($num * $rnd1 / 100);
		$rnd2 = floor($num * $rnd2 / 100);
		
		if ($item["type"] == 1 && $this->item->forClass != 1)
		{
			$rnd1 *= 2;
			$rnd2 *= 2;
		}
		
		$ret["upgradeitem"][0] = $rnd1;
		$ret["upgradeitem"][1] = $rnd2;
		// </upgradeitem>
		
		return $ret;
	}
}