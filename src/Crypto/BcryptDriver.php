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

namespace wlib\Application\Crypto;

/**
 * Bcrypt hashing driver.
 * 
 * @author Cédric Ducarre
 */
class BcryptDriver extends AbstractHashDriver implements HashDriverInterface
{
	/**
	 * Default cost value.
	 * @var integer
	 */
	private int $iCost = 10;

	/**
	 * Initialize the Bcrypt driver.
	 * 
	 * @param array $aOptions {cost: int}
	 */
	public function __construct(array $aOptions = [])
	{
		$this->iCost = $aOptions['cost'] ?? $this->iCost;
	}

	/**
	 * Hash the given plain value with BCrypt algorithm.
	 * 
	 * @param string $sValue Plain value to hash.
	 * @param array $aOptions {cost: int}
	 * @return string|false
	 */
	public function hash(string $sValue, array $aOptions = []): string|false
	{
		return password_hash($sValue, PASSWORD_BCRYPT, [
			'cost' => $aOptions['cost'] ?? $this->iCost
		]);
	}
 }