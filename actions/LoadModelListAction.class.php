<?php
class referencing_LoadModelListAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$modelNames = referencing_ReferencingService::getInstance()->getPersistentModels();
		foreach ($modelNames as $modelName)
		{
			$modelLabel = f_Locale::translate('&'. str_replace(array('_', '/'), array('.', '.document.'), $modelName) . '.Document-name;');
			$contents[] = '<model name="'.$modelName.'"><![CDATA[' . $modelLabel . ']]></model>'; 
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