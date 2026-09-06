<?php
if (!$index) exit;
?>
<div class="card">
    <h3 class="card-header text-center text-uppercase">Find Underworld</h3>
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
                    <option value="ID">Underworld ID</option>
                    <option value="uwhonor">Underworld Honor</option>
                </select>
            </div>
            <button type="submit" name="find" class="btn btn-primary">Search</button>
        </form>
		<?php 
            if (isset($_POST['find'])) {
                $playername = $_POST['searchName'] ?? '';
                $sortBy = $_POST['sortOrder'] ?? 'uwhonor';

                $allowedSorts = ['name', 'ID', 'uwhonor'];
                if (!in_array($sortBy, $allowedSorts)) {
                    $sortBy = 'uwhonor';
                }

                if (!empty($playername)) {
                    $sql = "SELECT underworld.ID, players.name, underworld.uwhonor FROM underworld JOIN players ON players.ID = underworld.owner WHERE players.name LIKE :playername ORDER BY $sortBy";
                    $params = [':playername' => "%$playername%"];
                } else {
                    $sql = "SELECT underworld.ID, players.name, underworld.uwhonor FROM underworld JOIN players ON players.ID = underworld.owner ORDER BY $sortBy";
                    $params = [];
                }

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($results) {
                    echo "<ul class='list-group mt-3'>";
                    foreach ($results as $Underworld) {
                        echo "<li class='list-group-item'>";
                        echo "Owner: " . htmlspecialchars($Underworld['name']) . ", Underworld Honor: " . htmlspecialchars($Underworld['uwhonor']);
                        echo " - <a href='index.php?page=underworld&id=" . urlencode($Underworld['ID']) . "'>Edit Underworld</a>";
                        echo "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='mt-3'>No Underworldes found.</p>";
                }
            }
        ?>
    </div>
</div>