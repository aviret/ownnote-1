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

use \OCP\AppFramework\ApiController;
use \OCP\IRequest;



class OwnnoteSharesController extends ApiController {

	public function __construct($appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function getShares($noteid, $shared_with_me, $reshares) {
		if ($shared_with_me) {
			return \OCP\Share::getItemSharedWith('ownnote', $noteid, 'shares');
		} else if ($reshares) {
			return array_values(\OCP\Share::getItemShared('ownnote', $noteid, 'shares'));
		}
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function share($noteid, $shareType, $shareWith, $publicUpload, $password, $permissions) {
		return \OCP\Share::shareItem('ownnote', intval($noteid), intval($shareType), $shareWith, intval($permissions));
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function unshare($itemSource, $shareType, $shareWith) {
		return \OCP\Share::unshare('ownnote', intval($itemSource), intval($shareType), $shareWith);
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */		
	public function setpermissions($itemSource, $shareType, $shareWith, $permissions) {
		return \OCP\Share::setPermissions('ownnote', intval($itemSource), intval($shareType), $shareWith, intval($permissions));
	}
}
