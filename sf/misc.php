<?php

// By Greg 100%

class Misc
{
	public static function getEvent() {
		$ce = &$GLOBALS['currEvent'];
		
		if ($ce == -1)
		{
			$year = date('Y');
			$month = date('n');
			
			$count = 0;
			$day_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);

			for ($i = 1; $i <= $day_count; $i++)
			{
				$date = $year . '/' . $month . '/'. $i;
				$get_name = date('l', strtotime($date));
				$day_name = substr($get_name, 0, 3);
				
				if($day_name == 'Sat')
				{
					$count++;
				}
			}
			
			$ce = $count % 5;
			if ($ce == 0)
				$ce = 1;
		}
		
		$b1 = 0; // XP Bonus
		$b2 = 0; // Gold Bonus
		$b3 = 0; // Mush Bonus
		$b4 = 0; // Epic Bonus
		
		$wd = date('D');
		
		if ($ce > 4 && $ce < 10) {
			// All bonus
			$b1 = 1;
			$b2 = 1;
			$b3 = 1;
			$b4 = 1;
		}else{
			// No weeekend?
			if ($wd != 'Sat' && $wd != 'Sun' && $GLOBALS["event_onlyWeekend"]) {
				return array(0, 0, 0, 0, 0); // Return no event
			}
			
			switch($ce) {
				case 1 :
					$b1 = 1;
				break;
				case 2 :
					$b4 = 1;
				break;
				case 3 :
					$b2 = 1;
				break;
				case 4 :
					$b3 = 1;
				break;
			}
		}
		return array($ce, $b1, $b2, $b3, $b4);
	}
	
	public static function getNow($new = true)
	{
		if (!$new)
			return floor(($GLOBALS["CURRTIME"] - strtotime("2010-01-01")) / 86400) % 365;
		
		$datetime1 = new DateTime("2010-01-01");

		$datetime2 = new DateTime(date("Y-m-d", $GLOBALS["CURRTIME"]));
		
		$difference = $datetime1->diff($datetime2);
		
		$now = $difference->m * 32 + $difference->d;
		
		return $now;
	}
	
	public static function biggest($nums)
	{
		if(!is_array($nums))
			return $nums;
		
		$ret = 0;
		
		for($i = 0; $i < count($nums); $i++)
		{
			if ($nums[$i] > $ret)
				$ret = $nums[$i];
		}
		
		return $ret;
	}
	
	public static function isIpBlocked($ip)
	{
		// By Greg
		
		$qry = $GLOBALS["db"]->prepare("SELECT Count(id) AS c FROM ipbans WHERE ip = :ip");
		
		$qry->bindParam(":ip", $ip);
		
		$qry->execute();
		
		return $qry->fetchAll()[0]["c"] > 0;
	}
	
	public static function getClientIp() {
		$headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
		foreach ($headers as $key) {
			if (array_key_exists($key, $_SERVER) === true) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = trim($ip);

					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
						return $ip;
					}
				}
			}
		}
	}
	
	public static function getCountryID($countryCode, $all = false) {
		$countryIDMap = [
			"ru" => 20, "fi" => 8, "ar" => 1, "tr" => 23, "nl" => 16,
			""   => 0, "ja" => 14, "it" => 13, "sk" => 21, "fr" => 9,
			"ko" => 15, "pl" => 17, "cs" => 2, "el" => 5, "da" => 3,
			"en" => 6, "hr" => 10, "de" => 4, "zh" => 24, "sv" => 22,
			"hu" => 11, "pt" => 12, "es" => 7, "pt-br" => 18, "ro" => 19
		];

		if($all) {
			$formatted = [];
			foreach ($countryIDMap as $countryCode => $id) {
				$formatted[] = "$countryCode,$id";
			}
			return implode(";", $formatted);
		} elseif(isset($countryIDMap[$countryCode])) {
			return $countryIDMap[$countryCode];
		} else {
			return 0;
		}
	}
	
	public static function dungNumber($i) {
		$i--;
			
		if($i == 15) return 17;
		if($i == 12) return 15;
		if($i == 13) return 19;
		if($i == 16) return 18;
		if($i == 18) return 24;
		if($i == 19) return 27;
		if($i == 20) return 21;
		if($i == 21) return 13;
		if($i == 22) return 14;
		if($i == 23) return 16;
		if($i == 24) return 20;
		if($i == 25) return 28;
		if($i == 26) return 22;
		if($i == 27) return 23;
		if($i == 28) return 25;
		if($i == 29) return 26;
			
		return $i + 1;
	}
	
	public static function dungNumberBattle($dung) {
		
		if($dung == 22) return 13;
		if($dung == 23) return 14;
		if($dung == 13) return 15;
		if($dung == 24) return 16;
		if($dung == 16) return 17;
		if($dung == 17) return 18;
		if($dung == 14) return 19;
		if($dung == 25) return 20;
		if($dung == 27) return 22;
		if($dung == 28) return 23;
		if($dung == 19) return 24;
		if($dung == 29) return 25;
		if($dung == 30) return 26;
		if($dung == 20) return 27;
		if($dung == 26) return 28;
		
		
		return $dung;
	}
	
	public static function dungBackground($dung) {
		if($dung == 26) return 50 + 13;
		if($dung == 22) return 50 + 14;
		if($dung == 23) return 50 + 16;
		if($dung == 25) return 50 + 17;
		
		return $dung + 50;
	}
}