<?php
/**
 * Nextcloud - ownnote
 *
 * @copyright Copyright (c) 2015, Ben Curtis <ownclouddev@nosolutions.com>
 * @copyright Copyright (c) 2017, Sander Brand (brantje@gmail.com)
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OwnNote\Controller;

use OC\User\Manager;
use OCA\OwnNote\Service\OwnNoteGroupService;
use OCA\OwnNote\Service\OwnNoteService;
use \OCP\AppFramework\ApiController;
use \OCP\App;
use OCP\IConfig;
use OCP\ILogger;
use \OCP\IRequest;
use \OCA\OwnNote\Lib\Backend;


class OwnnoteApiController extends ApiController {

	private $backend;
	private $config;
	private $noteService;
	private $noteGroupService;
	private $uid;

	public function __construct($appName, IRequest $request, ILogger $logger, IConfig $config, OwnNoteService $noteService, OwnNoteGroupService $groupService){
		parent::__construct($appName, $request);
		$this->backend = new Backend($config);
		$this->config = $config;
		$this->appName = $appName;
		$this->noteService = $noteService;
		$this->noteGroupService = $groupService;
		$this->uid = \OC::$server->getUserSession()->getUser()->getUID();
	}

	/**
	* MOBILE FUNCTIONS
	*/

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function index() {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		return $this->noteService->getListing($FOLDER, false);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function mobileindex() {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		return $this->noteService->getListing($FOLDER, true);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function remoteindex() {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		return $this->noteService->getListing($FOLDER, true);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function create($name, $group) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($name) && isset($group)) {
			$note = [
				'name' => $name,
				'group' => $group
			];
			return $this->noteService->create($FOLDER, $note, $this->uid);
		}
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function del($nid) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($nid))
			return $this->noteService->delete($FOLDER, $nid);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function edit($id) {
		if (isset($id)) {
			/**
			 * @param OwnNote $note
			 */
			$note = $this->noteService->find($id);
			return $note->getNote();
		}
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function save($id, $content) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($id) && isset($content)) {
			$note = [
				'id' => $id,
				'note' => $content,
				'mtime' => time()
			];

			return ($this->noteService->update($FOLDER, $note));
		}
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function ren($id, $newname, $newgroup) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($id) && isset($newname) && isset($newgroup))
			return $this->noteService->renameNote($FOLDER, $id, $newname, $newgroup);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function delgroup($group) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($group))
			return $this->noteGroupService->deleteGroup($FOLDER, $group);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function rengroup($group, $newgroup) {
		$FOLDER = $this->config->getAppValue($this->appName, 'folder', '');
		if (isset($group) && isset($newgroup))
			return $this->noteGroupService->renameGroup($FOLDER, $group, $newgroup);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function version() {
		$AppInstance = new App();
		return $AppInstance->getAppInfo($this->appName)["version"];
	}
}
