var entityMap = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': '&quot;',
    "'": '&#39;',
    "/": '&#x2F;'
  };

  function escapeHtml(string) {
    return String(string).replace(/[&<>"'\/]/g, function (s) {
      return entityMap[s];
    });
  }


var EQdkpChat = new function(){
	var windowFocus = true;
	var oldtitle = document.title;
	var titleInterval;
	var unreadChats = new Array();
	var onlineUsers = new Array();
	var mybreak = false;
	var minimizeLock = false;
	var blnIsPubMod = false;
	
	var check_new_interval, onlinelist_interval;
	
	function loadOnlineList(){
		$.get(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&userlist", function(data){
			if (data && data.count != undefined){
				$(".chatOnlineCount").html(data.count);
			}
			if (data && data.html != undefined){
				$(".chatOnlineList").html(data.html);
			}
			
			if (data && data.unread  != undefined){
				$.each(data.unread, function(i,key){
					if ($.inArray(key, unreadChats) == -1){
						unreadChats.push(key);
					}
				})
				updateUnreadWindows();
			}
			//OnlineUsers
			onlineUsers = data.user;
			$('#chatWindowList').find('.eqdkp-icon-online').remove();
			
			$(".chatWindowContainer").each(function(v){
				var key = $(this).attr("data-chat-id");
				var count = $(this).attr("data-user-count");
				if (key != "" && count < 3) {
					var title = $(".chat-"+key+" .chatWindow .chatWindowHeader span").html();
					title = $.trim(title);
					if (title && title != ""){
						if($.inArray(title, onlineUsers) >= 0){
							title = '<i class="eqdkp-icon-online" style="vertical-align: middle;"></i> '+title;
							$(".chat-"+key+" .chatWindow .chatWindowHeader span").html(title);
						}
					}
					
				}
			});
		});
	}
	
	this.loadOnlineList = loadOnlineList;
	
	this.init = function (intReloadTime, intRelodOnlinelist, isPubMod) {	
		loadOnlineList();
		loadOpenConversations();
		$(document).ready(function(){
			check_new_interval = window.setInterval("EQdkpChat.checkNew()", 1000*intReloadTime); //Seconds
			onlinelist_interval = window.setInterval("EQdkpChat.loadOnlineList()", 1000*60*intRelodOnlinelist); //Minutes
		})
		
		blnIsPubMod = isPubMod;
		
		bindActions();
		
		$(window).focus($.proxy(function() {
			windowFocus = true;
			stopBlinkTitle();
		}, this)).blur($.proxy(function() {
			windowFocus = false;
		}, this));
		
		$("#chatOnlineMinimized").on("click", function(){
			$("#chatOnlineMinimized").hide();
			$("#chatOnlineMaximized").show();
		});
		$("#chatOnlineMaximized .fa-times").on("click", function(){
			$("#chatOnlineMinimized").show();
			$("#chatOnlineMaximized").hide();
		});
		
		$("#chatOnlineSearch").bind("keyup change", function(){
			var searchname = $("#chatOnlineSearch").val();
			if (searchname == "") {
				$(".chatOnlineList li").show();
			} else {
				$(".chatOnlineList li").each(function(k, v){
					var username = $(this).find(".chat_username").html();
					if (username != undefined){
						var result = username.match(new RegExp(searchname, "i"));
						if (result){ $(this).show();} else {$(this).hide();}
					}
				});
			}
		});	
	}
	
	this.addUser = function(key){				
		$.get(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&getUser&key="+key,function(data){
			$(".chat-"+key+" .chatWindowAddUser").show();
			$(".chat-"+key+" .token-input-list-facebook, .chat-"+key+" .token-input-dropdown-facebook").remove();
			$(".chat-"+key+" .demo-input-local").tokenInput(data.user, {prePopulate: data.prepopulate, preventDuplicates: true,theme: "facebook"});
		});
	}
	
	this.addUserSubmit = function(key){
		var myuser = $(".chat-"+key+" .demo-input-local").val();
		this.closeConversation(key);
		$(".chat-"+key+" .chatWindowAddUser").hide();
		
		$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&addUser", {user: myuser}, function(data){
			if(data.count < 2) return;
			
			var key = data.key;
			openChatWindow(key, data.title, data.count);
			addMessages(key, data, 0);
			bindActions();
			unfocusAllWindows();
			focusWindowByKey(key);
		});
		
	}
	
	this.closeConversation = function (key){
		$(".chatContainer div[data-chat-id=\'"+key+"\']").remove();
		$.get(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&closeConversation&key="+key);
	}
	
	this.minConversation = function (key){
		$.get(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&minConversation&key="+key);
		minimizeConversation(key);
	}
	
	this.deleteMessage = function (messageID){
		$("div[data-post-id=\'"+messageID+"\']").remove();
		$.get(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&delete&msg="+messageID);
	}
	
	this.markAsRead = markAsRead;
	
	this.checkNew = checkNew;
	
	this.editTitleSubmit = editTitleSubmit;
	
	this.archiveGroupConversation = archiveGroupConversation;
	
	this.leaveConversation = function(key){
		this.closeConversation(key);
		$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&leaveConversation", {key: key}, function(data){
		});
	};
	
	function archiveGroupConversation(obj, key){
		$(obj).parent().parent().remove();
		$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&archiveGroupConv", { key: key});
	}
	
	function editTitleSubmit(key, title){
		$(".chat-"+key+ " .editTitleInput").remove();
		$(".chat-"+key+ " .chatWindowHeader span").html(title);
		
	}
	
	function markAsRead(key){
		$.get(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&markRead&key="+key);
	}
	
	
	this.openNewChat = function(key, title, user){
		openChatWindow(key, title, user.length);
		$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&openConversation", { key: key, 'user[]': user }, function(data){
			addMessages(key, data, 0);
			bindActions();
			unfocusAllWindows();
			focusWindowByKey(key);
		});	
	}
	
	function checkNew(){
		var open = new Array();
		var tsp = new Array();
		$(".chatWindowContainer").each(function(v){
			var key = $(this).attr("data-chat-id");
			if (key != "") {
				open.push(key);
				tsp.push($(this).find(".chatLastMessage-"+key).html());
			}
		});
		
		$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&checkNew", {'open[]': open, 'tsp[]': tsp}, function(data){
			$(".chatContainer, .chatBigContainer").find(".chatTmpPost").remove();
			if (data.new_messages != undefined){
				$.each(data.new_messages, function(key, value){
					var unread = addMessages(key, value, 1);
					var opened = $(".chat-"+key).attr("data-opened");
					if (opened == "1") return true;
					
					if (unread > 0 && key != "guildchat"){
						markWindowAsUnread(key);
						
						if (!$(".chatContainer .chat-"+key+" .chatWindow").hasClass("active")){
							blinkHeader(key);
							blinkTitle();
						} else {
							$(".chat-"+key).find(".chatNewPost").addClass("noNewPost");
							$(".chat-"+key).find(".chatNewPost").removeClass("chatNewPost");
						}
					}
				});
			}
			
			if(data.new_reed != undefined){
				$.each(data.new_reed, function(k, key){
					$(".chat-"+key).find(".chatReed").remove();
					var usercount = $(".chat-"+key).attr("data-user-count");
					
					if(usercount =="2" && $(".chatLastMessageByMe-"+key).html() == "1"){
						$(".chatMessages-"+key).append('<div class="chatReed"><i class="fa fa-check"></i> Read</div>');
						$(".chat-"+key+" .chatWindowContent").scrollTop($(".chat-"+key+" .chatWindowContent")[0].scrollHeight);
					}
				});
			}
		
			if(data.new_windows != undefined){
				$.each(data.new_windows, function(k, v){
					key = v.key;
					openChatWindow(key, v.title, v.count);
					$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&loadLatestMessages", { key: key}, function(data){
						var unread = addMessages(key, data, 1);
						blinkHeader(key);
						blinkTitle();
						bindActions();
					});
					markWindowAsUnread(key);
				});
			}
		});
	}
		
	function addMessages(key, data, markUnread){
		var unread = 0;
		
		if (data && data.messages != undefined){
			$.each(data.messages, function(k,v){
				if ($(".chatContainer .chatMessages-"+key).find("div[data-post-id='"+v.id+"']").length == 0){				
					if (v.reed == 0 && markUnread && v.user_id != mmocms_userid){
						var newpost = " chatNewPost";
						unread = unread +1;
					} else {
						var newpost = "";								
					}
					if(v.user_id == mmocms_userid) { var mine = ' mine'; } else { var mine = ''; }
					var html = '<div class="chatPost'+newpost+mine+'" data-post-id="'+v.id+'"><div class="chatAvatar" title="'+v.username+'"><a href="'+v.profile+'">'+v.avatar+'</a></div><div class="chatMsgContainer"><div class="chatUsername">'+v.username+'</div><div class="chatTime">'+v.date+'</div><div class="chatMessage">'+v.text+'</div></div><div class="clear"></div></div>';
					$(".chatContainer .chatMessages-"+key).append(html);
				}
				
				//Now for Big Container
				if ($(".chatBigContainer .chatMessages-"+key).find("div[data-post-id='"+v.id+"']").length == 0){				
					if (v.reed == 0 && markUnread && v.user_id != mmocms_userid){
						var newpost = " chatNewPost";
						unread = unread +1;
					} else {
						var newpost = "";								
					}
					var deleteIcon = (key == 'guildchat' && blnIsPubMod) ? '<span class="chatDeleteContainer"><i class="hand fa fa-times-circle icon-grey" onclick="EQdkpChat.deleteMessage('+v.id+')"></i></span>' : '';
					
					if(v.user_id == mmocms_userid) { var mine = ' mine'; } else { var mine = ''; }
					var html = '<div class="chatPost'+newpost+mine+'" data-post-id="'+v.id+'"><div class="chatAvatar" title="'+v.username+'"><a href="'+v.profile+'">'+v.avatar+'</a></div><div class="chatMsgContainer"><div class="chatUsername">'+v.username+'</div><div class="chatTime">'+v.date+'</div><div class="chatMessage">'+deleteIcon+v.text+'</div></div><div class="clear"></div></div>';
					$(".chatBigContainer .chatMessages-"+key).append(html);
				}
			});						
		}
		if (data && data.lasttime != undefined){
			$(".chatLastMessage-"+key).html(data.lasttime);
		}
		if (data && data.lastbyme != undefined){
			$(".chatLastMessageByMe-"+key).html(data.lastbyme);
		}
		$(".chat-"+key+" .chatWindowContent").scrollTop($(".chat-"+key+" .chatWindowContent")[0].scrollHeight);
		if (data.lastbyme == "1") return 0;
		return unread;
	}
	
	function editTitle(obj){
		var key = $(obj).parent().parent().parent().attr("data-chat-id");
		var usercount = $(obj).parent().parent().parent().attr("data-user-count");
		if (usercount != "2" && usercount != "1"){
			var value = $(obj).html();
			$(obj).html('<input type="text" value="'+value+'" style="width: 95%" class="editTitleInput" onblur="EQdkpChat.editTitleSubmit(\''+key+'\', this.value)">');
			$(obj).find('input').focus();
			
		}
	}
	
	function markWindowAsUnread(key){
		$(".chatContainer .chat-"+key).attr("data-chat-unread", 1);
		if ($.inArray(key, unreadChats) == -1){
			unreadChats.push(key);
		}
		
		updateUnreadWindows();
	}
	
	function markWindowAsRead(key){
		$(".chatContainer .chat-"+key).removeAttr("data-chat-unread");
		var pos = $.inArray(key, unreadChats);
		if (pos !== -1){
			unreadChats.splice(pos, 1);
		}
		updateUnreadWindows();
	}
	
	function updateUnreadWindows(){
		var count = 0;
		count = unreadChats.length;
		/*
		$(".chatWindowContainer").each(function(){
			if ($(this).attr("data-chat-unread") == "1"){
				count = count + 1;
			}
		})*/
		if (count == 0) {
			$(".chat-tooltip-container .notification-bubble-green").html("");
		} else {
			$(".chat-tooltip-container .notification-bubble-green").html(count);
		}	
	}
	
	function bindActions(){
		$(".chatWindow").on("click", function(e){
			focusWindow(this);
			
			var parent = $(this).parent();
			if(parent.hasClass('chatMinimized')){
				var key = $(this).parent().attr("data-chat-id");

				if(key != minimizeLock){
					maximizeConversation(key);
				}
			}
			minimizeLock = false;
		});
		
		$(".chatWindowHeader span").on("dblclick", function(e){
			editTitle(this);
		});
		
        $(document).on("keyup blur", ".chatInputSubmit", function(e){
			e.preventDefault();
            var value = $(this).val();
            if(value == "") $(this).height(20);

            while($(this).outerHeight() < this.scrollHeight) {
				$(this).height($(this).height()+20);
			};

			var key = $(this).parent().parent().parent().attr("data-chat-id");
			
            if (e.which == 13 && value != "") {
            	if (e.ctrlKey){
            		if (mybreak){
            			value += "\n";
            			$(this).val(value);
            			mybreak = false;
            		} else {
            			mybreak = true;
            		}
            	} else {
            	
	            	if (value != "\n"){
	            		$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&save", { key: key, txt: value });
	            		var html = '<div class="chatPost chatTmpPost mine"><div class="chatAvatar"><i class="fa-spin fa fa-spinner fa-lg"></i></div><div class="chatMsgContainer"><div class="chatTime">now</div><div class="chatMessage">'+escapeHtml(value)+'</div></div><div class="clear"></div></div>';
	            		$(".chatMessages-"+key).append(html);
	            		$(".chat-"+key).find(".chatReed").remove();
	            		$(".chat-"+key+" .chatWindowContent").scrollTop($(".chat-"+key+" .chatWindowContent")[0].scrollHeight);
	            	}
	
					$(this).val("");
				    $(this).height(20);
			    
            	}
			}
        });
        
        $(document).on("click", function(e){							
			var classname = e.target.className;
			if(classname.substring(0,4) != "chat") {
				unfocusAllWindows();
			}
		});
	}
	
	function unfocusAllWindows(){
		$(".chatWindow").removeClass("active");
	}
	
	function focusWindow(obj){
		unfocusAllWindows();
		$(obj).addClass("active");

		$(obj).find(".chatNewPost").addClass("noNewPost");
		
		$(obj).find(".chatWindowHeader").removeClass('blinkingHeader');
		stopBlinkTitle();
		
		var key = $(obj).parent().attr("data-chat-id");
		var lastbyme = $(".chatLastMessageByMe-"+key).html();
		
		markWindowAsRead(key);
		var usercount = $(".chat-"+key).attr("data-user-count");
		if (lastbyme == "0" || usercount !="2") markAsRead(key);
	}
	
	function focusWindowByKey(key){
		$(".chatWindow").removeClass("active");
		$(".chat-"+key).find(".chatWindowHeader").removeClass('blinkingHeader');
		$("#chatInput-"+key).parent().parent().addClass("active");
		$("#chatInput-"+key).focus();
		
		$(".chat-"+key).find(".chatNewPost").addClass('.noNewPost');
		stopBlinkTitle();
		updateUnreadWindows();
		
		var lastbyme = $(".chatLastMessageByMe-"+key).html();
		var usercount = $(".chat-"+key).attr("data-user-count");
		if (lastbyme == "0" || usercount !="2") markAsRead(key);
	}
	
	function loadOpenConversations(){
		$.get(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&loadOpenConversations", function(data){
			unfocusAllWindows();
			$.each(data, function(key, v){
				openChatWindow(key, v.title, v.count, v.minimized);
				$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&loadLatestMessages", { key: key}, function(data){
					var unread = addMessages(key, data, 1);
					$(".chat-"+key).removeAttr("data-opened");
					if (unread > 0){
						blinkHeader(key);
						blinkTitle();			
						markWindowAsUnread(key);
					}
				});
			})
			bindActions();	
		});
	}
	
	function openChatWindow(key, title, count, minimized){
		if ($("#chatWindowList").find(".chat-"+key).length == 0){
			var icon = "";

			if (count <= 2){
				//is he online?

				if($.inArray(title, onlineUsers) >= 0){
					icon = '<i class="eqdkp-icon-online" style="vertical-align: middle;"></i> ';
				}
			} else {
				//group icon
				//icon = '<i class="fa fa-group"  style="vertical-align: middle;"></i> ';
			}
			if(count > 2){
				var leave = '<br /><a href="javascript:EQdkpChat.leaveConversation(\''+key+'\')"><i class="fa fa-user-times"></i> Leave Conversation</a>';
			} else var leave = '';
			
			var minClass = (minimized) ? ' chatMinimized' : '';
			var html = '<div class="chatWindowContainer chat-'+key+minClass+'" data-chat-id="'+key+'" data-user-count="'+count+'" data-opened="1"><div class="chatWindow"><div class="chatWindowHeader"><span>'+icon+title+'</span><i class="fa fa-times floatRight hand" onclick="EQdkpChat.closeConversation(\''+key+'\')" style="margin-right: -5px; margin-left: 5px;"></i><i class="fa fa-chevron-down floatRight hand chatMinConvIcon" onclick="EQdkpChat.minConversation(\''+key+'\')" style="margin-right: 0px; margin-left: 5px;"></i><i class="fa fa-user-plus floatRight hand" onclick="EQdkpChat.addUser(\''+key+'\')"></i></div><div class="chatWindowAddUser" style="display:none;"><input type="text" class="demo-input-local" name="blah" /><button type="button" onclick="EQdkpChat.addUserSubmit(\''+key+'\');"><i class="fa fa-check"></i> Absenden</button><br />'+leave+'</div><div class="chatWindowContent"><span class="chatMessages-'+key+'"></span><div class="clear"></div></div><div class="chatInput"><textarea id="chatInput-'+key+'" class="chatInputSubmit" style="overflow: hidden; word-wrap: break-word; resize: none;"></textarea></div><div class="clear"></div><div class="chatLastMessage-'+key+'" style="display:none;">0</div><div class="chatLastMessageByMe-'+key+'" style="display:none;">0</div></div></div>';				
			$("#chatWindowList").append(html);
		}
	}
	
	function blinkTitle(){
		if (!windowFocus) {
			var isOldTitle = false;
			var newTitle = "New Chatmessage";

			titleInterval = self.setInterval(function(){
				document.title = isOldTitle ? oldtitle : newTitle;
				isOldTitle = !isOldTitle;
			},1000);

			$(window).focus($.proxy(function() {
				window.clearInterval(titleInterval);
				document.title = oldtitle;
			}, this));
		}
	}
	
	function stopBlinkTitle(){
		document.title = oldtitle;
		window.clearInterval(titleInterval);
		windowFocus = true;
	}
	
	function blinkHeader(key){
		var header = $(".chat-"+key+" .chatWindowHeader");
		$(header).addClass('blinkingHeader');
	}
	
	function minimizeConversation(key){
		minimizeLock = key;
		$(".chat-"+key).addClass('chatMinimized');
		$(".chat-"+key).removeAttr("data-opened");
	}
	
	function maximizeConversation(key){
		$(".chat-"+key).removeClass('chatMinimized');
		$.get(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&maxConversation&key="+key);
	}
};
