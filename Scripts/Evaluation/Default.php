<?php
/**
 *	OUTRAGEbot development
 */


class Evaluation extends Script
{
	public function onConstruct()
	{
		println("$ Evaluation plugin loaded");
	}
	
	
	public function onChannelCommand($sChannel, $sNickname, $sCommand, $sArguments)
	{
		if($sCommand == "help")
		{
			$sChannel("Oops, you're a fag.");
		}
	}
}