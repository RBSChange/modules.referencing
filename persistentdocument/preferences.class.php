<?php
/**
 * referencing_persistentdocument_preferences
 * @package referencing
 */
class referencing_persistentdocument_preferences extends referencing_persistentdocument_preferencesbase 
{
	/**
	 * @see f_persistentdocument_PersistentDocumentImpl::getLabel()
	 *
	 * @return String
	 */
	public function getLabel()
	{
		return f_Locale::translateUI(parent::getLabel());
	}
}