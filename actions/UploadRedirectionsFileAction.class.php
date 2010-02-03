<?php
class referencing_UploadRedirectionsFileAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		if ($request->hasParameter('submit'))
		{
			$website = DocumentHelper::getDocumentInstance($request->getParameter('websiteId'));
			$fileInfo = $request->getFiles();
			if ($fileInfo == null || empty($fileInfo['file']['tmp_name']) || !is_readable($fileInfo['file']['tmp_name']))
			{
				$fileName = realpath(dirname(__FILE__) . '/../setup/empty.csv');
				Framework::error(__METHOD__ . ' Use empty file :' . $fileName);
			}
			else
			{
				$fileName = $fileInfo['file']['tmp_name'];
			}
			$errors = new ArrayObject();
			$redirectionCount = referencing_RedirectionService::getInstance()->importFile($fileName, $website, $errors);
			$warnArray = array();
			$errorArray = array();
			
			foreach ($errors->getArrayCopy() as $error) 
			{
				if ($error['type'] == 'Alert')
				{
					$warnArray[] = $error;
				}
				else
				{
					$errorArray[] = $error;
				}
			}
			
			$request->setAttribute('redirectionCount', $redirectionCount);
			$request->setAttribute('errorArray', $errorArray);
			$request->setAttribute('errorCount', count($errorArray));
			
			$request->setAttribute('warnArray', $warnArray);
			$request->setAttribute('warnCount', count($warnArray));
			
			$request->setAttribute('totalmessage', count($warnArray) + count($errorArray));
			
			return View::SUCCESS;
		}
		return View::INPUT;
	}

	/**
	 * @return Boolean
	 */
	public function isSecure()
	{
		return true;
	}
}