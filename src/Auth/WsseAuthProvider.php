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
use wlib\Http\Server\Request;

/**
 * WSSE Authentication.
 *
 * @link http://www.xml.com/pub/a/2003/12/17/dive.html
 * @author Cédric Ducarre
 */
class WsseAuthProvider extends AuthProvider
{
	/**
	 * Token username.
	 * @var string
	 */
	private string $sUsername = '';

	/**
	 * Token digest.
	 * @var string
	 */
	private string $sDigest = '';

	/**
	 * Token nonce.
	 * @var string
	 */
	private string $sNonce = '';

	/**
	 * Token timestamp.
	 * @var string
	 */
	private string $sCreated = '';

	/**
	 * Nonces folder path.
	 * @var string
	 */
	private string $sNoncesPath = '';

	/**
	 * @param \wlib\Http\Server\Request $request HTTP request.
	 * @param \wlib\Application\Auth\IUserProvider $provider User provider.
	 * @param string $sNoncesPath Path where nonces cache files will be saved.
	 */
	public function __construct(Request $request, IUserProvider $provider, string $sNoncesPath)
	{
		parent::__construct($request, $provider);
		
		$this->sNoncesPath = rtrim($sNoncesPath, '/');

		if (!is_dir($this->sNoncesPath))
			throw new LogicException(
				'Nonces directory "'. $this->sNoncesPath .'" not found. '
				.'Please review application configuration or create it.'
			);
	}

	/**
	 * Authenticate user.
	 *
	 * @return bool
	 * @throws AuthenticateException
	 */
	public function authenticate()
	{
		try
		{
			$this->validateRequest();
			$this->validateToken();
		}
		catch (\Exception $e)
		{
			throw new AuthenticateException(
				'The WSSE authentication failed : '.$e->getMessage(),
				['WWW-Authenticate' => 'WSSE realm="'.$this->sRealm.'"']
			);
		}
	}

	/**
	 * Validate the HTTP request's compliance with WSSE.
	 *
	 * @throws \Exception
	 */
	private function validateRequest()
	{
		if ($this->request->getHeader('Authorization') === null)
			throw new \Exception('"Authorization" header not found');

		if ($this->request->getHeader('Authorization') !== 'WSSE profile="UsernameToken"')
			throw new \Exception('"Authorization" header must be \'WSSE profile="UsernameToken"\'');

		if ($this->request->getHeader('X-WSSE') === null)
			throw new \Exception('"X-WSSE" header not found');

		$sWSSERegex = '`UsernameToken Username="([^"]+)", PasswordDigest="([^"]+)", Nonce="([^"]+)", Created="([^"]+)"`';
		if (preg_match($sWSSERegex, $this->request->getHeader('X-WSSE'), $matches) !== 1)
			throw new \Exception('"X-WSSE" syntax error');

		$this->sUsername = $matches[1];
		$this->sDigest = $matches[2];
		$this->sNonce = $matches[3];
		$this->sCreated = $matches[4];
	}

	/**
	 * Validate the WSSE token.
	 *
	 * @throws \Exception
	 */
	private function validateToken()
	{
		$user = $this->users->getByUsername($this->sUsername);

		if ($user === false)
			throw new \Exception('unkown user');

		if (time() - strtotime($this->sCreated) > 300)
			throw new \Exception('timestamp too old (+300 s)');

		$sNonceCache = $this->sNoncesPath .'/'. $this->sNonce;

		if (file_exists($sNonceCache) && file_get_contents($sNonceCache) + 300 > time())
			throw new \Exception('nonce already used');

		file_put_contents($sNonceCache, time());

		// Validation digest
		$sDigest = base64_encode(
			sha1(base64_decode($this->sNonce).$this->sCreated.$user->password, true)
		);

		if ($sDigest !== $this->sDigest)
			throw new \Exception('invalid token');

		// Si on arrive ici, utilisateur validé
		$this->user = $user;
	}
}