<?php
class referencing_SaveIdInfoAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$website = DocumentHelper::getDocumentInstance($request->getParameter('websiteId'));
		$rs = referencing_ReferencingService::getInstance();
		$rs->setIdInfo($website, "google", $request->getParameter("googleId"), $request->getParameter("googleContent"), false);
		$rs->setIdInfo($website, "yahoo", $request->getParameter("yahooId"), $request->getParameter("yahooContent"), false);
		$rs->setIdInfo($website, "msn", '', $request->getParameter("msnContent"), true); // save
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
