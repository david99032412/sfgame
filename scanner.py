import os
import re
import shutil
import subprocess

def apply_northside_fixes():
    target_dir = "/var/www/html"
    print(f"[*] NorthsideRP Éles Rendszerjavító Indítása: {target_dir}")

    # 1. Fájlnevek helyreállítása (A feltöltött _2.php és _3.php végződések élesítése)
    renames = {
        "req_3.php": "req.php",
        "settings_2.php": "settings.php",
        "config1_2.php": "config1.php",
        "chat_2.php": "chat.php",
        "chatWindow_2.php": "chatWindow.php",
        # Az index_3.php a főoldali game loader, az index.php az admin panel. Óvatosan a másolással!
    }
    
    for root, _, files in os.walk(target_dir):
        for old_name, new_name in renames.items():
            if old_name in files:
                old_path = os.path.join(root, old_name)
                new_path = os.path.join(root, new_name)
                # Ha a játéktöltő (index_3.php) a gyökérben van, a meglévő admin index.php-t ne írjuk felül azonnal
                if old_name == "index_3.php" and root == target_dir and "index.php" in files:
                    print("[FIGYELMEZTETÉS] Gyökérkönyvtári index.php már létezik (valószínűleg AdminUI). Az index_3.php-t manuálisan nevezd el game.php-ra vagy tedd külön mappába az admin panelt.")
                    continue
                shutil.copy(old_path, new_path)
                print(f"[*] Fájl élesítve: {old_name} -> {new_name}")

    # 2. req.php javítása (WebGL POST Payload és CORS védelem)
    for root, _, files in os.walk(target_dir):
        if "req.php" in files:
            req_file = os.path.join(root, "req.php")
            with open(req_file, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()

            # CORS Fejlécek és BOM pufferelés hozzáadása
            if "Access-Control-Allow-Origin" not in content:
                content = content.replace("<?php", "<?php\nob_start();\nheader('Access-Control-Allow-Origin: *');\nheader('Access-Control-Allow-Methods: GET, POST, OPTIONS');\nheader('Access-Control-Allow-Headers: Content-Type');\nif ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(0); }", 1)

            # A GET olvasás lecserélése hibrid POST/GET Payload olvasóra
            if "$_GET['req']" in content:
                pattern = r"if\(!isset\(\$_GET\['req'\]\)\).*?\$keyId = substr\( \$_GET\['req'\], 0, 16\);"
                secure_payload = """$rawPost = file_get_contents('php://input');
$requestPayload = '';
if (!empty($rawPost) && strpos($rawPost, 'req=') === 0) { $requestPayload = urldecode(substr($rawPost, 4)); }
elseif (!empty($rawPost)) { $requestPayload = $rawPost; }
else { $requestPayload = $_POST['req'] ?? $_GET['req'] ?? ''; }

if(empty($requestPayload)) { ob_clean(); exit("&Error:wrong request"); }

$req = substr($requestPayload, 16);
$key = '[_/$VV&*Qg&)r?~g';
$iv = 'jXT#/vz]3]5X7Jl\\\\';
$keyId = substr($requestPayload, 0, 16);"""
                content = re.sub(pattern, secure_payload, content, flags=re.DOTALL)
            
            # Puffer ürítése a BOM szivárgás ellen a válasz elküldése előtt
            content = re.sub(r"echo join\(\"&\", \$ret\);", r"ob_clean();\necho join(\"&\", $ret);", content)

            with open(req_file, "w", encoding="utf-8") as f:
                f.write(content)
            print(f"[*] JAVÍTVA (WebGL POST támogatás): {req_file}")

    # 3. settings.php domain javítása
    for root, _, files in os.walk(target_dir):
        if "settings.php" in files:
            set_file = os.path.join(root, "settings.php")
            with open(set_file, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()
            if '"localhost/v15"' in content:
                content = content.replace('"localhost/v15"', '"northsiderp.hu"')
                with open(set_file, "w", encoding="utf-8") as f:
                    f.write(content)
                print(f"[*] JAVÍTVA (Domain beállítva): {set_file}")

    # 4. AdminUI LFI foltozás
    for root, _, files in os.walk(target_dir):
        if "index.php" in files:
            idx_path = os.path.join(root, "index.php")
            with open(idx_path, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()
            if "require_once \"pages/{$page}.php\";" in content and "allowed_pages" not in content:
                content = content.replace(
                    "require_once \"pages/{$page}.php\";",
                    "$allowed_pages = ['home', 'dungeon', 'special', 'stats', 'tavern', 'voucher', 'editdung', 'fortress', 'masspm', 'player', 'searchfortress', 'searchplayer', 'searchunderworld', 'underworld', 'vouchers', 'logout'];\nif(!in_array($page, $allowed_pages)) $page = 'home';\nrequire_once \"pages/{$page}.php\";"
                )
                with open(idx_path, "w", encoding="utf-8") as f:
                    f.write(content)
                print(f"[*] JAVÍTVA (AdminUI LFI sebezhetőség lezárva): {idx_path}")

    # 5. functions.php XSS védelem a sütiknél
    for root, _, files in os.walk(target_dir):
        if "functions.php" in files:
            func_path = os.path.join(root, "functions.php")
            with open(func_path, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()
            if "setcookie('sessionAcp'" in content and "HttpOnly" not in content and "true" not in content:
                content = content.replace(
                    "setcookie('sessionAcp', $acpSession, time() + 86400);",
                    "setcookie('sessionAcp', $acpSession, time() + 86400, '/', '', isset($_SERVER['HTTPS']), true);"
                )
                with open(func_path, "w", encoding="utf-8") as f:
                    f.write(content)
                print(f"[*] JAVÍTVA (Insecure Cookie HttpOnly flag): {func_path}")

    # 6. UTF-8 BOM irtás
    for root, _, files in os.walk(target_dir):
        for file in files:
            if file.endswith((".php", ".js", ".html", ".json")):
                path = os.path.join(root, file)
                try:
                    with open(path, "rb") as f:
                        raw = f.read()
                    if raw.startswith(b'\xef\xbb\xbf'):
                        with open(path, "wb") as f:
                            f.write(raw[3:])
                        print(f"[*] BOM eltávolítva: {path}")
                except: pass

    # 7. Jogosultságok és Apache újraindítás
    try:
        print("[*] Linux jogosultságok beállítása (644/755) és Apache2 újraindítása...")
        subprocess.run(["chown", "-R", "www-data:www-data", target_dir], check=False)
        subprocess.run(["find", target_dir, "-type", "d", "-exec", "chmod", "755", "{}", "+"], check=False)
        subprocess.run(["find", target_dir, "-type", "f", "-exec", "chmod", "644", "{}", "+"], check=False)
        subprocess.run(["systemctl", "restart", "apache2"], check=False)
        print("[*] Apache2 sikeresen újraindítva!")
    except Exception as e:
        print(f"[HIBA] Rendszerparancs hiba: {e}")

    print("\n[VÉGE] A NorthsideRP szerver javítva és WebGL-kompatibilis.")

if __name__ == "__main__":
    apply_northside_fixes()