<?php
ob_start();
header('Access-Control-Allow-Origin: *');
// Settings file
include 'settings.php';

// System from here
$errlvl = $sandbox ? E_ALL ^ E_DEPRECATED : 0;
error_reporting($errlvl);

// Maintenance
if(isset($serverMaintenance) && $serverMaintenance == true)
	exit('&Error:server not available');

// Functions
include 'sf/misc.php';
include 'sf/chat.php';
include 'sf/album.php';
include 'sf/entity.php';
include 'sf/fortress.php';
include 'sf/player.php';
include 'sf/achievments.php';
include 'sf/account.php';
include 'sf/item.php';
include 'sf/simulate.php';
include 'sf/guild.php';
include 'sf/pets.php';
include 'sf/blacksmith.php';
include 'sf/underworld.php';

date_default_timezone_set($timezone);

$ip = Misc::getClientIp();

$rawPost = file_get_contents('php://input');
$requestPayload = '';
if (!empty($rawPost) && strpos($rawPost, 'req=') === 0) { $requestPayload = urldecode(substr($rawPost, 4)); }
elseif (!empty($rawPost)) { $requestPayload = $rawPost; }
else { $requestPayload = $_POST['req'] ?? $_GET['req'] ?? ''; }

if(empty($requestPayload)) exit("&Error:wrong request");

$req = substr($requestPayload, 16);
$key = '[_/$VV&*Qg&)r?~g';
$iv = 'jXT#/vz]3]5X7Jl\\';
$keyId = substr($requestPayload, 0, 16);
if($keyId == "0-0K36aS2567C735")
	$key = "5O4ddy4KZLs41n6W";
else if($keyId != "0-00000000000000")
	exit("&Error:cryptoid not found&cryptoid:0-0K36aS2567C735&cryptokey:5O4ddy4KZLs41n6W");

$req = openssl_decrypt(base64_decode(str_pad(strtr($req, '-_', '+/'), strlen($req) % 4, '=', STR_PAD_RIGHT)), 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

$inj = ['--', '*', '+', '"', "'", '\'', ';'];
$req = str_replace($inj, '', $req);

$passSalt = 'ahHoj2woo1eeChiech6ohphoB7Aithoh';

if($logRequests == true)
{
	$rlog = "[".date('Y. m. d. H:i')."] ";
	$rlog .= $ip;
	$rlog .= ":" . $_SERVER['HTTP_USER_AGENT'];
	$rlog .= ":" . $cracktrack;
	file_put_contents("log.dat", (file_get_contents("log.dat") . PHP_EOL . $rlog));
}	

if($sandbox)
	$orireq = $req;

$req = explode("|", $req);

if (!isset($req[1]))
	$req = ["00000000000000000000000000000000", "accountcheck:Greg"];

$ssid = $req[0];
$req = explode(":", $req[1]);

$act = $req[0];
$args = explode("/", $req[1]);

if(strlen($ssid) != 32)
	exit("&Error:invalid ssid length");

# Calender [Only mushrooms]
const CAL = [[2, 1], [2, 1], [2, 2], [2, 2], [2, 3], [2, 1], [2, 1], [2, 2], [2, 2], [2, 3], [2, 1], [2, 1], [2, 2], [2, 2], [2, 3], [2, 1], [2, 1], [2, 2], [2, 2], [2, 3]];

if($ssid != "00000000000000000000000000000000"){
	$qry = $db->prepare("SELECT players.*, fortress.ut1, fortress.ut2, fortress.ut3, fortress.uttime1, fortress.uttime2, fortress.uttime3 FROM players LEFT JOIN fortress ON players.ID = fortress.owner WHERE ssid = :ssid");
	$qry->bindParam(':ssid', $ssid);
	$qry->execute();

	if($qry->rowCount() == 0)
		exit("&Error:sessionid invalid");
	
	$playerData = $qry->fetch(PDO::FETCH_ASSOC);
	$playerID = $playerData['ID'];
	$playerPoll = $playerData['poll'];
	$playerGuild = $playerData['guild'];
	$playerPerm = $playerData['perm'];
	
	if($playerData['banned'] != 0) 
		exit('&Error:admin lock permanent');
	
	$lightDungs = empty($playerData['lightdungeons']) ? [] : json_decode($playerData['lightdungeons'], true);
	if (count($lightDungs) < $maxDungeonsLight + 1) {
		$lightDungs = array_pad($lightDungs, $maxDungeonsLight + 1, 0);
		$qry = $db->prepare('UPDATE players SET lightdungeons = ? WHERE ID = ?');
		$qry->execute([json_encode(array_values($lightDungs)), $playerData['ID']]);
	}
	
	$shadowDungs = empty($playerData['shadowdungeons']) ? [] : json_decode($playerData['shadowdungeons'], true);
	if (count($shadowDungs) < $maxDungeonsShadow + 1) {
		$shadowDungs = array_pad($shadowDungs, $maxDungeonsShadow + 1, 0);
		$qry = $db->prepare('UPDATE players SET shadowdungeons = ? WHERE ID = ?');
		$qry->execute([json_encode(array_values($shadowDungs)), $playerData['ID']]);
	}
	
	$unlockPets = empty($playerData['petsunlock']) ? [] : json_decode($playerData['petsunlock'], true);
	if (count($unlockPets) < 100) {
		$unlockPets = array_pad($unlockPets, 100, 0);
		$qry = $db->prepare('UPDATE players SET petsunlock = ? WHERE ID = ?');
		$qry->execute([json_encode(array_values($unlockPets)), $playerData['ID']]);
	}
	
	$now = Misc::getNow();
	if($playerData['newday'] != $now) {
		$uw = new Underworld($playerID);
		if($uw->haveIt) {
			//Calculate hourly gold
			$uw->newHourlyGold($playerData["lvl"]);
				
			//Reset lured today  
			$uw->data["lured"] = 0;
			$db->exec("UPDATE underworld SET lured = ".$uw->data["lured"]." WHERE owner = ".$uw->data["owner"]);
				
			if($uw->data['time'] > 0) {
				$thirst = $playerData['thirst'] / 60;
					
				if($thirst > $uw->getTimeMachineThirst()[0]) {
					$thirst = $uw->getTimeMachineThirst()[0];
				}
					
				$days = $now - $playerData['newday'];
				$days --;
					
				$thirst += $days * $uw->getTimeMachineThirst()[0];
					
				$timebonus = floor($uw->getTimeMachineThirst()[0] / 4);  // Bonus
				$thirst += $timebonus;
					
				$thirst += $days * $timebonus;
					
				$uw->data["timeamount"] += $thirst;
					
				if($uw->data["timeamount"] > $uw->getTimeMachineThirst()[1]) {
					$uw->data["timeamount"] = $uw->getTimeMachineThirst()[1];
				}	
					
				$db->exec("UPDATE underworld SET timeamount = ".$uw->data["timeamount"]." WHERE owner = ".$uw->data["owner"]);
			}
				
		}
			
		$newday = $now;
		$playerData['beers'] = 0;
		$playerData['thirst'] = 6000;
		$playerData['mush'] += $dailyMushrooms;
		$mush = $playerData['mush'];
			
		$db->exec("UPDATE players SET newday = '$newday', beers = '0', thirst = '6000', mush = '$mush', voucherToday = 0 WHERE ID = '".$playerData['ID']."'");
	}
}

$ret[] = 'sfgame:1';
switch(strtolower($act)){
	case 'accountcreate':
	
		//"00000000000000000000000000000000|accountcreate:sp/pass/ddask@ldpwqe.com/2/8/3/3,302,4,6,5,6,1,2,3/0/sfgame_new_flash/pl
		//race/gender/class/face/
		
		// Check IP block
		if(Misc::isIpBlocked($ip))
			exit("&Error:your ip is blocked");
		
		$name = $args[0];
		
		//check if name allowed, if not exit like this
		if(preg_match('/[^A-Za-z0-9 ]/', $name) || strlen($name) > 26)
			exit('&Error:name is not avaible');

		//client reserved name
		if(preg_match('/admin/', strtolower($name)) || preg_match('/tulaj/', strtolower($name)) || preg_match('/owner/', strtolower($name)) || preg_match('/system/', strtolower($name)))
			exit('&Error:name is not avaible');

		//numeric numbers not allowed, compliactions with arena, clients fault...
		if(is_numeric($name))
			exit('&Error:name is not avaible');
		
		$pass = $args[1];
		$mail = $args[2];

		$gender = $args[3];
		$race = $args[4];
		$class = $args[5];

		$face = $args[6];

		
		$qry = $db->prepare("SELECT name FROM players WHERE name = :name");
		$qry->bindParam(':name', $name);
		$qry->execute();

		if($qry->fetch( PDO::FETCH_ASSOC ))
			exit("&Error:character exists");
		
		// No multiple e-mails
		$qry = $db->prepare("SELECT email FROM players WHERE email = :email");
		$qry->bindParam(':email', $mail);
		$qry->execute();

		if($qry->fetch( PDO::FETCH_ASSOC ))
			exit("&Error:mail not available");

		
		$startingGold *= 100;
		$passEnc = sha1($pass.$passSalt);
		$qry = $db->prepare("INSERT INTO players(name, password, email, face, race, gender, class, silver, mush, calenderNext)
			VALUES(:name, :pass, :mail, :face, :race, :gender, :class, $startingGold, $startingMush, :calenderNext)");
		
		$qry->bindParam(':name', $name);
		$qry->bindParam(':pass', $passEnc);
		$qry->bindParam(':mail', $mail);
		$qry->bindParam(':face', $face);
		$qry->bindParam(':race', $race);
		$qry->bindParam(':gender', $gender);
		$qry->bindParam(':class', $class);
		$qry->bindParam(':calenderNext', Misc::getNow());
		$qry->execute();

		$qry = $db->prepare("SELECT ID FROM players WHERE name = :name");
		$qry->bindParam(':name', $name);
		$qry->execute();

		$pid = $qry->fetch(PDO::FETCH_ASSOC)['ID'];
		
		//insert a nice welcoming message :P
		if($wmail_enable)
			$db->exec("INSERT INTO messages(sender, reciver, time, topic, message) VALUES(0, $pid, ".$GLOBALS["CURRTIME"].", '$wmail_subject', '$wmail_body')");

		//fortress
		$db->exec("INSERT INTO fortress(owner, wood, stone) VALUES($pid, $fortStartingWood, $fortStartingStone)");

		//copycats
		$db->exec("INSERT INTO copycats(owner, class, str, dex, intel, wit) VALUES($pid, 1, 1046, 358, 531, 1065);
					INSERT INTO copycats(owner, class, str, dex, intel, wit) VALUES($pid, 2, 358, 531, 1046, 799);
					INSERT INTO copycats(owner, class, str, dex, intel, wit) VALUES($pid, 3, 358, 1046, 531, 799);");


		//starting weapon
		$classNew = $class;
		if($class == 4) $classNew = 1;
		if($class == 5) $classNew = 1;
		if($class == 6) $classNew = 1;
		if($class == 7) $classNew = 3;
		if($class == 8) $classNew = 2;
		if($class == 9) $classNew = 2;
		
		$weapon = Item::genItem(1, 1, $classNew);
		$weapon['value_silver'] = 1;
		$weapon['item_id'] = 1 + ($classNew - 1) * 1000;
		
		if($class == 4) { // Assassin
			$weapon['item_id'] = 1;
			Account::addSecondWep($pid, $class);
		}
		
		$db->exec('INSERT INTO items(owner, slot, type, item_id, dmg_min, dmg_max, a1, a2, a3, a4, a5, a6, value_silver, value_mush) VALUES('.$pid.', 18, '.join(', ', $weapon).')');

		//album
		//Removed, album buyable in magic shop - Jack
		//$db->exec('INSERT INTO items(owner, slot, type, item_id, value_silver) VALUES('.$pid.', 0, 13, 1, 1)');

		//shops
		for($i = 0; $i < 12; $i++){
			$type = $i < 6 ? rand(1, 7) : rand(8, 10);
			$item = Item::genItem($type, 1, $class);
			$slot = 20 + $i;
			$db->exec('INSERT INTO items(owner, slot, type, item_id, dmg_min, dmg_max, a1, a2, a3, a4, a5, a6, value_silver, value_mush) VALUES('.$pid.', '.$slot.', '.join(', ', $item).')');
		}

		$updateArgs = [];
		//quests
		for($i = 1; $i <= 3; $i++){
			$quest = Account::generateQuest(1);
			$updateArgs[] = "quest_exp$i = ".$quest['exp'];
			$updateArgs[] = "quest_silver$i = ".$quest['silver'];
			$updateArgs[] = "quest_dur$i = ".$quest['duration'];
		}
		
		// Dungs unlocked on default?
		if(!$defAllDungUnlocked)
		{
			// Set dungeons to locked
			
			for($i = 1; $i < 15; $i++){
				$updateArgs[] = "d$i = 0";
				$updateArgs[] = "dd$i = 0";
			}

			$updateArgs[] = "d16 = 0";
			$updateArgs[] = "dd16 = 0";			
		}
		
		// By Jack
		// Lock/unlock other things if set in settings.php
		if(!$defToiletUnlocked){
			//Lock WC
			$updateArgs[] = "wcaura = 0";
		}
		
		if($defUwUnlocked){
			// UNDERWORLD
			$db->exec("INSERT INTO underworld(owner, soul) VALUES($pid, $uwStartingSoul)");
		}	
	
		if(!$tutorial) // check tutorial
			$updateArgs[] = "tutorial = -1";


		// Starting hourglass
		$updateArgs[] = "hourglass = ".$startingHourglass;

		$db->exec('UPDATE players SET '.join(', ', $updateArgs).' WHERE ID = '.$pid);

		//resp
		exit("skipallow:".$adventureMushSkip."&timestamp:".$GLOBALS["CURRTIME"]."&playerid:$pid&tracking.s:signup&success:");

		break; 
	case 'accountcheck':
		//success if name avalible, error in case of login
		//if keyid default, give out another keyset
		
		// Check IP block
		if(Misc::isIpBlocked($ip))
			exit("&Error:your ip is blocked");
		
		$keyId = "0-0K36aS2567C735";
		$key = "5O4ddy4KZLs41n6W";

		$name = $args[0];

		//check if name allowed, if not exit like this
		if(preg_match('/[^A-Za-z0-9 ]/', $name) || strlen($name) > 26)
			exit('&Error:name is not avaible');

		//client reserved name
		if(preg_match('/admin/', strtolower($name)) || preg_match('/tulaj/', strtolower($name)) || preg_match('/owner/', strtolower($name)))
			exit('&Error:name is not avaible');

		//numeric numbers not allowed, compliactions with arena, clients fault...
		if(is_numeric($name))
			exit('&Error:name is not avaible');

		$qry = $db->prepare("SELECT name FROM players WHERE name = :name");
		$qry->bindParam(':name', $name);
		$qry->execute();

		//if character exists -> login
		if($qry->fetch( PDO::FETCH_ASSOC ))
			exit("&Error:character exists&cryptoid:$keyId&cryptokey:$key");

		//if name is free
		exit("Success:&cryptoid:$keyId&cryptokey:$key");
		break;
	case 'accountlogin':
		
		// Check IP block
		if(Misc::isIpBlocked($ip))
			exit("&Error:your ip is blocked");
		
		$qry = $db->prepare("SELECT players.*, fortress.*, guilds.portal AS guild_portal, guilds.instructor, guilds.treasure, guilds.dungeon AS raid FROM players LEFT JOIN fortress ON players.ID = fortress.owner LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.name = :name");
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		$playerData = $qry->fetch ( PDO::FETCH_ASSOC );

		$playerID = $playerData["ID"];
		

		if($qry->rowCount() == 0)
			exit('&Error:player not found');

		if (sha1($playerData['password'].$args[2]) != $args[1])
			exit('&Error:wrong pass');

		// Check ban
		if($playerData['banned'] != 0) {
			exit('&Error:admin lock permanent');
		}

		//get items
		$items = $db->query("SELECT * FROM items WHERE owner = ".$playerData['ID']." ORDER BY slot ASC");
		$items = $items->fetchAll(PDO::FETCH_ASSOC);

		// gen ssid and randpw (Fix by Greg)
		// also whispers are empty cuz echoed it
		$ssid = md5(microtime() . $playerID);
		$loco = rand(1, 999);
		$time = $GLOBALS["CURRTIME"];
		
		setcookie('ssid', $ssid);
		
		$qry = $db->prepare('UPDATE players SET whisper = "", ip = ?, ssid = ?, poll = ? WHERE ID = ?');
		$qry->execute([$ip, $ssid, $time, $playerData['ID']]);
		
		//By Jack
		//Infinite beers
		if($infiniteBeer) {
			$playerData['beers'] = 0;
			$db->exec("UPDATE players SET beers = 0 WHERE ID = '".$playerData['ID']."'");
		}
		
		//Infinite wheel spins
		if($infiniteWheel) {
			$playerData['wheelcounts'] = 0;
			$db->exec("UPDATE players SET wheelcounts = 0 WHERE ID = '".$playerData['ID']."'");
		}

		//get copycats
		$copycats = $db->query("SELECT * FROM copycats WHERE owner = ".$playerData['ID']." ORDER BY class ASC");
		$copycats = $copycats->fetchAll();

		//get messages
		$messages = $db->query('SELECT messages.ID, players.name, messages.hasRead, messages.topic, messages.time 
				FROM messages LEFT JOIN players ON messages.sender = players.ID WHERE reciver = '.$playerData['ID'].' ORDER BY time DESC');
		$messages = $messages->fetchAll(PDO::FETCH_ASSOC);

		//create account obj
		$acc = new Account($playerData, $items, $copycats, true);

		$acc->data['new_msg'] = $db->query('SELECT Count(ID) AS c FROM messages WHERE reciver = '.$playerData['ID'].' AND hasRead = false')->fetch(PDO::FETCH_ASSOC)['c'];
		
		//tutorial skip - 16777215
		if(!$tutorial) {
			if($acc->data['tutorial'] != -1 && $acc->data['tutorial'] != 16777215) // check tutorial
				//$db->exec("UPDATE players SET tutorial = '16777215' WHERE ID = '".$playerData['ID']."'");
				$db->exec("UPDATE players SET tutorial = -1 WHERE ID = '".$playerData['ID']."'");
				$acc->data['tutorial'] = -1;
		}
		
		//tutorial set
		if($tutorial && $acc->data['tutorial'] == -1) {
			$db->exec("UPDATE players SET tutorial = 0 WHERE ID = '".$playerData['ID']."'");
			$acc->data['tutorial'] = 0;
		}	
		
		
		$ret[] = "login count:".$loco;
		$ret[] = "sessionid:".$ssid;
		$ret[] = "inboxcapacity:100";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owndescription.s:".$playerData['description'];
		$ret[] = "ownplayername.r:".$acc->getName();
		$acc->data['allplayer'] = $db->query("SELECT Count(*) AS c FROM players WHERE honor > -1")->fetch(PDO::FETCH_ASSOC)['c'];
		$ret[] = "maxrank:".$acc->data['allplayer'];
		//$ret[] = "skipallow:1";
		$ret[] = "skipallow:".$adventureMushSkip;
		
		$uw = new Underworld($acc->data["ID"]);
		
		if($uw->haveIt){
			$ret[] = "underworldprice.underworldPrice(10):".$uw->getUpgradePrice();
			$ret[] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUnitUpgradePrice();
			$ret[] = "underworldmaxsouls:".$uw->getMaxSouls();
		}
		
		$ret[] = "fortresspricereroll:".$acc->fortressRerollPrice();
		$ret[] = "fortressprice.fortressPrice(13):".$acc->getFortressPriceSave();
		$ret[] = "fortressGroupPrice.fortressPrice:".$acc->getHallOfKnightsPriceSave();
		$ret[] = "unitprice.fortressPrice(3):".$acc->getTrainUnitsPrice();
		$ret[] = "upgradeprice.upgradePrice(3):".$acc->getUpgradeUnitsPrice();
		$ret[] = "unitlevel(4):".$acc->getUnitLvls();
		
		// Fixed in PetsSave
		// Blacksmith fix by Jack (4,999,999 bug)
		/*if($acc->data["blacksmith"] != null) {
			$blacksmith = $acc->data["blacksmith"];
		}else{
			$blacksmith = "0/0/0/0";
		}*/
		
		// Pets by Greg
		$pD = new Pets($acc->data["pets"], $acc->data["petsFed"], $acc->data["petsDung"], $acc->data["petsPvP"], $acc->data["petsBest2"], null, $acc->data['blacksmith'], $acc->data["pethonor"]);
		if($pD->havePets()) {
			$ret[] = "petsdefensetype:" . $pD->pvpData[0][1];
			$ret[] = "ownpets.petsSave:" . $pD->getPetsSave();
		}

		if(($fortressBackpackSize = $acc->getFortressBackpackSize()) > 0)
			$ret[] = "fortresschest.item(".$fortressBackpackSize."):".$acc->getFortressBackpackSave();


		$ret[] = "singleportalenemylevel:200";
		if($acc->hasTower()){
			$ret[] = "owntower.towerSave:".$acc->getTowerSave();
			$ret[] = "owntowerlevel:0"; // Idk
		}

		//guild		
		if($acc->hasGuild()){
			$guild = new Guild($acc->data['guild']);

			$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
			$ret[] = "owngrouppotion.r:".$guild->getPotionData();
			$ret[] = "owngroupknights.r:".$guild->getHokData();
			$ret[] = "owngroupname.r:".$guild->data['name'];
			$ret[] = "owngroupdescription.s:".$guild->data['descr'];
			$ret[] = "owngroupmember.r:".$guild->getMemberList();
			$ret[] = "owngrouprank:".$guild->getRank();
			if(($oga = $guild->getOwnGroupAttack()) !== false)
				$ret[] = $oga;

			
			$chattime = $db->query("SELECT Max(chattime) as chattime FROM guildchat WHERE guildID = $playerData[guild]")->fetch(PDO::FETCH_ASSOC)['chattime'];
			$chat = Chat::getChat($playerData['guild']);

			$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
			$ret[] = "chattime:$chattime";
			
			$ret[] = 'groupskillprice(6):'.$guild->getUpgradeSkillPriceSave();
		}

		// Whispers by Greg
		$whisper = Chat::formatWhispers($playerData['whisper']);
		if($whisper != '') {
			$ret[] = 'chatwhisper.s:'.$whisper;
		}

		//  Witch by Greg
		$ret[] = "witch.witchData:9/{$acc->getWitchData()}/1452384000/0/1402139157/9/6/51/1387968268/0/61/1389353441/5/31/1390907951/8/101/1392626428/1/71/1394196822/2/41/1396169319/4/81/1398237044/7/11/1400137421/3/91/1402139201/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/";
		
		$ret[] = "tavernspecial:".Misc::getEvent()[0];
		$ret[] = 'wagesperhour:'.Account::getWagesPerHour($playerData['lvl']);
		$ret[] = "dragongoldbonus:13";
		$ret[] = "toilettfull:".$acc->toiletFullToday();
		
		$ret[] = 'messagelist.r:'.Chat::formatMessages($messages);
		
		// Combatlog by Greg
		$ret[] = "combatloglist.s:".$acc->getCombatLog();

		// Friends by Greg - v2
		$ret[] = "friendlist.r:".$acc->friendList();
		
		if($acc->hasAlbum())
			$ret[] = "scrapbook.r:".$acc->album->data;
		//$ret[] = "skipallow:1";
		$ret[] = "skipallow:".$adventureMushSkip;
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		//$ret[] = "serverversion:1245";
		//$ret[] = "serverversion:1268";
		$ret[] = "serverversion:".$gameVersion;
		
		// Achievments by Greg
		$achi = $acc->achievments->getText();
		
		$ret[] = "achievement(100):" . implode("/", $achi);
		
		// Boi I ran
		//$ret[] = "fortresswalllevel:3";
		
		$ret[] = 'languagecodelist.r:'.Misc::getCountryID(0, true);
		
		$ret[] = $acc->getDungeonResponse();
		$ret[] = 'unlockfeature:'.$acc->unlockFeature();
		
		$ret[] = "success:";

		// cal
		$result = "";
		foreach (CAL as $pair) { $result .= $pair[0] . "/" . $pair[1] . "/"; }
		$ret[] = 'calenderinfo:'.rtrim($result, "/");
		
		break;
	case 'playerarenaenemy':
		//act used to get new enemies for arena

		$acc = new Account(null, null, false, false);



		//set new enemies for arena if time is up or have no enemies
		if($acc->data['arena_nme1'] == 0){
			//alg: get rank, get 20 enemies around, select 3 at random
			$rank = $db->query("SELECT Count(*) as `rank` FROM players WHERE ID <> {$acc->data['ID']} AND honor > ".$acc->data['honor']);
			$rank = $rank->fetch(PDO::FETCH_ASSOC)['rank'];

			if($rank < 10)
				$rank = 0;
			else
				$rank -= 10;

			$playerpool = $db->query("SELECT ID FROM players FORCE INDEX(honor) WHERE ID <> {$acc->data['ID']} AND honor >= 0 ORDER BY honor DESC, ID DESC LIMIT $rank, 20")->fetchAll(PDO::FETCH_ASSOC);

			if(count($playerpool) < 4)
				exit('&Error:no player data');

			//shuffle once
			shuffle($playerpool);

			//shuffle while play in first 3
			while($playerpool[0] == $playerID || $playerpool[1] == $playerID || $playerpool[2] == $playerID)
				shuffle($playerpool);


			$acc->data['arena_nme1'] = $playerpool[0]['ID'];
			$acc->data['arena_nme2'] = $playerpool[1]['ID'];
			$acc->data['arena_nme3'] = $playerpool[2]['ID'];

			$db->exec("UPDATE players SET arena_nme1 = ".$playerpool[0]['ID'].", arena_nme2 = ".$playerpool[1]['ID'].", arena_nme3 = ".$playerpool[2]['ID']." WHERE ID = $playerID");
		}


		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();

		break;
	
	case 'playershadowbattle':
		$acc = new Account(null, null, true, true);
		
		$dungeon = (int) $args[0];
		$dungeonID = Misc::dungNumberBattle($dungeon);
		
		$dungData = json_decode($acc->data['shadowdungeons'], true);

		$idols = false;
		if($dungeonID == 18 && $dungeon == 18) {
			$dung = $acc->data['idols'];
			if($dung < 0 or $dung >= 21) exit();
			$idols = true;
		} else {
			$dung = $dungData[$dungeonID];
			if($dung < 0 or $dung >= 10) exit();
		}

		$Level = $dung + 1;
		
		if($acc->data["mirror"] <= 12 && $acc->data["status"] > 0)
			exit("&error:cannot do this right now");
				
		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit("&error:need a free slot");
		
		$qryArgs = $fightlog = $params = [];

		if ($acc->data['dungeon_time'] > $GLOBALS["CURRTIME"]) {
			$qryArgs[] = "mush = mush - 1";
			$acc->data['mush']--;
			$qryArgs[] = "dungeon_time = :dungeon_time";
			$params[':dungeon_time'] = 0;
		} else {
			$qryArgs[] = "dungeon_time = :dungeon_time";
			$params[':dungeon_time'] = $GLOBALS["CURRTIME"] + 3600;
		}
		
		//if dung 9 boss, monster = acc, ID -1?
		if($dungeonID == 9 && $Level == 10) {
			$monster = clone($acc);
			$monster->exp = 10000000;
			$monster->gold = 1000000;
			$monster->ID = 0;
			$monster->hp = round($monster->hp * 2.5);
			$monster->maxHp = $monster->hp;
			//fightheader takes id from here
			$monster->data['ID'] = 0;
			$mirrorFight = true;
		}else{
			$monster = ($idols) ? Monster::getLoopMonsters($dung + 1) : Monster::getShadowMonster($dungeonID, $dung + 1);
			$mirrorFight = true;
		}

		$playerGroup = $acc->copycats;
		$playerGroup[] = $acc;

		$simulation = new GroupSimulation($playerGroup, [$monster]);
		$simulation->simulate();

		$bgId = $dungeon;
		if($dungeonID == 26) $bgId = 13;
		if($dungeonID == 22) $bgId = 14;
		if($dungeonID == 23) $bgId = 16;
		if($dungeonID == 25) $bgId = 17;
		$bg = $bgId + 50;

		for($i = 0; $i < count($simulation->simulations); $i++) {
			$fight = $i+1;
			$fightlog[] = "fightheader".$fight.".fighters:12/0/0/".$bg."/1/".$simulation->fightHeaders[$i];
			$fightlog[] = "fight".$fight.".r:".$simulation->simulations[$i]->fightLog;
			$fightlog[] = "winnerid".$fight.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$fightlog[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		
		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;

		if($simulation->win){
			//win true
			$rewardLog[0] = 1;
			//silver
			// $rewardLog[2] = 1;
			//exp
			$rewardLog[3] = $monster->exp;

			$acc->addExp($monster->exp);

			$qryArgs[] = "exp = :exp";
			$params[':exp'] = $acc->data['exp'];

			$qryArgs[] = "lvl = :lvl";
			$params[':lvl'] = $acc->data['lvl'];
			
			if ($idols) {
				$acc->data['idols']++;
				$qryArgs[] = "idols = :idols";
				$params[':idols'] = $acc->data['idols'];
			} else {
				$dungData[$dungeonID]++;
				$qryArgs[] = "shadowdungeons = :shadowdungeons";
				$params[':shadowdungeons'] = json_encode(array_values($dungData));
			}

			//item reward, always epic, random class, no silver value, NEVER SHIELD
			while(($itemid = mt_rand(1, 7)) == 2);
			$item = Item::genItem($itemid, $acc->lvl, mt_rand(1, 3), 100, 0, 'tower');
			$item['value_silver'] = 0;
			$itemReward = $acc->insertItem($item, $freeSlot);

			$i = 9;
			foreach($item as $s){
				$rewardLog[$i] = $s;
				$i++;
			}
			

			//album
			if($acc->hasAlbum() && !$mirrorFight){
				$a1 = $acc->album->addMonster($monster->ID);
				$a2 = isset($itemReward) ? $acc->album->addItem($itemReward) : false;
				if($a1 || $a2){
					$acc->album->encode();

					$ret[] = "scrapbook.r:".$acc->album->data;
					$acc->data['album'] = $acc->album->count;

					$qryArgs[] = "album = :album";
					$params[':album'] = $acc->album->count;
					
					$qryArgs[] = "album_data = :album_data";
					$params[':album_data'] = $acc->album->data;
				}
			}
			
			
		}

		$sql = "UPDATE players SET " . join(", ", $qryArgs) . " WHERE ID = :id";
		$params[':id'] = $acc->data['ID'];

		$stmt = $db->prepare($sql);
		foreach ($params as $key => &$val) {
			$stmt->bindParam($key, $val);
		}
		
		$stmt->execute();
		
		$fightlog = join('&', $fightlog);
		$ret[] = $fightlog;
		
		$rewardLog = join("/", $rewardLog)."/";
		

		//$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "fightresult.battlereward:".$rewardLog;
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owntower.towerSave:".$acc->getTowerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;

	case 'playertowerbattle':
		//arg 0 = tower lvl, fuck your input m8

		//args for query
		$qryArgs = [];
		
		//Tower logs by Jack
		$fightlog = [];

		$acc = new Account(null, null, true, true);
		
		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit("&Error:need a free slot");
		if($acc->data['dungeon_time'] > $GLOBALS["CURRTIME"]){ //if time not up
			if($acc->data['mush'] <= 0)
				exit("&Error:need more coins");
			$acc->data['mush']--;
			$qryArgs[] = "mush = mush - 1";
			$acc->data['dungeon_time'] = 0;
			$qryArgs[] = "dungeon_time = ".$acc->data['dungeon_time'];
		}else{
			$acc->data['dungeon_time'] = $GLOBALS["CURRTIME"] + 3600;
			$qryArgs[] = "dungeon_time = ".$acc->data['dungeon_time'];
		}

		$monster = Monster::getTowerMonster($acc->data['tower']);
		
		if($monster === null)
			exit("&Error:tower closed");
		
		$playerGroup = $acc->copycats;
		$playerGroup[] = $acc;

		$simulation = new GroupSimulation($playerGroup, [$monster]);
		$simulation->simulate();
		
		for($i = 0; $i < count($simulation->simulations); $i++){
			$fight = $i+1;
			$fightlog[] = "fightheader".$fight.".fighters:5/0/0/0/1/".$simulation->fightHeaders[$i];
			$fightlog[] = "fight".$fight.".r:".$simulation->simulations[$i]->fightLog;
			$fightlog[] = "winnerid".$fight.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$fightlog[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		
		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;

		if($simulation->win){
			//win true
			$rewardLog[0] = 1;
			//silver
			$rewardLog[2] = 1;
			//no exp for tower
			// $rewardLog[3] = $monster->exp;

			$acc->data['tower']++;
			$qryArgs[] = "tower = tower + 1";


			//item reward, always epic for random claass, no silver value, NOT SHIELD
			while(($itemid = mt_rand(1, 7)) == 2);
			$item = Item::genItem($itemid, $acc->lvl, mt_rand(1, 3), 100, 0, 'tower');
			$item['value_silver'] = 0;
			$itemReward = $acc->insertItem($item, $freeSlot);

			$i = 9;
			foreach($item as $s){
				$rewardLog[$i] = $s;
				$i++;
			}
			

			//album
			if($acc->hasAlbum()){
				$a1 = $acc->album->addMonster($monster->ID);
				$a2 = $acc->album->addItem($itemReward);
				if($a1 || $a2){
					$acc->album->encode();

					$ret[] = "scrapbook.r:".$acc->album->data;
					$acc->data['album'] = $acc->album->count;

					// $db->query("UPDATE players SET album = ".$acc->album->count.", album_data = '".$acc->album->data."' WHERE ID = ".$acc->data['ID']);
					$qryArgs[] = "album = ".$acc->album->count;
					$qryArgs[] = "album_data = '".$acc->album->data."'";
				}
			}
		}

		$db->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$acc->data['ID']);
		
		$fightlog = join('&', $fightlog);
		$ret[] = $fightlog;
		
		$rewardLog = join("/", $rewardLog)."/";

		//$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "fightresult.battlereward:".$rewardLog;
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = $acc->getDungeonResponse();
		break;
	
	case 'playerdungeonbattle':
		$acc = new Account(null, null, true, true);
		$dungeon = (int) $args[0];
		$dungeonID = Misc::dungNumberBattle($dungeon);
		
		$dungData = json_decode($acc->data['lightdungeons'], true);
		
		$twister = false;

		if($dungeonID == 15 && $dungeon == 15) {
			$dung = $acc->data['twister'];
			if($dung < 0 or $dung >= 1000) exit();
			$twister = true;
		} else {
			$dung = $dungData[$dungeonID];
			if($dung < 0 or $dung >= 10) exit();
		}

		$Level = $dung + 1;
		
		if($acc->data["mirror"] <= 12 && $acc->data["status"] > 0)
			exit("&error:cannot do this right now");
				
		if(($freeSlot = $acc->getFreeBackpackSlot()) === false && (in_array($Level, [3, 5, 7, 10]) || $twister === true))
			exit("&error:need a free slot");
		
		$qryArgs = $fightlog = $params = [];

		if ($acc->data['dungeon_time'] > $GLOBALS["CURRTIME"]) {
			$qryArgs[] = "mush = mush - 1";
			$acc->data['mush']--;
			$qryArgs[] = "dungeon_time = :dungeon_time";
			$params[':dungeon_time'] = 0;
		} else {
			$qryArgs[] = "dungeon_time = :dungeon_time";
			$params[':dungeon_time'] = $GLOBALS["CURRTIME"] + 3600;
		}
		
		//if dung 9 boss, monster = acc, ID -1?
		if($dungeonID == 9 && $Level == 10) {
			$monster = clone($acc);
			$monster->exp = 10000000;
			$monster->gold = 1000000;
			$monster->ID = 0;
			//fightheader takes id from here
			$monster->data['ID'] = 0;
			$mirrorFight = true;
		} else {
			$monster = ($twister) ? Monster::getTwisterMonster($dung + 1) : Monster::getDungeonMonster($dungeonID, $dung + 1);
			$mirrorFight = false;
		}
		
		$bgId = $dungeon;
		if($dungeonID == 26) $bgId = 13;
		if($dungeonID == 22) $bgId = 14;
		if($dungeonID == 23) $bgId = 16;
		if($dungeonID == 25) $bgId = 17;
				
		$bg = $bgId + 50;
		$fightlog[] = "fightheader.fighters:4/0/0/".$bg."/2/".$acc->getFightHeader().$monster->getFightHeader();
		
		$simulation = new Simulation($acc, $monster);
		$simulation->simulate();

		$fightlog[] = "fight.r:".$simulation->fightLog;
		$fightlog[] = "winnerid:".$simulation->winnerID;

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		if($simulation->winnerID == $acc->data['ID']) {
			if ($twister) {
				if (rand(1, 100) < 50) {
					$item = Item::genItem(rand(1, 10), $acc->lvl, $acc->class, 25, 0, 'dungeon');
					$item['value_silver'] *= 4;
					$itemReward = $acc->insertItem($item, $freeSlot);

					$i = 9;
					foreach($item as $s) {
						$rewardLog[$i] = $s;
						$i++;
					}
				}
			} else {
				if (in_array($Level, [3, 7])) {
					$item = Item::genItem(rand(1, 10), $acc->lvl, $acc->class, 0, 0, 'dungeon');
					$item['value_silver'] *= 4;
					$itemReward = $acc->insertItem($item, $freeSlot);

					$i = 9;
					foreach($item as $s) {
						$rewardLog[$i] = $s;
						$i++;
					}
				}
				
				if (in_array($Level, [5, 10])) {
					$item = Item::genItem(rand(1, 10), $acc->lvl, $acc->class, 100, 0, 'dungeon');
					$item['value_silver'] *= 4;
					$itemReward = $acc->insertItem($item, $freeSlot);

					$i = 9;
					foreach($item as $s) {
						$rewardLog[$i] = $s;
						$i++;
					}
				}
			}
			
			//album
			if($acc->hasAlbum() && !$mirrorFight){
				$a1 = $acc->album->addMonster($monster->ID);
				$a2 = isset($itemReward) ? $acc->album->addItem($itemReward) : false;
				if($a1 || $a2){
					$acc->album->encode();

					$ret[] = "scrapbook.r:".$acc->album->data;
					$acc->data['album'] = $acc->album->count;

					$qryArgs[] = "album = :album";
					$params[':album'] = $acc->album->count;
					
					$qryArgs[] = "album_data = :album_data";
					$params[':album_data'] = $acc->album->data;
				}
			}
			
			//win true
			$rewardLog[0] = 1;
			//silver
			$rewardLog[2] = 0;
			//exp
			$rewardLog[3] = $monster->exp;

			$acc->addExp($monster->exp);

			$qryArgs[] = "exp = :exp";
			$params[':exp'] = $acc->data['exp'];

			$qryArgs[] = "lvl = :lvl";
			$params[':lvl'] = $acc->data['lvl'];
			
			if ($twister) {
				$acc->data['twister']++;
				$qryArgs[] = "twister = :twister";
				$params[':twister'] = $acc->data['twister'];
			} else {
				$dungData[$dungeonID]++;
				$qryArgs[] = "lightdungeons = :lightdungeons";
				$params[':lightdungeons'] = json_encode(array_values($dungData));
			}
		}
		
		$sql = "UPDATE players SET " . join(", ", $qryArgs) . " WHERE ID = :id";
		$params[':id'] = $acc->data['ID'];

		$stmt = $db->prepare($sql);
		foreach ($params as $key => &$val) {
			$stmt->bindParam($key, $val);
		}
		
		$stmt->execute();

		$fightlog = join('&', $fightlog);
		$ret[] = $fightlog;
		
		$rewardLog = join("/", $rewardLog)."/";
		
		$ret[] = "fightresult.battlereward:".$rewardLog;
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owntower.towerSave:".$acc->getTowerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = $acc->getDungeonResponse();
	break;

	case 'playerportalbattle':

		//args for query
		$qryArgs = [];
		
		$fightlog = []; // Logs by Jack

		$acc = new Account(null, null, false, true);

		//error checking | no need for a free slot here
		// if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
		// 	exit("&Error:need a free slot");

		//if time not up | now = current day since start of the year
		if( ($now = Misc::getNow(false)) == $acc->data['portal_time'])
			exit("&Error:portal cooldown notice");

		//set new date and update db
		$acc->data['portal_time'] = $now;
		$qryArgs[] = 'portal_time = '.$acc->data['portal_time'];


		//set monster current hp to the hp from database
		$monster = Monster::getPortalMonster($acc->data['portal'] + 1);
		$monster->hp = $acc->data['portal_hp'];

		$fightlog[] = "fightheader.fighters:6/0/0/1/2/".$acc->getFightHeader().$monster->getFightHeader();


		$simulation = new Simulation($acc, $monster);
		$simulation->simulate();

		$fightlog[] = "fight.r:".$simulation->fightLog;
		$fightlog[] = "winnerid:".$simulation->winnerID;

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		//rewarding
		if($simulation->winnerID == $acc->data['ID']){
			//win true
			$rewardLog[0] = 1;

			$acc->data['portal']++;
			$qryArgs[] = 'portal = '.$acc->data['portal'];

			//update mob hp in database
			if($acc->data['portal'] < 50){
				$acc->data['portal_hp'] = Monster::getPortalMonster($acc->data['portal'] + 1)->hp;
				$qryArgs[] = 'portal_hp = '.$acc->data['portal_hp'];
			}

			//album
			if($acc->hasAlbum()){
				if($acc->album->addMonster($monster->ID)){
					$acc->album->encode();

					$ret[] = "scrapbook.r:".$acc->album->data;
					$acc->data['album'] = $acc->album->count;

					// $db->query("UPDATE players SET album = ".$acc->album->count.", album_data = '".$acc->album->data."' WHERE ID = ".$acc->data['ID']);
					$qryArgs[] = "album = ".$acc->album->count;
					$qryArgs[] = "album_data = '".$acc->album->data."'";
				}
			}
		}else{
			//if lost, update database with remaining hp of mob

			$qryArgs[] = 'portal_hp = '.$monster->hp;
			$acc->data['portal_hp'] = $monster->hp;

		}
		
		$db->exec("UPDATE players SET ".join(", ", $qryArgs)." WHERE ID = ".$acc->data['ID']);
		
		$fightlog = join('&', $fightlog);
		$ret[] = $fightlog;
		
		$rewardLog = join("/", $rewardLog)."/";
		
		//$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "fightresult.battlereward:".$rewardLog;
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = $acc->getDungeonResponse();
		break;
	case 'groupportalbattle':

		$qryArgs = [];
		$time = $GLOBALS["CURRTIME"];

		$acc = new Account(null, null, false, true);


		$now = md5(date("Y-m-d", $time));
		$portal = md5(date("Y-m-d", $acc->data['gportal_time']));
	
		$guild = new Guild($playerGuild);

		// Check by Greg
		if($now == $portal)
			exit();
		
		//set new date and update db
		$acc->data['gportal_time'] = $time;
		$guild->guildPortalCD($playerID);

		if($guild->data['portal'] >= 50)
			exit('&Error:');

		$monster = Monster::getGuildPortalMonster($guild->data['portal']);
		$monster->hp = $guild->data['portal_hp'];

		$ret[] = "fightheader.fighters:7/0/0/0/1/".$acc->getFightHeader().$monster->getFightHeader();

		$simulation = new Simulation($acc, $monster);
		$simulation->simulate();

		$ret[] = "fight.r:".$simulation->fightLog;
		$ret[] = "winnerid:".$simulation->winnerID;

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		//rewarding
		if($simulation->winnerID == $acc->data['ID']){
			//win true
			$rewardLog[0] = 1;

			//chat log
			$dmgdealt = $guild->data['portal_hp'] - $monster->hp;
			$log = "#pw#".$acc->data['name']."#".$guild->data['portal']."#$dmgdealt";

			$guild->data['portal']++;
			$acc->data['guild_portal']++;
			$qryArgs[] = 'portal = '.$guild->data['portal'];

			//update mob hp in database
			if($guild->data['portal'] < 50){
				$guild->data['portal_hp'] = Monster::getGuildPortalMonster($guild->data['portal'])->hp;
				$qryArgs[] = 'portal_hp = '.$guild->data['portal_hp'];
			}

			//album for all members
			$guild->addAlbumMonster($monster->ID + 1);


		}else{
			//if lost, update database with remaining hp of mob
			$dmgdealt = $guild->data['portal_hp'] - $monster->hp;

			$qryArgs[] = 'portal_hp = '.$monster->hp;
			$guild->data['portal_hp'] = $monster->hp;
			
			$hpLeftPrc = round($guild->data['portal_hp'] / $monster->maxHp * 100);
			$log = "#po#".$acc->data['name']."#".$guild->data['portal']."#$dmgdealt#$hpLeftPrc";
		}


		
		//insert log && update guilds
		// $db->exec("INSERT INTO guildchat(guildID, playerID, message, time) VALUES($playerGuild, $playerID, '$log', $time)");
		$db->exec("UPDATE guilds SET ".join(", ", $qryArgs)." WHERE ID = ".$guild->data['ID']);

		//get guild chat
		// $chat = $db->query("SELECT players.name, guildchat.message, guildchat.time FROM guildchat LEFT JOIN players ON guildchat.playerID = players.ID 
		// 		WHERE guildchat.guildID = $playerGuild AND guildchat.time > $playerPoll ORDER BY guildchat.time DESC LIMIT 5");
		// $chat = $chat->fetchAll();

		$chattime = Chat::chatInsert($log, $playerGuild, $playerID);
		$chat = Chat::getChat($playerGuild);

		//update player portal time and poll
		$db->exec("UPDATE players SET gportal_time = ".$acc->data['gportal_time'].", poll = $time WHERE ID = $playerID");


		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";
		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "owngroupsave.groupSave:".$guild->getGroupSave();
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:$time";


		break;
	case 'playertowerbuylevel':

		$acc = new Account(null, null, true, false);

		$copycat = $acc->copycats[$args[0] - 1];

		if(($cost = Copycat::getLvlCost($copycat->data['lvl'])) > $acc->data['silver'])
			exit('&Error:need more gold');

		$acc->data['silver'] -= $cost;
		$db->exec("UPDATE players SET silver = silver - $cost WHERE ID = ".$acc->data['ID']);

		$copycat->lvlUp();

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'owntowerlevel:200';
		$ret[] = 'owntower.towerSave:'.$acc->getTowerSave();
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];

		break;
	case 'playersetface':
	
		$acc = new Account(null, null, false, false);
		
		$acc->data["race"] = (int) $args[0];
		$acc->data["gender"] = (int) $args[1];
		$acc->data["face"] = (int) $args[2];
		$acc->data["mush"] -= 1;
		
		$qry = $db->prepare("UPDATE players SET race = ?, gender = ?, face = ?, mush = mush - 1 WHERE ID = ?");
		$qry->execute([
			$args[0],
			$args[1],
			$args[2],
			$acc->data['ID']
		]);

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();

		break;
	case 'groupgethalloffame':

		if(strlen($args[1]) > 2){
		
			$qry = $db->prepare('SELECT ID, honor FROM guilds WHERE name = :name');
			$qry->bindParam(':name', $args[1]);
			$qry->execute();

			if($qry->rowCount() == 0)
				exit('&Error:group not found');

			$p = $qry->fetch(PDO::FETCH_ASSOC);

			$qry = $db->query('SELECT Count(*) as `rank` FROM guilds WHERE honor > '.$p['honor'].' OR (honor = '.$p['honor'].' AND ID > '.$p['ID'].')');

			$args[0] = $qry->fetch(PDO::FETCH_ASSOC)['rank'];
			// var_dump($args[0]);
		}

		$args[0] -= $args[2] + 1;
		if($args[0] < 0)
			$args[0] = 0;

		//SELECT guilds.*, count(players.guild) AS membercount FROM guilds LEFT JOIN players ON guilds.ID = players.guild GROUP BY guild ORDER BY membercount DESC LIMIT 15;
		$qry = $db->prepare("SELECT guilds.ID as gID, guilds.name, GROUP_CONCAT(players.name ORDER BY guild_rank) AS leader, Count(*) AS membercount, guilds.honor, '0' FROM guilds FORCE INDEX(honor) LEFT JOIN players ON guilds.ID = players.guild WHERE guilds.honor >= 0 GROUP BY players.guild 
			ORDER BY guilds.honor DESC, guilds.ID DESC LIMIT :f, 30");
		$qry->bindParam(':f', $args[0], PDO::PARAM_INT);
		$qry->execute();

		$guilds = $qry->fetchAll( PDO::FETCH_ASSOC );

		
		$list = [];
		$rank = $args[0] + 1;
		// for($i = 0; $i < count($guilds); $i++) {
		// 	var_dump($guilds[$i]);
		// 	// $list[] = "$rank,$guilds[$i]"
		// }
		foreach($guilds as $g){
			$g['leader'] = explode(',', $g['leader'])[0];
			$list[] = "$rank,$g[name],$g[leader],$g[membercount],$g[honor],0"; 
			$rank++;
		}

						//rank, name, leader, memberc, honor, fightstatus
		// ranklistgroup.r:1,Asgard United,guzii,50,37728,0;
		$ret[] = 'ranklistgroup.r:'.join(';', $list);
		$ret[] = "Success:";


		break;
	case 'playergethalloffame':
	
		if(strlen($args[1]) > 2){
			
			$args[1] = Account::formatUser($args[1]);

			$qry = $db->prepare('SELECT ID, honor FROM players WHERE name = :name');
			$qry->bindParam(':name', $args[1]);
			$qry->execute();

			if($qry->rowCount() == 0)
				exit('&Error:player not found');

			$p = $qry->fetch(PDO::FETCH_ASSOC);

			$qry = $db->query('SELECT Count(*) as `rank` FROM players WHERE (honor > '.$p['honor'].' OR (honor = '.$p['honor'].' AND ID > '.$p['ID'].'))');

			$args[0] = $qry->fetch(PDO::FETCH_ASSOC)['rank'];
		}

		$args[0] -= $args[2] + 1;
		if($args[0] < 0)
			$args[0] = 0;

		
		$qry = $db->prepare("SELECT players.name, guilds.name AS gname, players.lvl, players.honor, players.class, players.flag FROM players FORCE INDEX(honor) LEFT JOIN guilds ON players.guild = guilds.ID 
			WHERE players.honor >= 0 ORDER BY players.honor DESC, players.ID DESC LIMIT {$args[0]}, 30");
		$qry->execute();

		$players = $qry->fetchAll( PDO::FETCH_ASSOC );
		
		$list = [];
		for($i = 0; $i < count($players); $i++) {
			$rank = $args[0] + $i + 1;
			$list[] = $rank.','.join(',', $players[$i]);
		}
		
		//rank, name, gname, lvl, honor, class
		$ret[] = "Ranklistplayer.r:".join(';', $list);
		$ret[] = "Success:";

		break;
	case 'playerlookat':
		
		$acc = new Account(null, null, false, false);
		
		$args[0] = $acc::formatUser($args[0]);
		
		// Other player data - Fortress data by Greg
		
		if($args[0] == "?"){
			$qry = $db->query("SELECT players.*, guilds.portal AS guild_portal, guilds.name AS gname FROM players LEFT join guilds ON players.guild = guilds.ID WHERE players.ID = (SELECT enemyid FROM fortress WHERE owner = $playerID)");
		}else if(is_numeric($args[0])){
			$qry = $db->query("SELECT players.*, guilds.portal AS guild_portal, guilds.name AS gname FROM players LEFT join guilds ON players.guild = guilds.ID WHERE players.ID = $args[0]");
		}else{
			$qry = $db->prepare("SELECT players.*, guilds.portal AS guild_portal, guilds.name AS gname FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.name = :name");
			$qry->bindParam(':name', $args[0]);
			$qry->execute();
		}

		if($qry->rowCount() <= 0)
			exit('&Error:player not found');

		$playerData = $qry->fetch(PDO::FETCH_ASSOC);

		$qry = $db->query("SELECT * FROM items WHERE owner = '{$playerData['ID']}' AND slot BETWEEN 10 AND 19");
		$items = $qry->fetchAll(PDO::FETCH_ASSOC);
		$player = new Player($playerData, $items);

		$ret[] = "otherplayergroupname.r:".$playerData['gname'];
		$ret[] = "otherplayer.playerlookat:".$player->getLookatSave();
		$ret[] = "otherdescription.s:".$playerData['description'];
		$ret[] = "b"; // Hey Beter
		$ret[] = "otherplayername.r:".$player->data['name'];
		$ret[] = "otherplayerunitlevel(4):".$player->fortressAttackLevels();
		
		// By Greg
		$ret[] = "otherplayerfriendstatus:".$acc->otherPlayerFriendStatus($playerData['ID']);
		
		$ret[] = "otherplayerfortressrank:0"; // Fortress rank not working
		$ret[] = "soldieradvice:0"; // Removed it cuz it's hard to make
		$ret[] = "fortresspricereroll:".$acc->fortressRerollPrice(); // Reroll price
		$ret[] = "success:";
		break;
	case 'playerpollscrapbook':
		//dunno when this is called or why


		$ret[] = "Success:";
		$albumData = $db->query("SELECT album_data FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC)['album_data'];

		$ret[] = "scrapbook.r:$albumData";


		break;
	case 'playerscrapbookcorrupt':
		//if scrapbook count in player data and count from data don't match, client calls this
		//arg0 clients count from album data

		$ret[] = "Success:";
		$albumData = $db->query("SELECT album_data FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC)['album_data'];

		$ret[] = "scrapbook.r:$albumData";

		break;
	case 'playeradventurestart':
		//arg 0 = quest

		$acc = new Account(null, null, false, false);
		
		// Warn if inventory is full - Jack
		if(($freeSlot = $acc->getFreeBackpackSlot()) === false && $args[1] == 0)
			exit("&Error:your backpack is full");

		$acc->questStart(intval($args[0]));


		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];


		break;
	case 'playeradventurestop':

		$acc = new Account(null, null, false, false);

		$acc->questStop();


		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'playeradventurefinished':
		$acc = new Account(null, null, false, true);

		$fightlog = []; // Save quest fight by Jack
		
		// Fix by Greg
		if($acc->data['status_extra'] == 0)
			exit("&Error:no quest atm");
		
		if ($acc->data['status'] != 2)
			exit("&Error:no quest atm");
		
		//see if skipped, take mushrooms
		if($acc->data['status_time'] > $GLOBALS["CURRTIME"] && (($acc->data['mush'] <= 0 && $args[0] != "2")))
			exit('&Error:need more coins');
		else if($acc->data['status_time'] > $GLOBALS["CURRTIME"] && (($acc->data['hourglass'] <= 0 && $args[0] == "2")))
			exit("&Error:malformed response");
			
		$equipStats = $acc->getEquipStats();
		
		$w = $acc->data['wit'] + $equipStats['wit'];
		
		$dmg_min = round($w / 14);
		$dmg_max = round($w / 12);
		$hp = round(($w / 10) * 4 * ($acc->data['lvl'] + 1));
		
		$monsterID = ($acc->data['quest_exp'.$acc->data['status_extra']] % 163) + 1;
		$monster = new Monster($acc->data['lvl'], 2, ($acc->data['str'] / 2), ($acc->data['dex'] / 2), ($acc->data['intel'] / 2), ($acc->data['wit'] / 2), ($acc->data['luck'] / 2), $dmg_min, $dmg_max, $hp, 1, -$monsterID, 1, 10);
		
		$bg = $acc->questBackground($acc->data['quest_exp'.$acc->data['status_extra']]);
		
		$fightlog[] = "fightheader.fighters:1/0/0/".$bg."/0/".$acc->getFightHeader().$monster->getFightHeader();
		
		$simulation = new Simulation($acc, $monster);
		$simulation->simulate();

		$win = $simulation->winnerID == $acc->data['ID'];
		
		$fightlog[] = "fight.r:".$simulation->fightLog;
		$fightlog[] = "winnerid:".$simulation->winnerID;
		
		$fightlog = join('&', $fightlog);
		$ret[] = $fightlog;
		
		$battleReward = $acc->questFinish($win, $monsterID, $args[0]); // Finish quest
		
		
		
		//$ret[] = "fightresult.battlereward:".$acc->questFinish($win, $monsterID, $args[0]);
		$ret[] = "fightresult.battlereward:".$battleReward;
		$ret[] = "Success:";
		
		if(isset($RENEW))
			$acc = new Account(null, null, false, true);
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		
		if(($fortressBackpackSize = $acc->getFortressBackpackSize()) > 0)
			$ret[] = "fortresschest.item(".$fortressBackpackSize."):".$acc->getFortressBackpackSave();

		$ret[] = 'unlockfeature:'.$acc->unlockFeature();
		break;
	case 'playerworkstart':
		$hours = (int) $args[0];
		if ($hours < 1 || $hours > 10) exit;
		
		$acc = new Account($playerData, null, false, false);
		
		if ($acc->data["status"] != 0)
			exit ('&error:invalid status');

		$statusTime = $GLOBALS["CURRTIME"] + 3600 * $hours;

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:45/1/47/'.$statusTime;

		$db->exec("UPDATE players SET status = 1, status_time = $statusTime, status_extra = $hours WHERE ID = ".$acc->data['ID']);
		break;
	case 'playerworkstop':

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:45/0/47/0';

		$qry = $db->prepare('UPDATE players SET status = 0, status_extra = 0, status_time = 0 WHERE ID = :ID');
		$qry->execute([':ID' => $playerID]);

		break;
	case 'playerworkfinished':
		$acc = new Account($playerData, null, false, false);
		
		if ($acc->data["status"] != 1)
			exit ('&error:invalid status');
		
		if ($acc->data["status_time"] > $CURRTIME)
			exit ('&error:invalid status');

		$reward = Account::getWagesPerHour($acc->data['lvl']) * $acc->data['status_extra'];

		$db->exec("UPDATE players SET workedhours = workedhours + ".$acc->data['status_extra'].", status = 0, status_extra = 0, status_time = 0, silver = silver + $reward WHERE ID = ".$acc->data['ID']);

		$ret[] = 'Success:';
		$ret[] = "workreward:$reward";

		$reward += $acc->data['silver'];

		$ret[] = "#ownplayersave.playerSave:13/$reward/45/0/47/0";

		break;
	case 'playeritemmove':
		
		$class = new Account(null, null, false, false);
		$class = $class->data['class'];
		
		for ($index = 0; $index < count($args); $index++) {
			$args[$index] = intval($args[$index]);
		}
		
		if($class != 4) {
			if($args[0] == 1  && $args[2] == 1)
				exit("Success:");
		}	

		if($args[0] == 4  && $args[2] == 4)
			exit("Success:");

		if($args[0] == 3  && $args[2] == 3)
			exit("Success:");
		
		// Assassin move equipped swords - Fix
		if($args[0] == 1 && $args[2] == 1 && $class == 4) {
			if($args[1] != 9 && $args[1] != 10 && $args[3] != 9 && $args[3] != 10)
				exit("Success:");
		}	

		if($args[0] == 1 && $args[2] == 12)
			exit('&Error:you cannot sell from here');

		if($args[2] == 3 || $args[2] == 4)
			if($args[0] == 1)
				exit('&Error:you cannot sell from here');


		$itemBought = false;

		//if source shops, load album
		if($args[0] == 3 || $args[0] == 4)
			$itemBought = true;
		

		$acc = new Account(null, null, true, $itemBought);

		$acc->moveItem($args);
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		if(($fortressBackpackSize = $acc->getFortressBackpackSize()) > 0)
			$ret[] = "fortresschest.item(".$fortressBackpackSize."):".$acc->getFortressBackpackSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = 'unlockfeature:'.$acc->unlockFeature();


		break;
	case 'playertoilettflush':

		$acc = new Account(null, null);

		if($acc->toiletFull() != false)
			exit('&Error:toilett is not full');

		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit('&Error:need a free slot');

		$db->exec("UPDATE players SET wcaura = wcaura + 1, wcexp = 0 WHERE ID = $playerID");
		$acc->data['wcaura']++;
		$acc->data['wcexp'] = 0;

		//last arg - epic chance
		$item = Item::genItem(rand(1, 10), $acc->lvl, $acc->class, 100);

		$acc->insertItem($item, $freeSlot);


		$freeSlot += $freeSlot >= 100 ? -94 : +1;
		$ret[] = 'Success:';
		$ret[] = 'toilettspawnslot:'.$freeSlot;
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		if($freeSlot >= 100)
			$ret[] = "fortresschest.item(".$acc->getFortressBackpackSize()."):".$acc->getFortressBackpackSave();

		break;
	case 'playerpotionkill':
		$args[0] = intval($args[0]);
		$acc = new Account(null, null, false, false);

		$acc->data['potion_dur'.$args[0]] = 0;
		$acc->data['potion_type'.$args[0]] = 0;

		$db->exec("UPDATE players SET potion_dur$args[0] = 0 WHERE ID = ".$acc->data['ID']);

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'playerattributincrease': // Statbuy
		$args[0] = intval($args[0]);
		$args[0]--;

		$stat = ['str', 'dex', 'intel', 'wit', 'luck'][$args[0]];

		$qry = $db->prepare('SELECT ID, silver, '.$stat.' FROM players WHERE ssid = :ssid');
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$playerData = $qry->fetch(PDO::FETCH_ASSOC);

		$price = Account::getStatPrice($playerData[$stat] - 10);

		if($price > $playerData['silver'])
			exit("&Error:need more gold");

		$db->exec("UPDATE players SET silver = silver - $price, $stat = $stat + $statPlus WHERE ID = $playerData[ID]");

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:13/'.($playerData['silver'] - $price).'/'.($args[0] + 30).'/'.($playerData[$stat] + 25).'/'.($args[0] + 40).'/'.($playerData[$stat] - 9);



		break;
	case 'playerbeerbuy':

		$qry = $db->prepare('SELECT ID, thirst, beers, mush, class FROM players WHERE ssid = :ssid');
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$playerData = $qry->fetch(PDO::FETCH_ASSOC);


		if($playerData['thirst'] > 4800)
			exit("&Error:2muchthirst");

		/*if($playerData['mush'] <= 0)
			exit('&Error:need more coins');*/
		
		if($playerData['mush'] <= 0 && !$oktoberfest)
			exit('&Error:cannot afford beer');
			//exit('&Error:need more coins');

		if($playerData['beers'] >= 11)
			exit("&Error:max beers");

		// Beer update by Jack
		if(!$infiniteBeer)
		{
			$playerData['beers']++;
		}	
		$playerData['thirst'] += 1200;
		
		if($oktoberfest == false)
			$playerData['mush']--;

		//temporary for reseting portal timers, to revert, just switch out the comments and edit playersave
		//$db->exec('UPDATE players SET portal_time = 0, gportal_time = 0 WHERE ID = '.$playerData['ID']); // portals
		
		$db->exec('UPDATE players SET thirst = thirst + 1200 WHERE ID = '.$playerData['ID']);
		if(!$infiniteBeer)
			$db->exec('UPDATE players SET beers = beers + 1 WHERE ID = '.$playerData['ID']);
		else
			$db->exec('UPDATE players SET beers = 0 WHERE ID = '.$playerData['ID']);
		
		$db->exec('UPDATE players SET mush = '.$playerData['mush'].' WHERE ID = '.$playerData['ID']);
		//$db->exec("UPDATE players SET mush = mush - 1, thirst = thirst + 1200, beers = beers + 1, portal_time = 0, gportal_time = 0 WHERE ID = $playerID");
		$guild = new Guild($playerGuild);
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();

		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:14/'.$playerData['mush'].'/456/'.$playerData['thirst'].'/457/'.$playerData['beers'].'/29/'.$playerData['class'];

		break;
	case 'playernewwares':

		$acc = new Account(null, null, false, false);

		$acc->rerollShop(intval($args[0]));

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'playermountbuy':
		$args[0] = intval($args[0]);
		$acc = new Account($playerData, null, false, false);
		
		//mush cost
		$costMush = [0, 0, 1, 25][$args[0] - 1];
		if($costMush > $acc->data['mush'])
			exit('&Error:need more coins');

		//gold cost
		$costSilver = [100, 500, 1000, 0][$args[0] - 1];
		if($costSilver > $acc->data['silver'])
			exit('&Error:need more gold');

		$acc->data['mush'] -= $costMush;
		$acc->data['silver'] -= $costSilver;
		$resp = [];

		//if same mount, and time not expired, just inscrease the time
		if($acc->data['mount'] == $args[0] && $acc->data['mount_time'] > $GLOBALS["CURRTIME"]){
			$acc->data['mount_time'] += 1209600;
		}else{
			$acc->data['mount'] = $args[0];
			$acc->data['mount_time'] = $GLOBALS["CURRTIME"] + 1209600;

			$mountMultiplier = [0.9, 0.8, 0.7, 0.5][$args[0] - 1];

			for($i = 1; $i <= 3; $i++){
				$resp[] = (240 + $i).'/'.ceil($acc->data["quest_dur$i"] * $mountMultiplier);
			}
		}


		$resp[] = '13/'.$acc->data['silver'].'/14/'.$acc->data['mush'].'/286/'.($acc->data['tower'] * 65536 + $args[0]).'/451/'.$acc->data['mount_time'];

		$db->exec("UPDATE players SET mush = mush - $costMush, silver = silver - $costSilver, mount = $args[0], mount_time = ".$acc->data['mount_time'].' WHERE ID = '.$acc->data['ID']);
		
		$ret[] = 'Success:';
		$ret[] = '#ownplayersave.playerSave:'.join('/', $resp);
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];

		break;
	case 'playerwitchenchantitem':
		//arg 0 = enchant id counted from left to right

		//table of item types per enchant
		$itemType = [5, 6, 3, 10, 7, 4, 8, 1, 9][$args[0] - 1];

		$acc = new Account(null, null, false, false);
		
		$encd = false;
		
		$allenc = true;
		
		foreach($acc->equip as $item){
			if($item->type == $itemType && !$item->enchanted){
				$item->enchant();
				$encd = true;
				break;
			}
			else if (!$item->enchanted)
				$allenc = false;
		}
		
		if($encd) {
			$sql = "UPDATE players SET silver = silver - {$acc->fortressRerollPrice()} WHERE ID = '{$acc->data['ID']}'";
			$db->exec($sql);
			$acc->data['silver'] -= $acc->fortressRerollPrice();
		}
		
		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'timestamp'.$GLOBALS["CURRTIME"];

		break;
	case 'playermessagesend':

		//args: reciver/topic/message

		//reserve single numeric topic for system, simplier solution
		if(strlen($args[1]) == 1 && is_numeric($args[1]))
			exit();

		$qry = $db->prepare('SELECT ID, friends FROM players WHERE name = :name');
		$qry->bindParam(':name', $args[0]);
		$qry->execute();
		$fetch = $qry->fetch(PDO::FETCH_ASSOC);
		
		if($qry->rowCount() == 0)
			exit('&Error:recipient not found');

		if(Account::isUserIgnored($fetch['friends'], $playerID))
			exit("&Error:player not found"); // player is ignored
		
		$reciver = $fetch['ID'];
		$time = $GLOBALS["CURRTIME"];
		
		$qry = $db->prepare("INSERT INTO messages(sender, reciver, time, topic, message) VALUES($playerID, $reciver, $time, :topic, :message)");
		$qry->bindParam(':topic', $args[1]);
		$qry->bindParam(':message', $args[2]);
		$qry->execute();

		exit('Success:');

		break;
	case 'fortressbuildstart':
		//building id
		$args[0] --;

		$acc = new Account(null, null, false, false);

		$acc->fortressBuild(intval($args[0]));


		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressbuildstop':
		//REMEMBER TO REDUCE RETURNED RESOURCES
		//arg building id, fuck yo input m89

		$acc = new Account(null, null, false, false);

		$acc->fortressBuildStop();

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressbuildfinished':

		$acc = new Account(null, null, false, false);
		
		$acc->fortressBuildFinish();

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "fortressprice.fortressPrice(13):".$acc->getFortressPriceSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		//i guess if upgraded bank, mines or other shit that need update, include them
		$args[0]--;
		if($args[0] == 9){ 
			//fortress backpack
			$ret[] = "fortresschest.item(".$acc->getFortressBackpackSize()."):".$acc->getFortressBackpackSave();
		}

		break;
	case 'fortressgemstonestart':

		$acc = new Account(null, null, false, false);

		$acc->fortressDigStart();

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "fortressprice.fortressPrice(13):".$acc->getFortressPriceSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		
		break;
	case 'fortressgemstonestop':

		$acc = new Account(null, null, false, false);

		$acc->fortressDigStop();

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressgemstonefinished':

		$acc = new Account(null, null, false, false);

		//client check if there is a free slot, doesn't send the request if there is no space, but it's better to keep this here
		if(($freeSlot = $acc->getFreeBackpackSlot()) === false)
			exit("&Error:need a free slot");

		//checks if time's up, if enough mushrooms, resets db
		$acc->fortressDigFinish();

		// allhok by Greg
		if($acc->hasGuild()) {
			$allhok = $acc::getAllHok($acc->data['guild']);
		}else{
			//$allhok = 0;
			$allhok = $acc->data['hok']; // v2 - No guild, but we keep the user's hok
		}
		
		//class is not needed, but have it anyway in case i wanna have higher chance for class specific gems or whatever
		//send hall of knights level as epic chance, so bigger gem stat	(by Greg)
		$gem = Item::genItem(15, $acc->lvl, $acc->class, $allhok, $acc->data['b4']);
		
		if(!$defAllDungUnlocked && $acc->data['b4'] >= 10 && $acc->data['lvl'] >= 90 && $acc->data['pets'] != null) {
			
			$qry = $db->prepare('SELECT * FROM underworld WHERE owner = :owner');
			$qry->bindParam(':owner', $acc->data['ID']);
			$qry->execute();

			if($qry->rowCount() <= 0 && !$acc->heartInInv() && rand(1, 10) == 1) {
				$gem = Item::genItem(18, $acc->lvl, $acc->class, $allhok, $acc->data['b4']);
			}	
			
		}

		$acc->insertItem($gem, $freeSlot);


		$freeSlot += $freeSlot >= 100 ? -94 : +1;
		$ret[] = "gemstonebackpackslot:".$freeSlot;
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressgather':

		$acc = new Account(null, null, false, false);

		$acc->fortressGather(intval($args[0]));

		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "fortressprice.fortressPrice(13):".$acc->getFortressPriceSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];

		break;
	case 'fortressenemy':
		
		$acc = new Account(null, null, false, false);
		
		if($args[0] == '1')
			$acc->newFortressEnemy(); // New enemy
		
		if($acc->data["enemyid"] == 0)
			$acc->newFortressEnemy(false); // New enemy (required) - without money
		
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		
		// Reusing playerlookat so we just need the id
		$ret[] = "otherplayer.playerlookat:{$acc->data['enemyid']}/0/65537/0/100/100/2/0/2/307/305/5/304/1/4/9/0/0/6/1/3/10/10/10/10/10/0/3/0/3/3/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/65537/2001/2/6/2/4/5/3/3/3/1/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/10005/0/0/0/0/0/2/6/0/0/0/123/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/0/";
		$ret[] = "Success:";


		break;
	case 'fortressattack':
		// Fortress Attack by Greg
		
		$q = $args[0]; // How much soldiers

		if($q < 1) {
			exit(); // Zero soldiers wtf
		}
		
		$acc = new Account(null, null, false, false);
		
		$ret[] = implode("&", $acc->fortressAttack($q));
		$ret[] = "Success:";
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = "combatloglist.s:".$acc->getCombatLog(); // Update logs
		
		break;
	case 'fortressupgrade':

		$acc = new Account(null, null, false, false);

		$acc->fortressUnitUpgrade(intval($args[0]));

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'unitprice.fortressPrice(3):'.$acc->getTrainUnitsPrice();
		$ret[] = 'upgradeprice.upgradePrice(3):'.$acc->getUpgradeUnitsPrice();
		$ret[] = 'unitlevel(4):'.$acc->getUnitLvls();

		break;
	case 'fortressbuildunitstart':

		$acc = new Account(null, null, false, false);

		$acc->fortressUnitTrain(intval($args[0]), intval($args[1]));

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();


		break;
	case 'fortressgroupbonusupgrade':

		// Upgrading this fortress bonus shit
		// Aka hall of knights upgrade
		// By Greg
	
		$acc = new Account(null, null, false, false);
		$guild = new Guild($acc->data['guild']);

		$hokp = Fortress::getHallOfKnightsPrice($acc->data['hok']);
		
		if(!$hokp[4]) {
			exit("Success:");
		}
		
		if($hokp[2] > $acc->data['wood'] || $hokp[3] > $acc->data['stone']) {
			exit();
		}
		
		$acc->data['wood'] -= $hokp[2];
		$acc->data['stone'] -= $hokp[3];
		$acc->data['hok'] += 1;
		
		// Update datas
		$db->exec("UPDATE fortress SET wood = '{$acc->data['wood']}', stone = '{$acc->data['stone']}', hok = '{$acc->data['hok']}' WHERE owner = '$playerID'");

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		//$ret[] = 'othergroup.groupSave:'.$guild->getGroupSave();
		$ret[] = "fortressGroupPrice.fortressPrice:".$acc->getHallOfKnightsPriceSave();
		
		if($playerGuild > 0) {
			$guild = new Guild($playerGuild);
			if (!empty($guild->data)) {
				$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
				$ret[] = "owngrouppotion.r:".$guild->getPotionData();
				$ret[] = "owngroupknights.r:".$guild->getHokData();
				$ret[] = "owngroupname.r:".$guild->data['name'];
				$ret[] = "owngroupdescription.s:".$guild->data['descr'];
				$ret[] = "owngroupmember.r:".$guild->getMemberList();
				$ret[] = "owngrouprank:".$guild->getRank();
			}
		}

		break;
	case 'groupfound':
		//create guild
		//arg 0 name
		$acc = new Account(null, null, false, false);
		
		if ($acc->data['guild'] > 0)
			exit("&error:must leave group first");
		
		if ($acc->data['silver'] < 1000)
			exit('&Error:need more gold');
		
		$name = $args[0] ?? '';
		
		if(preg_match('/[^A-Za-z0-9 ]/', $name))
			exit('&Error:groupname is not available');
		if(strlen($name) > 17)
			exit('&Error:groupname is not available');
		if(strlen($name) < 4)
			exit('&Error:groupname is not available');
		if(is_numeric($name))
			exit('&Error:groupname is not available');

		$qry = $db->prepare('SELECT ID FROM guilds WHERE name = :name');
		$qry->execute([':name' => $name]);

		if($qry->rowCount() > 0)
			exit('&Error:groupname is not available');
		
		$qry = $db->prepare('INSERT INTO guilds(name) VALUES(:name)');
		$qry->execute([':name' => $name]);
		$guildID = $db->lastInsertId();
		
		$acc->data['silver'] -= 1000;
		$acc->data['guild'] = $guildID;
		$acc->data['guild_rank'] = 1;
		$acc->data['event_trigger_count'] = 0;
		$acc->data['guild_fight'] = 0;
		
		$qry = $db->prepare('UPDATE players SET guild = :guild, guild_rank = 1, silver = :silver, event_trigger_count = 0, guild_fight = 0 WHERE ID = :ID');
		$qry->execute([':guild' => $guildID, ':silver' => $acc->data['silver'], ':ID' => $acc->data['ID']]);

		$time = $GLOBALS["CURRTIME"];
		$message = '#in#'.$acc->data['name'];
		$qry = $db->prepare("INSERT INTO guildchat(guildID, playerID, message, time, chattime) VALUES(?, ?, ?, ?, 1)");
		$qry->execute([$guildID, $acc->data['ID'], $message, $time]);

		$guild = new Guild($guildID);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = "owngrouppotion.r:".$guild->getPotionData();
		$ret[] = "owngroupknights.r:".$guild->getHokData();
		$ret[] = "owngroupname.r:".$guild->data['name'];
		$ret[] = "owngroupdescription.s:".$guild->data['descr'];
		$ret[] = "owngroupmember.r:".$guild->getMemberList();
		$ret[] = "owngrouprank:".$guild->getRank();
		$ret[] = 'groupskillprice(6):'.$guild->getUpgradeSkillPriceSave();
		$ret[] = 'timestamp:'.$CURRTIME;

		break;
	case 'playerarenafight':

		$qryArgs = [];
		$fightlog = []; // By Jack

		//get opponent
		$qry = $db->prepare("SELECT players.*, guilds.portal AS guild_portal FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.name = :name");
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		//player not found
		if($qry->rowCount() == 0)
			exit("&Error:player not found");

		$opponentData = $qry->fetch(PDO::FETCH_ASSOC);

		$items = $db->query("SELECT * FROM items WHERE owner = ".$opponentData['ID']." AND slot BETWEEN 10 AND 19");
		$items = $items->fetchAll(PDO::FETCH_ASSOC);

		$opponent = new Player($opponentData, $items);

		//init account
		$acc = new Account(null, null, false, true);
		
		// Check if self fight
		if($acc->data['ID'] == $opponentData['ID'])
			exit('&Error:');
		
		if($acc->data['arena_time'] > $GLOBALS["CURRTIME"]){
			if($acc->data['mush'] < 0)
				exit('&Error:need more coins');
			else{
				$acc->data['mush']--;
				$qryArgs[] = 'mush = mush - 1';
			}
		}else{
			$acc->data['arena_time'] = $GLOBALS["CURRTIME"] + 600;
			$qryArgs[] = 'arena_time = '.$acc->data['arena_time'];
		}

		$fightlog[] = "fightheader.fighters:0/0/0/0/1/".$acc->getFightHeader().$opponent->getFightHeader();

		$simulation = new Simulation($acc, $opponent);
		$simulation->simulate();

		//max honor diff = 2k
		//formula: 100 + (opponent.honor - player.honor) / (max honor diff / 100)
		if($opponent->data['honor'] > $acc->data['honor'])
			$honor = min(200, 100 + round(($opponent->data['honor'] - $acc->data['honor']) / 20));
		else
			$honor = max(0, 100 + round(($opponent->data['honor'] - $acc->data['honor']) / 20));
		

		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;

		//album items before player save
		if($simulation->winnerID == $acc->data['ID']){

			// Set fights won + 1
			$acc->data['fightswon']++;
			$qryArgs[] = "fightswon = fightswon + 1";
			
			$rewardLog[0] = 1;

			$rewardLog[5] = $honor;
			$qryArgs[] = "honor = honor + $honor";

			$db->exec("UPDATE players SET honor = GREATEST(0, honor - $honor) WHERE ID = ".$opponent->data['ID']);

			if($acc->hasAlbum() && $acc->album->addItems($opponent->equip)){
				$acc->album->encode();
				// $db->exec("UPDATE players SET album_data = '".$acc->album->data."', album = ".$acc->album->count." WHERE ID = ".$acc->data['ID']);
				$qryArgs[] = 'album_data = "'.$acc->album->data.'", album = '.$acc->album->count;
				$ret[] = "scrapbook.r:".$acc->album->data;
				$acc->data['album'] = $acc->album->count;
			}
		}else{
			$honor = 200 - $honor;

			$rewardLog[5] = '-'.$honor;
			$qryArgs[] = "honor = GREATEST(0, honor - $honor)";

			$db->exec("UPDATE players SET honor = honor + $honor WHERE ID = ".$opponent->data['ID']);
		}

		//reset arena enemies
		for($i = 1; $i <= 3; $i++){
			$acc->data["arena_nme$i"] = 0;
			$qryArgs[] = "arena_nme$i = 0";
		}


		$db->exec("UPDATE players SET ".join(',', $qryArgs)." WHERE ID = $playerID");
		

		$fightlog[] = "fight.r:".$simulation->fightLog;
		$fightlog[] = "winnerid:".$simulation->winnerID;
		
		$fightlog = join('&', $fightlog);
		$ret[] = $fightlog;
		
		$ret[] = "Success:";
		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "combatloglist.s:".$acc->getCombatLog(); // Update logs
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		// $ret[] = "combatloglist.s:178047146,Ragnarak,1,0,1453561071,0;1829371711,Mrozu,0,9,1453548804,0;2019179682,Arbuz,0,0,1453542838,0;608863024,Fort Szatana,1,2,1453536855,0;749062124,Schwarze Seelen,0,2,1453529461,0;1756257553,Smoke,1,9,1453527707,0;1430622921,KleinesGrÃ¼nesMÃ¤nnchen,1,0,1453498654,0;690391557,Fort Szatana,1,2,1453494628,0;209357733,Schwarze Seelen,0,2,1453484127,0;1069100244,Yufie,0,0,1453470289,0;1577088167,Fort Szatana,1,2,1453449488,0;1085402077,KeMi,0,0,1453448193,0;1891565546,Schwarze Seelen,0,2,1453439977,0;615269297,FaiX,0,0,1453410393,0;2118894081,Gnadenlos,1,2,1453405221,0;59208430,Mysticwoman,1,0,1453392274,0;937567384,Gnadenlos,1,2,1453355203,0;1508609115,Schwarze Seelen,0,2,1453352471,0;1891729205,spino,1,0,1453329887,0;35842795,Gnadenlos,1,2,1453313103,0;931279938,Schwarze Seelen,0,2,1453310626,0;1192275299,Yulivee,0,0,1453297521,0;1141435371,Aviro,0,0,1453280641,0;721157918,Yulivee,0,0,1453277920,0;2033375401,Yulivee,0,0,1453274653,0;1245941037,Gnadenlos,1,2,1453268569,0;1954219141,Schwarze Seelen,0,2,1453267929,0;1987180995,Petter,0,0,1453229942,0;932256980,Gnadenlos,1,2,1453225438,0;1134002502,Schwarze Seelen,0,2,1453225037,0;1463110198,Mysticwoman,1,0,1453209612,0;908422992,crpzh,1,0,1453207557,0;1239827316,Swordrain,0,0,1453206326,0;1135909002,Gnadenlos,1,2,1453180647,0;1367861018,crpzh,0,0,1453168359,0;811858250,X9Rambo6X,1,0,1453156913,0;866597934,Momochi,0,9,1453151581,0;1426181582,Terrorman79,1,0,1453148036,0;165539174,18,0,3,1453136739,0;1214503715,ChallEnGeRRR,0,0,1453131933,0;1220250895,zarondechanger,1,0,1453114143,0;1927290930,17,1,3,1453093834,0;784169800,GrupaAzoty,1,2,1453082945,0;173641481,crpzh,0,0,1453068685,0;2030313976,crpzh,0,0,1453053120,0;1703023368,Deathrix,1,0,1453052841,0;1171886111,16,1,3,1453051246,0;601901905,FaiX,1,0,1453038432,0;576197653,Jan,0,0,1453035790,0;1854324173,audia17,1,0,1453032401,0;";



		break;
	case 'wheeloffortune':
		
		// Wheel of fortune by Greg
		
		// Load basic datas
		$qry = $db->prepare("SELECT lvl, wheelcounts, newwheel, mush FROM players WHERE ssid = :ssid");
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$prpa = $qry->fetch(PDO::FETCH_ASSOC);
		$prlvl = $prpa['lvl'];
		
		if($prpa['wheelcounts'] >= 20) {
			exit("&Error:need more gold"); // Anit-cheat lol
		}
		
		if($prpa['newwheel'] > $GLOBALS["CURRTIME"]) {
			$prpa['mush'] -= 1;
			if($prpa['mush'] < 0) {
				exit("&Error:need more coins");
			}
		}
		
		$prpa['newwheel'] = strtotime('tomorrow');
		
		$db->exec("UPDATE players SET mush = mush - 1, wheelcounts = wheelcounts + 1, newwheel = '".$prpa['newwheel']."' WHERE ID = $playerID");
		
		$qry = $db->prepare("SELECT b0, wood, stone FROM fortress WHERE owner = :pid");
		$qry->bindParam(':pid', $playerID);
		$qry->execute();
		$fortdata = $qry->fetch(PDO::FETCH_ASSOC);
	
		$acc = new Account(null, null, false, false);
	
		// Lets start
		$r = rand(1, 5); // 1 = Gold, 2 = XP, 3 = Wood, 4 = Stone, 5 = Mushroom
		
		$more = rand(1, 3) == 3 ? TRUE : FALSE;
		
		// Where are
		$bonus = array(4, 2, 6, 8);
		$normal = array(9, 7, 1, 3);
		
		// Default values
		$waa = 0;
		$muu = rand(50, 250);
		
		// Generate what and how much
		if($more) {
			// Give more
			switch($r) {
				case 1 :
					$waa = $bonus[0];
					$muu = Account::getQuestGold($prlvl, $goldbonus) * 4;
				break;
				case 2 :
					$waa = $bonus[1];
					$muu = $acc->generateQuest($prlvl, 0, 2)['exp'];
				break;
				case 3 :
					$waa = $bonus[2];
					$muu = intval( (Fortress::getGlobalMaxResources(1, $fortdata['b0']) / 7) * (rand(1000, 1100) / 1000) );
				break;
				case 4 :
					$waa = $bonus[3];
					$muu = intval( (Fortress::getGlobalMaxResources(2, $fortdata['b0']) / 7) * (rand(1000, 1100) / 1000) );
				break;
			}
		}else{
			// Give normal
			switch($r) {
				case 1 :
					$waa = $normal[0];
					$muu = Account::getQuestGold($prlvl, $goldbonus) * 2;
				break;
				case 2 :
					$waa = $normal[1];
					$muu = $acc->generateQuest($prlvl, 0, 1)['exp'];
				break;
				case 3 :
					$waa = $normal[2];
					$muu = intval( (Fortress::getGlobalMaxResources(1, $fortdata['b0']) / 7) * (rand(500, 550) / 1000) );
				break;
				case 4 :
					$waa = $normal[3];
					$muu = intval( (Fortress::getGlobalMaxResources(2, $fortdata['b0']) / 7) * (rand(500, 550) / 1000) );
				break;
			}
		}
		
		if($r == 5) {
			$waa = 0;
			$muu = rand(50, 250);
		}
		
		// Give things to player 1 = Gold, 2 = XP, 3 = Wood, 4 = Stone, 5 = Mushroom
		switch($r) {
			case 1 :
				$db->exec("UPDATE players SET silver = silver + $muu WHERE ID = $playerID");
			break;
			case 2 :
				$acc->addExp($muu);
				$db->exec("UPDATE players SET exp = ".$acc->data['exp'].", lvl = ".$acc->data['lvl']." WHERE ID = ".$playerID);
			break;
			case 3 :
				$new_wood = $fortdata['wood'] + $muu;
				if( $new_wood > Fortress::getGlobalMaxResources(1, $fortdata['b0']) ) {
					$new_wood = Fortress::getGlobalMaxResources(1, $fortdata['b0']);
				}
				$db->exec("UPDATE fortress SET wood = $new_wood WHERE owner = $playerID");
			break;
			case 4 :
				$new_stone = $fortdata['stone'] + $muu;
				if( $new_stone > Fortress::getGlobalMaxResources(2, $fortdata['b0']) ) {
					$new_stone = Fortress::getGlobalMaxResources(2, $fortdata['b0']);
				}
				$db->exec("UPDATE fortress SET stone = $new_stone WHERE owner = $playerID");
			break;
			case 5 :
				$db->exec("UPDATE players SET mush = mush + $muu WHERE ID = $playerID");
			break;
		}
		
		$acc = new Account(null, null, false, false);
		
		$ret[] = "Success:";
		$ret[] = "wheelresult(2):{$waa}/{$muu}";
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		//$ret[] = '#ownplayersave:2/1659705906/14/12/545/2/772/3';
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		break;
	case 'playermessageview':

		//arg0 = messageID

		$qry = $db->prepare("SELECT ID, message FROM messages WHERE reciver = $playerID AND ID = :msgid");
		$qry->bindParam(':msgid', $args[0]);
		$qry->execute();
		$msg = $qry->fetch(PDO::FETCH_ASSOC);

		if($qry->rowCount()>0){
				
			$db->exec('UPDATE messages SET hasRead = true WHERE ID = '.$msg['ID']);

			$ret[] = 'messagetext.s:'.$msg['message'];
			$ret[] = 'Success:';
			
			$messages = $db->query("SELECT messages.ID, players.name, messages.hasRead, messages.topic, messages.time 
				FROM messages LEFT JOIN players ON messages.sender = players.ID WHERE reciver = $playerID ORDER BY time DESC");
			$messages = $messages->fetchAll(PDO::FETCH_ASSOC);
			// $msgs = [];
			// foreach($messages as $msg){
			// 	if(strlen($msg['name']) == 0)
			// 		$msg['name'] = 'admin';
			// 	$msgs[] = join(',', $msg);
			// }

			$ret[] = 'Success:';
			$ret[] = 'messagelist.r:'.Chat::formatMessages($messages);
		}

		break;
	case 'playermessagedelete':

		if($args[0] == -1){
			$db->exec("DELETE FROM messages WHERE reciver = $playerID");

			exit('Success:&messagelist.r:');
		}else{
			$args[0] = intval($args[0]);
			$db->exec("DELETE FROM messages WHERE reciver = $playerID AND ID = $args[0]");

			$messages = $db->query("SELECT messages.ID, players.name, messages.hasRead, messages.topic, messages.time 
				FROM messages LEFT JOIN players ON messages.sender = players.ID WHERE reciver = $playerID ORDER BY time DESC");
			$messages = $messages->fetchAll(PDO::FETCH_ASSOC);
			// $msgs = [];
			// foreach($messages as $msg){
			// 	if(strlen($msg['name']) == 0)
			// 		$msg['name'] = 'admin';
			// 	$msgs[] = join(',', $msg);
			// }

			$ret[] = 'Success:';
			$ret[] = 'messagelist.r:'.Chat::formatMessages($messages);
		}



		break;
	
	case 'groupsetjoinslot':
		$acc = new Account($playerData, null, false, false);
		
		$action = $args[0];
		$minLvl = $args[1];
		$country = $args[2];
		$minLvl = min($minLvl, 999);
		$countryID = Misc::getCountryID($country);
		
		if ($acc->data['guild'] == 0 || $acc->data['guild_rank'] > 1)
			exit ('&error:no guild');
		
		$guild = new Guild($acc->data["guild"]);

		if (empty($guild->data))
			exit ('&error:no guild');
		
		if ($action == "-1") {
			if (!$guild->hasFreeInvitePlace()) exit ('&error:maxed');
			$qry = $db->prepare("INSERT INTO guildslots (guildID, minLvl, country) VALUES(?, ?, ?)");
			$qry->execute([$acc->data["guild"], $minLvl, $countryID]);
		} else {
			$littleBoy = $action - count($guild->invites) - count($guild->players) + 1;
			$g_slot = (int) ($littleBoy - 1);
			$slotID = $guild->slots[$g_slot]["ID"];

			if (!empty($slotID)) {
				$qry = $db->query("DELETE FROM guildslots WHERE ID = " . $slotID);
			}
		}

		$guild = new Guild($acc->data["guild"]);
		$ret[] = "owngroupsave.groupSave:" . $guild->getGroupSave();
		break;

	case 'groupjoinlist':
		$acc = new Account($playerData, null, false, false);
		$countryID = Misc::getCountryID($args[0]);

		$qry = $db->prepare("SELECT g.ID, g.name, (SELECT name FROM players WHERE guild = g.ID AND guild_rank = 1 LIMIT 1) AS leader, (SELECT COUNT(*) FROM players WHERE guild = g.ID) AS members, g.honor, @position := @position + 1 AS position FROM guilds g INNER JOIN guildslots gs ON g.ID = gs.guildID LEFT JOIN players p ON g.ID = p.guild AND p.guild_rank = 1 CROSS JOIN (SELECT @position := 0) AS pos WHERE gs.country = ? AND gs.minLvl <= ? GROUP BY g.ID ORDER BY g.honor DESC, g.name ASC");
		$qry->execute([$countryID, $acc->data["lvl"]]);
		$guildslots = $qry->fetchAll(PDO::FETCH_ASSOC);

		if (empty($guildslots)) {
			$ret[] = "joinablegrouplist.r:";
		} else {
			$ranklist = [];
			foreach ($guildslots as $slot) {
				$ranklist[] = implode(",", [
					$slot["position"],
					$slot["ID"],
					$slot["name"],
					$slot["leader"] ?? "",
					$slot["members"],
					$slot["honor"],
				]);
			}
			$ret[] = "joinablegrouplist.r:" . join(";", $ranklist);
		}

		$ret[] = "Success:";
	break;

	case 'groupjoin':
		$acc = new Account($playerData, null, false, false);

		$country = $args[1] == "int" ? 0 : Misc::getCountryID($args[1]);

		$qry = $db->prepare("SELECT guilds.*, guildslots.ID AS slot_id, guildslots.guildID, guildslots.minLvl, guildslots.country FROM guilds JOIN guildslots ON guildslots.guildID = guilds.ID WHERE guilds.name = ? AND guildslots.minLvl <= ? ORDER BY guildslots.ID DESC LIMIT 1");
		$qry->execute([$args[0], $acc->data["lvl"]]);

		$slotData = $qry->fetchAll(PDO::FETCH_ASSOC)[0];

		if (empty($slotData))
			exit("&error:must leave group first");
		
		$qry = $db->prepare("DELETE FROM guildslots WHERE ID = ?");
		$qry->execute([$slotData["slot_id"]]);
	
		$playerID = $acc->data['ID'];
		$guildID = $slotData['guildID'];
	
		$qry = $db->prepare("DELETE FROM guildinvites WHERE guildID = ? AND playerID = ?");
		$qry->execute([$slotData["guildID"], $acc->data["ID"]]);
		
		$acc->data['guild'] = $guildID;
		$acc->data['guild_rank'] = 3;
		$acc->data['event_trigger_count'] = $slotData['event_trigger_count'];

		//insert message
		$message = '#in#'.$acc->data['name'];
		$time = $GLOBALS["CURRTIME"];
		$chattime = Chat::chatInsert($message, $guildID, $playerID);
		$chat = Chat::getChat($guildID);

		$db->exec("UPDATE players SET guild = $guildID, guild_rank = 3, event_trigger_count = {$slotData['event_trigger_count']}, guild_fight = 0 WHERE ID = $playerID");

		$guild = new Guild($guildID);

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = "owngrouppotion.r:".$guild->getPotionData();
		$ret[] = "owngroupname.r:".$guild->data['name'];
		$ret[] = "owngroupdescription.s:".$guild->data['descr'];
		$ret[] = "owngroupmember.r:".$guild->getMemberList();
		$ret[] = "owngrouprank:".$guild->getRank();
		$ret[] = "chathistory.s(5):".Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";
		$ret[] = 'groupskillprice(6):'.$guild->getUpgradeSkillPriceSave();
	break;

	case 'grouplookat':
		// arg 0 = guild name

		$qry = $db->prepare('SELECT ID, descr FROM guilds WHERE name = :name OR ID = :name');
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		$fetch = $qry->fetch(PDO::FETCH_ASSOC);
		
		if($qry->rowCount() == 0)
			exit('&Error:group not found');
		
		$guild = new Guild($fetch['ID']);

		$ret[] = 'Success:';
		$ret[] = 'othergroup.groupSave:'.$guild->getGroupSave();
		$ret[] = 'othergroupdescription.s:'.$fetch['descr'];
		$ret[] = 'othergroupname.r:'.$guild->data['name'];
		$ret[] = 'othergroupmember.s:'.$guild->getMemberList();
		$ret[] = 'othergrouprank:1';
		$ret[] = 'othergroupfightcost:'.Guild::getAttackCost($guild->data['base']);
		if(($oga = $guild->getOtherGroupAttack()) !== false)
			$ret[] = $oga;

		break;
	case 'groupsetofficer':

		$time = $GLOBALS["CURRTIME"];
		$args[0] = intval($args[0]);
		$names = $db->query("SELECT name, guild_rank FROM players WHERE ID = $playerID OR ID = $args[0] ORDER BY guild_rank")->fetchAll();

		$promote = $names[1]['guild_rank'] == 2? 3 : 2;
		
		if(!$db->exec("UPDATE players SET guild_rank = $promote WHERE ID = $args[0] AND guild = $playerGuild"))
			exit('&Error:');

		$ftime = gmdate("H:i", $GLOBALS["CURRTIME"] + 3600);
		$name1 = $names[0]['name'];
		$name2 = $names[1]['name'];
		$message = "#ra#$ftime $name1#$promote#$name2";
		
		$chattime = Chat::chatInsert($message, $playerGuild, $playerID);
		
		$chat = Chat::getChat($playerGuild);

		//update player poll
		$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");

		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";

		break;
	case 'groupsetleader':
		$args[0] = intval($args[0]);
		$qry = $db->query("SELECT name, guild, guild_rank FROM players WHERE ID = $args[0] OR ID = $playerID ORDER BY guild_rank DESC")->fetchAll(PDO::FETCH_ASSOC);
		$newleader = $qry[0];
		$player = $qry[1];

		if($newleader['guild'] != $player['guild'] || $player['guild_rank'] != 1)
			exit();

		$db->exec("UPDATE players SET guild_rank = 2 WHERE ID = $playerID;UPDATE players SET guild_rank = 1 WHERE ID = $args[0]");

		$message = "#rv#$newleader[name]#$player[name]";

		$chattime = Chat::chatInsert($message, $playerGuild, $playerID);

		$guild = new Guild($playerGuild);

		$ret[] ='Success:';
		$ret[] ='owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] ='owngroupmember.r:'.$guild->getMemberList();
		$ret[] ='chathistory.s(5):'.Chat::formatChat(Chat::getChat($playerGuild));
		$ret[] ="chattime:$chattime";

		break;
	case 'groupremovemember':

		$time = $GLOBALS["CURRTIME"];	

		$args[0] = intval($args[0]);
		if($playerID == $args[0]){
			$acc = new Account(null, null, false, false);

			//disband guild
			if($acc->data['guild_rank'] == 1){
				$db->exec("INSERT INTO messages(sender, reciver, time, topic, message) SELECT $playerID, players.ID, UNIX_TIMESTAMP(), '1', '{$acc->data['gname']}' FROM players WHERE guild = $playerGuild AND players.ID != $playerID;
					UPDATE players SET guild = 0, guild_rank = 3, guild_fight = 0 WHERE guild = $playerGuild;
					DELETE FROM guilds WHERE ID = $playerGuild;
					DELETE FROM guildchat WHERE guildID = $playerGuild;
					DELETE FROM guildinvites WHERE guildID = $playerGuild;
					DELETE FROM guildfights WHERE guildAttacker = $playerGuild OR guildDefender = $playerGuild LIMIT 2;");

			}else{
				$ftime = gmdate("H:i", $time + 30);
				$name = $acc->data['name'];
				$message = "#ou#$ftime $name";

				Chat::chatInsert($message, $playerGuild, $playerID);
				$db->exec("UPDATE players SET guild = 0, guild_rank = 3, guild_fight = 0, event_trigger_count = 0 WHERE ID = $playerID");
			}

			$acc->data['guild'] = 0;
			$acc->data['guild_rank'] = 3;

			$ret[] = 'Success:';
			$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
			break;
		}

		//see if just removing invite
		if(!$db->exec("DELETE FROM guildinvites WHERE guildID = $playerGuild AND playerID = $args[0]")){

			//send message to the kicked player


			$db->exec("UPDATE players SET guild = 0, guild_rank = 3, guild_fight = 0, event_trigger_count = 0 WHERE ID = $args[0]");

			$ftime = gmdate("H:i", $time + 30);
			$name = $db->query("SELECT name FROM players WHERE ID = $args[0]")->fetch(PDO::FETCH_ASSOC)['name'];
			$message = "#ou#$ftime $name";

			//get chat before updating poll
			$chattime = Chat::chatInsert($message, $playerGuild, $playerID);

			$chat = Chat::getChat($playerGuild);

			//update player poll
			$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");
			$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
			$ret[] = "chattime:$chattime";
		}
		
		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();

		break;
		
	case 'groupinvitemember':
		if ($playerGuild == 0)
			exit ('&error:no guild');
		
		$guild = new Guild($playerGuild);
		
		if (empty($guild->data))
			exit ('&error:no guild');
		
		$acc = new Account($playerData, null, false, false);
		
		if($acc->data['guild_rank'] > 2) 
			exit ('&error:invalid guild rank');

		if(!$guild->hasFreeInvitePlace())
			exit('&Error:group is full');

		$time = $GLOBALS["CURRTIME"];
		$gName = $guild->data['name'];

		$qry = $db->prepare("SELECT ID, guild, name FROM players WHERE ID = :name");
		$qry->bindParam(":name", $args[0]);
		$qry->execute();
		$fetchF = $qry->fetch();
		$uID = $fetchF['ID'];

		if($acc::isUserIgnored($fetchF['friends'], $playerID))
			exit('&Error:player not found');

		if($acc->data['guild'] == $fetchF['guild'])
			exit("&Success:");
			
		$qry = $db->prepare("INSERT INTO messages(sender, reciver, time, topic, message) VALUES (:pid, :reciver, :time, :topic, :message)");
		$qry->execute(array(':pid' => $acc->data['ID'], ':reciver' => $uID, ':time' => time(), ':topic' => 5, ':message' => $gName));
		
		$qry = $db->prepare("INSERT INTO guildinvites(guildID, playerID) SELECT $playerGuild, players.ID FROM players WHERE ID = :name");
		$qry->bindParam(':name', $args[0]);
		$qry->execute();

		$invited = $db->query("SELECT players.ID, players.name, players.lvl FROM guildinvites LEFT JOIN players ON guildinvites.playerID = players.ID WHERE guildinvites.guildID = $playerGuild ORDER BY players.lvl DESC");
		$guild->invites = $invited->fetchAll(PDO::FETCH_ASSOC);

		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'owngroupmember.r:'.$guild->getMemberList();
		break;
		
	case 'groupinviteaccept':

		if($playerGuild != 0)
			exit('&Error:must leave group first');

		$qry = $db->prepare("SELECT guilds.ID, event_trigger_count FROM guilds WHERE name = :name");
		$qry->bindParam(':name', $args[0]);
		$qry->execute();
		$obj = $qry->fetch(PDO::FETCH_ASSOC);
		$guildID = $obj['ID'];
		$etc = $obj['event_trigger_count'];

		if($qry->rowCount() == 0)
			exit('&Error:group not found');

		if(!$db->exec("DELETE FROM guildinvites WHERE guildID = $guildID AND playerID = $playerID"))
			exit('&Error:you are not invited');

		$acc = new Account(null, null, false, false);
		$acc->data['guild'] = $guildID;
		$acc->data['guild_rank'] = 3;
		$acc->data['event_trigger_count'] = $etc;

		//insert message
		$message = '#in#'.$acc->data['name'];
		$time = $GLOBALS["CURRTIME"];
		// $db->exec("INSERT INTO guildchat(guildID, playerID, message, time) VALUES($guildID, $playerID, '$message', $time)");
		$chattime = Chat::chatInsert($message, $guildID, $playerID);
		$chat = Chat::getChat($guildID);

		$db->exec("UPDATE players SET guild = $guildID, guild_rank = 3, event_trigger_count = $etc, guild_fight = 0 WHERE ID = $playerID");


		$guild = new Guild($guildID);

		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = "owngrouppotion.r:".$guild->getPotionData();
		$ret[] = "owngroupname.r:".$guild->data['name'];
		$ret[] = "owngroupdescription.s:".$guild->data['descr'];
		$ret[] = "owngroupmember.r:".$guild->getMemberList();
		$ret[] = "owngrouprank:".$guild->getRank();
		$ret[] = "chathistory.s(5):".Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";
		$ret[] = 'groupskillprice(6):'.$guild->getUpgradeSkillPriceSave();
		
		break;
	
	case 'groupskillincrease':
		$type = (int) $args[0] ?? 0;
		$selType = ['skill_treasure', 'skill_instructor', 'skill_pet'][$type] ?? null;
		
		if ($selType === null)
			exit ('&error:');
		
		if ($playerGuild == 0)
			exit ('&error:no guild');
		
		$guild = new Guild($playerGuild);
		
		if (empty($guild->data))
			exit ('&error:no guild');
		
		$acc = new Account($playerData, null, false, false);
		
		list($costSilver, $costMush) = $guild->getUpgradeSkillPrice($acc->data[$selType]);
		
		$acc->data['silver'] -= $costSilver;
		
		if ($acc->data['silver'] < 0)
			exit ('&error:need more coins');
		
		$acc->data['mush'] -= $costMush;
		
		if ($acc->data['mush'] < 0)
			exit ('&error:need more coins');
		
		switch ($type) {
			case 0: case 1: $levelAdd = 1; break;
			case 2: $levelAdd = 5; break;
		}
		
		$guild->skill['guild_'.$selType] += $levelAdd;
		$acc->data[$selType] += $levelAdd;
		
		$qry = $db->prepare('UPDATE players SET mush = :mush, silver = :silver, '.$selType.' = :level WHERE ID = :ID');
		$qry->execute([':mush' => $acc->data['mush'], ':silver' => $acc->data['silver'], ':level' => $acc->data[$selType], ':ID' => $acc->data['ID']]);
		
		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		$ret[] = 'chathistory.s(5):'.Chat::formatChat(Chat::getChat($playerGuild));
		$ret[] = 'groupskillprice(6):'.$guild->getUpgradeSkillPriceSave();
	break;
		
	case 'groupincreasebuilding':
		if ($playerGuild == 0)
			exit ('&error:no guild');
		
		$guild = new Guild($playerGuild);
		
		if (empty($guild->data))
			exit ('&error:no guild');
		
		$acc = new Account($playerData, null, false, false);
		
		if($acc->data['mush'] < 5)
			exit('&Error:need more coins');
			
		if($guild->data['catapult'] >= 3)
			exit('&error:max catapult');
			
		$acc->data['mush'] -= 5;
		$guild->data['catapult']++;
			
		$db->exec("UPDATE guilds SET catapult = catapult + 1 WHERE ID = $playerGuild");
		$db->exec("UPDATE players SET mush = mush - 5 WHERE ID = $playerID");
			
		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";
			
		$time = $GLOBALS["CURRTIME"];
		$ftime = gmdate("H:i", $time + 3600);
		$message = "#bd#$ftime $player[name]#$args[0]";

		$chattime = Chat::chatInsert($message, $playerGuild, $playerID);
		$chat = Chat::getChat($playerGuild);

		$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");


		$ret[] = 'Success:';
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = 'groupskillprice(6):'.$guild->getUpgradeSkillPriceSave();
		$ret[] = "chattime:$chattime";
		$ret[] = '#ownplayersave.playerSave:14/'.$acc->data['mush'];
		
	break;

	case 'groupchat':

		$time = $GLOBALS["CURRTIME"];
		
		$limit = 5;
		
		$ins = $args[0];
		
		if(substr($ins, 0, 2) == "--"){
			$spc = explode(" ", substr($ins, 2));
			
			switch($spc[0]){
				
				default:
					exit("&Error:invalid command");
			}
		}
		
		$chattime = Chat::chatInsert($ins, $playerGuild, $playerID, $playerPerm);
		$chat = Chat::getChat($playerGuild, $limit);

		$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");

		$ret[] = 'Success:';
		$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
		$ret[] = "chattime:$chattime";


		break;
	case 'groupraiddeclare':
		// Raid
		$args[0] = 1000000;
	case 'groupattackdeclare':
		// Injection fix
		if(is_numeric($args[0]) == false)
			exit();

		$guild = new Guild($playerGuild);
		$guild->declareFight($args[0], $playerID);



		$ret[] = 'Success:';
		// $ret[] = '#ownplayersave:508/1';
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		if($args[0] != 1000000){
			//$aname = $db->query("SELECT name FROM guilds WHERE ID = $args[0]")->fetch(PDO::FETCH_ASSOC)['name'];
			
			$qry = $db->prepare("SELECT name FROM guilds WHERE ID = :groupid");
			$qry->bindParam(':groupid', $args[0]);
			$qry->execute();
			$aname = $qry->fetch(PDO::FETCH_ASSOC)['name'];
			
			$ret[] = "owngroupattack.r:".$aname;
		}
//		
		$acc = new Account(null, null, false, false);
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		break;
	case 'groupreadyattack':

		if($playerGuild == 0)
			exit();

		$fight = $db->query("SELECT guild_fight FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC)['guild_fight'];
		$fight++;
		if($fight != 1 && $fight != 3)
			exit();
		$db->exec("UPDATE players SET guild_fight = $fight WHERE ID = $playerID");

		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = "#ownplayersave:508/$fight";
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();

		break;
	case 'groupreadydefense':

		if($playerGuild == 0)
			exit();

		$fight = $db->query("SELECT guild_fight FROM players WHERE ID = $playerID")->fetch(PDO::FETCH_ASSOC)['guild_fight'];
		$fight += 2;
		if($fight != 2 && $fight != 3)
			exit();
		$db->exec("UPDATE players SET guild_fight = $fight WHERE ID = $playerID");

		$guild = new Guild($playerGuild);

		$ret[] = 'Success:';
		$ret[] = "#ownplayersave:508/$fight";
		$ret[] = 'timestamp:'.$GLOBALS["CURRTIME"];
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();


		break;
	case 'groupgetbattle':
		// Fixed by Greg
		
		$time = $GLOBALS["CURRTIME"];
		
		$acc = new Account(null, null, false, false); // New acc
		$guild = new Guild($playerGuild);

		// //SEE IF SIM FIGHT and shiet
		$guildData = $db->query("SELECT event_trigger_count, dungeon, honor, name FROM guilds WHERE ID = $playerGuild")->fetch(PDO::FETCH_ASSOC);

		$fights = $db->query("SELECT guildfights.ID, guildfights.guildAttacker, g1.name AS attacker, guildfights.guildDefender, g2.name AS defender, time 
			FROM guildfights LEFT JOIN guilds AS g1 ON guildfights.guildAttacker = g1.ID LEFT JOIN guilds AS g2 ON guildfights.guildDefender = g2.ID 
			WHERE (guildfights.guildAttacker = $playerGuild OR guildfights.guildDefender = $playerGuild) AND time <= $time ORDER BY time ASC");
		

		// //if simfight
		if(($n = $fights->rowCount()) > 0){
			$fights = $fights->fetchAll(PDO::FETCH_ASSOC);

			//delete fight from db right away
			$db->exec("DELETE FROM guildfights WHERE guildAttacker = $playerGuild OR guildDefender = $playerGuild LIMIT 2;");

			//update trigger count, defenders guild too
			foreach($fights as $fight){
				if($fight['guildDefender'] != 1000000)
					$db->exec("UPDATE guilds SET event_trigger_count = event_trigger_count + 1 WHERE ID = $fight[guildAttacker] OR ID = $fight[guildDefender]");
				else
					$db->exec("UPDATE guilds SET event_trigger_count = event_trigger_count + 1 WHERE ID = $fight[guildAttacker]");
				$guildData['event_trigger_count']++;
			}

			//ALG: loop through fights incase there are 2, always ordered by time ascending. Display only the lastest, which is simulated as 2nd
			//		if the fight is in guildfights table, it hasn't been simulated, simulate and add to logs
			//		always simulate from the perspective of the attacker, defender can use GroupSimulation::reverseGuildFightLog() on displaying

			foreach($fights as $fight){

				//fight log, plain string, fuck it
				$fightLog = [];

				//get players 
				// Fix by Jack only declared players fight
				//$players = $db->query("SELECT players.*, guilds.portal AS guild_portal FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.guild = $fight[guildAttacker] ORDER BY lvl ASC")->fetchAll(PDO::FETCH_ASSOC);
				$players = $db->query("SELECT players.*, guilds.portal AS guild_portal FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.guild = $fight[guildAttacker] AND players.guild_fight = 1 ORDER BY lvl ASC")->fetchAll(PDO::FETCH_ASSOC);
				$playerObjects = [];
				$items = $db->query("SELECT players.ID AS pid, items.* FROM items LEFT JOIN players ON items.owner = players.ID WHERE players.guild = $fight[guildAttacker] AND items.slot BETWEEN 10 AND 19");
				$items = $items->fetchAll(PDO::FETCH_GROUP);
				foreach($players as $player){
					//$items_p = $items[$player['ID']] ?? [];
					$items_p = (isset($items[$player['ID']])) ? $items[$player['ID']] : [];
					@$playerObjects[] = new Player($player, $items_p);
				}

				//get opponents, see if guild raid
				if($fight['guildDefender'] == 1000000){
					//just get shit from guild data, only players from guild call this
					$opponentObjects = Monster::getGuildRaid($guildData['dungeon']);
					
					//guild raid 150 lvl update - Jack
					//raid lvl 50 - 100
					if($guildData['dungeon'] >= 50 && $guildData['dungeon'] < 100) {
						
						$opponentObjects = []; //clear array
						
						$i = 0;
						foreach(Monster::getGuildRaid($guildData['dungeon'] - 50) as $monster) {
							
							if($i == count(Monster::getGuildRaid($guildData['dungeon'] - 50)) - 1)
								$monster->raidbuff(true); // monster is boss
							else
								$monster->raidbuff(false);
							
							$opponentObjects[] = $monster;
							
							$i++;
						}	
						
					//raid lvl 100-150	
					} else if($guildData['dungeon'] >= 100) {
						$opponentObjects = []; //clear array
						
						$i = 0;
						foreach(Monster::getGuildRaid($guildData['dungeon'] - 100) as $monster) {
							
							if($i == count(Monster::getGuildRaid($guildData['dungeon'] - 100)) - 1)
								$monster->raidbuff2(true); // monster is boss
							else
								$monster->raidbuff2(false);
							
							$opponentObjects[] = $monster;
							
							$i++;
						}	
					}	
					
				}else{
					//get other guild members here
					//$opponents = $db->query("SELECT players.*, guilds.portal AS guild_portal, guilds.honor as ghonor FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.guild = $fight[guildDefender] ORDER BY lvl ASC")->fetchAll(PDO::FETCH_ASSOC);
					$opponents = $db->query("SELECT players.*, guilds.portal AS guild_portal, guilds.honor as ghonor FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.guild = $fight[guildDefender] AND players.guild_fight = 2 ORDER BY lvl ASC")->fetchAll(PDO::FETCH_ASSOC);
					$opponentObjects = [];
					$items = $db->query("SELECT players.ID as pid, items.* FROM items LEFT JOIN players ON items.owner = players.ID WHERE players.guild = $fight[guildDefender] AND items.slot BETWEEN 10 AND 19");
					$items = $items->fetchAll(PDO::FETCH_GROUP);
					foreach($opponents as $opponent){
						//$items_p = $items[$opponent['ID']] ?? [];
						$items_p = (isset($items[$opponent['ID']])) ? $items[$opponent['ID']] : [];
						@$opponentObjects[] = new Player($opponent, $items_p);
					}
				}


				//simulate fight
				$simulation = new GroupSimulation($playerObjects, $opponentObjects);
				$simulation->simulate();

				//output logs
				for($i = 0; $i < count($simulation->simulations); $i++){
					$fightn = $i+1;
					$fightLog[] = "fightheader".$fightn.".fighters:3/0/0/1/1/".$simulation->fightHeaders[$i];
					$fightLog[] = "fight".$fightn.".r:".$simulation->simulations[$i]->fightLog;
					$fightLog[] = "winnerid".$fightn.".s:".$simulation->simulations[$i]->winnerID;
				}
				$fightLog[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();

				
				if($fight['guildDefender'] == 1000000){
					//insert raid chat logs
					$guildData['dungeon']++;
					if($simulation->win)
						$chatTime = Chat::chatInsert("#rplus#$guildData[dungeon]#", $playerGuild, 0);
					else
						$chatTime = Chat::chatInsert("#rminus#$guildData[dungeon]#", $playerGuild, 0);
				}else{
					//count out honor

					//max honor diff = 2k								
					//formula: 100 + (opponent.honor - player.honor) / (max honor diff / (max diff / 100))
					$attHonor = $guildData['honor'];
					$defHonor = $opponents[0]['ghonor'];

					if(abs($diff = $defHonor - $attHonor) < 2000)
						$honor = 100 + round($diff / 20);
					else
						$honor = 0;

					//update guilds honor
					if($simulation->win)
					{
						if($honor > $defHonor)
							$honor = $defHonor; // No farming on enemy guild pls
						
						$db->exec("UPDATE guilds SET honor = honor + $honor WHERE ID = $fight[guildAttacker]; UPDATE guilds SET honor = GREATEST(0, honor - $honor) WHERE ID = $fight[guildDefender]");
					}
					else
					{
						if($honor > $attHonor)
							$honor = $attHonor;	// No farming on enemy guild pls			
						
						$db->exec("UPDATE guilds SET honor = honor + $honor WHERE ID = $fight[guildDefender]; UPDATE guilds SET honor = GREATEST(0, honor - $honor) WHERE ID = $fight[guildAttacker]");
					}
					

					//need names
					// $attName = $players[0]['gname'];
					// $defName = $opponents[0]['gname'];
					$attName = $fight['attacker'];
					$defName = $fight['defender'];


					//fight logs, both guilds
					if($simulation->win){
						$chatTimeAtt = Chat::chatInsert("#aplus#$defName#$honor#", $fight['guildAttacker'], 0);
						$chatTimeDef = Chat::chatInsert("#dminus#$attName#$honor#", $fight['guildDefender'], 0);
					}else{
						$chatTimeAtt = Chat::chatInsert("#aminus#$defName#$honor#", $fight['guildAttacker'], 0);
						$chatTimeDef = Chat::chatInsert("#dplus#$attName#$honor#", $fight['guildDefender'], 0);
					}
					
				}

				//battlereward
				$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
				
				// endLog
				$endLog = "";
				
				// Raid or normal
				if($fight['guildDefender'] == 1000000)
				{
					$endLog = "0";
					
					if($simulation->win)
					{
						$endLog .= "/1";
						$battleReward[0] = 1;
						//add reward for dungeon
						$db->exec("UPDATE guilds SET dungeon = dungeon + 1 WHERE ID = $fight[guildAttacker]");
					}
					else
						$endLog .= "/0";
					
					$endLog .= "/" . $guildData['dungeon'];
					
					if($simulation->win) { // Guild raid xp by Jack
						$exp = Guild::getGuildBattleExp(0, $guildData['dungeon']);
						$endLog = $endLog . "/" . $exp;
						$battleReward[3] = $exp;
						
						// Add exp to all guild members who fighted
						foreach($guild->players as $gplayer) {
							if($gplayer['guild_fight'] == 1) { // Player fighted
								if($gplayer['ID'] == $playerID) { // Player is $acc
									$acc->addExp($exp);
									$db->exec("UPDATE players SET exp = '".$acc->data['exp']."', lvl = '".$acc->data['lvl']."' WHERE ID = ".$acc->data['ID']);
								}else{ // Other player
									$player = $db->query("SELECT * FROM players WHERE ID = ".$gplayer['ID']); // Select all to create new Account
									$player = $player->fetch(PDO::FETCH_ASSOC);
							
									$pl = new Account($player, null, false, false);
							
									$pl->addExp($exp);
							
									$db->exec("UPDATE players SET exp = '".$pl->data['exp']."', lvl = '".$pl->data['lvl']."' WHERE ID = ".$pl->data['ID']);
									
								}	
							}	
						}
						
					}
			
				}
				else
				{
					$endLog = "1";
					
					if($simulation->win)
						$winner = $fight['guildAttacker'];
					else
						$winner = $fight['guildDefender'];
					
					$endLog .= "/" . $winner . "/" . $honor;
					
					if($playerGuild == $winner)
					{
						$battleReward[0] = 1;
						$battleReward[6] = $honor;
					}
				}

				$endBattleData = 'fightresult.battlereward:'.join('/', $battleReward);
				
				$fightLog = join('&', $fightLog);
				//save logs to db
				$db->exec("INSERT INTO guildfightlogs(guildAttacker, guildDefender, log, endLog, time) VALUES($fight[guildAttacker], $fight[guildDefender], '$fightLog', '$endLog', $fight[time])");

				//UPDATE player guild_fight of both guilds | this now is temporary
				$db->exec("UPDATE players SET guild_fight = 0 WHERE guild = $fight[guildAttacker] OR guild = $fight[guildDefender]");
			}
			
			if($args[0] == 1)
			{
				$ret[] = $fightLog;
				$ret[] = $endBattleData;
			}

		}else if($args[0] == 1){
			//else if fight already simulated and player wants to see the fight, pull the logs ***** AND time > 0
			$log = $db->query("SELECT log, endLog FROM guildfightlogs WHERE (guildAttacker = $playerGuild OR guildDefender = $playerGuild) ORDER BY time DESC LIMIT 1");
			$fetch = $log->fetch(PDO::FETCH_ASSOC);
			$ret[] = $fetch['log'];
			
			// Get battlereward by Greg
			
			//battlereward
			$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
			
			$endLog = explode("/", $fetch["endLog"]);
			
			if($endLog[0] == "0" && $endLog[1] == "1")
				$battleReward[0] = 1;
			else if($endLog[0] == "1" && $playerGuild == $endLog[1])
			{
				$battleReward[0] = 1;
				$battleReward[6] = $endLog[2];
			}
			
			$battleReward[3] = isset($endLog[3]) ? $endLog[3] : 0;
			
			$ret[] = 'fightresult.battlereward:'.join('/', $battleReward);
		}

		
		//update player trigger count
		$acc->data['event_trigger_count'] = $guildData['event_trigger_count'];
		$db->exec("UPDATE players SET event_trigger_count = $guildData[event_trigger_count] WHERE ID = $playerID");


		$ret[] = 'Success:';
		$ret[] = "combatloglist.s:".$acc->getCombatLog(); // Update logs
		//$ret[] = '#ownplayersave:509/'.$guildData['event_trigger_count'];
		$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
		

		break;
	case 'playermessagewhisper':
		// Whispering by Greg
		// 19:37 Brayght:§ Szia
		
		// Args: 0 = name, 1 = message
		
		
		if(strlen($args[1]) > 255)
			exit("&Error:text too long");
		
		$acc = new Account(null, null, false, false);
		
		$args[0] = $acc::formatUser($args[0]);
		
		$msg = $acc->data['name'] . ':' . ($GLOBALS["CURRTIME"]) . ':' . $args[1];
		
		$qry = $db->prepare('SELECT ID, name, whisper, friends, guild FROM players WHERE name=:name');
		$qry->execute([':name' => $args[0]]);
		
		if($qry->rowCount() == 0)
			exit('&Error:player not found');
		
		$fetch = $qry->fetchAll()[0];
		
		if($fetch["guild"] <= 0)
			exit("&Error:player no guild");
		
		if(Account::isUserIgnored($fetch['friends'], $acc->data["ID"]))
			exit("&Error:player not found"); // player is ignored
		
		if($fetch['whisper'] == '') {
			$whisper = $msg;
		}else{
			$whisper = $fetch['whisper'] . '/' . $msg;
		}
		
		$sql = "UPDATE players SET whisper = :whisper WHERE ID = '{$fetch['ID']}'";
		
		$qry = $db->prepare($sql);
		
		$qry->bindParam(":whisper", $whisper);
		
		$qry->execute();
		
		//$db->exec($sql);
		
		$ret[] = "Success:";
		break;
	case 'playercombatlogmark':
		// I don't need this
		$ret[] = 'Success:';
		break;
	case 'playerlastfightstore': // Removed
		$ret[] = 'Success:';
		break;
	case 'playercombatlogview':
		// View log by Greg
		
		$type = intval(substr($args[0], 0, 1));
		
		$id = intval(substr($args[0], 1));
		
		switch($type){
			case 2:
			case 3:
				$log = $db->query("SELECT log, endLog FROM guildfightlogs WHERE ((guildAttacker = $playerGuild OR guildDefender = $playerGuild) AND ID = $id) ORDER BY time DESC LIMIT 1");
				
				if($log->rowCount() == 0)
					exit("not found");
				
				$fetch = $log->fetch(PDO::FETCH_ASSOC);
				$ret[] = $fetch['log'];
				
				//battlereward
				$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
				
				$endLog = explode("/", $fetch["endLog"]);
				
				if($endLog[0] == "0" && $endLog[1] == "1")
					$battleReward[0] = 1;
				else if($endLog[0] == "1" && $playerGuild == $endLog[1])
				{
					$battleReward[0] = 1;
					$battleReward[6] = $endLog[2];
				}
				
				$ret[] = 'fightresult.battlereward:'.join('/', $battleReward);
				
				$ret[] = "Success:";
				break;
			default:
				exit("&Error:player not found");
		}
		break;
	case 'playersetdescription':

		$qry = $db->prepare("UPDATE players SET description = :description WHERE ID = $playerID");
		$qry->bindParam(':description', $args[0]);
		$qry->execute();

		$ret[] = 'Success:';
		break;
	case 'poll':
		$ret[] = 'Success:';
		$time = $GLOBALS["CURRTIME"];
		$update = false;

		//fortress unit train
		if($playerData['ut1'] > 0 || $playerData['ut2'] > 0 || $playerData['ut3'] > 0){
			$accUpdate = false;
			$qryArgs = [];
			for($i = 1; $i <= 3; $i++){
				if($playerData["ut$i"] > 0 && ($timeElapsed = $GLOBALS["CURRTIME"] - $playerData["uttime$i"]) > 600){

					$accUpdate = true;
					$units = floor($timeElapsed / 600);
					if($units > $playerData["ut$i"]) {
						// Important fix by Greg - no minus soldier training, no more than max soldiers
						$units = $playerData["ut$i"];
					}
					if($units < $playerData["ut$i"])
						$newtime = $playerData["uttime$i"] + $units * 600;
					else
						$newtime = 0;
					$qryArgs[] = "ut$i = ut$i - $units, u$i = u$i + $units, uttime$i = $newtime";
				}
			}

			if($accUpdate){
				$db->exec("UPDATE fortress SET ".join(',', $qryArgs)." WHERE owner = $playerID");
				$acc = new Account();

				$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
			}
		}else if($playerPoll < $time - 30){
			//check messages with 30 sec intervall, this is incase chat is polling to reduce the load somewhat significantly
			$messages = $db->query("SELECT COUNT(*) FROM messages WHERE reciver = $playerID AND messages.time > $playerPoll")->fetch(PDO::FETCH_ASSOC);
			$newMessages = $messages['COUNT(*)'];
			
			if($newMessages > 0){
				$update = true;

				$messages = $db->query("SELECT messages.ID, players.name, messages.hasRead, messages.topic, messages.time 
					FROM messages LEFT JOIN players ON messages.sender = players.ID WHERE messages.reciver = $playerID ORDER BY time DESC");
				$messages = $messages->fetchAll(PDO::FETCH_ASSOC);
				// $msgs = [];
				// foreach($messages as $msg){
				// 	if(strlen($msg['name']) == 0)
				// 		$msg['name'] = 'admin';
				// 	$msgs[] = join(',', $msg);
				// }

				$ret[] = 'messagelist.r:'.Chat::formatMessages($messages);
				$ret[] = '#ownplayersave.playerSave:434/'.$messages[0]['ID'];

				//check if kick from guild message, load playerdata
			}
		}


		//types: 1 - quest, 2 - guild, 3 - raid, 4 - dungeon, 5 - tower, 6 - portal, 7 = gportal, 8 - fortressAtt, 9 - fortressDef, 10 - dark dungeons
		//ID,target name, win, type, time, marked
		// $ret[] = "combatloglist.s:1234,603,0,1,1456680807,0;1234,Nowakowscy,1,2,1456677448,0;";

		//guild chat and guild refreshing
		if($playerGuild > 0){
			// $chat = $db->query("SELECT players.name, guildchat.message, guildchat.time FROM guildchat LEFT JOIN players ON guildchat.playerID = players.ID 
			// 	WHERE guildchat.guildID = $playerGuild AND guildchat.time > $playerPoll ORDER BY guildchat.time DESC LIMIT 5");
			$chat = $db->query("SELECT Max(time) as newm, Max(chattime) as chattime FROM guildchat WHERE guildID = $playerGuild")->fetch(PDO::FETCH_ASSOC);
			
			$whisper = $db->query("SELECT whisper FROM players WHERE ID = '$playerID'")->fetchAll()[0]['whisper'];
			
			if($chat['newm'] > $playerPoll || $whisper != ''){
				$update = true;
				$chattime = $chat['chattime'];
				$chat = Chat::getChat($playerGuild);
				$ret[] = 'chathistory.s(5):'.Chat::formatChat($chat);
				$ret[] = "chattime:$chattime";
				
				// Whispers by Greg
				$whisper = Chat::formatWhispers($whisper);
				if($whisper != '') {
					$ret[] = 'chatwhisper.s:'.$whisper;
					$db->exec("UPDATE players SET whisper = '' WHERE ID = '$playerID'");
				}

				//IF system message, poll guild dataww
				if(Chat::containsSystemMessage($chat) || $playerPoll < $time - 10){
					$guild = new Guild($playerGuild);
					$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
					$ret[] = "owngrouppotion.r:".$guild->getPotionData();
					$ret[] = "owngroupname.r:".$guild->data['name'];
					//$ret[] = "owngroupdescription.s:".$guild->data['descr']; // TODO DESCR
					$ret[] = "owngroupmember.r:".$guild->getMemberList();
					$ret[] = "owngrouprank:".$guild->getRank();
					if(($oga = $guild->getOtherGroupAttack()) !== false)
						$ret[] = $oga;
				}

				//pull all shit above and bellow, namelist, potionlist, etc...
			}else if($playerPoll < $time - 30){
				//check time and load guild here every 60 sec or something?
				$update = true;
				$guild = new Guild($playerGuild);
				$ret[] = 'owngroupsave.groupSave:'.$guild->getGroupSave();
				$ret[] = "owngrouppotion.r:".$guild->getPotionData();
				$ret[] = "owngroupname.r:".$guild->data['name'];
				//$ret[] = "owngroupdescription.s:".$guild->data['descr']; // TODO DESCR
				$ret[] = "owngroupmember.r:".$guild->getMemberList();
				$ret[] = "owngrouprank:".$guild->getRank();
				if(($oga = $guild->getOwnGroupAttack()) !== false)
					$ret[] = $oga;
			}
		}


		

		//LIMIT THIS, CHECK IF 1 MIN GONE BY, chat will rek dis
		//update variable is true if player has recieved a message or read chat, without it shit will keep pulling
		if($update || $playerPoll < $time - 90)
			$db->exec("UPDATE players SET poll = $time WHERE ID = $playerID");

		$acc = new Account();
		
		if ($playerData['renew'] == 1) {
			$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
			$ret[] = $acc->getDungeonResponse();
			$db->exec("UPDATE players SET renew = 0 WHERE ID = $playerID");
			$playerData['renew'] = 0;
		}
		
		$ret[] = 'unlockfeature:'.$acc->unlockFeature();
		break;
		
	case 'groupsetdescription' :
		// By Greg
		// args 0 is description

		if($playerData['guild_rank'] != 1) {
			exit('&Error:');
		}
		
		$qry = $db->prepare("UPDATE guilds SET descr = :descr WHERE ID = :playerGuild");
		$qry->execute([
			':descr' => $args[0],
			':playerGuild' => $playerGuild
		]);
		
		$ret[] = "Success:";
	break;
	
	case "playergamblegold" :
		// By Greg
		// args 0 is how much the player would gamble
		
		if(rand(1, 3) == 3) {
			$gamble = $args[0];
		}else{
			$gamble = $args[0] * -1;
		}
		
		$qry = $db->prepare("SELECT silver FROM players WHERE ssid = :ssid");
		$qry->bindParam(':ssid', $ssid);
		$qry->execute();
		$prpa = $qry->fetch(PDO::FETCH_ASSOC);
		
		$prpa['silver'] = $prpa['silver'] + $gamble;
		
		if($prpa['silver'] < 0) {
			$prpa['silver'] = 0;
		}
		
		$db->exec("UPDATE players SET silver = '".$prpa['silver']."' WHERE ID = $playerID");
		
		$acc = new Account(null, null, false, false);
		
		$ret[] = "Success:";
		$ret[] = "gamblegoldvalue:".$gamble;
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;
	case "playergamblecoins" :
		$gamble = intval($args[0]);
		
		if($gamble < 1) 
			exit('&Success:');
		
		$acc = new Account($playerData, null, false, false);
		
		$gamble = (rand(1, 3) == 3) ? $gamble : $gamble * -1;
		
		$acc->data['mush'] = $acc->data['mush'] + $gamble;
		if($acc->data['mush'] < 0) $acc->data['mush'] = 0;
				
		$db->exec('UPDATE players SET mush = '.$acc->data['mush'].' WHERE ID = '.$acc->data['ID']);

		$ret[] = "Success:";
		$ret[] = "gamblecoinsvalue:".$gamble;
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;
	case "playergoldframebuy" :
		$acc = new Account($playerData, null, false, false);
		
		if($acc->data['gframe'] == 1)
			exit('&Error:');
		
		$acc->data['mush'] -= 1000;
		$acc->data['gframe'] = 1;
		
		if($acc->data['mush'] < 0)
			exit('&Error:need more coins');
		
		$db->exec("UPDATE players SET gframe = {$acc->data['gframe']}, mush = {$acc->data['mush']} WHERE ID = {$acc->data['ID']}");
		
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;
	case 'playergetpets':
		$acc = new Account(null, null, true, true);
		$pD = new Pets();
		if($pD->havePets()) {
			$ret[] = "petsdefensetype:" . $pD->pvpData[0][1];
			$ret[] = "ownpets.petsSave:" . $pD->getPetsSave();
		}
		$ret[] ="Success:";
	break;
	case "petsgetstats" :
		// Pet stats by Greg
		
		$pet = intval($args[0]);
		
		$pD = new Pets();
		
		$pS = $pD->getPetStats($pet);
		
		if($pS[0] < 1)
			exit("Success:"); // Don't have pet wtf, kys tryhard hacker
		
		$ret[] = "ownpetsstats.petsStats:$pet/$pS[0]/50/$pS[2]/" . implode("/", $pS[3]) . "/0/0/0/0/0/4/4/0/";
		$ret[] = "Success:";
	break;
	case "playerpetfeed" :
		$acc = new Account(null, null, false, false);
		$pD = new Pets();
		$now = floor((time() - strtotime("2010-01-01")) / 86400) % 365;
                        
		$pet = $pD->getPetStats($args[0]);

		if($pet[0] == 0) { // Don't have pet
			exit('&error:this pet is not in your collection');
		}
                                
		if ($pet[0] >= 100) // Pet max 100
			exit("&Error:pet is maxed out");
                                
		$petFed = isset($pD->fedData[$args[0] - 1]) ? $pD->fedData[$args[0] - 1] : [0, 0];
                                
		if($petFed[0] >= 3 && $petFed[1] == $now) { // Pet fed max today
			exit('&Error:pet is not hungry');
		}               
                        
		$names = ["food_black", "food_orange", "food_green", "food_red", "food_blue"][$pet[1] - 1];
		$food = $acc->data[$names];
 
		if($food < 1) { // Wrong class? IDK
			exit('&error:');
		}                       
                        
		$pD->petData[$args[0] - 1] += 1;
                        
		// Fed++
		if($petFed[1] != $now)
		{
			$petFed[0] = 1;
			$petFed[1] = $now;
		}
		else
			$petFed[0]++;
 
		$acc->data[$names] -= 1;
		$GLOBALS['db']->query('UPDATE players SET '.$names.' = '.$names.' - 1 WHERE ID = '.$acc->data['ID']); 
                        
		$pD->fedData[$args[0] - 1] = $petFed;
 
		$petData = implode("/", $pD->petData);
		$fedData = json_encode($pD->fedData);
        
		$qry = $db->prepare('UPDATE players SET pets = ?, petsFed = ? WHERE ID = ?');
		$qry->execute([$petData, $fedData, $acc->data['ID']]);
		

		$pD2 = new Pets();
		$ret[] = "petsHatchIndex:".$args[0];
		$ret[] = "ownpets.petsSave:" . $pD2->getPetsSave();
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "Success:";
	break;
	case "petsdungeonfight" :
		// Dungeon fight of pets by Greg
		$acc = new Account(null, null, false, false); // New acc
		
		$place = $args[1]; // Place
		$mypet = $args[3]; // Fighting pet
		
		if($acc->data['dungeon_time'] > $GLOBALS["CURRTIME"]){ //if time not up
			if($acc->data['mush'] <= 0)
				exit("&Error:need more coins");
			$acc->data['mush']--;
			$acc->data['dungeon_time'] = 0;
			$db->exec("UPDATE players SET mush = mush - 1, dungeon_time = 0 WHERE ID = ".$acc->data['ID']);
		}else{
			$acc->data['dungeon_time'] = $GLOBALS["CURRTIME"] + 3600;
			$db->exec("UPDATE players SET dungeon_time = '{$acc->data['dungeon_time']}' WHERE ID = ".$acc->data['ID']);
		}
		
		$freeSlot = $acc->getFreeBackpackSlot();
		if($freeSlot === false) {
			exit("&Error:need a free slot");
		}
		
		$pD = new Pets($acc->data["pets"], $acc->data["petsFed"], $acc->data["petsDung"], $acc->data["petsPvP"], $acc->data["petsBest2"], null, $acc->data["blacksmith"], $acc->data["pethonor"]);
		
		$enemyLvl = 1 + ($pD->dungData[$place - 1] * 2);
		
		$enemyId = ($place - 1) * 20 + $pD->dungData[$place - 1] + 1;
		
		$me = $pD->getPetStats($mypet);
		if($me[0] == 0)
			exit("&Error:");
		
		$enemy = $pD->getPetStats($enemyId, $enemyLvl);
		
		$meM = $pD->petToMonster($me);
		$meM->convertToMyPet();
		
		$enemyM = $pD->petToMonster($enemy);
		
		$ret[] = "fightheader.fighters:13/0/0/49/2/".$meM->getFightHeader().$enemyM->getFightHeader();

		$simulation = new Simulation($meM, $enemyM);
		$simulation->simulate();


		$ret[] = "fight.r:". $simulation->fightLog;
		$ret[] = "winnerid:". $simulation->winnerID;
		
		$rewardLog = [];
		for($i = 0; $i < 21; $i++)
			$rewardLog[] = 0;
		
		$win = $simulation->winnerID == 100000;
		
		// rewarding
		if($win)
		{
			// win true
			$rewardLog[0] = 1;
				switch ($place) {
						case 1: $db->exec("UPDATE players SET food_black = food_black + 1 WHERE ID = ".$playerID); break;
						case 2: $db->exec("UPDATE players SET food_orange = food_orange + 1 WHERE ID = ".$playerID); break;
						case 3: $db->exec("UPDATE players SET food_green = food_green + 1 WHERE ID = ".$playerID); break;
						case 4: $db->exec("UPDATE players SET food_red = food_red + 1 WHERE ID = ".$playerID); break;
						case 5: $db->exec("UPDATE players SET food_blue = food_blue + 1 WHERE ID = ".$playerID); break;
					}
			// Give item
			$it['type'] = 16;
			$it['item_id'] = 30 + $place;
			$it['dmg_max'] = 0;
			$it['dmg_min'] = 0;
			$it['a1'] = 0;
			$it['a2'] = 0;
			$it['a3'] = 0;
			$it['a4'] = 0;
			$it['a5'] = 0;
			$it['a6'] = 0;
			$it['value_silver'] = 1;
			$it['value_mush'] = 0;
			
			$rewardLog[9] = $it['type'];
			$rewardLog[10] = $it['item_id'];
			//$GLOBALS['acc']->insertItem($it, $GLOBALS['freeSlot']);
			
			// +1 pet dung lvl
			$pD->dungData[$place - 1]++;
			$db->exec("UPDATE players SET petsDung = '" . implode("/", $pD->dungData) . "' WHERE ssid = '$ssid'");
		}
$pD = new Pets();
		$ret[] = "fightresult.battlereward:".join("/", $rewardLog)."/";
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "ownpets.petsSave:" . $pD->getPetsSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
	break;
	case "petspvpfight" :
		$now = Misc::getNow();
		
		$acc = new Account(null, null, false, false); // New acc
		
		$freeSlot = $acc->getFreeBackpackSlot();
		if($freeSlot === false) {
			exit("&Error:need a free slot");
		}
		
		$pclass = intval($args[2]) - 1; // Attack enemy with which pet class
		
		if($pclass < 0 || $pclass > 4)
			exit();
		
		$myPets = [];
		$enemyPets = [];
		
		// My Pet class
		$pD = new Pets($acc->data["pets"], $acc->data["petsFed"], $acc->data["petsDung"], $acc->data["petsPvP"], $acc->data["petsBest2"], null, $acc->data["blacksmith"], $acc->data["pethonor"]);
		
		if ($pD->pvpData[1][$pclass] == $now)
			exit();
		
		for($i = ($pclass * 20); $i < ($pclass * 20 + 20); $i++)
		{
			$stats = $pD->getPetStats($i + 1);
			
			if($stats[0] == 0)
				continue;
			
			$temp = $pD->petToMonster($stats);
			$temp->convertToMyPet();
			
			$myPets[] = $temp;
		}
		
		usort($myPets, function($a, $b) {
			return $a->lvl - $b->lvl;
		});
		
		// Enemy Pet class
		$epD = new Pets(null, null, null, null, $pD->pvpData[0][3], $pD->pvpData[0][0]);
		
		for($i = (($pD->pvpData[0][1] - 1) * 20); $i < (($pD->pvpData[0][1] - 1) * 20 + 20); $i++)
		{
			$stats = $epD->getPetStats($i + 1);
			
			if($stats[0] == 0)
				continue;
			
			$enemyPets[] = $epD->petToMonster($stats);
		}
		
		usort($enemyPets, function($a, $b) {
			return $a->lvl - $b->lvl;
		});
		
		//simulate fight
		$simulation = new GroupSimulation($myPets, $enemyPets);
		$simulation->simulate();
		
		//output logs
		for($i = 0; $i < count($simulation->simulations); $i++)
		{
			$fightn = $i+1;
			$fightLog[] = "fightheader".$fightn.".fighters:14/0/0/0/1/".$simulation->fightHeaders[$i];
			$fightLog[] = "fight".$fightn.".r:".$simulation->simulations[$i]->fightLog;
			$fightLog[] = "winnerid".$fightn.".s:".$simulation->simulations[$i]->winnerID;
		}
		
		$fightLog[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		
		$enemyFetch = $db->query("SELECT name, pethonor FROM players WHERE ID = " . $pD->pvpData[0][0])->fetchAll()[0];
		
		$enemyName = $enemyFetch["name"];
		
		$enemyHonor = $enemyFetch["pethonor"];
		
		$fightLog[] = 'fightgroups.r:100000,100001,' . $acc->data["name"] . ',' . $enemyName;
		
		$battleReward = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
		
		// Calculate honor
		if($enemyHonor > $acc->data['pethonor'])
			$honor = min(200, 100 + round(($enemyHonor - $acc->data['pethonor']) / 20));
		else
			$honor = max(0, 100 + round(($enemyHonor - $acc->data['pethonor']) / 20));
		
		if($simulation->win)
		{
			$battleReward[0] = 1;
			
				switch ($args[2]) {
						case 1: $db->exec("UPDATE players SET food_black = food_black + 1 WHERE ID = ".$playerID); break;
						case 2: $db->exec("UPDATE players SET food_orange = food_orange + 1 WHERE ID = ".$playerID); break;
						case 3: $db->exec("UPDATE players SET food_green = food_green + 1 WHERE ID = ".$playerID); break;
						case 4: $db->exec("UPDATE players SET food_red = food_red + 1 WHERE ID = ".$playerID); break;
						case 5: $db->exec("UPDATE players SET food_blue = food_blue + 1 WHERE ID = ".$playerID); break;
					}	
			// Give item
			$it['type'] = 16;
			$it['item_id'] = 30 + $args[2];
			$it['dmg_max'] = 0;
			$it['dmg_min'] = 0;
			$it['a1'] = 0;
			$it['a2'] = 0;
			$it['a3'] = 0;
			$it['a4'] = 0;
			$it['a5'] = 0;
			$it['a6'] = 0;
			$it['value_silver'] = 1;
			$it['value_mush'] = 0;
			
			$battleReward[9] = $it['type'];
			$battleReward[10] = $it['item_id'];
			//$acc->insertItem($it, $GLOBALS['freeSlot']);
			
			$battleReward[5] = $honor;
			
			$acc->data["pethonor"] += $honor;
			
			$enemyQuery = "pethonor = GREATEST(0, pethonor - $honor)";
		}
		else
		{
			$honor = 200 - $honor;
			
			$battleReward[5] = -1 * $honor;
			
			$acc->data["pethonor"] -= $honor;
			
			$enemyQuery = "pethonor = pethonor + $honor";
		}
		
		$pD->honor = $acc->data["pethonor"];
		
		$db->exec("UPDATE players SET $enemyQuery WHERE ID = ".$pD->pvpData[0][0]);
		
		$pD->pvpData[1][$pclass] = $now;
		
		$petsPvP = json_encode($pD->pvpData);
		
		$qry = $db->prepare("UPDATE players SET petsPvp = :pvp, pethonor = :honor WHERE ID = $playerID");
		$qry->bindParam(":pvp", $petsPvP);
		$qry->bindParam(":honor", $acc->data["pethonor"]);
		$qry->execute();
		
		$pD->newPvPEnemy(); // Get a new enemy
		
		$pD = new Pets();
		$ret[] = "Success:";
		$ret[] = implode("&", $fightLog);
		$ret[] = 'fightresult.battlereward:'.join('/', $battleReward);
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "ownpets.petsSave:".$pD->getPetsSave();
		$ret[] = "petsdefensetype:" . $pD->pvpData[0][1];
	break;
	case 'playerfriendset' :
		// Set player friend status by Greg - v2
		// Args: 0 = ID, 1 = Status
		
		$id = intval($args[0]);
		$status = intval($args[1]);
		
		$acc = new Account(null, null, false, false); // New acc
		
		if($id == $acc->data["ID"])
			exit(); // WTF LEL
		
		$acc->setFriendStatus($id, $status);
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "friendlist.r:".$acc->friendList();
		$ret[] = "Success:";
	break;
	case 'petsgethalloffame' :
		if(strlen($args[1]) > 2){
			
			$args[1] = Account::formatUser($args[1]);

			$qry = $db->prepare('SELECT ID, pethonor FROM players WHERE name = :name');
			$qry->bindParam(':name', $args[1]);
			$qry->execute();

			if($qry->rowCount() == 0)
				exit('&Error:player not found');

			$p = $qry->fetch(PDO::FETCH_ASSOC);

			$qry = $db->query('SELECT Count(*) as `rank` FROM players WHERE pethonor > '.$p['pethonor'].' OR (pethonor = '.$p['pethonor'].' AND ID > '.$p['ID'].')');

			$args[0] = $qry->fetch(PDO::FETCH_ASSOC)['rank'];
		}

		$args[0] -= $args[2] + 1;
		if($args[0] < 0)
			$args[0] = 0;

		
		$qry = $db->prepare("SELECT players.name, guilds.name AS gname, players.petsDung, players.pethonor FROM players FORCE INDEX(pethonor) LEFT JOIN guilds ON players.guild = guilds.ID 
			WHERE players.pethonor >= 0 ORDER BY players.pethonor DESC, players.ID DESC LIMIT {$args[0]}, 30");
		$qry->execute();

		$players = $qry->fetchAll( PDO::FETCH_ASSOC );
		
		$list = [];
		for($i = 0; $i < count($players); $i++) {
			//$players[$i]["petsDung"] = explode("/", $players[$i]["petsDung"])[5] ?? 0;
			
			if($players[$i]["petsDung"] != null)
			{
				$petsDung = explode("/", $players[$i]["petsDung"])[5];
				
			}else{
				$petsDung = 0;
			}
			
			$players[$i]["petsDung"] = $petsDung;
				
			$rank = $args[0] + $i + 1;
			$list[] = $rank.','.join(',', $players[$i]).",0";
			
		}
		
		//rank, name, gname, lvl, honor
		$ret[] = "RanklistPets.r:".join(';', $list);
		$ret[] = "Success:";
	break;
	case 'fortressgethalloffame' :
		// Another useless..........
		/*
		$sql = "SELECT * FROM players WHERE ssid = '$ssid'";
		$qry = $db->query($sql);
		$name = $qry->fetch(PDO::FETCH_ASSOC)["name"];
		*/
		
		if(strlen($args[1]) > 2){
			
			$args[1] = Account::formatUser($args[1]);
			$owner = Account::getAccountID($args[1]);

			$qry = $db->prepare('SELECT fortressID, forthonor FROM fortress WHERE owner = :owner');
			$qry->bindParam(':owner', $owner);
			$qry->execute();

			if($qry->rowCount() == 0)
				exit('&Error:player not found');

			$p = $qry->fetch(PDO::FETCH_ASSOC);

			$qry = $db->query('SELECT Count(*) as `rank` FROM fortress WHERE forthonor > '.$p['forthonor'].' OR (forthonor = '.$p['forthonor'].' AND fortressID > '.$p['fortressID'].')');

			$args[0] = $qry->fetch(PDO::FETCH_ASSOC)['rank'];
		}

		$args[0] -= $args[2] + 1;
		if($args[0] < 0)
			$args[0] = 0;

		$upgraded = "fortress.b0 * 10 + fortress.b1 + fortress.b2 + fortress.b3 + fortress.b4 + fortress.b5 + fortress.b6
			+ fortress.b7 + fortress.b8 + fortress.b9 + fortress.b10 + fortress.b11";
		
		$qry = $db->prepare("SELECT players.name, guilds.name AS gname, {$upgraded} AS upgraded, fortress.forthonor AS forthonor FROM players FORCE INDEX(forthonor) LEFT JOIN guilds ON players.guild = guilds.ID 
			LEFT JOIN fortress ON players.ID = fortress.owner WHERE fortress.forthonor >= 0 ORDER BY fortress.forthonor DESC, players.ID DESC LIMIT {$args[0]}, 30");
		$qry->execute();

		$players = $qry->fetchAll( PDO::FETCH_ASSOC );
		
		$list = [];
		for($i = 0; $i < count($players); $i++) {
			
			if($players[$i]["upgraded"] > 0)
			{
				$rank = $args[0] + $i + 1;
				$list[] = $rank.','.join(',', $players[$i]).",0";
			}	
		}
		
		//rank, name, gname, upgraded, honor
		$ret[] = "Ranklistfortress.r:".join(';', $list);
		$ret[] = "Success:";
		
		//$ret[] = "Ranklistfortress.r:1591,kreszko1,,218,1417,0;1592,gergc33,Fairy Tail,250,1417,0;1593,Rara,Fáraók Céhe,238,1417,0;1594,Disturbed,,249,1416,0;1595,Drub,,248,1415,0;1596,Vasznpszvotsz,,228,1413,0;1597,Essneki,,266,1413,0;1598,stark2015,EmErGiNgS,226,1413,0;1599,Lazuman,,244,1413,0;1600,Piskota,,226,1413,0;1601,Harcicickány,,254,1412,0;1602,DeepDildoHentaiMonster,Magyar Betyár Sereg,209,1412,0;1603,oldtibi,Kitaszítottak,290,1412,0;1604,EthanW,rablok céh,257,1412,0;1605,AkcioooK,,258,1412,0;1606," . $name . ",NOT WORKING,285,1412,0;1607,adrianbuzi,SCBP,210,1411,0;1608,ladymoon,Shadow Sword,249,1411,0;1609,benedek11,,240,1411,0;1610,Szaura,,249,1410,0;1611,kompusz123,,231,1410,0;1612,Tóbi,We are the best,267,1410,0;1613,laczkovics123,,249,1410,0;1614,Sir Andreas,,258,1410,0;1615,Mazs,Kekuravtyemuj,211,1409,0;1616,A20,awful,227,1409,0;1617,Khyra,,238,1409,0;1618,Lanselot,,239,1408,0;1619,Bridget01,Angyalok És Ördögök,253,1407,0;1620,IKIHUN,,229,1407,0;1621,varázslatos márkócska,EmErGiNgS,223,1407,0;&Success:";
	break;
	case 'underworldgethalloffame':
		if(strlen($args[1]) > 2){
			
			$args[1] = Account::formatUser($args[1]);
			$owner = Account::getAccountID($args[1]);

			$qry = $db->prepare('SELECT ID, uwhonor FROM underworld WHERE owner = :owner');
			$qry->bindParam(':owner', $owner);
			$qry->execute();

			if($qry->rowCount() == 0)
				exit('&Error:player not found');

			$p = $qry->fetch(PDO::FETCH_ASSOC);

			$qry = $db->query('SELECT Count(*) as `rank` FROM underworld WHERE uwhonor > '.$p['uwhonor'].' OR (uwhonor = '.$p['uwhonor'].' AND ID > '.$p['ID'].')');

			$args[0] = $qry->fetch(PDO::FETCH_ASSOC)['rank'];
		}

		$args[0] -= $args[2] + 1;
		if($args[0] < 0)
			$args[0] = 0;
		
		$qry = $db->prepare("SELECT players.name, guilds.name AS gname, underworld.heart AS lvl, underworld.uwhonor AS honor FROM players FORCE INDEX(honor) LEFT JOIN guilds ON players.guild = guilds.ID 
			LEFT JOIN underworld ON players.ID = underworld.owner WHERE underworld.uwhonor >= 0 ORDER BY underworld.uwhonor DESC, players.ID DESC LIMIT {$args[0]}, 30");
		$qry->execute();

		$players = $qry->fetchAll( PDO::FETCH_ASSOC );
		
		$list = [];
		for($i = 0; $i < count($players); $i++) {
			
			if($players[$i]["lvl"] > 0)
			{
				$rank = $args[0] + $i + 1;
				$list[] = $rank.','.join(',', $players[$i]).",0";
			}	
		}
		
		//rank, name, gname, upgraded, honor
		$ret[] = "ranklistunderworld.r:".join(';', $list);
		$ret[] = "Success:";
	break;
	case "playersetnogroupinvite":
		// No group invite by Greg
		
		$val = $args[0] == 1 ? 1 : 0;
		
		$db->exec("UPDATE players SET noinv = $val WHERE ID = $playerID");
		
		$acc = new Account(null, null, false, false); // New acc
		
		
		
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		
		break;
		case 'playerwitchspenditem':
			$acc = new Account($playerData, null, false, false);
			
			if($acc->data['lvl'] < 66) exit('&Error:too low level');
			
			(int) $args[0];
			(int) $args[1];

			$args[2] = 11;
			$args[3] = 0;
			
			$acc->moveItem($args);

			$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		break;	
	case 'groupmessagesendaround':
		// Guild mail by Greg
		
		$subject = $args[0];
		$body = $args[1];
		
		$time = $GLOBALS["CURRTIME"];
		
		$qry = $db->query("SELECT ID FROM players WHERE guild = $playerGuild");
		
		foreach($qry->fetchAll() as $row){
			$reciver = $row['ID'];
			
			$qry = $db->prepare("INSERT INTO messages(sender, reciver, time, topic, message) VALUES($playerID, $reciver, $time, :topic, :message)");
			$qry->bindParam(':topic', $subject);
			$qry->bindParam(':message', $body);
			$qry->execute();
		}
	
		exit("Success:");
	
		break;
	default:
	
		if($sandbox)
		{
			var_dump($act);
			var_dump($args);
			$ret[] = "";
			$ret[] = "Error: not implemented";
		}
		else
			$ret[] = "Success:";


		break;
	case 'playertoilettopenwithkey':
		break;
	case 'underworldbuildstart': // Underworld building by Jack
	
		$acc = new Account(null, null, true, false); // New acc
		
		$uw = new Underworld($acc->data["ID"]);
		
		$b = $uw->getBuilding($args[0]);
		
		$silvercost = $uw->getBuildingPrice($args[0], $uw->data[$b] + 1);
		
		if($acc->data['silver'] >= $silvercost[0]) {
			$acc->data['silver'] -= $silvercost[0];
			$db->exec("UPDATE players SET silver = ".$acc->data['silver']." WHERE ID = ".$acc->data['ID']);
		} else {
			exit('&Error:need more gold');
		}	
	
		$uw->startBuilding($args[0]);
	
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owntower.towerSave:".$acc->getTowerSave();
		$ret[] = "underworldprice.underworldPrice(10):".$uw->getUpgradePrice();
		$ret[] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUnitUpgradePrice();
		$ret[] = "underworldmaxsouls:".$uw->getMaxSouls();
	
		break;
	case 'underworldbuildfinished':
		// Update v2
	
		$acc = new Account(null, null, true, false); // New acc
		$uw = new Underworld($acc->data["ID"]);
	
		$id = $uw->data['build_id'];
		
		$b = $uw->getBuilding($id);
		
		$qry = [];
		
		//By Jack
		//Stop when not building anything, fixes a click "hack"
		if($uw->data["build_id"] == null || $uw->data["build_id"] == 0)
			exit();
		
		// Soul Extractor, Gold Mine
		// Fix - after building done, current soul/gold 0
		if($id == 3 || $id == 4) {
			$g = $id - 2;
			
			$uw->data['gather'.$g] = $GLOBALS["CURRTIME"];
			$qry[] = "UPDATE underworld SET gather".$g." = ".$GLOBALS["CURRTIME"]." WHERE owner = ".$acc->data['ID'];
			
			if($id == 3)
				$uw->newHourlyGold($acc->data["lvl"]);
			
		}
		
		$mushcost = 0;
		if($uw->data['build_end'] > $GLOBALS["CURRTIME"])
			$mushcost = ceil(($uw->data['build_end'] - $GLOBALS["CURRTIME"]) / 600);
		
		if($mushcost > $acc->data['mush'])
			exit("&Error:need more coins");
	
		if($mushcost > 0){
			$acc->data['mush'] -= $mushcost;
			$qry[] = "UPDATE players SET mush = mush - ".$mushcost." WHERE ID = ".$acc->data['ID'];
		}
		
		$uw->finishBuilding($args[0]);
		
		// Honor system as fortress
		$uw->data['uwhonor'] += 10;
		$qry[] = "UPDATE underworld SET uwhonor = ".$uw->data['uwhonor']." WHERE owner = ".$acc->data['ID'];
		
		if(!empty($qry)) {
			$db->exec(join(';', $qry));
		}
		
		$uw = new Underworld($acc->data["ID"]);
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owntower.towerSave:".$acc->getTowerSave();
		$ret[] = "underworldprice.underworldPrice(10):".$uw->getUpgradePrice();
		$ret[] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUnitUpgradePrice();
		$ret[] = "underworldmaxsouls:".$uw->getMaxSouls();
		
		break;
	case 'underworldgather': // By Jack
		//1 - Gold ($ret[17])
		//2 - Soul Extractor ($ret[12])
		//3 - Time ($ret[26])
		
		$acc = new Account(null, null, true, false); // New acc
		$uw = new Underworld($acc->data["ID"]);
		
		$qry = [];
		
		$gain = 0;
		switch ($args[0]) {
			case 1: // Gold
				$gain = floor(($GLOBALS["CURRTIME"] - $uw->data["gather1"]) * ($uw->data["claim_gold"] / 3600));
				
				if($gain > floor($uw->data["claim_gold"]) * 10) 
					$gain = floor($uw->data["claim_gold"]) * 10;
				
				// Add
				$acc->data['silver'] += $gain;
				$qry[] =  "UPDATE players SET silver = ".$acc->data['silver']." WHERE ID = ".$acc->data['ID'];
			
			break;
			case 2: // Soul
				//$last_gather = max($uw->data["gather1"], $uw->data["gather2"], $uw->data["gather3"]);
				//$gain = floor(($last_gather - $uw->data["gather2"]) * ($uw->getSoulExtractorResources($uw->data["extractor"])[0] / 3600) * $soulbonus);
				$gain = floor(($GLOBALS["CURRTIME"] - $uw->data["gather2"]) * ($uw->getSoulExtractorResources($uw->data["extractor"])[0] / 3600) * $soulbonus);
				
				if($gain > $uw->getSoulExtractorResources($uw->data["extractor"])[1]) {
					$gain = $uw->getSoulExtractorResources($uw->data["extractor"])[1];
				}

				// Add
				$uw->data['soul'] += $gain;
				$qry[] = "UPDATE underworld SET soul = ".$uw->data['soul']." WHERE owner = ".$acc->data['ID'];
				
				break;
			case 3: // Time
				$uw->gatherTimeMachine();
			break;
			
		}	
		
		// Remove gathered
		$uw->data["gather".$args[0]] = $GLOBALS["CURRTIME"];
		$qry[] =  "UPDATE underworld SET gather".$args[0]." = ".$GLOBALS["CURRTIME"]." WHERE owner = ".$acc->data['ID'];
		
		$db->exec(join(';', $qry));
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owntower.towerSave:".$acc->getTowerSave($uw);
		break;
	case 'playersetflag': // Flag by Jack
		$val = $args[0] ?? '';
		$qry = $db->prepare('UPDATE players SET flag = :flag WHERE ID = :ID');
		$qry->execute([':flag' => $val, ':ID' => $playerID]);
		
		$ret[] = "Success:";
		break;
	case 'accountsetlanguage':
		$lang = $args[0] ?? '';
		
		$qry = $db->prepare('UPDATE players SET language = ? WHERE ID = ?');
		$qry->execute([$lang, $playerData['ID']]);
		
		$ret[] = 'success:';
	break;
	case 'playertutorialstatus': // Tutorial by Jack		
		$val = (int) ($args[0] ?? 0);
		$qry = $db->prepare('UPDATE players SET tutorial = :tutorial WHERE ID = :ID');
		$qry->execute([':tutorial' => $val, ':ID' => $playerID]);
		
		$ret[] = "Success:";
		$ret[] = '#ownplayersave:597/'.$val;
		break;
	case 'underworldbuildstop':
		$qry = [];
	
		$acc = new Account(null, null, true, false); // New acc
		$uw = new Underworld($acc->data['ID']);
		
		//Stop build
		$uw->stopBuilding();
			
		//Recieve 75% back of the resources
		$building = $uw->getBuilding($args[0]);
		
		$price = $uw->getBuildingPrice($args[0], ($uw->data[$building] + 1));
		$acc->data['silver'] += floor($price[0] * 0.75);
		$uw->data['soul'] += floor($price[1] * 0.75);
		
		$qry[] = "UPDATE players SET silver = ".$acc->data['silver']." WHERE ID = ".$acc->data['ID'];
		$qry[] = "UPDATE underworld SET soul = ".$uw->data['soul']." WHERE owner = ".$acc->data['ID'];
		
		$db->exec(join(';', $qry));
		
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = "Success:";
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owntower.towerSave:".$acc->getTowerSave();
		break;
	case 'underworldattack': // By Jack
		$acc = new Account(null, null, true, false);
		$uw = new Underworld($acc->data["ID"]);
		
		//Log fight
		$fightlog = [];
		
		if(!$uw->haveIt) {
			exit();
		}
		
		if($uw->data["lured"] >= $uw->data["gate"]) {
			exit(); // max lured today
		}

		//get opponent
		$qry = $db->prepare("SELECT players.*, guilds.portal AS guild_portal FROM players LEFT JOIN guilds ON players.guild = guilds.ID WHERE players.ID = :ID");
		$qry->bindParam(':ID', $args[0]);
		$qry->execute();

		//player not found
		if($qry->rowCount() == 0)
			exit("&Error:player not found");

		$opponentData = $qry->fetch(PDO::FETCH_ASSOC);
		
		$items = $db->query("SELECT * FROM items WHERE owner = ".$opponentData['ID']." AND slot BETWEEN 10 AND 19");
		$items = $items->fetchAll(PDO::FETCH_ASSOC);
		
		$p = new Player($opponentData, $items);
		$opponent = [$p];
		
		$unit = $uw->getUnderworldUnits();
		
		//simulate fight
		$simulation = new GroupSimulation($unit, $opponent);
		$simulation->simulate();
		
		//output logs
		for($i = 0; $i < count($simulation->simulations); $i++)
		{
			$fightn = $i+1;
			$fightlog[] = "fightheader".$fightn.".fighters:16/0/0/0/1/".$simulation->fightHeaders[$i];
			$fightlog[] = "fight".$fightn.".r:".$simulation->simulations[$i]->fightLog;
			$fightlog[] = "winnerid".$fightn.".s:".$simulation->simulations[$i]->winnerID;			
		}
		
		$fightlog[] = 'fightadditionalplayers.r:'.$simulation->getAdditionals();
		
		$uw->data["lured"] ++;
		$db->exec("UPDATE underworld SET lured = ".$uw->data["lured"]." WHERE owner = ".$acc->data["ID"]);

		$rewardLog = [];
		for($i = 0; $i < 6; $i++)
			$rewardLog[] = 0;
		
		if($simulation->win) {
			$rewardLog[0] = 1;
		
			$soulres = $uw->getSoulExtractorResources($uw->data["extractor"])[0];
			$soul = $soulres + (rand(floor($soulres / 8), floor($soulres / 6)));
			
			$lvl = $opponentData['lvl'] + $acc->data['lvl'];
			$soul += $soul * floor($lvl / 2.5); 
			
			$soul += floor($soul * ($lvl / 4));
			$soulbonus = floor($soul * (0.1 * $uw->data["torture"]));
			
			$rewardLog[3] = $soul;
			$rewardLog[4] = $soulbonus;
			
			$uw->data["soul"] += $soul + $soulbonus;
		
			$db->exec("UPDATE underworld SET soul = ".$uw->data["soul"]." WHERE owner = ".$acc->data["ID"]);
			
			//Max soul at lvl
			if($uw->data["battle_lvl"] <= 0)
				$uw->data["battle_lvl"] = $opponentData["lvl"] + 1;
			else
				$uw->data["battle_lvl"] ++;
			
			// Honor system as fortress
			$uw->data['uwhonor'] += round($opponentData["lvl"] / 40);
			$db->exec("UPDATE underworld SET uwhonor = ".$uw->data['uwhonor']." WHERE owner = ".$acc->data['ID']);
		
		} else {
			// Max soul at lvl if loss
			$uw->data["battle_lvl"] -= 5;
		
			if($uw->data["battle_lvl"] < 0)
				$uw->data["battle_lvl"] = 0;
			
			// Honor system loss 5
			$uw->data['uwhonor'] -= 5;
			if($uw->data['uwhonor'] < 0)
				$uw->data['uwhonor'] = 0;
			$db->exec("UPDATE underworld SET uwhonor = ".$uw->data['uwhonor']." WHERE owner = ".$acc->data['ID']);
		
		}	
		
		// Update max soul
		$db->exec("UPDATE underworld SET battle_lvl = ".$uw->data["battle_lvl"]." WHERE owner = ".$acc->data['ID']);
		
		
		//"underworldpillage\":[\"won\",\"fightnr\",\"gold\",\"souls\",\"bonussouls\",\"exp\"]
		
		$fightlog = join('&', $fightlog);
		$ret[] = $fightlog;
		
		$rewardLog = join("/", $rewardLog);
		
		$ret[] = "fightresult.underworldpillage:".$rewardLog;
		$ret[] = "combatloglist.s:".$acc->getCombatLog(); // Update logs
		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = 'Success:';
		$ret[] = "owntower.towerSave:".$acc->getTowerSave();
		
		
		break;	
	case 'getserverversion':
		$ret[] = "Success:";
		$ret[] = "serverversion:$gameVersion";
		$ret[] = "timestamp:" . time();
	break;
	case 'underworldupgradeunit': //By Jack
		// Args 0: unit
		// 1 - Goblins, 2 - Trolls, 3 - Keeper
		$qry = [];
		
		$acc = new Account(null, null, true, false);
		$uw = new Underworld($acc->data["ID"]);
		$defender = null;
		
		if(!$uw->haveIt)
			exit();
		
		switch($args[0]) {
			case 1: // Goblins
				if($uw->data['goblin'] <= 0)
					exit();
				
				$defender = "goblins";
			break;
			case 2: // Trolls
				if($uw->data['troll'] <= 0)
					exit();
				
				$defender = "trolls";
			break;
			case 3: // Keeper
				if($uw->data['keeper'] <= 0)
					exit();
				
				$defender = "keeper";
			break;
			
		}
		
		if($defender == null)
			exit();
		
		$price = $uw->getUnitUpgradePrice($args[0]);

		// Check player has enough gold and soul
		if($acc->data['silver'] < $price[0])
			exit();
				
		if($uw->data['soul'] < $price[1])
			exit();
				
		//Remove prices
		$acc->data['silver'] -= $price[0];
		$uw->data['soul'] -= $price[1];
		$qry[] = "UPDATE players SET silver = ".$acc->data['silver']." WHERE ID = ".$acc->data['ID'];
		$qry[] = "UPDATE underworld SET soul = ".$uw->data['soul']." WHERE owner = ".$acc->data['ID'];
		
		//Upgrade
		$unit = "unit_".$defender;
		$uw->data[$unit] ++;
		$qry[] = "UPDATE underworld SET ".$unit." = ".$uw->data[$unit]." WHERE owner = ".$acc->data['ID'];
		
		if(!empty($qry)) {
			$db->exec(join(';', $qry));
		}

		$ret[] = "timestamp:".$GLOBALS["CURRTIME"];
		$ret[] = 'Success:';
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = "owntower.towerSave:".$acc->getTowerSave();
		$ret[] = "underworldprice.underworldPrice(10):".$uw->getUpgradePrice();
		$ret[] = "underworldupgradeprice.underworldupgradePrice(3):".$uw->getUnitUpgradePrice();
		$ret[] = "underworldmaxsouls:".$uw->getMaxSouls();
		
	break;
	
	case 'playerhelpshiftauthtoken':
		$ret[] = 'success:';
	break;
	
	case 'accountsave':
		$mail = $args[0] ?? '';
		$pass = $args[1] ?? '';
		
		if (!filter_var($mail, FILTER_VALIDATE_EMAIL))
			exit("&Error:mail is unavaible");
		
		if (strlen($pass) != 40)
			exit("&Error:invalid password");
		
		$qry = $db->prepare("SELECT email FROM players WHERE email = ?");
		$qry->execute([$mail]);

		if($qry->fetch( PDO::FETCH_ASSOC ))
			exit("&Error:mail is unavaible");
		
		$acc = new Account($playerData, null, false, false);
		
		if ($acc->data['accountsave'] == 1)
			exit("&Error:already save");
		
		$acc->data['mush'] += 10;
		
		$qry = $db->prepare('UPDATE players SET email = ?, password = ?, accountsave = ?, mush = ? WHERE ID = ?');
		$qry->execute([$email, $pass, 1, $acc->data['mush'], $acc->data['ID']]);
			
		$ret[] = "timestamp:".$CURRTIME;
		$ret[] = 'Success:';
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
	break;
	
	case 'unlockfeature':
	$acc = new Account(null,null,false,false);
		$type = intval($args[0]);
		$value = intval($args[1]);
		
		$unlocked = false;
		
		if($type == 1) {
			if($acc->data['mirror'] > 12)
				exit('&error:');
			
			if($acc->data['unlockmirror'] < 1)
				exit('&error:');
			
			$acc->data['mirror'] ++;
			$acc->data['unlockmirror'] --;
			
			$qry = $db->prepare('UPDATE players SET mirror = :mirror, unlockmirror = :unlockmirror WHERE ID = :ID');
			$qry->execute(array(
				':mirror' => $acc->data['mirror'],
				':unlockmirror' => $acc->data['unlockmirror'],
				':ID' => $acc->data['ID']
			));
			
			$unlocked = true;
		}
		
		if($type == 20) {
			if($acc->data['petnest'] == 0) exit();
			if(!empty($acc->data['pets'])) exit();
			
			$pD = new Pets();

			$pD->petData[rand(0, 2)] = 1;
			$pD->petData[rand(20, 22)] = 1;
			$pD->petData[rand(40, 42)] = 1;
			$pD->petData[rand(60, 62)] = 1;
			$pD->petData[rand(80, 82)] = 1;
			
			$petData = implode("/", $pD->petData);
			$pD->recountBest();
						
			$qry = $db->prepare('UPDATE players SET pets = ?, petNest = 0 WHERE ID = ?');
			$qry->execute([$petData, $acc->data['ID']]);
			
			$ret[] = "ownpets.petsSave:".$pD->getPetsSave();
			
			$unlocked = true;
		}
		
		if($type == 21) {
			$unlockPets = json_decode($acc->data['petsunlock'], true);
			if($unlockPets[$value - 1] == 0) exit();
			if(empty($acc->data['pets'])) exit();
			
			$pet_class = ($value <= 20) ? 1 : floor(($value - 1) / 20) + 1;

			$pD = new Pets();
			
			$pet_dung = (int) $pD->dungData[$pet_class - 1];
			
			$pet_position = $value % 20;
			if($value % 20 == 0) $pet_position = 20;
			
			if($pD->petData[$value - 1] > 0) exit();
			
			if(($pet_position > 3) && ($pet_dung < $pet_position)) exit();
			
			$pD->petData[$value - 1] = 1;
			
			$petData = implode("/", $pD->petData);
			$pD->recountBest();
			
			$unlockPets[$value - 1] = 0;
			
			$qry = $db->prepare('UPDATE players SET pets = ?, petsunlock = ? WHERE ID = ?');
			$qry->execute([$petData, json_encode(array_values($unlockPets)), $acc->data['ID']]);
			
			$ret[] = "ownpets.petsSave:".$pD->getPetsSave();
			
			$unlocked = true;
		}
		
		if($unlocked) $acc = new Account(null,null,false,false);
		
		$ret[] = "ownplayersave.playerSave:".$acc->getPlayerSave();
		$ret[] = 'unlockfeature:'.$acc->unlockFeature();
	break;
	
	case 'playeropencalender':
		$acc = new Account($playerData, null, false, false);
		
		if ($acc->data['calenderNext'] == Misc::getNow())
			exit ('&error:already claimed today');
		
		$acc->data['calenderDay']++;
		
		if ($acc->data['calenderDay'] >= 20) {
			$acc->data['calenderDay'] = 0;
		}
		
		$acc->data['calenderNext'] = Misc::getNow();
		
		$calData = CAL[$acc->data['calenderDay'] - 1];
		
		$id = $calData[0];
		$val = $calData[1] * 500;
		
		switch ($id) {
			case 2: # mush
				$acc->data['mush'] += $val;
				$db->query("UPDATE players SET mush = {$acc->data['mush']} WHERE ID = {$acc->data['ID']}");
			break;
		}
		
		$db->query("UPDATE players SET calenderDay = {$acc->data['calenderDay']}, calenderNext = {$acc->data['calenderNext']} WHERE ID = {$acc->data['ID']}");
		
		$result = "";
		foreach (CAL as $pair) { $result .= $pair[0] . "/" . $pair[1] . "/"; }
		$ret[] = 'calenderinfo:'.rtrim($result, "/");
		
		$ret[] = 'calenderreward.r:'.$id.'/'.$val;
		$ret[] = 'Success:';
		$ret[] = 'ownplayersave.playerSave:'.$acc->getPlayerSave();
	break;
	
}

ob_clean();
echo join("&", $ret);
?>