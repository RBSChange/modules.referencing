<?php
class referencing_UrlrewritingRuleFinder
{
	public function getConfigFiles()
	{
		$fileContent = array();

		// Get the rules defined in the database.
		$urlRewritingInfos = referencing_UrlrewritinginfoService::getInstance()->createQuery()->find();		
		foreach ($urlRewritingInfos as $urlRewritingInfo)
		{
			$fileContent[$urlRewritingInfo->getPackage()] = $urlRewritingInfo->getContent();
		}
		
		// For the packages with no rule define in the database, look for a file.
		$modules = ModuleService::getInstance()->getModules();
		foreach ($modules as $module)
		{
			if (!isset($fileContent[$module]))
			{
				$filePath = website_urlrewriting_RulesParser::getInstance()->getDefinitionFilePathByPackage($module);
				if ($filePath)
				{
					$fileContent[$module] = f_util_FileUtils::read($filePath);
				}
			}
		}
		$filePath = CHANGE_CONFIG_DIR . DIRECTORY_SEPARATOR . 'urlrewriting.xml';
		if (is_readable($filePath))
		{
			$fileContent['webapp'] = f_util_FileUtils::read($filePath);
		}
		return $fileContent;
	}
}
?>