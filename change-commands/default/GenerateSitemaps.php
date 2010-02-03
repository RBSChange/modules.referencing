<?php
class commands_GenerateSitemaps extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "generate site map for all the websites";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Generate sitemaps ==");

		$this->loadFramework();
		$websites = website_WebsiteService::getInstance()->createQuery()->find();
		foreach ($websites as $website)
		{
			$this->message("Generate site map for ".$website->getDomain());
			referencing_ReferencingService::getInstance()->saveSitemapContents($website);	
		}

		$this->okMessage("Site map files successfully generated");
	}
}