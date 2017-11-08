<?php
class user
  {
    public function __construct(){
        user::session();
        $this->_bdd=new bdd;
        $this->_colusername='mail';  //username colonne
        $this->_colpassword='password';  //password colonne
        $this->_coluserid='id';  //user id colonne
        $this->_colbirth='birth'; //birth colonne
        $this->_tabuser='user';  //user table
        $this->_tabGuser='Guser';  //user table google
        if(isset($_SESSION['id']) and $_SESSION['id']!=''){
            $this->_userid=$_SESSION['id'];
        }
        else $this->_userid='';
        $this->_userid='';  //id de l'utilisateur il sera initaliser après l'auth
        $this->_password_type='sha512';  //type d'encodage du password user dans la bdd
    }
    public static function ip(){
        $ip = $_SERVER["REMOTE_ADDR"];
        // empechement du hijaking de session
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip=$ip.'_'.$_SERVER['HTTP_X_FORWARDED_FOR']; }
        if (isset($_SERVER['HTTP_CLIENT_IP'])) { $ip=$ip.'_'.$_SERVER['HTTP_CLIENT_IP']; }
        return $ip;
    }
    public function auth($user,$password,$social=0,$api=0){
        if($password==''){
            return False;
        }
        user::session();
        $password=hash($this->_password_type, $password);
        $this->_bdd->cache('SELECT '.$this->_coluserid.' as nb, permit_level as acl FROM '.$this->_tabuser.' where '.$this->_colusername.'=? and '.$this->_colpassword.'=?',array($user,$password));
        $var=$this->_bdd->exec();
	$now_time = date("Y-m-d");
	$this->_bdd->cache('UPDATE user SET last_connect=? where mail=?',array($now_time,$user));
	$this->_bdd->exec();
        if((isset($var[0][0]['nb']) and $var[0][0]['nb']!='' and $var[0][0]['nb']!='NULL') and $social==0 and $var[0][0]['acl']>0) {
          //  setcookie ("username", $_POST['username'], time() + 432000);
            $_SESSION['id']=$var[0][0]['nb'];
            $_SESSION['acl']=$var[0][0]['acl'];
            $_SESSION['userid']=$var[0][0]['nb'];
            $_SESSION['uid'] = sha1(uniqid('',true).'_'.mt_rand()); 
            $_SESSION['ip']=$this->ip();   // stockage de l'ip deu visiteur
            $_SESSION['username']=$user;
            $_SESSION['account_type']='local';
            $_SESSION['expires_on']=time()+INACTIVITY_TIMEOUT;  // Set session expiration.
            if($api==0){
                return True;
            } else {
                return array(True,$var[0][0]['nb']);
            }
            
        }
        else{
		return False;
        }
    }
    public function pass_lost($email,$password,$valid_password,$api=0){
	$bdd=new bdd();
	// Récuperation de l'ID de l'utilisateur
	$bdd->cache("SELECT id FROM user WHERE mail=?",array($email));
	$ret = $bdd->exec();
	$id = $ret['0']['0']['id'];
	// Modification du mot de passe en base avec la fonction changePassword
	$return = $this->changePassword($password,$valid_password,"False",$id);
	$none= "";
	// Update la clé à rien
	$bdd->cache("UPDATE user SET clef=? WHERE mail=?",array($none,$email));
	$bdd->exec();
	if ($api == 1) {
	   // APP Retour de changement de mot de passe OK
	} else {
	   // WEB Retour vers la page de changement OK
	   header('Location:../index.php?page=lost_pass&log=ok');
	}
    }
    public function mail_pass_lost($mail,$api=0){
	// Vérification si le forma de mail est valide
	if (filter_var($mail, FILTER_VALIDATE_EMAIL)){
	   $bdd=new bdd();
	   $bdd->cache("SELECT id FROM user WHERE mail=?",array($mail));
	   $user_exi = $bdd->exec();
	   // vérification que l'adresse ressorte bien un id en base
	   if ($user_exi[0][0]['id'] == "") {
	     if ($api == 1){
		// APP L'adresse est fausse, definition du retour
	     } else {
		// WEB Renvoi vers la page de message envoyé
		header('Location:../index.php?page=lost_pass&log=passnew');
	     }
	   } else {
		$this->mail_change_pass($mail);
		if ($api == 1){
		   return true;
		} else {
		   header('Location:../index.php?page=lost_pass&log=passnew');
		}
	    }
	} else {
	   if ($api == 1){
		// APP Retour en cas mail mal formé
	   } else {
		// WEB retour mail mal formé
		header('Location:../index.php?page=lost_pass&log=badmail');
	   }
	}
    }
  // Fonction pour la modification de mot de passe en étant authentifié
    public function pass_modif_auth($mail,$pass,$pass_valid,$pass_old,$api=0){
	$hash_pass = hash('sha512', $pass);
	$bdd=new bdd();
	// Vérification que le mot de passe correspond bien au compte
	$bdd->cache("SELECT mail FROM user WHERE password=?",array($hash_pass));
	$mails_users = $bdd->exec();
	// Vérfication que le mail soit OK
	if (!isset ($mails_users['0']['0']['mail'])){
	   if ($api == 1){
		// APP Retour de l'adresse inconnue
	   } else {
		// WEB Retour de l'adresse inconnue
		header('Location:../index.php?page=profil&mdp=ko');
	   }
	} else {
	// En cas de plusieurs users avec le même mot de passe :
	    foreach ($mail_user['0'] as $value){
		// On verifie qu'il correspond
		if ($mail_user['0'][$i]['mail'] == $mail){
		   $bdd=new bdd();
		   $bdd->cache("SELECT id FROM user WHERE mail=?",array($mail));
		   $ret = $bdd->exec();
		   $id = $ret[0][0]['id'];
		   $this->changePassword($pass,$pass_val,"False",$id);
		   header('Location:../index.php?page=profil&mdp=ok');
		   break;
		} else {
		   header('Location:../index.php?page=profil&mdp=ko');
		}
		$i+=1;
	    }
	}
    }
    public function sendmail($to,$subject,$messagetxt,$messagehtml){
	// Déclaration de l'adresse de destination.
	$mail = $to; 
	// On filtre les serveurs qui rencontrent des bogues.
	if (!preg_match("#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#", $mail))
	   {
		$passage_ligne = "\r\n";
	   }
	else
	   {
		$passage_ligne = "\n";
	   }
	//=====Déclaration des messages au format texte et au format HTML.
	$message_txt = $messagetxt;
	$message_html = '<html><head></head><body>'.$messagehtml.'</body></html>';
	//=====Création de la boundary.
	$boundary = "-----=".md5(rand());
	//=====Définition du sujet.
	$sujet = $subject;
	//=====Création du header de l'e-mail.
	$header = "From: \"POPOTE - L\'appli qui mijotte !\" <noreply@popote.xyz>".$passage_ligne;
	$header.= "Reply-to: \"POPOTE - L\'appli qui mijotte !\" <noreply@popote.xyz>".$passage_ligne;
	$header.= "MIME-Version: 1.0".$passage_ligne;
	$header.= "Content-Type: multipart/alternative;".$passage_ligne." boundary=\"$boundary\"".$passage_ligne;
	//=====Création du message.
	$message = $passage_ligne."--".$boundary.$passage_ligne;
	//=====Ajout du message au format texte.
	$message.= "Content-Type: text/plain; charset=\"utf-8\"".$passage_ligne;
	$message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
	$message.= $passage_ligne.$message_txt.$passage_ligne;
	$message.= $passage_ligne."--".$boundary.$passage_ligne;
	//=====Ajout du message au format HTML
	$message.= "Content-Type: text/html; charset=\"utf-8\"".$passage_ligne;
	$message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
	$message.= $passage_ligne.$message_html.$passage_ligne;
	$message.= $passage_ligne."--".$boundary."--".$passage_ligne;
	$message.= $passage_ligne."--".$boundary."--".$passage_ligne;
	//=====Envoi de l'e-mail.
	mail($mail,$sujet,$message,$header);
    }
// mail envoyé lors de l'inscription à popote
    public function mail_insc($mail){
	// Génération aléatoire d'une clé de validation
        $cle = md5(microtime(TRUE)*100000);
        // Integration de la clé dans la table user pour gérer le permit_level
        $bdd=new bdd();
        $bdd->cache("UPDATE user SET clef=? WHERE mail=?",array($cle,$mail));
        $bdd->exec();
        $subject = 'Activation sur Popote.xyz';
        $passage_ligne = "<br/>";
        $messagetxt ='Bonjour et bienvenue sur Popote,'.$passage_ligne;
        $messagetxt .=$passage_ligne;
        $messagetxt .='Pour activer votre compte, veuillez cliquer sur le lien ci'.$passage_ligne;
        $messagetxt .='dessous ou copier/coller le lien dans votre navigateur.'.$passage_ligne;
        $messagetxt .=$passage_ligne;
        $messagetxt .='https://popote.xyz/index.php?page=activation&log='.urlencode($mail).'&cle='.urlencode($cle).$passage_ligne;
        $messagetxt .=$passage_ligne;
        $messagetxt .='--------------------------------------------------------'.$passage_ligne;
        $messagetxt .=$passage_ligne;
        $messagetxt .='Ceci est un mail automatique, Merci de ne pas y répondre.'.$passage_ligne;
        $messagehtml = 'Bonjour et bienvenue sur Popote,'.$passage_ligne;
        $messagehtml .= $passage_ligne;
        $messagehtml .= 'Pour activer votre compte, veuillez cliquer sur le lien ci'.$passage_ligne;
        $messagehtml .= 'dessous ou copier/coller le lien dans votre navigateur.'.$passage_ligne;
        $messagehtml .= $passage_ligne;
        $messagehtml .= 'https://popote.xyz/index.php?page=activation&log='.urlencode($mail).'&cle='.urlencode($cle).$passage_ligne;
        $messagehtml .= $passage_ligne;
        $messagehtml .= '--------------------------------------------------------'.$passage_ligne;
        $messagehtml .= 'Ceci est un mail automatique, Merci de ne pas y répondre.'.$passage_ligne;
        $this->sendmail($mail,$subject,$messagetxt,$messagehtml);
    }
  // mail envoyé en cas de perte de mot da passe
    public function mail_change_pass($mail){
	// Génération aléatoire d'une clé de validation
                $cle = md5(microtime(TRUE)*100000);
	// Integration de la clé dans la table user pour gérer le changement de mot de passe
                $bdd=new bdd();
                $bdd->cache("UPDATE user SET clef=? WHERE mail=?",array($cle,$mail));
                $bdd->exec();
                $subject = 'Recuperation de votre mot de passe pour Popote.xyz';
                $passage_ligne = "<br/>";
                $messagetxt ='Bonjour, une demande de modification du mot de passe, suite à sa perte, à été demandé.'.$passage_ligne;
                $messagetxt .=$passage_ligne;
                $messagetxt .='Pour changer votre mot de passe veuillez cliquer sur le lien ci'.$passage_ligne;
                $messagetxt .='dessous ou copier/coller le lien dans votre navigateur.'.$passage_ligne;
                $messagetxt .=$passage_ligne;
                $messagetxt .='https://popote.xyz/index.php?page=lost_pass&log='.urlencode($mail).'&cle='.urlencode($cle).$passage_ligne;
                $messagetxt .=$passage_ligne;
                $messagetxt .='Si vous n\'êtes pas à l\'origine de cette demande, merci de ne pas en tenir compte.'.$passage_ligne;
                $messagetxt .=$passage_ligne;
                $messagetxt .='--------------------------------------------------------'.$passage_ligne;
                $messagetxt .=$passage_ligne;
                $messagetxt .='Ceci est un mail automatique, Merci de ne pas y répondre.'.$passage_ligne;
                $messagehtml ='Bonjour, une demande de modification du mot de passe, suite à sa perte, à été demandé.'.$passage_ligne;
                $messagehtml .=$passage_ligne;
                $messagehtml .='Pour changer votre mot de passe veuillez cliquer sur le lien ci'.$passage_ligne;
                $messagehtml .='dessous ou copier/coller le lien dans votre navigateur.'.$passage_ligne;
                $messagehtml .=$passage_ligne;
                $messagehtml .='https://popote.xyz/index.php?page=lost_pass&log='.urlencode($mail).'&cle='.urlencode($cle).$passage_ligne;
                $messagehtml .=$passage_ligne;
                $messagehtml .='Si vous n\'êtes pas à l\'origine de cette demande, merci de ne pas en tenir compte.'.$passage_ligne;
                $messagehtml .=$passage_ligne;
                $messagehtml .='--------------------------------------------------------'.$passage_ligne;
                $messagehtml .=$passage_ligne;
                $messagehtml .='Ceci est un mail automatique, Merci de ne pas y répondre.'.$passage_ligne;
                $this->sendmail($mail,$subject,$messagetxt,$messagehtml);
    }
    public function inscription($username,$password,$birth,$api=0){
        $password=hash($this->_password_type, $password);
    // Vérification de l'existance du compte
        $this->_bdd->cache('select * from '.$this->_tabuser.' where '.$this->_colusername.'=?',array($username));
        $resultat=$this->_bdd->exec();
        if(count($resultat[0])==0){
	// Insertion en base du compte de l'utilisateur
            $this->_bdd->cache('INSERT INTO '.$this->_tabuser.' set '.$this->_colpassword.'=?, '.$this->_colusername.'=?, '.$this->_colbirth.'=?',array($password,$username,$birth));
            $this->_bdd->exec();
            $this->mail_insc($username);
            if($api==1){
	// Retour appli : Le compte est créé mais doit être activé
                return true;
            } else {
	// Retour web : Le compte est créé mais doit être activé
                return true;
            }
	
        } else  {
            if($api==1){
	// Retour appli : le nom de compte choisi existe déjà
                return false;
            } else {
	// Retour web : le nom de compte choisi existe déjà
                return false;
            }
        }
    }
    public function inscriptionGoogle($email, $firstname,$lastname,$picture,$gender,$local){
        $this->_bdd->cache('select * from google where mail=?',array($email));
        $resultat=$this->_bdd->exec();
        if(count($resultat[0])==0){
            $this->_bdd->cache('INSERT INTO google set mail=?, prenom=?, nom=?, photo=?, gender=?,local=?',array($email, $firstname,$lastname,$picture,$gender,$local));
            $this->_bdd->exec();
        } else  {
            $this->_bdd->cache('UPDATE google set  prenom=?, nom=?, photo=?, gender=?,local=? where mail=?',array( $firstname,$lastname,$picture,$gender,$local,$email));
            $this->_bdd->exec();
        	}
        user::session();
        $_SESSION['id']=$resultat[0][0]['id'];
        $_SESSION['userid']=$resultat[0][0]['id'];
        $_SESSION['uid'] = sha1(uniqid('',true).'_'.mt_rand()); 
        $_SESSION['ip']=$this->ip();   // stockage de l'ip deu visiteur
        $_SESSION['username']=$email;
        $_SESSION['account_type']='google';
        $_SESSION['expires_on']=time()+INACTIVITY_TIMEOUT;  // Set session expiration.
        header('Location: ../index.php?page=choose');
        exit();
    }
    public function changePassword($password,$password2,$silent=False, $api='-1'){
        if(user::check_login() or $api!='-1'){
            if($password==$password2){
                $password=hash($this->_password_type, $password);
                if($api!='-1'){
                    $id=$api;
                } else {
                    $id=$_SESSION['id'];
                }
                $this->_bdd->cache('UPDATE '.$this->_tabuser.' SET '.$this->_colpassword.'=? WHERE '.$this->_coluserid.'=?',array($password,$id) );
                $this->_bdd->exec();
                if(!$silent){
                    msg(True,'Les mot de passe a bien été changé');
                }
            } else{
                if(!$silent){
                    msg(False, 'Les mots de passes sont differents');
                }
            }
        } else {
            if(!$silent){
                msg(False, 'vous ne pouvez pas faire ça vous n\'êtes pas authentifié');
            }
        }
    }
    public function getinfo(){
        $this->_bdd->cache('select * from '.$this->_tabuser.' where '.$this->_coluserid.' = '.$this->_userid,'');
        $var=$this->_bdd->exec();
        return $var;
    }
    public static function check_login($referer=''){
        user::session();
        // si la session n'existe pas ou qu l'ip a changer -> logout
        if (!isset ($_SESSION['uid']) || !$_SESSION['uid'] || $_SESSION['ip']!=user::ip() || time()>=$_SESSION['expires_on'])
        {
            user::logout();
            return False;
        }
        if($referer!='' and $referer!=$_SERVER['HTTP_REFERER']){
            user::logout();
        }
        $_SESSION['expires_on']=time()+INACTIVITY_TIMEOUT;  // mise a jour de la dte d'expiration
        return True;
    }
    public static function check_admin($referer=''){
        user::session();
        user::check_login($referer);
	// test avec les acl si l'utilisateur est admin
        if(isset($_SESSION['acl']) and $_SESSION['acl']==10){
            $resultat = true;
        } else {
            $resultat = false;
        }
        return $resultat;
    }
    public static function logout()
    // forcer la deconnexion
    {
        user::session();
        if(isset($_SESSION)){
             session_destroy();
         }
        //header('Location: login.php');
        //exit();
    }
    public static function session(){
        if(!isset($_SESSION)){
           session_start(); 
        } 
    }
}
