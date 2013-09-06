<?php
class RTKInterfaceException extends Exception
{
	public function RTKInterfaceException($msg, $code = 0)
	{
		parent::__construct($msg, $code);
	}
}
class RTKInterface
{
	private $host;
	private $port;
	private $saltedUser;
	private $saltedPassword;
	private $response;

	public function RTKInterface($port, $host, $user, $password, $salt)
	{
		$this->port = $port;
		$this->host = $host;
		$this->saltedUser = hash('sha1', $user.$salt,false);
		$this->saltedPassword = hash('sha1', $password.$salt,false);
	}
	public function executeCommand($cmd, $args='')
	{
		$suffix = ":2:".$this->saltedUser.":".$this->saltedPassword;
		$packet = "";
		$cmd = strtolower($cmd);
		switch($cmd)
		{
			case 'hold':
			case 'unhold':
			case 'enable':
			case 'disable':
			case 'version':
			case 'stop':
			case 'forcestop':
			case 'restart':
			case 'forcerstart':
				$packet = $cmd.$suffix;
				break;
			case 'reschedule':
				$args = trim($args);
				if (empty($args))
					throw new RTKInterfaceException("Illegal command parameter specified.");
				$packet = 'reschedule:'.$args.$suffix;
			default:
				throw new RTKInterfaceException("Illegal command type specified");
		}
		$this->dispatchUDPPacket($packet, $this->host, $this->port);
	}
	private function dispatchUDPPacket($packet,$host,$port)
	{
		$errno;
		$errstr;
		$this->setResponse('timeout');
		$ds = @fsockopen("udp://$host",$port, $errno, $errstr);
		if (!$ds) {
			throw new RTKInterfaceException("ERROR: $errno - $errstr");
		}else
		{
			socket_set_timeout($ds, 30);
			fputs($ds, $packet);
			$response = fgets($ds);
			$this->setResponse($response);
			if ($errno)
				throw new RTKInterfaceException("$errno: $errstr");
			fclose($ds);
		}

	}
	private function setResponse($response)
	{
		$this->response = $response;
	}
	public function getLastResponse()
	{
		return $this->response;
	}

}
