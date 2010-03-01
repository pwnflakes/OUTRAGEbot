<?php
/**
 *	Relay plugin created by Westie.
 *
 *	@ignore
 *	@copyright None
 *	@package OUTRAGEbot
 */


class Relay extends Plugins
{
	public
		$sChannel = "#westie",
		$bStopLoops = true,			// Mark as false if this causes problems.
		$sToken = "";
	
	
	public function onConstruct()
	{
		$this->sToken = Format::Bold.Format::Underline.Format::Blue.Format::Red.Format::Clear;
	}
	
	
	public function onTick()
	{
		if(!$this->getIBCCount("DELAY4150"))
		{
			return;
		}
		
		foreach($this->getIBCMessages("DELAY4150") as $sMessage)
		{
			$this->Message($this->sChannel, $sMessage);
		}
	}
	
	
	public function onMessage($sNickname, $sChannel, $sMessage)
	{
		$sNetwork = $this->getNetworkConfig('name');
		
		if($this->bStopLoops)
		{
			if(substr($sMessage, 0, strlen($this->sToken)) == $this->sToken)
			{
				return;
			}
		}
		
		foreach(Control::botGetNames() as $sGroup)
		{
			if($sGroup != $this->sBotGroup)
			{
				$this->sendIBCMessage($sGroup, $this->sToken."[{$sNetwork}] <{$sNickname}> {$sMessage}", "DELAY4150");
			}
		}
	}
}
