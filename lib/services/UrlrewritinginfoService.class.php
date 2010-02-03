<?php
class referencing_UrlrewritinginfoService extends f_persistentdocument_DocumentService
{
	/**
	 * @var referencing_UrlrewritinginfoService
	 */
	private static $instance;

	/**
	 * @return referencing_UrlrewritinginfoService
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
	 * @return referencing_persistentdocument_urlrewritinginfo
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_referencing/urlrewritinginfo');
	}

	/**
	 * Create a query based on 'modules_referencing/urlrewritinginfo' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_referencing/urlrewritinginfo');
	}

	/**
	 * @param String $package
	 * @return referencing_persistentdocument_urlrewritinginfo
	 */
	public function getByPackage($package)
	{
		return $this->createQuery()->add(Restrictions::eq('package', $package))->findUnique();
	}
	
	/**
	 * @param referencing_persistentdocument_urlrewritinginfo $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		if (!$document->getLabel())
		{
			$document->setLabel($document->getPackage());
		}
	}
	
	/**
	 * @param referencing_persistentdocument_urlrewritinginfo $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId = null)
	{
		// Compile URL rewriting.
		$parser = website_urlrewriting_RulesParser::getInstance();
		$parser->compile(true);
		
		// Clear caches.
		Framework::debug(__METHOD__.' is new');
		$package = $document->getPackage();
		
		$ruleParser = website_urlrewriting_RulesParser::getInstance();
		$definitionFilePath = $ruleParser->getDefinitionFilePathByPackage($package);
		if (!is_null($definitionFilePath))
		{
			$oldRulesAsXml = f_util_FileUtils::read($definitionFilePath);
		}
		else 
		{
			$oldRulesAsXml = '';
		}
		
		$newRulesAsXml = $document->getContent();
		$this->clearSimpleCache($oldRulesAsXml, $newRulesAsXml, $package);	
	}
	
	/**
	 * @param referencing_persistentdocument_urlrewritinginfo $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId = null)
	{
		// Compile URL rewriting.
		$parser = website_urlrewriting_RulesParser::getInstance();
		$parser->compile(true);
		
		// Clear caches.
		if ($document->isPropertyModified('content'))
		{
			Framework::debug(__METHOD__.' property modified');
			$package = $document->getPackage();
			$oldRulesAsXml = $document->getContentOldValue(); 
			$newRulesAsXml = $document->getContent();
			$this->clearSimpleCache($oldRulesAsXml, $newRulesAsXml, $package);
		}		
	}
	
	/**
	 * @param String $oldRulesAsXml
	 * @param String $newRulesAsXml
	 */
	private function clearSimpleCache($oldRulesAsXml, $newRulesAsXml, $package)
	{
		$differences = $this->getDifferences($oldRulesAsXml, $newRulesAsXml);
		
		// We can't detect caches including redirections to actions, so clear all cache.
		if (count($differences['redirections']) > 0)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' clear all simple cache.');
			}
			f_SimpleCache::clear();			
		}
		// For models and tags we can clear only caches that depends on them. 
		else 
		{
			foreach ($differences['models'] as $modelName)
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' clear simple cache by model: '.$package.'/'.$modelName);
				}
				$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($package.'/'.$modelName);
				f_SimpleCache::clearCacheByModel($model);
			}
			foreach ($differences['tags'] as $tag)
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' clear simple cache by tag: '.$tag);
				}
				f_SimpleCache::clearCacheByTag($tag);
			}
		}
	}
		
	/**
	 * @param String $oldRulesAsXml
	 * @param String $newRulesAsXml
	 * @return Array<String, Array<String>>
	 */
	private function getDifferences($oldRulesAsXml, $newRulesAsXml)
	{
		// TODO: here we just look for the models, tags and redirections with a rule. 
		//A better implementation should really check if the rule is modified.
		
		$models = array();
		$tags = array();
		$redirections = array();
		
		$docOld = new DOMDocument();
		$docOld->loadXML($oldRulesAsXml);
		$ruleNodes = $docOld->getElementsByTagName('rule');
		foreach ($ruleNodes as $ruleNode)
		{
			if ($ruleNode->hasAttribute('documentModel'))
			{
				$models[] = $ruleNode->getAttribute('documentModel');
			}
			else if ($ruleNode->hasAttribute('pageTag'))
			{
				$tags[] = $ruleNode->getAttribute('pageTag');
			}
			else if ($ruleNode->hasAttribute('redirection'))
			{
				$redirections[] = $ruleNode->getAttribute('redirection');
			}
		}
		
		$docNew = new DOMDocument();
		$docNew->loadXML($newRulesAsXml);
		$ruleNodes = $docNew->getElementsByTagName('rule');
		foreach ($ruleNodes as $ruleNode)
		{
			if ($ruleNode->hasAttribute('documentModel'))
			{
				$models[] = $ruleNode->getAttribute('documentModel');
			}
			else if ($ruleNode->hasAttribute('pageTag'))
			{
				$tags[] = $ruleNode->getAttribute('pageTag');
			}
			else if ($ruleNode->hasAttribute('redirection'))
			{
				$redirections[] = $ruleNode->getAttribute('redirection');
			}
		}
		
		return array('models' => array_unique($models), 'tags' => array_unique($tags), 'redirections' => $redirections);
	}
}