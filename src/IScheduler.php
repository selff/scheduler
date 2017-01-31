<?php

namespace Schedule;

interface IScheduler 
{
	
	public function initSettings($data);
	public function loadData($rows);
	public function makeSchedule();
	public function outputSchedule();

}