<?php
    if (!$index) exit;

	setcookie('sessionAcp', '', time() - 86400);
	list ($message_type, $message_title, $message_text) = ['success', 'Success', 'Successfully logged out'];
	
	$qry = $db->prepare('UPDATE players SET acpSession = :acpSession WHERE acpSession = :sessionWhere');
	$qry->execute([':acpSession' => null, ':sessionWhere' => $_COOKIE['sessionAcp']]);
?>