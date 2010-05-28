<?php
class commands_GenerateSitemap extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<domain>";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "generate site map for a given website domain";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		$this->loadFramework();
		$domains = website_WebsiteService::getInstance()->createQuery()
			->setProjection(Projections::property("domain"))
			->findColumn("domain");
		return array_diff($domains, $params);
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$domain = $params[0];

		$this->message("== Generate sitemap for $domain ==");

		$this->loadFramework();
		
		$wsms = website_WebsiteModuleService::getInstance();
		$websiteInfo  = $wsms->getWebsiteInfos($domain);
		
		if ($websiteInfo === null)
		{
			return $this->quitError("No website with domain $domain");
		}
		
		$website = DocumentHelper::getDocumentInstance($websiteInfo['id'], 'modules_website/website');
		$lang = $websiteInfo['localizebypath'] ? 'all' : f_util_ArrayUtils::firstElement($websiteInfo['langs']);
		
		$rs = referencing_ReferencingService::getInstance();
		$rs->createStorageDirectory();
		$rs->saveSitemapContents($website, $lang);

		$this->okMessage("Site map successfully generated");
	}
}