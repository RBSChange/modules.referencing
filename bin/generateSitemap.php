<?php

//generateSitemap.php WEBEDIT_HOME LANG WEBSITEID TEMPFILEPATH DOCID1 DOCID2 ...
define("WEBEDIT_HOME", $_SERVER['argv'][1]);
require_once WEBEDIT_HOME . "/framework/Framework.php";
$controller = Controller::newInstance("controller_ChangeController");

$lang = $_SERVER['argv'][2];
RequestContext::getInstance()->setLang($lang);

$website = DocumentHelper::getDocumentInstance(intval($_SERVER['argv'][3]));
website_WebsiteModuleService::getInstance()->setCurrentWebsite($website);
$_SERVER['HTTP_HOST'] = $website->getDomain();

$tmpFilePath = $_SERVER['argv'][4];

Framework::info("update $tmpFilePath ...");
Framework::info("WEBEDIT_HOME : " . WEBEDIT_HOME);
Framework::info("lang : " .$lang);
Framework::info("websiteId : " . $website->getId());
Framework::info("websiteDomain : " . $website->getDomain());
Framework::info("tmpFilePath : " . $tmpFilePath);

$docIds = array();
for ($i = 5; $i < (int)$_SERVER['argc']; $i++)
{
	$docIds[] = intval($_SERVER['argv'][$i]);
}

if (count($docIds) > 0)
{
	Framework::info("start DocId : " . $docIds[0]);
	$filerc = fopen($tmpFilePath, "a");
	referencing_ReferencingService::getInstance()->updateTempSiteMap($filerc, $website, $docIds);
	fclose($filerc);
}
Framework::info("Chunk end");
echo "0";