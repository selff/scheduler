<?php

namespace Schedule;

use \DateTime;
use \DateInterval;
use \DatePeriod;
use Exception\SchedulerException;
use Schedule\ISchedulerGenerator;

/**
 * ShedulerGenerator - prepare Schedule from dummy grid
 *
 *
 * @author  Andrey Selikov <selffmail@gmail.com>
 * @since   November 10, 2016
 * @link    https://github.com/selff/Scheduler
 * @version 1.0.2
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
    public $settings = array(
        'initial_preset' => true,
        'find_initial_time' => false,
        'initial_format' => 'H:i:s',
    );

    /**
     * Result columns of output table
     * @var array
     */
    protected $columns = array();

    /**
     * Result rows of output table
     * @var array
     */
    protected $rows = array();

    /**
     * Prepare output table
     * @var array
     */
    protected $schedule = array();


    /**
     * Generate schedule data
     *
     * @param array with input data
     * @return array with schedule result
     */
    public function generateSchedule($data)
    {

        $this->loadData($data);

        if (empty($this->rows) || empty($this->columns)) {
            throw new SchedulerException('Input data does not contain the required data');
        }

        $this->initTime();

        $this->createSchedule();

        if (empty($this->schedule)) {
            throw new SchedulerException('Error create schedule for data you entered');
        }

        return $this->generateResultGrid();
    }

    /**
     * Load input data as dummy table and initialize $columns and $rows for output
     *
     * @param array $rows
     * @return void
     */
    public function loadData($rows)
    {

        // по строкам
        foreach ($rows as $i => $row) {
            // по столбцам
            foreach ($row as $k => $v) {
                // первая строка содержит названия компаний
                if (0 == $i) {
                    if ($k > 3) $this->columns[($k - 4)]['name'] = $v;
                    // вторая строка содержит кол-во персонала компаний
                } elseif (1 == $i) {
                    if ($k > 3) $this->columns[($k - 4)]['persons'] = $v ? (int)$v : 1;
                    // начиная с третьей строки
                } else {
                    switch ($k) {
                        case 0:
                            $this->rows[($i - 1)]['name'] = $v;
                            break;
                        case 1:
                            $this->rows[($i - 1)]['type'] = $v;
                            break;
                        case 2:
                            $this->rows[($i - 1)]['priority'] = (int)$v;
                            break;
                        case 3:
                            $this->rows[($i - 1)]['face'] = $v;
                            break;
                        default:
                            $this->rows[($i - 1)]['intersection'][($k - 4)] = $v;
                            break;
                    }
                }
            }
        }
    }

    /**
     * Init time slots in output shedule table
     *
     * @param void
     * @return void
     */
    protected function initTime()
    {
        $j = 0;
        foreach ($this->daterange as $i => $date) {

            // find break periods
            $break_times = array();
            foreach ($this->breaks as $break) {
                if ($date >= $break['start'] && $date < $break['end']) {
                    $break_times[] = $date->format($this->settings['initial_format']);
                }
            }

            // if time not in a break_times - put to schedule
            if (!in_array($date->format($this->settings['initial_format']), $break_times)) {

                $this->schedule[$j]['time'] = $date->format($this->settings['initial_format']);
                $this->schedule[$j]['periods'] = array();
                $j++;
            }

        }

        if ($this->settings['initial_preset']) {
            foreach ($this->daterange as $i => $date) {
                foreach ($this->rows as $j => $intersection) {
                    foreach ($intersection['intersection'] as $k => $value) {
                        if ($value == $date->format($this->settings['initial_format'])) {
                            $this->schedule[$i]['periods'][] = array('columnValue' => $k, 'rowValue' => $j);
                            $this->settings['find_initial_time'] = true;
                        }
                    }
                }
            }
        }
    }

    /**
     * Create shedule table
     *
     * @param void
     * @return void
     */
    protected function createSchedule()
    {
        // for priority=1
        $this->fillSlots(1);
        // for priority=0
        $this->fillSlots(0);
    }

    /**
     * Fill shedule table for priority
     *
     * @param void
     * @return void
     */
    protected function fillSlots($priority)
    {
        foreach ($this->rows as $user => $row) {
            // найдем период в котором указан маркер
            foreach ($row['intersection'] as $column => $marker) {
                $marker = trim($marker);
                // начнем заполнять все ячейки которые помечены маркером
                if (strlen($marker) < 3 && ($marker) && $row['priority']==$priority) {
                    $this->fillSlot($user, $column);
                }
            }
        }
    }

    protected function fillSlot($user, $column)
    {
        $notSaved = true;
        $currentPeriod = $persons = 0;
        $iteration = 0;
        $ratio = $this->columns[$column]['persons'];
        $i = 0;
        do {

            $interupt = false;
            $periodBusyComp = false;
            $periodBusyUser = false;
            // проверим может этот интервал уже использован
            if (isset($this->schedule[$currentPeriod])) {
                if (count($this->schedule[$currentPeriod]['periods'])) {
                    foreach ($this->schedule[$currentPeriod]['periods'] as $k => $period) {

                        // в данный период пользователь уже использован
                        if ($period['rowValue'] == $user) {
                            $periodBusyUser = true;
                        }

                        // в данный период слот компании использован
                        if ($period['columnValue'] == $column
                            && ($iteration < $this->countPersBusyForPeriod($currentPeriod, $column))) {
                            $periodBusyComp = true;
                        }
                    }
                }
            }
            if (!$periodBusyUser && !$periodBusyComp) {
                $this->schedule[$currentPeriod]['periods'][] = array(
                    'columnValue' => $column, 'rowValue' => $user
                );
                $notSaved = false;
            }

            if (($i == (count($this->schedule) * $ratio - 1)) && $notSaved) {
                //Not found slot for user={$user}, column={$column}
                $interupt = true;
            }

            $i++;
            $currentPeriod++;

            if ($currentPeriod == count($this->schedule)) {
                $currentPeriod = 0;
                $iteration++;
                //if ($this->countPersBusyForPeriod($currentPeriod, $column) > $persons) $persons++;
            }

        } while ($notSaved && !$interupt);
    }

    /**
     * Count how persons busy for period in company (input table have persons limit)
     *
     * @param int $currentPeriod
     * @param int $column
     * @return int
     */
    protected function countPersBusyForPeriod($currentPeriod, $column)
    {
        $counter = 0;
        $periods = $this->schedule[$currentPeriod];
        foreach ($periods['periods'] as $period) {
            if ($period['columnValue'] == $column) {
                $counter++;
            }
        }
        return $counter;
    }

    /**
     * Fill shedule table
     *
     * @param void
     * @return array
     */
    public function generateResultGrid()
    {

        $data = array();
        $row = array('Company', 'Type', 'Priority' ,'User');
        foreach ($this->columns as $c) {
            $row[] = $c['name'];
        }
        $data[] = $row;
        $row = array('persons:', '', '');
        foreach ($this->columns as $c) {
            $row[] = $c['persons'];
        }
        $data[] = $row;
        foreach ($this->rows as $compkey => $company) {
            $row = array();
            $row[] = $company['name'];
            $row[] = $company['type'];
            $row[] = $company['face'];
            foreach ($this->columns as $columnkey => $column) {
                $marker = '';
                foreach ($this->schedule as $id => $shedule) {
                    foreach ($shedule['periods'] as $period) {
                        if (($period['columnValue'] == $columnkey) && ($period['rowValue'] == $compkey)) {
                            $marker = $shedule['time'];
                        }
                    }
                }
                if ($marker == '') {
                    foreach ($company['intersection'] as $id => $m) {
                        if (($id == $columnkey) && (strlen(trim($m)) > 0)) {
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