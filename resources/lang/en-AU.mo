��    "      ,  /   <      �  :   �  i   4  G   �  E   �  �   ,  �   �  �   �  �  N  �   �  �  |  (  �	  �   &  R   �     �  �     �     ;   �  Y   �  :   T  ;   �    �  +   �  &     {   +  �   �  4  R  �   �  �   &      7   '  x   _  �  �  y   s  �  �  :   ~  i   �  G   #  E   k  �   �  �   ]  �     �  �  �   n  �    (  �   �   �!  R   0"     �"  �   �"  �   �#  ;   C$  Y   $  :   �$  ;   %    P%  *   ]&  &   �&  {   �&  �   +'  4  �'  �   )  �   �)    �*  7   �+  x   �+  �  \,  y   �-             !                                                                         
                       	      "                                           A custom module to implement a OAuth2 client for webtrees. A new webtrees user account is requested based on the user data received from the authorization provider. Allow existing webtrees users to connect with an authorization provider Automatic user registration after sign in with authorization provider By selecting this option, additional debug information about the protocol flow between webtrees and the authorization provider will be logged in the webtrees website logs. By selecting this option, it is possible to hide the original webtrees sign in menu. This might be helpful if the custom module specific top menu for sign in is used instead. By selecting/unselecting this option, it is possible to activate/deactivate the sign in menu entry for webtrees. If deactivated, only sign in menu entries for authorization providers might be shown. Currently, all menus to directly sign in with webtrees are deactivated. Therefore, only sign in with authorization providers is possible. Please be sure that you establish a webtrees administrator account with an authorization provider. Otherwise, administrator sign in is not possible any more. Hint: You can still access the original webtrees login page by typing the login route into the browser line, e.g.: Currently, the webtrees password cannot be reset for users, who sign in with an authorization provider. Please contact the webtrees administrator. Currently, webtrees does not use the HTTPS protocol. If signing in with authorization providers, it is urgently recommended to use HTTPS in order to encrypt the communication with the authorization provider. If your server supports the protocol, HTTPS can be activated by changing "base_url" in the webtrees "config.ini.php" file. Currently, "base_url" does not start with "https://". Error while trying to get an access token from the authorization provider. Check the setting for urlAccessToken in the webtrees configuration. Check the server access logs (or .htaccess configuration files) whether any 301 or 302 redirects are applied, which might convert POST into GET requests. Failed security check: A user, who is currently not signed in, requested to connect an authorization provider with the current user. Failed to get the access token or the user details from the authorization provider Hide the webtrees sign in menu If selecting this option, an existing webtrees user can connect the webtrees account with an authorization provider. This will allow users to additionally sign in with an authorization providers while still using the webtrees user and password. In this view, you can only request a webtrees password. The authorization provider password is managed by the authorization provider and cannot be changed within webtrees. Invalid state in communication with authorization provider. Invalid user account data received from authorizaton provider. Email or username missing. Invalid user data received from the authorization provider Keep email address synchronized with authorization provider Login with the provided user credentials denied. The username or email provided from the authorization provider might already exist in webtrees. If you want to connect an existing webtrees user to an authorization provider, select: My pages / My account / Connect with Show original webtrees sign in as menu item Sign in with an authorization provider The authorization provider "%s" is not available. Please check the configuration in the following file: data/config.ini.php The configuration for the authorization provider "%s" does not include data for the option "%s". Please check the configuration in the following file: data/config.ini.php The custom module view "%s" is not registered as replacement for the standard webtrees view. There might be another module installed, which registered the same custom view. This can lead to unintended behavior. It is strongly recommended to deactivate one of the modules. The path of the parallel view is: %s The identity received by the authorization provider cannot be connected to the requested user, because it is already used to sign in by another webtrees user. The redirect URL for OAuth 2.0 communication has changed in custom module versions >= 1.1.0. If certain connections with authorization providers fail, you might need to update the authorization provider settings with the new redirect URL. The request to reset the passwort belongs to a user, who can sign in with an authorization provider. In this view, you can only change the webtrees password. The authorization provider password is managed by the authorization provider and cannot be changed within webtrees. The requested authorization provider could not be found This option allows users, who register with an authorization provider, to additionally sign in with a webtrees password. This selection prevents a user with the same email address in webtrees and at the authorization provider from changing the email address in webtrees. If the email address at the authorization provider changes, the email address in webtrees will be updated during sign in. If the administrator changes the email address in the webtrees user management, the email address synchronization will no longer continue. Timeout for connecting user %s with authorization provider %s. Please restart connecting with the authorization provider. Project-Id-Version: OAuth2Client
PO-Revision-Date: 2025-01-25 11:31+0100
Last-Translator: Rick Malkin <rick@alineofmalkins.com>
Language-Team: 
Language: en_AU
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
Plural-Forms: nplurals=2; plural=(n != 1);
X-Generator: Poedit 3.5
X-Poedit-Basepath: ../..
X-Poedit-KeywordsList: translate
X-Poedit-SearchPath-0: .
 A custom module to implement a OAuth2 client for WebTrees. A new webtrees user account is requested based on the user data received from the authorisation provider. Allow existing webtrees users to connect with an authorisation provider Automatic user registration after sign in with authorisation provider By selecting this option, additional debug information about the protocol flow between webtrees and the authorisation provider will be logged in the webtrees website logs. By selecting this option, it is possible to hide the original WebTrees sign in menu. This might be helpful if the custom module specific top menu for sign in is used instead. By selecting/unselecting this option, it is possible to activate/deactivate the sign in menu entry for WebTrees. If deactivated, only sign in menu entries for authorisation providers might be shown. Currently, all menus to directly sign in with WebTrees are deactivated. Therefore, only sign in with authorisation providers is possible. Please be sure that you establish a WebTrees administrator account with an authorisation provider. Otherwise, administrator sign in is not possible any more. Hint: You can still access the original WebTrees login page by typing the login route into the browser line, e.g.: Currently, the webtrees password cannot be reset for users, who sign in with an authorisation provider. Please contact the webtrees administrator. Currently, WebTrees does not use the HTTPS protocol. If signing in with authorisation providers, it is urgently recommended to use HTTPS in order to encrypt the communication with the authorisation provider. If your server supports the protocol, HTTPS can be activated by changing "base_url" in the WebTrees "config.ini.php" file. Currently, "base_url" does not start with "https://". Error while trying to get an access token from the authorisation provider. Check the setting for urlAccessToken in the webtrees configuration. Check the server access logs (or .htaccess configuration files) whether any 301 or 302 redirects are applied, which might convert POST into GET requests. Failed security check: A user, who is currently not signed in, requested to connect an authorisation provider with the current user. Failed to get the access token or the user details from the authorisation provider Hide the WebTrees sign in menu If selecting this option, an existing webtrees user can connect the webtrees account with an authorisation provider. This will allow users to additionally sign in with an authorisation providers while still using the webtrees user and password. In this view, you can only request a webtrees password. The authorisation provider password is managed by the authorisation provider and cannot be changed within webtrees. Invalid state in communication with authorisation provider. Invalid user account data received from authorisaton provider. Email or username missing. Invalid user data received from the authorisation provider Keep email address synchronised with authorisation provider Login with the provided user credentials denied. The username or email provided from the authorisation provider might already exist in webtrees. If you want to connect an existing webtrees user to an authorisation provider, select: My pages / My account / Connect with Show a custom module specific sign in menu Sign in with an authorisation provider The authorisation provider "%s" is not available. Please check the configuration in the following file: data/config.ini.php The configuration for the authorisation provider "%s" does not include data for the option "%s". Please check the configuration in the following file: data/config.ini.php The custom module view "%s" is not registered as replacement for the standard WebTrees view. There might be another module installed, which registered the same custom view. This can lead to unintended behavior. It is strongly recommended to deactivate one of the modules. The path of the parallel view is: %s The identity received by the authorisation provider cannot be connected to the requested user, because it is already used to sign in by another webtrees user. The redirect URL for OAuth 2.0 communication has changed in custom module versions >= 1.1.0. If certain connections with authorisation providers fail, you might need to update the authorisation provider settings with the new redirect URL. The request to reset the passwort belongs to a user, who can sign in with an authorisation provider. In this view, you can only change the webtrees password. The authorisation provider password is managed by the authorisation provider and cannot be changed within webtrees. The requested authorisation provider could not be found This option allows users, who register with an authorisation provider, to additionally sign in with a webtrees password. This selection prevents a user with the same email address in webtrees and at the authorsation provider from changing the email address in webtrees. If the email address at the authorisation provider changes, the email address in webtrees will be updated during sign in. If the administrator changes the email address in the webtrees user management, the email address synchronisation will no longer continue. Timeout for connecting user %s with authorisation provider %s. Please restart connecting with the authorisation provider. 