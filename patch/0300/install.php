<?php
/**
 * referencing_patch_0300
 * @package modules.referencing
 */
class referencing_patch_0300 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Remove old properties for robotsTxt and search engine ids.
		$archivePath = f_util_FileUtils::buildWebeditPath('modules/referencing/patch/0300/websiteinfo-old.xml');
		$oldModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($archivePath), 'referencing', 'websiteinfo');
		$oldProp = $oldModel->getPropertyByName('robotsTxt');
		f_persistentdocument_PersistentProvider::getInstance()->delProperty('referencing', 'websiteinfo', $oldProp);
		$oldModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($archivePath), 'referencing', 'websiteinfo');
		$oldProp = $oldModel->getPropertyByName('engineIdInfo');
		f_persistentdocument_PersistentProvider::getInstance()->delProperty('referencing', 'websiteinfo', $oldProp);
		
		// Add new forLang property.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/referencing/persistentdocument/websiteinfo.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'referencing', 'websiteinfo');
		$newProp = $newModel->getPropertyByName('forLang');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('referencing', 'websiteinfo', $newProp);
		
		// Set the forLang property for existing docs.
		foreach (referencing_WebsiteinfoService::getInstance()->createQuery()->find() as $websiteInfo)
		{
			$website = $websiteInfo->getWebsite();
			$websiteInfo->setForLang($website->getLocalizebypath() ? 'all' : $website->getLang());
			$websiteInfo->save();
		}
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'referencing';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}
}