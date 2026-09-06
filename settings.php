<?php
/***** SERVER SETTINGS *****/
// System settings
$gameName = "Shakes & Fidget";
$sandbox = false; // Sandbox mode, use TRUE only if you develope the script, it'll display errors and more info about not working things

// db
$db = new PDO('mysql:host=localhost;dbname=sfprivate;charset=utf8', 'root', '');
$db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("SET sql_mode=''");

$timezone = 'Europe/Budapest';
date_default_timezone_set($timezone);
$CURRTIME = time(); // If there are problems with the timezone, you can add to it.
$clientWeb = "localhost/v15"; // Domain of the server

$logRequests = false;

$countryCode = "eu";
$defLang = "en";

$gameVersion = 1988;

// Welcome mail settings
$wmail_enable = true; // Enable welcome mail? (wasting database space - Greg)
$wmail_subject = 'Welcome!'; // Subject

// The mail
$wmail_body = 'Hello world!'; // Mail body, Use $b for new lines
$saveBattleLogs = 1; // Save all logs 0 - no save

// Delete all unsaved battle log when this limit reached (original sfgame limit: 50)
$clearBattleLogs = 20;  // Set custom limit here (Disable: -1)

// Max storable log per player (These fight logs aren't deleted when clearing logs)
$maxBattleLogs = 15; // Disable: -1

// Game settings
$xpbonus = 3;
$goldbonus = 5;
$currEvent = -1; // Current event:  -1 = automatic, 0 = Nothing, 1 = XP, 2 = Epic, 3 = Gold, 4 = Mushroom, 5 = birthday, 6 = Christmas, 7 = Easter, 8 = Halloween, 9 = oktoberfest
$enable_mushroom_event = false; // Set it to true on small amount of mushroom servers
$event_onlyWeekend = true; // Normal events only on weekends
$oktoberfest = false; // Oktoberfest - free beer, no other tavern events
$event_xpbonus = 2; // Special event xp
$event_goldbonus = 15; // Special event gold
$epicbonus = 40; // Epicchance, understand it in sf/item.php
$event_epicbonus = 40;
$mushbonus = 3;
$event_mushbonus = 1;
$xpGenVersion = 1; // XP Generation version (1 = easy, 2 = harder)
$adventureMushSkip = 1; // Enable mushroom skip at quest travel (1 = enable, 0 = disable)
$weaponMultipliers = [2.3, 5.5, 2.8];
$statPlus = 50; // How much stat he gets on one click
$levelLimit = 2500; // Level limit
$startingGold = 1; // Starting silver
$startingMush = 1000000; // Starting mushroom
$defAllDungUnlocked = true; // Unlock all dungeons when you register
$defToiletUnlocked = false; // Unlock WC (when register)
$defUwUnlocked = true; // Unlock Underworld (when register)
$justBestPots = false; // Only XXL potions
$infiniteBeer = true; // True - Endless beer; False - Max. 10 beers per day
$infiniteWheel = false; // No limit wheel of fortune (Dr. Abawuwu) spins per day
$soulbonus = 20; // Faster soul produce in underworld
$uwStartingSoul = 0; // Underworld soul
$fortStartingWood = 0; // Fortress wood
$fortStartingStone = 0; // Fortress stone
$startingHourglass = 10000;
$skipAdventureTravel = false; // No travel when accept a quest (quest duration = 0:00)
$uwMaxSoul = 10; // Underworld max souls - In millions
$tutorial = false;

$dailyMushrooms = 10000;
$maxDungeonsLight = 28;
$maxDungeonsShadow = 28;

// mainly account (can use to log if no one accounts has usysclass to 3[moderator] or 4[administrator])
$acpLogin = 'System';
$acpPassword = 'test123';
$acpSalt = '9Vb6hdIonEDNWyBKK59TCgF9zLpCSVgl'; // recommend to change it