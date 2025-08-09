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
 * OAuth2Client
 *
 * A weebtrees(https://webtrees.net) 2.1 custom module for advanced GEDCOM import, export
 * and filter operations. The module also supports remote downloads/uploads via URL requests.
 * 
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\OAuth2Client\Contracts;

use Fisharebest\Webtrees\User;
use Jefferson49\Webtrees\Module\OAuth2Client\AuthorizationProviderUser;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;


/**
 * Interface for OAuth2 authorization providers to be used within webtrees 
 */
interface AuthorizationProviderInterface
{
    /**
     * @param string $redirectUri
     * @param array  $options
     * @param array  $collaborators
     */
    public function __construct(string $redirectUri, array $options = [], array $collaborators = []);

    /**
     * Get the name of the authorization client
     * 
     * @return string
     */
    public static function getName() : string;

    /**
     * Set the sing in button label for the authorization client
     * 
     * @param string $label
     * 
     * @return void
     */
    public function setSignInButtonLabel(string $label) : void;

    /**
     * Get the sign in button label for the authorization client
     * 
     * @return string
     */
    public function getSignInButtonLabel() : string;
    
    /**
     * Get the authorization URL
     *
     * @param  array $options
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(array $options = []);

    /**
     * Get the current value of the state parameter
     *
     * This can be accessed by the redirect handler during authorization.
     *
     * @return string
     */
    public function getState();
    
        /**
     * Get an access token from the provider using a specified grant and option set.
     *
     * @param  mixed                $grant
     * @param  array<string, mixed> $options
     * @throws IdentityProviderException
     * @return AccessTokenInterface
     */
    public function getAccessToken($grant, array $options = []);

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    public function getResourceOwner(AccessToken $token) : ResourceOwnerInterface;
    
    /**
     * Use access token to get user data from provider and return it as a webtrees User object
     * 
     * @param AccessToken $token
     * 
     * @return AuthorizationProviderUser
     */
    public function getUserData(AccessToken $token) : AuthorizationProviderUser;    

    /**
     * Returns a list with options that can be passed to the provider
     *
     * @return array   An array of option names, which can be set for this provider.
     *                 Options include `clientId`, `clientSecret`, `redirectUri`, etc.
     */
    public static function getRequiredOptions() : array;

    /**
     * Whether the provider provides enough user data for a webtrees registration, e.g. username and email
     *
     * @return bool
     */
    public static function supportsRegistration() : bool;

    /**
     * Returns the current value of the pkceCode parameter.
     *     *
     * @return string|null
     */
    public function getPkceCode() : ?string;

    /**
     * Set the value of the pkceCode parameter.
     *
     * When using PKCE this should be set before requesting an access token.
     *
     * @param string $pkceCode
     * @return void
     */
    public function setPkceCode($pkceCode);
}
