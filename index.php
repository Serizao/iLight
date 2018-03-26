<?php
include('include/autoload.php');
$router = new router($_GET['url']);
$router->get('/:titi', function($titi){ echo "Bienvenue sur ma homepage !"; });
$router->get('/posts/:id', function($id){ echo "Voila l'article $id"; });

