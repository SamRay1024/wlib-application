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

use wlib\Db\Db;
use wlib\Di\DiBox;
use wlib\Di\DiBoxProvider;

class DbPanel implements \Tracy\IBarPanel
{
	private $sName;
	private $db;

	public function __construct($sDbName, Db $db)
	{
		$this->sName = $sDbName;
		$this->db = $db;
	}

	public function getTab()
	{
		return
			'<span title="Database">'
				.'<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-database" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" /><path d="M4 6v6a8 3 0 0 0 16 0v-6" /><path d="M4 12v6a8 3 0 0 0 16 0v-6" /></svg>'
				.'<span class="tracy-label">DB:'.$this->sName.':'.$this->db->getQueriesCount().'</span>'
			.'</span>';
	}

	public function getPanel()
	{
		return '<h1>DB:'. $this->sName .'</h1>

			<div class="tracy-inner">
			<div class="tracy-inner-container">
				<pre>'. print_r($this->db, true).'</pre>
			</div>
			</div>
			';
	}
}

class TracyDiProvider implements DiBoxProvider
{
	public function provide(DiBox $box)
	{
		$aDb = config('app.databases');

		foreach ($aDb as $sName => $aCfg)
		{
			/** @var \wlib\Db\Db $db */
			$db = $box['db.'.$sName];

			$db->saveQueries(!$box['sys.production']);

			\Tracy\Debugger::getBar()->addPanel(new DbPanel($sName, $db));
		}
	}
}