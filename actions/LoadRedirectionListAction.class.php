<?php
class referencing_LoadRedirectionListAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$websiteId = $request->getParameter('websiteId');
		$redirectionArray = referencing_RedirectionService::getInstance()->createQuery()
			->add(Restrictions::eq('website.id', $websiteId))
			->addOrder(Order::asc('oldUrl'))
			->find();
			
		$contents = array();
		foreach ($redirectionArray as $r)
		{
			$contents[] = '<redirection id="' . $r->getId() . '" oldUrl="' . htmlspecialchars($r->getOldUrl()) . '" newUrl="' . htmlspecialchars($r->getNewUrl()) . '" />';
		}
		$request->setAttribute('contents', join("\n", $contents));
		
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