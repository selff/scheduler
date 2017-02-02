<?php

namespace Schedule;

use \DateTime;
use \DateInterval;
use \DatePeriod;
use \Exception;
use Schedule\ISchedulerGenerator;

/**
 * ShedulerGenerator - prepare Schedule from dummy grid
 *
 *
 * @author  Andrey Selikov <selffmail@gmail.com>
 * @since   November 10, 2016
 * @link    https://github.com/selff/Scheduler
 * @version 1.0.1
 */

class SchedulerGenerator implements ISchedulerGenerator
{

    /**
     * DateTime periods of breaks, with coma separate
     * @var array(DataTime $start,DataTime $end)
     */	
	public $breaks;
		
	/**
     * DatePeriod (begin event,slot interval,end event)
     * @var DatePeriod
     */
	public $daterange;

	/**
     * Some options 
     * @var array
     */
	public $settings = [
		'initial_preset' => true,
		'find_initial_time' => false,
		'initial_format' => 'H:i:s',
		];

	/**
     * Result columns of output table 
     * @var array
     */
	private $columns = [];

	/**
     * Result rows of output table 
     * @var array
     */
	private $rows = [];

	/**
     * Prepare output table 
     * @var array
     */
	private $schedule = [];


    /**
     * Generate shedule data
     *
     * @param void
	 */	
	public function generateSchedule($data){

		$this->loadData($data);
		$this->initTime();
		$this->fillSlots();
		return $this->generateResultGrid();
	}

    /**
     * Load input data as dummy table and initialize $columns and $rows for output
     *
     * @param array  $rows
	 */
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
		if (empty($this->rows) || empty($this->columns)) throw new SchedulerException('Input data does not contain the required data');
	}

    /**
     * Init time slots in output shedule table
     *
     * @param void
	 */
	private function initTime(){
    	$j=0;
    	foreach($this->daterange as $i=>$date){
    		
    		// find break periods
    		$break_times = array();
	    	foreach ($this->breaks as $break) {
	    		if ($date >= $break['start'] && $date < $break['end']) {
	    			$break_times[] = $date->format($this->settings['initial_format']);
	    		}
			}

			// if time not in a break_times - put to schedule
			if (!in_array($date->format($this->settings['initial_format']),$break_times)) {
				
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
			    			$this->settings['find_initial_time'] = true;

						}
			    	}
			    }
			}
		}
	}
    
    /**
     * Fill shedule table
     *
     * @param void
	 */	
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
			   						if ( $period['rowValue'] == $user ) { 
			   							$periodBusyUser = true; 
			   						}
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
			    			//Not found slot for user={$user}, column={$column}
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
   		
   		if (empty($this->schedule)) throw new SchedulerException('Failed to schedule the data you entered');
    }

    /**
     * Count how persons busy for period in company (input table have persons limit)
     *
     * @param int $currentPeriod
     * @param int $column
	 */		
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

    /**
     * Fill shedule table
     *
     * @param array $data
	 */	
    public function generateResultGrid(){
    	$data = array();
    	$row = array('Company','Type','User');
    	foreach($this->columns as $c) {
    		$row[] = $c['name'];
    	}
    	$data[] = $row;
    	$row = array('persons:','','');
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
    	return $data;
    }


}