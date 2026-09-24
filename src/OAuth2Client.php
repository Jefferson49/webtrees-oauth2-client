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

namespace Jefferson49\Webtrees\Module\OAuth2Client;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Html;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Module\ModuleMenuTrait;
use Fisharebest\Webtrees\Services\GedcomImportService;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Webtrees;
use Jefferson49\Webtrees\Authorization\Auth;
use Jefferson49\Webtrees\Helpers\ClassName;
use Jefferson49\Webtrees\Helpers\Configuration;
use Jefferson49\Webtrees\Helpers\Functions;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Log\CustomModuleLogInterface;
use Jefferson49\Webtrees\Module\ModuleCustomTrait;
use Jefferson49\Webtrees\Module\OAuth2Client\Factories\AuthorizationProviderFactory;
use Jefferson49\Webtrees\Module\OAuth2Client\LoginWithAuthorizationProviderAction;
use Jefferson49\Webtrees\Module\OAuth2Client\RequestHandlers\OAuth2Logout;
use Jefferson49\Webtrees\Module\OAuth2Client\RequestHandlers\RegisterWithProviderAction;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class OAuth2Client extends AbstractModule implements
	ModuleCustomInterface,
	ModuleConfigInterface,
    ModuleGlobalInterface,
    ModuleMenuInterface,
    CustomModuleLogInterface
{
    use ModuleCustomTrait;
    use ModuleConfigTrait;
    use ModuleGlobalTrait;
    use ModuleMenuTrait;

    //State of the OAuth2 session
    private $oauth2state;

    //A list of custom views, which are registered by the module
    private Collection $custom_view_list;

	//Custom module version
	public const CUSTOM_VERSION = '1.1.11';

    //Routes
	public const ROUTE_REDIRECT            = '/OAuth2Client';
    public const ROUTE_REGISTER_PROVIDER   = '/register-with-provider-action{/tree}';
	public const ROUTE_OAUTH2_LOGOUT       = '/oauth2-logout';

	//Github
	public const GITHUB_REPO = 'Jefferson49/webtrees-oauth2-client';

	//Author of custom module
	public const CUSTOM_AUTHOR = 'Markus Hemprich';

    //Prefences, Settings
	public const PREF_MODULE_VERSION = 'module_version';

	//Alert tpyes
	public const ALERT_DANGER  = 'alert_danger';
	public const ALERT_SUCCESS = 'alert_success';

    //Preferences
    public const PREF_SHOW_WEBTREES_LOGIN_IN_MENU   = 'show_webtrees_login_in_menu';
    public const PREF_SHOW_REGISTER_IN_MENU         = 'show_register_in_menu';
    public const PREF_SHOW_MY_ACCOUNT_IN_MENU       = 'show_my_account_in_menu';
    public const PREF_DONT_SHOW_WEBTREES_LOGIN_MENU = 'dont_show_webtrees_login_menu';
    public const PREF_DEBUGGING_ACTIVATED           = 'debugging_activated';
    public const PREF_USE_WEBTREES_PASSWORD         = 'use_webtrees_password';
    public const PREF_SYNC_PROVIDER_EMAIL           = 'sync_provider_email';
    public const PREF_CONNECT_WITH_PROVIDERS        = 'connect_with_providers';
    public const PREF_HIDE_WEBTREES_SIGN_IN         = 'hide_webtrees_sign_in';
    public const PREF_PRETTY_REDIRECT_URL           = 'pretty_redirect_url';

    //User preferences
    public const USER_PREF_PROVIDER_NAME     = 'provider_name';
    public const USER_PREF_ID_AT_PROVIDER    = 'id_at_provider';
    public const USER_PREF_EMAIL_AT_PROVIDER = 'email_at_provider';

    //Session values
    public const SESSION_PROVIDER_NAME       = 'session_provider_name';
    public const SESSION_TREE                = 'session_tree';
    public const SESSION_URL                 = 'session_url';
    public const SESSION_PROVIDER_TO_CONNECT = 'session_provider_to_connect';
    public const SESSION_USER_TO_CONNECT     = 'session_user_to_connect';
    public const SESSION_CONNECT_TIMESTAMP   = 'session_connect_timestamp';
    public const SESSION_CONNECT_ACTION      = 'session_connect_action';
    public const SESSION_CONNECT_TIMEOUT     = 300;

    //Connect actions
    public const CONNECT_ACTION_NONE         = 'connect_action_none';
    public const CONNECT_ACTION_CONNECT      = 'connect_action_connect';
    public const CONNECT_ACTION_DISCONNECT   = 'connect_action_disconnect';
    public const CONNECT_ACTION_REGISTER     = 'connect_action_register';


    /**
     * Constructor
     */
    public function __construct()
    {
        //Caution: Do not use the shared library jefferson47/webtrees-common within __construct(),
        //         because it might result in wrong autoload behavior
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::boot()
     */
    public function boot(): void
    {
        //Register this class in the webtrees container
        //This allows to access the module instance from other places, e.g. views/scripts (->assetUrl)
        Functions::registerInContainer(self::class, $this);

        //Check update of module version
        $this->checkModuleVersionUpdate();

        //Initialize custom view list
        $this->custom_view_list = new Collection;

		// Register a namespace for the views.
		View::registerNamespace(self::viewsNamespace(), $this->resourcesFolder() . 'views/');

        //Register a custom view for the login page
        View::registerCustomView(View::NAMESPACE_SEPARATOR . 'login-page', self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'login-page');
        $this->custom_view_list->add(self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'login-page');

        //Register a custom view for the registration page
        View::registerCustomView(View::NAMESPACE_SEPARATOR . 'register-page', self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'register-page');
        $this->custom_view_list->add(self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'register-page');

        //Register a custom view for the edit account page
        View::registerCustomView(View::NAMESPACE_SEPARATOR . 'edit-account-page', self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'edit-account-page');
        $this->custom_view_list->add(self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'edit-account-page');

        //Register a custom view for the password request page
        View::registerCustomView(View::NAMESPACE_SEPARATOR . 'password-request-page', self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'password-request-page');
        $this->custom_view_list->add(self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'password-request-page');

        //Register a custom view for the password reset page
        View::registerCustomView(View::NAMESPACE_SEPARATOR . 'password-reset-page', self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'password-reset-page');
        $this->custom_view_list->add(self::viewsNamespace() . View::NAMESPACE_SEPARATOR . 'password-reset-page');

        //Register the routes for the custom module
        Functions::registerRoute(self::ROUTE_REDIRECT, LoginWithAuthorizationProviderAction::class);
        Functions::registerRoute(self::ROUTE_REGISTER_PROVIDER, RegisterWithProviderAction::class);
        Functions::registerRoute(self::ROUTE_OAUTH2_LOGOUT, OAuth2Logout::class);
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::title()
     */
    public function title(): string
    {
        return I18N::translate('OAuth2 Client');
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::description()
     */
    public function description(): string
    {
        /* I18N: Description of the “AncestorsChart” module */
        return I18N::translate('A custom module to implement a OAuth2 client for webtrees.');
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleGlobalInterface::headContent()
     */
    public function headContent(): string
    {
        //Include CSS file in head of webtrees HTML to make sure it is always found
        $css = '<link href="' . $this->assetUrl('css/oauth2-client.css') . '" type="text/css" rel="stylesheet" />';
        $hide_login_logout_menu_css = '<link href="' . $this->assetUrl('css/hide-login-logout-menu.css') . '" type="text/css" rel="stylesheet" />';

        //If option to hide webtrees login menu is activated, add css to hide the related classes with "display: none"
        if (boolval($this->getPreference(self::PREF_DONT_SHOW_WEBTREES_LOGIN_MENU, '0'))) {
            $css .= "\n" . $hide_login_logout_menu_css;
        }

        return $css;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleMenuInterface::getMenu()
     */
    public function getMenu(Tree $tree): ?Menu
    {
        $url = route(ClassName::get(ClassName::HOME_PAGE));
        $theme = Session::get('theme');
        $menu_title_shown = in_array($theme, ['webtrees', 'minimal', 'xenea', 'fab', 'rural', '_myartjaub_ruraltheme_', '_jc-theme-justlight_']);
        $tree_name = $tree instanceof Tree ? $tree->name() : null;
        $submenus = [];

        //If no user is logged in
        if (!Auth::check()) {

            $menu_label = MoreI18N::xlate('Sign in');

            //Add webtrees sign in menu as submenu item, if preference is activated
            if (boolval($this->getPreference(self::PREF_SHOW_WEBTREES_LOGIN_IN_MENU, '1'))) {
                $submenus[] = new Menu(
                    MoreI18N::xlate('Sign in'),
                    route(ClassName::get(ClassName::LOGIN_PAGE) , [
                        'tree' => $tree_name,
                        'url'  => $url,
                    ]),
                    'menu-oauth2-client-item',
                    ['rel' => 'nofollow']
                );
            }

            //Add submenu items to sign in with authorization providers
            $sign_in_button_labels = AuthorizationProviderFactory::getSignInButtonLabels();

            foreach ($sign_in_button_labels as $provider_name => $sign_in_button_label) {

                $submenus[] = new Menu(
                    I18N::translate('Sign in with') . ' ' . $sign_in_button_label,
                    route(LoginWithAuthorizationProviderAction::class, [
                        'tree'          => $tree instanceof Tree ? $tree->name() : null,
                        'url'           => $url,
                        'provider_name' => $provider_name,
                    ]),
                    'menu-oauth2-client-item',
                    ['rel' => 'nofollow']
                );
            }
        }
        //If an user is already logged in
        else {

            $user = Auth::user();
            $provider_name = $user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '');
            $provider_options = AuthorizationProviderFactory::getProviderOptions($provider_name);
            $post_signout_url = $provider_options['postSignoutURI'] ?? null;
            $menu_label = $user->realName();

            //If user is connected with an authorization provider and has a sign out URL, add OAuth2 sign out as submenu item
            if ($provider_name !== '' && $post_signout_url !== null) {

                // Add sign out from provider
                $submenus[] = new Menu(
                    I18N::translate('Sign out of') . ' ' . $provider_name,
                    route(OAuth2Logout::class, [
                        'provider_name' => $provider_name,
                    ]),
                    'menu-oauth2-client-item',
                    ['rel' => 'nofollow']
                );
            }
            //Add sign out as submenu item
            else {
                $parameters = [
                    'data-wt-post-url'   => route(ClassName::get(ClassName::LOGOUT_PAGE)),
                    'data-wt-reload-url' => route(ClassName::get(ClassName::HOME_PAGE))
                ];
                $submenus[] = new Menu(MoreI18N::xlate('Sign out'), '#', 'menu-oauth2-client-item', $parameters);

            }

            //Add webtrees my account menu as submenu item, if preference is activated
            if (boolval($this->getPreference(self::PREF_SHOW_MY_ACCOUNT_IN_MENU, '1'))) {
                $submenus[] = new Menu(
                    MoreI18N::xlate('My account'),
                    route(ClassName::get(ClassName::ACCOUNT_EDIT), [
                        'tree' => $tree_name,
                        'user' => Auth::user()->id()
                    ]),
                    'menu-oauth2-client-item'
                );
            }

            //If user is connected with an authorization provider, offer disconnect
            if ($provider_name !== '') {
                $sub_menu_label = I18N::translate('Disconnect account from');
                $connect_action =  OAuth2Client::CONNECT_ACTION_DISCONNECT;
                $sign_in_button_labels = AuthorizationProviderFactory::getSignInButtonLabelsByUsers(new Collection([$user]));
            }
            //If user is not connected with an provider, offer to connect to all available providers
            else {
                $sub_menu_label = I18N::translate('Connect account with');
                $connect_action =  OAuth2Client::CONNECT_ACTION_CONNECT;
                $sign_in_button_labels = AuthorizationProviderFactory::getSignInButtonLabels();
            }

            //If users are allowed to connect/disconnect with providers, show submenu entries to connect or disconnect
            if (boolval($this->getPreference(OAuth2Client::PREF_CONNECT_WITH_PROVIDERS, '0'))) {
                foreach ($sign_in_button_labels as $provider_name => $sign_in_button_label) {

                    $submenus[] = new Menu(
                        $sub_menu_label . ' ' . $sign_in_button_label,
                        route(LoginWithAuthorizationProviderAction::class, [
                            'tree'            => $tree_name,
                            'url'             => $url,
                            'provider_name'   => $provider_name,
                            'user'            => $user !== null ? $user->id() : 0,
                            'connect_action'  => $connect_action,
                        ]),
                        'menu-oauth2-client-item',
                        ['rel' => 'nofollow']
                    );
                }
            }
        }

        //If no submenus
        if ((sizeof($submenus) === 0)) {

            //Dont show menu at all
            return null;
        }
        //If only one submenu item and theme shows menu titles, only show top menu with link
        elseif ((sizeof($submenus) === 1) && $menu_title_shown) {

            $menu = $submenus[0];
            $menu->setLabel($menu_label);
            $menu->setClass('menu-oauth2-client');

            return $menu;
        }
        //Show menu with submenus
        else {
            return new Menu($menu_label, '#', 'menu-oauth2-client' , ['rel' => 'nofollow'], $submenus);
        }
    }

    /**
     * Get the prefix for custom module specific logs
     *
     * @return string
     */
    public static function getLogPrefix() : string {
        return 'OAuth2 Client';
    }

    /**
     * Whether debugging is activated
     *
     * @return bool
     */
    public function debuggingActivated(): bool {
        return boolval($this->getPreference(self::PREF_DEBUGGING_ACTIVATED, '0'));
    }

    /**
     * View module settings in control panel
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->checkCustomViewAvailability();

        $this->layout = 'layouts/administration';

        $base_url              = Validator::attributes($request)->string('base_url');
        $pretty_urls           = Configuration::getConfigValue('rewrite_urls') === '1';
        $pretty_redirect_url   = boolval($this->getPreference(self::PREF_PRETTY_REDIRECT_URL, '0'));
        $redirect_url          = OAuth2Client::getRedirectUrl(true, $pretty_urls && $pretty_redirect_url);
        $modified_redirect_url = OAuth2Client::getRedirectUrl(true, $pretty_urls && $pretty_redirect_url) !== OAuth2Client::getRedirectUrl(false, $pretty_urls && $pretty_redirect_url);

        return $this->viewResponse(
            self::viewsNamespace() . '::settings',
            [
                'title'                                  => $this->title(),
                'base_url'                               => $base_url,
                'trees_with_hidden_menu'                 => $this->getTreeNamesWithHiddenCustomMenu(),
                'uses_https'                             => strpos(Strtoupper($base_url), 'HTTPS://') === false ? false : true,
                'pretty_urls'                            => $pretty_urls,
                'pretty_redirect_url'                    => $pretty_redirect_url,
                'redirect_url'                           => $redirect_url,
                'modified_redirect_url'                  => $modified_redirect_url,
                self::PREF_SHOW_WEBTREES_LOGIN_IN_MENU   => boolval($this->getPreference(self::PREF_SHOW_WEBTREES_LOGIN_IN_MENU, '1')),
                self::PREF_DONT_SHOW_WEBTREES_LOGIN_MENU => boolval($this->getPreference(self::PREF_DONT_SHOW_WEBTREES_LOGIN_MENU, '0')),
                self::PREF_HIDE_WEBTREES_SIGN_IN         => boolval($this->getPreference(self::PREF_HIDE_WEBTREES_SIGN_IN, '0')),
                self::PREF_DEBUGGING_ACTIVATED           => boolval($this->getPreference(self::PREF_DEBUGGING_ACTIVATED, '0')),
                self::PREF_USE_WEBTREES_PASSWORD         => boolval($this->getPreference(self::PREF_USE_WEBTREES_PASSWORD, '0')),
                self::PREF_SYNC_PROVIDER_EMAIL           => boolval($this->getPreference(self::PREF_SYNC_PROVIDER_EMAIL, '0')),
                self::PREF_CONNECT_WITH_PROVIDERS        => boolval($this->getPreference(self::PREF_CONNECT_WITH_PROVIDERS, '0')),
            ]
        );
    }

    /**
     * Save module settings after returning from control panel
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $save                          = Validator::parsedBody($request)->string('save', '');
        $show_webtrees_login_in_menu   = Validator::parsedBody($request)->boolean(self::PREF_SHOW_WEBTREES_LOGIN_IN_MENU, false);
        $dont_show_webtrees_login_menu = Validator::parsedBody($request)->boolean(self::PREF_DONT_SHOW_WEBTREES_LOGIN_MENU, false);
        $hide_webtrees_sign_in         = Validator::parsedBody($request)->boolean(self::PREF_HIDE_WEBTREES_SIGN_IN, false);
        $debugging_activated           = Validator::parsedBody($request)->boolean(self::PREF_DEBUGGING_ACTIVATED, false);
        $sync_provider_email           = Validator::parsedBody($request)->boolean(self::PREF_SYNC_PROVIDER_EMAIL, false);
        $use_webtrees_password         = Validator::parsedBody($request)->boolean(self::PREF_USE_WEBTREES_PASSWORD, false);
        $connect_with_providers        = Validator::parsedBody($request)->boolean(self::PREF_CONNECT_WITH_PROVIDERS, false);
        $pretty_redirect_url           = Validator::parsedBody($request)->boolean(self::PREF_PRETTY_REDIRECT_URL, false);

        //Save the received settings to the user preferences
        if ($save === '1') {
			$this->setPreference(self::PREF_SHOW_WEBTREES_LOGIN_IN_MENU, $show_webtrees_login_in_menu ? '1' : '0');
			$this->setPreference(self::PREF_DONT_SHOW_WEBTREES_LOGIN_MENU, $dont_show_webtrees_login_menu ? '1' : '0');
			$this->setPreference(self::PREF_HIDE_WEBTREES_SIGN_IN, $hide_webtrees_sign_in ? '1' : '0');
			$this->setPreference(self::PREF_DEBUGGING_ACTIVATED, $debugging_activated ? '1' : '0');
			$this->setPreference(self::PREF_USE_WEBTREES_PASSWORD, $use_webtrees_password ? '1' : '0');
			$this->setPreference(self::PREF_SYNC_PROVIDER_EMAIL, $sync_provider_email ? '1' : '0');
			$this->setPreference(self::PREF_CONNECT_WITH_PROVIDERS, $connect_with_providers ? '1' : '0');
			$this->setPreference(self::PREF_PRETTY_REDIRECT_URL, $pretty_redirect_url ? '1' : '0');
        }

        //Finally, show a success message
        $message = I18N::translate('The preferences for the module "%s" were updated.', $this->title());
        FlashMessages::addMessage($message, 'success');

        return redirect($this->getConfigLink());
    }

    /**
     * Check if module version is new and start update activities if needed
     *
     * @return void
     */
    public function checkModuleVersionUpdate(): void
    {
        $updated = false;

        // Update custom module version if changed
        if($this->getPreference(self::PREF_MODULE_VERSION, '') !== self::CUSTOM_VERSION) {

            // Warning message if updating from 1.0.x versions
            if (version_compare($this->getPreference(self::PREF_MODULE_VERSION, ''), '1.1.0' , '<=')) {

                $message = I18N::translate('The redirect URL for OAuth 2.0 communication has changed in custom module versions >= 1.1.0. If certain connections with authorization providers fail, you might need to update the authorization provider settings with the new redirect URL.');
                FlashMessages::addMessage($message, 'warning');
            }

            //Update module files
            if (require __DIR__ . '/../update_module_files.php') {
                $this->setPreference(self::PREF_MODULE_VERSION, self::CUSTOM_VERSION);
                $updated = true;
            }
        }

        if ($updated) {
            //Show flash message for update of preferences
            $message = I18N::translate('The preferences for the custom module "%s" were sucessfully updated to the new module version %s.', $this->title(), self::CUSTOM_VERSION);
            FlashMessages::addMessage($message, 'success');
        }
    }

    /**
     * Get the redirection URL for OAuth2 clients
     *
     * @param bool $replace_encodings  Whether to replace precent encodings
     * @param bool $pretty_url         Whether to provide a pretty URL
     *
     *
     * @return string
     */
    public static function getRedirectUrl(bool $replace_encodings = true, bool $pretty_url = false) : string {

        $request     = Functions::getFromContainer(ServerRequestInterface::class);
        $base_url    = Validator::attributes($request)->string('base_url');

        if ($pretty_url) {
            $redirectUrl = $base_url . self::ROUTE_REDIRECT;
        }
        else {
            $path        = version_compare(Webtrees::VERSION, '2.3', '>=') ? '' : parse_url($base_url, PHP_URL_PATH) ?? '';
            $parameters  = ['route' => $path];
            $url         = $base_url . '/index.php';
            $redirectUrl = Html::url($url, $parameters) . self::ROUTE_REDIRECT;
        }

        //Replace %2F in URL, because some providers do not accept it, e.g. Dropbox
        if ($replace_encodings) {
            $redirectUrl = self::replacePercentEncodings($redirectUrl);
        }

        return $redirectUrl;
    }

    /**
     * Replaces percent encodings (default %2F) in URLs
     *
     * @param string   url
     *
     * @return string  converted url
     */
    public static function replacePercentEncodings(string $redirectUrl, array $percent_encodings = ['%2F' => '/']) : string {

        $redirectUrl = str_replace('%2F', '/', $redirectUrl);

        return $redirectUrl;
    }


    /**
     * Get the names of all trees, where the custom menu is hidden
     *
     * @return array[string]
     */
    public function getTreeNamesWithHiddenCustomMenu(): array {

        $tree_service = new TreeService(new GedcomImportService());
        $trees_with_hidden_menus = [];

        foreach ($tree_service->all() as $tree) {
            if ($this->accessLevel($tree, ModuleMenuInterface::class) !== Auth::PRIV_PRIVATE) {
                $trees_with_hidden_menus[] = $tree->name();
            }
        }

        return $trees_with_hidden_menus;
    }
}
