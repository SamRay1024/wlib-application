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

use wlib\Http\Server\HttpException;
use wlib\Http\Server\Request;
use wlib\Http\Server\Response;

/**
 * Simple HTTP router.
 *
 * @author Cédric Ducarre
 */
class Router
{
	/**
	 * Current HTTP request.
	 * @var Request
	 */
	private $oRequest = null;

	/**
	 * Namespace for controllers.
	 * @var string
	 */
	private $sControllersNamespace = '';

	/**
	 * Base URI ("/" by default).
	 * @var string
	 */
	private $sBaseUri = '';

	/**
	 * Constructor.
	 *
	 * @param Request $oRequest Current HTTP request.
	 * @param string $sControllersNamespace Namespace of controllers where the router will dispatch requests.
	 * @param string $sBaseUri Base URI to ignore in dispatcher.
	 */
	public function __construct(Request $oRequest, string $sControllersNamespace, string $sBaseUri = '')
	{
		$this->oRequest = $oRequest;
		$this->sControllersNamespace = rtrim($sControllersNamespace, '\\');
		$this->sBaseUri = '/'.ltrim($sBaseUri, '/');
	}

	/**
	 * Dispatch the current request.
	 */
	public function dispatch()
	{
		$aRoute = $this->resolve();

		if (!$aRoute['controller_fqcn'])
			throw new HttpException(
				Response::HTTP_NOT_FOUND,
				'No suitable controller found for "'
				. ($aRoute['requested_path'] ?? '/') .'".'
			);

		return $aRoute;
	}

	/**
	 * Resolve current request.
	 * 
	 * Based on the request URI, the resolver search a corresponding controller.
	 * The resolver returns an array of one element :
	 * 
	 * - Key is the resolved path, ex: "hello/world/"
	 * - Value is the FQCN controller, ex: "App\Controllers\Hello\WorldController"
	 * 
	 * If no controller can be found, value is `false`.
	 * 
	 * @return array
	 */
	private function resolve(): array
	{
		$sRequestUri = $this->oRequest->getRequestUri();

		if ($this->sBaseUri != '' && strpos($sRequestUri, $this->sBaseUri) !== false)
			$sRequestUri = substr($sRequestUri, strlen($this->sBaseUri));

		$sRequestPath = (($iQueryPos = strpos($sRequestUri, '?')) !== false
			? substr($sRequestUri, 0, $iQueryPos)
			: $sRequestUri
		);

		$aRequestPath = explode('/', trim($sRequestPath, '/'));
		$sClassPath = $this->sControllersNamespace;

		if (!$sRequestPath)
		{
			$sClassPath = $this->sControllersNamespace .'\\IndexController';

			return [
				'requested_path' => $sRequestPath,
				'controller_fqcn' => (class_exists($sClassPath) ? $sClassPath : false),
				'args' => []
			];
		}

		$sRoutedPath = '';
		$sClassname = false;

		foreach ($aRequestPath as $sPart)
		{
			$sPart = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $sPart)));
			$sRoutedPath .= strtolower($sPart . '/');

			if (class_exists($sClassPath .'\\'. $sPart .'Controller'))
			{
				$sClassname = $sClassPath .'\\'. $sPart .'Controller';
				break;
			}
			elseif (class_exists('\\wlib\\Application\\Controllers\\'. $sPart .'Controller'))
			{
				$sClassname = '\\wlib\\Application\\Controllers\\'. $sPart .'Controller';
				break;
			}
			elseif ($sClassPath != $this->sControllersNamespace && class_exists($sClassPath .'\\IndexController'))
			{
				$sClassname = $sClassPath . '\\IndexController';
				break;
			}

			$sClassPath .= '\\'. $sPart;
		}

		return [
			'routed_path' => $sRoutedPath,
			'requested_path' => trim($sRequestPath, '/'),
			'controller_fqcn' => $sClassname,
			'args' => explode('/', trim(str_replace(trim($sRoutedPath, '/'), '', $sRequestPath), '/'))
		];
	}
}