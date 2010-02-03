<?php
class referencing_LoadExcludedUrlListAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$contents = referencing_ReferencingService::getInstance()->getSitemapExcludedUrlList(
			DocumentHelper::getDocumentInstance($request->getParameter('websiteId'))
			);
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