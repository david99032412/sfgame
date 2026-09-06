<?php
    if (!$index) exit;
	
	if (isset($_POST['buy'])) {
		$item = (int) ($_POST['buy'] ?? 0);
		
		switch ($item) {
			case 1:
				$price = 1250;
				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				$lightDungs = empty($player['lightdungeons']) ? [] : json_decode($player['lightdungeons'], true);
				if (count($lightDungs) < $maxDungeonsLight + 1) { array_pad($lightDungs, $maxDungeonsLight + 1, 0); }

				$allCompleted = true;
				for ($index = 10; $index <= $maxDungeonsLight; $index++) {
					if ($lightDungs[$index] != 10) {
						$allCompleted = false;
						break;
					}
					$lightDungs[$index] = 0;
				}
				
				if (!$allCompleted) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = $locale['dungeon-itm'][1]['error'];
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, lightdungeons = :lightdungeons, dungeonLightResetCount = dungeonLightResetCount + 1, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':lightdungeons' => json_encode(array_values($lightDungs)),
					':ID' => $player['ID']
				]);

				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
				
			case 2:
				$price = 1250;
				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				$shadowDungs = empty($player['shadowdungeons']) ? [] : json_decode($player['shadowdungeons'], true);
				if (count($shadowDungs) < $maxDungeonsShadow + 1) { array_pad($shadowDungs, $maxDungeonsShadow + 1, 0); }
				
				$allCompleted = true;
				for ($index = 10; $index <= $maxDungeonsShadow; $index++) {
					if ($shadowDungs[$index] != 10) {
						$allCompleted = false;
						break;
					}
					$shadowDungs[$index] = 0;
				}
				
				if (!$allCompleted) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = $locale['dungeon-itm'][2]['error'];
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, shadowdungeons = :shadowdungeons, dungeonShadowResetCount = dungeonShadowResetCount + 1, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':shadowdungeons' => json_encode(array_values($shadowDungs)),
					':ID' => $player['ID']
				]);

				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
				
			case 3:
				$price = 1750;
				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				if ($player['tower'] < 100) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = $locale['dungeon-itm'][3]['error'];
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, tower = 0, towerResetCount = towerResetCount + 1, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':ID' => $player['ID']
				]);

				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
				
			case 4:
				$price = 1750;
				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				if ($player['twister'] < 1000) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = $locale['dungeon-itm'][4]['error'];
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, twister = 0, twisterResetCount = twisterResetCount + 1, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':ID' => $player['ID']
				]);

				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
				
			case 5:
				$price = 1000;
				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				if ($player['idols'] < 21) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = $locale['dungeon-itm'][5]['error'];
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, idols = 0, idolsResetCount = idolsResetCount + 1, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':ID' => $player['ID']
				]);

				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
		}
		
	}
?>

<div class="card">
    <h3 class="card-header text-center text-uppercase"><?=$locale['menu-tabs']['dungeon']?></h3>
    <div class="card-body">
        <p class="text-center text-muted"><?=$locale['dungeon-itm']['description']?></p>
        <form method="post" class="row">
            <div class="col-12 col-md-4 mt-2 mt-md-0">
                <div class="card">
                    <img src="img/dung.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['dungeon-itm'][1]['title']?></h5>
                        <p class="card-text"><?=$locale['dungeon-itm'][1]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(1250, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="1" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mt-2 mt-md-0">
                <div class="card">
                    <img src="img/dung.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['dungeon-itm'][2]['title']?></h5>
                        <p class="card-text"><?=$locale['dungeon-itm'][2]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(1250, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="2" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mt-2 mt-md-0">
                <div class="card">
                    <img src="img/dung.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['dungeon-itm'][3]['title']?></h5>
                        <p class="card-text"><?=$locale['dungeon-itm'][3]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(1750, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="3" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/dung.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['dungeon-itm'][4]['title']?></h5>
                        <p class="card-text"><?=$locale['dungeon-itm'][4]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(1750, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="4" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/dung.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['dungeon-itm'][5]['title']?></h5>
                        <p class="card-text"><?=$locale['dungeon-itm'][5]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(1000, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="5" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>