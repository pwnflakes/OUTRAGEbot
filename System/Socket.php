<?php
/**
 *	Socket class for OUTRAGEbot
 *
 *	I suppose I have no intention to document this piece of code,
 *	I suppose.
 *
 *	@ignore
 *	@package OUTRAGEbot
 *	@copyright David Weston (c) 2010 -> http://www.typefish.co.uk/licences/
 *	@author David Weston <westie@typefish.co.uk>
 *	@version 1.1.1-RC3 (Git commit: c81a80d0bc6b5ee074fb0f9ea5c0376d00ed5bca)
 */

class Socket
{
	public
		$pMaster = null,
		$aConfig = array(),
		$aStatistics = array(),
		$rSocket = null,
		
		$isWaiting = false,
		$iPingTimer = -1,
		$iHasBeenReply = false,
		
		$iNoReply = 0,
		$isActive = false,
		$isRemove = false,
		
		$sChild = "",
		$iUseQueue = false,
		$aMessageQueue = array(),
		$aRequestConfig = array(),
		$aRequestOutput = array();
	
	
	/* The real constructor. */
	public function __construct($pMaster, $sChild, $aBasic)
	{
		$this->aConfig = $aBasic;
		$this->pMaster = $pMaster;
		$this->sChild = $sChild;
		
		$this->constructBot();
	}
	
	
	/* The real destructor. */
	public function __destruct()
	{
	}
	
	
	/* No idea. */
	public function __toString()
	{
		return $this->sChild;
	}
	
	
	/* Constructing the class */
	public function constructBot()
	{
		/* Resetting statistics */
		$this->aStatistics = array
		(
			"StartTime" => time(),
			"Input" => array
			(
				"Packets" => 0,
				"Bytes" => 0,
			),
			"Output" => array
			(
				"Packets" => 0,
				"Bytes" => 0,
			),
		);
		
		/* Shortcut, eh? */
		$pConfig = $this->pMaster->pConfig;
		
		$this->rSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		
		if(isset($pConfig->Network['bind']))
		{
			socket_bind($this->rSocket, $pConfig->Network['bind']);
		}
		
		socket_connect($this->rSocket, $pConfig->Network['host'], $pConfig->Network['port']);
		
		if(!isset($_SERVER['WINDIR']) && !isset($_SERVER['windir']))
		{
		 	socket_set_nonblock($this->rSocket);
		}
		
		if(isset($this->aConfig['password']))
		{
			$this->Output("PASS {$this->aConfig['password']}");
		}
		
		$this->Output("NICK {$this->aConfig['nickname']}");
		$this->Output("USER {$this->aConfig['username']} x x :{$this->aConfig['realname']}");
		
		$this->Active = true;
		$this->isWaiting = false;
		$this->iPingTimer = Timers::Create(array($this, "Ping"), 60, -1);
	}
	
	
	/* The socket gets shutdown by unsetting the class. */
	public function destructBot($sMessage = false)
	{
		$this->Output('QUIT :'.($sMessage == false ? $this->pMaster->pConfig->Network['quitmsg'] : $sMessage));
		Timers::Delete($this->iPingTimer);
		
		@socket_clear_error();
		
		$this->socketShutdown();
		$this->Active = false;
		$this->isWaiting = false;
	}
	
	
	/* Sending data from socket */
	public function Output($sRaw)
	{
		++$this->aStatistics['Output']['Packets'];
		$this->aStatistics['Output']['Bytes'] += strlen($sRaw.IRC_EOL);
		return @socket_write($this->rSocket, $sRaw.IRC_EOL);
	}
	
	
	/* All important ping check */
	public function Ping()
	{
		if(!$this->iHasBeenReply)
		{
			$this->iNoReply++;
			$this->iHasBeenReply = false;
		}
		
		if($this->iNoReply >= 5)
		{
			$this->destructBot();
			Timers::Create(array($this, "constructBot"), $this->aConfig['timewait'], 0);
			
			$this->isWaiting = true;
			$this->iNoReply = 0;
		}			
		
		$this->Output("PING ".time());
		$this->iHasBeenReply = false;
	}

	
	/* Recieving data from socket */
	public function Input()
	{
		if(!$this->isSocketActive())
		{
			if(!$this->isWaiting)
			{
				$this->destructBot();
				$this->isWaiting = true;
				$this->constructBot();
			}
			
			return;
		}	
		
		if($this->isWaiting)
		{
			return;
		}
		
		$this->scanMessageQueues();
		$this->scanSocket();
		
		return;
	}
	
	
	/* Is socket online? */
	private function isSocketActive()
	{
		if(!$this->Active)
		{
			return false;
		}
		
		if(!is_resource($this->rSocket))
		{
			return false;
		}
		
		if(strlen(socket_last_error($this->rSocket)) > 3)
		{
			return false;
		}
		
		return true;
	}
	
	
	/* Making it easier to check of clones */
	public function isClone()
	{
		return ($this->aConfig['slave'] !== false);
	}
	
	
	/* Changing the bot's nickname! */
	public function setNickname($sNickname)
	{
		$this->aConfig['nickname'] = $sNickname;
		$this->Output("NICK {$sNickname}");
	}
	
	
	/* Shutting down socket - used for restarting, and dying. */
	public function socketShutdown()
	{
		return @socket_shutdown($this->rSocket);
	}
	
	
	/* Dealing with the message queues. */
	private function scanMessageQueues()
	{
		if(count($this->aMessageQueue) && $this->iUseQueue == false)
		{
			foreach($this->aMessageQueue as $iKey => $sChunk)
			{
				$this->pMaster->getInput($this, $sChunk);
				unset($this->aMessageQueue[$iKey]);
			}
		}
	}
	
	
	/* Check the sockets for stupid crap */
	private function scanSocket()
	{
		$sInputString = socket_read($this->rSocket, 4096, PHP_BINARY_READ);
		
		foreach(explode("\n", $sInputString) as $sString)
		{		
			if(strlen($sString) < 3)
			{
				continue;
			}
			
			$sString = trim($sString);
			
			++$this->aStatistics['Input']['Packets'];
			$this->aStatistics['Input']['Bytes'] += strlen($sString);
			$this->pMaster->getInput($this, $sString);
		}
	}
}

?>
