<?php
class GenerateSitemapFilesTask extends ChangeTask
{
	protected function _main()
	{
		$websiteArray = website_WebsiteService::getInstance()->createQuery()->find();
		foreach ($websiteArray as $website)
		{
			referencing_ReferencingService::getInstance()->saveSitemapContents($website);
		}
	}
}