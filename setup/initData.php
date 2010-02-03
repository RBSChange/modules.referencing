<?php
class referencing_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
		
		// Create seo sub-directory into the webapp.
		referencing_ReferencingService::getInstance()->createStorageDirectory();

		// Generate the htaccess file.
		referencing_ReferencingService::getInstance()->refreshHtaccess();
	}	
}