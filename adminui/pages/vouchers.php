<?php
if (!$index) exit;

if (isset($_POST['create']) && !empty($_POST['resources'])) {
    $allowedResources = [1 => 'Gold', 2 => 'Mushroom', 3 => 'Wood', 4 => 'Stone', 5 => 'Soul', 6 => 'Metal', 7 => 'Arcane'];
    $resourceQuantities = array_intersect_key($_POST['resources'], $allowedResources);

    $totalQuantity = array_sum($resourceQuantities);
    if ($totalQuantity <= 0) {
        list ($message_type, $message_title, $message_text) = ['error', 'Error', "Error: No quantities provided. At least one resource must be greater than 0."];
    } else {
        $resourcesJson = json_encode($resourceQuantities);
        $uses = $_POST['uses'];
        $code = GenerateCode(15);

        $stmt = $db->prepare("INSERT INTO vouchers (code, type, uses) VALUES (?, ?, ?)");
        $stmt->execute([$code, $resourcesJson, $uses]);
        list ($message_type, $message_title, $message_text) = ['success', 'Success', "Voucher created successfully with code: $code"];
    }
}

if (isset($_POST['deleteAll'])) {
    $db->exec("DELETE FROM vouchers");
	list ($message_type, $message_title, $message_text) = ['success', 'Success', "All vouchers deleted successfully"];
}

if (isset($_POST['delete'])) {
    $voucherId = $_POST['delete'];
    $stmt = $db->prepare("DELETE FROM vouchers WHERE ID = ?");
    $stmt->execute([$voucherId]);
	list ($message_type, $message_title, $message_text) = ['success', 'Success', "Voucher deleted successfully"];
}
?>

<div class="card">
    <div class="card-header text-center text-uppercase">
        <form method="post">
            <button type="submit" name="deleteAll" class="btn btn-danger">Delete All Vouchers</button>
        </form>
    </div>
    <h3 class="card-header text-center text-uppercase">Create Voucher</h3>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?php
            $resources = ['Gold', 'Mushroom', 'Wood', 'Stone', 'Soul', 'Metal', 'Arcane'];
            foreach ($resources as $key => $resource):
                $index = $key + 1;
            ?>
                <div class="col-md-6">
                    <label class="form-label"><?= $resource ?>:</label>
                    <input type="number" name="resources[<?= $index ?>]" placeholder="Quantity of <?= $resource ?>" min="0" class="form-control">
                </div>
            <?php endforeach; ?>
            <div class="col-12">
                <label class="form-label">Number of Uses:</label>
                <input type="number" name="uses" min="1" required class="form-control">
            </div>
            <div class="col-12">
                <button type="submit" name="create" class="btn btn-primary">Create Voucher</button>
            </div>
        </form>
    </div>
    <div class="card-footer">
        <h4 class="text-center">Existing Vouchers</h4>
        <ul class="list-group list-group-flush">
            <?php
            $stmt = $db->prepare("SELECT ID, type, uses, code FROM vouchers");
            $stmt->execute();
            $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($vouchers as $voucher):
                $types = json_decode($voucher['type'], true);
                $typeTexts = [];
                foreach ($types as $typeId => $qty) {
                    $typeTexts[] = $resources[$typeId-1] . ": " . $qty;
                }
            ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    [<?=$voucher['code']?>] <?= implode(', ', $typeTexts) ?> - Uses: <?= $voucher['uses'] ?>
                    <form method="post" class="d-inline">
                        <button type="submit" name="delete" value="<?= $voucher['ID'] ?>" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>