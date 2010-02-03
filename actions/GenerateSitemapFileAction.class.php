<?php
class referencing_GenerateSitemapFileAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		set_time_limit(0);
		referencing_ReferencingService::getInstance()->saveSitemapContents(
			DocumentHelper::getDocumentInstance($request->getParameter('websiteId'))
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