<?php
class referencing_WebsiteinfoScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return referencing_persistentdocument_websiteinfo
     */
    protected function initPersistentDocument()
    {
    	return referencing_WebsiteinfoService::getInstance()->getNewDocumentInstance();
    }
}