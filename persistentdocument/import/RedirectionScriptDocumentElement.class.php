<?php
class referencing_RedirectionScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return referencing_persistentdocument_redirection
     */
    protected function initPersistentDocument()
    {
    	return referencing_RedirectionService::getInstance()->getNewDocumentInstance();
    }
}