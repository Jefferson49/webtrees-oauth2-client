<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2026 webtrees development team
 *                    <http://webtrees.net>
 *
 * Fancy Research Links (webtrees custom module):
 * Copyright (C) 2022 Carmen Just
 *                    <https://justcarmen.nl>
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
 * A weebtrees(https://webtrees.net) 2.1 custom module to implement an OAuth2 client
 * 
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\OAuth2Client\RequestHandlers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\RequestHandlers\HomePage;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\User;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Module\OAuth2Client\Factories\AuthorizationProviderFactory;
use Jefferson49\Webtrees\Module\OAuth2Client\OAuth2Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;

/*
 * Logout from webtrees and optionally from the authorization provider
 * 
 * Code from: Fisharebest\Webtrees\Http\RequestHandlers\Logout.php
 * Last check: 2026-04-08
 */
final class OAuth2Logout implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user             = Validator::attributes($request)->user();
        $provider_name    = Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_PROVIDER_NAME);
        $provider_options = AuthorizationProviderFactory::getProviderOptions($provider_name);
        $post_signout_url = $provider_options['postSignoutURI'] ?? null;

        if ($user instanceof User) {
            Log::addAuthenticationLog('Logout: ' . Auth::user()->userName() . '/' . Auth::user()->realName());
            Auth::logout();
            FlashMessages::addMessage(MoreI18N::xlate('You have signed out.'));
        }

        // Redirect to post signout URL if configured for authorization provider
        if ($post_signout_url !== null){

            return redirect($post_signout_url);
        }

        return redirect(route(HomePage::class));
    }
}
