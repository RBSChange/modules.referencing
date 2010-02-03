<?php
class referencing_LoadModelListAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$models = referencing_ReferencingService::getInstance()->getPersistentModels();
		foreach ($models as $model)
		{
			$modelLabel = f_Locale::translate('&modules.'.$model->getModuleName().'.document.'.$model->getDocumentName().'.Document-name;');
			$contents[] = '<model name="'.$model->getName().'"><![CDATA[' . $modelLabel . ']]></model>'; 
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