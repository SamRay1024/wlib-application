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

use wlib\Http\Server\Request;

/**
 * HTTP Basic authentication.
 *
 * @author Cédric Ducarre
 */
class BasicAuthProvider extends AuthProvider
{
	/**
	 * Authenticate user.
	 * 
	 * @param \wlib\Http\Server\Request $request
	 */
	public function authenticate(Request $request)
	{
		try
		{
			$sBasicRegex = '`Basic (.+)`';
			if (preg_match(
					$sBasicRegex,
					$request->getHeader('Authorization', ''),
					$matches
				) !== 1
			)
				throw new \Exception(
					'Missing "Authorization" header or wrong syntax ("Basic ...").'
				);

			$hash = base64_decode($matches[1]);
			$hash = explode($hash, ':', 2);

			$usr = $hash[0];
			$pwd = arrayValue($hash, 1, '');

			$user = $this->users->getByUsername($usr);

			if ($user === false || $user->getPassword() !== $pwd)
				throw new \Exception('Unkown user');

			$this->user = $user;
		}
		catch (\Exception $ex)
		{
			throw new AuthenticateException(
				$ex->getMessage(),
				['WWW-Authenticate' => 'Basic realm="'.$this->sRealm.'"']
			);
		}
	}
}