<?php
class socketServer{
	public function __construct($host,$port){
		$this->host = $host;
		$this->port = $port;
	}
	public function initSocket(){
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($socket, 0, $this->port);
		socket_listen($socket);
		$clients = array($socket);
		do {
		    $this->socket = socket_accept($socket); //Acceptation du socket=
		    $header = socket_read($this->socket, 1024); //lecture du socket en entrée
		    $this->perform_handshaking($header, $this->socket); //Envoie des entête
		    socket_getpeername($this->socket, $ip); //get ip address of connected socket
		    echo "Nouvelle connection avec IP : $ip \n";
				$msg = serialize(json_decode($this->read()))
				   socket_write($this->socket,$msg,strlen($msg));
		    /* Reception d'un socket */
		    while(socket_recv($this->socket, $buf, 1024, 0) >= 1)
		    {
		        $msg = $this->unmask($buf);
		        $received_msg = "Message received from $ip : $msg \n";
						$user = json_decode($msg)->user;
						$inUser = json_decode($this->read());

						if(count($inUserr) == 0) $inUser[0]['user'] = $user;
						else $inUser[count($inUser)]['user'] = $user;
						$inUser = json_encode($inUser);
						$this->writeUser($inUser);
		        $msg = $this->mask($inUser); //reponse
		        socket_write($this->socket,$msg,strlen($msg));
		    }
		    $buf = socket_read($this->socket, 1024, PHP_NORMAL_READ);
				//echo $buf;
		    if ($buf === false) {
		        echo "Connexion coupée avec IP : $ip \n";
		    }
		} while(true);
	}

	public function closeSocket(){
		socket_close($this->socket);
	}
	private function unmask($text) {
	    $length = ord($text[1]) & 127;
	    if($length == 126) {
	        $masks = substr($text, 4, 4);
	        $data = substr($text, 8);
	    }
	    elseif($length == 127) {
	        $masks = substr($text, 10, 4);
	        $data = substr($text, 14);
	    }
	    else {
	        $masks = substr($text, 2, 4);
	        $data = substr($text, 6);
	    }
	    $text = "";
	    for ($i = 0; $i < strlen($data); ++$i) {
	        $text .= $data[$i] ^ $masks[$i%4];
	    }
	    return $text;
	}
public function send($data){
	$msg = $this->mask($data); //reponse
	socket_write($this->socket,$msg,strlen($msg));['']
}
	private function mask($text)
	{
	    $b1 = 0x80 | (0x1 & 0x0f);
	    $length = strlen($text);
			echo $length;
	    if($length <= 125)
	        $header = pack('CC', $b1, $length);
	        elseif($length > 125 && $length < 65536)
	        $header = pack('CCn', $b1, 126, $length);
	        elseif($length >= 65536)
	        $header = pack('CCNN', $b1, 127, $length);
	        return $header.$text;
	}

	private function perform_handshaking($receved_header,$client_conn)
	{
	    $headers = array();
	    $lines = preg_split("/\r\n/", $receved_header);
	    foreach($lines as $line)
	    {
	        $line = chop($line);
	        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
	        {
	            $headers[$matches[1]] = $matches[2];
	        }
	    }

	    $secKey = $headers['Sec-WebSocket-Key'];
	    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	    //hand shaking header
	    $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	        "Upgrade: websocket\r\n" .
	        "Connection: Upgrade\r\n" .
	        "WebSocket-Origin: $this->host\r\n" .
	        "WebSocket-Location: ws://$this->host:$this->port/demo/shout.php\r\n".
	        "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	    @socket_write($client_conn,$upgrade,strlen($upgrade));
	}
	function writeUser($data){
		$myFile2 = "user.txt";

		$myFileLink2 = fopen($myFile2, 'w+') or die("Can't open file.");

		fwrite($myFileLink2, $data);

		fclose($myFileLink2);
	}

public function read(){
	$myFile = "user.txt";
	if(filesize($myFile) > 0){
		$myFileLink = fopen($myFile, 'w');
		$myFileContents = fread($myFileLink, filesize($myFile));
		fclose($myFileLink);
		return $myFileContents;
	} else {
		return '';
	}

}





}
$socket = new socketServer('127.0.0.1','8090');
$socket->initSocket();
