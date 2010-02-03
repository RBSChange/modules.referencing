<?php
class referencing_GetRootFolderIdAction extends referencing_Action
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$id = ModuleService::getInstance()->getRootFolderId('referencing');
		$request->setAttribute('document', DocumentHelper::getDocumentInstance($id));
		return self::getSuccessView();
	}
}