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
 * A weebtrees(https://webtrees.net) 2.1 custom module to implement an OAuth2 client
 * 
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\OAuth2Client\Provider;

use Fisharebest\Webtrees\User;
use Jefferson49\Webtrees\Module\OAuth2Client\AuthorizationProviderUser;
use Jefferson49\Webtrees\Module\OAuth2Client\Contracts\AuthorizationProviderInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;


/**
 * An OAuth2 authorization client for Github
 */
class GoogleAuthorizationProvider extends AbstractAuthorizationProvider implements AuthorizationProviderInterface
{        
    //The authorization provider
    protected AbstractProvider $provider;


    /**
     * @param string $redirectUri
     * @param array  $options
     * @param array  $collaborators
     */
    public function __construct(string $redirectUri, array $options = [], array $collaborators = [])
    {
        if ($redirectUri === '' && $options === []) return;

        $options = array_merge($options, [
            'redirectUri'             => $redirectUri,
        ]);

        $this->provider = new Google($options, $collaborators);
    }

    /**
     * Use access token to get user data from provider and return it as a webtrees User object
     * 
     * @param AccessToken $token
     * 
     * @return User
     */
    public function getUserData(AccessToken $token) : AuthorizationProviderUser {

        $user           = parent::getUserData($token);
        $resource_owner = $user->getRessourceOwner();

        //Apply specific user data provided by Google        
        //Take email as user name, because user name is not provided by Google
        $user->setUserName($resource_owner->getEmail() ?? '');
        $user->setRealName($resource_owner->getName() ?? '');

        return $user;
    }      

    /**
     * Returns a list with options that can be passed to the provider
     *
     * @return array   An array of option names, which can be set for this provider.
     *                 Options include `clientId`, `clientSecret`, `redirectUri`, etc.
     */
    public static function getRequiredOptions() : array {
        return [
            'clientId',
            'clientSecret',
        ];
    } 
}
