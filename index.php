<?php
$time_start = microtime(true);
include('include/autoload.php');
$view = new view('index');
if ($view->find('user')) {
    $view->replace('user', $_GET['titi']);
    $view->replace('word1', 'lemot');
    $view->render();
}
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "generate in $time secondes\n";
