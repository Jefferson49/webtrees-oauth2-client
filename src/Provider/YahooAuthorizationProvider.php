<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2026 webtrees development team
 *                    <http://webtrees.net>
 *
 * OAuth2Client (webtrees custom module):
 * Copyright (C) 2026 Markus Hemprich
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
 * A webtrees(https://webtrees.net) 2.1 custom module to implement an OAuth2 client
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\OAuth2Client\Provider;

use Jefferson49\Webtrees\Module\OAuth2Client\AuthorizationProviderUser;
use Jefferson49\Webtrees\Module\OAuth2Client\Contracts\AuthorizationProviderInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

class YahooAuthorizationProvider extends AbstractAuthorizationProvider implements AuthorizationProviderInterface
{
    protected AbstractProvider $provider;

    public function __construct(string $redirectUri, array $options = [], array $collaborators = [])
    {
        if ($redirectUri === '' && $options === []) return;

        $options = array_merge([
            'urlAuthorize'                 => 'https://api.login.yahoo.com/oauth2/request_auth',
            'urlAccessToken'               => 'https://api.login.yahoo.com/oauth2/get_token',
            'urlResourceOwnerDetails'      => 'https://api.login.yahoo.com/openid/v1/userinfo',
            'scope'                        => ['openid', 'profile', 'email'],
            'responseResourceOwnerId'      => 'sub',
        ], $options, [
            'redirectUri' => $redirectUri,
        ]);

        $this->provider = new GenericProvider($options, $collaborators);
    }

    public function getUserData(AccessToken $token): AuthorizationProviderUser
    {
        $user           = parent::getUserData($token);
        $resource_owner = $user->getRessourceOwner();

        // Yahoo has no username field. Strip domain from email.
        $data = $resource_owner->toArray();
        $user->setUserName($data['email'] ?? '');
        $user->setRealName($data['name'] ?? '');

        return $user;
    }

    public static function getRequiredOptions(): array
    {
        return ['clientId', 'clientSecret'];
    }

    public function getSignInButtonLabel(): string
    {
        return 'Yahoo';
    }
}
