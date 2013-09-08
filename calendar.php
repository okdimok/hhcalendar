<!DOCTYPE html>
<?php
setlocale(LC_ALL, 'ru_RU.utf-8');

function getDay($date, $weekday=false, $disabled=false){
	$result = '<td'
		.($disabled? ' class="disabled"':'')
		.'><div><div class="dummy"></div><div class="info">';
	$day = strftime(($weekday?'%A, ':'').'%e', strtotime($date));
	$result .= '<span class="date">'.$day.'</span>';
	return '</div></div>'.$result;
}

function getCalendar($date){
	$result = '<table class="month"><tbody>';

	$timestamp = strtotime($date);
	$cur_month = date("Y-m-", $timestamp);

	$date1 = $cur_month.'1';
	$timestamp = strtotime($date1);
	$last_day = date("t", $timestamp);
	$first_weekday = date("N", $timestamp) - 1; // 0 is Monday, 6 is Sunday
	$prev_month_ts = strtotime("first day of previous month", $timestamp);
	$prev_last_day = date("t", $prev_month_ts);

	$first_prev_day = $prev_last_day - $first_weekday+1;

	$prev_month = date("Y-m-", $prev_month_ts);
	$next_month = date("Y-m-", strtotime("first day of next month", $timestamp));

	$result .= '<tr>';

	for ($d = $first_prev_day; $d <= $prev_last_day; $d++) $result .= getDay($prev_month.$d, true, true);
	$d = 1;
	for ($d = 1, $wd = $first_weekday; $wd < 7; $d++, $wd++) $result .= getDay($cur_month.$d, true);
	$result .= '</tr><tr>';
	$wd=0;
	for ($wd = 0; $d <= $last_day; $d++, $wd++){
		 $result .= getDay($cur_month.$d);
		 if ($wd%7 == 6) $result .= '</tr><tr>';
	}

	for ($d = 1; $wd%7; $d++, $wd++){
		$result .= getDay($next_month.$d);
	}


	return $result.'</tr></tbody></table>';
	
}
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="reset.css">
<link rel="stylesheet" type="text/css" href="main.css">
</head>
<body>
<div id="top-panel">
<span class="a button" id="add-button-top">Добавить</span>
<a class="button" id="refresh-button-top"> Обновить</a>
<div id="search-bar">
<input type="text" class="autoclear" value="Событие, дата или участник"/>
</div>
</div><!--top-panel-->
<div id="container">
<div id="month-selector"><span id="to-prev-month" class="small-button">◂</span><span class="month-name">Март 2013</span><span id="to-next-month" class="small-button">▸</span><span id="to-today" class="small-button">Сегодня</span></div>
<?php echo getCalendar(@$_GET["month"]?:"2013-08-30");?>
</div><!--container-->
</body>
</html>
