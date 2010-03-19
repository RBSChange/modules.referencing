<?php
class referencing_SaveExcludedUrlListAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		referencing_ReferencingService::getInstance()->saveSitemapExcludedUrlList(
			DocumentHelper::getDocumentInstance($request->getParameter('websiteId')),
			$request->getParameter('forLang'),
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