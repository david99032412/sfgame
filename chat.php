<?php
require_once 'settings.php';

$response = ['errors' => [], 'messages' => []];

$qry = $db->prepare('SELECT ID, banned, usysclass FROM players WHERE ssid = :ssid');
$qry->execute([':ssid' => ($_COOKIE['ssid'] ?? '')]);
$player = $qry->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['message'])) {
    $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');

    if (empty($player)) {
        $response['errors'][] = "You are not logged in!";
    } elseif (strlen($message) < 1) {
        $response['errors'][] = "The message cannot be empty!";
    } elseif ($player['banned'] == 1) {
        $response['errors'][] = "You are banned from posting messages.";
    } else {
		if (preg_match('/^\/(\w+)\s*(.*)$/', $message, $matches)) {
            $command = $matches[1]; $argument = $matches[2];
			
            if ($command == 'ban' || $command == 'unban') {
                if ($player['usysclass'] < 3) {
                    $response['errors'][] = "You do not have permission to use this command.";
                } else {
                    $targetPlayer = $db->prepare('SELECT ID FROM players WHERE name = :name');
                    $targetPlayer->execute([':name' => $argument]);
                    $target = $targetPlayer->fetch(PDO::FETCH_ASSOC);

                    if (!$target) {
                        $response['errors'][] = "Player not found.";
                    } else {
                        if ($command == 'ban') {
                            $db->prepare('UPDATE players SET banned = 1, ssid = 0, acpSession = :acpSession WHERE ID = :id')->execute([':acpSession' => null, ':id' => $target['ID']]);
							$qry = $db->prepare('INSERT INTO chat_messages (time, message, sender) VALUES (:time, :message, :sender)');
							$qry->execute([':time' => $CURRTIME, ':message' => "Player $argument has been banned.", ':sender' => $player['ID']]);
                        } else {
                            $db->prepare('UPDATE players SET banned = 0 WHERE ID = :id')->execute([':id' => $target['ID']]);
							$qry = $db->prepare('INSERT INTO chat_messages (time, message, sender) VALUES (:time, :message, :sender)');
							$qry->execute([':time' => $CURRTIME, ':message' => "Player $argument has been unbanned.", ':sender' => $player['ID']]);
                        }
                    }
                }
            } elseif ($command == 'clear') {
                if ($player['usysclass'] < 3) {
                    $response['errors'][] = "You do not have permission to clear the chat.";
                } else {
                    $db->query('TRUNCATE TABLE chat_messages');
					$qry = $db->prepare('INSERT INTO chat_messages (time, message, sender) VALUES (:time, :message, :sender)');
					$qry->execute([':time' => $CURRTIME, ':message' => "Chat has been cleared.", ':sender' => $player['ID']]);
                }
            } elseif ($command == 'help') {
                $helpText = "Available commands:\n/ban [username] - Ban a player.\n/unban [username] - Unban a player.\n/clear - Clear the chat history.";
                $response['personalMessage'] = $helpText;
            } else {
                $response['errors'][] = "Unknown command.";
            }
			
		} else {
			$qry = $db->prepare('INSERT INTO chat_messages (time, message, sender) VALUES (:time, :message, :sender)');
			$qry->execute([':time' => $CURRTIME, ':message' => $message, ':sender' => $player['ID']]);
		}
    }
}

if (empty($response['errors'])) {
    $qry = $db->query("SELECT players.name, players.lvl, players.usysclass, players.class, chat_messages.* FROM chat_messages JOIN players ON players.ID = chat_messages.sender ORDER BY time DESC LIMIT 100");
    $messages = $qry->fetchAll(PDO::FETCH_ASSOC);
	$messages = array_reverse($messages); 
    foreach ($messages as $message) {
        $response['messages'][] = [
            'time' => date('H:i', $message['time']),
            'name' => $message['name'],
            'level' => $message['lvl'],
            'rankClass' => getRankClass($message['usysclass']),
            'rankIcon' => getRankClassIcon($message['class']),
            'messageText' => htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8')
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);

function getRankClass($usysclass) {
	if ($usysclass == 2) return 'vip';
	if ($usysclass == 3) return 'moderator';
	if ($usysclass == 4) return 'administrator';
	return 'player';
}

function getRankClassIcon($class) {
	return "<img src=\"res/chat/class/class{$class}.png\" width=\"25px\" height=\"25px\">";
}
?>
