<?php
class referencing_LoadUrlListAction extends f_action_BaseAction
{
	const MAX_URL = 1000;
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$website = DocumentHelper::getDocumentInstance($request->getParameter('websiteId'));
		$modelName = $request->getParameter('modelName');
		// Get URL info, including excluded URL (true).
		$rs = referencing_ReferencingService::getInstance();
		
		if (Framework::hasConfiguration('modules/referencing/maxurl'))
		{
			$maxUrl = Framework::getConfiguration('modules/referencing/maxurl');
		}
		else
		{
			$maxUrl = self::MAX_URL;
		}
		
		$urlInfoArray = $rs->getUrlInfoArray($website, $request->getParameter('forLang'), $modelName, true, $maxUrl);
		
		// Sort URL alphabetically.
		$url = array();
		foreach ($urlInfoArray as $key => $urlInfo)
		{
		    $url[$key] = $urlInfo->loc;
		    $urlInfoArray[$key]->loc = htmlspecialchars($urlInfoArray[$key]->loc);
		}
		array_multisort($url, SORT_ASC, $urlInfoArray);
		
		// Generate XML.
		$template = TemplateLoader::getInstance()
			->setPackageName('modules_referencing')
			->setMimeContentType('xml')
			->load('sitemap-simple');
		$template->setAttribute('urlInfoArray', $urlInfoArray);
		
		$priority = $rs->getSitemapOption($website, $modelName, 'priority');
		$changefreq = $rs->getSitemapOption($website, $modelName, 'changefreq');
		$template->setAttribute(
			'options',
			array(
				array(
					'name' => 'priority',
					'value' => is_null($priority) ? $rs->getSitemapDefaultPriority() : $priority
				),
				array(
					'name' => 'changefreq',
					'value' => is_null($changefreq) ? $rs->getSitemapDefaultChangefreq() : $changefreq 
				)
			)
		);
		
		$request->setAttribute('contents', $template->execute());
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
