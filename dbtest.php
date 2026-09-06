<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    $db = new PDO(
        'mysql:host=localhost;dbname=sfgame;charset=utf8',
        'root',
        'laller'
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "DB KAPCSOLAT OK<br>";

    echo "Szerver: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";

    $result = $db->query("SELECT COUNT(*) FROM players");
    echo "Players: " . $result->fetchColumn();

} catch (PDOException $e) {
    echo "<pre>";
    echo "DB HIBA:\n";
    echo $e->getMessage();
    echo "</pre>";
}