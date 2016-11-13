<?php

use \DateTime;
use \DateInterval;
use \DatePeriod;

//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
if (!isset($_POST['submit'])) {
	header('Content-Type: text/html; charset=UTF-8');	
	echo "<html>
	<head><title>Scheduler</title><style>
	.scheduler-page {margin:.75em;padding:.75em;font-size:1em;font-family:helvetica,arial,sans-serif;color:#666;}
	.scheduler-page table,form {border:2px dotted #eee;padding:.5em;}
	.scheduler-page form,input,p {font-size:1em;margin:.5em;font-family:helvetica,arial,sans-serif;}
	.scheduler-page h1 {margin:.5em;padding.5em;}
	.scheduler-page table {min-width:400px;width:80%}
	.scheduler-page table td {padding:.25em;}
	.scheduler-page table td:hover {background:#f2f2f2}
	.scheduler-page td.ctr {text-align:center;font-size:11px}
	.scheduler-page td.rgt {text-align:right;font-size:11px}
	.scheduler-page td.gray {background:#eee;}
	.scheduler-page td.olv {background:#efd;}
	</style></head>
	<body class=\"scheduler-page\">
	<h1>Scheduler</h1>
	<form enctype=\"multipart/form-data\" method=\"POST\" action=\"\">
	<p>The first and second rows in the table are services. The first line contains the name of the company, and the second line indicates the number of persons participating in the meeting of the company. The first three columns in the table are also services.<br>
	At the intersection of the row and column markers indicate if the counterparties must meet, and if you want to ask yourself the exact time they enter the marker instead of the exact time of the meeting.</p>
	<input name=\"userfile\" type=\"file\" /><br>
	<input type=\"text\" name=\"slot\" value=\"15\">Slot duration in minutes.<br>
	<input type=\"text\" name=\"start\" value=\"10:00\">Event Start Time HH:MM<br>
	<input type=\"text\" name=\"end\" value=\"17:00\">End Time events HH:MM<br>
	<input type=\"text\" name=\"breaks\" value=\"13:00-14:00\">Possible pauses separated by commas<br>
	<input type=\"submit\" name=\"submit\" value=\"Send CVS File\" />
	</form>
	<p>Example CSV-table for input:</p>
	<table>
	<tr><th>Company</th><th>Type</th><th>Face</th><th>Metro</th><th>IKEA</th><th>3M</th><th>Cisco</th></tr>
	<tr><td></td><td></td><td class=\"ctr gray\">persons:</td><td class=\"ctr gray\">2</td><td class=\"ctr gray\">2</td><td class=\"ctr gray\">1</td><td class=\"ctr gray\">1</td></tr>
	<tr><td class=\"olv\">Royal & SPA</td><td class=\"olv\">Hotel</td><td class=\"olv\">Katy</td><td class=\"ctr\">X</td><td class=\"ctr\">X</td><td></td><td></td></tr>
	<tr><td class=\"olv\">Royal & SPA</td><td class=\"olv\">Hotel</td><td class=\"olv\">Piter</td><td></td><td></td><td class=\"ctr\">X</td><td class=\"ctr\">X</td></tr>
	<tr><td class=\"olv\">Azimuth</td><td class=\"olv\">Hotel</td><td class=\"olv\">Oleg</td><td class=\"ctr\">X</td><td class=\"ctr\">12:30:00</td><td class=\"ctr\">X</td><td class=\"ctr\">X</td></tr>
	<tr><td class=\"olv\">Dolce Gusto</td><td class=\"olv\">Restouran</td><td class=\"olv\">Mishut</td><td class=\"ctr\">X</td><td class=\"ctr\">10:00:00</td><td></td><td></td></tr>
	<tr><td class=\"olv\">Red Square</td><td class=\"olv\">Mall</td><td class=\"olv\">Boris</td><td class=\"ctr\">X</td><td class=\"ctr\">X</td><td class=\"ctr\">X</td><td class=\"ctr\">X</td></tr>
	</table>
	</body>
	</html>";
} elseif(isset($_FILES["userfile"])) {
	$uploaddir = '/tmp/';
	//$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
	//move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile);
	$tmp_name = $_FILES["userfile"]["tmp_name"];
	$filename = basename($_FILES["userfile"]["name"]);
	//echo $uploaddir.$filename;
	move_uploaded_file($tmp_name, $uploaddir.$filename);
	
	if ($_FILES["userfile"]["error"] == UPLOAD_ERR_OK) {
		$csv = array();

		$lines = file($uploaddir.$filename, FILE_IGNORE_NEW_LINES);
		foreach ($lines as $key => $value) { $csv[$key] = str_getcsv($value); }
		
		
		$settings = array();
		if (isset($_POST['slot'])) $settings['slot_min'] = $_POST['slot'];
		if (isset($_POST['start'])) $settings['meeting_start'] = date('Y-m-d').' '.$_POST['start'].':00';
		if (isset($_POST['end'])) $settings['meeting_end'] = date('Y-m-d').' '.$_POST['end'].':00';
		if (isset($_POST['breaks'])) $settings['breaks'] = $_POST['breaks'];
		$scheduler = new \Scheduler($settings);
		
		$scheduler->parseStrings($csv);
		$scheduler->initialTime();
		//$scheduler->findBestPeriods();
		//$scheduler->findAllPeriods();
		$scheduler->findFreeSlot();
		//echo "<pre>";
		//$scheduler->showLog();
		//$scheduler->showUserSchedule();
		$scheduler->scheduleCSV();
		//print_r($scheduler->columns);
		//print_r($scheduler->company);
		//print_r($scheduler->schedule);
	} else {
		header('Content-Type: text/html; charset=UTF-8');
		echo "UPLOAD ERROR";
	}

} else {
	echo "HTML ERROR";
}

//$filename = "/home/andrey/tmp/Расписание_Хорека Шапито.csv";

class Scheduler {

	public $columns;
	public $company;
	public $settings;
	public $begin;
	public $end;
	public $breaks;
	public $interval;
	public $daterange;
	public $schedule;
	public $log;
	private $findTimeCell; 

	public function __construct($settings = array()) {

		$mydate = date("Y-m-d");

		$this->settings['initial_preset'] = true;
		$this->settings['initial_format'] = 'H:i:s';
		$this->settings['slot_min'] = '15';
		$this->settings['meeting_start'] = $mydate.' 10:00:00';
		$this->settings['meeting_end'] = $mydate.' 18:00:00';
		$this->settings['breaks'] = array();

		if (!empty($settings)) {
			foreach ($settings as $key => $value) {
				if (isset($this->settings[$key])) $this->settings[$key] = $value;
			}
		}
		
		$breaks = explode(",",$this->settings['breaks']);
		foreach($breaks as $break){
			$b = trim($break);
			if (preg_match("~^([0-9]{2}):([0-9]{2})-([0-9]{2}):([0-9]{2})$~",$b,$m)) {
				$this->breaks[] = array(
					'start' => new DateTime($mydate.' '.$m[1].':'.$m[2].':00'),
					'end' => new DateTime($mydate.' '.$m[3].':'.$m[4].':00'),
				);
			}
		}

		$this->schedule = array();
		$this->findInitialTimeCell = false;
		$this->log = array();  
		$this->begin = new DateTime($this->settings['meeting_start']);
		$this->end = new DateTime($this->settings['meeting_end']);
		$this->interval = new DateInterval('PT'.$this->settings['slot_min'].'M');
		$this->daterange = new DatePeriod($this->begin, $this->interval ,$this->end);

    }

    public function parseStrings($rows){
    	// по строкам
		foreach ($rows as $i=>$row) {
			// по столбцам
			foreach($row as $k=>$v) {
				// первая строка содержит названия компаний
				if (0 == $i) {
					if ($k>2) $this->columns[($k-3)]['name'] = $v;
				// вторая строка содержит кол-во персонала компаний
				} elseif (1 == $i) {
					if ($k>2) $this->columns[($k-3)]['pers'] = $v ? (int)$v : 1;
				// начиная с третьей строки 
				} else {
					switch ($k) {
						case 0:
							$this->company[($i-1)]['name'] = $v; 
							break;
						case 1: 
							$this->company[($i-1)]['type'] = $v; 
							break;
						case 2:
							$this->company[($i-1)]['face'] = $v; 
							break;
						default:
							$this->company[($i-1)]['intersection'][($k-3)] = $v;	
							break;
					}
				}
			}
		}
    }

    public function initialTime() {
    	$j=0;
    	foreach($this->daterange as $i=>$date){
    			
    			$temp = array();
	    		foreach ($this->breaks as $break) {
	    			if ($date >= $break['start'] && $date < $break['end']) {
	    				$temp[] = $date->format($this->settings['initial_format']);
	    			}
				}
				if (in_array($date->format($this->settings['initial_format']),$temp)) {
					//echo "BREAK ".$date->format($this->settings['initial_format'])."\n";
				} else {
					//echo "WORK! ".$date->format($this->settings['initial_format'])."\n";
					$this->schedule[$j]['time'] = $date->format($this->settings['initial_format']);
		    		$this->schedule[$j]['periods'] = array();	
		    		$j++;
		    	}
				
		}
		//echo count($this->schedule)."]<br>";
		//echo "<pre>"; print_r($this->schedule);

		if ($this->settings['initial_preset']) {
	    	foreach($this->daterange as $i=>$date){
			    foreach($this->company as $j=>$intersection) {
			    	foreach($intersection['intersection'] as $k=>$value) {
			    		if ($value == $date->format($this->settings['initial_format'])) {
			    			$this->schedule[$i]['periods'][] = array('columnValue' => $k,'rowValue' => $j);
			    			$this->findInitialTimeCell = true;
						}
			    	}
			    }
			}
		}
    }
/*
    // приоритет в заполнении расписания у тех у кого есть начальное время в таблице, поиск
    public function findBestPeriods(){
    	// есть начальное время в таблице
    	if (true == $this->findInitialTimeCell) {
		    foreach($this->schedule as $j=>$periods) {
		    	// найдем период в котором указано начальное время
		    	if (count($periods['periods']) > 0) {
		    		foreach($periods['periods'] as $k=>$value){
		    			// начнем заполнять соседние ячейки
		    			$this->fillUserShedule($value['rowValue'],$value['columnValue'],$j);
		    		}	
		    	}
		    }	
    	}
    }

    // заполнить расписание пользователя для которого нашли начальное время в таблице, поиск
    public function fillUserShedule($rowValue,$column,$period){
    	$intersection = $this->company[$rowValue]['intersection'];
    	foreach ($intersection as $columnValue=>$marker){
    		if ( ($columnValue != $column) && ( trim($marker) != '') ) {
    			// нашли период, заполним следующий
    			$this->findFreeSlot($rowValue,$columnValue,$period);
    		}
    	}	
    }

	public function findAllPeriods(){
    
    	foreach($this->company as $rowValue=>$intersection) {//echo "company = {$rowValue} :: ";
		   	// найдем период в котором указан маркер
		    foreach ($intersection['intersection'] as $columnValue=>$marker) { //echo "column = {$columnValue} ";
		    	
		    	$marker = trim($marker);
		    	
		    	// начнем заполнять все ячейки которые помечены маркером
		    	if (strlen($marker)<3) {
		    		
		    		$this->findFreeSlot($rowValue,$columnValue);
		    	}
		    	//echo "<br>";
		    }
		}	
    
    }
*/
    public function countPersBusyForPeriod($currentPeriod, $column) {
    	$counter = 0;
    	$periods = $this->schedule[$currentPeriod];
    	foreach($periods['periods'] as $period) {
	    	if ($period['columnValue'] == $column) {
	    		$counter++;
	    	}
    	}
    	return $counter;
    }
    // 
    public function findFreeSlot(){//$user,$column,$startPeriod=0
    	//echo count($this->schedule)."|";
    	
    	foreach($this->company as $user=>$intersection) {//echo "company = {$rowValue} :: ";
		   	// найдем период в котором указан маркер
		    foreach ($intersection['intersection'] as $column=>$marker) { //echo "column = {$columnValue} ";
		    	
		    	$marker = trim($marker);

		    	// начнем заполнять все ячейки которые помечены маркером
		    	if (strlen($marker)<3 && ($marker)) {

			    	$notsaved = true; 
			    	$startPeriod = $currentPeriod = $pers = 0;
			    	$prohod = 0;
			    	//$periodBusyComp = array();
			    	$koef = $this->columns[$column]['pers'];

		    		for($z=0;$z<$koef;$z++){

		    		}


			    	//for ($i=0; $i < $koef; $i++) $periodBusyComp[$i] = false;
			    	$i=0;
			    	//echo "<br>";
			    	// если существует следующий период
			    	do {

			    		$interupt = false;
						
						//echo "$user $column $currentPeriod $pers - "; 
			    		// перебор времени вперед начиная от начального
			    		//if ($currentPeriod === $startPeriod) { $currentPeriod++; }
			    		//elseif ($currentPeriod < count($this->schedule)) { $currentPeriod++; }
			    		// когда дойдем до конца перебор времени назад начиная от начального
			    		//if ($currentPeriod === count($this->schedule)) {$currentPeriod = ($startPeriod-1);}
			    		//elseif ($currentPeriod < $startPeriod) { $currentPeriod--;}

			    		$periodBusyComp = false; $periodBusyUser = false; 
			    		// проверим может этот интервал уже использован
			    		if (isset($this->schedule[$currentPeriod])) {
							if (count($this->schedule[$currentPeriod]['periods'])) {
			   					foreach($this->schedule[$currentPeriod]['periods'] as $k=>$period) {
			   						// в данный период пользователь уже использован
			   						if ( $period['rowValue'] == $user ) { $periodBusyUser = true; }
			   						// в данный период слот компании использован
			   						if ( $period['columnValue'] == $column 
			   							&& ($prohod < $this->countPersBusyForPeriod($currentPeriod,$column) ) )  { 
			   								$periodBusyComp = true; 
			   						}
			   					}
			   					//if ( $this->columns[$column]['pers'] == $this->countPersBusyForPeriod($currentPeriod,$column) ) {
			   						//$periodBusyComp = true;
			   					//}
			   				}
			    		}
			    		if (!$periodBusyUser && !$periodBusyComp )  {
			    			$this->schedule[$currentPeriod]['periods'][] = array(
			   					'columnValue' => $column,'rowValue' => $user
			   				);
			   				$notsaved = false;
			    		}
			    		// прошлись по всем слотам и не смогли найти доступный период
			    		if ( ( $i == (  count($this->schedule)*$koef - 1) ) && $notsaved ) {
			    			$this->addToLog("Not found slot for user={$user}, column={$column}");
			    			$interupt = true;
			    		}
			    		//echo "$i $currentPeriod $user $column $periodBusy; <br>";
			    		$i++; $currentPeriod++;
			    		
			    		if ($currentPeriod == count($this->schedule)) {
			    			$currentPeriod = 0;
			    			$prohod++;
			    			if ($this->countPersBusyForPeriod($currentPeriod,$column) > $pers) $pers++;
			    		}

			    	} while ($notsaved && !$interupt);

			    }
			}
		}
   		
    }

    public function showUserSchedule(){
    	$userSchedule = array();

    	foreach ($this->schedule as $key => $slot) {
    		foreach($slot['periods'] as $period) {
    			$userSchedule[$period['rowValue']][] = array($slot['time'] => $period['columnValue']);
    		}
    	}
    	foreach ($userSchedule as $user => $value) {
    	 	echo "(user=$user) ";
    	 	foreach ($value as $i=>$arr) {
    	 		foreach($arr as $key => $clm) {
    	 			echo "$key={$clm},  ";
    	 		}
    	 	}
    	 	echo "<br>";
    	}
    }

	public function outputCSV($data) {
		global $filename;
        header("Content-type: text/csv");
    	header("Content-Disposition: attachment; filename={$filename}");
    	header("Pragma: no-cache");
    	header("Expires: 0");

    //outputCSV($data);
        $outputBuffer = fopen("php://output", 'w');
        //$outputBuffer = fopen("/home/andrey/tmp/output.csv", 'w');
        foreach($data as $val) {
            fputcsv($outputBuffer, $val);
        }
        fclose($outputBuffer);
    }

    public function scheduleCSV(){
    	$data = array();
    	$row = array('Company','Type','User');
    	foreach($this->columns as $c) {
    		$row[] = $c['name'];
    	}
    	$data[] = $row;
    	$row = array('','','');
    	foreach($this->columns as $c) {
    		$row[] = $c['pers'];
    	}
    	$data[] = $row;
    	foreach($this->company as $compkey=>$company) {
    		$row = array();
    		$row[] = $company['name'];
    		$row[] = $company['type'];
    		$row[] = $company['face'];
    		foreach($this->columns as $columnkey=>$column) {
    			$marker = '';
    			foreach($this->schedule as $id=>$shedule) {
    				foreach ($shedule['periods'] as $period) {
    					if ( ($period['columnValue'] == $columnkey) && ($period['rowValue'] == $compkey) ) {
    						$marker = $shedule['time'];
    					} 
    				}
    			}
    			if ($marker == ''){
	    			foreach($company['intersection'] as $id=>$m) {
	    				if ( ($id == $columnkey) && (strlen(trim($m))>0) ) {
	    					$marker = 'X';
	    				}
	    			}
	    		}
    			$row[] = $marker;
    		}
    		$data[] = $row;
    	}
    	$this->outputCSV($data);
    }

    public function addToLog($str){
    	$this->log[] = $str;
    }

    public function showLog(){
    	foreach($this->log as $str) {
    		echo "$str\n";
    	}
    }


}


