<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 *                    <http://webtrees.net>
 *
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
 * Update custom module files, e.g. to upgrade to a new version
 * 
 */
 
declare(strict_types=1);

use Fisharebest\Webtrees\FlashMessages;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;


//Error message
$message  = basename(__DIR__) .': ';
$message .= 'An error occured during updating the custom module files to a new version. Try to reload webtrees. If the error does not disappear, delete the custom module folder and re-install the new version from scratch.';

//Create filesystem
$file_system = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/vendor'));

//Delete old libraries
$file_system->deleteDirectory('cissee');
$file_system->deleteDirectory('Jefferson49/Webtrees');

//If exists old path with upper case, rename to lower case
$old_path = 'Jefferson49';
$new_path = 'jefferson49';

try {
    if ($file_system->fileExists($old_path . '/webtrees-common/autoload.php')) {
        $file_system->move($old_path, $new_path);
    }
}
catch (\Exception $e) {
    FlashMessages::addMessage($message);
    return false;
}

//Check accesibility of common library
if (!$file_system->fileExists($new_path . '/webtrees-common/autoload.php')) {
    FlashMessages::addMessage($message);
    return false;
}

return true;
