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

	public function __construct(string $sBasePath, array $aOptions = [])
	{
		$this->sBasePath = rtrim($sBasePath, '\/');

		$this->initConfig($aOptions);
		$this->initAutoloader();
		$this->initErrorReporting();
		$this->initTimeAndLocale();
		$this->initSession();
		$this->initServiceProviders();
	}
	
	/**
	 * Prepend base path to the given path if it's not absolute.
	 *
	 * A path is considered absolute if it starts with a directory separator.
	 * 
	 * @param string $sPath Path to prepend if necessary.
	 * @return void
	 */
	private function prependBasePath(string $sPath)
	{
		return (!str_starts_with($sPath, '/')
			? $this->sBasePath.DIRECTORY_SEPARATOR.$sPath
			: $sPath
		);	
	}

	/**
	 * Initialize options and load configurations files.
	 */
	private function initConfig(array $aOptions)
	{
		$aOptions = array_replace(
			[
				'sys.config_dir'	=> 'config',
				'sys.composer'		=> '',
				'sys.env_filename'	=> '.env'
			],
			$aOptions
		);

		$aOptions['sys.base_path'] = $this->sBasePath;
		$aOptions['sys.config_dir'] = $this->prependBasePath($aOptions['sys.config_dir']);

		foreach ($aOptions as $mKey => $mValue)
		{
			$this->bind($mKey, $mValue);
		}

		loadDotEnvFile($this->sBasePath . DIRECTORY_SEPARATOR . $this['sys.env_filename']);
		addConfigIncludePath($this['sys.config_dir']);
		
		$this->bind('sys.production', (bool) config('app.production', false));

		createDir(config('app.cache_path'), 0755);
		createDir(config('app.logs_path'), 0755);
	}
	
	/**
	 * Initialize errors handling and debugging stuff.
	 */
	private function initErrorReporting()
	{
		error_reporting(-1);
		ini_set('display_errors', !$this['sys.production']);
		set_error_handler([$this, 'handleError']);
	}

	/**
	 * Initialize autoloading.
	 */
	private function initAutoloader()
	{
		if (!is_a($this['sys.composer'], 'Composer\Autoload\ClassLoader'))
		{
			throw new \RuntimeException(
				'You must provide the kernel with the Composer instance'
				.' using the "sys.composer" option.'
			);
		}

		$aPsr4Folders = config('app.psr4_folders', null);

		if (is_array($aPsr4Folders))
		{
			foreach ($aPsr4Folders as $sNS => $sFolderPath)
			{
				$this['sys.composer']->addPsr4($sNS,$sFolderPath);
			}
		}
	}

	/**
	 * Initialize locale and translation tools.
	 */
	private function initTimeAndLocale()
	{
		date_default_timezone_set(config('app.timezone', 'Europe/Paris'));
	}

	/**
	 * Initialize session.
	 */
	private function initSession()
	{
		ini_set('session.use_cookie',		1);
		ini_set('session.use_only_cookie',	1);
		ini_set('session.use_trans_id',		0);
	}

	/**
	 * Initialize kernel service providers.
	 */
	private function initServiceProviders()
	{
		$this->register(DebugDiProvider::class);
		$this->register(SysDiProvider::class);
		$this->register(\wlib\Application\Templates\EngineDiProvider::class);
		$this->register(\wlib\Application\Auth\AuthDiProvider::class);
		$this->register(\wlib\Application\Mailer\MailerDiProvider::class);
		$this->register(\wlib\Application\Crypto\HashDiProvider::class);
		$this->register(\wlib\Application\L10n\L10nDiProvider::class);
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
		try
		{
			$this->bootServiceProviders();
			$response = $this->handleRequest();
		}
		catch (HttpException $ex)
		{
			$response = $this->handleRequestError($ex);
		}

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