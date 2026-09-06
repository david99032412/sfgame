<?php
    if (!$index) exit;

    $qry = $db->prepare('SELECT ID, name, usysclass FROM players WHERE poll > :15mins');
    $qry->execute([':15mins' => $CURRTIME - 900]);
    $players15mins = $qry->fetchAll(PDO::FETCH_ASSOC);

    $qry = $db->prepare('SELECT ID, name, usysclass FROM players WHERE poll > :24hrs');
    $qry->execute([':24hrs' => $CURRTIME - 86400]);
    $players24hrs = $qry->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <h3 class="card-header text-center text-uppercase">Online players [15 minutes]</h3>
    <div class="card-body">
        <?php if (!empty($players15mins)): ?>
            <?php echo implode(', ', array_map(function($player) {
                return FormatName($player);
            }, $players15mins)); ?>
        <?php else: ?>
            <span style="color: red;">No active players</span>
        <?php endif; ?>
    </div>
</div>
<div class="card mt-2">
    <h3 class="card-header text-center text-uppercase">Online players [24 hours]</h3>
    <div class="card-body">
        <?php if (!empty($players24hrs)): ?>
            <?php echo implode(', ', array_map(function($player) {
                return FormatName($player);
            }, $players24hrs)); ?>
        <?php else: ?>
            <span style="color: red;">No active players</span>
        <?php endif; ?>
    </div>
</div>
