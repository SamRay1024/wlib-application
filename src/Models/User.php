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

namespace wlib\Application\Models;

use RuntimeException;
use UnexpectedValueException;
use wlib\Application\Exceptions\UnexpectedFieldValueException;
use wlib\Db\Table;

/**
 * User model.
 * 
 * This is the model used by the authentication service.
 * 
 * @author Cédric Ducarre
 */
class User extends Table
{
	const TABLE_NAME = 'users';
	const COL_ID_NAME = 'id';
	const COL_CREATED_AT_NAME = 'created_at';
	const COL_UPDATED_AT_NAME = 'updated_at';
	const COL_DELETED_AT_NAME = 'deleted_at';

	/**
	 * Run the create table SQL statement.
	 *
	 * @return void
	 */
	public function createTable()
	{
		$this->oDb->execute(
			'CREATE TABLE IF NOT EXISTS users (
				id INTEGER PRIMARY KEY,
				name VARCHAR NOT NULL,
				email VARCHAR NOT NULL UNIQUE,
				password VARCHAR,
				token VARCHAR,
				can_login INTEGER,
				created_at DATETIME,
				updated_at DATETIME,
				verified_at DATETIME,
				deleted_at DATETIME
			);'
		);
	}

	public function filterFields(array $aFields, $id = 0): array
	{
		$aFiltered = filter_var_array($aFields, [
			'name'			=> FILTER_DEFAULT,
			'email'			=> FILTER_VALIDATE_EMAIL,
			'password'		=> FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'token'			=> FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'can_login'		=> FILTER_VALIDATE_BOOL,
			'verified_at'	=> $this->getFilter('validate_date')
		], false);
		
		if (!count($aFiltered))
			throw new UnexpectedValueException(
				static::class .' : no data provided. Nothing to do.'
			);

		if ($aFiltered === false)
			throw new RuntimeException(
				static::class.' : an error occured while filtering fields.'
				.' Please check your filters.'
			);

		if (in_array(false, $aFiltered, true))
			throw new UnexpectedValueException(sprintf(
				static::class .' : unexpected value(s) for field(s) "%s".',
				implode(', ', array_keys($aFiltered, false, true))
			), 400);

		if (isset($aFiltered['email']) && $this->exists('email', $aFiltered['email'], $id))
			throw new UnexpectedFieldValueException('url', sprintf(
				static::class .' : Email "%s" already added.',
				$aFiltered['email']
			), 409);

		foreach (['name', 'password', 'token'] as $sFieldName)
			if (isset($aFiltered[$sFieldName]))
				$aFiltered[$sFieldName] = strip_tags($aFiltered[$sFieldName]);

		if (isset($aFiltered['password']))
			$aFiltered['password'] = password_hash($aFiltered['password'], PASSWORD_BCRYPT);

		return $aFiltered;
	}

	/**
	 * Check if an account is already active (= verified) from email.
	 * 
	 * @param string $sEmail Email account.
	 * @return boolean
	 */
	public function isAccountActive(string $sEmail): bool
	{
		return 0 < $this->oDb->query()->select(self::COL_ID_NAME)
			->from(self::TABLE_NAME)
			->where('email = :email AND verified_at IS NOT NULL')
			->setParameter('email', $sEmail)
			->run()
			->fetchColumn();
	}
}