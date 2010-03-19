<?php
class referencing_GetSitemapIndexAction extends referencing_Action
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$domain = $_SERVER['HTTP_HOST'];
		$wsms = website_WebsiteModuleService::getInstance();
		$websiteInfo  = $wsms->getWebsiteInfos($domain);
		
		$website = DocumentHelper::getDocumentInstance($websiteInfo['id'], 'modules_website/website');
		$lang = $websiteInfo['localizebypath'] ? 'all' : f_util_ArrayUtils::firstElement($websiteInfo['langs']);
		
		$index = $request->getParameter('index', 0);
		$contents = referencing_ReferencingService::getInstance()->getSitemapIndexContents($website, $lang, $index);
		if ($contents !== null)
		{
			// Content is gzipped (URL rewriting rule in .htaccess points to sitemap_index.xml.gz).
			header('Content-type: application/octet-stream');
			header('Content-length: '.strlen($contents));
			die($contents);
		}
		else
		{
			$HTTP_Header = new HTTP_Header();
			$HTTP_Header->sendStatusCode(404);
			die();
		}
	}
	
	/**
	 * @return Boolean
	 */
	public function isSecure()
	{
		return false;
	}
}