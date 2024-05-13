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

use wlib\Db\Db;

/**
 * User provider by database.
 *
 * @author Cédric Ducarre
 */
class UserDbProvider implements IUserProvider
{
	/**
	 * DB instance.
	 * @var \wlib\Db\Db
	 */
	private $db;

	/**
	 * @param \wlib\Db\Db Database instance.
	 */
	public function __construct(Db $db)
	{
		$this->db = $db;
	}

	/**
	 * Get an user from its ID.
	 *
	 * @param int $id User ID.
	 * @return IUser|null
	 */
	public function getById(mixed $id): ?IUser
	{
		return $this->fetchUser('id', (string)(int) $id);
	}

	/**
	 * Get an user from its key (API, ...).
	 *
	 * @param string $sKey Key.
	 * @return IUser|null
	 */
	public function getByKey(string $sKey): ?IUser
	{
		return $this->fetchUser('email', $sKey);
	}

	/**
	 * Get an user from its username.
	 *
	 * @param string $sUsername Username.
	 * @return IUser|null
	 */
	public function getByUsername(string $sUsername): ?IUser
	{
		return $this->fetchUser('email', $sUsername);
	}

	/**
	 * Fetch the user from the given database column.
	 * 
	 * @param string $sColumn Database column name.
	 * @param string $sValue Column value.
	 * @return IUser
	 */
	private function fetchUser(string $sColumn, string $sValue): ?IUser
	{
		$query = $this->db->query()
			->select(['id', 'name', 'email', 'password', 'can_login'])
			->from('users')
			->where($sColumn .' = :value')
			->setParameter(':value', $sValue, \PDO::PARAM_STR)
			->run();

		if (!$query)
			return null;

		if (!($row = $query->fetch()))
			return null;

		return new User([
			'id'		=> (int) $row->id,
			'key'		=> $row->email,
			'name'		=> $row->name,
			'username'	=> $row->email,
			'password'	=> $row->password,
			'can_login'	=> (bool) $row->can_login
		]);
	}
}