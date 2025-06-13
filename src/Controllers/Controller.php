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

namespace wlib\Application\Controllers;

use RuntimeException;
use wlib\Application\Auth\AuthenticateException;
use wlib\Application\Auth\AuthProviderInterface;
use wlib\Application\Auth\UserInterface;
use wlib\Application\Sys\Cache;
use wlib\Application\Sys\Kernel;
use wlib\Db\Db;
use wlib\Http\Server\HttpException;
use wlib\Http\Server\Request;
use wlib\Http\Server\Response;
use wlib\Http\Server\Session;

/**
 * Base controller.
 * 
 * @author Cédric Ducarre
 */
abstract class Controller
{
	/**
	 * Application instance.
	 * @var \wlib\Application\Sys\Kernel
	 */
	protected Kernel $app;

	/**
	 * Current HTTP request.
	 * @var \wlib\Http\Server\Request
	 */
	protected Request $request;

	/**
	 * Current HTTP response.
	 * @var \wlib\Http\Server\Response
	 */
	protected Response $response;

	/**
	 * Session handler.
	 * @var \wlib\Http\Server\Session
	 */
	protected Session $session;

	/**
	 * Authentication provider.
	 * @var \wlib\Application\Auth\AuthProviderInterface
	 */
	protected ?AuthProviderInterface $auth;

	/**
	 * Authenticated user.
	 * @var \wlib\Application\Auth\UserInterface
	 */
	protected ?UserInterface $user;

	/**
	 * HTTP cache handler.
	 * @var \wlib\Appplication\Cache
	 */
	protected Cache $cache;

	/**
	 * Handler for default database (if set in config).
	 * @var \wlib\Db\Db
	 */
	protected Db $db;

	/**
	 * Route arguments (this is what follows the URI that led to the controller).
	 * @var array
	 */
	protected array $aArgs = [];

	/**
	 * Controller unique string.
	 * @var string
	 */
	protected string $sUid;

	/**
	 * Controller boot.
	 * 
	 * @param \wlib\Application\Sys\Kernel $app
	 */
	public function __construct(Kernel $app)
	{
		$this->app = $app;

		$this->request = $this->app['http.request'];
		$this->response = $this->app['http.response'];
		$this->session = $this->app['http.session'];
		
		if ($this->app->has('db.default'))
			$this->db = $this->app['db.default'];

		$this->aArgs = $this->app['http.route']['args'];

		$this->initialize();
		$this->authenticate();
		$this->checkAccessRights();
		$this->makeUniqueId();
		$this->checkFlooding();
		$this->initCache();
		$this->run();
	}

	/**
	 * Initialize the controller.
	 * 
	 * Called before `start()`, see it as a constructor.
	 */
	public function initialize() {}

	/**
	 * Start 
	 *
	 * This is the only method than have to be implemented in child controllers.
	 */
	abstract public function start();

	/**
	 * Run the controller.
	 * 
	 * Execute the `start()` controller method, excepts if there is a valid cached
	 * previous response.
	 */
	protected function run()
	{
		if (!$this->readCache())
		{
			ob_start();

			$this->start();

			if (!$this->response->hasBody())
				$this->response->setBody(ob_get_clean());
			else
				$this->response->replace(ob_get_clean() . $this->response->getBodyString());

			$this->saveCache();
		}
	}

	/**
	 * Get the response.
	 *
	 * @return \wlib\Http\Server\Response
	 */	
	public function getResponse(): Response
	{
		return $this->response;
	}

	/* ==== AUTHENTICATION ================================================== */

	/**
	 * Define the authentication provider for the controller.
	 *
	 * You have to return the provider key as registered in the app container
	 * (DiBox).
	 * 
	 * By default, "auth.public" provider provide a default public user for
	 * controllers which doesn't have to protect access.
	 * 
	 * @return string|array
	 */
	protected function authentification()
	{
		return 'auth.public';
	}

	/**
	 * Define access rule(s) to controller.
	 *
	 * Overwrite this method in your controllers to use this capability.
	 *
	 * @return boolean `false`to forbide access to controller.
	 */
	protected function allow(): bool
	{
		return true;
	}

	/**
	 * Authenticate user.
	 */
	protected function authenticate()
	{
		$mAuthentification = $this->authentification();

		if (is_string($mAuthentification))
			$this->auth = $this->app->get($mAuthentification);
		
		elseif (is_array($mAuthentification))
		{
			$this->auth = $this->app->get(key($mAuthentification), current($mAuthentification));
		}

		if (is_null($this->auth))
			throw new RuntimeException(
				'Invalid authentification provider : '
				.print_r($mAuthentification)
				.'.'
			);

		try
		{
			$this->auth->authenticate($this->request);
			$this->user = $this->auth->getUser();

			if (!$this->user)
				throw new AuthenticateException('User not found');
		}
		catch (AuthenticateException $e)
		{
			throw new HttpException($e->getCode(), $e->getMessage(), $e->getHeaders());
		}
	}

	/**
	 * Check access rights to the controller.
	 * 
	 * Two methods are involved. If one of those return `false`, execution is halted
	 * with a 403 response :
	 * 
	 * - User::canLogin()
	 * - Controller::allow()
	 */
	protected function checkAccessRights()
	{
		if (!$this->user || !$this->user->canLogin() || !$this->allow())
			$this->haltForbidden(
				'Access denied ('. $this->request->getIp() .')'
			);
	}

	/* ==== FLOODING ======================================================== */

	/**
	 * Define a time limit between two calls to controller.
	 *
	 * Overwrite this method in your controllers to use this capability.
	 *
	 * @return int Time in milliseconds (0 = unlimited).
	 */
	protected function limit(): int
	{
		return 0;
	}

	/**
	 * Check if the controller access limit has been exceeded.
	 */
	protected function checkFlooding()
	{
		$iMilliseconds = abs($this->limit());

		if ($iMilliseconds <= 0)
			return;

		if (!session_id())
			session_start();

		$sSessionKey = 'wlib.http.throttle'
			.'.'. str_replace('.', '-', $this->request->getIP())
			.'.'. $this->sUid
		;
		
		$iLastHit = session($sSessionKey);
		session([$sSessionKey => intval(microtime(true) * 1000)]);

		if ((intval(microtime(true) * 1000) - $iLastHit) < $iMilliseconds)
			$this->haltTooManyRequests(
				'You have to wait '. $iMilliseconds .' milliseconds between two requests.',
				['Retry-After: '. (int) (round($iMilliseconds / 1000) ?: 1)]
			);
	}

	/* ==== CACHE =========================================================== */

	/**
	 * Define cache lifetime of current HTTP response.
	 *
	 * Overwrite this method in your controllers to use this capability.
	 *
	 * @return int Time in seconds (0 = no cache).
	 */
	protected function cache()
	{
		return 0;
	}

	/**
	 * Init cache instance if controller defines a response lifetime.
	 *
	 * @see self::cache()
	 */
	private function initCache()
	{
		if (($iDelay = $this->cache()))
		{
			$this->cache = $this->app['http.cache'];
			$this->cache
				->setDelay($iDelay)
				->setStoragePath(config('app.cache_path'))
				->setFileName($this->sUid .'.cache');
		}
	}

	/**
	 * Get the cached response if available.
	 *
	 * If a valid cached response exists, it replaces the current response.
	 *
	 * @return bool
	 */
	private function readCache(): bool
	{
		if (isset($this->cache))
		{
			$response = $this->cache->read();

			if ($response)
			{
				$this->response = $response;
				return true;
			}
		}

		return false;
	}

	/**
	 * Save response to cache if enabled.
	 */
	private function saveCache()
	{
		if (isset($this->cache) && $this->cache->getDelay())
		{
			$this->cache->save($this->response);
		}
	}

	/* ==== UTILS =========================================================== */
	
	/**
	 * Make a unique ID string for controller.
	 */
	protected function makeUniqueId()
	{
		$sRequestUri = trim($this->request->getPathInfo(), '/');
		
		$this->sUid = ($sRequestUri ? str_replace('/', '-', $sRequestUri) : 'index');
	}
	
	/**
	 * Get the controller unique ID string.
	 * 
	 * @return string
	 */
	public function getUid(): string
	{
		return $this->sUid;
	}
	
	/**
	 * Get a route argument.
	 *
	 * @param  mixed $iIndex Argument index.
	 * @param  mixed $sDefault Default value.
	 * @return string
	 */
	protected function arg(int $iIndex, string $sDefault = ''): string
	{
		return arrayValue($this->aArgs, $iIndex, $sDefault);
	}
	
	/**
	 * Get all route arguments.
	 * 
	 * @return array
	 */
	protected function args(): array
	{
		return $this->aArgs;
	}
	
	/* ==== REQUEST HELPERS ================================================= */

	/**
	 * Get the requested HTTP method.
	 * 
	 * @param boolean $bReal Get the real HTTP method.
	 * @return string|null
	 */
	protected function method(bool $bReal = false): ?string
	{
		return ($bReal
			? $this->request->getOriginalMethod()
			: $this->request->getMethod()
		);
	}
	
	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isGet(): bool { return $this->request->isGet(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isPost(): bool { return $this->request->isPost(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isPut(): bool { return $this->request->isPut(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isPatch(): bool { return $this->request->isPatch(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isDelete(): bool { return $this->request->isDelete(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isOptions(): bool { return $this->request->isOptions(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isHead(): bool { return $this->request->isHead(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isFormData(): bool { return $this->request->isFormData(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isAjax(): bool { return $this->request->isAjax(); }

	/**
	 * @see \wlib\Http\Server\Request
	 * @return bool
	 */
	protected function isJson(): bool { return $this->request->isJson(); }

	/**
	 * Get $_GET value.
	 * 
	 * @param string|int $mKey Element key of null for getting all.
	 * @param mixed $mDefault Default value.
	 * @return mixed
	 */
	protected function param(string|int $mKey = null, mixed $mDefault = null): mixed
	{
		return $this->request->get($mKey, $mDefault);
	}

	/**
	 * Check if a $_GET value exists.
	 *
	 * @param string|int $mKey Key to check.
	 * @return boolean
	 */
	protected function hasParam(string|int $mKey)
	{
		return $this->request->hasGet($mKey);
	}

	/**
	 * Returns the raw parameters of current HTTP request.
	 *
	 * @return string
	 */
	protected function rawParam(): string
	{
		return $this->request->getQueryString();
	}

	/**
	 * Get $_POST value.
	 * 
	 * @param string|int $mKey Element key of null for getting all.
	 * @param mixed $mDefault Default value.
	 * @return mixed
	 */
	protected function data(string|int $mKey = null, mixed $mDefault = null): mixed
	{
		return $this->request->post($mKey, $mDefault);
	}

	/**
	 * Check if a $_POST value exists.
	 *
	 * @param string|int $mKey Key to check.
	 * @return boolean
	 */
	protected function hasData(string|int $mKey)
	{
		return $this->request->hasPost($mKey);
	}

	/**
	 * Returns the raw body of the current HTTP request.
	 *
	 * @return string
	 */
	protected function rawData(): string
	{
		return $this->request->getRawInput();
	}

	/**
	 * Get the path URI which leds to the controller.
	 * 
	 * @return string
	 */
	protected function pathUri()
	{
		return $this->app['http.route']['routed_path'];
	}
	
	/**
	 * Get a CSRF protection token.
	 *
	 * Don't forget to clean the token when your form handling is over.
	 * 
	 * @return string
	 */
	protected function getFormToken(): string
	{
		return $this->session->getToken($this->request->getRequestUri());
	}

	/**
	 * Check CSRF protection token.
	 * 
	 * @param string $sTokenName Token identifier.
	 * @param string $sFieldName Field name in witch token value has been put.
	 * @throw HttpException if token no set or invalid.
	 */
	protected function checkFormToken(string $sFieldName = '_token')
	{
		$sTokenName = $this->request->getRequestUri();

		if (
			!$this->hasData($sFieldName)
			|| !$this->session->isValidToken($sTokenName, $this->data($sFieldName))
		)
			throw new HttpException(
				Response::HTTP_METHOD_NOT_ALLOWED,
				__('Invalid form, please try again.')
			);

		$this->session->removeToken($sTokenName);
	}

	/**
	 * Halt execution with "400 Bad Request" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltBadRequest(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_BAD_REQUEST,
			$sMessage ?? 'Bad Request',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "401 Unauthorized" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltUnauthorized(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_UNAUTHORIZED,
			$sMessage ?? 'Unauthorized',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "403 Forbidden" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltForbidden(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_FORBIDDEN,
			$sMessage ?? 'Forbidden',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "404 Not Found" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltNotFound(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_NOT_FOUND,
			$sMessage ?? 'Not Found',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "406 Not Acceptable" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltNotAcceptable(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_NOT_ACCEPTABLE,
			$sMessage ?? 'Not Acceptable',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "409 Conflict" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltConflict(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_CONFLICT,
			$sMessage ?? 'Conflict',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "417 Expectation Failed" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltExpectationFailed(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_EXPECTATION_FAILED,
			$sMessage ?? 'Expectation Failed',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "429 Too Many Requests" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltTooManyRequests(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_TOO_MANY_REQUESTS,
			$sMessage ?? 'Too Many Requests',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "500 Internal Server Error" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltInternalServerError(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_INTERNAL_SERVER_ERROR,
			$sMessage ?? 'Internal Server Error',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "501 Not Implemented" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltNotImplemented(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_NOT_IMPLEMENTED,
			$sMessage ?? 'Not Implemented',
			$aHeaders
		);
	}

	/**
	 * Halt execution with "503 Service Unavailable" status.
	 *
	 * @param string $sMessage Error message.
	 * @param array $aHeaders Additionals HTTP headers.
	 * @throws HttpException
	 */
	public function haltServiceUnavailable(string $sMessage = null, array $aHeaders = [])
	{
		throw new HttpException(
			Response::HTTP_SERVICE_UNAVAILABLE,
			$sMessage ?? 'Service Unavailable',
			$aHeaders
		);
	}

	/**
	 * Redirect and exit.
	 *
	 * @param string $sURL Redirect URL.
	 * @param int $iStatusCode HTTP status code.
	 */
	public function redirect(string $sURL, int $iStatusCode = Response::HTTP_FOUND)
	{
		$this->response->redirect($sURL, $iStatusCode);
		exit();
	}
	
	/**
	 * Redirect with 301 Moved Permanently status code and exit.
	 *
	 * @param string $sURL Redirect URL.
	 */
	public function redirectPermanent(string $sURL)
	{
		$this->response->redirect($sURL, Response::HTTP_MOVED_PERMANENTLY);
		exit();
	}

	/**
	 * Redirect with 303 See Other status code and exit.
	 *
	 * @param string $sURL Redirect URL.
	 */
	public function redirectAfterPost(string $sURL)
	{
		$this->response->redirect($sURL, Response::HTTP_SEE_OTHER);
		exit();
	}
}