<?php
if (!$index) exit;

$qry = $db->prepare('SELECT players.name, underworld.* FROM underworld JOIN players ON players.ID = underworld.owner WHERE underworld.ID = :ID');
$qry->execute([':ID' => ($_GET['id'] ?? 0)]);
$underworld = $qry->fetch(PDO::FETCH_ASSOC);

if (empty($underworld)) {
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=index.php\" />";
    exit;
}

if (isset($_POST['save'])) {
	$args = $params = [];
	
	foreach ($_POST as $key => $value) {
		if (!isset($underworld[$key])) continue;
		$underworld[$key] = $value;
		$args[] = "{$key} = ?";
		$params[] = $value;
	}
	
	if (!empty($args)) {
		$params[] = $underworld['owner'];
		$qry = $db->prepare('UPDATE underworld SET '.join(',', $args).' WHERE owner = ?');
		$qry->execute($params);
	}
}
?>

<div class="card">
	<h3 class="card-header text-center">Edit underworld <?=$underworld['name']?></h3>
		<div class="card-body">
			<form method="post">
				<div class="mb-3">
					<label class="form-label">Soul:</label>
					<input type="text" class="form-control" name="soul" value="<?= $underworld['soul'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Honor:</label>
					<input type="text" class="form-control" name="uwhonor" value="<?= $underworld['uwhonor'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Heart of darkness:</label>
					<input type="text" class="form-control" name="heart" value="<?= $underworld['heart'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Underworld Gate:</label>
					<input type="text" class="form-control" name="gate" value="<?= $underworld['gate'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Torture Chamber:</label>
					<input type="text" class="form-control" name="torture" value="<?= $underworld['torture'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Keeper:</label>
					<input type="text" class="form-control" name="keeper" value="<?= $underworld['keeper'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Soul extractor:</label>
					<input type="text" class="form-control" name="extractor" value="<?= $underworld['extractor'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Goblin pit:</label>
					<input type="text" class="form-control" name="gold" value="<?= $underworld['gold'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Gladiator trainer:</label>
					<input type="text" class="form-control" name="gladiator" value="<?= $underworld['gladiator'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Troll block:</label>
					<input type="text" class="form-control" name="troll" value="<?= $underworld['troll'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Gold pit:</label>
					<input type="text" class="form-control" name="gold" value="<?= $underworld['gold'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Adventuromatic:</label>
					<input type="text" class="form-control" name="time" value="<?= $underworld['time'] ?>">
				</div>
				<button type="submit" name="save" class="btn btn-primary">Update Underworld</button>
			</form>
	</div>
</div>