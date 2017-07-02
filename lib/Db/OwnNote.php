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

namespace OCA\OwnNote\Db;
use \OCP\AppFramework\Db\Entity;

/**
 * @method integer getId()
 * @method void setId(integer $value)
 * @method void setName(string $value)
 * @method string getName()
 * @method void setGrouping(string $value)
 * @method string getGrouping()
 * @method void setUid(string $value)
 * @method string getNote()
 * @method void setNote(string $value)
 * @method string getUid()
 * @method void setMtime(integer $value)
 * @method integer getMtime()
 * @method void setDeleted(integer $value)
 * @method integer getDeleted()
 */


class OwnNote extends Entity implements  \JsonSerializable{

	use EntityJSONSerializer;

	protected $name;
	protected $grouping;
	protected $uid;
	protected $mtime;
	protected $deleted;
	protected $note;
	
	public function __construct() {
		// add types in constructor
		$this->addType('mtime', 'integer');
	}
	/**
	 * Turns entity attributes into an array
	 */
	public function jsonSerialize() {
		$now = new \DateTime();
		return [
			'id' => $this->getId(),
			'mtime' => $this->getMtime(),
			'timediff' =>  $now->getTimestamp() - $this->getMtime(),
			'name' => $this->getName(),
			'uid' => $this->getUid(),
			'group' => $this->getGrouping(),
			'note' => $this->getNote(),
			'deleted' => $this->getDeleted(),
		];
	}
}