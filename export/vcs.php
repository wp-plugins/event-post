<?php
if(isset($_GET['t']) && isset($_GET['sd']) && isset($_GET['ed']) && isset($_GET['d']) && isset($_GET['a']) && isset($_GET['u'])){
	date_default_timezone_set('Europe/Paris') ;
	header("content-type:text/x-vcalendar");
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public");
	header("Content-Disposition: attachment; filename=".$_GET['u'].".vcs;" );
	echo"BEGIN:VCALENDAR\r\nVERSION:2.0\r\n";
	$sta = array('ACCEPTED','COMPLETED');
	$mt = strtotime($_GET['sd']);
	$vdat = date("Ymd",$mt).'T'.date("His",$mt).'Z';
	$vtz = $_GET['tz'];
	$mte = strtotime($_GET['ed']);
	$vdate = date("Ymd",$mte).'T'.date("His",$mte).'Z';
	//str_replace('-','',$m_date).'T'.str_replace(':','',$m_heure).'Z';
	echo"BEGIN:VEVENT\r\nPRODID:agenda_eelv\r\nSUMMARY:".stripslashes($_GET['t'])."\r\nUID:".$_GET['u']."\r\nLOCATION:".stripslashes($_GET['a'])."\r\nDTEND$vtz:$vdate\r\nDTSTART$vtz:$vdat\r\nDESCRIPTION:".stripslashes($_GET['d'])."\r\nEND:VEVENT\r\n";
	echo"END:VCALENDAR\r\n";
}
?>