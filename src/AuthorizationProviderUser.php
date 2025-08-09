<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 *                    <http://webtrees.net>
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
 * OAuth2-Client
 *
 * A weebtrees(https://webtrees.net) 2.1 custom module for advanced GEDCOM import, export
 * and filter operations. The module also supports remote downloads/uploads via URL requests.
 * 
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\OAuth2Client;

use Fisharebest\Webtrees\User;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;


/**
 * An extended webtrees user, which provides additional data structures for authorization providers
 */
class AuthorizationProviderUser extends User
{
    //The user ID, which is provided for the user from the authorization provider
    protected string $authorization_provider_user_id;

    //The user data, which is provided via OAuth2 for the user from the authorization provider
    protected array $user_data;   

    //The ressource owner object, which is provided from the authorization provider
    protected ResourceOwnerInterface $ressource_owner;

    /**
     * @param int                    $user_id
     * @param string                 $user_name
     * @param string                 $real_name
     * @param string                 $email
     * @param string                 $authorization_provider_user_id   The user ID, which is provided for the user from the authorization provider
     * @param array                  $user_data                        The user data, which is provided via OAuth2 for the user from the authorization provider
     * @param ResourceOwnerInterface $ressource_owner                  The ressource owner object, which is provided from the authorization provider
     * 
     */
    public function __construct(int $user_id, string $user_name, string $real_name, string $email, string $authorization_provider_user_id, array  $user_data, ResourceOwnerInterface $ressource_owner)
    {
        $this->authorization_provider_user_id = $authorization_provider_user_id;
        $this->user_data = $user_data;
        $this->ressource_owner = $ressource_owner;

        parent::__construct($user_id, $user_name, $real_name, $email);
    }

    /**
     * Get the user ID, which is provided for the user from the authorization provider
     * 
     * @return string
     */    
    public function getAuthorizationProviderUserId(): string {

        return $this->authorization_provider_user_id;
    }

    /**
     * Get the user data, which is provided via OAuth2 for the user from the authorization provider
     * 
     * @return array
     */    
    public function getUserData(): array {

        return $this->user_data;
    }

    /**
     * Get the ressource owner object, which is provided from the authorization provider
     * 
     * @return ResourceOwnerInterface
     */    
    public function getRessourceOwner(): ResourceOwnerInterface {

        return $this->ressource_owner;
    }        
}
