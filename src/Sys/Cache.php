<?php declare(strict_types=1);

/* ==== LICENCE AGREEMENT =====================================================
 *
 * © Cédric Ducarre (20/05/2010)
 * 
 * wlib is a set of tools aiming to help in PHP web developpement.
 * 
 * This software is governed by the CeCILL license under French law and
 * abiding by the rules of distribution of free software. You can use, 
 * modify and/or redistribute the software under the terms of the CeCILL
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 * 
 * As a counterpart to the access to the source code and rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty and the software's author, the holder of the
 * economic rights, and the successive licensors have only limited
 * liability.
 * 
 * In this respect, the user's attention is drawn to the risks associated
 * with loading, using, modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean that it is complicated to manipulate, and that also
 * therefore means that it is reserved for developers and experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or 
 * data to be ensured and, more generally, to use and operate it in the 
 * same conditions as regards security.
 * 
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 * 
 * ========================================================================== */

namespace wlib\Application\Sys;

use wlib\Http\Server\Request;
use wlib\Http\Server\Response;

/**
 * Http server cache handler.
 *
 * @author Cédric Ducarre
 */
class Cache
{
	/**
	 * Folder path where save cache files.
	 * @var string
	 */
	private $sStoragePath = '';

	/**
	 * File name of cached response (must be unique).
	 * @var string
	 */
	private $sFilename = '';

	/**
	 * Cache retention time (in seconds).
	 * @var integer
	 */
	private $iDelay = 0;
	
	/**
	 * Constructor.
	 *
	 * @param string $sStoragePath Init. cache storage path.
	 * @param integer $iDelay Init. cache retention time (in seconds)
	 * @return void
	 */
	public function __construct(string $sStoragePath = '', int $iDelay = 0)
	{
		$this->setStoragePath($sStoragePath);
		$this->setDelay($iDelay);
	}

	/**
	 * Set storage path.
	 *
	 * @param string $sPath Address of cache folder.
	 * @return self
	 */
	public function setStoragePath($sPath): self
	{
		$this->sStoragePath = rtrim($sPath, '/') . '/';
		return $this;
	}

	/**
	 * Set the cache file name of current response.
	 *
	 * @param string $sFilename Name of cache file.
	 * @return self
	 */
	public function setFilename($sFilename): self
	{
		$this->sFilename = basename($sFilename);
		return $this;
	}

	/**
	 * Set cache file lifetime.
	 *
	 * @param integer $iSeconds Time in seconds.
	 * @return self
	 */
	public function setDelay(int $iSeconds): self
	{
		$this->iDelay = $iSeconds;
		return $this;
	}

	/**
	 * Get storage path.
	 *
	 * @return string
	 */
	public function getStoragePath(): string
	{
		return $this->sStoragePath;
	}

	/**
	 * Get cache file name.
	 *
	 * @return string
	 */
	public function getFilename(): string
	{
		return $this->sFilename;
	}

	/**
	 * Get cache lifetime
	 *
	 * @return integer
	 */
	public function getDelay(): int
	{
		return $this->iDelay;
	}

	/**
	 * Cache the given HTTP response.
	 *
	 * @param \wlib\Http\Server\Response $oResponse Current response.
	 * @param integer $iMode Cache file permissions.
	 */
	public function save(Response $oResponse, int $iMode = 0644)
	{
		if ($this->iDelay <= 0)
			return;

		$sFileOutTmp = $this->sStoragePath . $this->sFilename . microtime(true) .'.tmp';
		$hTempFile = fopen($sFileOutTmp, 'w');

		if (!$hTempFile)
			throw new \RuntimeException('Unable to create cache response file.');

		$iNow = time();

		$oResponse->setLastModified($iNow);
		$oResponse->setExpires($iNow + $this->iDelay);
		$oResponse->setHeader('Cache-Control', 'public, s-maxage=' . (int)$this->iDelay, true);

		fwrite($hTempFile, $oResponse->getStatusString() ."\r\n");
		fwrite($hTempFile, $oResponse->getHeadersString() ."\r\n");

		$hBody = $oResponse->getBody();
		rewind($hBody);

		stream_copy_to_stream($hBody, $hTempFile);
		fclose($hTempFile);

		$sFileOut = $this->sStoragePath . $this->sFilename .'.'. microtime(true);
		rename($sFileOutTmp, $sFileOut);
		chmod($sFileOut, $iMode);
	}

	/**
	 * Load a response from cache.
	 *
	 * ## Behavior
	 *
	 * Return cache file if it exists and is not expired. Outdated files are purged.
	 *
	 * @return Response|false
	 */
	public function read(): Response|false
	{
		if ($this->iDelay <= 0)
			return false;

		$oResponse = false;
		$aFilesCached = glob($this->sStoragePath . $this->sFilename .'.*');

		if (is_array($aFilesCached) && sizeof($aFilesCached) > 0)
		{
			$file = end($aFilesCached);

			if ($file == '..')
				return false;

			$parts = explode('.', $file);

			// If not outdated
			if (time() - (int)$parts[count($parts) - 2] <= (int)$this->iDelay)
			{
				$aCachedResponse = $this->readCacheFile($file);

				$oResponse = new Response(new Request());
				$oResponse
					->setHeaders($aCachedResponse['headers'])
					->setBody($aCachedResponse['body']);
			}
			else unlink($file);
		}

		return $oResponse;
	}

	/**
	 * Read a cache file.
	 *
	 * ## Returning array
	 *
	 * Two keys :
	 *
	 * - 'headers', `array` : array of response headers,
	 * - 'body', `string` : response body.
	 *
	 * @param string $sFilename Address of cache file.
	 * @return array
	 */
	private function readCacheFile(string $sFilename): array
	{
		$sContent = file_get_contents($sFilename);
		$aContent = explode("\r\n", $sContent, 3);

		$aResult = [
			'headers' => [], 'body' => (isset($aContent[2]) ? $aContent[2] : '')
		];

		$aHeaders = explode("\n", $aContent[1]);

		foreach ($aHeaders as $sHeader)
		{
			$aHeader = explode(':', $sHeader, 2);
			$aResult['headers'][$aHeader[0]] = (isset($aHeader[1]) ? trim($aHeader[1]) : '');
		}

		return $aResult;
	}
}