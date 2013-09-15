<!DOCTYPE html>
<?php
setlocale(LC_ALL, 'ru_RU.utf-8');
$pdo = null;

function connect(){
	global $pdo;
	try {
		$pdo = new PDO(
		'mysql:host=localhost;dbname=hhcalendar',
		'hhcalendar',
		'YS76AtdmJjnaGJBc',
		array(
			PDO::ATTR_PERSISTENT => true
		)
		);
		return true;
	}
	catch (PDOException $e){
		print "Error!: " . $e->getMessage() . "<br/>";
		die();
	}
}

function russianDate($date, $week_day=true) {

  $unix_time=strtotime($date);
  if (!$unix_time) $unix_time = $date;
  if (!$unix_time or $date == '0000-00-00') return false;

  $Months = array("01"=>"января",
                  "02"=>"февраля",
                  "03"=>"марта",
                  "04"=>"апреля",
                  "05"=>"мая",
                  "06"=>"июня", 
                  "07"=>"июля",
                  "08"=>"августа",
                  "09"=>"сентября",
                  "10"=>"октября",
                  "11"=>"ноября",
				  "12"=>"декабря");
  $week_days = array('1'=>'понедельник',
				  '2'=>'вторник',
				  '3'=>'среду',
				  '4'=>'четверг',
				  '5'=>'пятницу',
				  '6'=>'субботу',
				  '7'=>'воскресенье');
  $day = strftime("%d", $unix_time);
  settype($day, "integer");
  $month = strftime("%m", $unix_time);
  $year = strftime("%Y", $unix_time);

  $result = $day." ".$Months[$month];

  if ($week_day) {
  	$wd =  strftime("%u", $unix_time);
  	$result .= ', в'.($wd == '2'?'о':'').' '.$week_days[$wd];
  }
  return $result;
}

function dateFromRussian($rdate) {
	  $Months = array("01"=>"янв",
                  "02"=>"фев",
                  "03"=>"мар",
                  "04"=>"апр",
                  "05"=>"мая",
                  "06"=>"июн", 
                  "07"=>"июл",
                  "08"=>"авг",
                  "09"=>"сен",
                  "10"=>"окт",
                  "11"=>"ноя",
				  "12"=>"дек");
	$rdate = explode(" ", $rdate);
	$month = 0;

	foreach ($Months as $mn => $ms) {
		if (strpos($rdate[1], $ms)!==false) {
			$month = $mn;
			break;
		}
	}

	$year = date("Y");
	$day = $rdate[0];
	return "$year-$month-$day";
}

function getEvent($event) {
	$result = '<div class="title">'
	.$event->title
	.'</div>
	<div class="participants">'
	.$event->participants
	.'</div>'
	.'<div class="description">'
	.$event->description
	.'</div>';

	return $result;
}

function eventCreateAction() {
	global $pdo;
	if ($_REQUEST['action'] === 'Удалить'){
			$request = $pdo->prepare("DELETE FROM events where date=:date");
			$request->execute(array(':date'=>$_REQUEST['date']));
	} else {
		$event = new stdClass();
		$event->date = $_REQUEST['date'];
		$event->title = $_REQUEST['title'];
		$event->participants = $_REQUEST['participants'];
		$event->description = $_REQUEST['description'];
		updateEvent($event);
	} 	
}

function fastCreateAction() {
	$fc = @$_REQUEST['fast-create'];
	if (!$fc) return;
	$fc = explode(', ', $fc);
	$event = new stdClass();
	$event->date = dateFromRussian($fc[0]);
	$event->title = @$fc[1];
	$event->participants = @$fc[2];
	updateEvent($event);
}

function updateEvent($event){
	global $pdo;
	$request = $pdo->prepare("SELECT * FROM events where date=:date");
	$request->execute(array(':date'=>$event->date));
	if ($request->fetch()){
		$updating = $pdo->prepare("UPDATE events SET title=:title, participants=:participants, description=:description WHERE date=:date");
	} else {
		$updating = $pdo->prepare("INSERT INTO events (title, date, participants, description) VALUES (:title, :date, :participants, :description)");
	}
	$updating->execute(array(
		':title'=>$event->title,
		':date'=>$event->date,
		':participants'=>@$event->participants?:'',
		':description'=>@$event->description?:''
	));
}

function getDay($date, $weekday=false, $disabled=false, $today= false, $request = false){
	global $pdo;
	$event = null;
	if ($request) {
		$request->execute(array(':date'=>$date));
		$event = $request->fetchObject();
	}
	$result = '<td data-date="'.$date.'"
		data-russian-date="'.russianDate($date).'"
		class="'
		.($disabled? 'disabled ':'')
		.($date == $today ?'today ':'')
		.($event? 'event' :'')
		.'"><div><div class="dummy"></div><div class="info">';
	$day = strftime(($weekday?'%A, ':'').'%e', strtotime($date));
	$result .= '<span class="date">'.$day.'</span>';
	if ($event) $result .= getEvent($event);
	return '</div></div>'.$result;
}

function getCalendar($date){
	global $pdo;
	$result = '<table class="month"><tbody>';

	$request = $pdo->prepare("SELECT * FROM events where date=:date");

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

	$today = date("Y-m-d");

	$result .= '<tr>';

	for ($d = $first_prev_day; $d <= $prev_last_day; $d++) 
		$result .= getDay($prev_month.$d,  true, true,  $today, $request);
	$d = 1;
	for ($d = 1, $wd = $first_weekday; $wd < 7; $d++, $wd++)
		$result .= getDay($cur_month.$d,   true, false, $today, $request);
	$result .= '</tr><tr>';
	$wd=0;
	for ($wd = 0; $d <= $last_day; $d++, $wd++){
		 $result .= getDay($cur_month.$d, false, false, $today, $request);
		 if ($wd%7 == 6) $result .= '</tr><tr>';
	}

	for ($d = 1; $wd%7; $d++, $wd++){
		$result .= getDay($next_month.$d, false, true,  $today, $request);
	}


	return $result.'</tr></tbody></table>';
	
}

function getMonthLine($date){
	$timestamp = strtotime($date);
	$cur_month = date("Y-m-1", $timestamp);
	$prev_month = date("Y-m-1", strtotime("first day of previous month", $timestamp));
	$next_month = date("Y-m-1", strtotime("first day of next month", $timestamp));

	$today = date("Y-m-d");

	return '<div id="month-selector">
	<span id="to-prev-month" data-date="'.$prev_month.'" class="small-button">◂</span>
	<span class="month-name" data-date="'.$cur_month.'">'.
	strftime('%B %G', strtotime($date))
	.'</span>
	<span id="to-next-month" data-date="'.$next_month.'" class="small-button">▸</span><span id="to-today"  data-date="'.$today.'" class="small-button">Сегодня</span></div>';
}

function runAction(){
	$act = @$_REQUEST['act']?:'default';
	if ($act === 'month-load'){
		return monthLoadView();
	} elseif ($act === 'event-create'){
		eventCreateAction();
		return monthLoadView();
	} elseif ($act === 'fast-create') {
		fastCreateAction();
		return monthLoadView();
	}
	else {
		return defaultView();
	}
}


function defaultView(){
	$date = @$_REQUEST['date']?: date("Y-m-d");
	echo '
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="reset.css"/>
	<link rel="stylesheet" type="text/css" href="main.css"/>
	<script src="jquery-2.0.3.min.js"></script>
	<script src="iface.js"></script>
	</head>
	<body>
	<div id="top-panel">
	<span class="a button" id="add-button-top">Добавить</span>
	<a class="button" id="refresh-button-top"> Обновить</a>
	<div id="search-bar">
	<input type="text" class="autoclear" name="search" value="Событие, дата или участник"/>
	</div>
	</div><!--top-panel-->
	<div id="container">'
	.getMonthLine($date)
	.getCalendar($date)
	.'</div><!--container-->
	<div id="fast-create" class="dialog">
	<div class="arrow top"><div></div></div>
	<div class="close" title="Закрыть">⨯</div>
	<form method="post">
	<input type="text" class="autoclear" name="fast-create" value="6 октября, ПроУлочки"/><br>
	<input type="hidden" name="act" value="fast-create"/>
	<input type="submit" class="small-button" value="Создать"/>
	</form>
	</div>
	<div id="event-create" class="dialog">
	<div class="arrow left"><div></div></div>
	<div class="close" title="Закрыть">⨯</div>
	<form method="post">
	<input type="text" class="autoclear" name="title" value="Событие"/><br>
	<div id="participants-title">Участники:</div><br>
	<input type="text" class="autoclear" name="participants" value="Имена участников"/><br>
	<textarea class="autoclear" name="description">Описание</textarea><br>
	<input type="hidden" name="act" value="event-create"/>
	<input type="submit" class="small-button" name="action" value="Готово"/>
	<input type="submit" class="small-button" name="action" value="Удалить"/>
	</form>
	</div>

	</body>
	</html>';
}

function monthLoadView(){
	$date = @$_REQUEST['load-date']?: (@$_REQUEST['date']?:date("Y-m-d"));
	echo getMonthLine($date).getCalendar($date);
}

connect();
runAction();
