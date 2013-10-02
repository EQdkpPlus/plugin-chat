var EQdkpChat = new function(){
	var windowFocus = true;
	var oldtitle = document.title;
	var titleInterval;
	var unreadChats = new Array();
	
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
			
		});
	}
	
	this.loadOnlineList = loadOnlineList;
	
	this.init = function () {
		
		loadOnlineList();
		loadOpenConversations();
		$(document).ready(function(){
			check_new_interval = window.setInterval("EQdkpChat.checkNew()", 5000);
			onlinelist_interval = window.setInterval("EQdkpChat.loadOnlineList()", 1000*60*5);
		})
		
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
		$("#chatOnlineMaximized .icon-remove").on("click", function(){
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
	
	this.markAsRead = markAsRead;
	
	this.checkNew = checkNew;
	
	this.editTitleSubmit = editTitleSubmit;
	
	this.archiveGroupConversation = archiveGroupConversation;
	
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
							$(".chat-"+key).find(".chatNewPost").animate({
								backgroundColor: "#f9f9f9"
							}, 800 );
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
						$(".chatMessages-"+key).append('<div class="chatReed"><i class="icon-ok"></i> Gelesen</div>');
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
					if (v.reed == 0 && markUnread){
						var newpost = " chatNewPost";
						unread = unread +1;
					} else {
						var newpost = "";								
					}
					var html = '<div class="chatPost'+newpost+'" data-post-id="'+v.id+'"><div class="chatTime">'+v.date+'</div><div class="chatAvatar" title="'+v.username+'">'+v.avatar+'</div><div class="chatMessage">'+v.text+'</div><div class="clear"></div></div>';
					$(".chatContainer .chatMessages-"+key).append(html);
				}
				
				//Now for Big Container
				if ($(".chatBigContainer .chatMessages-"+key).find("div[data-post-id='"+v.id+"']").length == 0){				
					if (v.reed == 0 && markUnread){
						var newpost = " chatNewPost";
						unread = unread +1;
					} else {
						var newpost = "";								
					}
					var html = '<div class="chatPost'+newpost+'" data-post-id="'+v.id+'"><div class="chatTime">'+v.date+'</div><div class="chatAvatar" title="'+v.username+'">'+v.avatar+'</div><div class="chatMessage">'+v.text+'</div><div class="clear"></div></div>';
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
			$(".chat-tooltip-container .notification-bubble-red").html("");
		} else {
			$(".chat-tooltip-container .notification-bubble-red").html(count);
		}	
	}
	
	function bindActions(){
		$(".chatWindow").on("click", function(e){
			focusWindow(this);
		});
		
		$(".chatWindowHeader span").on("dblclick", function(e){
			editTitle(this);
		});
		
        $(document).on("keyup blur", ".chatInputSubmit", function(e){
			e.preventDefault();
            var value = $(this).val();        

            while($(this).outerHeight() < this.scrollHeight) {
				$(this).height($(this).height()+20);
			};

			var key = $(this).parent().parent().parent().attr("data-chat-id");
			
            if (e.which == 13 && value != "") {
            	if (value != "\n"){
            		$.post(mmocms_root_path+ "plugins/chat/ajax.php"+mmocms_sid+"&save", { key: key, txt: value });
            		var html = '<div class="chatPost chatTmpPost"><div class="chatTime">jetzt</div><div class="chatAvatar"><i class="icon-spin icon-spinner icon-large"></i></div><div class="chatMessage">'+value+'</div><div class="clear"></div></div>';
            		$(".chatMessages-"+key).append(html);
            		$(".chat-"+key).find(".chatReed").remove();
            		$(".chat-"+key+" .chatWindowContent").scrollTop($(".chat-"+key+" .chatWindowContent")[0].scrollHeight);
            	}

				$(this).val("");
			    $(this).height(20);	
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
		$(".chatWindowHeader").css("backgroundColor", "#b0b0b0");
	}
	
	function focusWindow(obj){
		unfocusAllWindows();
		$(obj).addClass("active");

		$(obj).find(".chatNewPost").animate({backgroundColor: "#f9f9f9"}, 700).removeClass("chatNewPost");
		$(obj).find(".chatWindowHeader").stop().css("backgroundColor", "#2E78B0");
		stopBlinkTitle();
		
		var key = $(obj).parent().attr("data-chat-id");
		var lastbyme = $(".chatLastMessageByMe-"+key).html();
		
		markWindowAsRead(key);
		var usercount = $(".chat-"+key).attr("data-user-count");
		if (lastbyme == "0" || usercount !="2") markAsRead(key);
	}
	
	function focusWindowByKey(key){
		$(".chatWindow").removeClass("active");	
		$(".chat-"+key).find(".chatWindowHeader").css("backgroundColor", "#2E78B0");
		$("#chatInput-"+key).parent().parent().addClass("active");
		$("#chatInput-"+key).focus();
		$(".chat-"+key).find(".chatNewPost").animate({
			backgroundColor: "#f9f9f9"
		}, 700 );
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
				openChatWindow(key, v.title, v.count);
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
	
	function openChatWindow(key, title, count){
		if ($("#chatWindowList").find(".chat-"+key).length == 0){
			var html = '<div class="chatWindowContainer chat-'+key+'" data-chat-id="'+key+'" data-user-count="'+count+'" data-opened="1"><div class="chatWindow"><div class="chatWindowHeader"><span>'+title+'</span><i class="icon-remove floatRight hand" onclick="EQdkpChat.closeConversation(\''+key+'\')" style="margin-right: -5px; margin-left: 5px;"></i><i class="icon-plus floatRight hand" onclick="EQdkpChat.addUser(\''+key+'\')"></i></div><div class="chatWindowAddUser" style="display:none;"><input type="text" class="demo-input-local" name="blah" /><button type="button" onclick="EQdkpChat.addUserSubmit(\''+key+'\');"><i class="icon-ok"></i> Absenden</button></div><div class="chatWindowContent"><span class="chatMessages-'+key+'"></span><div class="clear"></div></div><div class="chatInput"><textarea id="chatInput-'+key+'" class="chatInputSubmit" style="overflow: hidden; word-wrap: break-word; resize: none;"></textarea></div><div class="clear"></div><div class="chatLastMessage-'+key+'" style="display:none;">0</div><div class="chatLastMessageByMe-'+key+'" style="display:none;">0</div></div></div>';				
			$("#chatWindowList").append(html);
		}
	}
	
	function blinkTitle(){
		if (!windowFocus) {
			var isOldTitle = false;
			var newTitle = "Neue Chat-Nachricht";

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
	}
	
	function blinkHeader(key){
		var header = $(".chat-"+key+" .chatWindowHeader");
		$(header).animate({backgroundColor:"#2B619C"}, 700, $.proxy(function() {
			$(header).animate({backgroundColor:"#4086D4"}, 700, $.proxy(function() {
				blinkHeader(key);
			}, this));
		}, this));
	}

}