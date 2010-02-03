<?php
class referencing_LoadUrlRewritingInfoForPackageAction extends referencing_Action
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$package = $request->getParameter('package');

		$ruleParser = website_urlrewriting_RulesParser::getInstance();
		$definitionFilePath = $ruleParser->getDefinitionFilePathByPackage($package);
		if (!is_null($definitionFilePath))
		{
			$fileContent = f_util_FileUtils::read($definitionFilePath);
			$fileMd5 = md5($fileContent);
		}
		else 
		{
			$fileContent = '';
			$fileMd5 = '';
		}
		
		$components[] = '<component name="package">'.$package.'</component>';		
		$components[] = '<component name="content"><![CDATA['.$fileContent.']]></component>';		
		$components[] = '<component name="baseFileSignature"><![CDATA['.$fileMd5.']]></component>';		

		$request->setAttribute('contents', '<document>'.join("\n", $components).'</document>');
		return self::getSuccessView();
	}
	
	/**
	 * @return Boolean
	 */
	protected function suffixSecureActionByDocument()
	{
		return false;
	}
}