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
		return f_util_FileUtils::buildChangeBuildPath('seo');
	}
	
	/**
	 * @return void
	 */
	public final function createStorageDirectory()
	{
		$seoDir = $this->getStorageDirectory();
		f_util_FileUtils::mkdir($seoDir);
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
	// sitemap.xml management                                                //
	//                                                                       //
	///////////////////////////////////////////////////////////////////////////
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $forLang
	 * @param Integer $siteMapIndex
	 * @return String
	 */
	private function getSitemapPathForWebsite($website, $forLang, $siteMapIndex = 0)
	{
		return $this->getStorageDirectory() . DIRECTORY_SEPARATOR . 'sitemap-' . $website->getId() . '-' . $forLang . '-' . $siteMapIndex . '.xml.gz';
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $forLang
	 * @param Integer $siteMapIndex
	 * @return String
	 */
	private function getSitemapIndexPathForWebsite($website, $forLang, $siteMapIndex = 0)
	{
		return $this->getStorageDirectory() . DIRECTORY_SEPARATOR . 'sitemap-index-' . $website->getId() . '-' . $forLang . '-' . $siteMapIndex . '.xml.gz';
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $forLang
	 * @param unknown_type $index
	 */
	private function getSitemapUrl($website, $forLang, $index)
	{
		$websiteUrl = ($forLang !== 'all') ? $website->getUrlForLang($forLang) : $website->getUrl();
		return $websiteUrl . DIRECTORY_SEPARATOR . 'sitemap' . $index . '.xml.gz';
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $forLang
	 * @return String
	 */
	public function getSitemapExcludedUrlList($website, $forLang)
	{
		$doc = $this->getInfoDocumentForWebsiteAndLang($website, $forLang);
		if ($doc !== null)
		{
			return $doc->getSitemapExcludedUrl();
		}
		return '';
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $forLang
	 * @param Boolean $createIfNeeded
	 * @return referencing_persistentdocument_websiteinfo
	 */
	private function getInfoDocumentForWebsiteAndLang($website, $forLang, $createIfNeeded = false)
	{
		$wis = referencing_WebsiteinfoService::getInstance();
		$forLang = $website->getLocalizebypath() ? 'all' : $forLang;
		
		$doc = $wis->createQuery()->add(Restrictions::eq('website', $website))
			->add(Restrictions::eq('forLang', $forLang))->findUnique();
		if ($createIfNeeded && $doc === null)
		{
			$doc = $wis->getNewDocumentInstance();
			$doc->setWebsite($website);
			$doc->setForLang($forLang);
		}
		return $doc;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $forLang
	 * @param String $contents
	 */
	public function saveSitemapExcludedUrlList($website, $forLang, $contents)
	{
		$doc = $this->getInfoDocumentForWebsiteAndLang($website, $forLang, true);
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
	 * @param String $forLang
	 * @param String $modelName
	 * @param Boolean $includeExcludedUrl
	 * @return Array<referencing_UrlInfo>
	 */
	public function getUrlInfoArray($website, $forLang, $modelName = null, $includeExcludedUrl = false, $maxUrls = -1)
	{
		website_WebsiteModuleService::getInstance()->setCurrentWebsite($website);
		if ($modelName === null)
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
			$this->appendModelToUrlInfoArray($website, $forLang, $model, $urlInfoArray, $includeExcludedUrl, $maxUrls);
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
	
	/**
	 * @var Array<String, Boolean>
	 */
	private $exludedModels = null;
	
	/**
	 * @return Array<String, Boolean>
	 */
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
		if (f_util_ClassUtils::methodExists($documentService, 'getIdsForSitemap'))
		{
			$resultArray = $documentService->getIdsForSitemap($website, $maxUrl);		
		}
		else
		{
			$resultArray = array();
			$query = $model->getDocumentService()->createQuery()
				->add(Restrictions::published())->add(Restrictions::eq('model', $model->getName()))
				->setProjection(Projections::property('id'));

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
	 * @param String $forLang
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @param Array<referencing_UrlInfo> $urlInfoArray
	 * @param Boolean $includeExcludedUrl
	 */
	private function appendModelToUrlInfoArray($website, $forLang, $model, &$urlInfoArray, $includeExcludedUrl, $maxUrl)
	{
		$rqc = RequestContext::getInstance();
		$langs = ($forLang == 'all') ? $website->getI18nInfo()->getLangs() : array($forLang);

		$modelPriority = $this->getSitemapOption($website, $forLang, $model->getName(), self::SITEMAP_PRIORITY);
		$modelChangefreq = $this->getSitemapOption($website, $forLang, $model->getName(), self::SITEMAP_CHANGEFREQ);
		foreach ($langs as $lang)
		{
			try 
			{
				$rqc->beginI18nWork($lang);
				
				$resultArray = $this->buildDocumentIds($website, $model, $maxUrl);
				foreach ($resultArray as $result)
				{
					$doc = DocumentHelper::getDocumentInstance($result);
					// Cas particulier des pages de détail :
					// Si une page a un tag 'functional_*', alors elle ne doit pas figurer dans
					// la liste des URLs du site car la page n'est qu'un "support" pour un autre
					// document, et c'est ce document qui a une URL.
					if ($this->isAllowedInSitemap($doc))
					{
						$url = LinkHelper::getDocumentUrl($doc, $lang);
						$isUrlExcluded = $this->isUrlExcludedInWebsite($url, $website, $forLang);
						if (!$isUrlExcluded || $includeExcludedUrl)
						{
							$urlInfo = new referencing_UrlInfo();
							$urlInfo->loc = $url;
							$urlInfo->lastmod = date('c', date_Calendar::getInstance($doc->getModificationDate())->getTimestamp());
							$urlInfo->isExcluded = $isUrlExcluded;
							$urlPriority = $this->getSitemapOptionForUrl($website, $forLang, $url, self::SITEMAP_PRIORITY);
							$urlChangefreq = $this->getSitemapOptionForUrl($website, $forLang, $url, self::SITEMAP_CHANGEFREQ);
							$urlInfo->priority = ($urlPriority === null) ? $modelPriority : $urlPriority;
							$urlInfo->changefreq = ($urlChangefreq === null) ? $modelChangefreq : $urlChangefreq;
							$urlInfoArray[] = $urlInfo;
						}
					}
					$doc = null;
				}
			
				$rqc->endI18nWork();
			}
			catch (Exception $e)
			{
				$rqc->endI18nWork();
				throw $e;
			}
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
	 * @param String $forLang
	 * @return Boolean
	 */
	private function isUrlExcludedInWebsite($urlToCheck, $website, $forLang)
	{
		$websiteId = $website->getId();
		if (! isset($this->excludedUrl[$websiteId]))
		{
			$this->excludedUrl[$websiteId] = explode("\n", $this->getSitemapExcludedUrlList($website, $forLang));
			foreach ($this->excludedUrl[$websiteId] as &$url)
			{
				$url = trim($url);
			}
		}
		return in_array($urlToCheck, $this->excludedUrl[$websiteId]);
	}
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @param String $forLang
	 * @return void
	 */
	public function saveSitemapContents($website, $forLang)
	{
		$siteMaps = array();
		$siteMapIndex = 0;
		
		$rqc = RequestContext::getInstance();
		$langs = ($forLang == 'all') ? $website->getI18nInfo()->getLangs() : array($forLang);
		foreach ($langs as $lang)
		{
			try 
			{
				$rqc->beginI18nWork($lang);
		
				$docIds = $this->getDocumentIdsForWebsite($website);
				$docIdCount = count($docIds);
				
				for ($i = 0; $i < $docIdCount; $i += self::MAX_URL_PER_FILE)
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
						$processHandle = popen("php $batch " . WEBEDIT_HOME . " " . $lang . " " . $website->getId() . " " . $tmpFile . " " . implode(" ", $chunk), "r");
						while (($string = fread($processHandle, 1000)) != false)
						{
							Framework::info($string);
						}
						pclose($processHandle);
					}
					file_put_contents($tmpFile, "\n</urlset>", FILE_APPEND);
					
					$this->compressFile($tmpFile, $this->getSitemapPathForWebsite($website, $forLang, $siteMapIndex));
					unlink($tmpFile);
					
					$siteMaps[] = array('url' => $this->getSitemapUrl($website, $forLang, $siteMapIndex), 'lastMod' => date('c', time()));
					$siteMapIndex++;
				}
		
				$rqc->endI18nWork();
			}
			catch (Exception $e)
			{
				$rqc->endI18nWork();
				throw $e;
			}
		}
		
		// Generate the sitemap index.
		$templateIndex = TemplateLoader::getInstance()->setPackageName('modules_referencing')->setMimeContentType('xml')->load('sitemap-index');
		
		$siteMapsCount = count($siteMaps);
		Framework::info(__METHOD__ . " $siteMapsCount generated url sitemap files.");
		
		$siteMapIndex = 0;
		for($i = 0; $i < $siteMapsCount; $i += self::MAX_URL_PER_INDEX_FILE)
		{
			Framework::info(__METHOD__ . ' Generating index file $siteMapIndex');
			$subSiteMaps = array_slice($siteMaps, $i, self::MAX_URL_PER_INDEX_FILE);
			$templateIndex->setAttribute('sitemaps', $subSiteMaps);
			$filePath = $this->getSitemapIndexPathForWebsite($website, $forLang, $siteMapIndex);
			// Content is gzipped (URL rewriting rule in .htaccess points to sitemap.xml.gz).
			Framework::info(__METHOD__ . " Saving index file $filePath");
			f_util_FileUtils::write($filePath, gzencode($templateIndex->execute()), f_util_FileUtils::OVERRIDE);
			$siteMapIndex ++;
		}
	}
	
	/**
	 * @param unknown_type $source
	 * @param unknown_type $dest
	 */
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
	
	/**
	 * @param unknown_type $filerc
	 * @param website_persistentdoculent_website $website
	 * @param String $forLang
	 * @param Integer[] $docIds
	 */
	public function updateTempSiteMap($filerc, $website, $forLang, $docIds)
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
					$isUrlExcluded = $this->isUrlExcludedInWebsite($url, $website, $forLang);
					if (! $isUrlExcluded)
					{
						$modelPriority = $this->getSitemapOption($website, $forLang, $model->getName(), self::SITEMAP_PRIORITY);
						$modelChangefreq = $this->getSitemapOption($website, $forLang, $model->getName(), self::SITEMAP_CHANGEFREQ);
						
						$urlInfo = new referencing_UrlInfo();
						$urlInfo->loc = $url;
						$urlInfo->lastmod = date('c', date_Calendar::getInstance($doc->getModificationDate())->getTimestamp());
						$urlInfo->isExcluded = $isUrlExcluded;
						$urlPriority = $this->getSitemapOptionForUrl($website, $forLang, $url, self::SITEMAP_PRIORITY);
						$urlChangefreq = $this->getSitemapOptionForUrl($website, $forLang, $url, self::SITEMAP_CHANGEFREQ);
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
	 * @param String $forLang
	 * @param Integer $siteMapIndex
	 * @return String gzipped sitemap contents | null if the map does not exists
	 */
	public function getSitemapContents($website, $forLang, $siteMapIndex = 0)
	{
		$path = $this->getSitemapPathForWebsite($website, $forLang, $siteMapIndex);
		if (Framework::isInfoEnabled()) { Framework::info(__METHOD__ . "($path)"); }
		if (file_exists($path))
		{
			return f_util_FileUtils::read($path);
		}
		return null;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param String $forLang
	 * @param Integer $siteMapIndex
	 * @return String gzipped sitemap contents | null if the map does not exists
	 */
	public function getSitemapIndexContents($website, $forLang, $siteMapIndex = 0)
	{
		$path = $this->getSitemapIndexPathForWebsite($website, $forLang, $siteMapIndex);
		if (Framework::isInfoEnabled()) { Framework::info(__METHOD__ . "($path)"); }
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
	
	private $sitemapOptionsPerWebsiteAndLang;
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @param String $forLang
	 * @param String $modelName
	 * @param String $optionName
	 * @return String
	 */
	public function getSitemapOption($website, $forLang, $modelName, $optionName)
	{
		$websiteId = $website->getId();
		if (!isset($this->sitemapOptionsPerWebsiteAndLang[$websiteId.'/'.$forLang]))
		{
			$infoDoc = $this->getInfoDocumentForWebsiteAndLang($website, $forLang);
			if ($infoDoc !== null && is_string($infoDoc->getSitemapOptions()))
			{
				$this->sitemapOptionsPerWebsiteAndLang[$websiteId.'/'.$forLang] = unserialize($infoDoc->getSitemapOptions());
			}
		}
		$sitemapOptions = &$this->sitemapOptionsPerWebsiteAndLang[$websiteId.'/'.$forLang];
		
		if (is_array($sitemapOptions) && isset($sitemapOptions[$modelName]) && isset($sitemapOptions[$modelName][$optionName]))
		{
			return $sitemapOptions[$modelName][$optionName];
		}
		return null;
	}
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @param String $forLang
	 * @param String $modelName
	 * @param String $optionName
	 * @param String $value
	 * @param Boolean $save
	 */
	public function setSitemapOption($website, $forLang, $modelName, $optionName, $value, $save = true)
	{
		$infoDoc = $this->getInfoDocumentForWebsiteAndLang($website, $forLang, true);
		$websiteId = $website->getId();
		if (!isset($this->sitemapOptionsPerWebsiteAndLang[$websiteId.'/'.$forLang]))
		{
			if ($infoDoc !== null)
			{
				$this->sitemapOptionsPerWebsiteAndLang[$websiteId.'/'.$forLang] = unserialize($infoDoc->getSitemapOptions());
			}
		}
		$sitemapOptions = &$this->sitemapOptionsPerWebsiteAndLang[$websiteId.'/'.$forLang];
		
		if (!is_array($sitemapOptions))
		{
			$sitemapOptions = array();
		}
		if (!isset($sitemapOptions[$modelName]))
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
	
	/**
	 * @var Array<String, Array>
	 */
	private $sitemapUrlInfoPerWebsiteAndLang;
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @param String $forLang
	 * @param String $modelName
	 * @param String $optionName
	 * @return String
	 */
	public function getSitemapOptionForUrl($website, $forLang, $url, $optionName)
	{
		$websiteId = $website->getId();
		if (! isset($this->sitemapUrlInfoPerWebsite[$websiteId.'/'.$forLang]))
		{
			$infoDoc = $this->getInfoDocumentForWebsiteAndLang($website, $forLang);
			if (! is_null($infoDoc))
			{
				$this->sitemapUrlInfoPerWebsiteAndLang[$websiteId.'/'.$forLang] = unserialize($infoDoc->getSitemapUrlInfo());
			}
		}
		$sitemapUrlInfo = &$this->sitemapUrlInfoPerWebsiteAndLang[$websiteId.'/'.$forLang];
		
		if (is_array($sitemapUrlInfo) && isset($sitemapUrlInfo[$url]) && isset($sitemapUrlInfo[$url][$optionName]))
		{
			return $sitemapUrlInfo[$url][$optionName];
		}
		return null;
	}
	
	/**
	 * @param website_persistentdoculent_website $website
	 * @param String $forLang
	 * @param String $modelName
	 * @param String $optionName
	 * @param String $value
	 * @param Boolean $save
	 */
	public function setSitemapOptionForUrl($website, $forLang, $url, $optionName, $value, $save = true)
	{
		$infoDoc = $this->getInfoDocumentForWebsiteAndLang($website, $forLang, true);
		$websiteId = $website->getId();
		if (! isset($this->sitemapUrlInfoPerWebsiteAndLang[$websiteId.'/'.$forLang]))
		{
			if (! is_null($infoDoc))
			{
				$this->sitemapUrlInfoPerWebsiteAndLang[$websiteId.'/'.$forLang] = unserialize($infoDoc->getSitemapUrlInfo());
			}
		}
		$sitemapUrlInfo = &$this->sitemapUrlInfoPerWebsiteAndLang[$websiteId.'/'.$forLang];
		
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
			try 
			{
				Controller::getInstance();
			} 
			catch (ControllerException $e)
			{
				Controller::newInstance("controller_ChangeController");
			}
			
			$this->generateAllSitemapFiles();
		}
	}
	
	/**
	 * @return void
	 */
	private function generateAllSitemapFiles()
	{
		$websites = website_WebsiteService::getInstance()->createQuery()->find();
		foreach ($websites as $website)
		{
			$langs = ($website->getLocalizebypath()) ? array('all') : $website->getI18nInfo()->getLangs();
			foreach ($langs as $lang)
			{
				$this->saveSitemapContents($website, $lang);
			}	
		}
	}
}