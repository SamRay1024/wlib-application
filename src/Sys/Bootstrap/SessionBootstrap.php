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
 * Bootstrap for session configuration.
 *
 * Handles conditional session startup and session configuration.
 *
 * @package wlib\Application\Sys\Bootstrap
 */
final class SessionBootstrap extends AbstractBootstrap
{
	/**
	 * {@inheritDoc}
	 */
	protected function initialize(): void
	{
		$aConfig = $this->getConfig();

		// Check if session should be auto-started (default: true for backward compatibility)
		$bAutoStart = (bool) ($aConfig['app.session.auto_start'] ?? true);

		if (!$bAutoStart) {
			return;
		}

		// Configure session settings
		ini_set('session.use_cookie', 1);
		ini_set('session.use_only_cookie', 1);
		ini_set('session.use_trans_id', 0);

		// Additional session configuration from config
		$aSessionConfig = $aConfig['app.session'] ?? [];

		if (isset($aSessionConfig['cookie_path'])) {
			ini_set('session.cookie_path', $aSessionConfig['cookie_path']);
		}

		if (isset($aSessionConfig['cookie_domain'])) {
			ini_set('session.cookie_domain', $aSessionConfig['cookie_domain']);
		}

		if (isset($aSessionConfig['cookie_lifetime'])) {
			ini_set('session.cookie_lifetime', $aSessionConfig['cookie_lifetime']);
		}

		if (isset($aSessionConfig['cookie_secure'])) {
			ini_set('session.cookie_secure', (int) $aSessionConfig['cookie_secure']);
		}

		if (isset($aSessionConfig['cookie_httponly'])) {
			ini_set('session.cookie_httponly', (int) $aSessionConfig['cookie_httponly']);
		}

		if (isset($aSessionConfig['name'])) {
			ini_set('session.name', $aSessionConfig['name']);
		}

		// Note: We don't start the session here, just configure it.
		// The session will be started when first accessed via session_start()
		// or when the session service is first used.
	}
}