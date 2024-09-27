��            )   �      �  :   �  E   �  m   "  e   �  b   �     Y  �  g     �  R   �  8   I  �   �  \   m  ;   �  M     Z   T     �     �     �  #   �     �  �   
	  	  �	  4    Z   <  a   �  1   �  �   +  7   �  ?     h  P  `   �  M     y   h  �   �     }  
   �  �       �  Z   �  D     '  P  {   x  H   �  W   =  `   �     �            #   $     H    U  %  h  R  �  j   �  l   L  :   �  �   �  D   �  H                                                     	                                                
                                       A custom module to implement a OAuth2 client for webtrees. Automatic user registration after sign in with authorization provider Cannot use the login data of the authorization provider. More than one primary key defined for the user data. Cannot use the login data of the authorization provider. Neither username nor email is a primary key. Cannot use the login data of the authorization provider. No primary key defined for the user data. Control panel Currently, webtrees does not use the HTTPS protocol. If signing in with authorization providers, it is urgently recommended to use HTTPS in order to encrypt the communication with the authorization provider. If your server supports the protocol, HTTPS can be activated by changing "base_url" in the webtrees "config.ini.php" file. Currently, "base_url" does not start with "https://". Custom module Failed to get the access token or the user details from the authorization provider Further registration data - Please complete and continue If you sign in with an authorization provider, your password cannot be changed within webtrees. If you request a new password from webtrees, you will receive an email. However, the included link to create a new passwort will not work. In order to use changed settings for a download/upload, the settings need to be saved first. Invalid state in communication with authorization provider. Mandatory user account data from the authorization provider cannot be changed No valid user account data received from authorizaton provider. Username or email missing. OAuth2 Client Register with Settings Sign in with authorization provider Signed in in with The custom module "%s" is activated in parallel to the %s custom module. This can lead to unintended behavior, because both of the modules have registered the same custom view "%s". It is strongly recommended to deactivate one of the modules. The custom module "%s" is activated in parallel to the %s custom module. This can lead to unintended behavior. If using the %s module, it is strongly recommended to deactivate the "%s" module, because the identical functionality is also integrated in the %s module. The custom module view "%s" is not registered as replacement for the standard webtrees view. There might be another module installed, which registered the same custom view. This can lead to unintended behavior. It is strongly recommended to deactivate one of the modules. The path of the parallel view is: %s The length of the "%s" exceeded the maximum length of %s and was reduced to %s characters. The preferences for the custom module "%s" were sucessfully updated to the new module version %s. The preferences for the module "%s" were updated. The request to reset the passwort belongs to a user, who signs in with %s. The password is managed by the authorization provider (%s) and cannot be changed within webtrees. The requested authorization provider could not be found User account data from authorization provider cannot be changed Project-Id-Version: OAuth2Client
PO-Revision-Date: 2024-09-27 06:01+0200
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
 Ein benutzerspezifisches Modul, welches einen OAuth2 Client für webtrees zur Verfügung stellt. Automatische Benutzer-Registrierung nach Einloggen mit Autorisierungsanbieter Die Login-Daten vom Autorisierungsanbieter können nicht genutzt werden. Es ist mehr als ein Primärschlüssel definiert. Die Login-Daten vom Autorisierungsanbieter können nicht genutzt werden. Weder Benutzername noch Email sind als verbindliche Primärschlüssel vorgesehen. Die Login-Daten vom Autorisierungsanbieter können nicht genutzt werden. Es ist kein verbindlicher Primärschlüssel definiert. Verwaltung Webtrees unterstützt aktuell kein HTTPS-Protokoll. Beim Einloggen mit Autorisierungsanbietern wird dringend empfohlen, HTTPS zu nutzen, um die Kommunikation mit dem Autorisierungsanbieter zu verschlüsseln. Wenn Ihr Server das Protokoll unterstützt, kann HTTPS durch Ändern von "base_url" in der webtrees-Datei "config.ini.php" aktiviert werden. Aktuell startet "base_url" nicht mit "https://". Benutzerdefiniertes Modul Fehler beim Empfang des Zugriffs-Tokens oder der Benutzer-Daten vom Autorisierungsanbieter Weitere Registrierungsdaten - Bitte vervollständigen und fortsetzen Wenn Sie sich mit einem Autorisierungsanbieter anmelden, kann Ihr Passwort nicht innerhalb von webtrees geändert werden. Wenn Sie ein neues Passwort von webtrees anfordern, erhalten Sie zwar ein Email. Allerdings wird der enthaltene Link zur Erzeugung eines neuen Passworts nicht funktionieren. Um geänderte Einstellungen beim Herunterladen/Hochladen zu nutzen, müssen die Einstellungen zunächst gespeichert werden. Ungültiger Zustand in der Kommunikation mit dem Autorisierungsanbieter. Obligatorische Benutzer-Daten vom Autorisierungsanbieter können nicht geändert werden Vom Autorisierungsanbieter wurden keine validen Daten empfangen. Benutzer-Name oder Email fehlt. OAuth2 Client Registrierung mit Einstellungen Mit Autorisierungsanbieter anmelden Anmelden mit Das benutzerspezifische Modul "%s" ist parallel zum benutzerspezifische Modul %s aktiviert. Dies kann zu unbeabsichtigtem Verhalten führen, weil beide Module die gleiche Custom View "%s" registriert haben. Es wird dringend empfohlen eines der beiden Module zu deaktivieren. Das benutzerspezifische Modul "%s" ist parallel zum benutzerspezifische Modul %s aktiviert. Dies kann zu unbeabsichtigtem Verhalten führen. Bei Verwendung des Moduls %s wird dringend empfohlen, das Modul "%s" zu deaktivieren, weil die identische Funktion ebenfalls im Modul %s integriert ist. Der Custom View "%s" ist nicht als Ersatz für den Standard Custom View von webtrees registriert. Es könnte ein anderes Modul installiert sein, welches den gleiche Custom View registriert hat. Dies kann zu unerwartetem Verhalten führen. Es wird dringend empfohlen, eines der Module zu deaktivieren. Der Pfad für den Custom View ist: %s Die Länge der/des "%s" überschreitet die maximale Länge von %s und wurde daher auf %s Zeichen gekürzt. Die Einstellungen für das benutzerdefinierte Modul "%s" wurden für die neue Modul-Version %s aktualisiert. Die Einstellungen für das Modul "%s" wurden aktualisiert. Die Anfrage zum Rücksetzen des Passworts betrifft einen Benutzer, welcher sich mit %s anmeldet. Das Passwort wird vom Autorisierungsanbieter (%s) verwaltet und kann nicht innerhalb von webtrees geändert werden. Der angeforderte Autorisierungsanbieter konnte nicht gefunden werden Benutzer-Daten vom Autorisierungsanbieter können nicht geändert werden 