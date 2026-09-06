<?php
	require_once 'settings.php';
	
	$config = [
		"required_client_version_min" => "15.000",
		"required_app_version" => "15.100",
		"recommended_app_version" => "15.100",
		"language_version" => 1,
		"language_path" => "https://{$clientWeb}/res/sfgame3/lang/",
		"allow_world_selection" => true,
		"resource_directory" => "https://{$clientWeb}/res/sfgame3",
		"language_code" => "{$defLang}",
		"link_support" => "", #"https://sfgame.net/support/?playerid=%playerid%&serverid=%serverid%&lang=%language%",
		"link_forum" => "", #"https://s1.sfgame.de/link.php?game_id=1&lang=%language%&type=forum",
		"link_manual" => "", #"https://cfg.sfgame.de/link.php?game_id=1&lang=%language%&type=manual",
		"tv_poll_interval_short" => 60,
		"tv_poll_interval_long" => 5,
		"tv_function_name" => "flimmerkiste",
		"dungeon_video_url" => "https://cdn.playa-games.com/res/sfgame3/assets/video/dot_wizard/dot_wizard23_intro.mp4",
		"sso" => [
		  "domain" => "northsiderp.hu"
		  ],
		"cookie" => [
		  "consent" => [
			"playa" => [
			  "name" => "playa-cookie-consent"
			  ]
			]
		  ],
		"tracking" => [
		  "signup" => [
			"url" => "", #"https://%server_domain%/marketing/map.php?a=signup&b=%cid%&c=%player_id%&d=1&e=%server_id%&is_mobile=1",
			"function" => "default_phandler",
			"adjust" => "33i4et",
			"adjust_ios" => "4rv53h",
			"adjust_android" => "33i4et"
			],
		  "email_validated" => [
			"url" => "", #"https://%server_domain%/marketing/map.php?a=email_validated&b=%cid%&c=%player_id%&d=1&e=%server_id%&is_mobile=1",
			"function" => "default_phandler",
			"adjust" => "oqz1c6",
			"adjust_ios" => "gqyw29",
			"adjust_android" => "oqz1c6"
			],
		  "188" => [
			"url" => "", #"https://%server_domain%/marketing/map.php?a=188&b=%cid%&c=%player_id%&d=1&e=%server_id%&is_mobile=1",
			"function" => "default_phandler",
			"adjust" => "6ywtoy",
			"adjust_ios" => "ftudil",
			"adjust_android" => "6ywtoy"
			],
		  "197" => [
			"url" => "", #"https://%server_domain%/marketing/map.php?a=197&b=%cid%&c=%player_id%&d=1&e=%server_id%&is_mobile=1",
			"function" => "default_phandler",
			"adjust" => "ix96nl",
			"adjust_ios" => "3gtzxo",
			"adjust_android" => "ix96nl"
			],
		  "payment_done" => [
			"url" => "", #"https://%server_domain%/marketing/map.php?a=payment_done&b=%cid%&c=%player_id%&d=1&e=%server_id%&is_mobile=1",
			"function" => "default_phandler"
			],
		  "payment_done_first" => [
			"url" => "", #"https://%server_domain%/marketing/map.php?a=payment_done_first&b=%cid%&c=%player_id%&d=1&e=%server_id%&is_mobile=1",
			"function" => "default_phandler"
			],
		  "accountlogin" => [
			"url" => "", #"https://%server_domain%/marketing/map.php?a=accountlogin&b=%cid%&c=%player_id%&d=1&e=%server_id%&is_mobile=1",
			"function" => "default_phandler"
			],
		  "appstart" => [
			"url_windows" => "", #"https://cfg.sfgame.net/marketing/map.php?a=appstart&b=%cid%&player_id=%player_id%&d=1&server_id=%server_id%&screen=%screen%&os=%os%&language=%language%&timezone=%timezone%&platform=steam"
			]
		  ],
		"payment" => [
		  "extras" => [
			"coupons" => [
			  "url" => "", #"https://coins.playa-games.com/voucher?player=<playerid>_<paymentid>_<serverid>_<gameid>",
			  "api" => "", #"https://coupon.playa-games.com/redeem"
			  ],
			"offerwall" => [
			  "function" => "offerwall",
			  "parameters" => [
				"<playerid>_<paymentid>_<serverid>_<gameid>"
				]
			  ]
			],
		  "coupons" => [
			"api" => "", #"https://coins.playa-games.com"
			]
		  ],
		"servers" => [
			[
			  "i" => 1,
			  "d" => "{$clientWeb}",
			  "c" => "{$countryCode}"
			]
		  ]
	];
	
	echo json_encode($config, JSON_PRETTY_PRINT);
?>