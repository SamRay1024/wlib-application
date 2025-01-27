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

namespace wlib\Application\Controllers;

use Exception;
use UnexpectedValueException;
use wlib\Application\Exceptions\UnexpectedFieldValueException;
use wlib\Http\Server\HttpException;
use wlib\Http\Server\Response;

/**
 * RESTFul controller.
 * 
 * @author Cédric Ducarre
 */
class RestController extends Controller
{
	public function initialize()
	{
		$this->response->addHeader('Content-Type', 'application/json');
	}

	final public function start()
	{
		$sMethod = strtolower($this->method());
		
		try
		{
			method_exists($this, $sMethod) or $this->haltNotImplemented(sprintf(
				static::class .' : "%s()" method is not implemented.',
				$sMethod
			));

			call_user_func_array([$this, $sMethod], $this->args());

			switch ($sMethod)
			{
				case 'post':
					$this->response->setStatus(
						$this->response->hasBody()
						? Response::HTTP_CREATED
						: Response::HTTP_NO_CONTENT
					);
					break;

				default:
					$this->response->setStatus(
						$this->response->hasBody()
						? Response::HTTP_OK
						: Response::HTTP_NO_CONTENT
					);
			}
		}
		catch (UnexpectedValueException $ex)
		{
			$this->response->json(
				['error' => [
					'code' => $ex->getCode(),
					'title' => Response::getStatusMessage($ex->getCode()),
					'detail' => $ex->getMessage(),
					'fields' => ($ex instanceof UnexpectedFieldValueException
						? $ex->getFields()
						: false
					)
				]],
				422
			);
		}
		catch (HttpException $ex)
		{
			$this->response->json(
				['error' => [
					'code' => $ex->getStatusCode(),
					'title' => Response::getStatusMessage($ex->getStatusCode()),
					'detail' => $ex->getMessage()
				]],
				$ex->getStatusCode()
			);
		}
		catch (Exception $ex)
		{
			$this->response->json(
				['error' => [
					'code' => $ex->getCode() ?: 500,
					'title' => Response::getStatusMessage(500),
					'detail' => $ex->getMessage()
				]],
				500
			);
		}
	}
}