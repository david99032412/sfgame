<?php
    if (!$index) exit;
	$specials = [10, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 16, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 17, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 2, 200, 201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 40, 41, 42, 44, 45, 46, 49, 53, 54, 55, 56, 57, 58, 59, 6, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 7, 70, 71, 72, 73, 74, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99];
	
	if (isset($_POST['buy'])) {
		$item = (int) ($_POST['buy'] ?? 0);
		$price = 1000;
		
		do {
			if (!in_array($item, $specials)) {
				$message_type = 'error'; $message_title = $locale['error-title'];
				$message_text = $locale['special-itm']['error2'];
				break;
			}
			
			if ($player['portrait'] == $item) {
				$message_type = 'error'; $message_title = $locale['error-title'];
				$message_text = $locale['special-itm']['error'];
				break;
			}
			
			if ($player['mush'] < $price) {
				$message_type = 'error'; $message_title = $locale['error-title'];
				$message_text = sprintf($locale['missing-currency'], 
					formatNumberByLocale($price - $player['mush'], $player['language']),
					'mush.png'
				);
				break;
			}
			
			$qry = $db->prepare('UPDATE players SET mush = mush - :cost, portrait = :portrait, renew = 1 WHERE ID = :ID');
			$qry->execute([
				':cost' => $price,
				':portrait' => $item,
				':ID' => $player['ID']
			]);

			$message_type = 'success'; $message_title = $locale['success-title'];
			$message_text = $locale['transaction-success'];
			break;
		} while (0);
	}
?>

<div class="card">
    <h3 class="card-header text-center text-uppercase"><?=$locale['menu-tabs']['special']?></h3>
    <div class="card-body">
        <form method="post" class="row">
            <?php foreach ($specials as $count => $special) : ?>
            <div class="col-12 col-md-4 <?=($count >= 3 ? 'mt-2' : 'mt-2 mt-md-0')?>">
                <div class="card">
                    <div class="special-thumb">
                        <img src="img/special/special<?=$special?>.png" class="card-img-top">
                    </div>
                    <div class="card-body text-center">
                        <button type="submit" name="buy" value="<?=$special?>" class="btn btn-primary w-100"><?=$locale['labels']['buy']?> (<?=formatNumberByLocale(1000, $player['language'])?> <img src="img/mush.png">)</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </form>
    </div>
</div>
