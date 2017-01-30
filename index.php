<?php

require_once('./src/IScheduler.php');
require_once('./src/Scheduler.php');

$uploaddir = '/tmp/';

use Schedule\Scheduler as Scheduler;

if (isset($_POST['submit'])) {
	
	$Scheduler = new Scheduler();
	$Scheduler->loadSettings($_POST);
	
	if (isset($_FILES["userfile"])) {

		$tmp_name = $_FILES["userfile"]["tmp_name"];
		$filename = basename($_FILES["userfile"]["name"]);
		move_uploaded_file($tmp_name, $uploaddir.$filename);
		if ($_FILES["userfile"]["error"] == UPLOAD_ERR_OK) {
			
			$csv = array();
			$lines = file($uploaddir.$filename, FILE_IGNORE_NEW_LINES);
			foreach ($lines as $key => $value) { $csv[$key] = str_getcsv($value); }

			$Scheduler->loadData($csv);
			$Scheduler->makeSchedule();
			//$Scheduler->outputSchedule();
			$Scheduler->outputScheduleCSV();

		}

	}	
}

header('Content-Type: text/html; charset=UTF-8');	
switch ($_SERVER['REQUEST_URI']) {
	case '/run' :  break;
	default : print file_get_contents("./public/wellcome.html");
}
