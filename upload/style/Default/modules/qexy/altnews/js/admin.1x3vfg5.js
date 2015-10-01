$(function(){

	var csrf_key = $('.altnews').attr('data-csrf');

	$('body').on('click', '.altnews .altnews-admin .remove-news', function(){

		if(!confirm('Вы уверены, что хотите удалить выбранную новость?')){ return false; }

		var news = $(this).closest('tr');
		var id = news.attr('data-id');

		$.ajax({
			url: "api.php?do=altnews&op=news_remove",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "apicsrf=" + csrf_key+"&id="+id,

			error: function(data){
				alert('Ошибка удаления новости!');
				return false;
			},

			success: function(data){
				if(data.type===false){ alert(data.msg); return false; }

				news.fadeOut('normal', function(){
					$(this).remove();
				});
			}
		});

		return false;
	});

	$('body').on('click', '.altnews .altnews-admin .remove-category', function(){

		if(!confirm('Удаление категории приведет к переносу всех новостей из этой категории в категорию "Без категории". Вы уверены, что хотите выполнить выбранное действие?')){ return false; }

		var category = $(this).closest('tr');
		var id = category.attr('data-id');

		$.ajax({
			url: "api.php?do=altnews&op=category_remove",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "apicsrf=" + csrf_key+"&id="+id,

			error: function(data){
				alert('Ошибка удаления категории!');
				return false;
			},

			success: function(data){
				if(data.type===false){ alert(data.msg); return false; }

				category.fadeOut('normal', function(){
					$(this).remove();
				});
			}
		});

		return false;
	});

	$('body').on('input change', '.altnews .news-change input[name="img"]', function(){
		var formdata = new FormData();

		formdata.append('img', $(this)[0].files[0]);
		formdata.append('apicsrf', csrf_key);

		$.ajax({
			url: "api.php?do=altnews&op=news_upload_img",
			dataType: "json",
			type: 'POST',
			async: true,
			cache: false,
			processData: false,
			contentType: false,
			data: formdata,

			error: function(data){
				alert('Ошибка загрузки файла!');
				return false;
			},

			success: function(data){
				if(data.type===false){ alert(data.msg); return false; }

				$('.altnews .news-change input[name="img-form"]').val(data.data);
				$('.altnews .news-change #news-img').attr('src', data.data);
			}
		});
	});

});