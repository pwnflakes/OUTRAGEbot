<?php
/**
 *	OUTRAGEbot development
 */


class CoreConfiguration
{
	/**
	 *	Loads a configuration file from a specific location.
	 */
	static function ParseLocation($sLocation)
	{
		$sConfigName = substr(basename($sLocation), 0, -4);

		if($sConfigName[0] == "~")
		{
			return false;
		}
		
		$aConfiguration = parse_ini_file($sLocation, true);
		
		if(!is_array($aConfiguration) || count($aConfiguration) <= 1)
		{
			println(" * Sorry, looks like the network {$sConfigName} failed to load!");
			return false;
		}
		
		$pConfig = new stdClass();
		
		$bSlave = false;
		
		foreach($aConfiguration as $sConfigKey => $aConfigObject)
		{
			if($sConfigKey[0] == "~")
			{
				$sConfigKey = substr($sConfigKey, 1);
				$pConfig->$sConfigKey = (object) $aConfigObject;
				
				continue;
			}
			
			$aConfigObject = array_merge(array("nickname" => $sConfigKey), $aConfigObject, array("slave" => $bSlave));
			$pConfig->Bots[$sConfigKey] = (object) $aConfigObject;
			
			$bSlave = true;
		}
		
		self::verifyConfiguration($pConfig);
		
		return Core::addInstance($sConfigName, new CoreMaster($pConfig));
	}
	
	
	/**
	 *	Ensures that the required variables are indeed in memory.
	 */
	static function verifyConfiguration($pConfig)
	{
		$pConfig->Server = new stdClass();
		$pNetwork = $pConfig->Network;
		
		if(empty($pNetwork->delimiter))
		{
			$pNetwork->delimiter = "!";
		}
		
		if(empty($pNetwork->rotation))
		{
			$pNetwork->rotation = SEND_DEF;
		}
		
		if(empty($pNetwork->quitmsg))
		{
			$pNetwork->quitmsg = "OUTRAGEbot is going to bed :(";
		}
		
		if(empty($pNetwork->version))
		{
			$pNetwork->version = "OUTRAGEbot ".BOT_VERSION." (rel. ".BOT_RELDATE."); David Weston; http://outrage.typefish.co.uk";
		}
		
		if(empty($pNetwork->perform))
		{
			$pNetwork->perform = array();
		}
		
		$pNetwork->ownerArray = array();
		$pNetwork->pluginArray = array();
		$pNetwork->channelArray = array();
		
		if(!empty($pNetwork->owners))
		{
			foreach(explode(',', $pNetwork->owners) as $sOwnerAddress)
			{
				$pNetwork->ownerArray[] = trim($sOwnerAddress);
			}
		}
		
		if(!empty($pNetwork->channels))
		{
			foreach(explode(',', $pNetwork->channels) as $sChannelName)
			{
				$pNetwork->channelArray[] = trim($sChannelName);
			}
		}
		
		if(!empty($pNetwork->plugins))
		{
			foreach(explode(',', $pNetwork->plugins) as $sPluginName)
			{
				$pNetwork->pluginArray[] = trim($sPluginName);
			}
		}
		
		return $pConfig;
	}
}