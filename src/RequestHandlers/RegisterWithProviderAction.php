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
 * OAuth2-Client
 *
 * A weebtrees(https://webtrees.net) 2.1 custom module to implement an OAuth2 client
 * 
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\OAuth2Client\RequestHandlers;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\Http\RequestHandlers\LoginPage;
use Fisharebest\Webtrees\Http\RequestHandlers\RegisterAction;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Services\CaptchaService;
use Fisharebest\Webtrees\Services\EmailService;
use Fisharebest\Webtrees\Services\RateLimitService;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Helpers\DeactivatedCaptchaService;
use Jefferson49\Webtrees\Helpers\Functions;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Exception;

/**
 * Register with an authorization provider
 */
class RegisterWithProviderAction implements RequestHandlerInterface
{
    use ViewResponseTrait;

    private CaptchaService $captcha_service;

    /**
     * @param CaptchaService $captcha_service
     */
    public function __construct(CaptchaService $captcha_service)
    {
        $this->captcha_service = $captcha_service;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->checkRegistrationAllowed();

        $tree               = Validator::attributes($request)->treeOptional();

        $password_token     = Validator::queryParams($request)->string('password_token', '');
        $email              = Validator::queryParams($request)->string('email', '');
        $real_name          = Validator::queryParams($request)->string('real_name', '');
        $user_name          = Validator::queryParams($request)->string('user_name', '');
        $comments           = Validator::parsedBody($request)->string('comments');

        try {
            if ($this->captcha_service->isRobot($request)) {
                throw new Exception(MoreI18N::xlate('Please try again.'));
            }
        } 
        catch (Exception $ex) {
            FlashMessages::addMessage($ex->getMessage(), 'danger');

            return redirect(route(LoginPage::class));
        }        

        //Generate a request for a new webtrees user account
        $random_password  = md5($password_token . time());

        $params = [
            'comments'        => $comments,
            'email'           => $email,
            'password'        => $random_password,
            'realname'        => $real_name,
            'username'        => $user_name,
        ];

        $request         = Functions::getFromContainer(ServerRequestInterface::class);
        $request         = $request->withAttribute('tree', $tree instanceof Tree ? $tree: null);
        $request         = $request->withParsedBody($params);

        //Use a deactivated captcha service to call the request handler directly from the code
        $request_handler = new RegisterAction(new DeactivatedCaptchaService, new EmailService, new RateLimitService(), new UserService);
    
        return $request_handler->handle($request);
    }

    /**
     * Check that visitors are allowed to register on this site.
     *
     * @return void
     * @throws HttpNotFoundException
     */
    private function checkRegistrationAllowed(): void
    {
        if (Site::getPreference('USE_REGISTRATION_MODULE') !== '1') {
            throw new HttpNotFoundException();
        }
    }
}
