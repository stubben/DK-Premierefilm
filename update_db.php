<?php
/**
print_r($_POST);
Array
(
    [url] => http://www.kino.dk/upload/movie_premiere.html
)
*/

function _encode_string_array ($stringArray) {
	$s = strtr(base64_encode(addslashes(gzcompress(serialize($stringArray),9))), '+/=', '-_,');
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

$url = $_POST['url'];
$last_change = _get_last_change($url);

if (!file_exists('txt/'.$last_change.'.txt')) {
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
	$movies_ser						= $last_change.'.txt';
	$fh 							= fopen('txt/'.$movies_ser, 'w') or die("can't open file");
	$movies_ser_data				= _encode_string_array($movies_year_month_day);
	fwrite($fh, $movies_ser_data);
	fclose($fh);
	print 'DB updated '.date ("Y-m-d H:i:s");
}
?>