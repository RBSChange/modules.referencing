<?php
class referencing_GenerateRedirectionsFileAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		referencing_RedirectionService::getInstance()->writeRedirectionsFile();
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