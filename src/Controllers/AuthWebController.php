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

use Exception;
use RuntimeException;
use UnexpectedValueException;
use wlib\Application\Auth\AuthenticateException;
use wlib\Application\Auth\WebGuard;
use wlib\Http\Server\HttpException;
use wlib\Http\Server\Session;

/**
 * Web user identification controller proposal.
 * 
 * It's a controller which uses the WebGuard to identify users on your application.
 * 
 * You can use it as is or create your own.
 * 
 * @author Cédric Ducarre
 */
abstract class AuthWebController extends FrontController
{
	/**
	 * Guard of web authentication.
	 * @var \wlib\Application\Auth\WebGuard
	 */
	private WebGuard $guard;

	/**
	 * List of allowed routes for this controller.
	 * @var array
	 */
	protected array $aAllowedRoutes = [
		'login', 'logout', 'register', 'verify', 'forgot', 'renew'
	];
	
	/**
	 * Rate limiter to this controller.
	 *
	 * @return int
	 */
	public function limit(): int { return 200; }
	
	/**
	 * Controller construct.
	 */
	public function initialize()
	{
		parent::initialize();

		$this->guard = $this->app['guard.web'];
	}

	/**
	 * Startup.
	 */
	public function start()
	{
		$sAuthRoute = $this->arg(0);

		if (!in_array($sAuthRoute, $this->aAllowedRoutes) ||
			!method_exists($this, $sAuthRoute))
			$this->haltNotFound('"'. $sAuthRoute .'" route not found.');

		$this->session->start();
		$this->$sAuthRoute();
	}

	/**
	 * Render the given screen.
	 * 
	 * If you want to overwrite the default file template, you have to duplicate
	 * the file "resources/templates/auth.html.php" in your application.
	 * 
	 * The current screen will be `$screen` template variable.
	 * 
	 * @param string $sScreen Screen to render.
	 * @param array $aVars Variables passed to template.
	 */
	private function renderScreen(string $sScreen, array $aVars = [])
	{
		$aVars['screen'] = $sScreen;
		$aVars['stylesheets'] = $this->stylesheets();

		$this->response->html($this->render('auth', $aVars));	
	}
	
	/**
	 * Define the stylesheets links to use with the auth template file.
	 *
	 * Each link is an array of two items to define the <link> attributes :
	 * 
	 * - "href", mandatory
	 * - "media", optional, "screen" if not defined
	 * 
	 * @return array
	 */
	protected function stylesheets(): array
	{
		return [];
	}
	
	/**
	 * Login route.
	 */
	protected function login()
	{
		if ($this->isPost()
			&& $this->hasData('username')
			&& $this->hasData('password')
		) {
			try
			{
				$this->checkFormToken();
				
				$this->guard->login(
					$this->data('username'),
					$this->data('password')
				);

				$this->redirectAfterPost($this->param('redirect_to', '/'));
			}
			catch (HttpException|AuthenticateException $e)
			{
				$this->session->flash('error', 
					[$e->getMessage(), ['username' => $this->data('username')]],
					Session::FLASH_ERROR
				);

				$this->redirectAfterPost(
					$this->guard->getLoginUrl($this->param('redirect_to', '/'))
				);
			}
		}

		$aError = $this->session->flash('error');
		$aSuccess = $this->session->flash('success');

		$this->renderScreen('login', [
			'error'		=> access($aError, 'message', ''),
			'success'	=> access($aSuccess, 'message', ''),
			'username'	=> access($aError, 'data.username', ''),
			'token'		=> $this->getFormToken()
		]);
	}
	
	/**
	 * Logout route.
	 */
	protected function logout()
	{
		$this->guard->logout();

		$this->redirect(
			$this->param('redirect_to', $this->guard->getLoginUrl())
		);
	}
	
	/**
	 * Register route.
	 */
	public function register()
	{
		if ($this->isPost() && $this->hasData('email'))
		{
			try
			{
				$this->checkFormToken();
				$this->guard->register($this->data('email'));
				$this->redirectAfterPost($this->guard->getRegisterUrl().'?waiting');
			}
			catch (HttpException | UnexpectedValueException | RuntimeException $e)
			{
				$this->session->flash(
					'error',
					[$e->getMessage(), ['email' => $this->data('email')]],
					Session::FLASH_ERROR
				);
				$this->redirectAfterPost($this->guard->getRegisterUrl());
			}

			return;
		}

		$aError = $this->session->flash('error');

		$this->renderScreen(
			'register'. ($this->hasParam('waiting') ? '-waiting' : ''),
			[
				'error'		=> access($aError, 'message', ''),
				'token'		=> (!$this->hasParam('waiting') ? $this->getFormToken() : ''),
				'email'		=> (!$this->hasParam('waiting') ? access($aError, 'data.email', '') : '')
			]
		);
	}
	
	/**
	 * Verify email route.
	 */
	public function verify()
	{
		try
		{
			if (!$this->hasParam('k'))
				throw new RuntimeException();
		
			if ($this->isPost())
			{
				$this->checkFormToken();

				$bVerified = $this->guard->verify(
					$this->param('k'), $this->data('name'), $this->data('password')
				);

				if (!$bVerified)
					throw new RuntimeException(
						__('Verification failed : please renew your registration.')
					);

				$this->session->flash(
					'success',
					__('Your account has been verified. You can now log in !'),
					session::FLASH_SUCCESS
				);

				$this->redirectAfterPost($this->guard->getLoginUrl());
			}
		}
		catch (HttpException | UnexpectedValueException $e)
		{
			$this->session->flash(
				'error',
				[$e->getMessage(), ['name' => $this->data('name')]],
				Session::FLASH_ERROR
			);
			$this->redirectAfterPost($this->guard->getVerifyUrl($this->param('k')));
		}
		catch (Exception $e)
		{
			if ($e->getMessage())
				$this->session->flash('error', $e->getMessage(), Session::FLASH_ERROR);

			$this->redirectAfterPost($this->guard->getRegisterUrl());
		}

		$aError = $this->session->flash('error');

		$this->renderScreen('verify', [
			'screen'	=> 'verify',
			'error'		=> access($aError, 'message', ''),
			'token'		=> $this->getFormToken(),
			'name'		=> access($aError, 'data.name', '')
		]);
	}
	
	/**
	 * Forgot password route.
	 */
	public function forgot()
	{
		if ($this->isPost() && $this->hasData('email'))
		{
			try
			{
				$this->checkFormToken();
				$this->guard->startforgotPassword($this->data('email'));
				$this->redirectAfterPost($this->guard->getForgotUrl() .'?waiting');
			}
			catch (HttpException|UnexpectedValueException $e)
			{
				$this->session->flash('error', $e->getMessage(), Session::FLASH_ERROR);
				$this->redirectAfterPost($this->guard->getForgotUrl());
			}
		}

		$aError = $this->session->flash('error');

		$this->renderScreen(
			'forgot'. ($this->hasParam('waiting') ? '-waiting' : ''),
			[
				'error'		=> access($aError, 'message', ''),
				'token'		=> (!$this->hasParam('waiting') ? $this->getFormToken() : ''),
				'email'		=> (!$this->hasParam('waiting') ? access($aError, 'data.email', '') : '')
			]
		);
	}
	
	/**
	 * Renew password route.
	 */
	public function renew()
	{
		try
		{
			if (!$this->hasParam('k'))
				throw new RuntimeException();

			$iUserId = $this->guard->getUserIdFromToken($this->param('k'));

			if (!$iUserId)
				throw new RuntimeException(__('Password renewal failed : user not found.'));
		
			if ($this->isPost())
			{
				$this->checkFormToken();

				$bUpdated = $this->guard->renewPassword(
					$this->param('k'),
					$this->data('password')
				);

				if (!$bUpdated)
					throw new RuntimeException(
						__('Password renewal failed : please restart the procedure.')
					);

				$this->session->flash(
					'success',
					__('Your password has been updated. You should log in again.'),
					session::FLASH_SUCCESS
				);

				$this->redirectAfterPost($this->guard->getLoginUrl());
			}
		}
		catch (HttpException | UnexpectedValueException $e)
		{
			$this->session->flash(
				'error',
				[$e->getMessage(), ['name' => $this->data('name')]],
				Session::FLASH_ERROR
			);

			$this->redirectAfterPost(
				$this->guard->getRenewUrl($this->param('k'))
			);
		}
		catch (Exception $e)
		{
			if ($e->getMessage())
				$this->session->flash('error', $e->getMessage(), Session::FLASH_ERROR);

			$this->redirectAfterPost($this->guard->getForgotUrl());
		}

		$aError = $this->session->flash('error');

		$this->renderScreen('renew', [
			'error'		=> access($aError, 'message', ''),
			'token'		=> $this->getFormToken()
		]);
	}
}