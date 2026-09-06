<?php
function CheckIsLogged() {
    global $acpLogin, $acpPassword, $db;

    $sessionCookie = $_COOKIE['sessionAcp'] ?? '';
    if (empty($sessionCookie)) {
        return false;
    }

    $validSalt = EncryptSessionCookie($acpLogin . $acpPassword . "0");

    if ($sessionCookie == $validSalt) {
        return true;
    }

    $qry = $db->prepare('SELECT ID, usysclass, name FROM players WHERE acpSession = :acpSession');
    $qry->execute([':acpSession' => $sessionCookie]);

    if ($qry->rowCount() > 0) {
        return true;
    }

    return false;
}

function hashPassword($password) {
	return sha1($password.'ahHoj2woo1eeChiech6ohphoB7Aithoh');
}

function LoginAcp($inputLogin, $inputPassword) {
    global $acpLogin, $acpPassword, $db;
    
    if ($acpLogin == $inputLogin && $acpPassword == $inputPassword) {
        $acpSession = EncryptSessionCookie($acpLogin . $acpPassword . "0");
        setcookie('sessionAcp', $acpSession, time() + 86400, '/', '', isset($_SERVER['HTTPS']), true);
        return ['success', 'Success', 'You have successfully logged into the main account.'];
    } else {
        $qry = $db->prepare("SELECT ID, password, usysclass FROM players WHERE name = :inputLogin");
        $qry->execute([':inputLogin' => $inputLogin]);
        $user = $qry->fetch(PDO::FETCH_ASSOC);
        
        if (empty($user))
            return ['error', 'Error', 'User not found. Please check your credentials.'];
        
        if (hashPassword($inputPassword) != $user['password'])
            return ['error', 'Error', 'Invalid password. Please try again.'];
        
        if ($user['usysclass'] < 3)
            return ['error', 'Error', 'You do not have the authority to use ACP'];
        
        $acpSession = EncryptSessionCookie($inputLogin . $inputPassword . $user['ID']);
        $qry = $db->prepare('UPDATE players SET acpSession = :acpSession WHERE ID = :ID');
        $qry->execute([':acpSession' => $acpSession, ':ID' => $user['ID']]);
        
        setcookie('sessionAcp', $acpSession, time() + 86400, '/', '', isset($_SERVER['HTTPS']), true);
        return ['success', 'Success', 'You have successfully logged in.'];
    }
}

function EncryptSessionCookie($string) {
    global $acpSalt;
    return hash('sha256', $string . $acpSalt);
}

function FormatName($player) {
    $color = 'white';
    if ($player['usysclass'] == 4) {
        $color = 'red';
    } elseif ($player['usysclass'] == 3) {
        $color = 'lime';
    } elseif ($player['usysclass'] == 2) {
        $color = 'gold';
    }

    $link = '<a href="index.php?page=player&id=' . $player['ID'] . '" class="text-decoration-none">';
    $link .= '<span style="color:' . $color . ';">' . htmlspecialchars($player['name']) . '</span>';
    $link .= '</a>';

    return $link;
}

function FormatMessageText($string) {
    $result = "";
    $length = strlen($string);

    for ($i = 0; $i < $length; $i++) {
        $char = $string[$i];
        switch ($char) {
            case '%':
                $result .= '$P';
                break;
            case ':':
                $result .= '$c';
                break;
            case ';':
                $result .= '$S';
                break;
            case '|':
                $result .= '$p';
                break;
            case '/':
                $result .= '$s';
                break;
            case '&':
                $result .= '$+';
                break;
            case '$':
                $result .= '$d';
                break;
            case '"':
                $result .= '$q';
                break;
            case '#':
                $result .= '$r';
                break;
            case "\r":
                $result .= '$b';
                break;
            case "\n": break;
            default:
                $result .= $char;
        }
    }
    return $result;
}

function DungeonName($dungeonNumber) {
    switch ($dungeonNumber) {
        case 1: return 'Desecrated catacombs';
        case 2: return 'The mines of gloria';
        case 3: return 'The ruins of gnark';
        case 4: return 'The cutthroat grotto';
        case 5: return 'The emerald scale altar';
        case 6: return 'The toxic tree';
        case 7: return 'The magma stream';
        case 8: return 'The frost blood temple';
        case 9: return 'The pyramids of madness';
        case 10: return 'Black skull fortress';
        case 11: return 'Circus of terror';
        case 12: return 'Hell';
        case 15: return '13th floor';
        case 19: return 'Easteros';
        case 17: return 'Time-honored school of magic';
        case 18: return 'Hemorridor';
        case 24: return 'Nordic gods';
        case 27: return 'Mount olympus';
        case 21: return 'Tavern of the dark doppelgangers';
        case 13: return 'Dragon\'s hoard';
        case 14: return 'House of horrors';
        case 16: return 'The 3rd league of superheroes';
        case 20: return 'Dojo of childhood heroes';
        case 28: return 'Monster grotto';
        case 22: return 'City of intrigues';
        case 23: return 'School of magic express';
        case 25: return 'Ash mountain';
        case 26: return 'Playa HQ';
        default: return 'Unknown dungeon';
    }
}

function DungeonLevel($level) {
	if ($level == 0) return "Open";
	if ($level < 10) return "Level {$level}";
	return "Finished";
}

function GenerateCode($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}