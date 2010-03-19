<?php
class referencing_LoadWebsiteListAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$websites = website_WebsiteService::getInstance()->getAll();
		$forSitemap = array();
		$forRedirection = array();
		$rqc = RequestContext::getInstance();
		foreach ($websites as $website)
		{
			$forRedirection[] = array('id' => $website->getId(), 'domain' => $website->getDomain(), 'label' => $website->getLabel());
			if (!$website->getLocalizebypath())
			{
				foreach ($rqc->getSupportedLanguages() as $lang)
				{
					try 
					{
						$rqc->beginI18nWork($lang);
						$forSitemap[] = array('id' => $website->getId(), 'domain' => $website->getDomain(), 'label' => $website->getLabel(), 'lang' => $lang);
						$rqc->endI18nWork();
					}
					catch (Exception $e)
					{
						$rqc->endI18nWork();
						throw $e;
					}
				}
			}
			else 
			{
				$forSitemap[] = array('id' => $website->getId(), 'domain' => $website->getDomain(), 'label' => $website->getLabel(), 'lang' => 'all');
			}
		}
		return $this->sendJSON(array('forSitemap' => $forSitemap, 'forRedirection' => $forRedirection));
	}

	/**
	 * @return Boolean
	 */
	public function isSecure()
	{
		return true;
	}
}