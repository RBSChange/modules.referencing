<?php

//generateSitemap.php WEBEDIT_HOME LANG WEBSITEID TEMPFILEPATH DOCID1 DOCID2 ...
$controller = Controller::newInstance("controller_ChangeController");

$lang = $_POST['argv'][0];
RequestContext::getInstance()->setLang($lang);

$website = DocumentHelper::getDocumentInstance(intval($_POST['argv'][1]));
website_WebsiteModuleService::getInstance()->setCurrentWebsite($website);
$_SERVER['HTTP_HOST'] = $website->getDomain();

$tmpFilePath = $_POST['argv'][2];

Framework::info("update $tmpFilePath ...");
Framework::info("WEBEDIT_HOME : " . WEBEDIT_HOME);
Framework::info("lang : " .$lang);
Framework::info("websiteId : " . $website->getId());
Framework::info("websiteDomain : " . $website->getDomain());
Framework::info("tmpFilePath : " . $tmpFilePath);

$docIds = array();
for ($i = 3; $i < count($_POST['argv']); $i++)
{
	$docIds[] = intval($_POST['argv'][$i]);
}

if (count($docIds) > 0)
{
	Framework::info("start DocId : " . $docIds[0]);
	$filerc = fopen($tmpFilePath, "a");
	referencing_ReferencingService::getInstance()->updateTempSiteMap($filerc, $website, $lang, $docIds);
	fclose($filerc);
}
Framework::info("Chunk end");
echo "0";