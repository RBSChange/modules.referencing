<?php
/**
 * referencing_UrlrewritinginfoScriptDocumentElement
 * @package modules.referencing.persistentdocument.import
 */
class referencing_UrlrewritinginfoScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return referencing_persistentdocument_urlrewritinginfo
     */
    protected function initPersistentDocument()
    {
    	return referencing_UrlrewritinginfoService::getInstance()->getNewDocumentInstance();
    }
}