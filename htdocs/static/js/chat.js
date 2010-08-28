Chat = (function() {

	var DEFAULT_NAME = 'Guest',
		HEIGHT_BUFFER = 108,
		KEY_UP = 38,
		KEY_DN = 40,
		ONLINE_NOW = 'online now',
		STATUS_OFFLINE = 0,
		STATUS_ONLINE = 1,
		STATUS_AWAY = 2,
		STATUS_TYPING = 3,
		TITLE_INTERVAL = 1000,
		TYPE_EMOTE = 2,
		UPDATE_INTERVAL = 3500,
		URL_UPDATE = '/api/get/messages/%s/%s';

	var ops,
		queue = [],
		user = {
			name : DEFAULT_NAME
		};

	var init = function(params) {

		var name = $('#name');

		ops = params || {};
		ops.focus = true;
		ops.title = ops.title || document.title;

		if (!name.val()) name.val($.cookie(ops.roomName + '_name') || DEFAULT_NAME);
		user.name = name.val();

		addEvents();
		resize();
		scrollIntoView();
		setTimeout(update, UPDATE_INTERVAL);

	};

	var addEvents = function() {

		// Send a message
		$('#controls').submit(function() {

			var el = document.getElementById('message'),
				msg = el.value;

			if (msg == '') return;

			parseMessage(msg);
			history.add(msg);

			el.value = '';

			return false;

		});

		// Focusing for new messages
		$('message').focus(function() {
			newMessage.off();
		});
		$(document).blur(function() {
			ops.focus = false;
		});
		$(document).focus(function() {
			newMessage.off();
		});

		// History
		$(document).keydown(history.keyDown);

		// Change name
		$('#name').change(function() {
			user.name = document.getElementById('name').value || DEFAULT_NAME;
			$.cookie(ops.roomName + '_name', user.name);
		});

		// Prevent scrolling when looking around
		$(document).mousedown(function() {
			ops.mouseDown = true;
		});

		// Mouse handling
		$(document).mouseup(function() {
			ops.mouseDown = false;
		});

		// Resizing
		$(window).resize(function() {
			resize();
		});

		// Upload a file
		$('#upload > input').change(upload);

	};

	var addLine = function(className) {

		var el, li;

		el = document.getElementById('chats').getElementsByTagName('ul')[0];
		li = document.createElement('li');
		li.className = className;

		el.appendChild(li);

	};

	var addMessage = function(message) {

		var date, el, li, link = '#',
			html = '';

		if (!message) {
			return;
		}

		el = document.getElementById('chats').getElementsByTagName('ul')[0];
		li = document.createElement('li');

		if (message.message_id) {
			date = new Date(message.timestamp * 1000);
			link = printf(
				location.href  + '/history/%s/%s/%s/#message-%s',
				date.getFullYear(),
				(date.getMonth() + 1),
				date.getDate(),
				message.message_id
			);
		}

		if (message.date) {
			html += '<a class="date" href="' + link + '">[' + message.date + ']</a>';
		} else {
			html += '<a class="date" href="' + link + '">[' + getTime() + ']</a>';
		}

		html += "\n";

		if (message.name) {
			if (message.type == TYPE_EMOTE) {
				html += '<span title="' + message.hostname  + '">' + message.name + '</span>';
			} else {
				html += '<span class="name" title="' + message.hostname  + '">&lt;' + message.name + '&gt;</span>';
			}
		}

		html += "\n";
		html += '<span class="message">' + message.message + '</span>';

		if (message.className) {
			li.className = message.className;
		} else if (matchWatches(message.message)) {
			li.className = 'hl';
		}

		li.innerHTML = html;
		el.appendChild(li);

		scrollIntoView();

	};

	var error = (function() {

		var notice = function(msg) {
			systemMessage(msg || 'Sorry! An error occurred. Trying again...');
		};

		return {
			notice : notice
		};

	})();

	var execCommand = function(cmd) {

		switch (cmd.action) {
			case 'topic':
				$('#chats').find('strong').html(cmd.value.replace('\\', ''));
				break;
		}

	};

	var getTime = function() {

		var date = new Date(),
			hour = date.getHours(),
			mins = date.getMinutes();

		if (hour > 12) hour -= 12;
		if (mins < 10) mins = '0' + mins;

		return hour + ':' + mins;

	};

	var history = (function() {

		var index = 0,
			messages = [];

		return {
			add : function(text) {
				messages.push(text);
				index = messages.length;
			},
			keyDown : function(e) {
				switch (e.keyCode) {
					case KEY_UP:
						if (index > 0) {
							index--;
							$('#message').val(messages[index]);
						}
						break;
					case KEY_DN:
						if (index >= messages.length) {
							$('#message').val('');
							index = messages.length;
						} else {
							index++;
							$('#message').val(messages[index]);
						}
						break;
				}
			}
		};

	})();

	var killCSS = function() {

		var head = document.getElementsByTagName('head')[0],
			links = head.getElementsByTagName('link');

		for (var i = 0; i < links.length; i++) {
			if (links[i].rel == 'stylesheet') {
				head.removeChild(links[i]);
			}
		}

	};

	var loadCSS = function(file) {

		var link = document.createElement('link');
		link.href = file;
		link.rel = 'stylesheet';
		link.type = 'text/css';
		document.getElementsByTagName('head')[0].appendChild(link);

	};

	var matchWatches = function(msg) {
		return (msg.search(user.name) != -1);
	};

	var newMessage = (function() {

		var interval,
			on = true,
			num = 0,
			toggle;

		return {
			off : function() {

				on = false;
				ops.focus = true;
				num = 0;

				if (interval) {
					clearInterval(interval);
					document.title = ops.title;
					interval = false;
				}

			},
			on : function(numNew) {

				if (ops.focus || (numNew == 0)) return;

				if (num == 0) {
					addLine('sep');
				}

				num += numNew;
				on = true;

				interval = interval || setInterval(function() {
					document.title = (toggle ? '*' + ops.title : printf('(%s New Message' + ((num == 1) ? '' : 's') + ')', num));
					toggle = !toggle;
				}, TITLE_INTERVAL);

			}
		};

	})();

	var parseMessage = function(msg) {

		var tokens = msg.split(' ');

		switch (tokens[0]) {

			case '/clear':
				document.getElementById('chats').getElementsByTagName('ul')[0].innerHTML = '<li>Messages cleared</li>';
				break;

			case '/echo':
				addMessage({
					message : msg.substr(5, msg.length)
				});
				break;

			case '/help':
				switch (tokens[1]) {
					case 'clear':
						systemMessage('Usage: /clear');
						systemMessage('Clears the current screen.');
						break;
					case 'echo':
						systemMessage('Usage: /echo');
						systemMessage('Dumps out some text to the message pane (seen only by you).');
						break;
					case 'help':
						systemMessage('Usage: /help (optional context)');
						systemMessage('Displays a list of available commands or explains about a specific command.');
						break;
					case 'history':
						systemMessage('Usage: /history');
						systemMessage("Opens a new window with this room's history.");
						break;
					case 'img': // Intentional fallthroughs
					case 'image':
						systemMessage('Usage: /img url');
						systemMessage('Displays an image.');
						break;
					case 'mp3':
						systemMessage('Usage: /mp3 url_of_mp3_file');
						systemMessage('Embeds a flash player that plays your mp3 file.');
						break;
					case 'search':
						systemMessage('Usage: /search search_term');
						systemMessage('Searches for a search string and outputs info about it.');
						break;
					case 'seen':
						systemMessage('Usage: /seen username');
						systemMessage('Tells you when a user last spoke in this room.');
						break;
					case 'tgen':
					case 'tgen':
						systemMessage('Usage: /tgen ("best" or number_of_words)');
						systemMessage('Displays said number of TGEN words or pulls from the "best" file.');
						break;
					case 'theme':
						systemMessage('Usage: /theme name_of_theme');
						systemMessage('Changes your theme. Current options: default, experimental, josh');
						break;
					case 'time':
						systemMessage('Usage: /time (epoch seconds or string date)');
						systemMessage('Converts the time between epoch seconds and string date.');
						break;
					case 'topic':
						systemMessage('Usage: /topic new topic');
						systemMessage("Sets the room's topic.");
						break;
					case 'yt': // Intentional fallthroughs
					case 'youtube':
						systemMessage('Usage: /yt youtube_video_id');
						systemMessage('Embeds a youtube video.');
						break;
					default:
						systemMessage('Current commands are: /clear /echo /help /history /img /image /mp3 /search /seen /tgen /theme /time /topic /yt /youtube');
						break;
				}
				break;

			case '/history':
				window.open(location.href + '/history');
				break;

			case '/theme':
				setTheme(tokens[1]);
				break;

			default:
				queue.push(msg);
				break;

		}

	};

	var printf = function() {

		var str = arguments[0];

		for (var i = 1; i < arguments.length; i++) {
			str = str.replace(/%s/, arguments[i]);
		}

		return str;

	};

	var resize = function() {

		var heights = {
			doc : $(window).height(),
			controls : $('#controls').height()
		};

		var height = parseInt(heights.doc - (heights.controls + HEIGHT_BUFFER));

		$('#chats ul').css('height', height + 'px');

	};

	var scrollIntoView = function() {

		if (ops.mouseDown) return;

		var el = document.getElementById('chats').getElementsByTagName('ul')[0];
		el.scrollTop = el.scrollHeight;
		el.scrollTop = el.scrollHeight;

	};

	var setOnline = function(users) {

		var el = document.getElementById('users').getElementsByTagName('ul')[0],
			offline,
			html = '';

		for (var i = 0; i < users.length; i++) {

			if (users[i].offline_text) {
				offline = printf(' <span class="date">(%s)</span>', users[i].offline_text);
			} else {
				offline = '';
			}

			html += printf(
				'<li class="%s" title="%s">%s%s</li>',
				users[i].status_text,
				users[i].hostname,
				users[i].name,
				offline
			);

		}

		el.innerHTML = html;

	};

	var setTheme = function(theme) {

		if (!theme) {
			theme = $.cookie(ops.roomName + '_theme') || 'default';
			systemMessage(printf('Your theme is currently "%s"', theme));
			return;
		}

		switch (theme) {
			case 'default':
			case 'experimental':
			case 'josh':
				$.cookie(ops.roomName + '_theme', theme);
				killCSS();
				loadCSS('/static/css/' + theme + '.css');
				systemMessage('Loading theme &#8230;');
				break;
			default:
				systemMessage('Invalid theme!');
				parseMessage('/help theme');
				break;
		}

	};

	var systemMessage = function(msg) {

		addMessage({
			className : 'system',
			name : 'system',
			message : msg
		});

	};

	var unique = function(len) {

		var i, key = '';

		len = len || 10;

		for (i = 0; i < len; i++) {
			key += String.fromCharCode(DGE.rand(97, 122));
		}

		return key;

	};

	var update = function() {

		var success = function(data) {

			var i;

			if (!data) return;

			try {

				eval('var res = ' + data);

				for (i = 0; i < res.commands.length; i++) {
					execCommand(res.commands[i]);
				}

				for (i = 0; i < res.messages.length; i++) {
					addMessage(res.messages[i]);
				}

				ops.lastMessageId = res.last_message_id || ops.lastMessageId;
				newMessage.on(res.messages.length);
				setOnline(res.users);

			} catch(e) {
				error.notice(e);
			};

		};

		var status = null;

		status = 1;
		if (ops.focus) {
			if ($('#message').val()) {
				status = STATUS_TYPING;
			} else {
				status = STATUS_ONLINE;
			}
		} else {
			status = STATUS_AWAY;
		}

		$.ajax({
			complete : function() {
				setTimeout(update, UPDATE_INTERVAL);
			},
			data : {
				'messages[]' : queue,
				'name' : user.name,
				'status' : status
			},
			success : success,
			type : 'POST',
			url : printf(URL_UPDATE, ops.roomName, ops.lastMessageId),
		});

		queue = [];

	};

	var upload = function(e) {

		var form = $('#controls').get(0),
			iframe = $('#iframe-upload').get(0);

		form.target = iframe.name;
		form.submit();
		$(this).val('');

	};

	return {
		init : init
	};

})();
