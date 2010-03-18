<?php
class referencing_UrlInfo
{
	public $loc;
	public $lastmod;
	public $priority;
	public $changefreq;
	
	public $isExcluded = false;
}

class referencing_ReferencingService extends BaseService
{
	const SITEMAP_PRIORITY = 'priority';
	const SITEMAP_CHANGEFREQ = 'changefreq';
	
	const SITEMAP_PRIORITIES_LIST_ID = 'modules_referencing/sitemappriorities';
	const SITEMAP_CHANGEFREQS_LIST_ID = 'modules_referencing/sitemapchangefreqs';
	const SITEMAP_AUTO_GENERATION_LIST_ID = 'modules_referencing/sitemapautogeneration';
	
	/**
	 * The maximum is 50 000 urls and 10Mb. 
	 * With 20 000 urls, we can reasonably assume that the weight won't be more than 10Mb. 
	 */
	const MAX_URL_PER_FILE = 20000;
	const MAX_URL_PER_INDEX_FILE = 1000;
	
	/**
	 * @var referencing_ReferencingService
	 */
	private static $instance;
	
	/**
	 * @return referencing_ReferencingService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @return String
	 */
	public final function getStorageDirectory()
	{
		return f_util_FileUtils::buildWebappPath('seo');
	}
	
	/**
	 * @return void
	 */
	public final function createStorageDirectory()
	{
		$seoDir = $this->getStorageDirectory();
		f_util_FileUtils::mkdir($seoDir);
		f_util_FileUtils::chown($seoDir, null, ApacheService::getInstance()->getGroup());
		f_util_FileUtils::chmod($seoDir, "2775");
	}
	
	/**
	 * @return void
	 */
	public function refreshHtaccess()
	{
		$apacheService = ApacheService::getInstance();
		
		// Get the redirections.
		$content = "# Rules for redirections.\n" . referencing_RedirectionService::getInstance()->generateRules(referencing_RedirectionService::MODE_HTACCESS);
		
		// Generate the file.
		$apacheService->generateSpecificConfFileForModule('referencing', $content);
		
		// Compile the .htaccess.
		$apacheService->compileHtaccess();
	}
	
	///////////////////////////////////////////////////////////////////////////
	//                                                                       //
	// robots.txt management                                                 //
	//                                                                       //
	///////////////////////////////////////////////////////////////////////////
	

	/**
	 * @param website_persistentdocument_website $website
	 * @param String $contents
	 */
	public function saveRobotsTxtContents($website, $contents)
	{
		// Update the document in the database.
		$doc = $this->getInfoDocumentForWebsite($website, true);
		$doc->setRobotsTxt($contents);
		$doc->save();
		
		// Update the file in seo directory.
		f_util_FileUtils::write($this->getRobotsTxtPathForWebsite($website), $contents, f_util_FileUtils::OVERRIDE);
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return String
	 */
	private function getRobotsTxtPathForWebsite($website)
	{
		return $this->getStorageDirectory() . DIRECTORY_SEPARATOR . 'robots-' . $website->getId() . '.txt';
	}
	
	/**
	 * @return String
	 */
	private function getDefaultRobotsTxtContent()
	{
		$filePath = FileResolver::getInstance()->setPackageName('modules_referencing')->setDirectory('lib')->getPath('default-robots.txt');
		try
		{
			return f_util_FileUtils::read($filePath);
		}
		catch (FileNotFoundException $e)
		{
			Framework::warn(__METHOD__ . ' EXCEPTION for path "' . $filePath . '": ' . $e->getMessage());
			return '';
		}
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param Boolean $useDefaultIfEmpty if set to true and there is no content defined in the database, the contents are loaded from the default-robots.txt file.
	 * @return String $contents
	 */
	public function getRobotsTxtContents($website, $useDefaultIfEmpty = true)
	{
		$content = '';
		
		// First look for a document.
		$doc = $this->getInfoDocumentForWebsite($website);
		if (! is_null($doc))
		{
			$content = $doc->getRobotsTxt();
		}
		
		// Next look for the default file.
		if ($content == '' && $useDefaultIfEmpty)
		{
			$content = $this->getDefaultRobotsTxtContent();
		}
		
		return $content;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return String $contents
	 */
	public function getRobotsTxtContentsFromDisk($website)
	{
		try
		{
			return f_util_FileUtils::read($this->getRobotsTxtPathForWebsite($website));
		}
		catch (FileNotFoundException $e)
		{
			Framework::warn(__METHOD__ . ' EXCEPTION: ' . $e->getMessage());
			return '';
		}
	}
	
	///////////////////////////////////////////////////////////////////////////
	//                                                                       //
	// sitemap.xml management                                                //
	//                                                                       //
	///////////////////////////////////////////////////////////////////////////
	

	/**
	 * @param website_persistentdocument_website $website
	 * @param Integer $siteMapIndex
	 * @return String
	 */
	private function getSitemapPathForWebsite($website, $siteMapIndex = 0)
	{
		return $this->getStorageDirectory() . DIRECTORY_SEPARATOR . 'sitemap-' . $website->getId() . '-' . $siteMapIndex . '.xml.gz';
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param Integer $siteMapIndex
	 * @return String
	 */
	private function getSitemapIndexPathForWebsite($website, $siteMapIndex = 0)
	{
		return $this->getStorageDirectory() . DIRECTORY_SEPARATOR . 'sitemap-index-' . $website->getId() . '-' . $siteMapIndex . '.xml.gz';
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param unknown_type $index
	 */
	private function getSitemapUrl($website, $index)
	{
		return $website->getUrl() . DIRECTORY_SEPARATOR . 'sitemap' . $index . '.xml.gz';
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return String
	 */
	public function getSitemapExcludedUrlList($website)
	{
		$doc = $this->getInfoDocumentForWebsite($website);
		if ($doc !== null)
		{
			return $doc->getSitemapExcludedUrl();
		}
		return '';
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param Boolean $createIfNeeded
	 * @return referencing_persistentdocument_websiteinfo
	 */
	private function getInfoDocumentForWebsite($website, $createIfNeeded = false)
	{
		$wis = referencing_WebsiteinfoService::getInstance();
		$doc = $wis->createQuery()->add(Restrictions::eq('website.id', $website->getId()))->findUnique();
		if ($createIfNeeded && is_null($doc))
		{
			$doc = $wis->getNewDocumentInstance();
			$doc->setWebsite($website);
		}
		return $doc;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $contents
	 */
	public function saveSitemapExcludedUrlList($website, $contents)
	{
		$doc = $this->getInfoDocumentForWebsite($website, true);
		$doc->setSitemapExcludedUrl($contents);
		$doc->save();
	}
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @return Array<Integer>
	 */
	private function getDocumentIdsForWebsite($website)
	{
		$result = array();
		$models = $this->getPersistentModels();
		foreach ($models as $model)
		{
			$result = array_merge($result, $this->buildDocumentIds($website, $model, -1));
		}
		return $result;
	}
	
	/**
	 * Returns an array containing the URLs of all the pages in the given website.
	 *
	 * @param website_persistentdoculent_website $website
	 * @param String $modelName
	 * @param Boolean $includeExcludedUrl
	 * @return Array<referencing_UrlInfo>
	 */
	public function getUrlInfoArray($website, $modelName = null, $includeExcludedUrl = false, $maxUrls = -1)
	{
		website_WebsiteModuleService::getInstance()->setCurrentWebsite($website);
		if (is_null($modelName))
		{
			$models = $this->getPersistentModels();
		}
		else
		{
			$models = array(f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName));
		}
		$urlInfoArray = array();		
		foreach ($models as $model)
		{
			$this->appendModelToUrlInfoArray($website, $model, $urlInfoArray, $includeExcludedUrl, $maxUrls);
		}
		return $urlInfoArray;
	}
	
	/**
	 * @return Array<f_persistentdocument_PersistentDocumentModel>
	 */
	public function getPersistentModels()
	{
		$result = array();
		$models = f_persistentdocument_PersistentDocumentModel::getDocumentModels();
		$excludedModels = $this->getExludedModels();
		foreach ($models as $model)
		{
			if ($model->getDocumentName() === 'preferences' || isset($excludedModels[$model->getName()]))
			{
				continue;
			}
			if (($model instanceof website_persistentdocument_pagemodel) || ($model instanceof website_persistentdocument_pageexternalmodel))
			{
				$result[] = $model;
			}
			else
			{				
				$service = $model->getDocumentService();
				if (f_util_ClassUtils::methodExists($service, "hasIdsForSitemap"))
				{
					if ($service->hasIdsForSitemap())
					{
						$result[] = $model;
					}
				}
				else 
				{
					$tagName = $this->buildFunctionalTagName($model, 'detail');
					if ($this->pageHasTag($tagName))
					{
						$result[] = $model;
					}
					else
					{
						$tagName = $this->buildContextualTagName($model, 'detail');
						if ($this->pageHasTag($tagName))
						{
							
							$result[] = $model;
						}
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * @param String $tagName
	 */
	private function pageHasTag($tagName, $website = null)
	{
		$query = website_PageService::getInstance()->createQuery()->add(Restrictions::published())->add(Restrictions::hasTag($tagName));
		
		if ($website !== null)
		{
			$query->add(Restrictions::descendentOf($website->getId()));
		}
		$result = $query->setProjection(Projections::rowCount('rowcount'))->find();
		
		return $result[0]['rowcount'] > 0;
	}
	
	private $exludedModels = null;
	
	private function getExludedModels()
	{
		if ($this->exludedModels === null)
		{
			$this->exludedModels = array();
			if (Framework::hasConfiguration('modules/referencing/exludedmodels'))
			{
				$models = preg_split('/[\s,]+/', Framework::getConfiguration('modules/referencing/exludedmodels'));
				foreach ($models as $value)
				{
					$modelName = trim($value);
					if (! f_util_StringUtils::isEmpty($modelName))
					{
						$this->exludedModels[$modelName] = true;
					}
				}
			}
		}
		return $this->exludedModels;
	}
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return array<'id' => Integer>
	 */
	private function buildDocumentIds($website, $model, $maxUrl)
	{
		$documentService = $model->getDocumentService();
		if(f_util_ClassUtils::methodExists($documentService, 'getIdsForSitemap'))
		{
			$resultArray = $documentService->getIdsForSitemap($website, $maxUrl);		
		}
		else
		{
			$resultArray = array();
			$query = $model->getDocumentService()->createQuery()->add(Restrictions::published())->add(Restrictions::eq('model', $model->getName()))->setProjection(Projections::property('id'));
			
			if ($maxUrl > 0)
			{
				$query->setMaxResults($maxUrl);
			}
			
			if (($model instanceof website_persistentdocument_pagemodel) || ($model instanceof website_persistentdocument_pageexternalmodel))
			{
				$resultArray = $query->add(Restrictions::descendentOf($website->getId()))->findColumn('id');
			}
			else
			{
				$tagName = $this->buildFunctionalTagName($model, 'detail');
				if ($this->pageHasTag($tagName, $website))
				{
					$resultArray = $query->add(Restrictions::descendentOf($website->getId()))->findColumn('id');
				}
				else
				{
					$tagName = $this->buildContextualTagName($model, 'detail');
					if ($this->pageHasTag($tagName, $website))
					{
						$resultArray = $query->findColumn('id');
					}
				}
			}		
		}
		
		return $resultArray;
	}
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @param Array<referencing_UrlInfo> $urlInfoArray
	 * @param Boolean $includeExcludedUrl
	 *
	 */
	private function appendModelToUrlInfoArray($website, $model, &$urlInfoArray, $includeExcludedUrl, $maxUrl)
	{
		$resultArray = $this->buildDocumentIds($website, $model, $maxUrl);
		
		$modelPriority = $this->getSitemapOption($website, $model->getName(), self::SITEMAP_PRIORITY);
		$modelChangefreq = $this->getSitemapOption($website, $model->getName(), self::SITEMAP_CHANGEFREQ);
		foreach ($resultArray as $result)
		{
			$doc = DocumentHelper::getDocumentInstance($result);
			// Cas particulier des pages de détail :
			// Si une page a un tag 'functional_*', alors elle ne doit pas figurer dans
			// la liste des URLs du site car la page n'est qu'un "support" pour un autre
			// document, et c'est ce document qui a une URL.
			if ($this->isAllowedInSitemap($doc))
			{
				$url = LinkHelper::getDocumentUrl($doc);
				$isUrlExcluded = $this->isUrlExcludedInWebsite($url, $website);
				if (! $isUrlExcluded || $includeExcludedUrl)
				{
					$urlInfo = new referencing_UrlInfo();
					$urlInfo->loc = $url;
					$urlInfo->lastmod = date('c', date_Calendar::getInstance($doc->getModificationDate())->getTimestamp());
					$urlInfo->isExcluded = $isUrlExcluded;
					$urlPriority = $this->getSitemapOptionForUrl($website, $url, self::SITEMAP_PRIORITY);
					$urlChangefreq = $this->getSitemapOptionForUrl($website, $url, self::SITEMAP_CHANGEFREQ);
					$urlInfo->priority = is_null($urlPriority) ? $modelPriority : $urlPriority;
					$urlInfo->changefreq = is_null($urlChangefreq) ? $modelChangefreq : $urlChangefreq;
					$urlInfoArray[] = $urlInfo;
				}
			}
			$doc = null;
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return Boolean
	 */
	private function isAllowedInSitemap($document)
	{
		$isAllowedInSitemap = true;
		if ($document instanceof website_persistentdocument_page || $document instanceof website_persistentdocument_pageexternal)
		{
			$ts = TagService::getInstance();
			$tags = $ts->getTags($document);
			foreach ($tags as $tag)
			{
				if ($ts->isFunctionalTag($tag) || $this->isSystemPageTag($tag) || $document->getNavigationVisibility() == 0)
				{
					$isAllowedInSitemap = false;
					break;
				}
			}
		}
		return $isAllowedInSitemap;
	}
	
	/**
	 * @param String $tag
	 * @return Boolean
	 */
	private function isSystemPageTag($tag)
	{
		return false || $tag == 'contextual_website_website_error404' || $tag == 'contextual_website_website_server-error' || $tag == 'contextual_website_website_error401-1';
	}
	
	/**
	 * @var Array<String>
	 */
	private $excludedUrl = array();
	
	/**
	 * @param String $url
	 * @param website_persistentdoculent_website $website
	 * @return Boolean
	 */
	private function isUrlExcludedInWebsite($urlToCheck, $website)
	{
		$websiteId = $website->getId();
		if (! isset($this->excludedUrl[$websiteId]))
		{
			$this->excludedUrl[$websiteId] = explode("\n", $this->getSitemapExcludedUrlList($website));
			foreach ($this->excludedUrl[$websiteId] as &$url)
			{
				$url = trim($url);
			}
		}
		return in_array($urlToCheck, $this->excludedUrl[$websiteId]);
	}
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @return void
	 */
	public function saveSitemapContents($website)
	{
		$this->createStorageDirectory();
		
		$docIds = $this->getDocumentIdsForWebsite($website);
		$docIdCount = count($docIds);
		$siteMaps = array();
		$siteMapIndex = 0;
		
		for($i = 0; $i < $docIdCount; $i += self::MAX_URL_PER_FILE)
		{
			$tmpFile = f_util_FileUtils::getTmpFile('smurl' . $siteMapIndex . '_');
			file_put_contents($tmpFile, '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
			$chunks = array_chunk(array_slice($docIds, $i, self::MAX_URL_PER_FILE), 500);
			$nChunks = count($chunks);
			$index = 1;
			foreach ($chunks as $chunk)
			{
				Framework::info("processing chunk " . $index ++ . " out of " . $nChunks . " for " . $tmpFile);
				$batch = f_util_FileUtils::buildWebeditPath('modules', 'referencing', 'bin', 'generateSitemap.php');
				$processHandle = popen("php $batch " . WEBEDIT_HOME . " " . RequestContext::getInstance()->getLang() . " " . $website->getId() . " " . $tmpFile . " " . implode(" ", $chunk), "r");
				while (($string = fread($processHandle, 1000)) != false)
				{
					Framework::info($string);
				}
				pclose($processHandle);
			}
			file_put_contents($tmpFile, "\n</urlset>", FILE_APPEND);
			
			$this->compressFile($tmpFile, $this->getSitemapPathForWebsite($website, $siteMapIndex));
			unlink($tmpFile);
			
			$siteMaps[] = array('url' => $this->getSitemapUrl($website, $siteMapIndex), 'lastMod' => date('c', time()));
			$siteMapIndex ++;
		}
		
		// Generate the sitemap index.
		$templateIndex = TemplateLoader::getInstance()->setPackageName('modules_referencing')->setMimeContentType('xml')->load('sitemap-index');
		
		$siteMapsCount = count($siteMaps);
		Framework::info("$siteMapsCount generated url sitemap files.");
		
		$siteMapIndex = 0;
		for($i = 0; $i < $siteMapsCount; $i += self::MAX_URL_PER_INDEX_FILE)
		{
			Framework::info("generating index file $siteMapIndex ");
			$subSiteMaps = array_slice($siteMaps, $i, self::MAX_URL_PER_INDEX_FILE);
			$templateIndex->setAttribute('sitemaps', $subSiteMaps);
			$filePath = $this->getSitemapIndexPathForWebsite($website, $siteMapIndex);
			// Content is gzipped (URL rewriting rule in .htaccess points to sitemap.xml.gz).
			Framework::info("Saving index file $filePath");
			f_util_FileUtils::write($filePath, gzencode($templateIndex->execute()), f_util_FileUtils::OVERRIDE);
			$siteMapIndex ++;
		}
	}
	
	private function compressFile($source, $dest)
	{
		Framework::info(__METHOD__ . " from $source to $dest");
		
		$fp_out = gzopen($dest, 'w9');
		if ($fp_out)
		{
			$fp_in = fopen($source, 'rb');
			if ($fp_in)
			{
				while (! feof($fp_in))
				{
					gzwrite($fp_out, fread($fp_in, 1024 * 512));
				}
				fclose($fp_in);
				Framework::info(__METHOD__ . " Compressed");
			}
			gzclose($fp_out);
		}
	}
	
	public function updateTempSiteMap($filerc, $website, $docIds)
	{
		foreach ($docIds as $id)
		{
			try
			{
				$doc = DocumentHelper::getDocumentInstance($id);
				$model = $doc->getPersistentModel();
				
				if ($this->isAllowedInSitemap($doc))
				{
					$url = LinkHelper::getDocumentUrl($doc);
					$isUrlExcluded = $this->isUrlExcludedInWebsite($url, $website);
					if (! $isUrlExcluded)
					{
						$modelPriority = $this->getSitemapOption($website, $model->getName(), self::SITEMAP_PRIORITY);
						$modelChangefreq = $this->getSitemapOption($website, $model->getName(), self::SITEMAP_CHANGEFREQ);
						
						$urlInfo = new referencing_UrlInfo();
						$urlInfo->loc = $url;
						$urlInfo->lastmod = date('c', date_Calendar::getInstance($doc->getModificationDate())->getTimestamp());
						$urlInfo->isExcluded = $isUrlExcluded;
						$urlPriority = $this->getSitemapOptionForUrl($website, $url, self::SITEMAP_PRIORITY);
						$urlChangefreq = $this->getSitemapOptionForUrl($website, $url, self::SITEMAP_CHANGEFREQ);
						$urlInfo->priority = is_null($urlPriority) ? $modelPriority : $urlPriority;
						$urlInfo->changefreq = is_null($urlChangefreq) ? $modelChangefreq : $urlChangefreq;
						
						fwrite($filerc, "\t<url>");
						fwrite($filerc, "<loc>" . $urlInfo->loc . "</loc>");
						fwrite($filerc, "<lastmod>" . $urlInfo->lastmod . "</lastmod>");
						if ($urlInfo->changefreq)
						{
							fwrite($filerc, "<changefreq>" . $urlInfo->changefreq . "</changefreq>");
						}
						if ($urlInfo->priority)
						{
							fwrite($filerc, "<priority>" . $urlInfo->priority . "</priority>");
						}
						fwrite($filerc, "</url>\n");
					}
				}
			}
			catch (Exception $e)
			{
				Framework::exception($e);
			}
		}
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param Integer $siteMapIndex
	 * @return String gzipped sitemap contents | null if the map does not exists
	 */
	public function getSitemapContents($website, $siteMapIndex = 0)
	{
		$path = $this->getSitemapPathForWebsite($website, $siteMapIndex);
		if (file_exists($path))
		{
			return f_util_FileUtils::read($path);
		}
		return null;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param Integer $siteMapIndex
	 * @return String gzipped sitemap contents | null if the map does not exists
	 */
	public function getSitemapIndexContents($website, $siteMapIndex = 0)
	{
		$path = $this->getSitemapIndexPathForWebsite($website, $siteMapIndex);
		Framework::info(__METHOD__ . "($path)");
		if (file_exists($path))
		{
			return f_util_FileUtils::read($path);
		}
		return null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @param String $type May be 'list' or 'detail'.
	 * @return String
	 */
	private function buildFunctionalTagName($model, $type)
	{
		return 'functional_' . $model->getOriginalModuleName() . '_' . $model->getOriginalDocumentName() . '-' . $type;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @param String $type May be 'list' or 'detail'.
	 * @return String
	 */
	private function buildContextualTagName($model, $type)
	{
		return 'contextual_website_website_modules_' . $model->getOriginalModuleName() . '_page-' . $type;
	}
	
	private $sitemapOptionsPerWebsite;
	
	/**
	 * @param unknown_type $website
	 * @param unknown_type $modelName
	 * @param unknown_type $optionName
	 * @return String
	 */
	public function getSitemapOption($website, $modelName, $optionName)
	{
		$websiteId = $website->getId();
		if (! isset($this->sitemapOptionsPerWebsite[$websiteId]))
		{
			$infoDoc = $this->getInfoDocumentForWebsite($website);
			if (! is_null($infoDoc))
			{
				$this->sitemapOptionsPerWebsite[$websiteId] = unserialize($infoDoc->getSitemapOptions());
			}
		}
		$sitemapOptions = &$this->sitemapOptionsPerWebsite[$websiteId];
		
		if (is_array($sitemapOptions) && isset($sitemapOptions[$modelName]) && isset($sitemapOptions[$modelName][$optionName]))
		{
			return $sitemapOptions[$modelName][$optionName];
		}
		return null;
	}
	
	/**
	 * @param String $website
	 * @param String $modelName
	 * @param String $optionName
	 * @param String $value
	 * @param Boolean $save
	 * @return String
	 */
	public function setSitemapOption($website, $modelName, $optionName, $value, $save = true)
	{
		$infoDoc = $this->getInfoDocumentForWebsite($website, true);
		$websiteId = $website->getId();
		if (! isset($this->sitemapOptionsPerWebsite[$websiteId]))
		{
			if (! is_null($infoDoc))
			{
				$this->sitemapOptionsPerWebsite[$websiteId] = unserialize($infoDoc->getSitemapOptions());
			}
		}
		$sitemapOptions = &$this->sitemapOptionsPerWebsite[$websiteId];
		
		if (! is_array($sitemapOptions))
		{
			$sitemapOptions = array();
		}
		if (! isset($sitemapOptions[$modelName]))
		{
			$sitemapOptions[$modelName] = array();
		}
		$sitemapOptions[$modelName][$optionName] = $value;
		$infoDoc->setSitemapOptions(serialize($sitemapOptions));
		if ($save)
		{
			$infoDoc->save();
		}
	}
	
	private $sitemapUrlInfoPerWebsite;
	
	/**
	 * @param unknown_type $website
	 * @param unknown_type $modelName
	 * @param unknown_type $optionName
	 * @return String
	 */
	public function getSitemapOptionForUrl($website, $url, $optionName)
	{
		$websiteId = $website->getId();
		if (! isset($this->sitemapUrlInfoPerWebsite[$websiteId]))
		{
			$infoDoc = $this->getInfoDocumentForWebsite($website);
			if (! is_null($infoDoc))
			{
				$this->sitemapUrlInfoPerWebsite[$websiteId] = unserialize($infoDoc->getSitemapUrlInfo());
			}
		}
		$sitemapUrlInfo = &$this->sitemapUrlInfoPerWebsite[$websiteId];
		
		if (is_array($sitemapUrlInfo) && isset($sitemapUrlInfo[$url]) && isset($sitemapUrlInfo[$url][$optionName]))
		{
			return $sitemapUrlInfo[$url][$optionName];
		}
		return null;
	}
	
	/**
	 * @param String $website
	 * @param String $modelName
	 * @param String $optionName
	 * @param String $value
	 * @param Boolean $save
	 * @return String
	 */
	public function setSitemapOptionForUrl($website, $url, $optionName, $value, $save = true)
	{
		$infoDoc = $this->getInfoDocumentForWebsite($website, true);
		$websiteId = $website->getId();
		if (! isset($this->sitemapUrlInfoPerWebsite[$websiteId]))
		{
			if (! is_null($infoDoc))
			{
				$this->sitemapUrlInfoPerWebsite[$websiteId] = unserialize($infoDoc->getSitemapUrlInfo());
			}
		}
		$sitemapUrlInfo = &$this->sitemapUrlInfoPerWebsite[$websiteId];
		
		if (! is_array($sitemapUrlInfo))
		{
			$sitemapUrlInfo = array();
		}
		if (! isset($sitemapUrlInfo[$url]))
		{
			$sitemapUrlInfo[$url] = array();
		}
		$sitemapUrlInfo[$url][$optionName] = $value;
		$infoDoc->setSitemapUrlInfo(serialize($sitemapUrlInfo));
		if ($save)
		{
			$infoDoc->save();
		}
	}
	
	/**
	 * @return Float
	 */
	public function getSitemapDefaultPriority()
	{
		$prefValue = ModuleService::getInstance()->getPreferenceValue('referencing', 'sitemapDefaultPriority');
		return is_null($prefValue) ? '0.5' : $prefValue;
	}
	
	/**
	 * @return Float
	 */
	public function getSitemapDefaultChangefreq()
	{
		$prefValue = ModuleService::getInstance()->getPreferenceValue('referencing', 'sitemapDefaultChangefreq');
		return is_null($prefValue) ? 'monthly' : $prefValue;
	}
	
	/**
	 * @return void
	 */
	public function onDayChange()
	{
		$prefValue = ModuleService::getInstance()->getPreferenceValue('referencing', 'sitemapAutoGeneration');
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ':' . $prefValue);
		}
		$generate = false;
		switch ($prefValue)
		{
			case 'daily' :
				$generate = true;
				break;
			case 'weekly' :
				$generate = (date('w') == '1'); // monday
				break;
			case 'monthly' :
				$generate = (date('j') == '1'); // first day of month
				break;
			case 'never' :
				break;
		}
		
		if ($generate)
		{
			$this->generateAllSitemapFiles();
		}
	}
	
	/**
	 * @return void
	 */
	private function generateAllSitemapFiles()
	{
		foreach (website_WebsiteService::getInstance()->createQuery()->find() as $website)
		{
			$this->saveSitemapContents($website);
		}
	}
	
	///////////////////////////////////////////////////////////////////////////
	//                                                                       //
	// IDs management                                                        //
	//                                                                       //
	///////////////////////////////////////////////////////////////////////////
	

	/**
	 * @param website_persistentdocument_website $website
	 * @return array<String=>Array<String=>String>>
	 * @example
	 *   [
	 *     'yahoo' => [ 'id' => '1234567', 'content => '4e487dda' ],
	 *     'google' => [ 'id' => '111da4567', 'content => '' ],
	 *     'msn' => [ 'id' => '', 'content' => '<users><user>1245984</user></users>' ]
	 *   ]
	 */
	public function getIdInfo($website)
	{
		$infoDoc = $this->getInfoDocumentForWebsite($website);
		if (! is_null($infoDoc))
		{
			return unserialize($infoDoc->getEngineIdInfo());
		}
		return array();
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $engine
	 * @param String $id
	 * @return String or null
	 */
	public function getIdContentForEngineAndId($website, $engine, $id)
	{
		$infoDoc = $this->getInfoDocumentForWebsite($website);
		if (! is_null($infoDoc))
		{
			$info = unserialize($infoDoc->getEngineIdInfo());
			//echo "**<br />"; var_dump($info); echo "<br />**\n";
			if (isset($info[$engine]) && isset($info[$engine]['id']) && $info[$engine]['id'] == $id && isset($info[$engine]['content']))
			{
				return $info[$engine]['content'];
			}
		}
		return null;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $engine
	 * @param String $id
	 * @param Boolean $save
	 * @return array<String=>String>
	 */
	public function setIdInfo($website, $engine, $id, $content, $save = true)
	{
		$infoDoc = $this->getInfoDocumentForWebsite($website, true);
		$info = unserialize($infoDoc->getEngineIdInfo());
		$info[$engine] = array('id' => $id, 'content' => $content);
		$infoDoc->setEngineIdInfo(serialize($info));
		if ($save)
		{
			$infoDoc->save();
		}
	}
}
