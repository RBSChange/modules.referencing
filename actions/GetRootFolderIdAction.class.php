<?php
class referencing_GetRootFolderIdAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$id = ModuleService::getInstance()->getRootFolderId('referencing');
		return $this->sendJSON(array('id' => $id));
	}
}