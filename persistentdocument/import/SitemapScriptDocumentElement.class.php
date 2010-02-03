<?php
class referencing_SitemapScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return referencing_persistentdocument_sitemap
     */
    protected function initPersistentDocument()
    {
    	return referencing_SitemapService::getInstance()->getNewDocumentInstance();
    }
}