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

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\User;
use Jefferson49\Webtrees\Module\OAuth2Client\AuthorizationProviderUser;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Exception;


/**
 * An abstract OAuth2 authorization client, which provides basic methods
 */
abstract class AbstractAuthorizationProvider
{
    //The authorization provider
    protected AbstractProvider $provider;
    
    //A label for the sign in button
    protected string $sign_in_button_label;

    public const USER_DATA_PRIMARY_KEY   = 'primary_key';
    public const USER_DATA_MANDATORY_KEY = 'mandatory_key';
    public const USER_DATA_OPTIONAL_KEY  = 'optional_key';
    public const USER_DATA_UNUSED_KEY    = 'unused_key';
   

    /**
     * Get the name of the authorization client
     * 
     * @return string
     */
    public static function getName() : string {

        $name_space = str_replace('\\\\', '\\',__NAMESPACE__ );
        $class_name = str_replace($name_space . '\\', '', static::class);
        return str_replace('AuthorizationProvider', '', $class_name);
    }    

    /**
     * Set the sing in button label for the authorization client
     * 
     * @param string $label
     * 
     * @return void
     */
    public function setSignInButtonLabel(string $label) : void {

        $this->sign_in_button_label = $label;
    }

    /**
     * Get the sign in button label for the authorization client
     * 
     * @return string
     */
    public function getSignInButtonLabel() : string {

        return $this->sign_in_button_label ?? $this->getName();
    }

    /**
     * Get the authorization URL
     *
     * @param  array $options
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(array $options = [])
    {
        return $this->provider->getAuthorizationUrl($options);
    }

    /**
     * Get the current value of the state parameter
     *
     * This can be accessed by the redirect handler during authorization.
     *
     * @return string
     */
    public function getState()
    {
        return $this->provider->getState();
    }    

    /**
     * Get an access token from the provider using a specified grant and option set.
     *
     * @param  mixed                $grant
     * @param  array<string, mixed> $options
     * @throws IdentityProviderException
     * @return AccessTokenInterface
     */
    public function getAccessToken($grant, array $options = [])
    {
        try {
            return $this->provider->getAccessToken($grant, $options);
        }
        catch (IdentityProviderException $e) {
            $message  = $e->getMessage();
            $response = $e->getResponseBody();
            $status_code = $response->getStatusCode();
            $reason_phrase = $response->getReasonPhrase();
            $error_text = ' Error message: ' . $message . ', Status code: ' .  $status_code . ', Reason phrase: ' . $reason_phrase . ' .';

            throw new IdentityProviderException($error_text . I18N::translate('Error while trying to get an access token from the authorization provider. Check the setting for urlAccessToken in the webtrees configuration. Check the server access logs (or .htaccess configuration files) whether any 301 or 302 redirects are applied, which might convert POST into GET requests.'), 0, 0);
        }
    }

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    public function getResourceOwner(AccessToken $token) : ResourceOwnerInterface
    {
        return $this->provider->getResourceOwner($token);
    }

    /**
     * Use access token to get user data from provider and return it as a webtrees User object
     * 
     * @param  AccessToken               $token
     * @throws IdentityProviderException
     * 
     * @return AuthorizationProviderUser
     */
    public function getUserData(AccessToken $token) : AuthorizationProviderUser {

        $resourceOwner = $this->provider->getResourceOwner($token);
        $user_data     = $resourceOwner->toArray();

        try {
            $authorization_provider_user_id = (string) $resourceOwner->getId();
        }
        catch (Exception $e) {
            throw new IdentityProviderException(I18N::translate('Invalid user data received from the authorization provider') . ': '. json_encode($user_data) . ' . ' . I18N::translate('Check the setting for urlResourceOwnerDetails in the webtrees configuration.'), 0, $user_data);
        }

        //Email: Default has to be empty, because empty email needs to be detected as error
        $email = $user_data['email'] ?? '';

        //User name: Default has to be empty, because empty username needs to be detected as error
        $user_name = $user_data['username'] ?? '';

        //Real name
        if(isset($user_data['name']) && is_string($user_data['name'])) {
            $real_name = $user_data['name'];
        }
        else {
            $real_name = '$user_name';
        }

        return new AuthorizationProviderUser(0, $user_name, $real_name, $email, $authorization_provider_user_id, $user_data, $resourceOwner);
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
        ];
    }

    /**
     * Returns an array with the webtrees user data keys, which defines if they are primary or mandatory
     * See class: Fisharebest\Webtrees\User
     *
     * @return array   An array with the webtrees user data keys. The value for a key defines if it is primary, mandatory, or optional
     */
    public static function getUserKeyInformation() : array {
        return [
            'user_name' => self::USER_DATA_MANDATORY_KEY,
            'real_name' => self::USER_DATA_OPTIONAL_KEY,
            'email'     => self::USER_DATA_PRIMARY_KEY,
        ];
    } 

    /**
     * Validate the provider
     *
     * @return string   Validation error; empty string if no error
     */
    public function validate() : string
    {
        //Validate the user data definition of the provider
        if (!in_array(AbstractAuthorizationProvider::USER_DATA_PRIMARY_KEY, static::getUserKeyInformation())) {
            return I18N::translate('Cannot use the sign in data of the authorization provider. No primary key defined for the user data.');
        }
        elseif (Array_count_values(static::getUserKeyInformation())[AbstractAuthorizationProvider::USER_DATA_PRIMARY_KEY] > 1) {
            return I18N::translate('Cannot use the sign in data of the authorization provider. More than one primary key defined for the user data.');
        }
        elseif (static::getUserKeyInformation()['user_name'] !== AbstractAuthorizationProvider::USER_DATA_PRIMARY_KEY
            &&  static::getUserKeyInformation()['email']     !== AbstractAuthorizationProvider::USER_DATA_PRIMARY_KEY) {

            return I18N::translate('Cannot use the sign in data of the authorization provider. Neither username nor email is a primary key.');
        }
 
        return '';
    }    

    /**
     * Update a webtrees user according to the user data received from the authorization provider
     *
     * @param User $user                     the user to be updated
     * @param User $user_data_from_provider  user data received from an authorization provider
     *
     * @return void
     */
    public static function updateUserData(User $user, User $user_data_from_provider): void {

        if ($user->email() !== $user_data_from_provider->email()) {
            $user->setEmail($user_data_from_provider->email());
        }
    }
}
