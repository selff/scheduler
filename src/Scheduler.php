<?php

namespace Schedule; 

use Schedule\SchedulerGenerator;
use Schedule\SchedulerException;
use Schedule\ISchedulerGenerator;
use \DateTime;
use \DatePeriod;
use \DateInterval;

/**
 * Sheduler - send input data to SchedulerGenerator and output Schedule grid
 *
 *
 * @author  Andrey Selikov <selffmail@gmail.com>
 * @since   November 10, 2016
 * @link    https://github.com/selff/Scheduler
 * @version 1.0.1
 */

class Scheduler 
{
	
	protected $SchedulerGenerator = null;

	protected $uploaddir = '/tmp';

	protected $filename = '';
	protected $savefile = '';

	protected $inidir = './csv';
    protected $outdir = './csv';

	protected $example_dummy_grid = 'example2.csv';
	
	protected $scheduler_grid = array();

	public function __construct(ISchedulerGenerator $SchedulerGenerator){

			$this->SchedulerGenerator = $SchedulerGenerator;
			//$this->uploaddir = ini_get('upload_tmp_dir');
	}

    /**
     * Validate post user data
     *
     * @param array $post
     * @return array $data
	 */	
	protected function validatePost($post){

		$data = array();
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
	protected function initSettings($options){
	
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
     * Load input user data , and init $this->scheduler_grid[]
     *
	 */	
	public function initFromPost(){

		$csv = array();
		if (isset($_POST['submit'])) {
			
			$options = $this->validatePost($_POST);
			$this->initSettings($options);

				//file upload 
				$tmp_name = $_FILES["userfile"]["tmp_name"];
				$filename = basename($_FILES["userfile"]["name"]);
				move_uploaded_file($tmp_name, $this->uploaddir.'/'.$filename);
				if ($_FILES["userfile"]["error"] == UPLOAD_ERR_OK) {

					// read user file
					$this->filename = $filename;
					$lines = file($this->uploaddir.'/'.$filename, FILE_IGNORE_NEW_LINES);

				} else {

					// read example file
					$this->filename = $this->example_dummy_grid;
					$lines = file($this->inidir.'/'.$this->example_dummy_grid, FILE_IGNORE_NEW_LINES);

				}
				if ($lines) {
					foreach ($lines as $key => $value) { 
						$csv[$key] = str_getcsv($value); 
					}
				} else {

					throw new SchedulerException('Input file is empty :(');

				}

		}
			
		$this->scheduler_grid = $this->SchedulerGenerator->generateSchedule($csv);
        if (isset($_POST['fileSave'])) {
            $this->savefile = $this->outdir.DIRECTORY_SEPARATOR.uniqid('scheduler-'.date("YmdHis").'-').'.csv';
            $this->SchedulerGenerator->saveCSV($this->savefile);
        }
	}

    /**
     * Make table structur
     *
     * @return string $output
	 */	
	public function outputTable() {
		$output = "<p>Generated schedule table:</p>".PHP_EOL;
		$output .= "<table class=\"table\">";
		foreach ($this->scheduler_grid as $i=>$row) {
			$output .= "<tr class=\"row-{$i}\">";
				foreach ($row as $j=>$column) {
					$output .= "<td class=\"column-{$j}\">{$column}</td>";
				}
			$output .= "</tr>";
		}
		$output .= "</table>".PHP_EOL;
		if ($this->savefile) {
		    $output.="<p><a class=\"text-danger\" href='".$this->savefile."'>Download result</a></p>".PHP_EOL;
        }
		return $output;
    }

}