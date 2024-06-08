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
 * Plaintext hashing driver.
 * 
 * ## WARNING
 * 
 * Please consider that this driver exists only for local installations and is
 * absolutely not a secure one.
 * 
 * Prefer use BcryptDriver on a real application that needs security.
 * 
 * @author Cédric Ducarre
 */
class PlaintextDriver extends AbstractHashDriver implements IHashDriver
{
	/**
	 * Return the given string without hashing.
	 * 
	 * @param string $sValue Plain value.
	 * @param array $null Not used in this driver.
	 * @return string|false
	 */
	public function hash(string $sValue, array $null = []): string|false
	{
		return $sValue;
	}

	/**
	 * Check if the given values a equals.
	 *
	 * @param string $sPlainValueOne First plain value.
	 * @param string $sPlainValueTwo Second plain value.
	 * @param array $null Not used in this driver.
	 * @return bool
	 */
	public function check(string $sPlainValueOne, string $sPlainValueTwo, array $null = []): bool
	{
		return (strcmp($sPlainValueOne, $sPlainValueTwo) === 0);
	}
 }