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

namespace Jefferson49\Webtrees\Module\OAuth2Client;

use Exception;
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
use Fisharebest\Webtrees\Services\UpgradeService;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\User;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Services\ModuleService;
use Jefferson49\Webtrees\Helpers\Functions;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Log\CustomModuleLog;
use Jefferson49\Webtrees\Module\OAuth2Client\Contracts\AuthorizationProviderInterface;
use Jefferson49\Webtrees\Module\OAuth2Client\Factories\AuthorizationProviderFactory;
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
        $user            = Validator::attributes($request)->user();

        $tree_name       = Validator::queryParams($request)->string('tree', '');
        $code            = Validator::queryParams($request)->string('code', '');
        $state           = Validator::queryParams($request)->string('state', '');
        $provider_name   = Validator::queryParams($request)->string('provider_name', '');
        $url             = Validator::queryParams($request)->isLocalUrl()->string('url', route(HomePage::class));
        $connect_action  = Validator::queryParams($request)->string('connect_action', OAuth2Client::CONNECT_ACTION_NONE);

        $tree            = Functions::getTreeByName($tree_name);
        $oauth2_client   = $this->module_service->findByName(OAuth2Client::activeModuleName());
        $log_module      = Functions::moduleLogInterface($oauth2_client);

        //Save/load the provider name to/from the session
        if ($provider_name !== '') {
            Session::put(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_PROVIDER_NAME, $provider_name);
            Session::put(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_URL, $url);
            Session::put(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_TREE, $tree instanceof Tree ? $tree->name() : '');
            $retreived_provider_name_from_session = false;
        }
        else {
            $provider_name = Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_PROVIDER_NAME);
            $url           = Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_URL, route(HomePage::class));
            $tree_name     = Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_TREE, '');
            $tree          = Functions::getTreeByName($tree_name);
            $retreived_provider_name_from_session = true;
        }

        $provider = (new AuthorizationProviderFactory())::make($provider_name, OAuth2Client::getRedirectUrl());

        //Check if requested provider is available
        if ($provider === null) {
            FlashMessages::addMessage(I18N::translate('The requested authorization provider could not be found') . ': ' . $provider_name, 'danger');
            return redirect(route(LoginPage::class, ['tree' => $tree instanceof Tree ? $tree->name() : null, 'url' => $url]));            
        }
        if (!$retreived_provider_name_from_session) {
            CustomModuleLog::addDebugLog($log_module, 'Found the requested authorization provider' . ': ' . $provider_name);
        }        

        //If we shall disconnect a user from the provider
        if(     $connect_action === OAuth2Client::CONNECT_ACTION_DISCONNECT
            &&  $provider_name === $user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '')
            &&  $user === Auth::user()) {

            //Reset provider in the user preferences
            $user->setPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '');
            $user->setPreference(OAuth2Client::USER_PREF_ID_AT_PROVIDER, '');
            $user->setPreference(OAuth2Client::USER_PREF_EMAIL_AT_PROVIDER, '');

            $message = I18N::translate('Disconnected the user %s from provider: %s', $user->userName(), $provider->getSignInButtonLabel());
            FlashMessages::addMessage($message, 'success');
            CustomModuleLog::addDebugLog($log_module, $message);

            return redirect($url);    
        }
        //If we shall connect an existing user to a provider, remember provider in session
        elseif(     $connect_action === OAuth2Client::CONNECT_ACTION_CONNECT
                &&  $user === Auth::user()) {

            Session::put(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_PROVIDER_TO_CONNECT, $provider_name);
            Session::put(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_USER_TO_CONNECT, $user->id());
            Session::put(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_CONNECT_TIMESTAMP, time());

            CustomModuleLog::addDebugLog($log_module, 'Received a request to connect the user ' . $user->userName() . ' to provider: ' . $provider_name);
        }
        //If session contains a user to connect, which is different from the logged in user, reset session values
        elseif(     0 !== Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_USER_TO_CONNECT, 0)
                &&  Auth::id() !== Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_USER_TO_CONNECT, 0)) {

            self::deleteSessionValuesForProviderConnection();

            $message = I18N::translate('Failed security check: A user, who is currently not signed in, requested to connect an authorization provider with the current user.');
            FlashMessages::addMessage($message, 'danger');
            CustomModuleLog::addDebugLog($log_module, $message);

            return redirect($url);    
        }
        //If timeout for connect request, reset session values
        elseif(time() - OAuth2Client::SESSION_CONNECT_TIMEOUT > Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_CONNECT_TIMESTAMP, time())) {

            self::deleteSessionValuesForProviderConnection();

            $message = I18N::translate('Timeout for connecting user %s with authorization provider %s. Please restart connecting with the authorization provider.', $user->userName(), $provider->getSignInButtonLabel(),);
            FlashMessages::addMessage($message, 'danger');
            CustomModuleLog::addDebugLog($log_module, $message);

            return redirect($url);
        }

        // Start of main OAuth 2.0 process, from: 
        // If we don't have an authorization code then get one
        if ($code === '') {

            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();
            CustomModuleLog::addDebugLog($log_module, 'Received authorization URL' . ': ' . $authorizationUrl);

            // Get the state generated for you and store it to the session.
            Session::put(OAuth2Client::activeModuleName() . 'oauth2state', $provider->getState());

            // Save PKCE code to session (only relevant if PKCE is configured)
            Session::put(OAuth2Client::activeModuleName() . 'oauth2pkceCode', $provider->getPkceCode());
        
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
                'tree'         => $tree instanceof Tree ? $tree : null,
                'alert_type'   => OAuth2Client::ALERT_DANGER, 
                'module_name'  => $oauth2_client->title(),
                'text'         => I18N::translate('Invalid state in communication with authorization provider.'),
            ]);
        } else {        
            try {
                //Load PKCE code from session (only relevant if PKCE is configured)
                $provider->setPkceCode(Session::get(OAuth2Client::activeModuleName() . 'oauth2pkceCode', ''));

                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $code
                ]);
                CustomModuleLog::addDebugLog($log_module, 'Received accesss token from authorization provider' . ': ' . $code);
        
                // Using the access token, we can get the user data of the resource owner
                $user_data_from_provider = $provider->getUserData($accessToken);
                CustomModuleLog::addDebugLog($log_module, 'Received user data from authorization provider' . ': ' . json_encode([
                    'user_name' => $user_data_from_provider->userName(),
                    'real_name' => $user_data_from_provider->realName(),
                    'email'     => $user_data_from_provider->email(),
                    ]));

            } catch (Exception $e) {

                // Failed to get the access token or user details.
                $error_message = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
                CustomModuleLog::addDebugLog($log_module, 'Failed to get the access token or user details' . ': ' . $error_message);

                return $this->viewResponse(OAuth2Client::viewsNamespace() . '::alert', [
                    'title'        => I18N::translate('OAuth 2.0 communication error'),
                    'tree'         => $tree instanceof Tree ? $tree : null,
                    'alert_type'   => OAuth2Client::ALERT_DANGER,
                    'module_name'  => $oauth2_client->title(),
                    'text'         => I18N::translate('Failed to get the access token or the user details from the authorization provider') . ': ' . $error_message,
            ]);
            }
        }

        //Reduce user data from provider to max length allowed in the webtrees database
        $user_name = $this->resizeUserData('Username', $user_data_from_provider->userName(), true);
        $real_name = $this->resizeUserData('Real name', $user_data_from_provider->realName(), true);
        $email     = $this->resizeUserData('Email address', $user_data_from_provider->email(), true);
        $authorization_provider_id = $user_data_from_provider->getAuthorizationProviderUserId();

        CustomModuleLog::addDebugLog($log_module, 'Adjusted user data from authorization provider to webtrees' . ': ' . json_encode([
                'authorization_provider_id' => $authorization_provider_id,
                'user_name'                 => $user_name, 
                'real_name'                 => $real_name,
                'email'                     => $email,
            ]));

        $provider_to_connect = Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_PROVIDER_TO_CONNECT, '');
        $user_to_connect     = Session::get(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_USER_TO_CONNECT, 0);

        //Check if username/email already exists
        $existing_user = $this->user_service->findByEmail($email) ?? $this->user_service->findByUserName($user_name);

        //Check if the authorizatiohn provider ID is already connected with an user
        $provider_id_is_connected = $this->findUserByAuthorizationProviderId($provider, $authorization_provider_id) !== null;

        //Check if user has not signed in before (i.e. existing user, no provider name, no login timestamp)
        $existing_user_not_signed_in_yet = false;   
        if ($existing_user !== null && ($existing_user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '') === '') && ($existing_user->getPreference(UserInterface::PREF_TIMESTAMP_ACTIVE) === '0')) {
            $existing_user_not_signed_in_yet = true;
        }

        //If we shall connect an existing user to a provider   
        if($provider_to_connect === $provider_name && $user_to_connect !== 0) {

            //We do not connect an existing user who has not signed in yet, because it might have been registered based on an authorization provider (and not signed in yet)
            //We do not connect users with an authorization provider user ID, which is already connected to another user
            if ($existing_user_not_signed_in_yet OR $provider_id_is_connected) {
                $message = I18N::translate('The identity received by the authorization provider cannot be connected to the requested user, because it is already used to sign in by another webtrees user.');
                FlashMessages::addMessage($message, 'danger');
                CustomModuleLog::addDebugLog($log_module, $message);
                return redirect(route(LoginPage::class, ['tree' => $tree instanceof Tree ? $tree->name() : null, 'url' => $url]));
            }

            $user = $this->user_service->find($user_to_connect);
            $user->setPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, $provider_name);
            $user->setPreference(OAuth2Client::USER_PREF_ID_AT_PROVIDER, $authorization_provider_id);
            $user->setPreference(OAuth2Client::USER_PREF_EMAIL_AT_PROVIDER, $email);

            $message = I18N::translate('Sucessfully connected existing user %s with provider: %s', $user->userName(), $provider->getSignInButtonLabel());
            FlashMessages::addMessage($message, 'success');
            CustomModuleLog::addDebugLog($log_module, $message);

            //Reset session values
            self::deleteSessionValuesForProviderConnection();
        }
        //If user does not exist already and user is not connected already, register based on the authorization provider user data
        elseif ($existing_user === null && !$provider_id_is_connected) {

            //If user did not request to register (i.e. signed in and no account was found)
            if ($connect_action !== OAuth2Client::CONNECT_ACTION_REGISTER) {
                FlashMessages::addMessage(I18N::translate('Currently, no webtrees user account is related to the user data received from the authorization provider.'));
            }

            // If provider does not support registration, show messages and redirect to login page
            if (!$provider::supportsRegistration()) {
                FlashMessages::addMessage(I18N::translate('It is not possible to request a webtrees account with %s.', $provider->getSignInButtonLabel()));
                FlashMessages::addMessage(I18N::translate('To connect an existing user with %s, sign in and select: My pages / My account / Connect with', $provider->getSignInButtonLabel()));
                CustomModuleLog::addDebugLog($log_module, 'Provider does not support webtrees registration.');
                return redirect(route(LoginPage::class, ['tree' => $tree instanceof Tree ? $tree->name() : null, 'url' => $url]));
            }
            // If no email was retrieved from authorization provider, show messages and redirect to login page
            elseif ($email === '' OR $user_name === '') {
                FlashMessages::addMessage(I18N::translate('Invalid user data received from %s. Email or username missing.', $provider->getSignInButtonLabel()), 'danger');
                FlashMessages::addMessage(I18N::translate('To connect an existing user with %s, sign in and select: My pages / My account / Connect with', $provider->getSignInButtonLabel()));
                CustomModuleLog::addDebugLog($log_module, 'Invalid user account data received from authorizaton provider. Email or username missing.');
                return redirect(route(LoginPage::class, ['tree' => $tree instanceof Tree ? $tree->name() : null, 'url' => $url]));
            }
            else {
                //Check if registration is allowed
                if (Site::getPreference('USE_REGISTRATION_MODULE') !== '1') {
                    FlashMessages::addMessage(I18N::translate('Requesting a new webtrees user account is currently not allowed.'), 'danger');
                    throw new HttpNotFoundException();
                }

                FlashMessages::addMessage(I18N::translate('Press "continue" to request a new webtrees user acccount with %s.', $provider->getSignInButtonLabel()));
                FlashMessages::addMessage(I18N::translate('To connect an existing user with %s, sign in and select: My pages / My account / Connect with', $provider->getSignInButtonLabel()));
            }

            CustomModuleLog::addDebugLog($log_module, 'Forward to register with provider page');

            //Show register with provider page
            return $this->viewResponse(OAuth2Client::viewsNamespace() . '::register-with-provider-page', [
                'captcha'        => $this->captcha_service->createCaptcha(),
                'show_caution'   => Site::getPreference('SHOW_REGISTER_CAUTION') === '1',
                'title'          => I18N::translate('Request a new user account with an authorization provider'),
                'tree'           => $tree,
                'url'            => $url,
                'provider_name'  => $provider_name,
                'email'          => $email,
                'password_token' => $accessToken->getToken(),
                'real_name'      => $real_name,
                'user_name'      => $user_name,
                'comments'       => '',
            ]);
        }            

        //Login
        //Code from Fisharebest\Webtrees\Http\RequestHandlers\LoginAction
        try {
            $user = $this->doLogin($email, $provider, $authorization_provider_id, $log_module->getLogPrefix());            

            //Update email address if we have not just newly connected the user and email shall be synchronized with provider
            if (    $user_to_connect === 0
                &&  boolval($oauth2_client->getPreference(OAuth2Client::PREF_SYNC_PROVIDER_EMAIL, '0'))
                &&  $user->email() !== $email) {

                    $user->setEmail($email);
                    FlashMessages::addMessage(I18N::translate('The email address for user %s was updated to: %s', $user->userName(), $user->email()));
                    CustomModuleLog::addDebugLog($log_module, 'Updated email for user: ' . $user->userName());
            }

            if (Auth::isAdmin() && $this->upgrade_service->isUpgradeAvailable()) {
                FlashMessages::addMessage(MoreI18N::xlate('A new version of webtrees is available.') . ' <a class="alert-link" href="' . e(route(UpgradeWizardPage::class)) . '">' . MoreI18N::xlate('Upgrade to webtrees %s.', '<span dir="ltr">' . $this->upgrade_service->latestVersion() . '</span>') . '</a>');
            }

            // Redirect to the target URL
            return redirect($url);

        } catch (Exception $ex) {
            // Failed to log in.
            FlashMessages::addMessage($ex->getMessage(), 'danger');
            CustomModuleLog::addDebugLog($log_module, 'Failed to login: ' . $ex->getMessage());

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
     * @param string                         $email                      Email address of user
     * @param AuthorizationProviderInterface $provider                   The authorization provider
     * @param string                         $authorization_provider_id  User ID from the authorizationprovider
     * @param string                         $oauth_log_prefix           Prefix for OAuth2 login lohd
     *
     * @return void
     * @throws Exception
     * 
     * @return User                              The logged in user
     */
    private function doLogin(string $email, AuthorizationProviderInterface $provider, string $authorization_provider_id, string $oauth_log_prefix): User
    {
        if ($_COOKIE === []) {
            Log::addAuthenticationLog('Login failed (no session cookies): ' . $provider->getName() . ' ' . $authorization_provider_id);
            throw new Exception(MoreI18N::xlate('You cannot sign in because your browser does not accept cookies.'));
        }

        //Try to get user by authorization provider id; otherwise try to get user by email
        $user = $this->findUserByAuthorizationProviderId($provider, $authorization_provider_id) ?? $this->user_service->findByEmail($email);

        if ($user === null) {
            Log::addAuthenticationLog('Login failed (no such user/email): ' . $provider->getName() . ' ' . $authorization_provider_id);
            throw new Exception(MoreI18N::xlate('The username or password is incorrect.'));
        }

        if ($user->getPreference(UserInterface::PREF_IS_EMAIL_VERIFIED) !== '1') {
            Log::addAuthenticationLog('Login failed (not verified by user): ' . $provider->getName() . ' ' . $authorization_provider_id);
            throw new Exception(MoreI18N::xlate('This account has not been verified. Please check your email for a verification message.'));
        }

        if ($user->getPreference(UserInterface::PREF_IS_ACCOUNT_APPROVED) !== '1') {
            Log::addAuthenticationLog('Login failed (not approved by admin): ' . $provider->getName() . ' ' . $authorization_provider_id);
            throw new Exception(MoreI18N::xlate('This account has not been approved. Please wait for an administrator to approve it.'));
        }

        //If user logs in with authorization provider for the first time
        //(i.e. preference for OAuth2 provider has not yet been set)
        if ($user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '') === '') {

            //If time stamp is different from 0 (i.e. user already logged in at least once before)
            if ($user->getPreference(UserInterface::PREF_TIMESTAMP_ACTIVE) !== '0') {
                Log::addAuthenticationLog($oauth_log_prefix . ': ' . 'Login denied. The email address or username already exists: ' . $provider->getName() . ' ' . $authorization_provider_id);
                throw new Exception(I18N::translate('Login denied. The email address or username already exists.') . ' ' .
                                    I18N::translate('To connect an existing user with %s, sign in and select: My pages / My account / Connect with', $provider->getSignInButtonLabel()));
            }
        }
        //If user has authorization provider, but provider/ID does not match
        elseif (    ($user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '') !== $provider->getName()) 
                OR  ($user->getPreference(OAuth2Client::USER_PREF_ID_AT_PROVIDER, '') !== $authorization_provider_id)) {

                Log::addAuthenticationLog($oauth_log_prefix . ': ' . 'Login denied. The email address or username already exists: ' . $provider->getName() . ' ' . $authorization_provider_id);
                throw new Exception(I18N::translate('Login denied. The email address or username already exists.') . ' ' .
                                    I18N::translate('To connect an existing user with %s, sign in and select: My pages / My account / Connect with', $provider->getSignInButtonLabel()));
        }

        Auth::login($user);
        Log::addAuthenticationLog('Login: ' . Auth::user()->userName() . '/' . Auth::user()->realName());
        Auth::user()->setPreference(UserInterface::PREF_TIMESTAMP_ACTIVE, (string) time());

        //Save authorization provider data to user preferences
        $user->setPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, $provider->getName());
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
            //FlashMessages::addMessage(I18N::translate('The length of "%s" exceeded the maximum length of %s and was reduced to %s characters.', MoreI18N::xlate($name), $length, $length));
        }

        return substr($value, 0, $length);
    }

    /**
     * Find user by authorization provider ID
     *
     * @param AuthorizationProviderInterface $provider                   The authorization provider
     * @param string                         $authorization_provider_id  User ID from the authorizationprovider
     *
     * @return User
     */
    private function findUserByAuthorizationProviderId(AuthorizationProviderInterface $provider, string $authorization_provider_id): ?User
    {
        $users = Functions::getAllUsers();

        foreach($users as $user) {
    
            if (    $user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME)  === $provider->getName()
                &&  $user->getPreference(OAuth2Client::USER_PREF_ID_AT_PROVIDER) === $authorization_provider_id) {

                return $user;
            }
        }

        return null;
    }

    /**
     * Delete session values for provider connection
     *
     * @return void
     */
    private static function deleteSessionValuesForProviderConnection(): void
    {
        Session::forget(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_PROVIDER_TO_CONNECT);
        Session::forget(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_USER_TO_CONNECT);
        Session::forget(OAuth2Client::activeModuleName() . OAuth2Client::SESSION_CONNECT_TIMESTAMP);
    }
}
