<?php
if (!$index) exit;

if (isset($_POST['send'])) {
    $topic = $_POST['topic'] ?? '';
    $message = $_POST['message'] ?? '';

    if (strlen($topic) < 5 || strlen($topic) > 100 || strlen($message) < 10 || strlen($message) > 1000) {
		$message_type = 'error'; $message_title = 'Error';
		$message_text = 'Invalid input lengths for topic or message.';
    } else {
        $qry = $db->prepare('SELECT ID FROM players');
        $qry->execute();
        $players = $qry->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($players)) {
			$message_type = 'error'; $message_title = 'Error';
			$message_text = 'No one player found!';
		} else {
			$insertQry = $db->prepare('INSERT INTO messages(sender, reciver, topic, message, hasRead, time) VALUES (0, :receiver, :topic, :message, 0, :time)');
			
			foreach ($players as $player) {
				$insertQry->execute([
					':receiver' => $player['ID'],
					':topic' => FormatMessageText($topic),
					':message' => FormatMessageText($message),
					':time' => $CURRTIME
				]);
			}

			$message_type = 'success'; $message_title = 'Success';
			$message_text = 'Messages sent successfully.';
		}
    }
}
?>
<div class="card">
    <h3 class="card-header text-center text-uppercase">Mass PM</h3>
    <div class="card-body">
        <form method="post">
            <div class="form-group mb-3">
                <label for="topic">Subject:</label>
                <input type="text" class="form-control" id="topic" name="topic" required minlength="5" maxlength="100" placeholder="Enter subject" autocomplete="off">
            </div>
            <div class="form-group mb-3">
                <label for="message">Message:</label>
                <textarea class="form-control" id="message" name="message" required minlength="10" maxlength="1000" rows="4" placeholder="Enter message" autocomplete="off"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" name="send">Send to All</button>
        </form>
    </div>
</div>