<?php
class GenerateSitemapFileTask extends ChangeTask
{
	private $domain = null;
	
	protected function _main()
	{
		if ( ! is_null($this->domain) )
		{
			$website = website_WebsiteService::getInstance()->createQuery()
				->add(Restrictions::eq('domain', $this->domain))
				->findUnique();
			if ( ! is_null($website) )
			{
				referencing_ReferencingService::getInstance()->saveSitemapContents($website);
			}
			else $this->log("There is no website with this domain name: ".$this->domain.".", Project::MSG_ERR);
		}
	}
	
	
	/**
	 * @param Integer $value
	 */
	public function setDomain($value)
	{
		$this->domain = $value;
	}
}