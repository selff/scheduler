<?php
require './vendor/autoload.php';
//require_once('./src/SchedulerGenerator.php');
//require_once('./src/SchedulerException.php');
//require_once('./src/Scheduler.php');

use Schedule\Scheduler;
use Schedule\SchedulerGenerator;
use Schedule\SchedulerException;

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    	throw new SchedulerException($errstr, $errno);
});

$action = isset($_GET['action'])?$_GET['action']:'';

switch ($action) {
	
	case '/run':
		
		try {

			$Scheduler = new Scheduler(new SchedulerGenerator());
			$Scheduler->initFromPost();
			$output = $Scheduler->outputTable();
			http_response_code(200);// OK

		} catch (Exception $e) {

			http_response_code(409);//Conflict
		    $output = 
		    	'<div class="bs-callout bs-callout-danger">'.
		    	'<h4>Oops! This is error: '.  $e->getMessage(). "</h4>\n".
		    	'</div>';
		} 

		include_once('./templates/output_table.php');

		break;
	
	default:
		
		print file_get_contents("./templates/wellcome.html");
		
		break;
}
	


