<?php
ini_set('auto_detect_line_endings', TRUE);
require './vendor/autoload.php';

use Schedule\Scheduler;
use Schedule\SchedulerGenerator;
use Schedule\SchedulerException;
use Schedule\Auth;

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new SchedulerException($errstr, $errno);
});

session_start();
session_regenerate_id();
if (isset($_POST['login']) && isset($_POST['password'])) {

    if (Auth::nodb($_POST['login'], $_POST['password'])) {
        // auth okay, setup session
        $_SESSION['user'] = $_POST['login'];
        // redirect to required page
        //header("Location: index.php");

    } else {
        // didn't auth go back to loginform
        header("Location: loginform.php");
        exit;
    }

} elseif (!isset($_SESSION['user'])) {

    header("Location: loginform.php");
    exit;

}

$action = '';
foreach (['run', 'logout'] as $a) {
    if (isset($_GET[$a])) {

        $action = $a;
    }
}

switch ($action) {
    case 'run':

        try {

            $Scheduler = new Scheduler(new SchedulerGenerator());
            $Scheduler->run();
            $output = $Scheduler->outputTable();
            header("HTTP/1.1 200 OK");

        } catch (Exception $e) {

            header('HTTP/1.1 409 Conflict');
            $output =
                '<div class="bs-callout bs-callout-danger">' .
                '<h4>Oops! This is error: ' . $e->getMessage() . "</h4>" . PHP_EOL .
                '<p>Error in: ' . nl2br($e->getTraceAsString()) . "</p>" . PHP_EOL .
                '</div>';
        }

        include_once('./templates/header.html');
        print($output);
        include_once('./templates/footer.html');
        break;
    case 'logout':
        unset($_SESSION['user']);
        session_destroy();
        header("Location: loginform.php");
        break;
    default:

        include_once('./templates/header.html');

        $thetable = "<table class='table'>";
        $Scheduler = new Scheduler(new SchedulerGenerator());
        $csv = $Scheduler->loadCsv($Scheduler->getExamplePath());
        foreach ($csv as $i => $rows) {
            $thetable .= "<tr>";
            if (is_array($rows)) {
                foreach ($rows as $cell) {
                    if ($i == 0) {
                        $thetable .= "<th>{$cell}</th>";
                    } else {
                        $thetable .= "<td class='ctr gray'>{$cell}</td>";
                    }
                }
            }
            $thetable .= "</tr>";
        }
        $thetable .= "</table>";

        include_once('./templates/wellcome.php');
        include_once('./templates/footer.html');
        break;
}
	


