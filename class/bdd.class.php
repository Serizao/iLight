<?php
class bdd
  {
    public function __construct() //connection a la base de donnée dans la classe
    {
        $this->_data=array();
        $this->_cache=array();
        $this->_result=array();
        $this->_stmt ='';
        $this->_pdo = new PDO('mysql:host='.HOST_BDD.';charset=utf8;dbname='.BASE_BDD,USER_BDD,PASS_BDD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    }
    public function cache($requete,$data){
        $this->_cache[]=$requete;
        $this->_data[]=$data;
    }
    public function exec($protecXSS=0)
    {
        $number_req = count($this->_cache);
        for($r=0;$r<$number_req;$r++)
        {   
            $exclude_fetch = array('update', 'delete','insert');
            $req=explode(" ",$this->_cache[$r]);
            $i=0;
            if(isset($this->_data[$r]) and !empty($this->_data[$r]) and $this->_data[$r]!='')
            {
                $stmt = $this->_pdo->prepare($this->_cache[$r]);
                $taille=count($this->_data[$r]);
                for($s=0;$s<$taille;$s++)
                {
                    $i++;
                    if($protecXSS==0){
                        $value=htmlspecialchars(strip_tags($this->_data[$r][$s]), ENT_QUOTES);
                    } else {
                        $value=$this->_data[$r][$s];
                    }
                    $stmt->bindParam($i, $this->_data[$r][$s], PDO::PARAM_STR); 
                }
                try {
                    $stmt->execute();
                } catch (PDOException $e) {
                    echo 'Echec de la connexion : ' . $e->getMessage();
                    exit;
                }
                if(isset($req[0]) and !in_array(strtolower($req[0]), $exclude_fetch)){ //on verifie que la requete ne commence pas pas update delete etc...
                    $result[$r]=$stmt->fetchAll(); 
                } else {
                    $result= 'ok';
                }
                $this->_result=$result;
            }else{
                $stmt = $this->_pdo->prepare($this->_cache[$r]);
                $stmt->execute();
                if ($number_req>1){
                    $result[$r]=$stmt->fetchAll();
                } else {
                    if(isset($req[0]) and !in_array(strtolower($req[0]), $exclude_fetch)){ //on verifie que la requete ne commence pas pas update delete etc...
                    $result=$stmt->fetchAll();
                    } else {
                        $result= 'ok';
                    }
                } 
                $this->_result=$result;
            }
        }
      $this->_stmt = $stmt;
        $result=$this->_result;
        $this->clear_cache();
        return $result;
    }
    public function clear_cache(){
        unset($this->_cache);
        unset($this->_result);
        unset($this->_data);
    }
    public function lastid()
    {
        return $this->_pdo->lastInsertId();
    }
    public function countcol()
    {
        return $this->_stmt->columnCount();
    }
    public function countrow()
    {
        return $this->_stmt->rowCount();
    }
    
    
}
?>
