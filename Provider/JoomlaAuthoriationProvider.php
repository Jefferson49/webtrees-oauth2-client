<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2024 webtrees development team
 *                    <http://webtrees.net>
 *
 * Fancy Research Links (webtrees custom module):
 * Copyright (C) 2022 Carmen Just
 *                    <https://justcarmen.nl>
 *
 * ExtendedImportExport (webtrees custom module):
 * Copyright (C) 2024 Markus Hemprich
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

namespace Jefferson49\Webtrees\Module\OAuth2Client;

use Fisharebest\Webtrees\User;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * An OAuth2 authorization client for Joomla
 */
class JoomlaAuthoriationProvider implements AuthorizationProviderInterface
{
    //The authorization provider
    private GenericProvider $provider;


    /**
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])    
    {
        $this->provider = new GenericProvider($options, $collaborators);
    }

    /**
     * Get the name of the authorization client
     * 
     * @return string
     */
    public static function getName() : string {
        return 'Joomla';
    }

    /**
     * Get the authorization URL
     *
     * @param  array $options
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(array $options = [])
    {
        return $this->provider->getAuthorizationUrl($options);
    }

    /**
     * Get the current value of the state parameter
     *
     * This can be accessed by the redirect handler during authorization.
     *
     * @return string
     */
    public function getState()
    {
        return $this->provider->getState();
    }    

    /**
     * Get an access token from the provider using a specified grant and option set.
     *
     * @param  mixed                $grant
     * @param  array<string, mixed> $options
     * @throws IdentityProviderException
     * @return AccessTokenInterface
     */
    public function getAccessToken($grant, array $options = [])
    {
        return $this->provider->getAccessToken($grant, $options);
    }

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    public function getResourceOwner(AccessToken $token) : ResourceOwnerInterface
    {
        return $this->provider->getResourceOwner($token);
    }

    /**
     * Use access token to get user data from provider and return it as a webtrees User object
     * 
     * @param AccessToken $token
     * 
     * @return string
     */
    public function getUserData(AccessToken $token) : User {

        $resourceOwner = $this->provider->getResourceOwner($token);
        $user_data = $resourceOwner->toArray();

        $webtrees_user = new User(
            $user_data['id'] ?? '',
            $user_data['username'] ?? '',
            $user_data['name'] ?? '',
            $user_data['email'] ?? ''
        );


        return $webtrees_user;
    }
}
