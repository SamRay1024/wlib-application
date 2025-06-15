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

namespace wlib\Application\Sys;

use UnexpectedValueException;
use wlib\Db\Db;
use wlib\Di\DiBox;
use wlib\Di\DiBoxProvider;

/**
 * Application main services provider.
 * 
 * @author Cédric Ducarre
 */
class SysDiProvider implements DiBoxProvider
{
	public function provide(DiBox $box)
	{
		$box->singleton('http.request', \wlib\Http\Server\Request::class);
		$box->singleton('http.response', \wlib\Http\Server\Response::class);
		$box->singleton('http.session', \wlib\Http\Server\Session::class);
		$box->singleton('http.cache', \wlib\Application\Sys\Cache::class);

		$box->bind('http.router', function($box, $args)
		{
			return new Router($box['http.request'], $args[0]) /* base URI */;
		});
	
		$aDatabases = config('app.databases');

		if (!is_array($aDatabases))
			throw new UnexpectedValueException(
				'Config entry "app.databases" must be an array of connections.'
			);

		foreach ($aDatabases as $sName => $aConnection)
		{
			if (trim($aConnection['driver']) == '')
				continue;

			$box->singleton('db.'.$sName, function($box, $args) use ($aConnection)
			{
				return new Db(
					(string) arrayValue($aConnection, 'driver'),
					(string) arrayValue($aConnection, 'database'),
					(string) arrayValue($aConnection, 'username'),
					(string) arrayValue($aConnection, 'password'),
					(string) arrayValue($aConnection, 'host'),
					(int) arrayValue($aConnection, 'port'),
					(string) arrayValue($aConnection, 'charset'),
					(int) arrayValue($aConnection, 'timeout')
				);
			});
		}
	}
}