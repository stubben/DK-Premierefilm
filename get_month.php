<?php
header('Content-Type: text/html; charset=ISO-8859-1');
function _get_month_number ($monthName) {
	$monthNameArray = array (
		'01' => "Januar",
		'02' => "Februar",
		'03' => "Marts",
		'04' => "April",
		'05' => "Maj",
		'06' => "Juni",
		'07' => "Juli",
		'08' => "August",
		'09' => "September",
		'10' => "Oktober",
		'11' => "November",
		'12' => "December"
	);
	$monthNumber = array_search($monthName, $monthNameArray);
	return $monthNumber;
}

function _decode_string_array ($stringArray) {
	$s = unserialize(gzuncompress(stripslashes(base64_decode(strtr($stringArray, '-_,', '+/=')))));
	return $s;
}

/**
print_r($_GET);
Array
(
    [y] => 2013
    [m] => Maj
    [lc] => 1368487801
)
*/
$selected_month = _get_month_number($_GET['m']);
if (file_exists('txt/'.$_GET['lc'].'.txt')) {
	$m = file_get_contents('txt/'.$_GET['lc'].'.txt');
	$movies_year_month_day 	= _decode_string_array($m);
	if (!empty($movies_year_month_day[$_GET['y']])) {
		foreach ($movies_year_month_day[$_GET['y']] as $month => $days) {
			if ($month == $selected_month) {
				print "<div class=\"row\">\n";
				print '<div class="cell h1_month">';
				print '<h1 id="'.$_GET['m'].'">'.$_GET['m']."</h1>\n";
				print '</div>';
				print '
							<div class="cell">
								<div class="pls ptl md_cur_t1">
									<div class="md_arr_t2 md_arr_t2 js_go_to_top md_bxsh_t1">
										<div class="pat">Top</div>
									</div>
								</div>
							</div>
				';
				print '</div>';
				foreach ($days as $day => $titles) {
					print '<h2>'.$day.'</h2>';
					print '<ul>';
					foreach ($titles as $k => $title) {
						print '<li class="imdb"><a title="'.$title.'" href="http://www.imdb.com/find?q='.$title.'&s=all">'.$title."</a></li>\n";
					}
					print '</ul>';
				}
			}
		}
	}
}
?>
<script>
$('.js_go_to_top').on("click",function(){
	$('html,body').animate({scrollTop: 0},400);
});
</script>