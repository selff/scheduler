<?php

namespace Schedule;

use \DateTime;
use \DateInterval;
use \DatePeriod;
use Schedule\IScheduler;

class Scheduler implements IScheduler
{
	private $begin;
	
	private $end;
	
	private $breaks;
	
	private $interval;
	
	private $daterange;
	
	private $log;

	private $findTimeCell; 

	private $settings;

	private $columns = [];

	private $rows = [];

	private $schedule = [];

	public function loadSettings($data){

		$mydate = date("Y-m-d"); // TODO make meeting for more one day
		
		$this->settings['initial_preset'] = true;
		$this->settings['initial_format'] = 'H:i:s';
		$this->settings['slot_min'] = '15';

		$breaks = explode(",",$data['breaks']);
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
		$this->begin = new DateTime($mydate.' '.$data['start'].':00');
		$this->end = new DateTime($mydate.' '.$data['end'].':00');
		$this->interval = new DateInterval('PT'.$data['slot'].'M');
		$this->daterange = new DatePeriod($this->begin, $this->interval ,$this->end);
	}

	public function loadData($rows){

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
							$this->rows[($i-1)]['name'] = $v; 
							break;
						case 1: 
							$this->rows[($i-1)]['type'] = $v; 
							break;
						case 2:
							$this->rows[($i-1)]['face'] = $v; 
							break;
						default:
							$this->rows[($i-1)]['intersection'][($k-3)] = $v;	
							break;
					}
				}
			}
		}
	}

	private function initTime(){
    	$j=0;
    	foreach($this->daterange as $i=>$date){
    			
    		$temp = array();
	    	foreach ($this->breaks as $break) {
	    		if ($date >= $break['start'] && $date < $break['end']) {
	    			$temp[] = $date->format($this->settings['initial_format']);
	    		}
			}
			if (!in_array($date->format($this->settings['initial_format']),$temp)) {
				
				$this->schedule[$j]['time'] = $date->format($this->settings['initial_format']);
		    	$this->schedule[$j]['periods'] = array();	
		    	$j++;
		    }
				
		}

		if ($this->settings['initial_preset']) {
	    	foreach($this->daterange as $i=>$date){
			    foreach($this->rows as $j=>$intersection) {
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
    
    public function addToLog($str){
    	$this->log[] = $str;
    }

    public function showLog(){
    	foreach($this->log as $str) {
    		echo "$str\n";
    	}
    }

	private function fillSlots(){

    	foreach($this->rows as $user=>$intersection) {
		   	// найдем период в котором указан маркер
		    foreach ($intersection['intersection'] as $column=>$marker) { 
		    	$marker = trim($marker);

		    	// начнем заполнять все ячейки которые помечены маркером
		    	if (strlen($marker)<3 && ($marker)) {

			    	$notsaved = true; 
			    	$startPeriod = $currentPeriod = $pers = 0;
			    	$prohod = 0;
			    	$koef = $this->columns[$column]['pers'];

		    		for($z=0;$z<$koef;$z++){

		    		}

			    	$i=0;

			    	do {

			    		$interupt = false;
						
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
			   				}
			    		}
			    		if (!$periodBusyUser && !$periodBusyComp )  {
			    			$this->schedule[$currentPeriod]['periods'][] = array(
			   					'columnValue' => $column,'rowValue' => $user
			   				);
			   				$notsaved = false;
			    		}
			    		
			    		if ( ( $i == (  count($this->schedule)*$koef - 1) ) && $notsaved ) {
			    			$this->addToLog("Not found slot for user={$user}, column={$column}");
			    			$interupt = true;
			    		}

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
	
	private function countPersBusyForPeriod($currentPeriod, $column) {
    	$counter = 0;
    	$periods = $this->schedule[$currentPeriod];
    	foreach($periods['periods'] as $period) {
	    	if ($period['columnValue'] == $column) {
	    		$counter++;
	    	}
    	}
    	return $counter;
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
    	foreach($this->rows as $compkey=>$company) {
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

	private function outputCSV($data) {
		global $filename;
        header("Content-type: text/csv");
    	header("Content-Disposition: attachment; filename={$filename}");
    	header("Pragma: no-cache");
    	header("Expires: 0");

        $outputBuffer = fopen("php://output", 'w');
        foreach($data as $val) {
            fputcsv($outputBuffer, $val);
        }
        fclose($outputBuffer);
    }

	public function makeSchedule(){

		$this->initTime();
		$this->fillSlots();

	}

	public function outputScheduleCSV(){
		$this->scheduleCSV();
	}

	public function outputSchedule(){

		//TODO output HTML Table

	}
}