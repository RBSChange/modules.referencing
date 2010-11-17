<?php
class referencing_RedirectionService extends f_persistentdocument_DocumentService
{
	/**
	 * @var referencing_RedirectionService
	 */
	private static $instance;

	/**
	 * @return referencing_RedirectionService
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
	 * @return referencing_persistentdocument_redirection
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_referencing/redirection');
	}

	/**
	 * Create a query based on 'modules_referencing/redirection' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_referencing/redirection');
	}

	/**
	 * @param String $file
	 * @param website_persistentdocument_website $website
	 * @param ArrayObject $errorArray
	 * @return Integer
	 */
	public function importFile($file, $website, $errorArray)
	{
		try 
		{
			$handle = fopen($file, 'r');
			$lineNumber = 0;
			$redirectionArray = array();	
			while (($data = fgetcsv($handle, 2048, ';')) !== false)
			{
				$oldUrl = ''; $newUrl = ''; $lineNumber++;
	
				if (count($data) == 1 && $data[0] === '')
				{
						//Ligne vide
						$errorArray[] = $this->buildImportError($lineNumber, $oldUrl, $newUrl, 1003, true);
						continue;					
				} 
				else if (count($data) >= 2)
				{
					$oldUrl = trim($data[0]);
					$newUrl = trim($data[1]);
					if ($oldUrl === '' && $newUrl === '')
					{
						//Ligne vide
						$errorArray[] = $this->buildImportError($lineNumber, $oldUrl, $newUrl, 1003, true);
						continue;
					} 
					else if ($oldUrl === '')
					{
						//Ancienne Url absente
						$errorArray[] = $this->buildImportError($lineNumber, $oldUrl, $newUrl, 1004);
						continue;						
					}
					else if ($newUrl === '')
					{
						//Nouvelle Url absente
						$errorArray[] = $this->buildImportError($lineNumber, $oldUrl, $newUrl, 1006);
						continue;						
					}
					if ($oldUrl[0] != '/') {$oldUrl = '/' . $oldUrl;}
					
					if (strpos($oldUrl, ' ') !== false || 
						strlen($oldUrl) > 254)
					{
						//Ancienne Url invalide
						$errorArray[] = $this->buildImportError($lineNumber, $oldUrl, $newUrl, 1005);
						continue;						
					}
					
					if (strpos($newUrl, ' ') !== false || 
						strlen($newUrl) > 250 || strlen($newUrl) < 7 || 
						strpos($newUrl, 'http://') !== 0)
					{
						//Nouvelle Url invalide
						$errorArray[] = $this->buildImportError($lineNumber, $oldUrl, $newUrl, 1007);
						continue;						
					}
					
					if (isset($redirectionArray[$oldUrl]))
					{
						//Duplication d'url
						$errorArray[] = $this->buildImportError($lineNumber, $oldUrl, $newUrl, 1010);
						continue;							
					}
					
					$newUrlStatus = $this->getUrlStatus($newUrl);
					if ($newUrlStatus != 200)
					{
						$errorArray[] = $this->buildImportError($lineNumber, $oldUrl, $newUrl, $newUrlStatus, true);
					}
		
					$redirection = $this->getNewDocumentInstance();
					$redirection->setOldUrl($oldUrl);
					$redirection->setNewUrl($newUrl);
					$redirection->setWebsite($website);
					$redirectionArray[$oldUrl] = $redirection;
				}
				else
				{
					//la ligne ne contient pas le bon nombre d'entrée
					$errorArray[] = $this->buildImportError($lineNumber, '', '', 1008);
				}	
			}
			fclose($handle);
			$handle = null;
		}
		catch (Exception $e)
		{
			if ($handle != null) {fclose($handle);}
			$errorArray->exchangeArray(array());
			//Fichier ilisible
			$errorArray[] = $this->buildImportError(0, '', '', 1009);
			return 0;
		}
		
		if ($lineNumber == 0)
		{
			//Fichier vide
			$errorArray[] = $this->buildImportError(0, '', '', 1001);
			return 0;
		}
		else if (count($redirectionArray) == 0)
		{
			//Toutes les lignes du fichier sont invalide
			$errorArray->exchangeArray(array());
			$errorArray[] = $this->buildImportError(0, '', '', 1002);
			return 0;
		}
		
		try 
		{
			$this->importRedirections($redirectionArray, $website);
		}
		catch (Exception $e)
		{
			$errorArray->exchangeArray(array());
			//Erreur de sauvegarde le fichier n'as pas été importé
			$errorArray[] = $this->buildImportError(0, '', '', 1000);
			return 0;
		}
		
		return count($redirectionArray);
	}
	
	private function buildImportError($lineNumber, $oldUrl, $newUrl, $errorCode, $avertissement = false)
	{
		$localErrocKey = 'Code_' . $errorCode;
		$msg = f_Locale::translateUI('&modules.referencing.bo.importerrors.' . $localErrocKey .';');
		if ($msg == $localErrocKey)
		{
			$msg = f_Locale::translateUI('&modules.referencing.bo.importerrors.Unknown-Error;');
		}
		
		if ($avertissement)
		{
			$type = 'Alert';
		}
		else
		{
			$type = 'Error';
		}
		return array('line' => $lineNumber, 'oldurl' => $oldUrl, 'newurl' => $newUrl, 
			'type' => $type, 'errorcode' => $errorCode, 'errormsg' =>$msg);
	}
	
	/**
	 * @param referencing_persistentdocument_redirection[] $redirectionArray
	 * @param website_persistentdocument_website $website
	 */
	private function importRedirections($redirectionArray, $website)
	{
		try 
		{
			$this->tm->beginTransaction();
			$this->removeAllRedirections($website);
			foreach ($redirectionArray as $redirection) 
			{
				$redirection->save();
			}
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
			throw $e;
		}
	}

	/**
	 * Check Url Status :
	 * < 100 => CURL ERROR (6 - CURLE_COULDNT_RESOLVE_HOST, 28 - CURLE_OPERATION_TIMEDOUT ...)
	 * 	@see http://curl.haxx.se/libcurl/c/libcurl-errors.html
	 * 100 => Not HTTP/1.0 or HTTP/1.1 protocol
	 * 200 >= HTTP header status (200 - OK, 404 - not found, 301 - Premanently redirect ...)
	 *  @see http://www.w3.org/Protocols/HTTP/HTRESP.html
	 * @param string $url
	 * @return integer
	 */
	private function getUrlStatus($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($ch);
		$status = curl_errno($ch);
		curl_close($ch);
		if ($status === 0)
		{
			if (preg_match("/^HTTP\/1\.[1|0]\s(\d{3})/", $data, $matches))
			{
				$status = intval($matches[1]);
			}
			else
			{
				$status = 100;
			}
		}
		return $status;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return void
	 */
	private function removeAllRedirections($website)
	{
		foreach ($this->createQuery()->add(Restrictions::eq('website.id', $website->getId()))->setProjection(Projections::property('id'))->addOrder(Order::desc('document_id'))->find() as $result)
		{
			DocumentHelper::getDocumentInstance($result['id'])->delete();
		}
	}

	/**
	 * @return void
	 */
	public function writeRedirectionsFile()
	{
		referencing_ReferencingService::getInstance()->refreshHtaccess();
	}
	
	const MODE_HTACCESS = 1;
	const MODE_APACHE_CONF = 2;
	
	/**
	 * @return void
	 */
	public function generateRules($mode = self::MODE_HTACCESS)
	{
		// Write redirections file.
		$data = array();
		if ($mode == self::MODE_APACHE_CONF)
		{
			$data[] = 'RewriteEngine On';
		}
		$website = null;
		foreach ($this->createQuery()->addOrder(Order::asc('website'))->find() as $redirection)
		{
			$website = $redirection->getWebsite();
			$data[] = 'RewriteCond %{SERVER_NAME} ^(www\.)?' . $this->fixForRegExp($website->getDomain()).'$ [NC]';
			$oldUrl = $this->removeStartingSlash($redirection->getOldUrl());
			if ($mode == self::MODE_APACHE_CONF)
			{
				$oldUrl = '/'.$oldUrl;
			}
									
			$newUrl = $this->removeStartingSlash($redirection->getNewUrl());
			if (!preg_match('/^https?:.+/', $newUrl) )
			{
				$newUrl = 'http://' . $website->getDomain() . '/' . $newUrl;
			}
			
			// For pages with variables, we need:
			// * a specific condition to check the query string.
			// * a '?' in trhe rewrite rule after the new URL to remove the query string.
			if (strpos($oldUrl, '?') !== false)
			{
				list($oldUrl, $queryString) = explode('?', $oldUrl);
				$data[] = 'RewriteCond %{QUERY_STRING} ^'.$this->fixForRegExp($queryString).'$';
				$data[] = 'RewriteRule ^' . $this->fixForRegExp($oldUrl) . '$ ' . $newUrl . '? [L,R=301]';
			}
			else 
			{
				$data[] = 'RewriteRule ^' . $this->fixForRegExp($oldUrl) . '$ ' . $newUrl . ' [L,R=301]';
			}			
		}
		return join("\n", $data);
	}
	
	// protected methods
	
	/**
	 * @param referencing_persistentdocument_redirection $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		// Check if another redirection already exists with the same 'oldUrl' and 'website'.
		$existingRedirection = $this->createQuery()
			->add(Restrictions::eq('website.id', $document->getWebsite()->getId()))
			->add(Restrictions::eq('oldUrl', $document->getOldUrl()))
			->findUnique();
		if ( ! is_null($existingRedirection) && $document->getId() != $existingRedirection->getId() )
		{
			throw new ValidationException("Duplicate 'oldUrl' field in redirection in the same website.");
		}

		// Set the redirection label.
		$document->setLabel(f_util_StringUtils::shortenString('Redirection de ' . $document->getOldUrl() . ' vers ' . $document->getNewUrl()));
	}
	
	// private methods

	/**
	 * @param String $value
	 * @return String
	 */
	private function fixForRegExp($value)
	{
		return preg_quote($value);
	}
	
	/**
	 * Removes the first character if it is a '/'.
	 * @param String $url
	 * @return String
	 */
	private function removeStartingSlash($url)
	{
		if (substr($url, 0, 1) == '/')
		{
			return substr($url, 1);
		}
		return $url;
	}
	
	// Deprecated

	/**
	 * @deprecated with no replacement
	 */
	public function createApacheDirectory()
	{
		// moved in ApplyWebappPolicy
	}
}