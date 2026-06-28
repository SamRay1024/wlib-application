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

use wlib\Application\Sys\Kernel;

/**
 * Bootstrap for environment configuration.
 *
 * Handles PHP configuration, environment detection, and .env file loading.
 *
 * @package wlib\Application\Sys\Bootstrap
 */
final class EnvironmentBootstrap extends AbstractBootstrap
{
	/**
	 * {@inheritDoc}
	 */
	protected function initialize(): void
	{
		$kernel = $this->getKernel();
		$aConfig = $this->getConfig();

		// Initialize configuration with defaults
		$aOptions = array_replace(
			[
				'sys.config_dir' => 'config',
				'sys.composer' => '',
				'sys.env_filename' => '.env'
			],
			$aConfig
		);

		$aOptions['sys.base_path'] = $kernel->getBasePath();
		$aOptions['sys.config_dir'] = $this->prependBasePath($aOptions['sys.config_dir'], $kernel);
		$aOptions['sys.env_file'] = $kernel->getBasePath() . DIRECTORY_SEPARATOR . $aOptions['sys.env_filename'];

		// Bind all options to the kernel
		foreach ($aOptions as $sKey => $mValue)
			$kernel->bind($sKey, $mValue);

		// Load .env file
		try
		{
			if (function_exists('loadDotEnvFile'))
				loadDotEnvFile($kernel['sys.env_file']);
		}
		catch (\Exception $e)
		{
			// Silently fail if .env file doesn't exist
		}

		// Add config include path
		if (function_exists('addConfigIncludePath'))
			addConfigIncludePath($kernel['sys.config_dir']);

		// Set production flag
		if (function_exists('config'))
		{
			$kernel->bind('sys.production', (bool) config('app.production', false));

			// Create necessary directories
			createDir(config('app.cache_path'), 0755);
			createDir(config('app.logs_path'), 0755);
		}
	}

	/**
	 * Prepend base path to the given path if it's not absolute.
	 *
	 * @param string $sPath Path to prepend if necessary.
	 * @param Kernel $kernel Kernel instance.
	 * @return string
	 */
	private function prependBasePath(string $sPath, Kernel $kernel): string
	{
		return (!str_starts_with($sPath, '/')
			? $kernel->getBasePath() . DIRECTORY_SEPARATOR . $sPath
			: $sPath
		);
	}
}