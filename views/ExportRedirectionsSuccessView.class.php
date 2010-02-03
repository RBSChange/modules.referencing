<?php
class referencing_ExportRedirectionsSuccessView extends f_view_BaseView
{

	public function _execute($context, $request)
	{
		$websiteId = $request->getParameter('websiteId');
		$website = DocumentHelper::getDocumentInstance($websiteId);
		$fieldNames = array("Old URL", "New URL");
		$data = array();

		foreach (referencing_RedirectionService::getInstance()->createQuery()->add(Restrictions::eq('website.id', $websiteId))->find() as $redirection)
		{
			$data[] = array($redirection->getOldUrl(), $redirection->getNewUrl());
		}

		$fileName = 'export_redirections_'.str_replace(' ', '-', $website->getLabel()).'_'.date('Ymd_His').'.csv';
		$options = new f_util_CSVUtils_export_options();
		$options->separator = ";";
		$options->outputHeaders = false;

		$csv = f_util_CSVUtils::export($fieldNames, $data, $options);
		header("Content-type: text/comma-separated-values");
		header('Content-length: '.strlen($csv));
		header('Content-disposition: attachment; filename="'.$fileName.'"');
		echo $csv;
		exit;
	}
}