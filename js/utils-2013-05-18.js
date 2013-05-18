$(document).ready(function() {
	/**
	$('.imdb').click(function(){
		var title = $(this).find('div').attr("title");
		$('.imdb_title').html(title);
		$('.imdb_poster').find('img[class*=imdb_id], .no_image').remove();
	 	$.ajax({
			//url: "http://www.imdbapi.com/?t=" + titles[i],
			url: "http://www.imdbapi.com/?t="+title,
			dataType: 'jsonp',
			success: function(data) {
				if (data.Poster) {
					$('.imdb_poster').append('<img class="imdb_id_'+data.imdbID+'" src="'+data.Poster+'" width="50px">');
				} else {
					$('.imdb_poster').append('<div class="no_image">No image</div>');
				}
			},
		    error: function(data) {
		        alert('I\'m sorry Dave, I can\'t do that.');
		    },
		    complete: function(xhr, data) {
		        if (xhr.status == 0) {
		            //alert('fail');
		        } else {
		            //alert('complete');
		        }
		    }
	    });
	});
	*/
	$('.md_month').find("a").on("click",function(e){
		e.preventDefault();
		var link = $(this).attr('rel');
		var link_h1 = $('#'+link);
		$('html,body').animate({scrollTop: link_h1.offset().top},400);
	});
	$('.js_go_to_top').click(function(){
		$('html,body').animate({scrollTop: 0},400);
	});
});