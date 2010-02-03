<?php
/**
 * @date Fri, 18 Jul 2008 09:01:11 +0000
 * @author intbonjf
 */
class referencing_PreferencesService extends f_persistentdocument_DocumentService
{
	/**
	 * @var referencing_PreferencesService
	 */
	private static $instance;

	/**
	 * @return referencing_PreferencesService
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
	 * @return referencing_persistentdocument_preferences
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_referencing/preferences');
	}

	/**
	 * Create a query based on 'modules_referencing/preferences' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_referencing/preferences');
	}
	
	/**
	 * @param referencing_persistentdocument_preferences $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		$document->setLabel('&modules.referencing.bo.general.Module-name;');
	}
}