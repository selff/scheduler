<?php

namespace Schedule;

interface IScheduler 
{
	
	public function loadSettings($data);
	public function loadData($rows);
	public function makeSchedule();
	public function outputSchedule();

}