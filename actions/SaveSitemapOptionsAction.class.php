<?php
class referencing_SaveSitemapOptionsAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$website = DocumentHelper::getDocumentInstance($request->getParameter('websiteId'));
		$urlArray = $request->getParameter('urls', array());
		$modelName = $request->getParameter('modelName');
		$rs = referencing_ReferencingService::getInstance();
		
		$priority = $request->getParameter('priority');
		$changeFreq = $request->getParameter('changefreq');
		$forLang = $request->getParameter('forLang');
		if (($count = count($urlArray)) > 0)
		{
			for ($i=0 ; $i<$count ; $i++)
			{
				$url = $urlArray[$i];
				$rs->setSitemapOptionForUrl($website, $forLang, $url, 'priority', $priority, false);
				$rs->setSitemapOptionForUrl($website, $forLang, $url, 'changefreq', $changeFreq, $i == ($count - 1)); // true: save on last option 
			}
		}
		else
		{
			$rs->setSitemapOption($website, $forLang, $modelName, 'priority', $priority, false);
			$rs->setSitemapOption($website, $forLang, $modelName, 'changefreq', $changeFreq, true); // save
		}
		return self::getSuccessView();
	}

	/**
	 * @return Boolean
	 */
	public function isSecure()
	{
		return true;
	}
}