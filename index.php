<?php require_once 'settings.php'; ?>
<!doctype html>
<html lang="en-us">
<head>
<title><?=$gameName?></title>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<meta name="apple-itunes-app" content="app-id=556886960">
<meta name="google-site-verification" content="rMNAC3YTd-FMTC95GGP9EC-YE-omho-YRHJgePnWvhE">
<meta name="apple-mobile-web-app-capableX" content="yes">
<meta name="description" content="The fun Shakes & Fidget browser game">
<meta name="robots" content="index, follow">
<style>
    * {
        margin: 0;
        padding: 0;
        font-family: sans-serif;
    }

    #gameContainer {
        width: 100%;
        height: 100%;
    }

    #gameContainer canvas {
        width: 100%;
        height: 100%;
        position: absolute;
        display:block;
    }

    div#webgl-content {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 100%;
        -webkit-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        background-color: #000;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -o-user-select: none;
        user-select: none;
    }

    div#loadingBox {
        width: 100%;
        height: 0px;
        position: absolute;
        top: 50%;
        margin-top: 137px;
        text-align: center;
    }

    div#icon {
        width: 1024px;
        height: 256px;
        position: relativ;
        top: 50%;
        left: 50%;
        margin-left: 0px;
        margin-top: 0px;
        background-image: url("https://<?=$clientWeb?>/res/sfgame3/splash/logo.png");
        background-repeat: no-repeat;
    }

    div#box {
        width: 1024px;
        height: 256px;
        position: absolute;
        top: 50%;
        left: 50%;
        margin-left: -512px;
        margin-top: -138px;
    }

    div#bgBar {
        display: none;
        position: absolute;
        width: 300px;
        margin-left: -150px;
        left: 50%;
        height: 18px;
        background-color: #15212E;
    }

    div#progressBar {
        display: none;
        left: 50%;
        position: absolute;
        margin-left: -150px;
        margin-top: -15px;
        width: 0px;
        height: 48px;
        background: #000 url("https://<?=$clientWeb?>/res/sfgame3/splash/progressbar.png") no-repeat right top;
    }

    p#loadingInfo {
        color: #fff;
        letter-spacing: 1px;
        position: absolute;
        width: 100%;
        font-family: sans-serif;
        text-align: center;
        top: 50%;
        font-size: 11px;
        font-weight: 500;
        margin-top: 140px;
        text-shadow: 0px 0px 5px #000;
    }

    div#spinner {
        position: absolute;
        height: 18px;
        left: 50%;
        margin-left: -150px;
        width: 300px;
        position: relative;
        overflow: hidden;
        background-color: #15212E;
        box-shadow: 1px 1px 5px #111;
    }

    div#spinner:before {
        display: block;
        position: absolute;
        content: "";
        width: 150%;
        margin-left: -10px;
        height: 10px;
        background-color: #0A84DD;
        transform: rotate(-5deg);
        animation: loading 1s linear infinite;
    }

    div#error {
        color: #F00;
    }

    #overlay {
        position: fixed;
        display: none;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0,0,0,0.8);
        z-index: 2;
        text-align: center;
        color: #0A3B54;
        font-size: 20px;
        font-weight: bold;
    }

    #overlayc { width: 600px; height: 300px; position: absolute; left: 0; right: 0; top: 0; bottom: 0; margin:auto; }
    #overlay input,textarea,button { color: #FFF; background-color: #0A3B54; font-size: 20px; padding:5px; border:none; margin:auto; }
    #overlay button { color: #EFBF41; }

    @keyframes loading {
        from { top: -185% }
        to { top: 225% }
    }
    #ot-sdk-btn-floating {
      display:none;
    }


    /* Ad Video Player */
        /* The Modal (background) */
        .offerwall-modal,
        .video-modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            padding-top: 100px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0, 0, 0); /* Fallback color */
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
        }

        /* Modal Content */
        .offerwall-modal-content,
        .video-modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            background-color: #fefefe;
            margin: auto;
            padding: 0;
            border: 1px solid #888;
            width: 70%;
            height: 70%;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
            -webkit-animation-name: animatetop;
            -webkit-animation-duration: 0.4s;
            animation-name: animatetop;
            animation-duration: 0.4s
        }

        /* Add Animation */
        @-webkit-keyframes animatetop {
            from {
                top: -300px;
                opacity: 0
            }
            to {
                top: 0;
                opacity: 1
            }
        }

        @keyframes animatetop {
            from {
                top: -300px;
                opacity: 0
            }
            to {
                top: 0;
                opacity: 1
            }
        }

        /* The Close Button */
        .offerwall-close,
        .video-close {
            color: #f0c240;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .offerwall-close:hover,
        .offerwall-close:focus, 
        .video-close:hover,
        .video-close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .offerwall-modal-header, 
        .video-modal-header {
            padding: 2px 16px;
            background-color: #082b3f;
            color: #f0c240;
        }

        .offerwall-modal-body, 
        .video-modal-body {
            padding: 2px 16px;
            height: 100%;
            background-color: black;
        }

        .video-modal-footer {
            padding: 2px 16px;
            background-color: #082b3f;
            color: #f0c240;
        }

        .video-modal-footer h3 {
            min-height: 1em;
        }
		
		#toggler-chat {
			position: fixed;
			top: 50px;
			right: 0px;
			width: 40px;
			height: 100px;
			cursor: pointer;
			z-index: 1001;
			transform: translateY(-50%);
			transition: right 0.5s;
			background: url('res/chat/arrow2.png') no-repeat center center;
		}
		
		#toggler-chat.arrow {
			background: url('res/chat/arrow2.png') no-repeat center center;
		}

		#toggler-chat.arrow2 {
			background: url('res/chat/arrow.png') no-repeat center center;
		}
	
		#chat-iframe {
			width: 620px;
			height: 100%;
			position: fixed;
			top: 0;
			right: -620px;
			transition: right 0.5s;
			z-index: 1000;
			border: none;
		}
</style>

<div id="toggler-chat" onclick="toggleChat()"></div>
<iframe id="chat-iframe" src="chatWindow.php"></iframe>

<a style="position: absolute; z-index: 5; top: 105px; right: 0;" href="https://<?=$clientWeb?>/shop" target="_blank"><img title="User Panel" width="75" src="https://<?=$clientWeb?>/res/itemshop.png"></a>

<script>
// Workaround for old Unity version on Mac OS X 11
var userAgent = navigator.userAgent;
if (/Mac OS X 11/.test(userAgent)) {
    userAgent = userAgent.replace('Mac OS X 11', 'Mac OS X 10');
    navigator.__defineGetter__('userAgent', function(){
      return userAgent;
    });
}

var isIframe = false;
var flimmerkistePoll = 1;
var moshid = 'hash=68b18449f576df7c246a36b2bb6f6a49&time=1682705191';
var PlayaCookie = {}
var storage = {};
</script>
<script crossorigin="anonymous" src="https://<?=$clientWeb?>/res/js/offerwall.js?version=1"></script>
<script crossorigin="anonymous" src="https://<?=$clientWeb?>/res/js/thegame.js?version=13"></script>
<script crossorigin="anonymous" src="https://<?=$clientWeb?>/res/sfgame3/Build/shakesandfidget.loader.js"></script>
<script crossorigin="anonymous" src="https://<?=$clientWeb?>/res/js/playa-pixel-queue.min.js?version=3"></script>
<script crossorigin="anonymous" src="https://<?=$clientWeb?>/2.0/storage_client.js"></script>

<script>
function toggleChat() {
	var iframe = document.getElementById('chat-iframe');
	var toggler = document.getElementById('toggler-chat');
	if (iframe.style.right === '0px') {
		iframe.style.right = '-620px';
		toggler.style.right = '0px';
		toggler.classList.remove('arrow2');
		toggler.classList.add('arrow');
	} else {
		iframe.style.right = '0px';
		toggler.style.right = '620px';
		toggler.classList.remove('arrow');
		toggler.classList.add('arrow2');
	}
}
	
function hasConsent(section) {
    switch (section) {
        case 'game_analytics':
            break;
    }   
    return true;
}
function initStorage() {
    if (typeof LocalStorageClient != 'function' ) {
      console.error('PLAYAWEBGL', 'LocalStorageClient not initialized. AdBlocker active?');

      return;
    }

    storage = new LocalStorageClient('https://<?=$clientWeb?>', 'ls');

    window.PlayaStorageInit = function() {
        console.log('PLAYAWEBGL', 'PlayaStorageInit invoked');
        return storage.Init();
    };

    window.PlayaStorageClear = function() { return storage.Clear(); };
    window.PlayaStorageGet = function(key) {
        console.log('PLAYAWEBGL', 'PlayaStorageGet', key, storage.Get(key));
        return storage.Get(key);
    };

    window.PlayaStorageUnset = function(key) { return storage.Unset(key); };
    window.PlayaStorageSet = function(key, value) { return storage.Set(key, value); };


    var ls = document.getElementById('ls');

    if (ls === null) {
        ls = document.createElement('div');
        ls.setAttribute('id', 'ls');
        ls.style.display = 'none';
        ls = document.getElementsByTagName('body')[0].appendChild(ls);
    }

    if (ls === null) {
        console.error('PLAYAWEBGL', 'Cannot create communication layer for LocalStorageClient');
        return;
    }

    ls.PlayaStorageInitCallback = function(e) {
        console.log('PLAYAWEBGL', 'PlayaStorageInitCallback invoked', e);

        if (game !== null) {
            game.SendMessage('JavaScriptCallbacks', 'OnLocalStorageInitialized');
        } else {
            console.error('PLAYAWEBGL', 'PlayaStorageInitCallback: game is null');
        }
    }

    ls.PlayaStorageDataCallback  = function(k,v) {
        console.log('PLAYAWEBGL', 'PlayaStorageDataCallback invoked', k, v);

        if (game === null) {
            console.error('PLAYAWEBGL', 'game is not initialized', k);

            return;
        }

        if (k && k == "sfgame_new/accounts") {
            console.log('PLAYAWEBGL', 'OnLocalStorageGet invoked', k, v);
            game.SendMessage('JavaScriptCallbacks', 'OnLocalStorageGet', JSON.stringify(v));
        }
    }
}
</script>

<script> // --- Unity Loader

    var game = null;

    try { window.indexedDB } catch (err) { ERROR = "'Indexed DB' is disabled in your browser! Please enable it to play the game!"; }

    function unityProgress(progress) {
        var container = document.getElementById('gameContainer');
        if (container) document.body.style.background = container.style.background;

        if (progress == 1) {
            document.getElementById("loadingInfo").innerHTML = "PROCESSING...";
            document.getElementById("spinner").style.display = "inherit";
            document.getElementById("bgBar").style.display = "none";
            document.getElementById("progressBar").style.display = "none";
        } else if (progress > 0) {
            document.getElementById("progressBar").style.width = (300 * (progress * 0.6) + 15) + "px"
            document.getElementById("loadingInfo").innerHTML = "";
            document.getElementById("spinner").style.display = "none";
            document.getElementById("bgBar").style.display = "block";
            document.getElementById("progressBar").style.display = "inherit";
        }
    }    

    function unityComplete(unityInstance) {
	game = unityInstance;

        document.getElementById("loadingBox").style.display = "none";
        document.getElementById("icon").style.display = "none";
        document.getElementById("loadingInfo").style.display = "none";
        document.getElementById("box").style.display = "none";
    }

    function onPageLoad() {
        if (typeof ERROR !== 'undefined') {
            document.getElementById("spinner").style.display = "none";
            document.getElementById("loadingBox").innerHTML = '<div id="error">' + ERROR + '</div>';
            document.getElementById("loadingBox").style.display = "block";
        } else {
            load();        
        }
    }; 

    function load() {
        var buildUrl = "https://<?=$clientWeb?>/res/sfgame3/Build/";        
        var container = document.querySelector("#canvas")       

        var config = {
            dataUrl: buildUrl + "bd3dadb1d1d3b9c8685f8453238207a7.data.gz",
            frameworkUrl: buildUrl + "038158da4990ed35f7140519c7b6ea28.js.gz",
            codeUrl: buildUrl + "49512cbacaf9d9806d56a7c446b9cfd2.wasm.gz",
            streamingAssetsUrl: "https://<?=$clientWeb?>/res/sfgame3/StreamingAssets/WEBGL",
            companyName: "Playa Games GmbH",
            productName: "Shakes & Fidget",
            productVersion: "15.200.230406.1",
        // matchWebGLToCanvasSize: false, // Uncomment this to separately control WebGL canvas render size and DOM element size.
        // devicePixelRatio: 1, // Uncomment this to override low DPI rendering on high DPI displays.
        };
        
        createUnityInstance(container, config, unityProgress)
        .then(unityComplete)
        .catch((message) => {
            console.error('UNITY ERROR: ', message)}
        );
    }

    function showHelpshift(id, name, language, token, params)
    {
		return;
    }

    function closeHelpshift()
    {
		return;
    }

    if (window.addEventListener) {
        window.addEventListener("touchmove", function (e) { e.preventDefault (); }, false);
        window.addEventListener("message", function(e) { storage.displayMessage(e)}, false);
    } else {
        window.attachEvent("ontouchmove", function (e) { e.preventDefault (); });
        window.attachEvent("onmessage", function(e) { storage.displayMessage(e)});
    }

    document.addEventListener("DOMContentLoaded", function(){
        onPageLoad();
    });
    window.addEventListener("load", function(){
        initStorage();
    });
	
	flimmerkiste = (mode, player_id, g, b) => {
		return 0;
	}
</script>
</head>
<body >

<div id="webgl-content">
    	<div id="gameContainer">
		<canvas id="canvas"></canvas>
	</div>
</div>
<div id="box">
    <div id="icon"></div>
</div>
<div id="loadingBox">
    <div id="spinner"></div>
    <div id="bgBar"></div>
    <div id="progressBar"></div>
</div>
<p id="loadingInfo"></p>

<script type="text/javascript">
   window.playapixelqueue = new PlayaPixelQueue(1, 458, "", "");

    window.playapixelqueue.addCallback(function () {
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
    
        gtag('config', 'DC-8816948');
        gtag('event', 'conversion', {
            'allow_custom_scripts': true,
            'send_to': 'DC-8816948/invmedia/i7wmkrjb+standard',
            'anonymize_ip': true
        });
        window.playapixelqueue.logger("Google pixel called", {id: "DC-8816948"});
    }, "http://www.googletagmanager.com/gtag/js?id=DC-8816948");

    // Global site tag (gtag.js) - Google AdWords: 828067760
    window.playapixelqueue.addCallback(function () {
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'AW-828067760');
        gtag('event', 'page_view', {
          'send_to': 'AW-828067760',
          'user_id': '1',
          'anonymize_ip': true
        });
        window.playapixelqueue.logger('Google pixel called', {id: 'AW-828067760'});
    }, 'http://www.googletagmanager.com/gtag/js?id=AW-828067760'); 
	
    // Google Code für richtig hältst
    window.playapixelqueue.addCallback(function(){
        var google_conversion_id = 1026250221;
        var google_custom_params = window.google_tag_params;
        var google_remarketing_only = true;
    }, "http://www.googleadservices.com/pagead/conversion.js");
    
        window.playapixelqueue.addUrl(
            window.playapixelqueue.getMapUrl('regstart', '', '', 'marketing'),
            "regstart"
        );
    </script>
<!-- version: 15.200.230406.1, build date: 06.04.2023 11:21:15 -->

</body>

</html>
