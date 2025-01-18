<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2024 webtrees development team
 *                    <http://webtrees.net>
 *
 * OAuth2Client (webtrees custom module):
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

namespace Jefferson49\Webtrees\Module\OAuth2Client\Provider;

use Fisharebest\Webtrees\User;
use Jefferson49\Webtrees\Module\OAuth2Client\AuthorizationProviderUser;
use Jefferson49\Webtrees\Module\OAuth2Client\Contracts\AuthorizationProviderInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;


/**
 * An OAuth2 authorization client for WordPress
 */
class WordPressAuthorizationProvider extends AbstractAuthorizationProvider implements AuthorizationProviderInterface
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
            'scopes'                  => 'openid profile email',
            'scopeSeparator'          => ' '
        ]);
        
        $this->provider = new GenericProvider($options, $collaborators);

        if (isset($options['signInButtonLabel'])) {
            $this->setSignInButtonLabel($options['signInButtonLabel']);
        }
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
        $user_data      = $user->getUserData();

        //Apply specific user data provided by Github

        $first_name   = $user_data['first_name']   ?? '';
        $last_name    = $user_data['last_name']    ?? '';
        $display_name = $user_data['display_name'] ?? '';

        $real_name = $first_name;

        if ((strlen($first_name) > 0) && (strlen($last_name) > 0)) {
            $real_name .= ' ';
        } 

        $real_name .= $last_name;

        //If no real name could be generated from first/last name, use display name
        if ($real_name === '') {
            $real_name = $display_name;
        }

        $user->setRealName($real_name);

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
            'urlAuthorize',
            'urlAccessToken',
            'urlResourceOwnerDetails',
            'signInButtonLabel',
        ];   
    }    
}
