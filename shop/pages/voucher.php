<?php
if (!$index) exit;
error_reporting(E_ALL);
if (isset($_POST['reedem'])) {
    $voucherCode = $_POST['voucherCode'];
    
    $stmt = $db->prepare("SELECT ID, code, type, uses FROM vouchers WHERE code = ?");
    $stmt->execute([$voucherCode]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($voucher) {
        if ($player['voucherToday'] == 0) {
            $rewards = json_decode($voucher['type'], true);
            $bs = (empty($player['blacksmith'])) ? [0,0,0,0] : array_map('intval', explode("/", $player["blacksmith"]));
			
            foreach ($rewards as $typeId => $quantity) {
				$quantity = intval($quantity);
                switch ($typeId) {
                    case 1: // Gold
                        $stmt = $db->prepare("UPDATE players SET silver = silver + ? WHERE ID = ?");
                        $stmt->execute([$quantity * 100, $player['ID']]);
                        break;
                    case 2: // Mushroom
                        $stmt = $db->prepare("UPDATE players SET mush = mush + ? WHERE ID = ?");
                        $stmt->execute([$quantity, $player['ID']]);
                        break;
                    case 3: // Wood
                        $stmt = $db->prepare("UPDATE fortress SET wood = wood + ? WHERE owner = ?");
                        $stmt->execute([$quantity, $player['ID']]);
                        break;
                    case 4: // Stone
                        $stmt = $db->prepare("UPDATE fortress SET stone = stone + ? WHERE owner = ?");
                        $stmt->execute([$quantity, $player['ID']]);
                        break;
                    case 5: // Soul
                        $stmt = $db->prepare("UPDATE underworld SET soul = soul + ? WHERE owner = ?");
                        $stmt->execute([$quantity, $player['ID']]);
                        break;
					case 6: // Metal
						$bs[0] += $quantity;
						break;
					case 7: // Arcane
						$bs[1] += $quantity;
						break;
                }
            }

            if ($voucher['uses'] > 1) {
                $stmt = $db->prepare("UPDATE vouchers SET uses = uses - 1 WHERE ID = ?");
                $stmt->execute([$voucher['ID']]);
            } else {
                $stmt = $db->prepare("DELETE FROM vouchers WHERE ID = ?");
                $stmt->execute([$voucher['ID']]);
            }
			
            $stmt = $db->prepare("UPDATE players SET voucherToday = 1, blacksmith = ? WHERE ID = ?");
            $stmt->execute([implode('/', $bs), $player['ID']]);
			
			$message_type = 'success'; $message_title = $locale['success-title'];
			$message_text = $locale['vouchers-itm']['success'];
        } else {
			$message_type = 'error'; $message_title = $locale['error-title'];
			$message_text = $locale['vouchers-itm']['error1'];
        }
    } else {
		$message_type = 'error'; $message_title = $locale['error-title'];
		$message_text = $locale['vouchers-itm']['error2'];
    }
}
?>
<div class="card">
    <h3 class="card-header text-center text-uppercase"><?=$locale['menu-tabs']['voucher']?></h3>
    <div class="card-body">
        <form method="post" class="row">
            <div class="col-10">
                <input type="text" name="voucherCode" placeholder="<?=$locale['vouchers-itm']['enter-code']?>" class="form-control" required autocomplete="off">
            </div>
			<div class="col-2">
				<button type="submit" name="reedem" class="btn btn-primary mb-2"><?=$locale['vouchers-itm']['reedem']?></button>
			</div>
        </form>
    </div>
</div>