<html>
<head>
<title>Chat</title>
</head>
<style>
#panel {
    position: fixed;
    top: 0px;
    left: 0px;
}

#panel #content {
    width: 600px;
    height: 660px;
    padding: 10px;
    z-index: 20;
    background: url(res/chat/bgchat.png);
    position: absolute;
    border-radius: 10px 0 0 10px;
    left: 0px;
}

#screen {
    width: 100%;
    height: calc(632px - 15px);
    overflow: auto;
    text-align: left;
    font-size: 12px;
    font-family: Verdana;
    margin-bottom: 7px;
}

input#message {
	width: 608px;
	height: 35px;
	color: #C0C0C0;
	padding: 2px;
	border: 1px solid #1A1A1A;
	background: #3A3A3A;
	font-size: 16px;
	margin-left: 1px;
}

.chat_time { 
    color:#ffa700f0;
    font-weight:bold;
}

.player { 
    color:#f7f7f7;
    font-weight:bold;
    background: url(res/chat/color/white.gif);
    text-shadow: 0px 0px 5px white;
}

.vip {
    color:gold;
    font-weight:bold;
    background: url(res/chat/color/gold.gif);
    text-shadow: 0px 0px 5px gold;
}

.administrator {
    color:red;
    font-weight:bold;
    background: url(res/chat/color/red.gif);
    text-shadow: 0px 0px 5px #ff1717d4;
}

.level { 
    color:#f9ff00bd;
    font-weight:bold;
}

.message { 
    color:#dadada;
    font-weight:bold; 
}

.alert {
    color: white;
    background-color: #dc3545;
    padding: 10px;
    margin: 10px 0;
    border-radius: 5px;
    text-align: center;
    border: 1px solid transparent;
    box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
}
</style>
<body>
    <div id="panel">
        <div class="button" id="title"></div>  
        <div id="content">
            <center>
                <div id="screen"></div>
                <input name="message" id="message" type="text" MAXLENGTH="300" size="90" autocomplete="off" placeholder="Type the message and click Enter to send." />
            </center>
        </div>
    </div>
    <script>
		const php_file = 'chat.php';
		document.addEventListener('DOMContentLoaded', function() {
			update();
			document.getElementById("message").addEventListener('keydown', function(e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					send_msg();
				}
			});
			setInterval(update, 5000);
		});

		function send_msg() {
			const message = document.getElementById("message").value.trim();
			if (message === '') {
				displayAlert("Message cannot be empty.");
				return;
			}

			fetch(php_file, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'message=' + encodeURIComponent(message)
			})
			.then(response => response.json())
			.then(data => {
				if (data.errors && data.errors.length > 0) {
					displayAlert(data.errors.join("<br>"));
				} else {
					updateMessages(data.messages, data.personalMessage);
					document.getElementById("message").value = "";
					if (document.getElementById('alertBox')) {
						document.getElementById('alertBox').style.display = 'none';
						document.getElementById('screen').style.height = '617px';
					}
				}
			})
			.catch(error => console.error('Error:', error));
		}


		async function update() {
			try {
				const response = await fetch(php_file);
				const data = await response.json();
				if (data.errors && data.errors.length > 0) {
					displayAlert(data.errors.join("<br>"));
				} else {
					updateMessages(data.messages, data.personalMessage);
				}
			} catch (error) {
				console.error('Error:', error);
			}
		}


		function updateMessages(messages, personalMessage) {
			const screen = document.getElementById("screen");
			screen.innerHTML = '';
			messages.forEach(msg => {
				const msgHtml = `<span class="chat_time">[${msg.time}]</span>&nbsp;<span class="${msg.rankClass}">${msg.name} (Lv: ${msg.level}) ${msg.rankIcon}</span>&nbsp;<span class="message">${msg.messageText}</span><br/>`;
				screen.innerHTML += msgHtml;
			});
			// Ensure scrolling to the bottom
			screen.scrollTop = screen.scrollHeight;

			if (personalMessage) {
				alert(personalMessage);
			}
		}


		function displayAlert(message) {
			let alertBox = document.getElementById('alertBox');
			let screen = document.getElementById('screen');

			// Define heights
			const screenWithAlertHeight = '558px';
			const originalScreenHeight = '617px';

			if (!alertBox) {
				alertBox = document.createElement('div');
				alertBox.id = 'alertBox';
				alertBox.className = 'alert';
				let contentDiv = document.getElementById('content');
				contentDiv.insertBefore(alertBox, contentDiv.firstChild);
			}

			alertBox.innerHTML = message;
			alertBox.style.display = 'block';
			screen.style.height = screenWithAlertHeight;

			setTimeout(() => {
				alertBox.style.display = 'none';
				screen.style.height = originalScreenHeight;
			}, 5000); // Hides the alert after 5 seconds
		}
    </script>
</body>
</html>