<?php
class imap{
public function __construct()
  {
    $this->_host   = IMAP_HOST;
    $this->_port   = IMAP_PORT;
    $this->_option = IMAP_OPTION;
    $this->_mbox   = FALSE;
  }
  public function connect($user,$password,$folder=''){
      $this->_user     = $user;
      $this->_password = $password;
      $this->_mbox     = imap_open('{'.$this->_host.':'.$this->_port.$this->_option.'}', $user, $password);
      if (FALSE === $this->_mbox) {
          $return['error'] = true;
		      $return['msg']   = 'La connexion a échoué. Vérifiez vos paramètres!';
		  } else {
          $return['error'] = false;
      }
      return $return;
  }
  public function check_connection(){
    if(!$this->_mbox){
      $return['error'] = true;
      $return['msg']   = 'Vous devez vous connecter en premier';
    } else {
      $return['error'] = false;
    }
    return $return;
  }
  public function folder_list(){
    $state = $this->check_connection();
    if(!$state['error']) {
      $list = imap_list($this->_mbox, '{'.$this->_host.'}', "*");
      if (is_array($list)) {
        foreach ($list as $val) {
          $return['data'][] = utf8_encode(imap_utf7_decode($val)) ;
        }
        $return['error'] = false;
      } else {
        $return['error'] = true;
        $return['msg']   = "imap_list a échoué : " . imap_last_error() . "\n";
      }
    } else {
      $return = $state;
    }
    return $return;
  }
  public function list_folder_content($folder,$per_page=25,$page=1,$sort = null){
    $state  = imap_reopen($this->_mbox,'{'.$this->_host.':'.$this->_port.$this->_option.'}'.$folder);
    $sorted = imap_sort($this->_mbox, SORTARRIVAL, 1);
    $msgs = array_chunk($sorted, $per_page)[$page-1];
    if(!$state['error']) {
      $info = imap_check($this->_mbox);
      if (FALSE !== $info) {
        $sorting = array(
          'direction' => array(   'asc' => 0,
                                  'desc' => 1),
          'by'        => array(   'date' => SORTDATE,
                                  'arrival' => SORTARRIVAL,
                                  'from' => SORTFROM,
                                  'subject' => SORTSUBJECT,
                                  'size' => SORTSIZE));
          $by = (true === is_int($by = $sorting['by'][$sort[0]])) ? $by : $sorting['by']['date'];
          $direction = (true === is_int($direction = $sorting['direction'][$sort[1]])) ? $direction : $sorting['direction']['desc'];
          $nbMessages = min(50, $info->Nmsgs);

          $return['data'] = array_reverse(imap_fetch_overview($this->_mbox, implode($msgs, ','), 0));
          $return['error'] = false;
      } else {
        $return['error'] = true;
        $return['msg']   = 'Impossible de lire la boite mail';
      }
    } else {
      $return = $state;
    }
    return $return;
  }
  public function show_msg($uid){

    $return['error'] = false;
    $return['data'] = imap_qprint(imap_body($this->_mbox, $uid));
    return $return;
  }
  public function __destruct(){
    imap_close($this->_mbox);
  }
}
?>
