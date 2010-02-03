<?php
class referencing_PreferencesScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return referencing_persistentdocument_preferences
     */
    protected function initPersistentDocument()
    {
    	$document = ModuleService::getInstance()->getPreferencesDocument('referencing');
    	return ($document !== null) ? $document : referencing_PreferencesService::getInstance()->getNewDocumentInstance();
    }
}