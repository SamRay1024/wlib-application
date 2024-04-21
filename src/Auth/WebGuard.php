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

use LogicException;
use RuntimeException;
use UnexpectedValueException;
use wlib\Application\Kernel;
use wlib\Application\Models\User;
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
	 * @var \wlib\Application\Kernel
	 */
	private Kernel $app;

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
	 * Verifiy email address URL.
	 * @var string
	 */
	private string $sVerifyUrl = '';

	/**
	 * Forgot password URL.
	 * @var string
	 */
	private string $sForgotUrl = '';

	/**
	 * Renew password URL.
	 * @var string
	 */
	private string $sRenewUrl = '';

	/**
	 * Initialize the Guard.
	 * 
	 * @param \wlib\Application\Kernel $app Current application instance.
	 * @param \wlib\Http\Server\Session $session Current session.
	 * @param \wlib\Application\Auth\IUserProvider $users Users provider.
	 */
	public function __construct(Kernel $app, Session $session, IUserProvider $users)
	{
		$this->app = $app;
		$this->session = $session;
		$this->users = $users;

		$this->sLoginUrl	= config('app.guard.web.login_url', '/auth/login');
		$this->sLogoutUrl	= config('app.guard.web.logout_url', '/auth/logout');
		$this->sRegisterUrl	= config('app.guard.web.register_url', '/auth/register');
		$this->sVerifyUrl	= config('app.guard.web.verify_url', '/auth/verify');
		$this->sForgotUrl	= config('app.guard.web.forgot_url', '/auth/forgot');
		$this->sRenewUrl	= config('app.guard.web.renew_url', '/auth/renew');

		if (!$this->session->isStarted())
			$this->session->start();
	}
	
	/**
	 * Log in an user.
	 *
	 * @param string $sUsername User identifier.
	 * @param string $sPassword User password.
	 * @return \wlib\Application\Auth\IUser
	 */
	public function login(string $sUsername, string $sPassword): IUser
	{
		/** @var IUser */
		$user = $this->users->getByUsername($sUsername);

		if (
			is_null($user)
			|| !password_verify($sPassword, $user->getPassword())
			|| !$user->canLogin()
		)
			throw new AuthenticateException(
				__('Access denied, please check your credentials.')
			);

		unset($user->password);

		$this->session->set(self::SESS_USER_KEY, $user);

		return $user;
	}

	/**
	 * Log out the current user.
	 * 
	 * @return true
	 */
	public function logout()
	{
		unsession(self::SESS_USER_KEY);
		return true;
	}

	/**
	 * Check if a user is logged in.
	 * 
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		return $this->session->has(self::SESS_USER_KEY);
	}

	/**
	 * Get the current user.
	 * 
	 * @return \wlib\Application\Auth\IUser
	 */
	public function getCurrentUser()
	{
		return $this->session->get(self::SESS_USER_KEY);
	}

	/**
	 * Register a new user.
	 * 
	 * Registering process is as follow :
	 * 
	 * - Add user email in database awaiting validation,
	 * - Send en email with a back link to verify the email address.
	 * 
	 * @param string $sUserEmail Email user address.
	 */
	public function register(string $sUserEmail)
	{
		if (filter_var($sUserEmail, FILTER_VALIDATE_EMAIL) === false)
			throw new UnexpectedValueException(
				__('You have to provide an email address to register.')
			);

		$sToken = md5(uniqid());

		/** @var \wlib\Application\Models\User $dbuser */
		$dbuser = $this->app->getTable(User::class);
		
		if ($dbuser->isAccountActive($sUserEmail))
			throw new RuntimeException(
				__('This email address is already registered.')
			);

		$dbuser->save(
			['name' => '» new «', 'email' => $sUserEmail, 'token' => $sToken],
			$dbuser->findId('email', $sUserEmail)
		);

		// TODO : create an email template
		$mail = $this->app->get('mailer.mail');

		$mail->addAddress($sUserEmail);
		$mail->isHTML(true);
		$mail->Subject	= __('Confirm your registration');
		$mail->Body		= '<h1>Registration confirmation</h1>'
			.'<p>To finalize your account registration, please click on the following link :</p>'
			.'<p><a href="//'.config('app.base_url').$this->getVerifyUrl().'?vk='.urlencode($sToken).'">Confirm my email address</a></p>'
			.'<p>If you haven\'t asked for an account registration, you can simply ignore and delete this message.</p>'
		;
		// $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

		$mail->send();
	}

	/**
	 * Retreive user ID from the verify token.
	 * 
	 * @param string $sToken Token created registration step.
	 * @return integer|false
	 */
	public function getUserIdFromToken(string $sToken)
	{
		$dbuser = $this->app->getTable(User::class);
		return $dbuser->findId('token', $sToken);
	}

	/**
	 * Verify a registration.
	 * 
	 * - Clean registration token
	 * - Set the verification date
	 * - Authorize user to log in (can_login field)
	 * 
	 * @param int $iUserId User ID.
	 * @param string $sName Name of the user account.
	 * @param string $sPassword Password.
	 * @return bool
	 * @throw LogicException in case of wrong parameters values.
	 */
	public function verify(int $iUserId, string $sName, string $sPassword)
	{
		if (!$iUserId)
			throw new LogicException('User ID not provided.');

		if (trim($sName) == '')
			throw new LogicException('User name can\'t be empty.');

		if (trim($sPassword) == '')
			throw new LogicException('User password can\'t be empty.');

		$dbuser = $this->app->getTable(User::class);
		return (bool) $dbuser->save(
			[
				'name' => $sName,
				'password' => $sPassword,
				'token' => '',
				'can_login' => true,
				'verified_at' => 'NOW()'
			],
			$iUserId
		);
	}
	
	/**
	 * Start the password renewal procedure.
	 * 
	 * - Set a token in user account record,
	 * - Send an email with a back link to renew password.
	 *
	 * @param string $sUserEmail
	 * @return void
	 * @throws UnexpectedValueException if given user email is invalid.
	 */
	public function startforgotPassword(string $sUserEmail)
	{
		if (filter_var($sUserEmail, FILTER_VALIDATE_EMAIL) === false)
			throw new UnexpectedValueException(
				__('You have to provide an email address to renew your password.')
			);

		/** @var \wlib\Application\Models\User $dbuser */
		$dbuser = $this->app->getTable(User::class);

		if ($dbuser->isAccountActive($sUserEmail))
		{
			$sToken = md5(uniqid());
			$dbuser->save(
				['token' => $sToken],
				$dbuser->findId('email', $sUserEmail)
			);

			// TODO : create an email template
			$mail = $this->app->get('mailer.mail');

			$mail->addAddress($sUserEmail);
			$mail->isHTML(true);
			$mail->Subject	= __('Password renewal confirmation');
			$mail->Body		= '<h1>Password renewal confirmation</h1>'
			. '<p>To renew your password, please click on the following link :</p>'
			. '<p><a href="//' . config('app.base_url') . $this->getRenewUrl() .'?rk='. urlencode($sToken) .'">Renew my password</a></p>'
			. '<p>If you haven\'s ask for a password renewal, you can simply ignore and delete this message.</p>'
			;

			$mail->send();
		}
	}
	
	/**
	 * Save the new password for the given user ID.
	 *
	 * - Save the new password,
	 * - Clear the user token,
	 * - Send a confirmation email.
	 * 
	 * @param integer $iUserId User ID.
	 * @param string $sPassword New password.
	 * @return boolean
	 * @throws LogicException
	 */
	public function renewPassword(int $iUserId, string $sPassword): bool
	{
		if (!$iUserId)
			throw new LogicException('User ID not provided.');

		if (trim($sPassword) == '')
			throw new LogicException('User password can\'t be empty.');

		$dbuser = $this->app->getTable(User::class);
		$bUpdated = $dbuser->save(
			[
				'password' => $sPassword,
				'token' => '',
				'can_login' => true
			],
			$iUserId
		);

		if ($bUpdated)
		{
			// TODO : send mail
		}

		// TODO : add hook event

		return $bUpdated;
	}
	
	/**
	 * Get the login URL.
	 *
	 * @param string $sRedirectTo URL to redirect after successfull login.
	 * @return string
	 */
	public function getLoginUrl(string $sRedirectTo = ''): string
	{
		return $this->sLoginUrl.($sRedirectTo ? '?redirect_to='.urlencode($sRedirectTo) : '');
	}
	
	/**
	 * Get the logout URL.
	 *
	 * @return string
	 */
	public function getLogoutUrl(): string
	{
		return $this->sLogoutUrl;
	}
	
	/**
	 * Get the register URL.
	 *
	 * @return string
	 */
	public function getRegisterUrl(): string
	{
		return $this->sRegisterUrl;
	}

	/**
	 * Get the verify email URL.
	 *
	 * @return string
	 */
	public function getVerifyUrl(): string
	{
		return $this->sVerifyUrl;
	}
	
	/**
	 * Get the forgot password URL.
	 *
	 * @return string
	 */
	public function getForgotUrl(): string
	{
		return $this->sForgotUrl;
	}
	
	/**
	 * Get the password renewal URL.
	 *
	 * @return string
	 */
	public function getRenewUrl(): string
	{
		return $this->sRenewUrl;
	}
}