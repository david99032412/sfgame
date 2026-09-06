<?php
    if (!$index) exit;
	
	if (isset($_POST['upgrade'])) {
		$statsUpgrade = $_POST['stats'] ?? [];
		
		$upgraded = [];
		
		$totalCost = 0;
		$totalSilver = $player['silver'];
		foreach (['str', 'dex', 'intel', 'wit', 'luck'] as $statName) {
			if (!isset($statsUpgrade[$statName]) || $statsUpgrade[$statName] < 1) continue;
			if ($player[$statName] <= 3152) continue;
			if (($price = (10000000 * $statsUpgrade[$statName]) * 100) > $totalSilver) continue;
			
			$upgraded[$statName] = $statsUpgrade[$statName];
			$totalCost += $price;
			$totalSilver -= $price;
		}
		
		if (empty($upgraded)) {
			$message_type = 'error'; $message_title = $locale['error-title'];
			$message_text = sprintf($locale['stats-itm']['error'], formatNumberByLocale(10000000, $player['language'], true));
		} else {
			$stats = [];
			$qryArgs = $qryParams = [];
			
			foreach ($upgraded as $stat => $upgrades) {
				$stats[] = sprintf($locale['stats-itm']['stats-txt'], $locale['stats-itm'][$stat], formatNumberByLocale($upgrades, $player['language']));
				$qryArgs[] = "$stat = $stat + :$stat";
				$qryParams[":$stat"] = $upgrades;
			}
			
			$qryArgs[] = 'silver = silver - :cost';
			$qryParams[':cost'] = $totalCost;
			$qryParams[':ID'] = $player['ID'];
			
			$qry = $db->prepare('UPDATE players SET ' . join(',', $qryArgs) . ', renew = 1 WHERE ID = :ID');
			$qry->execute($qryParams);
			
			$message_type = 'success'; $message_title = $locale['success-title'];
			$message_text = sprintf($locale['stats-itm']['success'], join('<br/>', $stats), formatNumberByLocale(($totalCost / 100), $player['language'], true));
		}
	}
?>

<div class="card">
    <h3 class="card-header text-center text-uppercase"><?=$locale['menu-tabs']['stats']?></h3>
    <div class="card-body">
		<p class="text-center text-muted"><?=sprintf($locale['stats-itm']['description'], formatNumberByLocale(10000000, $player['language'], true), 'gold.png')?></p>
		<form method="post" class="row">
            <div class="col-12 col-md-4 mt-2">
				<div class="card">
					<div class="card-body text-center">
						<label for="str"><?=$locale['stats-itm']['str']?></label>
						<input type="number" id="str" name="stats[str]" class="form-control" min="0" value="0">
					</div>
				</div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
				<div class="card">
					<div class="card-body text-center">
						<label for="dex"><?=$locale['stats-itm']['dex']?></label>
						<input type="number" id="dex" name="stats[dex]" class="form-control" min="0" value="0">
					</div>
				</div>
            </div>
			
            <div class="col-12 col-md-4 mt-2">
				<div class="card">
					<div class="card-body text-center">
						<label for="intel"><?=$locale['stats-itm']['intel']?></label>
						<input type="number" id="intel" name="stats[intel]" class="form-control" min="0" value="0">
					</div>
				</div>
            </div>
			
			<div class="col-12 col-md-4 mt-2">
				<div class="card">
					<div class="card-body text-center">
						<label for="wit"><?=$locale['stats-itm']['wit']?></label>
						<input type="number" id="wit" name="stats[wit]" class="form-control" min="0" value="0">
					</div>
				</div>
            </div>
			
			<div class="col-12 col-md-4 mt-2">
				<div class="card">
					<div class="card-body text-center">
						<label for="luck"><?=$locale['stats-itm']['luck']?></label>
						<input type="number" id="luck" name="stats[luck]" class="form-control" min="0" value="0">
					</div>
				</div>
			</div>
			
            <div class="col-12 mt-4">
				<div class="card">
					<div class="card-body text-center">
						<p><?=$locale['stats-itm']['total-price']?>: <span id="totalCost">0</span> <img src="img/gold.png" width="25px" height="25px"></p>
						<button type="submit" name="upgrade" class="btn btn-primary"><?=$locale['labels']['upgrade']?></button>
					</div>
				</div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('change', function() {
        var totalCost = 0;
        document.querySelectorAll('input[type="number"]').forEach(input => {
            totalCost += input.value * 10000000;
        });
        document.getElementById('totalCost').textContent = formatNumberByLocale(totalCost, '<?=$player['language']?>');
    });
});

function formatNumberByLocale(number, lang) {
    const locales = {
        'cs': 'cs-CZ', 'da': 'da-DK', 'de': 'de-DE', 'el': 'el-GR',
        'en': 'en-US', 'es': 'es-ES', 'fr': 'fr-FR', 'hu': 'hu-HU',
        'it': 'it-IT', 'ja': 'ja-JP', 'ko': 'ko-KR', 'nl': 'nl-NL',
        'pl': 'pl-PL', 'pt': 'pt-PT', 'pt-br': 'pt-BR', 'ro': 'ro-RO',
        'ru': 'ru-RU', 'sk': 'sk-SK', 'sv': 'sv-SE', 'tr': 'tr-TR',
        'zh': 'zh-CN'
    };

    if (!locales.hasOwnProperty(lang)) {
        return false;
    }

    const locale = locales[lang];
    const formatter = new Intl.NumberFormat(locale, {
        style: 'decimal'
    });

    return formatter.format(number);
}
</script>
