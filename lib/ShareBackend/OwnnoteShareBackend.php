<?php

namespace OCA\OwnNote\ShareBackend;

use \OCP\Share_Backend;

class OwnnoteShareBackend implements Share_Backend {

	private $db;

	public function __construct() {
		$this->db = \OC::$server->getDatabaseConnection();
	}

	/**
	 * Check if this $itemSource exist for the user
	 * @param string $itemSource
	 * @param string $uidOwner Owner of the item
	 * @return boolean|null Source
	 *
	 * Return false if the item does not exist for the user
	 * @since 5.0.0
	 */
	public function isValidSource($itemSource, $uidOwner) {
		// todo: real test
		// id => 1, has admin 
		// has owner this note?
		
		return true;		
	}
	
	/**
	 * Get a unique name of the item for the specified user
	 * @param string $itemSource
	 * @param string|false $shareWith User the item is being shared with
	 * @param array|null $exclude List of similar item names already existing as shared items @deprecated since version OC7
	 * @return string Target name
	 *
	 * This function needs to verify that the user does not already have an item with this name.
	 * If it does generate a new name e.g. name_#
	 * @since 5.0.0
	 */
	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		// note id (should be unique)
		return $itemSource;
	}

	/**
	 * Converts the shared item sources back into the item in the specified format
	 * @param array $items Shared items
	 * @param int $format
	 * @return array
	 *
	 * The items array is a 3-dimensional array with the item_source as the
	 * first key and the share id as the second key to an array with the share
	 * info.
	 *
	 * The key/value pairs included in the share info depend on the function originally called:
	 * If called by getItem(s)Shared: id, item_type, item, item_source,
	 * share_type, share_with, permissions, stime, file_source
	 *
	 * If called by getItem(s)SharedWith: id, item_type, item, item_source,
	 * item_target, share_type, share_with, permissions, stime, file_source,
	 * file_target
	 *
	 * This function allows the backend to control the output of shared items with custom formats.
	 * It is only called through calls to the public getItem(s)Shared(With) functions.
	 * @since 5.0.0
	 */
	public function formatItems($items, $format, $parameters = null) {
		if ($format === 'shares') {
			return $items;
		}
		
		// get the ownnote ids
		$ids = Array();
		foreach($items as $item) {
			$ids[] = $item['item_source'];
		}
		
		// get notes from database
		$select_clause = "SELECT id, uid, name, grouping, mtime, deleted FROM *PREFIX*ownnote WHERE id in (";
		$select_clause .= implode(',', $ids);
		$select_clause .= ") ORDER BY id";
		$q = $this->db->executeQuery($select_clause, array());
		//$query = \OCP\DB::prepare($select_clause);
		$results = $q->fetchAll();

		// add permissions to items
		if ($format === 'populated_shares') {
			$full_items = Array();
			foreach($results as $index => $result) {
				$full_items[] = array_merge($items[$index], $result);
			}
			$results = $full_items;
		}
		
		return $results;
	}
	
	/**
	 * Check if a given share type is allowd by the back-end
	 *
	 * @param int $shareType share type
	 * @return boolean
	 *
	 * The back-end can enable/disable specific share types. Just return true if
	 * the back-end doesn't provide any specific settings for it and want to allow
	 * all share types defined by the share API
	 * @since 8.0.0
	 */
	public function isShareTypeAllowed($shareType) {
		return true;
	}
	
}