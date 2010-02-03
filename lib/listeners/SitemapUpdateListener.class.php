<?php
class referencing_SitemapUpdateListener
{
	public function onDayChange($sender, $params)
	{
		referencing_ReferencingService::getInstance()->onDayChange();
	}
}