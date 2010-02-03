<?php
class referencing_LoadUrlRewritingModuleListAction extends referencing_Action
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		// Get existing documents.
		$urlRewritingInfos = referencing_UrlrewritinginfoService::getInstance()->createQuery()->find();
		$existingInfos = array();
		foreach ($urlRewritingInfos as $urlRewritingInfo)
		{
			$existingInfos[$urlRewritingInfo->getPackage()] = $urlRewritingInfo;
		}

		// Construct the module list.
		$ruleParser = website_urlrewriting_RulesParser::getInstance();
		$modules = ModuleService::getInstance()->getModulesObj();
		foreach ($modules as $module)
		{
			$moduleLabel = f_Locale::translate('&modules.'.$module->getName().'.bo.general.Module-name;');
			$packageName = $module->getFullName();
			
			// Check document existence.
			if (isset($existingInfos[$packageName]))
			{
				$existingInfo = $existingInfos[$packageName]; 
				$documentId = $existingInfo->getId();
				$hasDocument = 'true';
			}
			else 
			{
				$documentId = '';
				$hasDocument = 'false';
			}
			
			// Check file existence and updates.
			$definitionFilePath = $ruleParser->getDefinitionFilePathByPackage($module->getFullName());
			$hasDefinitionFile = (!is_null($definitionFilePath)) ? 'true' : 'false';
			$hasDefinitionFileChanged = 'false';
			if (!is_null($definitionFilePath))
			{
				$currentMd5 = md5(f_util_FileUtils::read($definitionFilePath));
				if ($hasDocument == 'true')
				{
					$hasDefinitionFileChanged = (($currentMd5 != $existingInfo->getBaseFileSignature())) ? 'true' : 'false';
				}
			}
			
			// Content generation.
			$contents[$moduleLabel] = '<module name="'.$packageName.'" hasDocument="'.$hasDocument.'" documentId="'.$documentId.'" hasDefinitionFile="'.$hasDefinitionFile.'" hasDefinitionFileChanged="'.$hasDefinitionFileChanged.'"><![CDATA['.$moduleLabel.']]></module>';
			ksort($contents);
		}
		
		$request->setAttribute('contents', '<modules>'.join("\n", $contents).'</modules>');
		return self::getSuccessView();
	}
}