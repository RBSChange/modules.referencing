<?php
class referencing_GetSitemapAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$index = $request->getParameter('index', 0);
		$contents = referencing_ReferencingService::getInstance()->getSitemapContents($website, $index);
		if ($contents !== null)
		{
			// Content is gzipped (URL rewriting rule in .htaccess points to sitemap.xml.gz).
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