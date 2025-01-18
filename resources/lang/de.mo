��    :      �  O   �      �  :   �  E   4  �   z  �   &  �   �  �   �  L        l  �  z  �   
  �   �
  �   �  �  �            
   +  (  6  R   _  8   �     �     �  �    �   �  �   d  ;     M   L  :   �  ;   �       �   #  M   �                .  &   I     p  *   ~     �  *   �  +   �       &   (     O  {   a  �   �  �   �  	  {  4  �  L   �  Z     a   b  1   �    �  7        @  !   ^  ?   �  h  �  `   )  M   �  �   �  �   �   �   n!  �   T"  W   �"  
   >#  �  I#  /  %  �   O&  '  '  �  /(     �)     �)     �)  u  �)  Z   m+  D   �+  	   ,  $   ,  �  <,  �   .  �   �.  H   �/  M   �/  >   0  =   X0     �0  �   �0  W   o1     �1     �1  !   �1  .   2     E2  5   W2  !   �2  ,   �2  E   �2     "3  )   /3     Y3  �   k3  �   �3    �4  %  �5  R  �6  Y   48  j   �8  l   �8  :   f9  F  �9  D   �:  )   -;  !   W;  H   y;        7                  '   !   2       $             8                                 5   "   .          #       )   %   *   	                                3   4               6   (            0   9   -                    
       :          &   /   1       ,                +        A custom module to implement a OAuth2 client for webtrees. Automatic user registration after sign in with authorization provider By selecting this option, additional debug information about the protocol flow between webtrees and the authorization provider will be logged in the webtrees website logs. By selecting this option, it is possible to hide the original webtrees sign in menu. This might be helpful if the custom module specific top menu for sign in is used instead. By selecting/unselecting this option, it is possible to activate/deactivate the sign in menu entry for webtrees. If deactivated, only sign in menu entries for authorization providers might be shown. By selecting/unselecting this option, it is possible to show/hide a custom module specific sign in menu in the webtrees main menu. Check the setting for urlResourceOwnerDetails in the webtrees configuration. Control panel Currently, all menus to directly sign in with webtrees are deactivated. Therefore, only sign in with authorization providers is possible. Please be sure that you establish a webtrees administrator account with an authorization provider. Otherwise, administrator sign in is not possible any more. Hint: You can still access the original webtrees login page by typing the login route into the browser line, e.g.: Currently, all sign in menus are deactivated/hidden. Please activate one of the sign in menus with the settings below. Hint: You can still access the original webtrees login page by typing the login route into the browser line, e.g.: Currently, the webtrees password cannot be reset for users, who sign in with an authorization provider. Please contact the webtrees administrator. Currently, the webtrees sign in menu is deactivated and the custom module sign in menu is hidden for the following trees: %s. Some of the users will not be able to sign in. Please consider to activate one of the sign in menus with the settings below. Currently, webtrees does not use the HTTPS protocol. If signing in with authorization providers, it is urgently recommended to use HTTPS in order to encrypt the communication with the authorization provider. If your server supports the protocol, HTTPS can be activated by changing "base_url" in the webtrees "config.ini.php" file. Currently, "base_url" does not start with "https://". Custom module Debug Settings Debug logs Error while trying to get an access token from the authorization provider. Check the setting for urlAccessToken in the webtrees configuration. Check the server access logs (or .htaccess configuration files) whether any 301 or 302 redirects are applied, which might convert POST into GET requests. Failed to get the access token or the user details from the authorization provider Further registration data - Please complete and continue Hide Hide the webtrees sign in menu If selecting this option, a user with the same email address between webtrees and the authorizaton provider will not be allowed to change the email address within webtrees. If the email address has changed at the authorization provider, the webtrees email address will be updated during sign in. If the administrator decides to change the email address in the webtrees user administration, the synchronization of the email address will be canceled. If selecting this option, a user, who can sign in with an authorization provider, will additionally be allowed to sign in with a webtrees password. In this view, you can only request a webtrees password. The authorization provider password is managed by the authorization provider and cannot be changed within webtrees. Invalid state in communication with authorization provider. Invalid user account data received from authorizaton provider. Email missing. Invalid user data received from the authorization provider Keep email address synchronized with authorization provider Keep synchronized Login with the provided user credentials denied. The username or email provided from the authorization provider might already exist in webtrees. Mandatory user account data from the authorization provider cannot be changed OAuth 2.0 communication error OAuth2 Client Password (within webtrees) Redirect to webtrees registration page Register with Settings for Password and Email Management Settings for Sign In Menus Show a custom module specific sign in menu Show original webtrees sign in as menu item Sign in with Sign in with an authorization provider Signed in with %s The authorization provider "%s" is not available. Please check the configuration in the following file: data/config.ini.php The configuration for the authorization provider "%s" does not include data for the option "%s". Please check the configuration in the following file: data/config.ini.php The custom module "%s" is activated in parallel to the %s custom module. This can lead to unintended behavior, because both of the modules have registered the same custom view "%s". It is strongly recommended to deactivate one of the modules. The custom module "%s" is activated in parallel to the %s custom module. This can lead to unintended behavior. If using the %s module, it is strongly recommended to deactivate the "%s" module, because the identical functionality is also integrated in the %s module. The custom module view "%s" is not registered as replacement for the standard webtrees view. There might be another module installed, which registered the same custom view. This can lead to unintended behavior. It is strongly recommended to deactivate one of the modules. The path of the parallel view is: %s The email address provided from the authorization provider cannot be changed The length of the "%s" exceeded the maximum length of %s and was reduced to %s characters. The preferences for the custom module "%s" were sucessfully updated to the new module version %s. The preferences for the module "%s" were updated. The request to reset the passwort belongs to a user, who can sign in with an authorization provider. In this view, you can only change the webtrees password. The authorization provider password is managed by the authorization provider and cannot be changed within webtrees. The requested authorization provider could not be found This user can sign in with %s Use webtrees password in parallel User account data from authorization provider cannot be changed Project-Id-Version: OAuth2Client
PO-Revision-Date: 2025-01-18 08:50+0100
Last-Translator: 
Language-Team: 
Language: de
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
Plural-Forms: nplurals=2; plural=(n != 1);
X-Generator: Poedit 3.5
X-Poedit-Basepath: ../..
X-Poedit-KeywordsList: translate
X-Poedit-SearchPath-0: .
 Ein benutzerspezifisches Modul, welches einen OAuth2 Client für webtrees zur Verfügung stellt. Automatische Benutzer-Registrierung nach Einloggen mit Autorisierungsanbieter Wenn Sie diese Option auswählen, werden zusätzliche Debug-Informationen über den Protokollfluss zwischen webtrees und dem Autorisierungsanbieter in den Website-Logs von webtrees protokolliert. Durch Auswahl dieser Option ist es möglich, das ursprüngliche Anmelde-Menü von webtrees auszublenden. Dies kann hilfreich sein, wenn stattdessen das modulspezifische Menü für die Anmeldung verwendet wird. Durch Auswählen/Abwählen dieser Auswahloption ist es möglich, den Menüeintrag für webtrees im Anmeldemenü zu aktivieren/deaktivieren. Falls deaktiviert, werden ggfs. nur Menüeinträge für Autorisierungsanbieter angezeigt. Durch Auswählen/Abwählen dieser Option ist es möglich, ein modulspezifisches Anmelde-Menü im Hauptmenü von webtrees anzuzeigen/auszublenden. Prüfen Sie die Einstellung für urlResourceOwnerDetails in der webtrees Konfiguration. Verwaltung Aktuell sind alle Menüs zum direkten Anmelden in webtrees deaktiviert. Daher ist aktuell nur ein Anmelden über Autorisierungsanbieter möglich. Bitte stellen Sie sicher, dass ein webtrees Administrator-Account über einen Autorisierungsanbieter etabliert ist. Anderfalls ist es nicht mehr möglich, sich als Administrator anzumelden. HInweis: Es ist immer noch möglich, die original webtrees Anmeldeseite durch Eingabe der Route in der Browserzeile aufzurufen, z.B.: Derzeit sind alle Anmelde-Menüs deaktiviert/versteckt. Bitte aktivieren Sie eines der Anmelde-Menüs mit den untenstehenden Einstellungen. Hinweis: Sie können weiterhin auf die ursprüngliche Anmeldeseite von webtrees zugreifen, indem Sie den folgenden Anmeld-Pfad in die Browserzeile eingeben, z. B.: Derzeit kann das webtrees-Passwort für Benutzer, die sich mit einem Autorisierungsanbieter anmelden, nicht zurückgesetzt werden. Bitte wenden Sie sich an den webtrees-Administrator. Aktuell ist das webtrees Anmelde-Menü deaktiviert und das modulspezifische Anmelde-Menü wird für die folgenden Stammbäume nicht angezeigt: %s. Einige Anwender werden sich nicht anmelden können. Bitte erwägen Sie, eines der Anmelde-Menüs mit den untenstehenden Einstellungen zu aktivieren. Webtrees nutzt aktuell kein HTTPS-Protokoll. Beim Einloggen mit Autorisierungsanbietern wird dringend empfohlen, HTTPS zu nutzen, um die Kommunikation mit dem Autorisierungsanbieter zu verschlüsseln. Wenn Ihr Server das Protokoll unterstützt, kann HTTPS durch Ändern von "base_url" in der webtrees-Datei "config.ini.php" aktiviert werden. Aktuell startet "base_url" nicht mit "https://". Benutzerdefiniertes Modul Debug-Einstellungen Debug-Logeinträge Beim Versuch, ein Zugriffstoken vom Autorisierungsanbieter abzurufen, ist ein Fehler aufgetreten. Überprüfen Sie die Einstellung für urlAccessToken in der webtrees Konfiguration. Überprüfen Sie die Server-Zugriffsprotokolle (oder .htaccess Konfigurationsdateien), ob 301- oder 302-Weiterleitungen angewendet werden, die möglicherweise POST- in GET-Anfragen umwandeln. Fehler beim Empfang des Zugriffs-Tokens oder der Benutzer-Daten vom Autorisierungsanbieter Weitere Registrierungsdaten - Bitte vervollständigen und fortsetzen Verbergen Das webtrees Anmelde-Menü verbergen Wenn Sie diese Option auswählen, darf ein Benutzer mit derselben E-Mail-Adresse in webtrees und beim Autorisierungsanbieter die E-Mail-Adresse in webtrees nicht ändern. Wenn sich die E-Mail-Adresse beim Autorisierungsanbieter geändert hat, wird die E-Mail-Adresse in webtrees beim Anmelden aktualisiert. Wenn der Administrator beschließt, die E-Mail-Adresse in der webtrees-Benutzerverwaltung zu ändern, wird die Synchronisierung der E-Mail-Adresse abgebrochen. Wenn Sie diese Option auswählen, darf sich ein Benutzer, der sich mit einem Autorisierungsanbieter anmelden kann, zusätzlich auch mit einem webtrees-Passwort anmelden. In dieser Ansicht können Sie nur ein webtrees-Passwort anfordern. Das Autorisierungsanbieter-Passwort wird vom Autorisierungsanbieter verwaltet und kann innerhalb von webtrees nicht geändert werden. Ungültiger Zustand in der Kommunikation mit dem Autorisierungsanbieter. Vom Autorisierungsanbieter wurden keine validen Daten empfangen. Email fehlt. Ungültige Benutzerdaten vom Authorisierungsanbieter empfangen E-Mail Adresse mit dem Autorisierungsanbieter synchron halten Synchron halten Anmeldung mit den angegebenen Benutzeranmeldeinformationen verweigert. Der vom Autorisierungsanbieter angegebene Benutzername oder die E-Mail-Adresse ist möglicherweise bereits in Webtrees vorhanden. Obligatorische Benutzer-Daten vom Autorisierungsanbieter können nicht geändert werden OAuth 2.0 Kommunikationsfehler OAuth2 Client Passwort (innerhalb von webtrees) Weiterleitung zur webtrees-Registrierungsseite Registrierung mit Einstellungen für die Passwort- und Email-Verwaltung Einstellungen für Anmelde-Menüs Ein modulspezifisches Anmelde-Menü anzeigen Menü-Eintrag für das ursprüngliche webtrees Anmelde-Menü anzeigen Anmelden mit Anmelden mit einem Autorisierungsanbieter Angemeldet mit %s Der  Authorisierungsanbieter "%s" ist nicht verfügbar. Bitte prüfen Sie die Konfiguration in der folgenden Datei: data/config.ini.php Die Konfiguration für den Authorisierungsanbieter "%s" enthält keine Angaben für die Option "%s". Bitte prüfen Sie die Konfiguration in der folgenden Datei: data/config.ini.php Das benutzerspezifische Modul "%s" ist parallel zum benutzerspezifische Modul %s aktiviert. Dies kann zu unbeabsichtigtem Verhalten führen, weil beide Module die gleiche Custom View "%s" registriert haben. Es wird dringend empfohlen eines der beiden Module zu deaktivieren. Das benutzerspezifische Modul "%s" ist parallel zum benutzerspezifische Modul %s aktiviert. Dies kann zu unbeabsichtigtem Verhalten führen. Bei Verwendung des Moduls %s wird dringend empfohlen, das Modul "%s" zu deaktivieren, weil die identische Funktion ebenfalls im Modul %s integriert ist. Der Custom View "%s" ist nicht als Ersatz für den Standard Custom View von webtrees registriert. Es könnte ein anderes Modul installiert sein, welches den gleiche Custom View registriert hat. Dies kann zu unerwartetem Verhalten führen. Es wird dringend empfohlen, eines der Module zu deaktivieren. Der Pfad für den Custom View ist: %s Die vom Autorisierungsanbieter bereitgestellte E-Mail-Adresse kann nicht geändert werden Die Länge der/des "%s" überschreitet die maximale Länge von %s und wurde daher auf %s Zeichen gekürzt. Die Einstellungen für das benutzerdefinierte Modul "%s" wurden für die neue Modul-Version %s aktualisiert. Die Einstellungen für das Modul "%s" wurden aktualisiert. Die Anfrage zum Zurücksetzen des Passworts gehört zu einem Benutzer, der sich mit einem Autorisierungsanbieter anmelden kann. In dieser Ansicht können Sie nur das webtrees-Passwort ändern. Das Autorisierungsanbieter-Passwort wird vom Autorisierungsanbieter verwaltet und kann nicht innerhalb von webtrees geändert werden. Der angeforderte Autorisierungsanbieter konnte nicht gefunden werden Dieser Benutzer kann sich mit %s anmelden webtrees-Passwort parallel nutzen Benutzer-Daten vom Autorisierungsanbieter können nicht geändert werden 