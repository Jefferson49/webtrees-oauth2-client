<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 *                    <http://webtrees.net>
 *
 * Fancy Research Links (webtrees custom module):
 * Copyright (C) 2022 Carmen Just
 *                    <https://justcarmen.nl>
 *
 * OAuth2Client (webtrees custom module):
 * Copyright (C) 2025 Markus Hemprich
 *                    <http://www.familienforschung-hemprich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * 
 * autoload for webtrees custom module: OAuth2-Client
 * 
 */
 
declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\OAuth2Client;

use Composer\Autoload\ClassLoader;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;


//Check availability of correct webtrees-common library; update files if needed
$file_system = new Filesystem(new LocalFilesystemAdapter(__DIR__));
if (!$file_system->fileExists('/vendor/jefferson49/webtrees-common/autoload.php')) {
    if (!require __DIR__ . '/update_module_files.php') return false;
}

//Autoload the latest version of the common code library, which is shared between webtrees custom modules
//Caution: This autoload needs to be executed before autoloading any other libraries from __DIR__/vendor
require_once __DIR__ . '/vendor/jefferson49/webtrees-common/autoload.php';

//Autoload this webtrees custom module
$loader = new ClassLoader(__DIR__);
$loader->addPsr4('Jefferson49\\Webtrees\\Module\\OAuth2Client\\', __DIR__ . '/src');
$loader->register();

//Autoload league/oauth2 clients
require_once __DIR__ . '/vendor/autoload.php';

//Directly include provider wrappers, because they shall be detected by "get_declared_classes"
$files = $file_system->listContents('/src/Provider')->toArray();
foreach ($files as $file) {
    require_once __DIR__ . '/'. $file->path();
}

return true;
