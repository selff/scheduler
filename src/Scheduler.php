<?php

namespace Schedule; 

use Schedule\SchedulerGenerator;
use Schedule\SchedulerException;
use Schedule\ISchedulerGenerator;
use \DateTime;
use \DatePeriod;
use \DateInterval;


class Scheduler 
{
	
	public $SchedulerGenerator = null;

	private $uploaddir = '/tmp/';

	private $filename = '';

	private $scheduler_data = []; 

	public function __construct(ISchedulerGenerator $SchedulerGenerator){

			$this->SchedulerGenerator = $SchedulerGenerator;

	}

    /**
     * Validate post user data
     *
     * @param array $post
     * @return array $data
	 */	
	private function validatePost($post){

		$data = [];
		if (isset($post['slot']) && preg_match("~[0-9]+~",$post['slot'])) $data['slot'] = $post['slot'];
		else throw new SchedulerException('Input field "Slot duration" is wrong');
		if (isset($post['start']) && preg_match("~\d{2}:\d{2}+~",$post['start'])) $data['start'] = $post['start'];
		else throw new SchedulerException('Input field "Event start time" is wrong');
		if (isset($post['end']) && preg_match("~\d{2}:\d{2}+~",$post['end'])) $data['end'] = $post['end'];
		else throw new SchedulerException('Input field "End time" is wrong');
		if (isset($post['breaks']) && preg_match("~^(\d{2}:\d{2}-\d{2}:\d{2},?\s?)+$~",$post['breaks'])) $data['breaks'] = $post['breaks'];
		else throw new SchedulerException('Input field "Possible pauses" is wrong');	
			
		return $data;
	}

    /**
     * Init settings before generate schedule
     *
     * @param array $options
     * @return void
	 */	
	private function initSettings($options){
	
		// Is input data consist initial preset times for meeting?
		$this->SchedulerGenerator->settings['initial_preset'] = true;
		$this->SchedulerGenerator->settings['find_initial_time'] = false;
		$this->SchedulerGenerator->settings['initial_format'] = 'H:i:s';

		$breaks = explode(",",$options['breaks']);
		foreach($breaks as $break){
			$b = trim($break);
			if (preg_match("~^([0-9]{2}):([0-9]{2})-([0-9]{2}):([0-9]{2})$~",$b,$m)) {
				$this->SchedulerGenerator->breaks[] = array(
					'start' => new DateTime(date("Y-m-d").' '.$m[1].':'.$m[2].':00'),
					'end' => new DateTime(date("Y-m-d").' '.$m[3].':'.$m[4].':00'),
				);
			}
		}
		
		$this->SchedulerGenerator->daterange = new DatePeriod(
			new DateTime(date("Y-m-d").' '.$options['start'].':00'), 
			new DateInterval('PT'.$options['slot'].'M'),
			new DateTime(date("Y-m-d").' '.$options['end'].':00')
		);		
	}


    /**
     * Load input user data , and init $this->scheduler_data[]
     *
     * @param void
     * @return void
	 */	
	public function initFromPost(){

		$csv = array();
		if (isset($_POST['submit'])) {
			
			$options = $this->validatePost($_POST);
			$this->initSettings($options);

			if (isset($_FILES["userfile"])) {

				//file upload 
				$tmp_name = $_FILES["userfile"]["tmp_name"];
				$filename = basename($_FILES["userfile"]["name"]);
				move_uploaded_file($tmp_name, $this->uploaddir.$filename);
				if ($_FILES["userfile"]["error"] == UPLOAD_ERR_OK) {
					$this->filename = $filename;
					// read file
					$lines = file($this->uploaddir.$filename, FILE_IGNORE_NEW_LINES);
					foreach ($lines as $key => $value) { 
						$csv[$key] = str_getcsv($value); 
					}
				} else {
					throw new SchedulerException('File not upload :(');
				}
			} else {
				throw new SchedulerException('Where is a file?');
			}
		}
			
		$this->scheduler_data = $this->SchedulerGenerator->makeSchedule($csv);

	}

    /**
     * Write data to csv file
     *
     * @param array $data
     * @param string $filename
	 */	
	private function outputCSV() {
		
        header("Content-type: text/csv");
    	header("Content-Disposition: attachment; filename={$this->filename}");
    	header("Pragma: no-cache");
    	header("Expires: 0");

        $outputBuffer = fopen("php://output", 'w');
        foreach($this->scheduler_data as $val) {
            fputcsv($outputBuffer, $val);
        }
        fclose($outputBuffer);
    }

    /**
     * Make table structur
     *
     * @param array $data
     * @return string $output
	 */	
	public function outputTable() {
		$output = "<p>Generated schedule table:</p>";
		$output .= "<table class=\"table\">";
		foreach ($this->scheduler_data as $row) {
			$output .= "<tr>";
				foreach ($row as $column) {
					$output .= "<td>{$column}</td>";
				}
			$output .= "</tr>";
		}
		$output .= "</table>";
		return $output;
    }

}