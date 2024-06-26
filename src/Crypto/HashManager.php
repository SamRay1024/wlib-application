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

use LogicException;

/**
 * Hash manager to make and check password.
 * 
 * @author Cédric Ducarre
 */
class HashManager implements HashDriverInterface
{
	const ALGO_BCRYPT = 'bcrypt';
	const ALGO_PLAINTEXT = 'plaintext';

	/**
	 * The current hashing driver.
	 * @var HashDriverInterface
	 */
	private HashDriverInterface $driver;
	
	/**
	 * Initialize manager with an algorithm.
	 *
	 * @param string $sAlgo One of the self::ALGO_* constant.
	 * @param mixed $aOptions Algorithm options. See drivers to learn more.
	 */
	public function __construct(string $sAlgo = self::ALGO_BCRYPT, array $aOptions = [])
	{
		switch ($sAlgo)
		{
			case 'bcrypt':
				$this->driver = new BcryptDriver($aOptions);
				break;
			
			case 'plaintext':
				$this->driver = new PlaintextDriver($aOptions);
				break;

			default:
				throw new LogicException(sprintf(
					'Unable to find a suitable driver for "%s" hashing algorithm.',
					$sAlgo
				));
		}
	}

	/**
	 * Get info about the given hashed value.
	 *
	 * @see password_get_info()
	 * @param string $sHashedValue Hashed value to analyse.
	 * @return array{algo: int, algoName: string, options: array}
	 */
	public function info(string $sHashedValue): array
	{
		return $this->driver->info($sHashedValue);
	}

	/**
	 * Hash the given value.
	 * 
	 * @param string $sPlainValue Value to hash.
	 * @param array $aOptions Options according to the current driver.
	 * @return string|false
	 */
	public function hash(string $sPlainValue, array $aOptions = []): string|false
	{
		return $this->driver->hash($sPlainValue, $aOptions);
	}

	/**
	 * Check if the given plain value corresponds to the given hashed value.
	 *
	 * @param string $sPlainValue Plain value.
	 * @param string $sHashedValue Hashed value.
	 * @param array $aOptions Options according to the current driver.
	 * @return bool
	 */
	public function check(string $sPlainValue, string $sHashedValue, array $aOptions = []): bool
	{
		return $this->driver->check($sPlainValue, $sHashedValue, $aOptions);
	}
 }