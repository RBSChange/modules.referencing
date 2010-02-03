<?php
class referencing_SaveRobotsTxtAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		referencing_ReferencingService::getInstance()->saveRobotsTxtContents(
	    	DocumentHelper::getDocumentInstance($request->getParameter('websiteId')),
	    	$request->getParameter('contents')
	    	);
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