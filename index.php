<?php

require_once('./src/SchedulerGenerator.php');
require_once('./src/Scheduler.php');

use Schedule\Scheduler;
use Schedule\SchedulerGenerator;

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    	throw new Exception($errstr, $errno);
});

$action = isset($_GET['action'])?$_GET['action']:'';

switch ($action) {
	
	case '/run':
		
		try {

			$Scheduler = new Scheduler();
			$Scheduler->initFromPost();
			$output = $Scheduler->outputTable();

		} catch (Exception $e) {

		    $output = 
		    	"<div class=\"bs-callout bs-callout-danger\">".
		    	'<h4>O-o-o shit! This is error: '.  $e->getMessage(). "</h4>\n".
		    	"</div>";
		} 

		include_once('public/output_table.php');

		break;
	
	default:
		
		print file_get_contents("./public/wellcome.html");
		
		break;
}
	


