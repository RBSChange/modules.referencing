<?php
class referencing_GetSitemapIndexAction extends referencing_Action
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$index = $request->getParameter('index', 0);
		Framework::info(__METHOD__ . ":" . $website->__toString() . ":". $index);
		$contents = referencing_ReferencingService::getInstance()->getSitemapIndexContents($website, $index);
		if ($contents !== null)
		{
			// Content is gzipped (URL rewriting rule in .htaccess points to sitemap_index.xml.gz).
			header('Content-type: application/octet-stream');
			header('Content-length: '.strlen($contents));
			die($contents);
		}
		else
		{
			$HTTP_Header= new HTTP_Header();
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