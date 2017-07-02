<?php
/**
 * Nextcloud - passman
 *
 * @copyright Copyright (c) 2016, Sander Brand (brantje@gmail.com)
 * @copyright Copyright (c) 2016, Marcos Zuriaga Miguel (wolfi@wolfi.es)
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

namespace OCA\OwnNote\Utility;


use DateTime;
use DOMDocument;
use OC\Files\Filesystem;

class Evernote {

	/**
	 * @param $folder
	 * @param $file
	 */
	public static function checkEvernote($folder, $file) {
		$utils = new Utils();
		$html = "";
		if ($html = Filesystem::file_get_contents($folder . "/" . $file)) {
			$DOM = new DOMDocument;
			$DOM->loadHTML($html);
			$items = $DOM->getElementsByTagName('meta');
			$isEvernote = false;
			for ($i = 0; $i < $items->length; $i++) {
				$item = $items->item($i);
				if ($item->hasAttributes()) {
					$attrs = $item->attributes;
					foreach ($attrs as $a => $attr) {
						if ($attr->name == "name") {
							if ($attr->value == "exporter-version" || $attr->value == "Generator") {
								$isEvernote = true;
								continue;
							}
						}
					}
				}
			}
			if ($isEvernote) {
				$items = $DOM->getElementsByTagName('img');
				$isEvernote = false;
				for ($i = 0; $i < $items->length; $i++) {
					$item = $items->item($i);
					if ($item->hasAttributes()) {
						$attrs = $item->attributes;
						foreach ($attrs as $a => $attr) {
							if ($attr->name == "src") {
								$url = $attr->value;
								if (!$utils->startsWith($url, "http") && !$utils->startsWith($url, "/") && !$utils->startsWith($url, "data")) {
									if ($data = Filesystem::file_get_contents($folder . "/" . $url)) {
										$type = pathinfo($url, PATHINFO_EXTENSION);
										$base64 = "data:image/" . $type . ";base64," . base64_encode($data);
										$html = str_replace($url, $base64, $html);
									}
								}
							}
						}
					}
				}
				Filesystem::file_put_contents($folder . "/" . $file, $html);
			}
		}
	}
}