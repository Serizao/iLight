<?php
class lucca {
  public function __construct(){
    $this->_apiKey  = "socity";
    $this->_baseUrl = "https://socity.ilucca.net/api/v3/";
    $this->_curl    = new curl;
    $this->_curl->setHeader(array('Authorization: Lucca application='.$this->_apiKey));
  }
  public function getUser($id=''){
    if($id != '') $id = '/'.$id;
    $data = $this->_curl->get($this->_baseUrl.'users'.$id);
    return $this->sortie($data);
  }
  public function sortie($data){
    if($data === false){
      return $this->_curl->error();
    } else {
      return $data;
    }
  }
}
