<?php
class referencing_ActionBase extends f_action_BaseAction
{
	
	/**
	 * Returns the referencing_PreferencesService to handle documents of type "modules_referencing/preferences".
	 *
	 * @return referencing_PreferencesService
	 */
	public function getPreferencesService()
	{
		return referencing_PreferencesService::getInstance();
	}
	
	/**
	 * Returns the referencing_RedirectionService to handle documents of type "modules_referencing/redirection".
	 *
	 * @return referencing_RedirectionService
	 */
	public function getRedirectionService()
	{
		return referencing_RedirectionService::getInstance();
	}
	
	/**
	 * Returns the referencing_WebsiteinfoService to handle documents of type "modules_referencing/websiteinfo".
	 *
	 * @return referencing_WebsiteinfoService
	 */
	public function getWebsiteinfoService()
	{
		return referencing_WebsiteinfoService::getInstance();
	}
	
	/**
	 * Returns the referencing_UrlrewritinginfoService to handle documents of type "modules_referencing/urlrewritinginfo".
	 *
	 * @return referencing_UrlrewritinginfoService
	 */
	public function getUrlrewritinginfoService()
	{
		return referencing_UrlrewritinginfoService::getInstance();
	}
	
}