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
			if (!$website->getLocalizebypath())
			{
				foreach ($website->getI18nInfo()->getLangs() as $lang)
				{
					try
					{
						$rqc->beginI18nWork($lang);
						$forSitemap[] = array('id' => $website->getId(), 'domain' => $website->getDomain(), 'label' => $website->getLabel(), 'lang' => $lang);
						$forRedirection[] = array('id' => $website->getId(), 'domain' => $website->getDomain(), 'label' => $website->getLabel());
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
				$forRedirection[] = array('id' => $website->getId(), 'domain' => $website->getDomain(), 'label' => $website->getLabel());
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
