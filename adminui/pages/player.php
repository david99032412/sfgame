<?php
if (!$index) exit;

$qry = $db->prepare('SELECT * FROM players WHERE ID = :ID');
$qry->execute([':ID' => ($_GET['id'] ?? 0)]);
$player = $qry->fetch(PDO::FETCH_ASSOC);

$blacksmith = empty($player['blacksmith']) ? [0, 0, 0, 0] : explode('/', $player['blacksmith']);

if (empty($player)) {
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=index.php\" />";
    exit;
}

if (isset($_POST['save'])) {
	$setPass = $player['password'];
	if (!empty($_POST['password'])) {
		$setPass = hashPassword($_POST['password']);
	}
	
	$blacksmith[0] = (int) ($_POST['bs'][0] ?? 0);
	$blacksmith[1] = (int) ($_POST['bs'][1] ?? 0);
	
	$qry = $db->prepare('UPDATE players SET 
		name = ?, 
		lvl = ?, 
		usysclass = ?, 
		banned = ?, 
		gframe = ?, 
		voucherToday = ?, 
		exp = ?, 
		honor = ?, 
		email = ?, 
		password = ?, 
		silver = ?, 
		mush = ?, 
		luckycoin = ?, 
		hourglass = ?, 
		food_black = ?,
		food_orange = ?,
		food_green = ?,
		food_red = ?,
		food_blue = ?,
		blacksmith = ?
		WHERE ID = ?
	');
	
	$qry->execute([
		$_POST['name'] ?? '',
		$_POST['lvl'] ?? 1,
		$_POST['usysclass'] ?? 1,
		$_POST['banned'] ?? 0,
		$_POST['gframe'] ?? 0,
		$_POST['voucherToday'] ?? 0,
		$_POST['exp'] ?? 0,
		$_POST['honor'] ?? 0,
		$_POST['email'] ?? '',
		$setPass ?? '',
		$_POST['silver'] ?? 0,
		$_POST['mush'] ?? 0,
		$_POST['luckycoin'] ?? 0,
		$_POST['hourglass'] ?? 0,
		$_POST['food_black'] ?? 0,
		$_POST['food_orange'] ?? 0,
		$_POST['food_green'] ?? 0,
		$_POST['food_red'] ?? 0,
		$_POST['food_blue'] ?? 0,
		implode('/', $blacksmith),
		$player['ID']
	]);
	
	if ($_POST['banned'] == 1 && $player['banned'] == 0) {
		$qry = $db->prepare('UPDATE players SET ssid = 0 WHERE ID = :ID');
		$qry->execute([':ID' => $player['ID']]);
	}
	
	if ($_POST['usysclass'] < 3 && $player['usysclass'] >= 3) {
		$qry = $db->prepare('UPDATE players SET acpSession = :acpSession WHERE ID = :ID');
		$qry->execute([':acpSession' => null, ':ID' => $player['ID']]);
	}
	
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=index.php?page=player&id={$player['ID']}\" />";
    exit;
}
?>

<div class="card">
	<h3 class="card-header text-center">Player Editor</h3>
		<div class="card-body">
			<h3>Dungeon Editor <a href="index.php?page=editdung&id=<?=$player['ID']?>" class="btn btn-primary">Click</a></h3>
			<form method="post">
				<div class="mb-3">
					<label class="form-label">Name:</label>
					<input type="text" class="form-control" name="name" value="<?= $player['name'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Level:</label>
					<input type="number" class="form-control" name="lvl" value="<?= $player['lvl'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Rank:</label>
					<select class="form-select" name="usysclass">
						<option value="1" <?=$player['usysclass']==1?'Selected':''?>>Player</option>
						<option value="2" <?=$player['usysclass']==2?'Selected':''?>>VIP</option>
						<option value="3" <?=$player['usysclass']==3?'Selected':''?>>Moderator</option>
						<option value="4" <?=$player['usysclass']==4?'Selected':''?>>Administrator</option>
					</select>
				</div>
				
				<div class="mb-3">
					<label class="form-label">Ban:</label>
					<select class="form-select" name="banned">
						<option value="1" <?=$player['banned']==1?'Selected':''?>>Yes</option>
						<option value="0" <?=$player['banned']==0?'Selected':''?>>No</option>
					</select>
				</div>
				
				<div class="mb-3">
					<label class="form-label">Gold Frame:</label>
					<select class="form-select" name="gframe">
						<option value="1" <?=$player['gframe']==1?'Selected':''?>>Yes</option>
						<option value="0" <?=$player['gframe']==0?'Selected':''?>>No</option>
					</select>
				</div>
				
				<div class="mb-3">
					<label class="form-label">Reedem voucher today:</label>
					<select class="form-select" name="voucherToday">
						<option value="1" <?=$player['voucherToday']==1?'Selected':''?>>Yes</option>
						<option value="0" <?=$player['voucherToday']==0?'Selected':''?>>No</option>
					</select>
				</div>
				
				<div class="mb-3">
					<label class="form-label">Experience:</label>
					<input type="number" class="form-control" name="exp" value="<?= $player['exp'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Honor:</label>
					<input type="number" class="form-control" name="honor" value="<?= $player['honor'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Email:</label>
					<input type="email" class="form-control" name="email" value="<?= $player['email'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Password:</label>
					<input type="password" class="form-control" name="password" value="" placeholder="Enter new password if needed">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Gold:</label>
					<input type="number" class="form-control" name="silver" value="<?= ($player['silver'] / 100) ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Mushrooms:</label>
					<input type="number" class="form-control" name="mush" value="<?= $player['mush'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Luckycoins:</label>
					<input type="number" class="form-control" name="luckycoin" value="<?= $player['luckycoin'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Hourglasses:</label>
					<input type="number" class="form-control" name="hourglass" value="<?= $player['hourglass'] ?>">
				</div>

				<div class="mb-3">
					<label class="form-label">Blacksmith Metal:</label>
					<input type="number" class="form-control" name="bs[0]" value="<?= $blacksmith[0] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Blacksmith Arcane:</label>
					<input type="number" class="form-control" name="bs[1]" value="<?= $blacksmith[1] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Blackberry of shadow:</label>
					<input type="number" class="form-control" name="food_black" value="<?= $player['food_black'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Lemon of the light:</label>
					<input type="number" class="form-control" name="food_orange" value="<?= $player['food_orange'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Apple of the earth:</label>
					<input type="number" class="form-control" name="food_green" value="<?= $player['food_green'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Strawberry of the fire:</label>
					<input type="number" class="form-control" name="food_red" value="<?= $player['food_red'] ?>">
				</div>
				
				<div class="mb-3">
					<label class="form-label">Plum of the water:</label>
					<input type="number" class="form-control" name="food_blue" value="<?= $player['food_blue'] ?>">
				</div>
				
				<button type="submit" name="save" class="btn btn-primary">Update Player</button>
			</form>
	</div>
</div>