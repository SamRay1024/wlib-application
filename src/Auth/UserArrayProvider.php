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

use wlib\Application\Crypto\IHashDriver;

/**
 * User provider by array.
 *
 * @author Cédric Ducarre
 */
class UserArrayProvider implements UserProviderInterface
{
	/**
	 * Users list.
	 * @var array
	 */
	private array $aUsersList = [];

	/**
	 * Users can't register or update password with this provider.
	 * 
	 * @return false
	 */
	public function writeable(): bool
	{
		return false;
	}

	/**
	 * Set the users list.
	 *
	 * ## List format
	 *
	 * Associative array of ['username' => 'key'].
	 *
	 * @param array $aUsersList Array of users.
	 */
	public function __construct(array $aUsersList = [])
	{
		$this->aUsersList = $aUsersList;
	}

	/**
	 * Alias of `getByKey()`.
	 *
	 * @param mixed $mId User ID.
	 * @return UserInterface|null
	 */
	public function getById(mixed $mId): ?UserInterface
	{
		return $this->getByUsername($mId);
	}

	/**
	 * Get an user from its key (API, ...).
	 *
	 * @param string $sKey Key value.
	 * @return UserInterface|null
	 */
	public function getByKey(string $sKey): ?UserInterface
	{
		return $this->getByUsername($sKey);
	}

	/**
	 * Get an user from ist username.
	 *
	 * @param string $sUsername Username value.
	 * @return UserInterface|null
	 */
	public function getByUsername(string $sUsername): ?UserInterface
	{
		if (!isset($this->aUsersList[$sUsername]))
			return null;

		return new User([
			'id'        => $sUsername,
			'key'       => $sUsername,
			'username'  => $sUsername,
			'password'  => access($this->aUsersList, $sUsername.'.password', ''),
			'can_login' => access($this->aUsersList, $sUsername.'.can_login', true),
		]);
	}
}