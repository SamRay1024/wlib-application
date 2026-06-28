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

namespace wlib\Application\Sys\Bootstrap;

/**
 * Bootstrap for timezone and locale configuration.
 *
 * Sets the default timezone and locale for the application.
 *
 * @package wlib\Application\Sys\Bootstrap
 */
final class TimezoneBootstrap extends AbstractBootstrap
{
	/**
	 * {@inheritDoc}
	 */
	protected function initialize(): void
	{
		$aConfig = $this->getConfig();

		// Set default timezone
		$sTimezone = $aConfig['app.timezone'] ?? 'Europe/Paris';
		date_default_timezone_set($sTimezone);

		// Set locale if configured (default: fr_FR)
		$sLocale = $aConfig['app.locale'] ?? 'fr_FR';
		$this->setLocale($sLocale);
	}

	/**
	 * Set the application locale.
	 *
	 * @param string $sLocale Locale string (e.g., 'fr_FR.UTF-8', 'en_US.UTF-8').
	 * @return void
	 */
	private function setLocale(string $sLocale): void
	{
		// Try different locale categories
		$aCategories = [
			LC_ALL,
			LC_COLLATE,
			LC_CTYPE,
			LC_MONETARY,
			LC_NUMERIC,
			LC_TIME,
			LC_MESSAGES
		];

		foreach ($aCategories as $iCategory)
		{
			try { setlocale($iCategory, $sLocale); }
			catch (\ValueError $e)
			{
				// Locale not available, try without encoding
				$sLocaleWithoutEncoding = $this->removeEncodingFromLocale($sLocale);

				if ($sLocaleWithoutEncoding !== $sLocale)
				{
					try { setlocale($iCategory, $sLocaleWithoutEncoding); }
					catch (\ValueError $ex)
					{
						// Still not available, skip
						continue;
					}
				}
			}
		}
	}

	/**
	 * Remove encoding from locale string.
	 *
	 * @param string $sLocale Locale string.
	 * @return string
	 */
	private function removeEncodingFromLocale(string $sLocale): string
	{
		$aParts = explode('.', $sLocale);
		return $aParts[0] ?? $sLocale;
	}
}