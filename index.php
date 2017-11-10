<?php
$index = ['h1'=>'toto'];
$time_start = microtime(true);
include('include/autoload.php');
$view = new view('view/index');
$view->set('title', 'Mybest site');
$view->set('index', $index);
echo $view->render();
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "generate in $time secondes\n";
