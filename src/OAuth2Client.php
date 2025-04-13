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

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Html;
use Fisharebest\Webtrees\Http\RequestHandlers\AccountEdit;
use Fisharebest\Webtrees\Http\RequestHandlers\HomePage;
use Fisharebest\Webtrees\Http\RequestHandlers\LoginPage;
use Fisharebest\Webtrees\Http\RequestHandlers\Logout;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Module\ModuleMenuTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\GedcomImportService;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Jefferson49\Webtrees\Helpers\Functions;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Log\CustomModuleLogInterface;
use Jefferson49\Webtrees\Module\OAuth2Client\Factories\AuthorizationProviderFactory;
use Jefferson49\Webtrees\Module\OAuth2Client\LoginWithAuthorizationProviderAction;
use Jefferson49\Webtrees\Module\OAuth2Client\RequestHandlers\RegisterWithProviderAction;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function substr;


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
	public const CUSTOM_VERSION = '1.1.4';

    //Routes
	public const REDIRECT_ROUTE = '/OAuth2Client';
	public const REGISTER_PROVIDER_ROUTE = '/register-with-provider-action{/tree}';

	//Github repository
	public const GITHUB_REPO = 'Jefferson49/webtrees-oauth2-client';

	//Github API URL to get the information about the latest releases
	public const GITHUB_API_LATEST_VERSION = 'https://api.github.com/repos/'. self::GITHUB_REPO . '/releases/latest';
	public const GITHUB_API_TAG_NAME_PREFIX = '"tag_name":"v';

	//Author of custom module
	public const CUSTOM_AUTHOR = 'Markus Hemprich';

    //Prefences, Settings
	public const PREF_MODULE_VERSION = 'module_version';

	//Alert tpyes
	public const ALERT_DANGER = 'alert_danger';
	public const ALERT_SUCCESS = 'alert_success';

    //Preferences
    public const PREF_SHOW_WEBTREES_LOGIN_IN_MENU   = 'show_webtrees_login_in_menu';
    public const PREF_SHOW_REGISTER_IN_MENU   = 'show_register_in_menu';
    public const PREF_SHOW_MY_ACCOUNT_IN_MENU   = 'show_my_account_in_menu';
    public const PREF_DONT_SHOW_WEBTREES_LOGIN_MENU = 'dont_show_webtrees_login_menu';
    public const PREF_DEBUGGING_ACTIVATED = 'debugging_activated';
    public const PREF_USE_WEBTREES_PASSWORD = 'use_webtrees_password';
    public const PREF_SYNC_PROVIDER_EMAIL = 'sync_provider_email';
    public const PREF_CONNECT_WITH_PROVIDERS = 'connect_with_providers';
    public const PREF_HIDE_WEBTREES_SIGN_IN = 'hide_webtrees_sign_in';

    //User preferences
    public const USER_PREF_PROVIDER_NAME = 'provider_name';
    public const USER_PREF_ID_AT_PROVIDER = 'id_at_provider';
    public const USER_PREF_EMAIL_AT_PROVIDER = 'email_at_provider';

    //Session values
    public const SESSION_PROVIDER_NAME = 'provider_name';
    public const SESSION_TREE = 'tree';
    public const SESSION_URL = 'url';
    public const SESSION_PROVIDER_TO_CONNECT = 'provider_to_connect';
    public const SESSION_USER_TO_CONNECT = 'user_to_connect';
    public const SESSION_CONNECT_TIMESTAMP = 'connect_timestamp';
    public const SESSION_CONNECT_TIMEOUT = 300;

    //Connect actions
    public const CONNECT_ACTION_NONE = 'connect_action_none';
    public const CONNECT_ACTION_CONNECT = 'connect_action_connect';
    public const CONNECT_ACTION_DISCONNECT = 'connect_action_disconnect';
    public const CONNECT_ACTION_REGISTER = 'connect_action_register';


   /**
     * OAuth2Client constructor.
     */
    public function __construct()
    {
        //Caution: Do not use the shared library jefferson47/webtrees-common within __construct(), 
        //         because it might result in wrong autoload behavior
    }

    /**
     * Initialization.
     *
     * @return void
     */
    public function boot(): void
    {              
        //Check update of module version
        $this->checkModuleVersionUpdate();

        //Initialize custom view list
        $this->custom_view_list = new Collection;

		// Register a namespace for the views.
		View::registerNamespace(self::viewsNamespace(), $this->resourcesFolder() . 'views/');

        //Register a custom view for the login page
        View::registerCustomView('::login-page', self::viewsNamespace() . '::login-page');
        $this->custom_view_list->add(self::viewsNamespace() . '::login-page');

        //Register a custom view for the registration page
        View::registerCustomView('::register-page', self::viewsNamespace() . '::register-page');
        $this->custom_view_list->add(self::viewsNamespace() . '::register-page');

        //Register a custom view for the edit account page
        View::registerCustomView('::edit-account-page', self::viewsNamespace() . '::edit-account-page');
        $this->custom_view_list->add(self::viewsNamespace() . '::edit-account-page');

        //Register a custom view for the password request page
        View::registerCustomView('::password-request-page', self::viewsNamespace() . '::password-request-page');
        $this->custom_view_list->add(self::viewsNamespace() . '::password-request-page');

        //Register a custom view for the password reset page
        View::registerCustomView('::password-reset-page', self::viewsNamespace() . '::password-reset-page');
        $this->custom_view_list->add(self::viewsNamespace() . '::password-reset-page');

        //Register a route for the communication with the authorization provider
        $router = Registry::routeFactory()->routeMap();                 
        $router
        ->get(LoginWithAuthorizationProviderAction::class, self::REDIRECT_ROUTE)
        ->allows(RequestMethodInterface::METHOD_POST);

        //Register a route for the RegisterWithProviderAction request handler
        $router = Registry::routeFactory()->routeMap();                 
        $router
        ->get(RegisterWithProviderAction::class, self::REGISTER_PROVIDER_ROUTE)
        ->allows(RequestMethodInterface::METHOD_POST);        
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
     * @see \Fisharebest\Webtrees\Module\AbstractModule::resourcesFolder()
     */
    public function resourcesFolder(): string
    {
        return dirname(__DIR__, 1) . '/resources/';
    }

    /**
     * Get the active module name, e.g. the name of the currently running module
     *
     * @return string
     */
    public static function activeModuleName(): string
    {
        return '_' . basename(dirname(__DIR__, 1)) . '_';
    }
    
    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleLatestVersion()
     */
    public function customModuleLatestVersion(): string
    {
        // No update URL provided.
        if (self::GITHUB_API_LATEST_VERSION === '') {
            return $this->customModuleVersion();
        }
        return Registry::cache()->file()->remember(
            $this->name() . '-latest-version',
            function (): string {
                try {
                    $client = new Client(
                        [
                        'timeout' => 3,
                        ]
                    );

                    $response = $client->get(self::GITHUB_API_LATEST_VERSION);

                    if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                        $content = $response->getBody()->getContents();
                        preg_match_all('/' . self::GITHUB_API_TAG_NAME_PREFIX . '\d+\.\d+\.\d+/', $content, $matches, PREG_OFFSET_CAPTURE);

						if(!empty($matches[0]))
						{
							$version = $matches[0][0][0];
							$version = substr($version, strlen(self::GITHUB_API_TAG_NAME_PREFIX));	
						}
						else
						{
							$version = $this->customModuleVersion();
						}

                        return $version;
                    }
                } catch (GuzzleException $ex) {
                    // Can't connect to the server?
                }

                return $this->customModuleVersion();
            },
            86400
        );
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/' . self::GITHUB_REPO;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $language
     *
     * @return array
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customTranslations()
     */
    public function customTranslations(string $language): array
    {
        $lang_dir   = $this->resourcesFolder() . 'lang/';
        $file       = $lang_dir . $language . '.mo';
        if (file_exists($file)) {
            return (new Translation($file))->asArray();
        } else {
            return [];
        }
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
        $url = route(HomePage::class);
        $theme = Session::get('theme');
        $menu_title_shown = in_array($theme, ['webtrees', 'minimal', 'xenea', 'fab', 'rural', '_myartjaub_ruraltheme_', '_jc-theme-justlight_']);
        $tree_name = $tree instanceof Tree ? $tree->name() : null;
        $submenus = [];

        //If no user is logged in
        if (!Auth::check()) {

            $menu_label = MoreI18N::xlate('Sign in');

            //Add webtrees sign in menu as submenu item, if preference is activated
            if (boolval($this->getPreference(self::PREF_SHOW_WEBTREES_LOGIN_IN_MENU, '1'))) {
                $submenus[] = new Menu(MoreI18N::xlate('Sign in'), route(LoginPage::class), 'menu-oauth2-client-item' , ['rel' => 'nofollow']);
            }

            //Add submenu items to sign in with authorization providers
            $sign_in_button_labels = AuthorizationProviderFactory::getSignInButtonLabels();

            foreach ($sign_in_button_labels as $provider_name => $sign_in_button_label) {

                $submenus[] = new Menu(I18N::translate('Sign in with') . ' ' . $sign_in_button_label, 
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
            $menu_label = $user->realName();
    
            //Add sign out as submenu item
            $parameters = [
                'data-wt-post-url'   => route(Logout::class),
                'data-wt-reload-url' => route(HomePage::class)
            ];
            $submenus[] = new Menu(MoreI18N::xlate('Sign out'), '#', 'menu-oauth2-client-item', $parameters);

            //Add webtrees my account menu as submenu item, if preference is activated
            if (boolval($this->getPreference(self::PREF_SHOW_MY_ACCOUNT_IN_MENU, '1'))) {
                $submenus[] = new Menu(MoreI18N::xlate('My account'), route(AccountEdit::class, ['tree' => $tree_name, 'user' => Auth::user()->id()]), 'menu-oauth2-client-item');
            }

            //If user is connected with an authorization provider, offer disconnect
            if ($user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '') !== '') {
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

                    $submenus[] = new Menu($sub_menu_label . ' ' . $sign_in_button_label, 
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
     * Get the namespace for the views
     *
     * @return string
     */
    public static function viewsNamespace(): string
    {
        return self::activeModuleName();
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

        return $this->viewResponse(
            self::viewsNamespace() . '::settings',
            [
                'title'                                  => $this->title(),
                'base_url'                               => Validator::attributes($request)->string('base_url'),
                'trees_with_hidden_menu'                 => $this->getTreeNamesWithHiddenCustomMenu(),
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

        //Save the received settings to the user preferences
        if ($save === '1') {
			$this->setPreference(self::PREF_SHOW_WEBTREES_LOGIN_IN_MENU, $show_webtrees_login_in_menu ? '1' : '0');
			$this->setPreference(self::PREF_DONT_SHOW_WEBTREES_LOGIN_MENU, $dont_show_webtrees_login_menu ? '1' : '0');
			$this->setPreference(self::PREF_HIDE_WEBTREES_SIGN_IN, $hide_webtrees_sign_in ? '1' : '0');
			$this->setPreference(self::PREF_DEBUGGING_ACTIVATED, $debugging_activated ? '1' : '0');
			$this->setPreference(self::PREF_USE_WEBTREES_PASSWORD, $use_webtrees_password ? '1' : '0');
			$this->setPreference(self::PREF_SYNC_PROVIDER_EMAIL, $sync_provider_email ? '1' : '0');
			$this->setPreference(self::PREF_CONNECT_WITH_PROVIDERS, $connect_with_providers ? '1' : '0');
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
     * Check availability of the registered custom views and show flash messages with warnings if any errors occur 
     *
     * @return void
     */
    private function checkCustomViewAvailability() : void {

        $module_service = new ModuleService();
        $custom_modules = $module_service->findByInterface(ModuleCustomInterface::class);
        $alternative_view_found = false;

        foreach($this->custom_view_list as $custom_view) {

            [[$namespace], $view_name] = explode(View::NAMESPACE_SEPARATOR, (string) $custom_view, 2);

            foreach($custom_modules->forget($this->activeModuleName()) as $custom_module) {

                $view = new View('test');

                try {
                    $file_name = $view->getFilenameForView($custom_module->name() . View::NAMESPACE_SEPARATOR . $view_name);
                    $alternative_view_found = true;
    
                    //If a view of one of the custom modules is found, which are known to use the same view
                    if (in_array($custom_module->name(), ['_jc-simple-media-display_', '_webtrees-simple-media-display_'])) {
                        
                        $message =  '<b>' . MoreI18N::xlate('Warning') . ':</b><br>' .
                                    I18N::translate('The custom module "%s" is activated in parallel to the %s custom module. This can lead to unintended behavior. If using the %s module, it is strongly recommended to deactivate the "%s" module, because the identical functionality is also integrated in the %s module.', 
                                    '<b>' . $custom_module->title() . '</b>', $this->title(), $this->title(), $custom_module->title(), $this->title());
                    }
                    else {
                        $message =  '<b>' . MoreI18N::xlate('Warning') . ':</b><br>' . 
                                    I18N::translate('The custom module "%s" is activated in parallel to the %s custom module. This can lead to unintended behavior, because both of the modules have registered the same custom view "%s". It is strongly recommended to deactivate one of the modules.', 
                                    '<b>' . $custom_module->title() . '</b>', $this->title(),  '<b>' . $view_name . '</b>');
                    }
                    FlashMessages::addMessage($message, 'danger');
                }    
                catch (RuntimeException $e) {
                    //If no file name (i.e. view) was found, do nothing
                }
            }
            if (!$alternative_view_found) {

                $view = new View('test');

                try {
                    $file_name = $view->getFilenameForView($view_name);

                    //Check if the view is registered with a file path other than the current module; e.g. another moduleS probably registered it with an unknown views namespace
                    if (mb_strpos($file_name, $this->resourcesFolder()) === false) {
                        throw new RuntimeException;
                    }
                }
                catch (RuntimeException $e) {
                    $message =  '<b>' . MoreI18N::xlate('Error') . ':</b><br>' .
                                I18N::translate(
                                    'The custom module view "%s" is not registered as replacement for the standard webtrees view. There might be another module installed, which registered the same custom view. This can lead to unintended behavior. It is strongly recommended to deactivate one of the modules. The path of the parallel view is: %s',
                                    '<b>' . $custom_view . '</b>', '<b>' . $file_name  . '</b>');
                    FlashMessages::addMessage($message, 'danger');
                }
            }
        }
        
        return;
    }   

    /**
     * Get the redirection URL for OAuth2 clients
     * 
     * @param string base_url           The webtrees base ULR (from config.ini.php)
     * @param bool   replace_encodings  Whether to replace precent encodings
     * 
     * @return string
     */
    public static function getRedirectUrl(bool $replace_encodings = true) : string {

        // Create an ugly URL for the route to the module as redirect URL
        // Note: Pretty URLs cannot be used, because they do not work with URL parameters
        $request     = Functions::getFromContainer(ServerRequestInterface::class);
        $base_url    = Validator::attributes($request)->string('base_url');
        $path        = parse_url($base_url, PHP_URL_PATH) ?? '';
        $parameters  = ['route' => $path];
        $url         = $base_url . '/index.php';

        $redirectUrl = Html::url($url, $parameters) . self::REDIRECT_ROUTE;

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
