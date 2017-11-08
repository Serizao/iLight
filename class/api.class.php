<?php

class api
{
   	public function __construct(){
   		$this->_bdd  			= new bdd();
   		$this->_user 			= new user();
   		$this->_apiKey			= 'U8yCpi7yc6kVtBDDqfnmLyPm4LEk4FgjRfdrXcNLPzdFstnoUW';
   		$this->_idUser			= '';
   		$this->_keyLength		= '125';
   	}
   	public function checkRequestArray($number='2'){
   		if(isset($this->_requestArray[$number]) and !empty($this->_requestArray[$number])){
   			$return=true;
   		} else {
   			$return=false;
   		}
   		return $return;
   	}
   	public function get_auth(){
   		$username=$this->_requestArray['2'];
   		$password=$this->_requestArray['3'];
   		$authState=$this->_user->auth($username,$password,0,1);
   		if($authState['0']){
   			$token = bin2hex(openssl_random_pseudo_bytes($this->_keyLength));
   			$this->_bdd->cache('INSERT INTO token_api SET id=?, id_user=?, token=?, last_use=? ', array(
   				'',
   				$authState['1'],
   				$token,
   				date("Y-m-d")
   			));
   			$this->_bdd->exec();
   			$return['token']	= $token;
   			$return['idUser']	= $authState['1'];
   			$this->display('success',array($return));
   		} else {
   			$this->display('unauthorized','login error');
	   		$return = false;
   		}
   	}
   	public function get_inscription(){
   		$username=base64_decode($this->_requestArray['2']);
   		$password=base64_decode($this->_requestArray['3']);
   		$birth=base64_decode($this->_requestArray['4']);
   		$registerState=$this->_user->inscription($username,$password,$birth,1);
   		if($registerState){
   			$this->display('success','Vous êtes à présent enregistré vous pouvez maintenant vous authentifié');
   		} else {
   			$this->display('badRequest','L\'utilisateur existe déjà');
   		}
   		
   	}
   	//reset password send mail
   		public function get_forgotPassword(){
   		$mail=$this->_requestArray['2'];
   		$this->_user->mail_pass_lost($mail,1);
   		$this->display('success','OK');
   		
   	}
//checklogin
   	public function checkToken($fonction=0){
   		if($this->checkRequestArray('0')){
   			$this->_bdd->cache('select id_user from token_api where token=?',array($this->_requestArray['0']));
   			$userToken=$this->_bdd->exec();
   	   		if($this->_requestArray['0']==$this->_apiKey ){
   	   				if($fonction=='1'){
	   						$return = false;
	   					} else {
	   						$return = true;
	   					}
	   		} else {
	   			if(isset($userToken['0']['0']['id_user'])){
	   				if($userToken['0']['0']['id_user']!=''){
	   					if($fonction=='1'){
	   							$return = $userToken['0']['0']['id_user'];
	   						} else {
	   							$return = true;
	   						}
	   					} else {
	   						$return = true;
	   					}
	   				} else {
	   					$this->display('unauthorized','login error');
	   					$return = false;
	   				}
	   			}
	   	} else {
	   		$this->display('badRequest','token missing');
	   		$return = false;
	   	}
	   	return $return;
   	}
//utiliser pour les paramètre en url
   	public function get(){
   		$this->_requestArray = str_replace($_SERVER['DOCUMENT_URI'], "", $_SERVER['REQUEST_URI']);
   		$this->_requestArray = explode('/', trim($this->_requestArray,'/'));
   		$this->_request      = $this->_requestArray; //pour eviter de modifier le tableau
   		if(isset($this->_request['1'])){
			$this->_request 	 = preg_replace('/[^a-z0-9_]+/i','',$this->_request['1']);
	   		if(isset($this->_request) and !empty($this->_request)){
	   			if($this->checkToken()){
	   				$this->method();
	   			}
  			}
		}
   	}
//retourn la manière dons le serveur a été interroger (GET POST)
   	public function method(){
   		unset($this->_content);
   		$this->_method = strtoupper($_SERVER['REQUEST_METHOD']);
	   		switch ($this->_method) {
	   		 	case 'GET':
			    	 $this->_parameters = $_GET;
			    	 $this->tryMethod('get_'.$this->_request);
			    	break;
			 	case 'PUT':
			    	parse_str(file_get_contents('php://input'), $this->_parameters);
			    	$this->tryMethod('put_'.$this->_request);
			    	break;
				case 'POST':
			    	$this->_parameters = $this->param();
			    	$this->tryMethod('post_'.$this->_request);
			    	break;
				case 'DELETE':
					parse_str(file_get_contents('php://input'), $this->_parameters);
					break;
			}
	}
	public function param(){  //recupère les parametre POST
		$json = file_get_contents('php://input');
		$obj = json_decode($json);
		$obj=(array) $obj;
		return $obj;
	}
	//test pour voir si la method existe
	public function tryMethod($method=''){
		if(method_exists($this, $method) and !empty($method)){
		    $this->{$method}();
		} else {
			$this->display('badRequest','Unknow command');
		}
	}
  public function get_removeCart(){
    if(isset($this->_requestArray['2']) and !empty($this->_requestArray['2'])){
      $this->_bdd->cache('select * from cart where id=? and id_user=?',array($this->_requestArray['2'],$this->checkToken(1)));
      $result=$this->_bdd->exec();
      if($result[0]){
        $this->_bdd->cache('delete from cart where id=? and id_user=?',array($this->_requestArray['2'],$this->checkToken(1)));
        $this->_bdd->cache('delete from cart_ingredient where id_cart=?',array($this->_requestArray['2']));
        $this->_bdd->cache('delete from cart_recipe where id=?',array($this->_requestArray['2']));
        $this->_bdd->exec();
        $this->display('success', 'OK');
      } else {
        $this->display('badRequest','The cart is not yourth');
      }
    } else {
			$this->display('badRequest','Parameter is missing');
		}
  }
	public function get_personByRecipe(){
		if(isset($this->_requestArray['2']) and !empty($this->_requestArray['2']) and isset($this->_requestArray['3']) and !empty($this->_requestArray['3'])){
			$this->_bdd->cache('SELECT person FROM `cart_recipe` WHERE id_cart=? and id_recipe=?',array($this->_requestArray['2'], $this->_requestArray['3']));
			$result=$this->_bdd->exec();
			$this->display('success', $result[0]);
		} else {
			$this->display('badRequest','Parameter is missing');
		}
	}
// utilisé dans choose.php pour la récupération des id repas en random et également les détails des repas en cas d'indication d'un id.
	public function get_repas(){
		if(isset($this->_requestArray['2']) and !empty($this->_requestArray['2'])){
			$this->_bdd->cache('select * from recipe where id=?',array($this->_requestArray['2']));
			$result=$this->_bdd->exec();
			$path = '/var/www/html/recipe-img/'.$this->_requestArray['2'].'.'.$result[0][0]['picture'];
			$type = pathinfo($path, PATHINFO_EXTENSION);
			$data = file_get_contents($path);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
			$result[0][0]['photo']=$base64;
			//$result[0][0]['photo']='http://'.$_SERVER['HTTP_HOST'].'/img-recipe/'.$this->_requestArray['2'].'.'.$result[0][0]['picture'];
			$result[0][0]['ing']=$this->ingredientThumb($this->_requestArray['2']);
			$result[0][0]['ingD']=$this->ingredientThumb($this->_requestArray['2'], 1);
		} else {
			$this->_bdd->cache('select id from recipe where 1 ORDER BY RAND() ','');
			$result=$this->_bdd->exec();
		}
		$this->display('success',$result );
	}

/
	//utilisé pour savoir si l'utisateur est connecté
	public function get_checkLogin(){
		$state='False';
		if($this->checkToken()){
			$state='True';
		}
		$this->display('success',$state);
	}
	public function post_logout(){
		user::logout();
		$this->display('success','ok');
	}
	public function post_changePassword(){
		if($this->checkToken()){
			if($this->_parameters['password']==$this->_parameters['password2']){
				$this->_user->changePassword($this->_parameters['password'],$this->_parameters['password2'],True);
				$this->display('success','ok');
			} else {
				$this->display('badRequest',"les password ne sont pas identique");
			}
		} else {
			$this->display('badRequest',"utilisateur non connecter");
		}
	}
	// authentification
	public function post_auth(){
		if(isset($this->_parameters['user']) and !empty($this->_parameters['user'])){
			if(isset($this->_parameters['password']) and !empty($this->_parameters['password'])){
				$user=new user();
				if($user->auth($this->_parameters['user'],$this->_parameters['password'])){
					$this->display('success','ok');
				}
			} else {
				$this->display('badRequest',"il manque le mot de passe");
			}
		} else {
			$this->display('badRequest',"il manque le nom d'utilisateur");
		}
	}
	// utilisé pour le retour de la fontcion
	public function display($status='forbidden', $content=''){
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
		header('Content-Type: application/json');
		switch($status){
			case 'success':
				header('HTTP/1.1 200 OK');
				header("Content-Type: application/json;charset=utf-8");
				break;
			case 'badRequest':
				header('HTTP/1.1 400 Bad Request');
				break;
			case 'unauthorized':
				header('HTTP/1.1 401 Unauthorized');
				break;
			case 'created':
				header('HTTP/1.1 201 Created');
				break;
			case 'forbidden':
				header('HTTP/1.1 403 Forbidden');
				break;
		}
		if(isset($content) and !empty($content)){
			$content = ["status"=>$status,"msg"=>$content];
			echo json_encode($content);
		}
	}
}
