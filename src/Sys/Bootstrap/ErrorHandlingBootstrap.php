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
use Psr\Log\LoggerInterface;

/**
 * Bootstrap for error handling configuration.
 *
 * Configures PHP error reporting, exception handling, and PSR-3 logger integration.
 *
 * @package wlib\Application\Sys\Bootstrap
 */
final class ErrorHandlingBootstrap extends AbstractBootstrap
{
	/**
	 * {@inheritDoc}
	 */
	protected function initialize(): void
	{
		$kernel = $this->getKernel();

		// Set error reporting to maximum
		error_reporting(-1);

		// Display errors based on production environment
		$bProduction = $kernel['sys.production'] ?? false;
		ini_set('display_errors', !$bProduction);

		// Set custom error handler
		set_error_handler([$kernel, 'handleError']);

		// Try to use PSR-3 logger if available
		$this->setupPsr3Logger($kernel);
	}

	/**
	 * Set up PSR-3 logger for error handling.
	 *
	 * @param Kernel $kernel Kernel instance.
	 * @return void
	 */
	private function setupPsr3Logger(Kernel $kernel): void
	{
		if (interface_exists(LoggerInterface::class) && $kernel->has('logger'))
		{
			/** @var LoggerInterface $logger */
			$logger = $kernel->get('logger');

			// Override error handler to use PSR-3 logger
			set_error_handler(function($iLevel, $sMessage, $sFile = '', $iLine = 0) use ($logger, $kernel)
			{
				if (error_reporting() & $iLevel)
				{
					$logger->error($sMessage, [
						'level' => $iLevel,
						'file' => $sFile,
						'line' => $iLine
					]);

					throw new \ErrorException($sMessage, 0, $iLevel, $sFile, $iLine);
				}

				return false;
			});

			// Override exception handler if possible
			if (function_exists('set_exception_handler'))
			{
				set_exception_handler(function(\Throwable $oException) use ($logger)
				{
					$logger->critical($oException->getMessage(), [
						'exception' => get_class($oException),
						'file' => $oException->getFile(),
						'line' => $oException->getLine(),
						'trace' => $oException->getTraceAsString()
					]);
				
					// Re-throw to let the application handle it
					throw $oException;
				});
			}
		}
	}
}