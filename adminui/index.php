<?php require_once '../settings.php'; require_once 'functions.php'; ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$gameName?> | AdminUI</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
	<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<link href="//cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5.0.16/dark.min.css" rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
</head>
<style>
body::before {
	content: '';
	position: fixed;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	background: url('img/background.jpg') no-repeat center center;
	background-size: 100% 100%;
	opacity: 0.5;
	filter: blur(9px);
	z-index: -1;
}
</style>

<body>
    <div class="container">
    <?php 
		$page = $_GET['page'] ?? 'home';
		$index = true;
		
		if (!CheckIsLogged()) {
		if (isset($_POST['login'])) {
			$username = $_POST['username'] ?? '';
			$password = $_POST['password'] ?? '';
			list ($message_type, $message_title, $message_text) = LoginAcp($username, $password);
		}
    ?>
	<div class="d-flex justify-content-center align-items-center vh-100">
		<div class="card" style="width: 22rem;">
			<div class="card-body">
				<h5 class="card-title text-center">Please sign in</h5>
				<form class="form-signin" method="post">
					<div class="mb-3">
						<label for="inputEmail" class="form-label">Username</label>
						<input type="text" id="inputEmail" class="form-control" placeholder="Enter username" required autofocus name="username" autocomplete="off">
					</div>
					<div class="mb-3">
						<label for="inputPassword" class="form-label">Password</label>
						<input type="password" id="inputPassword" class="form-control" placeholder="Enter password" required name="password" autocomplete="off">
					</div>
					<div class="d-grid gap-2">
						<button class="btn btn-lg btn-primary" type="submit" name="login">Sign in</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php } else { ?>
	<div class="row mx-5 mt-3">

		<div class="col-lg-12">
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-12 col-lg-10 d-flex flex-column flex-lg-row justify-content-left">
							<a href="index.php?page=home" class="btn btn-primary my-1 mx-lg-1">Homepage</a>
							<a href="index.php?page=masspm" class="btn btn-primary my-1 mx-lg-1">Mass PM</a>
							<a href="index.php?page=searchplayer" class="btn btn-primary my-1 mx-lg-1">Players</a>
							<a href="index.php?page=searchfortress" class="btn btn-primary my-1 mx-lg-1">Fortresses</a>
							<a href="index.php?page=searchunderworld" class="btn btn-primary my-1 mx-lg-1">Underworlds</a>
							<a href="index.php?page=vouchers" class="btn btn-primary my-1 mx-lg-1">Vouchers</a>
						</div>
						<div class="col-12 col-lg-2 d-flex justify-content-lg-end">
							<a href="index.php?page=logout" class="btn btn-primary my-1 w-100 w-lg-auto">Logout</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- TABS -->
		<div class="col-lg-12 mt-2">
			<?php
				$index = true;
				$page = $_GET['page'] ?? 'home';
				$allowed_pages = ['home', 'dungeon', 'special', 'stats', 'tavern', 'voucher', 'editdung', 'fortress', 'masspm', 'player', 'searchfortress', 'searchplayer', 'searchunderworld', 'underworld', 'vouchers', 'logout'];
if(!in_array($page, $allowed_pages)) $page = 'home';
require_once "pages/{$page}.php";
			?>
		</div>
	</div>
	<?php } ?>

    </div>
    <script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
	
	<?php if (isset($message_title, $message_text, $message_type)) : ?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		Swal.fire({
			title: '<?= $message_title ?>',
			html: '<?= $message_text ?>',
			icon: '<?= $message_type ?>',
			confirmButtonText: 'OK'
		}).then((result) => {
			if (result.value) {
				<?php if (isset($_GET['page']) && $_GET['page'] != 'logout'): ?>
					window.location.href = 'index.php?page=<?= $_GET['page'] ?>';
				<?php else: ?>
					window.location.href = 'index.php';
				<?php endif; ?>
			}
		});
	});
	</script>
	<?php endif; ?>
</body>
</html>
