<?php

class Chat {

	public static function getChat($gid, $limit = 5){
		$chat = $GLOBALS['db']->query("SELECT players.name, guildchat.spec, guildchat.message, guildchat.time FROM guildchat LEFT JOIN players ON guildchat.playerID = players.ID 
					WHERE guildchat.guildID = $gid ORDER BY chattime DESC LIMIT $limit");
		return $chat->fetchAll(PDO::FETCH_ASSOC);
	}

	//returns chattime
	public static function chatInsert($message, $guild, $player, $rank = 0){
		$spec = $rank;
		$time = $GLOBALS["CURRTIME"];
		$x = $GLOBALS['db']->query("SELECT Count(*) AS c FROM guildchat WHERE guildID = $guild;");
		$x = $x->fetch(PDO::FETCH_ASSOC)['c'];
		if ($x < 1) {
			$chattime = $GLOBALS['db']->query("SELECT Max(chattime) AS chattimer FROM guildchat WHERE guildID = $guild;");
			$chattime = $chattime->fetch(PDO::FETCH_ASSOC);
			$ctimer = isset($chattime['chattimer']) ? $chattime['chattimer'] + 1 : 1;
			
			$qry = $GLOBALS["db"]->prepare("INSERT INTO guildchat(guildID, playerID, message, time, chattime, spec) VALUES($guild, $player, :message, $time, $ctimer + 1, $spec)");
			
			$qry->bindParam(":message", $message);
			
			$qry->execute();
			
			$GLOBALS['db']->exec();
		}else{
			$chattime = $GLOBALS["db"]->prepare("SELECT @cht := Max(chattime) AS chattimer FROM guildchat WHERE guildID = $guild;
				INSERT INTO guildchat(guildID, playerID, message, time, chattime, spec) VALUES($guild, $player, :message, $time, @cht + 1, $spec)");
			
			$chattime->bindParam(":message", $message);
			
			$chattime->execute();
			
			$ctimer = $chattime->fetch(PDO::FETCH_ASSOC)['chattimer'] + 1;
		}
		return $ctimer;
	}

	public static function formatChat($messages){

		//gold donate
		//#dg#14:29 Pan Marcel#38500


		$chatHistory = ['', '', '', '', ''];

		$i = 0;
		foreach($messages as $msg){


			if(strpos($msg['message'], '#') !== false){
				//system message, formatted beforehand
				$chatHistory[$i] = $msg['message'];

			}else{
				//normal player message
				$message = $msg['message'];
				$formattedTime = gmdate("H:i", $msg['time'] + 7200);
				$name = $msg['name'];
				$perm = $msg['spec'];
				
				// TODO: languages
				if ($perm == '2') {
					$name = '[+]' . $name;
				}elseif ($perm == '3') {
					$name = '[++]' . $name;
				}elseif ($perm == '4') {
					$name = '@' . $name;
				}

				$chatHistory[$i]= "$formattedTime $name:§ $message"; 
			}
			$i++;
		}

		return join('/', $chatHistory);
	}

	//checks if chat message is a system message
	public static function containsSystemMessage($chat){

		//fix this shit

		foreach($chat as $msg)
			if(strpos($msg['message'], '#') !== false)
				return true;
		

		return false;
	}

	public static function formatMessages($messages){
		//guild invite
		//601979186,maks03,0,5,1388396179

		//guild disbanded
		//1077418003,Pan Marcel,0,1,1387482797

		$msgs = [];
		foreach($messages as $msg){
			if(strlen($msg['name']) == 0)
				$msg['name'] = 'admin';
			$msgs[] = join(',', $msg);
		}

		return join(';', $msgs);
	}
	
	public static function formatWhispers($data) {
		if ($data == '')
			return '';
		// 19:37 Brayght:§ Szia
		$whispers = [];
		$data = explode('/', $data);
		krsort($data);
		foreach($data as $in) {
			$array = explode(':', $in); // 0 name, 1 time, 2 msg
			
			$whispers[] = date('H:i', $array[1]) . ' ' . $array[0] . ':§ ' . $array[2];
		}
		
		return implode('/', $whispers);
	}

}

?>