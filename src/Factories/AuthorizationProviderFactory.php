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

namespace Jefferson49\Webtrees\Module\OAuth2Client\Factories;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Webtrees;
use Jefferson49\Webtrees\Module\OAuth2Client\Contracts\AuthorizationProviderInterface;
use Jefferson49\Webtrees\Module\OAuth2Client\OAuth2Client;
use Illuminate\Support\Collection;

use ReflectionMethod;

use function file_exists;
use function parse_ini_file;


/**
 * Factory for an OAuth2 authorization provider with a defined interface for webtrees integration
 */
class AuthorizationProviderFactory
{
    /**
     * Create an OAuth2 authorization provider
     * 
     * @param string $name          name of the authorization provider
     * @param string $redirectUri   redirection URL from authorization provider to webtrees OAuth2 client
     * 
     * @return AuthorizationProviderInterface   A configured authorization provider. Null, if error 
     */
    public static function make(string $name, string $redirectUri) : ?AuthorizationProviderInterface
    {
        $name_space = str_replace('\\\\', '\\',__NAMESPACE__ );
        $name_space = str_replace('Factories', 'Provider\\', $name_space);
        $options = self::readProviderOptionsFromConfigFile($name);

        //If no options found
        if (sizeof($options) === 0) {
            return null;
        }

        $provider_names = self::getAuthorizatonProviderNames();

        foreach($provider_names as $class_name => $provider_name) {
            if ($provider_name === $name) {
                $class_name = $name_space . $class_name;
                return new $class_name($redirectUri, $options);
            }
        }

        //If no provider found
        return null;
    }

	/**
     * Return the names of all available authorization providers
     *
     * @return array array<class_name => provider_name>
     */ 

    public static function getAuthorizatonProviderNames(): array {

        $provider_names = [];
        $name_space = str_replace('\\\\', '\\',__NAMESPACE__ );
        $name_space_provider = str_replace('Factories', 'Provider\\', $name_space);
        $name_space_contracts = str_replace('Factories', 'Contracts\\', $name_space);

        foreach (get_declared_classes() as $class_name) { 
            if (strpos($class_name, $name_space_provider) !==  false) {
                if (in_array($name_space_contracts . 'AuthorizationProviderInterface', class_implements($class_name))) {
                    if (str_replace($name_space_provider, '',  $class_name) !== 'AbstractAuthorizationProvider') {
                        $reflectionMethod = new ReflectionMethod($class_name, 'getName');
                        $class_name = str_replace($name_space_provider, '', $class_name);
                        $provider_names[$class_name] = $reflectionMethod->invoke(null);    
                    }
                }
            }
        }

        return $provider_names;
    }

	/**
     * Read the options of the provider from the webtrees config.ini.php file
     * 
     * @param string $name  Authorization provider name
     * 
     * @return array        An array with the options. Empty if options could not be read completely.
     */ 

    public static function readProviderOptionsFromConfigFile(string $name): array {

        $options = [];
        $name_space = str_replace('\\\\', '\\',__NAMESPACE__ );
        $name_space_provider = str_replace('Factories', 'Provider\\', $name_space);
        $provider_names = self::getAuthorizatonProviderNames();

        foreach ($provider_names as $class_name => $provider_name) {
            if ($provider_name === $name) {
                $reflectionMethod = new ReflectionMethod($name_space_provider . $class_name, 'getRequiredOptions');
                $option_names = $reflectionMethod->invoke(null);
                break;
            }
        }

        // Read the configuration settings
        if (file_exists(Webtrees::CONFIG_FILE)) {
            $config = parse_ini_file(Webtrees::CONFIG_FILE);
            foreach ($config as $key => $value) {
                if (strpos($key, $name . '_') === 0) {
                    $key = str_replace($name . '_', '', $key);
                    $options[$key] = $value;
                }
            }
        }
        else {
            return [];
        }

        //Return if no options found, i.e. the authorization provider is not configured
        if (sizeof($options) === 0) {
            return [];
        }

        //Check if configuration is complete, i.e. contains all required options
        foreach ($option_names as $option_name) {
            if (!key_exists($option_name, $options)) {
                FlashMessages::addMessage(I18N::translate('The configuration for the authorization provider "%s" does not include data for the option "%s". Please check the configuration in the following file: data/config.ini.php', $provider_name, $option_name), 'danger');
                return [];
            }
        }

        return $options;
    }

	/**
     * Whether a provider provides enough user data for a webtrees registration, e.g. username and email
     * 
     * @param string $name  Authorization provider name
     * 
     * @return bool
     */ 

     public static function providerSupportsRegistration(string $name): bool {

        $options = [];
        $allowed = false;
        $name_space = str_replace('\\\\', '\\',__NAMESPACE__ );
        $name_space_provider = str_replace('Factories', 'Provider\\', $name_space);
        $provider_names = self::getAuthorizatonProviderNames();

        foreach ($provider_names as $class_name => $provider_name) {
            if ($provider_name === $name) {
                $reflectionMethod = new ReflectionMethod($name_space_provider . $class_name, 'supportsRegistration');
                $allowed = $reflectionMethod->invoke(null);
                break;
            }
        }

        return $allowed;
     }

	/**
     * Get the sign in button labels for all active authorization providers
     * 
     * @param bool $registration If true, only providers are included, which provide enough user data for a webtrees registration
     * 
     * @return array [provider_name => label]
     */ 

    public static function getSignInButtonLabels($registration = false): array {    

        $provider_names = self::getAuthorizatonProviderNames();

        $sign_in_button_labels = [];

        //Remove any providers from list, for which no sufficient config is available, or which do not allow webtrees registration
        foreach($provider_names as $class_name => $provider_name) {
            if(self::readProviderOptionsFromConfigFile($provider_name) === []) {
                unset($provider_names[$class_name]);
            }
            if($registration && !self::providerSupportsRegistration($provider_name)) {
                unset($provider_names[$class_name]);
            }
        }

        //Get sign in button labels for all providers
        foreach($provider_names as $class_name => $provider_name) {
            $provider = self::make($provider_name, '');
            $sign_in_button_labels[$provider_name] = $provider->getSignInButtonLabel();
        }

        //Alphabetically sort provider labels
        uasort($sign_in_button_labels, function (string $a, string $b) {
            return strcmp($a, $b);
        });

        return $sign_in_button_labels;
    }

	/**
     * Get sign in button labels for a set of users
     * 
     * @param Collection [User]  $users
     * @param bool               $registration If true, only providers are included, which provide enough user data for a webtrees registration
     * 
     * 
     * @return array [provider_name => label]
     */ 

    public static function getSignInButtonLabelsByUsers(Collection $users, $registration = false): array {

        $labels = self::getSignInButtonLabels($registration);
        $labels_for_users = [];
    
        foreach($users as $user) {
            foreach($labels as $provider_name => $label) {

                if($provider_name === $user->getPreference(OAuth2Client::USER_PREF_PROVIDER_NAME, '')) {
                    $labels_for_users[$provider_name] = $label;
                }
            }
        }

        return $labels_for_users;
    }
}
