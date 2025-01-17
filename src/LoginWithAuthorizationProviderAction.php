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
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\DB;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\Http\RequestHandlers\HomePage;
use Fisharebest\Webtrees\Http\RequestHandlers\LoginPage;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\Http\RequestHandlers\UpgradeWizardPage;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\Services\CaptchaService;
use Fisharebest\Webtrees\Services\UpgradeService;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\User;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Services\ModuleService;
use Illuminate\Database\Query\JoinClause;
use Jefferson49\Webtrees\Helpers\Functions;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Log\CustomModuleLog;
use Jefferson49\Webtrees\Module\OAuth2Client\Factories\AuthorizationProviderFactory;
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
        $tree          = Validator::attributes($request)->treeOptional();
        $url           = Validator::queryParams($request)->isLocalUrl()->string('url', route(HomePage::class));

        //Save or load the provider name to the session
        if ($provider_name !== '') {
            Session::put(OAuth2Client::activeModuleName() . 'provider_name', $provider_name);
            Session::put(OAuth2Client::activeModuleName() . 'url', $url);
            Session::put(OAuth2Client::activeModuleName() . 'tree', $tree);
            $retreived_provider_name_from_session = false;
        }
        else {
            $provider_name = Session::get(OAuth2Client::activeModuleName() . 'provider_name');
            $tree          = Session::get(OAuth2Client::activeModuleName() . 'tree', null);
            $url           = Session::get(OAuth2Client::activeModuleName() . 'url', route(HomePage::class));
            $retreived_provider_name_from_session = true;
        }

        $oauth2_client = $this->module_service->findByName(OAuth2Client::activeModuleName());
        $log_module = Functions::moduleLogInterface($oauth2_client);

        $user = null;

        $provider = (new AuthorizationProviderFactory())::make($provider_name, OAuth2Client::getRedirectUrl());

        //Check if requested provider is available
        if ($provider === null) {
            FlashMessages::addMessage(I18N::translate('The requested authorization provider could not be found') . ': ' . $provider_name, 'danger');
            return redirect(route(LoginPage::class, ['tree' => $tree, 'url' => $url]));            
        }
        if (!$retreived_provider_name_from_session) {
            CustomModuleLog::addDebugLog($log_module, 'Found the requested authorization provider' . ': ' . $provider_name);
        }

        //Validate the requested provider
        $validation_result = $provider->validate();
        if ($validation_result !== '') {
            FlashMessages::addMessage($validation_result, 'danger');
            return redirect(route(LoginPage::class, ['tree' => $tree, 'url' => $url]));            
        }
        elseif (!$retreived_provider_name_from_session) {
            CustomModuleLog::addDebugLog($log_module, 'Successfully validated the requested authorization provider' . ': ' . $provider_name);
        }

        // If we don't have an authorization code then get one
        if ($code === '') {

            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();
            CustomModuleLog::addDebugLog($log_module, 'Received authorization URL' . ': ' . $authorizationUrl);

            // Get the state generated for you and store it to the session.
            Session::put(OAuth2Client::activeModuleName() . 'oauth2state', $provider->getState());
        
            // Redirect the user to the authorization URL.
            CustomModuleLog::addDebugLog($log_module, 'Redirecting to authorization URL');
            return redirect($authorizationUrl);
        
        // Check given state against previously stored one to mitigate CSRF attack
        } elseif ($state === '' ||  !Session::has(OAuth2Client::activeModuleName() . 'oauth2state') || $state !== Session::get(OAuth2Client::activeModuleName() . 'oauth2state', '')) {
        
            if (Session::get(OAuth2Client::activeModuleName() . 'oauth2state', '') !== '') {
                Session::forget(OAuth2Client::activeModuleName() . 'oauth2state');
            }
        
            return $this->viewResponse(OAuth2Client::viewsNamespace() . '::alert', [
                'title'        => I18N::translate('OAuth 2.0 communication error'),
                'tree'          => $tree instanceof Tree ? $tree->name() : null,
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
                CustomModuleLog::addDebugLog($log_module, 'Received accesss token from authorization provider' . ': ' . $code);
        
                // Using the access token, we can get the user data of the resource owner
                $user_data_from_provider = $provider->getUserData($accessToken);
                CustomModuleLog::addDebugLog($log_module, 'Received user data from authorzation provider' . ': ' . json_encode([
                    'user_name' => $user_data_from_provider->userName(),
                    'real_name' => $user_data_from_provider->realName(),
                    'email'     => $user_data_from_provider->email(),
                    ]));

            } catch (IdentityProviderException $e) {

                // Failed to get the access token or user details.
                CustomModuleLog::addDebugLog($log_module, 'Failed to get the access token or user details' . ': ' . $e->getMessage());

                return $this->viewResponse(OAuth2Client::viewsNamespace() . '::alert', [
                    'title'        => I18N::translate('OAuth 2.0 communication error'),
                    'tree'         => $tree instanceof Tree ? $tree->name() : null,
                    'alert_type'   => OAuth2Client::ALERT_DANGER,
                    'module_name'  => $oauth2_client->title(),
                    'text'         => I18N::translate('Failed to get the access token or the user details from the authorization provider') . ': ' . $e->getMessage(),
            ]);
            }
        }

        //Reduce user data from provider to max length allowed in the webtrees database
        $user_name = $this->resizeUserData('Username', $user_data_from_provider->userName(), true);
        $real_name = $this->resizeUserData('Real name', $user_data_from_provider->realName(), true);
        $email     = $this->resizeUserData('Email address', $user_data_from_provider->email(), true);
        $password  = $this->resizeUserData('Password', $accessToken->getToken(), false);
        $authorization_provider_id = $user_data_from_provider->getAuthorizationProviderUserId();

        CustomModuleLog::addDebugLog($log_module, 'Adjusted user data from authorization provider to webtrees' . ': ' . json_encode([
                'authorization_provider_i' => $authorization_provider_id,
                'user_name'                => $user_name, 
                'real_name'                => $real_name,
                'email'                    => $email,
            ]));
        
        CustomModuleLog::addDebugLog($log_module, 'Determined email for webtrees login' . ': ' . $email);

        //If no email was retrieved from authorization provider, redirect to login page
        if ($email === '') {
            FlashMessages::addMessage(I18N::translate('Invalid user account data received from authorizaton provider. Email missing.'), 'danger');
            return redirect(route(LoginPage::class, ['tree' => $tree, 'url' => $url]));
        }

        //Check email
        $email_found = $this->user_service->findByEmail($email) !== null;

        //If user does not exist already, redirect to registration page based on the authorization provider user data
        if (!$email_found && $this->findUserByAuthorizationProviderId($provider_name, $authorization_provider_id) === null) {

            FlashMessages::addMessage(I18N::translate('Redirect to webtrees registration page') . ': ' . $email, 'success');
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
            $user = $this->doLogin($email, $provider_name, $authorization_provider_id, $log_module->getLogPrefix());

            //ToDo: Update the user with the data received from the provider
            $provider->updateUserData($user, $user_data_from_provider);

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
     * @param string $email                      Email address of user
     * @param string $provider_name              Name of the authorization provider
     * @param string $authorization_provider_id  User ID from the authorizationprovider
     * @param string $oauth_log_prefix           Prefix for OAuth2 login lohd
     *
     * @return void
     * @throws Exception
     * 
     * @return User                              The logged in user
     */
    private function doLogin(string $email, string $provider_name, string $authorization_provider_id, string $oauth_log_prefix): User
    {
        if ($_COOKIE === []) {
            Log::addAuthenticationLog('Login failed (no session cookies): ' . $provider_name . ' ' . $authorization_provider_id);
            throw new Exception(MoreI18N::xlate('You cannot sign in because your browser does not accept cookies.'));
        }

        //Try to get user by authorization provider id; otherwise try to get user by email
        $user = $this->findUserByAuthorizationProviderId($provider_name, $authorization_provider_id) ?? $this->user_service->findByEmail($email);

        if ($user === null) {
            Log::addAuthenticationLog('Login failed (no such user/email): ' . $provider_name . ' ' . $authorization_provider_id);
            throw new Exception(MoreI18N::xlate('The username or password is incorrect.'));
        }

        if ($user->getPreference(UserInterface::PREF_IS_EMAIL_VERIFIED) !== '1') {
            Log::addAuthenticationLog('Login failed (not verified by user): ' . $provider_name . ' ' . $authorization_provider_id);
            throw new Exception(MoreI18N::xlate('This account has not been verified. Please check your email for a verification message.'));
        }

        if ($user->getPreference(UserInterface::PREF_IS_ACCOUNT_APPROVED) !== '1') {
            Log::addAuthenticationLog('Login failed (not approved by admin): ' . $provider_name . ' ' . $authorization_provider_id);
            throw new Exception(MoreI18N::xlate('This account has not been approved. Please wait for an administrator to approve it.'));
        }

        //If user logs in with authorization provider for the first time
        //(i.e. preference for OAuth2 provider has not yet been set)
        if ($user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '') === '') {

            //If time stamp is different from 0 (i.e. user already logged in at least once before)
            if ($user->getPreference(UserInterface::PREF_TIMESTAMP_ACTIVE) !== '0') {
                Log::addAuthenticationLog($oauth_log_prefix . ': ' . 'Login denied. The provided username/email from the authorization provider is identical to an existing webtrees user, who has already logged in before: ' . $provider_name . ' ' . $authorization_provider_id);
                throw new Exception(I18N::translate('Login with the provided user credentials denied. The username or email provided from the authorization provider might already exist in webtrees.'));
            }
        }

        Auth::login($user);
        Log::addAuthenticationLog('Login: ' . Auth::user()->userName() . '/' . Auth::user()->realName());
        Auth::user()->setPreference(UserInterface::PREF_TIMESTAMP_ACTIVE, (string) time());

        //Save authorization provider data to user preferences
        $user->setPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, $provider_name);
        $user->setPreference(OAuth2Client::USER_PREF_ID_AT_PROVIDER, $authorization_provider_id);
        $user->setPreference(OAuth2Client::USER_PREF_EMAIL_AT_PROVIDER, $email);

        Session::put('language', Auth::user()->getPreference(UserInterface::PREF_LANGUAGE));
        Session::put('theme', Auth::user()->getPreference(UserInterface::PREF_THEME));

        I18N::init(Auth::user()->getPreference(UserInterface::PREF_LANGUAGE));

        return $user;
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

    /**
     * Find user by authorization provider ID
     *
     * @param string $provider_name              Name of the authorization provider
     * @param string $authorization_provider_id  User ID from the authorizationprovider
     *
     * @return User
     */
    private function findUserByAuthorizationProviderId(string $provider_name, string $authorization_provider_id): User|null
    {
        $users = Functions::getAllUsers();

        foreach($users as $user) {
    
            if (    $user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME)  === $provider_name
                &&  $user->getPreference(OAuth2Client::USER_PREF_ID_AT_PROVIDER) === $authorization_provider_id) {

                return $user;
            }
        }

        return null;
    }       
}
