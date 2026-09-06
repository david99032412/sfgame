<?php
    if (!$index) exit;
	
	$diffLight = 100 + (100 * ($player['dungeonLightResetCount'] * 5));
	$xpBonusLight = min(50, 5 * $player['dungeonLightResetCount']);
	$diffShadow = 100 + (100 * ($player['dungeonShadowResetCount'] * 5));
	$xpBonusShadow = min(50, 5 * $player['dungeonShadowResetCount']);
	$diffTower = 100 + (100 * ($player['towerResetCount'] * 5));
	$xpBonusTower = min(50, 5 * $player['towerResetCount']);
	$diffTwister = 100 + (100 * ($player['twisterResetCount'] * 5));
	$xpBonusTwister = min(50, 5 * $player['twisterResetCount']);
	$diffIdols = 100 + (100 * ($player['idolsResetCount'] * 5));
	$xpBonusIdols = min(50, 5 * $player['idolsResetCount']);
	
	$qry = $db->prepare('SELECT COUNT(ID) AS count FROM players WHERE poll > :15m');
	$qry->execute([':15m' => $CURRTIME - 900]);
	$online15 = $qry->fetch(PDO::FETCH_ASSOC)['count'];
	
	$qry = $db->prepare('SELECT COUNT(ID) AS count FROM players WHERE poll > :24h');
	$qry->execute([':24h' => $CURRTIME - 86400]);
	$online24 = $qry->fetch(PDO::FETCH_ASSOC)['count'];
	
	$qry = $db->query('SELECT ID FROM players');
	$accs = $qry->rowCount();
?>
<div class="card mb-4">
    <h3 class="card-header text-center text-uppercase"><?=$locale['home-itm']['player_info']?></h3>
    <div class="card-body">
        <div class="text-center" style="font-size:20px;">
            <p><?=$locale['home-itm']['player_name']?>: <?=$player['name']?> (<?=$locale['home-itm']['level']?> <font color="gold"><?=formatNumberByLocale($player['lvl'], $player['language'])?></font>)</p>
            <p><?=$locale['home-itm']['rank']?>: <?=$locale['ranks'][$player['usysclass']]??''?></p>
            <p><?=$locale['home-itm']['class']?>: <img src="img/class/class<?=$player['class']?>.png" width="25" height="25"></p>
            <p><?=$locale['home-itm']['mushrooms']?>: [<font color="gold"><?=formatNumberByLocale($player['mush'], $player['language'])?></font>]</p>
            <p><?=$locale['home-itm']['completed_quests']?>: [<font color="gold"><?=formatNumberByLocale($player['questsdone'], $player['language'])?></font>]</p>
            <p><?=$locale['home-itm']['worked_hours']?>: [<font color="gold"><?=formatNumberByLocale($player['workedhours'], $player['language'])?></font>]</p>
            <p><?=$locale['home-itm']['arena_wins']?>: [<font color="gold"><?=formatNumberByLocale($player['fightswon'], $player['language'])?></font>]</p>
            <p><?=$locale['home-itm']['current_difficulty_light']?>: [<font color="gold"><?=formatNumberByLocale($diffLight, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_xp_bonus_light']?>: [<font color="gold"><?=formatNumberByLocale($xpBonusLight, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_difficulty_shadow']?>: [<font color="gold"><?=formatNumberByLocale($diffShadow, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_xp_bonus_shadow']?>: [<font color="gold"><?=formatNumberByLocale($xpBonusShadow, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_difficulty_tower']?>: [<font color="gold"><?=formatNumberByLocale($diffTower, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_xp_bonus_tower']?>: [<font color="gold"><?=formatNumberByLocale($xpBonusTower, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_difficulty_twister']?>: [<font color="gold"><?=formatNumberByLocale($diffTwister, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_xp_bonus_twister']?>: [<font color="gold"><?=formatNumberByLocale($xpBonusTwister, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_difficulty_idols']?>: [<font color="gold"><?=formatNumberByLocale($diffIdols, $player['language'])?> %</font>]</p>
            <p><?=$locale['home-itm']['current_xp_bonus_idols']?>: [<font color="gold"><?=formatNumberByLocale($xpBonusIdols, $player['language'])?> %</font>]</p>
        </div>
    </div>
</div>

<div class="card">
    <h3 class="card-header text-center text-uppercase"><?=$locale['home-itm']['server_stats']?></h3>
    <div class="card-body">
        <div class="text-center" style="font-size:20px;">
            <p><?=$locale['home-itm']['online_15m']?>: [<font color="gold"><?=formatNumberByLocale($online15, $player['language'])?></font>]</p>
            <p><?=$locale['home-itm']['online_24h']?>: [<font color="gold"><?=formatNumberByLocale($online24, $player['language'])?></font>]</p>
            <p><?=$locale['home-itm']['registered']?>: [<font color="gold"><?=formatNumberByLocale($accs, $player['language'])?></font>]</p>
        </div>
    </div>
</div>
