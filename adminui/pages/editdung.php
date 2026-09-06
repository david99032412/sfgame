<?php
if (!$index) exit;

$qry = $db->prepare('SELECT ID, name, lightdungeons, shadowdungeons, tower, twister, idols FROM players WHERE ID = :ID');
$qry->execute([':ID' => ($_GET['id'] ?? 0)]);
$player = $qry->fetch(PDO::FETCH_ASSOC);

$lightDungs = empty($player['lightdungeons']) ? [] : json_decode($player['lightdungeons'], true);
if (count($lightDungs) < $maxDungeonsLight + 1) {
	$lightDungs = array_pad($lightDungs, $maxDungeonsLight + 1, 0);
}

$shadowDungs = empty($player['shadowdungeons']) ? [] : json_decode($player['shadowdungeons'], true);
if (count($shadowDungs) < $maxDungeonsShadow + 1) {
	$shadowDungs = array_pad($shadowDungs, $maxDungeonsShadow + 1, 0);
}

if (empty($player)) {
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=index.php\" />";
    exit;
}

if (isset($_POST['save'])) {
    $lightDungs = array_map(function($value) {
        return intval($value);
    }, $_POST['lightDungs']);
	
    $shadowDungs = array_map(function($value) {
        return intval($value);
    }, $_POST['shadowDungs']);
	
	$player['tower'] = (int) ($_POST['tower'] ?? 0);
	$player['twister'] = (int) ($_POST['twister'] ?? 0);
	$player['idols'] = (int) ($_POST['idols'] ?? 0);
	
	$qry = $db->prepare('UPDATE players SET lightdungeons = ?, shadowdungeons = ?, tower = ?, twister = ?, idols = ? WHERE ID = ?');
	$qry->execute([
		json_encode(array_values($lightDungs)),
		json_encode(array_values($shadowDungs)),
		$player['twister'],
		$player['twister'],
		$player['idols'],
		$player['ID']
	]);
}
?>
<div class="card">
	<h3 class="card-header text-center">Edit dungeons <?=$player['name']?></h3>
	<div class="card-body text-center">
		
		<form method="post">
            
                <div class="row">
                    <div class="col-lg-6">
						<h3>Light World</h3><hr/>
						<?php for ($index = 1; $index <= $maxDungeonsLight; $index++) : ?>
							<div class="mb-3">
								<label class="form-label"><?=DungeonName($index)?>:</label>
								<select name="lightDungs[<?=$index?>]" class="form-select">
									<?php for ($index2 = 0; $index2 <= 10; $index2++) : ?>
										<option value="<?=$index2?>"<?=($lightDungs[$index] == $index2) ? ' selected' : ''?>><?=DungeonLevel($index2)?></option>
									<?php endfor; ?>
								</select>
							</div>
						<?php endfor; ?>
                    </div>
					
                    <div class="col-lg-6">
						<h3>Shadow World</h3><hr/>
						<?php for ($index = 1; $index <= $maxDungeonsShadow; $index++) : ?>
							<div class="mb-3">
								<label class="form-label"><?=DungeonName($index)?>:</label>
								<select name="shadowDungs[<?=$index?>]" class="form-select">
									<?php for ($index2 = 0; $index2 <= 10; $index2++) : ?>
										<option value="<?=$index2?>"<?=($shadowDungs[$index] == $index2) ? ' selected' : ''?>><?=DungeonLevel($index2)?></option>
									<?php endfor; ?>
								</select>
							</div>
						<?php endfor; ?>
                    </div>
					
					<h3>Other</h3><hr/>
					
					<div class="col-lg-6">
						<div class="mb-3">
							<label class="form-label">Tower:</label>
							<input class="form-control" name="tower" type="number" value="<?=$player['tower']?>" min="-1" max="100">
						</div>
					</div>
					
					<div class="col-lg-6">
						<div class="mb-3">
							<label class="form-label">Twister:</label>
							<input class="form-control" name="twister" type="number" value="<?=$player['twister']?>" min="0" max="1000">
						</div>
					</div>
					
					<div class="col-lg-6">
						<div class="mb-3">
							<label class="form-label">Idols:</label>
							<input class="form-control" name="idols" type="number" value="<?=$player['idols']?>" min="0" max="21">
						</div>
					</div>
					<hr/>
					<div class="col-lg-12">
						<button type="submit" name="save" class="btn btn-primary">Save</button>
					</div>
                </div>
            
		</form>
	</div>
</div>