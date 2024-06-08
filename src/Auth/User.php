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

/**
 * Authenticated user.
 *
 * @author Cédric Ducarre
 */
class User implements IUser
{
	/**
	 * User attributes.
	 * @var array
	 */
	private array $aAttributes = [];

	/**
	 * Construc an user instance.
	 * 
	 * Native attributes are :
	 * 
	 * - 'id',
	 * - 'key',
	 * - 'username',
	 * - 'password',
	 * - 'can_login'.
	 * 
	 * @param array $aAttributes Array of current user attributes.
	 */
	public function __construct(array $aAttributes)
	{
		$this->aAttributes = $aAttributes;
	}

	/**
	 * Get user ID.
	 * 
	 * @return int|string
	 */
	public function getId(): int|string
	{
		return $this->aAttributes['id'];
	}

	/**
	 * Get user key.
	 * 
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->aAttributes['key'];
	}

	/**
	 * Get user name.
	 * 
	 * @return string
	 */
	public function getUsername(): string
	{
		return $this->aAttributes['username'];
	}

	/**
	 * Get user password.
	 * 
	 * @return string
	 */
	public function getPassword(): string
	{
		return $this->aAttributes['password'];
	}

	/**
	 * Checks if user has right to login.
	 * 
	 * @return bool
	 */
	public function canLogin(): bool
	{
		return ($this->aAttributes['can_login'] == true);
	}

	/**
	 * Get custom attributes.
	 * 
	 * @param string $sName Attribute name.
	 */
	public function __get(string $sName)
	{
		return $this->aAttributes[$sName];
	}

	/**
	 * Set custom attributes.
	 * 
	 * @param string $sName Attribute name.
	 * @param mixed $mValue Attribute value.
	 */
	public function __set(string $sName, mixed $mValue)
	{
		$this->aAttributes[$sName] = $mValue;
	}

	/**
	 * Checks if attribute exists.
	 * 
	 * @param string $sName Attribute name.
	 * @return bool
	 */
	public function __isset(string $sName): bool
	{
		return isset($this->aAttributes[$sName]);
	}

	/**
	 * Remove an attribute.
	 * 
	 * @param string $sName Attribute name.
	 */
	public function __unset(string $sName)
	{
		unset($this->aAttributes[$sName]);
	}
}