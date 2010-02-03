<?php
class referencing_LoadWebsiteListAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$websites = website_WebsiteService::getInstance()->createQuery()->find();
		$contents = array();
		foreach ($websites as $website)
		{
			$contents[] = '<website id="' . $website->getId().'" domain="' . $website->getDomain().'"><![CDATA[' . $website->getLabel() . ']]></website>';
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