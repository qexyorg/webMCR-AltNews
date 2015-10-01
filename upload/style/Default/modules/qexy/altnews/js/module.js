$(function(){

	function getUrlParam(name){
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);

		return (results == null) ? null : decodeURIComponent(results[1].replace(/\+/g, " "));
	}

	var csrf_key = $('.altnews').attr('data-csrf');

	$('body').on('click', '.altnews .altnews-a-vote a', function(){

		var nid = $(this).closest('.altnews-a-vote').attr('data-id');
		var value = $(this).attr('data-value');

		$.ajax({
			url: "api.php?do=altnews&op=news_likes",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "apicsrf=" + csrf_key+"&nid="+nid+"&value="+value,

			error: function(data){
				alert('Ошибка голосования!');
				return false;
			},

			success: function(data){
				if(data.type===false){ alert(data.msg); return false; }

				$('.altnews .altnews-a-vote[data-id="'+nid+'"] > li > a[data-value="1"] > span').text(data.data.likes).hide().show('fast');
				$('.altnews .altnews-a-vote[data-id="'+nid+'"] > li > a[data-value="0"] > span').text(data.data.dislikes).hide().show('fast');
			}
		});

		return false;
	});

	$('body').on('click', '.altnews .altnews-comments-form #comment-add', function(){

		var form = $(this).closest('.altnews-comments-form');
		var textarea = form.children('textarea');

		var nid = getUrlParam('nid');
		var message = textarea.val();

		var count = $('.altnews .altnews-full li.news-comments > span').text();
		count = parseInt(count);

		$.ajax({
			url: "api.php?do=altnews&op=news_comment_add",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "apicsrf=" + csrf_key+"&nid="+nid+"&message="+message,

			error: function(data){
				alert('Ошибка добавления комментария!');
				return false;
			},

			success: function(data){
				if(data.type===false){ alert(data.msg); return false; }

				textarea.val('');

				$('.altnews .altnews-comments > .content').prepend(data.data.comment).hide().fadeIn('normal');
				$('.altnews .altnews-full li.news-comments > span').text(count+1);

				$("[rel='tooltip']").tooltip({container: 'body'});

				$('html, body').stop().animate({
					scrollTop: $('.altnews .altnews-comments > .content').offset().top-50
				}, 0);
			}
		});

		return false;
	});

	$('body').on('click', '.altnews .altnews-comments .remove-comment', function(){

		if(!confirm('Вы уверены, что хотите удалить выбранный комментарий?')){ return false; }

		var comment = $(this).closest('.altnews-comments-id');
		var id = comment.attr('data-id');
		var nid = getUrlParam('nid');

		var count = $('.altnews .altnews-full li.news-comments > span').text();
		count = parseInt(count);

		$.ajax({
			url: "api.php?do=altnews&op=news_comment_remove",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "apicsrf=" + csrf_key+"&id="+id+"&nid="+nid,

			error: function(data){
				alert('Ошибка удаления комментария!');
				return false;
			},

			success: function(data){
				if(data.type===false){ alert(data.msg); return false; }

				comment.fadeOut('normal', function(){
					$('.altnews .altnews-full li.news-comments > span').text(count-1);
					$(this).remove();
				});
			}
		});

		return false;
	});

	$('body').on('click', '.altnews .altnews-comments .edit-comment, .altnews .altnews-comments .edit-comment-reset', function(){

		var comment = $(this).closest('.altnews-comments-id');
		var id = comment.attr('data-id');
		var that = $(this);

		$.ajax({
			url: "api.php?do=altnews&op=news_comment_get",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "apicsrf=" + csrf_key+"&id="+id,

			error: function(data){
				alert('Ошибка получения комментария!');
				console.log(data);
				return false;
			},

			success: function(data){
				if(data.type===false){ alert(data.msg); return false; }

				if(that.hasClass('edit-comment-reset')){
					$('.altnews-comments-id[data-id="'+id+'"] .text').html(data.data.text_html).hide().fadeIn('normal');
					return false;
				}

				$('.altnews-comments-id[data-id="'+id+'"] .text').html(data.data.bb_panel+'<textarea class="bb-comment input-block-level" rows="6">' +
					data.data.text_bb+'</textarea><p class="text-right">' +
					'<button class="btn btn-primary edit-comment-btn">Сохранить</button> ' +
					'<button class="btn edit-comment-reset">Отмена</button></p>').hide().fadeIn('normal');
			}
		});

		return false;
	});

	$('body').on('click', '.altnews .altnews-comments .edit-comment-btn', function(){

		var comment = $(this).closest('.altnews-comments-id');
		var id = comment.attr('data-id');
		var message = $('.altnews-comments-id[data-id="'+id+'"] .text textarea').val();

		$.ajax({
			url: "api.php?do=altnews&op=news_comment_edit",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "apicsrf=" + csrf_key+"&id="+id+"&message="+message,

			error: function(data){
				alert('Ошибка редактирования комментария!');
				return false;
			},

			success: function(data){
				if(data.type===false){ alert(data.msg); return false; }

				$('.altnews-comments-id[data-id="'+id+'"] .text').html(data.data.text).hide().fadeIn('normal');

				$('.altnews-comments-id[data-id="'+id+'"] .altnews-com-id-footer .date-update span').text(data.data.time);
			}
		});

		return false;
	});

});