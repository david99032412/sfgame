<?php require_once '../settings.php'; 
function formatNumberByLocale($number, $lang, $silver = false) {
    $formats = [
        'cs' => [' ', '.'], 'da' => ['.', ','], 'de' => ['.', ','], 'el' => ['.', ','],
        'en' => [',', '.'], 'es' => ['.', ','], 'fr' => [' ', ','], 'hu' => [' ', ','],
        'it' => ['.', ','], 'ja' => [',', '.'], 'ko' => [',', '.'], 'nl' => ['.', ','],
        'pl' => [' ', ','], 'pt' => ['.', ','], 'pt-br' => ['.', ','], 'ro' => ['.', ','],
        'ru' => [' ', ','], 'sk' => [' ', ','], 'sv' => [' ', ','], 'tr' => ['.', ','],
        'zh' => [',', '.']
    ];

    $thousandSep = $formats[$lang][0] ?? ',';
    $decimalSep = $formats[$lang][1] ?? '.';

    if ($silver && $number >= 1000) {
        $decimalPlaces = 0;
    } else {
        $decimalPlaces = (int) ($number != (int) $number); 
    }

    return number_format($number, $decimalPlaces, $decimalSep, $thousandSep);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$gameName?> | Item Shop</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
	<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<link href="//cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5.0.16/dark.min.css " rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
</head>
<style>
body::before {
	content: '';
	position: fixed;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	background: url('img/background.jpg') no-repeat center center;
	background-size: 100% 100%;
	opacity: 0.5;
	filter: blur(9px);
	z-index: -1;
}
	
.special-thumb {
	width: 100%;
	height: 0;
	padding-top: 100%;
	position: relative;
	background-size: cover;
	background-position: center;
	background-image: url('img/special_background.png');
}

.special-thumb img {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
}
</style>

<body>
    <div class="container-flex">
    <?php 
        $qry = $db->prepare('SELECT players.*, fortress.wood, fortress.stone, underworld.soul, players.blacksmith FROM players LEFT JOIN fortress ON fortress.owner = players.ID LEFT JOIN underworld ON underworld.owner = players.ID WHERE players.ssid = :ssid');
        $qry->execute([':ssid' => $_COOKIE['ssid']]);
        $player = $qry->fetch(PDO::FETCH_ASSOC);

		list ($metal, $arcane) = (empty($player['blacksmith']) ? [0, 0] : explode('/', $player['blacksmith']));
		
		$language = $player['language'] ?? 'en';
		$localeFile = file_exists("lang/{$language}.json") ? 
			file_get_contents("lang/{$language}.json") : 
			file_get_contents("lang/en.json");
			
		$locale = json_decode($localeFile, true);

        if (empty($player)) {
            echo '<div class="alert alert-warning" role="alert">'.$locale['errors']['no-login'].'</div>';
        } elseif ($player['banned'] == 1) {
            echo '<div class="alert alert-danger" role="alert">'.$locale['errors']['perm-ban'].'</div>';
        } else {
			
		$page = $_GET['page'] ?? 'home';
		$index = true;
    ?>
	
	<div class="row mx-5 mt-3">
		<div class="col-lg-12">
			<div class="card">
				<div class="card-body">
					<span style="white-space: nowrap;" class="text-uppercase">
						<?=$player['name']?>
					</span> |
					<span style="white-space: nowrap;">
						<img src="img/gold.png" style="width: 25px; height: 25px;"> <?=formatNumberByLocale(($player['silver'] / 100), $player['language'], true)?> 
					</span>
					<span style="white-space: nowrap;">
						<img src="img/mush.png" style="width: 25px; height: 25px;"> <?=formatNumberByLocale($player['mush'], $player['language'])?>
					</span>
					<span style="white-space: nowrap;">
						<img src="img/wood.png" style="width: 25px; height: 25px;"> <?=formatNumberByLocale($player['wood'], $player['language'])?>
					</span>
					<span style="white-space: nowrap;">
						<img src="img/stone.png" style="width: 25px; height: 25px;"> <?=formatNumberByLocale($player['stone'], $player['language'])?>
					</span>
					<span style="white-space: nowrap;">
						<img src="img/soul.png" style="width: 25px; height: 25px;"> <?=formatNumberByLocale($player['soul'] ?? 00, $player['language'])?>
					</span>
					<span style="white-space: nowrap;">
						<img src="img/metal.png" style="width: 25px; height: 25px;"> <?=formatNumberByLocale($metal, $player['language'])?>
					</span>
					<span style="white-space: nowrap;">
						<img src="img/arcane.png" style="width: 25px; height: 25px;"> <?=formatNumberByLocale($arcane, $player['language'])?>
					</span>
				</div>
			</div>
		</div>
	
		<div class="col-lg-2 mt-2">
			<div class="card">
				<div class="card-body">
					<a href="index.php?page=home" class="btn btn-primary w-100"><?=$locale['menu-tabs']['home']?></a>
					<a href="index.php?page=tavern" class="btn btn-primary w-100 mt-2"><?=$locale['menu-tabs']['tavern']?></a>
					<a href="index.php?page=special" class="btn btn-primary w-100 mt-2"><?=$locale['menu-tabs']['special']?></a>
					<a href="index.php?page=dungeon" class="btn btn-primary w-100 mt-2"><?=$locale['menu-tabs']['dungeon']?></a>
					<a href="index.php?page=stats" class="btn btn-primary w-100 mt-2"><?=$locale['menu-tabs']['stats']?></a>
					<a href="index.php?page=voucher" class="btn btn-primary w-100 mt-2"><?=$locale['menu-tabs']['voucher']?></a>
				</div>
			</div>
		</div>
		
		<!-- TABS -->
		<div class="col-lg-10 mt-2">
		<?php $allowed_pages = ['home', 'dungeon', 'special', 'stats', 'tavern', 'voucher', 'editdung', 'fortress', 'masspm', 'player', 'searchfortress', 'searchplayer', 'searchunderworld', 'underworld', 'vouchers', 'logout'];
if(!in_array($page, $allowed_pages)) $page = 'home';
require_once "pages/{$page}.php"; ?>
		</div>
		
		<?php } ?>
	</div>
	

    </div>
    <script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
	
	<?php if (isset($message_title, $message_text, $message_type)) : ?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		Swal.fire({
			title: '<?= $message_title ?>',
			html: '<?= $message_text ?>',
			icon: '<?= $message_type ?>',
			confirmButtonText: 'OK'
		}).then((result) => {
			if (result.value) {
				<?php if (isset($_GET['page'])): ?>
					window.location.href = 'index.php?page=<?= $_GET['page'] ?>';
				<?php else: ?>
					window.location.href = 'index.php';
				<?php endif; ?>
			}
		});
	});
	</script>
	<?php endif; ?>
</body>
</html>