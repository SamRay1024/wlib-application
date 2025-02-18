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

namespace wlib\Application\Templates;

/**
 * Just a very simple PHP template engine.
 * 
 * @author Cédric Ducarre
 */
class Engine
{
	/**
	 * Templates files locations.
	 * @var array
	 */
	protected array $aSrcPath = [];

	/**
	 * Default template file extension.
	 * @var string
	 */
	protected string $sFileExt = '';

	/**
	 * Data to transmit to templates files.
	 * @var array
	 */
	protected array $aData = [];
	
	/**
	 * __construct
	 *
	 * @param string $sPath Default location.
	 * @param string $sFileExtension Template file extension.
	 */
	public function __construct(string $sPath = '', string $sFileExtension = '.php')
	{
		if (trim($sPath))
			$this->addSrcPath($sPath);

		$this->setFileExtension($sFileExtension);
	}
	
	/**
	 * Add a template files location.
	 *
	 * You can add multiple locations to create template inheritance. The last
	 * path added will be the first from which template files will be searched.
	 * 
	 * @param mixed $sPath
	 * @return self
	 */
	public function addSrcPath(string $sPath): self
	{
		is_dir($sPath) or throw new \LogicException(sprintf(
			'Templates dir "%s" not found.',
			$sPath
		));

		array_unshift($this->aSrcPath, $sPath);

		return $this;
	}
	
	/**
	 * Define template file extension.
	 *
	 * @param string $sExtension
	 * @return self
	 */
	public function setFileExtension(string $sExtension): self
	{
		$this->sFileExt = $sExtension;
		return $this;
	}
	
	/**
	 * Render the given template file.
	 *
	 * @param string $sTplFile Template file address.
	 * @param array $aData Data to pass to the template file.
	 * @return string Rendered template.
	 */
	public function render(string $sTplFile, array $aData = []): string
	{
		if (count($aData))
			$this->aData = array_merge($this->aData, $aData);

		reset($this->aSrcPath);

		foreach ($this->aSrcPath as $sSrcPath)
		{
			$sTplFileFull = $sSrcPath .'/'. ltrim($sTplFile, '/') . $this->sFileExt;

			if (file_exists($sTplFileFull))
			{
				extract($this->aData);

				ob_start();
				include $sTplFileFull;
				return ob_get_clean();
			}
		}
		
		throw new \LogicException(sprintf(
			'Template file "%s" not found.',
			$sTplFileFull
		));
	}
}