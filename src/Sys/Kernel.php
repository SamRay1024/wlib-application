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

use wlib\Db\Table;
use wlib\Di\DiBox;
use wlib\Http\Server\HttpException;
use wlib\Http\Server\Response;
use wlib\Tools\Hooks;

/**
 * Kernel is the heart of a wlib application.
 *
 * @author Cédric Ducarre
 */
class Kernel extends DiBox
{
	const VERSION = '0.0.1';
	
	/**
	 * Application base path.
	 *
	 * @var string
	 */
	protected $sBasePath = '';

	/**
	 * Kernel options.
	 *
	 * @var array
	 */
	protected array $aOptions = [];

	/**
	 * Whether the kernel has been bootstrapped.
	 *
	 * @var bool
	 */
	protected bool $bBootstrapped = false;

	public function __construct(string $sBasePath, array $aOptions = [])
	{
		$this->sBasePath = rtrim($sBasePath, '\/');
		$this->aOptions = $aOptions;
	}

	/**
	 * Bootstrap the kernel.
	 * Initializes configuration, autoloader, error reporting, timezone, session, and service providers.
	 *
	 * @return void
	 */
	public function bootstrap(): void
	{
		if ($this->bBootstrapped) {
			return;
		}

		// Define the bootstrap classes in execution order
		$aBootstrapClasses = [
			\wlib\Application\Sys\Bootstrap\EnvironmentBootstrap::class,
			\wlib\Application\Sys\Bootstrap\AutoloaderBootstrap::class,
			\wlib\Application\Sys\Bootstrap\ErrorHandlingBootstrap::class,
			\wlib\Application\Sys\Bootstrap\TimezoneBootstrap::class,
			\wlib\Application\Sys\Bootstrap\SessionBootstrap::class,
			\wlib\Application\Sys\Bootstrap\ServiceProvidersBootstrap::class,
		];

		// Execute each bootstrap
		foreach ($aBootstrapClasses as $sBootstrapClass) {
			try {
				$oBootstrap = new $sBootstrapClass();
				$oBootstrap->boot($this, $this->aOptions);
			} catch (\Throwable $oException) {
				// Log bootstrap error if logger is available
				if ($this->has('logger')) {
					/** @var \Psr\Log\LoggerInterface $oLogger */
					$oLogger = $this->get('logger');
					$oLogger->critical(
						'Bootstrap error: ' . $oException->getMessage(),
						[
							'bootstrap' => $sBootstrapClass,
							'exception' => $oException
						]
					);
				}

				throw $oException;
			}
		}

		$this->bBootstrapped = true;
	}

	/**
	 * Prepend base path to the given path if it's not absolute.
	 *
	 * A path is considered absolute if it starts with a directory separator.
	 * 
	 * @param string $sPath Path to prepend if necessary.
	 * @return string
	 */
	private function prependBasePath(string $sPath): string
	{
		return (!str_starts_with($sPath, '/')
			? $this->sBasePath.DIRECTORY_SEPARATOR.$sPath
			: $sPath
		);
	}

	/**
	 * Convert errors to ErrorException.
	 * 
	 * @param int $iLevel Error level.
	 * @param string $sMessage Error message.
	 * @param string $sFile File concerned.
	 * @param int $iLine Line concerned.
	 */
	public function handleError($iLevel, $sMessage, $sFile = '', $iLine = 0)
	{
		if (error_reporting() & $iLevel)
			throw new \ErrorException($sMessage, 0, $iLevel, $sFile, $iLine);
	}

	/**
	 * Run application.
	 */
	public function run()
	{
		$this->bootstrap();

		try
		{
			$this->bootServiceProviders();
			$response = $this->handleRequest();
		}
		catch (HttpException $ex)
		{
			$response = $this->handleRequestError($ex);
		}
		
		if ($this->has('sys.clockwork'))
			$this->get('sys.clockwork')->requestProcessed();
		
		$response->send();
	}
	
	/**
	 * Call `boot()` method of all service providers that defined one.
	 *
	 * The `boot()` method receives current application instance.
	 * 
	 * @return void
	 */
	private function bootServiceProviders()
	{
		foreach ($this->getProviders() as $provider)
		{
			if (method_exists($provider, 'boot'))
				call_user_func([$provider, 'boot'], $this);
		}
	}
	
	/**
	 * Handle the HTTP request.
	 *
	 * @return Response
	 */
	private function handleRequest(): Response
	{
		$aRoute = $this->get(
			'http.router',
			[
				config('app.ns_controllers'),
				config('app.base_uri', '/')
			]
		)
			->dispatch();

		Hooks::do('wlib.app.router.dispatch.after', ['route' => &$aRoute]);

		$this->bind('http.route', $aRoute);

		/* @var \wlib\Application\Controllers\Controller $controller */
		$controller = new $aRoute['controller_fqcn']($this);
		return $controller->getResponse();
	}
	
	/**
	 * Handle request error.
	 *
	 * @param mixed $ex Raised exception by controller.
	 * @return Response
	 */
	private function handleRequestError(\Exception $ex): Response
	{
		$request = $this->get('http.request');
		$response = $this->get('http.response');

		if ($request->wantsJson() || $request->isJson())
		{
			$response->json(
				['error' => [
					'code' => $ex->getStatusCode(),
					'title' => Response::getStatusMessage($ex->getStatusCode()),
					'detail' => $ex->getMessage()
				]],
				$ex->getStatusCode()
			);
		}
		else
		{
			$response->html(
				'<h1>'. $ex->getStatusCode() .' '. Response::getStatusMessage($ex->getStatusCode())
				.' !</h1><p>Sorry, something wen\'t wrong !</p>'
				.'<h2>Message thrown :</h2><p>'. $ex->getMessage() .'</p>',
				$ex->getStatusCode()
			);
		}

		return $response;
	}

	/**
	 * Get the current kernel version.
	 * 
	 * @return string
	 */
	public function getVersion(): string
	{
		return static::VERSION;
	}

	/**
	 * Get a database table instance.
	 * 
	 * @param string $sClassName Class name of the table to instanciate.
	 * @param string $sDatabaseName Name of the database as declared in app config file.
	 */
	public function getTable(string $sClassName, string $sDatabaseName = 'default'): Table
	{
		return $this->make($sClassName, [$this['db.'. $sDatabaseName]]);
	}
}