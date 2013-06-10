$(document).ready(function() {
	$.ajax({
		url: "update_db.php",
		data: {url: url},
		type: 'post',
		success: function(data) {}
	});
	$('.md_month').find("a").on("click",function(e){
		$('html,body').animate({scrollTop: 0},400);
		$('#fetching-data').css({'position': 'relative', 'left': '50%', 'top': '100px'}).show();
		e.preventDefault();
		var month = $(this).attr('rel');
		$('.month_selected').empty();
		var request = $.ajax({
			type: "GET",
			timeout: 5000,
			url: 'get_month.php',
			data: {y: year, m: month, lc: last_change},
			success: function(data) {
				$('.month_selected').html(data);
			},
			error: function() {
				$('.month_selected').html('<div class="pal">Der er noget galt... Aaarrggh!</div>');	
			}
		});	
		request.done(function() {
			$('#fetching-data').hide();
			$('html,body').animate({scrollTop: $('.month_selected').offset().top},400);
		});			
	});
	$('.js_go_to_top').on("click",function(){
		$('html,body').animate({scrollTop: 0},400);
	});
});