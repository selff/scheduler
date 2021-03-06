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

    protected $uploadDir;
    protected $saveFile;
    protected $iniDir;
    protected $outDir;
    protected $example_dummy_grid;

    protected $scheduler_grid = array();

    public function __construct(ISchedulerGenerator $SchedulerGenerator)
    {
        $this->SchedulerGenerator = $SchedulerGenerator;
        $this->init();
    }

    protected function init()
    {
        if (!file_exists(__DIR__.'/../config.php')) {
            throw new SchedulerException('File not found: '.__DIR__.'/config.php');
        } else {
            $config = require (__DIR__.'/../config.php');
            foreach ($config['parameters'] as $key=>$param) {
                if(property_exists($this, $key)) {
                    $this->$key = $param;
                }
            }
        }
    }

    /**
     * Validate post user data
     *
     * @throws
     * @param array $post
     * @return array $data
     */
    protected function validatePost($post)
    {

        $data = array();
        if (isset($post['slot']) && preg_match("~[0-9]+~", $post['slot'])) $data['slot'] = $post['slot'];
        else throw new SchedulerException('Input field "Slot duration" is wrong');
        if (isset($post['start']) && preg_match("~\d{2}:\d{2}+~", $post['start'])) $data['start'] = $post['start'];
        else throw new SchedulerException('Input field "Event start time" is wrong');
        if (isset($post['end']) && preg_match("~\d{2}:\d{2}+~", $post['end'])) $data['end'] = $post['end'];
        else throw new SchedulerException('Input field "End time" is wrong');
        if (isset($post['breaks']) && preg_match("~^(\d{2}:\d{2}-\d{2}:\d{2},?\s?)+$~", $post['breaks'])) $data['breaks'] = $post['breaks'];
        else throw new SchedulerException('Input field "Possible pauses" is wrong');

        return $data;
    }

    /**
     * Init settings before generate schedule
     *
     * @throws
     * @param array $options
     * @return void
     */
    protected function initSettings($options)
    {

        // Is input data consist initial preset times for meeting?
        $this->SchedulerGenerator->settings['initial_preset'] = true;
        $this->SchedulerGenerator->settings['find_initial_time'] = false;
        $this->SchedulerGenerator->settings['initial_format'] = 'H:i';

        $breaks = explode(",", $options['breaks']);
        foreach ($breaks as $break) {
            $b = trim($break);
            if (preg_match("~^([0-9]{2}):([0-9]{2})-([0-9]{2}):([0-9]{2})$~", $b, $m)) {
                $this->SchedulerGenerator->breaks[] = array(
                    'start' => new DateTime(date("Y-m-d") . ' ' . $m[1] . ':' . $m[2] . ':00'),
                    'end' => new DateTime(date("Y-m-d") . ' ' . $m[3] . ':' . $m[4] . ':00'),
                );
            }
        }

        $this->SchedulerGenerator->daterange = new DatePeriod(
            new DateTime(date("Y-m-d") . ' ' . $options['start'] . ':00'),
            new DateInterval('PT' . $options['slot'] . 'M'),
            new DateTime(date("Y-m-d") . ' ' . $options['end'] . ':00')
        );
    }


    /**
     * Load input user data , and init $this->scheduler_grid[]
     *
     */
    public function run()
    {
        $csv = array();
        if (isset($_POST['submit'])) {

            $options = $this->validatePost($_POST);
            $this->initSettings($options);

            if (preg_match('~^[,;]{1}$~u', $_POST['separator'])) {
                $separator = $_POST['separator'];
            } else {
                $separator = ",";
            }

            //file upload
            $tmp_name = $_FILES["userfile"]["tmp_name"];
            $fileName = basename($_FILES["userfile"]["name"]);
            move_uploaded_file($tmp_name, $this->uploadDir . '/' . $fileName);
            if ($_FILES["userfile"]["error"] == UPLOAD_ERR_OK) {
                $fileName = $this->uploadDir . '/' . $fileName;
            } else {
                $fileName = $this->getExamplePath();
            }

            $csv = $this->loadCsv($fileName,$separator);

            if (empty($csv)) {
                throw new SchedulerException('Input file is empty :(');
            } else {
                foreach($csv as $k=>$v) {
                    if (empty($v)) unset($csv[$k]);
                }
            }
        }

        $this->scheduler_grid = $this->SchedulerGenerator->generateSchedule($csv);

        $this->saveFile = $this->outDir . DIRECTORY_SEPARATOR . 'scheduler' . '.csv';
        $this->SchedulerGenerator->saveCSV($this->saveFile,$separator);

    }

    public function getExamplePath()
    {
        return $this->iniDir . '/' . $this->example_dummy_grid;
    }

    public function loadCsv($fileName,$separator=",")
    {
        $csv = [];
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            do {
                $result = fgetcsv($handle,0, $separator);
                $csv[] = $result;
            } while ($result);
            fclose($handle);
        }
        return $csv;
    }

    /**
     * Make table structur
     *
     * @return string $output
     */
    public function outputTable()
    {
            $output = "<div class='row'><div class='col-md-12'>";
            $output .= "<p>Generated schedule table for time: ";

            $output .= $this->SchedulerGenerator->getPeriods();

            $output .= "</p>" . PHP_EOL;
            $output .= "<table class=\"table\">";
            foreach ($this->scheduler_grid as $i => $row) {
                $output .= "<tr class=\"row-{$i}\">";
                foreach ($row as $j => $column) {
                    if ($i==0) {
                        $output .= "<th>{$column}</th>";
                    } else {
                        $output .= "<td class=\"column-{$j}\">{$column}</td>";
                    }
                }
                $output .= "</tr>";
            }
            $columns = [];
            foreach ($this->scheduler_grid as $i => $row) {
                if ($i>2){
                    foreach ($row as $j => $column) {
                        if ($j>3 && $this->SchedulerGenerator->isMarker($column)) {
                            $columns[$j][] = $row[0];
                        } else {
                            if (!isset($columns[$j])) $columns[$j] = [];
                        }
                    }
                }
            }
            $output .= "<tr>";
            foreach ($columns as $j=>$arr){
                if ($j>3 && count($arr)>0) {
                    $output .= "<td>".implode(",\n",$arr)."</td>";
                } else {
                    $output .= "<td></td>";
                }
            }
            $output .= "</tr>";
            $output .= "</table>" . PHP_EOL;
            $output .= "<p><a href='index.php'>&larr; Return home</a>";
            if ($this->saveFile) {
                 $output .= " | <a class=\"text-danger\" href='" . $this->saveFile . "'>Download result &#9660;</a>";
            }
            $output .= "</p>" . PHP_EOL;
            $output .= "</div></div>";
            return $output;
    }

}