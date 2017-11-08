<?php
function chargerClasse($classe)
{
    if(file_exists('./class/'.$classe . '.class.php')){ //suivant d'ou la classe est appeler
        require './class/'.$classe . '.class.php';
    }
    if(file_exists('../class/'.$classe . '.class.php')){
        require '../class/'.$classe . '.class.php';
    }
}
spl_autoload_register('chargerClasse');
