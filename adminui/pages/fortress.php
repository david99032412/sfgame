<?php
if (!$index) exit;

$qry = $db->prepare('SELECT players.name, fortress.* FROM fortress JOIN players ON players.ID = fortress.owner WHERE fortress.fortressID = :ID');
$qry->execute([':ID' => ($_GET['id'] ?? 0)]);
$fortress = $qry->fetch(PDO::FETCH_ASSOC);

if (empty($fortress)) {
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=index.php\" />";
    exit;
}

if (isset($_POST['save'])) {
	$args = $params = [];
	
	foreach ($_POST as $key => $value) {
		if (!isset($fortress[$key])) continue;
		$fortress[$key] = $value;
		$args[] = "{$key} = ?";
		$params[] = $value;
	}
	
	if (!empty($args)) {
		$params[] = $fortress['fortressID'];
		$qry = $db->prepare('UPDATE fortress SET '.join(',', $args).' WHERE fortressID = ?');
		$qry->execute($params);
	}
}
?>

<div class="card">
	<h3 class="card-header text-center">Edit fortress <?=$fortress['name']?></h3>
		<div class="card-body">
			<form method="post">
				<div class="mb-3">
					<label class="form-label">Wood:</label>
					<input type="text" class="form-control" name="wood" value="<?= $fortress['wood'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Stone:</label>
					<input type="text" class="form-control" name="stone" value="<?= $fortress['stone'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Honor:</label>
					<input type="text" class="form-control" name="forthonor" value="<?= $fortress['forthonor'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Fortress:</label>
					<input type="text" class="form-control" name="b0" value="<?= $fortress['b0'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Laborers' quarters:</label>
					<input type="text" class="form-control" name="b1" value="<?= $fortress['b1'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Woodcutter's hut:</label>
					<input type="text" class="form-control" name="b2" value="<?= $fortress['b2'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Quarry:</label>
					<input type="text" class="form-control" name="b3" value="<?= $fortress['b3'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Gem mine:</label>
					<input type="text" class="form-control" name="b4" value="<?= $fortress['b4'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Academy:</label>
					<input type="text" class="form-control" name="b5" value="<?= $fortress['b5'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Archery guild:</label>
					<input type="text" class="form-control" name="b6" value="<?= $fortress['b6'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Barracks:</label>
					<input type="text" class="form-control" name="b7" value="<?= $fortress['b7'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Mages' tower:</label>
					<input type="text" class="form-control" name="b8" value="<?= $fortress['b8'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Treasury:</label>
					<input type="text" class="form-control" name="b9" value="<?= $fortress['b9'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Smithy:</label>
					<input type="text" class="form-control" name="b10" value="<?= $fortress['b10'] ?>">
				</div>
				<div class="mb-3">
					<label class="form-label">Fortificatrions:</label>
					<input type="text" class="form-control" name="b11" value="<?= $fortress['b11'] ?>">
				</div>
				<button type="submit" name="save" class="btn btn-primary">Update Fortress</button>
			</form>
	</div>
</div>