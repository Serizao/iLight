<?php
class curl{
  public function __construct(){
    $this->_curl = curl_init();
    $this->_arg  = array();
    $this->_arg[CURLOPT_RETURNTRANSFER] = true;
    $this->setStrictSSL(true);
    $this->_arg[CURLOPT_CAINFO]=  dirname(__FILE__) . '\cacert.pem';
    $this->_arg[CURLOPT_CAPATH]=  dirname(__FILE__) . '\cacert.pem';
    $this->_arg[CURLOPT_USERAGENT]="Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36";
    $this->_return ='';

  }
  public function get($url){
    $this->_arg[CURLOPT_URL]=$url;
    return $this->execCurl();
  }
  public function post($url,$data){
    $this->_arg[CURLOPT_POST]=true;
    $this->_arg[CURLOPT_URL]=$url;
    return $this->execCurl();
  }
  public function error(){
    if($this->_return === false)
    {
        return curl_error($this->_curl);
    }
  }
  public function setUserAgent($data){
    $this->_arg[CURLOPT_USERAGENT]=$data;
  }
  public function setHeader(array $data){
      $this->_arg[CURLOPT_HTTPHEADER]=$data;
  }
  public function setStrictSSL($data){
    $this->_arg[CURLOPT_SSL_VERIFYPEER]=$data;
  }
  public function addArg($data){
  }
  public function execCurl(){
    curl_setopt_array($this->_curl, $this->_arg);
    $this->_return = curl_exec($this->_curl);
    return $this->_return;
  }
  public function close(){
    curl_close($this->_curl);
  }
  public function __destruct() {
    curl_close($this->_curl);
  }
}
?>
