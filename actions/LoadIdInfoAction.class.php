<?php
class referencing_LoadIdInfoAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$idFilesInfo = referencing_ReferencingService::getInstance()->getIdInfo(
			DocumentHelper::getDocumentInstance($request->getParameter('websiteId'))
			);
		$contents = array();
		foreach ($idFilesInfo as $engine => $info)
		{
			$contents[] = '<engine name="' .  $engine . '" id="'.$info['id'].'"><![CDATA[' . $info['content'] . ']]></engine>';
		}
		$request->setAttribute('contents', '<contents>' . join("\n", $contents) . '</contents>');
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