<?php
if (!$index) exit;
?>
<div class="card">
    <h3 class="card-header text-center text-uppercase">Find Player</h3>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label for="searchName" class="form-label">Player Name:</label>
                <input type="text" class="form-control" id="searchName" name="searchName" placeholder="Enter player name">
            </div>
            <div class="mb-3">
                <label for="sortOrder" class="form-label">Sort By:</label>
                <select class="form-select" id="sortOrder" name="sortOrder">
                    <option value="name">Name</option>
                    <option value="ID">ID</option>
                    <option value="lvl">Level</option>
                </select>
            </div>
            <button type="submit" name="find" class="btn btn-primary">Search</button>
        </form>
		<?php 
			if (isset($_POST['find'])) {
				$playername = $_POST['searchName'] ?? '';
				
				$allowedSorts = ['name', 'ID', 'lvl'];
				if (!in_array($sortBy, $allowedSorts)) {
					$sortBy = 'name';
				}

				if (!empty($playername)) {
					$sql = "SELECT ID, name, lvl FROM players WHERE name LIKE :playername";
					$params = [':playername' => "%$playername%"];
				} else {
					$sql = "SELECT ID, name, lvl FROM players ORDER BY $sortBy";
					$params = [];
				}

				$stmt = $db->prepare($sql);
				$stmt->execute($params);
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

				if ($results) {
					echo "<ul class='list-group mt-3'>";
					foreach ($results as $player) {
						echo "<li class='list-group-item'>";
						echo "Name: " . htmlspecialchars($player['name']) . ", Level: " . htmlspecialchars($player['lvl']);
						echo " - <a href='index.php?page=player&id=" . urlencode($player['ID']) . "'>Edit player</a>";
						echo "</li>";
					}
					echo "</ul>";
				} else {
					echo "<p class='mt-3'>No players found.</p>";
				}
			}
		?>
    </div>
</div>