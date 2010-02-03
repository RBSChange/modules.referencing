<?php
class referencing_WebsiteinfoService extends f_persistentdocument_DocumentService
{
	/**
	 * @var referencing_WebsiteinfoService
	 */
	private static $instance;

	/**
	 * @return referencing_WebsiteinfoService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return referencing_persistentdocument_websiteinfo
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_referencing/websiteinfo');
	}

	/**
	 * Create a query based on 'modules_referencing/websiteinfo' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_referencing/websiteinfo');
	}

	/**
	 * @param referencing_persistentdocument_websiteinfo $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		$document->setLabel("Information about website " . $document->getWebsite()->getDomain());
	}
}