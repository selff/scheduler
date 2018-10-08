<?php
require './vendor/autoload.php';

use Schedule\Scheduler;
use Schedule\SchedulerGenerator;
use Schedule\SchedulerException;

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    	throw new SchedulerException($errstr, $errno);
});

$action = isset($_GET['run'])?'run':'';

switch ($action) {
	
	case 'run':
		
		try {

			$Scheduler = new Scheduler(new SchedulerGenerator());
			$Scheduler->run();
			$output = $Scheduler->outputTable();
			header("HTTP/1.1 200 OK");

		} catch (Exception $e) {

			//http_response_code(409);//Conflict
			header('HTTP/1.1 409 Conflict');
		    $output = 
		    	'<div class="bs-callout bs-callout-danger">'.
		    	'<h4>Oops! This is error: '.  $e->getMessage(). "</h4>".PHP_EOL.
                '<p>Error in: '.nl2br($e->getTraceAsString())."</p>".PHP_EOL.
		    	'</div>';
		} 

		include_once('./templates/output_table.php');

		break;
	
	default:
		
		print file_get_contents("./templates/wellcome.html");
		
		break;
}
	


