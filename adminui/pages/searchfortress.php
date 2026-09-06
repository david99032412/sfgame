<?php
if (!$index) exit;
?>
<div class="card">
    <h3 class="card-header text-center text-uppercase">Find Fortress</h3>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label for="searchName" class="form-label">Player Name:</label>
                <input type="text" class="form-control" id="searchName" name="searchName" placeholder="Enter player name">
            </div>
            <div class="mb-3">
                <label for="sortOrder" class="form-label">Sort By:</label>
                <select class="form-select" id="sortOrder" name="sortOrder">
                    <option value="name">Player Name</option>
                    <option value="fortressID">Fortress ID</option>
                    <option value="forthonor">Fortress Honor</option>
                </select>
            </div>
            <button type="submit" name="find" class="btn btn-primary">Search</button>
        </form>
		<?php 
            if (isset($_POST['find'])) {
                $playername = $_POST['searchName'] ?? '';
                $sortBy = $_POST['sortOrder'] ?? 'forthonor';

                $allowedSorts = ['name', 'fortressID', 'forthonor'];
                if (!in_array($sortBy, $allowedSorts)) {
                    $sortBy = 'forthonor';
                }

                if (!empty($playername)) {
                    $sql = "SELECT fortress.fortressID, players.name, fortress.forthonor FROM fortress JOIN players ON players.ID = fortress.owner WHERE players.name LIKE :playername ORDER BY $sortBy";
                    $params = [':playername' => "%$playername%"];
                } else {
                    $sql = "SELECT fortress.fortressID, players.name, fortress.forthonor FROM fortress JOIN players ON players.ID = fortress.owner ORDER BY $sortBy";
                    $params = [];
                }

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($results) {
                    echo "<ul class='list-group mt-3'>";
                    foreach ($results as $fortress) {
                        echo "<li class='list-group-item'>";
                        echo "Owner: " . htmlspecialchars($fortress['name']) . ", Fortress Honor: " . htmlspecialchars($fortress['forthonor']);
                        echo " - <a href='index.php?page=fortress&id=" . urlencode($fortress['fortressID']) . "'>Edit Fortress</a>";
                        echo "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='mt-3'>No fortresses found.</p>";
                }
            }
        ?>
    </div>
</div>