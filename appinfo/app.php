<?php
/**
 * ownCloud - ownnote
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Ben Curtis <ownclouddev@nosolutions.com>
 * @copyright Ben Curtis 2015
 */

namespace OCA\OwnNote\AppInfo;


use OCA\OwnNote\AppInfo\Application;

require_once __DIR__ . '/autoload.php';

$app = new Application(); // \AppInfo\Application();
$app->registerNavigationEntry();

\OCP\Share::registerBackend ('ownnote', '\OCA\OwnNote\ShareBackend\OwnnoteShareBackend');
\OCP\App::registerAdmin('ownnote', 'admin');
