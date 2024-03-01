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

namespace wlib\Application\Auth;

use wlib\Http\Server\Session;

/**
 * Guard for web authentication.
 * 
 * The guard handles users web access : login, logout, register and renew password.
 * 
 * @author Cédric Ducarre
 */
class WebGuard
{
	const SESS_USER_KEY = '_user';

	/**
	 * @var \wlib\Http\Server\Session
	 */
	private Session $session;

	/**
	 * @var \wlib\Application\Auth\IUserProvider
	 */
	private IUserProvider $users;

	/**
	 * Login URL.
	 * @var string
	 */
	private string $sLoginUrl = '';

	/**
	 * Logout URL.
	 * @var string
	 */
	private string $sLogoutUrl = '';

	/**
	 * Register URL.
	 * @var string
	 */
	private string $sRegisterUrl = '';

	/**
	 * Renew password URL.
	 * @var string
	 */
	private string $sRenewUrl = '';

	public function __construct(Session $session, IUserProvider $users)
	{
		$this->session = $session;
		$this->users = $users;

		$this->sLoginUrl	= config('app.guard.web.login_url', '/auth/login');
		$this->sLogoutUrl	= config('app.guard.web.logout_url', '/auth/logout');
		$this->sRegisterUrl	= config('app.guard.web.register_url', '/auth/register');
		$this->sRenewUrl	= config('app.guard.web.renew_url', '/auth/renew');

		if (!$this->session->isStarted())
			$this->session->start();
	}
	
	/**
	 * Log in an user.
	 *
	 * @param string $sUsername User identifier.
	 * @param string $sPassword User password.
	 * @return IUser
	 */
	public function login(string $sUsername, string $sPassword): IUser
	{
		/** @var IUser */
		$user = $this->users->getByUsername($sUsername);

		if (
			is_null($user)
			|| !($user->getPassword() == $sPassword)
			|| !$user->canLogin()
		)
			throw new AuthenticateException('Access denied, please check your credentials.');

		unset($user->password);

		$this->session->set(self::SESS_USER_KEY, $user);

		return $user;
	}

	public function logout()
	{
		unsession(self::SESS_USER_KEY);
		return true;
	}

	public function isLoggedIn()
	{
		return $this->session->has(self::SESS_USER_KEY);
	}

	public function getCurrentUser()
	{
		return $this->session->get(self::SESS_USER_KEY);
	}

	public function register()
	{

	}

	public function renewPassword()
	{

	}

	public function getLoginUrl(string $sRedirectTo = ''): string
	{
		return $this->sLoginUrl.($sRedirectTo ? '?redirect_to='.urlencode($sRedirectTo) : '');
	}

	public function getLogoutUrl(): string
	{
		return $this->sLogoutUrl;
	}

	public function getRegisterUrl(): string
	{
		return $this->sRegisterUrl;
	}

	public function getRenewUrl(): string
	{
		return $this->sRenewUrl;
	}
}