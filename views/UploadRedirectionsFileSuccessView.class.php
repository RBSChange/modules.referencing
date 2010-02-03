<?php
class referencing_UploadRedirectionsFileSuccessView extends f_view_BaseView
{
	public function _execute($context, $request)
	{
		$this->setTemplateName('Referencing-Upload-File-Success', K::HTML);
		$this->setAttribute('errorArray', $request->getAttribute('errorArray'));
		$this->setAttribute('errorCount', $request->getAttribute('errorCount'));
		
		$this->setAttribute('warnArray', $request->getAttribute('warnArray'));
		$this->setAttribute('warnCount', $request->getAttribute('warnCount'));
		
		$this->setAttribute('redirectionCount', $request->getAttribute('redirectionCount'));
		$this->setAttribute('totalmessage', $request->getAttribute('totalmessage'));
	}
}