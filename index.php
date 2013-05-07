<?php
if ((empty ($_REQUEST['showYear'])) ? $_REQUEST['showYear'] = date ("Y") : $_REQUEST['showYear'] = $_REQUEST['showYear']);

// In case of minification of static HTML
function _minify_html($buffer) {
    $search = array(
        '/\>[^\S ]+/s', //strip whitespaces after tags, except space
        '/[^\S ]+\</s', //strip whitespaces before tags, except space
        '/(\s)+/s',  // shorten multiple whitespace sequences
        '/^\s+|\n|\r|\s+$/m' // remove line breaks
        );
    $replace = array(
        '>',
        '<',
        '\\1',
        ''
        );
    $buffer = preg_replace($search, $replace, $buffer);
    return $buffer;
}

// Cache START
$url = $_SERVER["SCRIPT_NAME"];
$break = Explode('/', $url);
$file = $break[count($break) - 1];
$cachefile = 'cached-'.substr_replace($file ,"",-4).'-'.$_REQUEST['showYear'].'.html';
//$cachetime = 86400;
$cachetime = 1;

// Serve from the cache if it is younger than $cachetime
if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
    echo "<!-- Cached copy, generated ".date('H:i', filemtime($cachefile))." -->\n";
    include($cachefile);
    exit;
}
ob_start(); // Start the output buffer

function _get_month_name ($monthNumber) {
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
	return $monthNameArray[$monthNumber];
}

function _get_day_name ($day) {
	$dayNameArray = array (
		'Monday' 	=> "Mandag",
		'Tuesday' 	=> "Tirsdag",
		'Wednesday' => "Onsdag",
		'Thursday' 	=> "Torsdag",
		'Friday' 	=> "Fredag",
		'Saturday' 	=> "Lørdag",
		'Sunday' 	=> "Søndag"
	);
	return $dayNameArray[$day];
}

function _encode_string_array ($stringArray) {
	$s = strtr(base64_encode(addslashes(gzcompress(serialize($stringArray),9))), '+/=', '-_,');
	return $s;
}

function _decode_string_array ($stringArray) {
	$s = unserialize(gzuncompress(stripslashes(base64_decode(strtr($stringArray, '-_,', '+/=')))));
	return $s;
}

function _get_last_change($url) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_NOBODY, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FILETIME, true);
	$r = curl_exec($curl);
	if ($r === false) {
		die (curl_error($curl));

	}
	$ts = curl_getinfo($curl, CURLINFO_FILETIME);
	if ($ts != -1) { //otherwise unknown
	    return strtotime(date("Y-m-d H:i:s", $ts));
	}
}

function _get_data($url) {
	$curl = curl_init();
	$timeout = 5;
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($curl, CURLOPT_ENCODING, 'UTF-8');
	$data = curl_exec($curl);
	if ($data === false) {
		die (curl_error($curl));

	}
	curl_close($curl);
	return $data;
}
$url = "http://www.kino.dk/upload/movie_premiere.html";

$last_change = _get_last_change($url);

if (file_exists('txt/'.$last_change.'.txt')) {
	$m = file_get_contents('txt/'.$last_change.'.txt');
	$movies_year_month_day 	= _decode_string_array($m);
	$all_years 				= array_keys($movies_year_month_day);
	$first_year 			= end($all_years);
	$last_year 				= reset($all_years);
} else {
	$html = _get_data($url);
	/* Result: 
	<html>
		<head>
			<title>Premierefilm</title>
			<style>
				body {
					background-color: #fff;
					color:#000;
					font-family: Verdana,Arial;
					font-size:12px;
				}
				h1 {
					font-size:14px;
					margin: 10px 0px 0px 0px;
				}
			</style>
		</head>
		<body><h1>1938-08-22</h1>Den mandlige husassistent<br /><h1>1938-09-29</h1>Snehvide og de syv dværge<br /><h1>1940-01-08</h1>Lincoln, folkets helt<br /><h1>1945-12-26</h1>
	*/
	$htmlElements = preg_split('/<[^>]*[^\/]>/i', $html, -1, PREG_SPLIT_NO_EMPTY);
	$i = 0;
	foreach ($htmlElements as $k => $v) {
		$v = trim($v);
		if (!empty($v) && $i > 6) { // start after </head>
			if ($i&1) { // even counter
				$movies[$i]['date'] = $v;
			} else {
				$movies[$i-1]['title'] = preg_split("/(<br\/>|<br \/>)/", utf8_decode($v), -1, PREG_SPLIT_NO_EMPTY);
				// split titles into array for each line break
			}
		}
		$i++;
	}
	// remove last element
	array_pop($movies);
	foreach ($movies as $k => $v) {
		$movies_year_month_day[substr($v['date'], 0, 4)][substr($v['date'], 5, 2)][substr($v['date'], 8, 2)] = $v['title'];
		/*
		Array
		(
			[2014] => Array
			        (
			            [01] => Array
			                (
			                    [09] => Array
			                        (
			                            [0] => 47 Ronin
			                            [1] => Reasonable Doubt
			                        )
							)
					)
		)
		*/
	}

	$movies_year_month_day 			= array_reverse($movies_year_month_day,true); // newest first, preserve keys
	$all_years 						= array_keys($movies_year_month_day);
	$first_year 					= end($all_years);
	$last_year 						= reset($all_years);
	$movies_ser						= _get_last_change($url).'.txt';
	$fh 							= fopen('txt/'.$movies_ser, 'w') or die("can't open file");
	$movies_ser_data				= _encode_string_array($movies_year_month_day);
	fwrite($fh, $movies_ser_data);
	fclose($fh);
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
	<title>Premierefilm i <?php print $_REQUEST['showYear']; ?></title>
	<meta charset="iso-8859-1">
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=yes"></head>
	<meta name"description" content="Oversigt over alle premierefilm i Danmark<?php print ' i '.$_REQUEST['showYear']; ?>">
	<link href="css/w-2013-05-01m.css" rel="stylesheet">
	<link rel="apple-touch-icon" href="img/premierefilm.png"/>
	<link rel="apple-touch-icon-precomposed" href="img/premierefilm.png"/>
<body>
<div class="top">
	<div class="row">
		<div class="cell s1of4 md_theme_c1 md_h2"></div>
		<div class="cell s1of4 md_theme_c2 md_h2"></div>
		<div class="cell s1of4 md_theme_c3 md_h2"></div>
		<div class="cell s1of4 md_theme_c4 md_h2"></div>
	</div>
	<div class="row">
		<div class="cell s1of1 md_bxsh_t1 md_h1 md_h1"></div>
	</div>
</div>
<header class="row">
	<div class="cell">
		<h1 class="fs1 plm md_header_title">Premierefilm i </h1>
	</div>
	<div class="cell pls">
	<?php
	print "\t<select class=\"md_sel_t1\" id=\"year\" onchange=\"window.open(this.options[this.selectedIndex].value,'_top')\">\n";
	foreach ($all_years as $k => $year) {
		if (($_REQUEST['showYear'] == $year) ? $selected = " selected" : $selected = "");
		print "\t\t\t<option value=\"./?showYear=".$year.$showMonth."\"".$selected.">".$year."</options>\n";
	}
	print "\t\t</select>\n";
	?>
	</div>
	<div class="cell ptm plm">
		<div class="md_arr_t1 js_sel_year">
			<div class="pam fs4 fw7">Vælg år!</div>
		</div>
	</div>
</header>
<?php /*
<div class="imdb_title" style="height:20px"></div>
<div class="imdb_poster" style="height:75px"></div>
*/ ?>
<div class="wrap">
	<article class="row">
	<?php
	if (!empty($movies_year_month_day[$_REQUEST['showYear']])) {
		foreach ($movies_year_month_day[$_REQUEST['showYear']] as $month => $days) {
			if (($month == date("m")) ? $this_month_highlight = " bdc2" : $this_month_highlight = "");
			print "<div class=\"md_month mhs mvl".$this_month_highlight."\">\n";
			print '<a href="#'._get_month_name($month).'" rel="'._get_month_name($month).'">';
			print substr (_get_month_name($month),0,3);
			$c = '';
			foreach ($days as $day => $titles) {
				$c = $c + count($titles);
			}
			print '<strong>'.$c.'</strong>';
			print '</a>';
			print '</div>';
		}
	}
	?>
	</article>
		<?php
		if (!empty($movies_year_month_day[$_REQUEST['showYear']])) {
			foreach ($movies_year_month_day[$_REQUEST['showYear']] as $month => $days) {
				print "<div class=\"row\">\n";
				print '<div class="cell h1_month">';
				print '<h1 id="'._get_month_name($month).'">'._get_month_name($month)."</h1>\n";
				print '</div>
							<div class="cell">
								<div class="pls ptl md_cur_t1">
									<div class="md_arr_t2 md_arr_t2 js_go_to_top md_bxsh_t1">
										<div class="pat">Top</div>
									</div>
								</div>
							</div>
						</div>';
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
		?>
</div>
<footer>
Maskinstormer.dk
</footer>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
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

var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-111843-19']);
_gaq.push(['_setDomainName', 'maskinstormer.dk']);
_gaq.push(['_trackPageview']);

(function() {
var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();


function hideAddressBar() {
  if(!window.location.hash) {
      if(document.height < window.outerHeight) {
          document.body.style.height = (window.outerHeight + 50) + 'px';
      }
      setTimeout( function(){ window.scrollTo(0, 1); }, 50 );
  }
}
window.addEventListener("load", function(){ if(!window.pageYOffset){ hideAddressBar(); } } );
window.addEventListener("orientationchange", hideAddressBar );
</script>
</body>
</html>
<?php
// Cache the contents to a file
$cached = fopen($cachefile, 'w');
fwrite($cached, ob_get_contents());
//fwrite($cached, _minify_html(ob_get_contents()));
fclose($cached);
ob_end_flush(); // Send the output to the browser
?>