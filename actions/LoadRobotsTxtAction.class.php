<?php
class referencing_LoadRobotsTxtAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$website = DocumentHelper::getDocumentInstance($request->getParameter('websiteId'));
		$contents = referencing_ReferencingService::getInstance()->getRobotsTxtContents($website);
		$request->setAttribute('contents', '<contents><![CDATA[' . $contents . ']]></contents>');
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