<?php

require_once('./src/IScheduler.php');
require_once('./src/Scheduler.php');

$uploaddir = '/tmp/';
$outputTable = '';

use Schedule\Scheduler as Scheduler;

if (isset($_POST['submit'])) {
	
	// init Sheduler
	$Scheduler = new Scheduler();

	// settings init from user input
	// TODO validate _POST
	$Scheduler->initSettings($_POST);
	
	if (isset($_FILES["userfile"])) {

		//file upload 
		$tmp_name = $_FILES["userfile"]["tmp_name"];
		$filename = basename($_FILES["userfile"]["name"]);
		move_uploaded_file($tmp_name, $uploaddir.$filename);
		if ($_FILES["userfile"]["error"] == UPLOAD_ERR_OK) {
			
			// read file
			$csv = array();
			$lines = file($uploaddir.$filename, FILE_IGNORE_NEW_LINES);
			foreach ($lines as $key => $value) { $csv[$key] = str_getcsv($value); }

			// load input data from csv file
			$Scheduler->loadData($csv);

			// generate Schedule
			$Scheduler->makeSchedule();
			
			// output data 
			$outputTable = $Scheduler->outputSchedule();
			//$Scheduler->outputScheduleCSV($filename);
			include_once("./public/output_table.php");
		}

	}	
} else {
	print file_get_contents("./public/wellcome.html");
}

