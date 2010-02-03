<?php
class referencing_UploadRedirectionsFileInputView extends f_view_BaseView
{
	public function _execute($context, $request)
	{
		$this->setTemplateName('Referencing-Upload-File-Input', K::HTML);
		$this->setAttribute('websiteId', $request->getParameter('websiteId'));
	}
}