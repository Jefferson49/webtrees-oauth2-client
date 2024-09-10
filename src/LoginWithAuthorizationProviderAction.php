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

namespace Jefferson49\Webtrees\Module\OAuth2Client;

use Exception;
use Cissee\WebtreesExt\MoreI18N;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\Http\RequestHandlers\HomePage;
use Fisharebest\Webtrees\Http\RequestHandlers\LoginPage;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\Http\RequestHandlers\UpgradeWizardPage;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\Services\CaptchaService;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\UpgradeService;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\User;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Module\OAuth2Client\Factories\AuthorizationProviderFactory;
use Jefferson49\Webtrees\Module\OAuth2Client\Provider\AbstractAuthoriationProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function substr;

/**
 * Perform a login with an authorization provider
 */
class LoginWithAuthorizationProviderAction implements RequestHandlerInterface
{
    use ViewResponseTrait;

	//Module service to search and find modules
	private ModuleService $module_service;

    private UpgradeService $upgrade_service;

    private UserService $user_service;
    private CaptchaService $captcha_service;

    /**
     * @param UpgradeService $upgrade_service
     * @param UserService    $user_service
     */
    public function __construct(UpgradeService $upgrade_service, UserService $user_service, ModuleService $module_service, CaptchaService $captcha_service)
    {
        $this->upgrade_service = $upgrade_service;
        $this->user_service    = $user_service;
        $this->module_service  = $module_service;
        $this->captcha_service = $captcha_service;        
    }

    /**
     * Perform a login.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code          = Validator::queryParams($request)->string('code', '');
        $state         = Validator::queryParams($request)->string('state', '');
        $provider_name = Validator::queryParams($request)->string('provider_name', '');
        $base_url      = Validator::attributes($request)->string('base_url');
        $tree          = Validator::attributes($request)->treeOptional();
        $url           = Validator::queryParams($request)->isLocalUrl()->string('url', route(HomePage::class));

        //Save or load the provider name to the session
        if ($provider_name !== '') {
            Session::put(OAuth2Client::activeModuleName() . 'provider_name', $provider_name);
            Session::put(OAuth2Client::activeModuleName() . 'url', $url);
            Session::put(OAuth2Client::activeModuleName() . 'tree', $tree);
        }
        else {
            $provider_name = Session::get(OAuth2Client::activeModuleName() . 'provider_name');
        }

        $oauth2_client = $this->module_service->findByName(OAuth2Client::activeModuleName());
        $user = null;

        $provider = (new AuthorizationProviderFactory())->make($provider_name, OAuth2Client::getRedirectUrl($base_url));

        //Check if requested provider is available
        if ($provider === null) {
            FlashMessages::addMessage(I18N::translate('The requested authorization provider could not be found') . ': ' . $provider_name, 'danger');
            return redirect(route(LoginPage::class, ['tree' => $tree, 'url' => $url]));            
        }

        //Validate the requested provider
        $validation_result = $provider->validate();
        if ($validation_result !== '') {
            FlashMessages::addMessage($validation_result, 'danger');
            return redirect(route(LoginPage::class, ['tree' => $tree, 'url' => $url]));            
        }

        // If we don't have an authorization code then get one
        if ($code === '') {

            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();

            // Get the state generated for you and store it to the session.
            Session::put(OAuth2Client::activeModuleName() . 'oauth2state', $provider->getState());
        
            // Redirect the user to the authorization URL.
            return redirect($authorizationUrl);
        
        // Check given state against previously stored one to mitigate CSRF attack
        } elseif ($state === '' ||  !Session::has(OAuth2Client::activeModuleName() . 'oauth2state') || $state !== Session::get(OAuth2Client::activeModuleName() . 'oauth2state', '')) {
        
            if (Session::get(OAuth2Client::activeModuleName() . 'oauth2state', '') !== '') {
                Session::forget(OAuth2Client::activeModuleName() . 'oauth2state');
            }
        
            return $this->viewResponse(OAuth2Client::viewsNamespace() . '::alert', [
                'alert_type'   => OAuth2Client::ALERT_DANGER, 
                'module_name'  => $oauth2_client->title(),
                'text'         => I18N::translate('Invalid state in communication with authorization provider.'),
            ]);
        } else {        
            try {
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $code
                ]);
        
                // Using the access token, we can get the user data of the resource owner        
                $user = $provider->getUserData($accessToken);

            } catch (IdentityProviderException $e) {

                // Failed to get the access token or user details.
                return $this->viewResponse(OAuth2Client::viewsNamespace() . '::alert', [
                    'alert_type'   => OAuth2Client::ALERT_DANGER,
                    'module_name'  => $oauth2_client->title(),
                    'text'         => I18N::translate('Failed to get the access token or the user details from the authorization provider') . ': ' . $e->getMessage(),
            ]);
            }
        }

        $tree = Session::get(OAuth2Client::activeModuleName() . 'tree', null);
        $url  = Session::get(OAuth2Client::activeModuleName() . 'url', route(HomePage::class));
        
        //If no user name or email was retrieved from authorization provider, redirect to login page
        if ($user->userName() === '' OR $user->email() === '') {
            FlashMessages::addMessage(I18N::translate('No valid user account data received from authorizaton provider. Username or email missing.'), 'danger');
            return redirect(route(LoginPage::class, ['tree' => $tree, 'url' => $url]));
        }

        $user_name = $user->userName();

        //If no real name was received from authorization provider, the user name is chosen as default
        $real_name = $user->realName() !== '' ? $user->realName() : $user_name;

        //Reduce user data to max length allowed in the webtrees database
        $user_name = $this->resizeUserData('Username', $user_name, true);
        $real_name = $this->resizeUserData('Real name', $real_name, true);
        $email     = $this->resizeUserData('Email address', $user->email(), true);
        $password  = $this->resizeUserData('Password', $accessToken->getToken(), false);

        //If user does not exist already, redirect to registration page based on the authorization provider user data
        if ($this->user_service->findByIdentifier($user_name) === null) { 

            $title        = MoreI18N::xlate('Request a new user account');
            $show_caution = Site::getPreference('SHOW_REGISTER_CAUTION') === '1';

            //Check if registration is allowed
            if (Site::getPreference('USE_REGISTRATION_MODULE') !== '1') {
                throw new HttpNotFoundException();
            }
                
            return $this->viewResponse(OAuth2Client::viewsNamespace() . '::register-page', [
                'captcha'       => $this->captcha_service->createCaptcha(),
                'comments'      => I18N::translate('Automatic user registration after sign in with authorization provider'),
                'email'         => $email,
                'realname'      => $real_name,
                'show_caution'  => $show_caution,
                'title'         => $title,
                'tree'          => $tree instanceof Tree ? $tree->name() : null,
                'username'      => $user_name,
                'password'      => $password,
                'provider_name' => $provider_name,
            ]);
        }            

        //Login
        //Code from Fisharebest\Webtrees\Http\RequestHandlers\LoginAction
        try {
            if ($provider->getUserKeyInformation()['user_name'] === AbstractAuthoriationProvider::USER_DATA_PRIMARY_KEY) {
                $identifyer = $user_name;
            }
            else {
                $identifyer = $email;
            }

            $this->doLogin($identifyer);

            if (Auth::isAdmin() && $this->upgrade_service->isUpgradeAvailable()) {
                FlashMessages::addMessage(MoreI18N::xlate('A new version of webtrees is available.') . ' <a class="alert-link" href="' . e(route(UpgradeWizardPage::class)) . '">' . MoreI18N::xlate('Upgrade to webtrees %s.', '<span dir="ltr">' . $this->upgrade_service->latestVersion() . '</span>') . '</a>');
            }

            // Redirect to the target URL
            return redirect($url);
        } catch (Exception $ex) {
            // Failed to log in.
            FlashMessages::addMessage($ex->getMessage(), 'danger');

            return redirect(route(LoginPage::class, [
                'tree'     => $tree instanceof Tree ? $tree->name() : null,
                'url'      => $url,
            ]));
        }        
    }	

    /**
     * Log in, if we can. Throw an exception, if we can't.
     * Code from Fisharebest\Webtrees\Http\RequestHandlers\LoginAction
     *
     * @param string $identifyer
     *
     * @return void
     * @throws Exception
     */
    private function doLogin(string $identifyer): void
    {
        if ($_COOKIE === []) {
            Log::addAuthenticationLog('Login failed (no session cookies): ' . $identifyer);
            throw new Exception(MoreI18N::xlate('You cannot sign in because your browser does not accept cookies.'));
        }

        $user = $this->user_service->findByIdentifier($identifyer);

        if ($user === null) {
            Log::addAuthenticationLog('Login failed (no such user/email): ' . $identifyer);
            throw new Exception(MoreI18N::xlate('The username or password is incorrect.'));
        }

        if ($user->getPreference(UserInterface::PREF_IS_EMAIL_VERIFIED) !== '1') {
            Log::addAuthenticationLog('Login failed (not verified by user): ' . $identifyer);
            throw new Exception(MoreI18N::xlate('This account has not been verified. Please check your email for a verification message.'));
        }

        if ($user->getPreference(UserInterface::PREF_IS_ACCOUNT_APPROVED) !== '1') {
            Log::addAuthenticationLog('Login failed (not approved by admin): ' . $identifyer);
            throw new Exception(MoreI18N::xlate('This account has not been approved. Please wait for an administrator to approve it.'));
        }

        Auth::login($user);
        Log::addAuthenticationLog('Login: ' . Auth::user()->userName() . '/' . Auth::user()->realName());
        Auth::user()->setPreference(UserInterface::PREF_TIMESTAMP_ACTIVE, (string) time());

        //Save OAuth2 login status to user preferences
        $user->setPreference(OAuth2Client::USER_PREF_LOGIN_WITH_OAUTH2_PROVIDER, '1');

        Session::put('language', Auth::user()->getPreference(UserInterface::PREF_LANGUAGE));
        Session::put('theme', Auth::user()->getPreference(UserInterface::PREF_THEME));

        I18N::init(Auth::user()->getPreference(UserInterface::PREF_LANGUAGE));
    }

    /**
     * Reduce user data to the max size allowed in the webtrees database
     *
     * @param string $name                Name of the user data, e.g. user_name, email, ...
     * @param string $value               Value of the user data
     * @param bool   $add_flash_message   Whether to add a flash message
     *
     * @return string
     */
    private function resizeUserData(string $name, string $value, bool $add_flash_message = false): string
    {
        if ($name === 'Username') {
            $length = 32;
        }
        elseif ($name === 'Password') {
            $length = 128;
        }
        else {
            $length = 64;
        }

        if ($add_flash_message && Strlen($value) > $length) {
            FlashMessages::addMessage(I18N::translate('The length of the "%s" exceeded the maximum length of %s and was reduced to %s characters.', MoreI18N::xlate($name), $length, $length));
        }

        return substr($value, 0, $length);
    }
}
