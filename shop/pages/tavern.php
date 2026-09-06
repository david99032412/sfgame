<?php
    if (!$index) exit;
	
	if (isset($_POST['buy'])) {
		$item = (int) ($_POST['buy'] ?? 00);
		
		switch ($item) {
			case 1: case 2: case 3:
				$price = [9, 39, 99][$item - 1]; $beers = [1, 5, 10][$item - 1];

				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				if ($player['beers'] < $beers) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = $locale['tavern-itm'][$item]['error'];
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, beers = beers - :beers, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':beers' => $beers,
					':ID' => $player['ID']
				]);
				
				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
			
			case 4: case 5: case 6: case 7: case 8: case 9:
				$price = [10, 20, 39, 69, 169, 329][$item - 4]; $thirst = [20, 50, 100, 200, 500, 1000][$item - 4];

				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, thirst = thirst + :thirst, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':thirst' => $thirst * 60,
					':ID' => $player['ID']
				]);
				
				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
				
			case 10: case 11: case 12:
				$price = [1, 9, 89][$item - 10]; $luckycoin = [10, 100, 1000][$item - 10];
				
				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, luckycoin = luckycoin + :luckycoin, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':luckycoin' => $luckycoin,
					':ID' => $player['ID']
				]);

				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
				
			case 13: case 14: case 15:
				$price = [5, 49, 199][$item - 13]; $hourglass = [10, 100, 500][$item - 13];
				
				if ($player['mush'] < $price) {
					$message_type = 'error'; $message_title = $locale['error-title'];
					$message_text = sprintf($locale['missing-currency'], 
						formatNumberByLocale($price - $player['mush'], $player['language']),
						'mush.png'
					);
					break;
				}
				
				$qry = $db->prepare('UPDATE players SET mush = mush - :cost, hourglass = hourglass + :hourglass, renew = 1 WHERE ID = :ID');
				$qry->execute([
					':cost' => $price,
					':hourglass' => $hourglass,
					':ID' => $player['ID']
				]);

				$message_type = 'success'; $message_title = $locale['success-title'];
				$message_text = $locale['transaction-success'];
				break;
		}
	}
?>

<div class="card">
	<h3 class="card-header text-center text-uppercase"><?=$locale['menu-tabs']['tavern']?></h3>
    <div class="card-body">
        <form method="post" class="row">
            <div class="col-12 col-md-4 mt-2 mt-md-0">
                <div class="card">
                    <img src="img/barkeeper_portrait.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][1]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][1]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(9, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="1" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 mt-2 mt-md-0">
                <div class="card">
                    <img src="img/barkeeper_portrait.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][2]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][2]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(39, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="2" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 mt-2 mt-md-0">
                <div class="card">
                    <img src="img/barkeeper_portrait.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][3]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][3]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(69, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="3" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-taverne.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][4]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][4]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(10, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="4" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-taverne.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][5]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][5]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(20, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="5" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-taverne.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][6]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][6]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(39, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="6" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-taverne.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][7]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][7]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(69, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="7" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-taverne.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][8]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][8]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(169, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="8" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-taverne.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][9]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][9]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(329, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="9" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-gluecksrad.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][10]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][10]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(1, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="10" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-gluecksrad.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][11]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][11]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(9, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="11" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/sf_icon-gluecksrad.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][12]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][12]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(89, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="12" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/itm17_3_1.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][13]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][13]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(5, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="13" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/itm17_3_1.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][14]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][14]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(49, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="14" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
                <div class="card">
                    <img src="img/itm17_3_1.png" class="img-thumbnail mx-auto d-block mt-2" width="150px" height="150px">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?=$locale['tavern-itm'][15]['title']?></h5>
                        <p class="card-text"><?=$locale['tavern-itm'][15]['description']?></p>
                        <p class="card-text"><strong><?=$locale['labels']['price']?>:</strong> <?=formatNumberByLocale(199, $player['language'])?> <img src="img/mush.png"></p>
                        <button type="submit" name="buy" value="15" class="btn btn-primary"><?=$locale['labels']['buy']?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
