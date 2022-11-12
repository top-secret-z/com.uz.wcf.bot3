### Das 'Schweizer Messer' für Ihre Community.

Die WoltLab Suite bietet nur wenige Möglichkeiten, die Pflege der Community und eine engere Betreuung der Mitglieder zu automatisieren. Vieles muss manuell gemacht werden, was viel Zeit in Anspruch nimmt und zudem das Risiko birgt, Dinge schlicht zu vergessen.  **Community Bot**  kann Ihnen viele mühsame Arbeiten abnehmen und zuverlässig für Sie erledigen.

### Funktion

Aufgrund des großen Funktionsumfangs und der vielfältigen Konfigurationsmöglichkeiten würde eine detaillierte Beschreibung hier den Rahmen sprengen. An dieser Stelle nur so viel:

**Community Bot**  ermöglicht durch Erstellen einzelner Bots das Überwachen vieler Vorgänge, vom Ändern der Signatur durch einen Benutzer über Neuregistrierungen und Geburtstage und andere Jubiläen der Mitglieder bis hin zur Inaktivität der Benutzer, und das gezielte Reagieren darauf. Er kann über einen Feedreader Inhalte importieren, Benutzer willkommen heißen, Benutzern zu Jubiläen gratulieren, zeitgesteuert und bei Bedarf wiederkehrend Benachrichtigungen verschicken, er kann inaktive Benutzer animieren, sich wieder mehr an der Community zu beteiligen, kann Benutzer abhängig von vielen Parametern Benutzergruppen zuweisen oder sogar löschen und so weiter und so fort. Dabei kann in der Regel durch Bedingungen festgelegt werden, auf welche Benutzer reagiert werden soll bzw. welche Benutzer benachrichtigt werden sollen und welche nicht.

Derzeit stehen folgende Aktionen zur Verfügung, die der  **Community Bot**  ausführen kann:

Basispaket

-   Feedreader
-   System - Fehler
-   System - Kommentare
-   System - Konversationen
-   System - Meldungen
-   System - Rundschreiben
-   System - Statistik
-   System - Updates
-   Artikel - Änderung
-   Artikel - Neuer Artikel
-   Benutzer - Einstellungen
-   Benutzer - Geburtstag
-   Benutzer - Gesamtzahl
-   Benutzer - Gruppenzuordnung
-   Benutzer - Gruppenänderung
-   Benutzer - Inaktivität
-   Benutzer - Likes und Dislikes
-   Benutzer - Mitgliedschaft
-   Benutzer - Neuer Benutzer
-   Benutzer - Verwarnung

Forum-Erweiterung

-   Forum - Beitrag - Änderung durch Autor
-   Forum - Beitrag - Anzahl
-   Forum - Beitrag - Moderation
-   Forum - Thema - Hilfreichste Antwort
-   Forum - Thema - Moderation
-   Forum - Thema - Modifizierung
-   Forum - Thema - Neu
-   Forum - Statistik
-   Forum - Top-Poster

Blog-Erweiterung

-   Blog - Artikel - Änderung durch Autor
-   Blog - Artikel - Anzahl
-   Blog - Artikel - Neu
-   Blog - Blog - Änderung durch Autor
-   Blog - Blog - Neu
-   Blog - Statistik
-   Blog - Top-Blogger

Als Benachrichtigungsarten stehen derzeit zur Verfügung:

Basispaket

-   Systembenachrichtigung
-   Artikel (ein- und mehrsprachig)
-   E-Mail
-   Kommentar (Pinnwand)
-   Konversation (individuelle oder Gruppen-Konversation)

Forum-Erweiterung

-   Forum - Beitrag
-   Forum - Thema

Blog-Erweiterung

-   Blog - Artikel

Mit neuen WSC-Versionen eingeführte Funktionen, z.B. Trophäen in WSC 3.1, werden natürlich in neue  **Community Bot**-Versionen übernommen.

Die Sprache der Benachrichtigung kann frei gewählt oder automatisch ermittelt werden. Zudem können Benachrichtigungen parallel in allen installierten Sprachen erstellt werden. Über eine Vielzahl von Platzhaltern lassen sich automatisiert und aktionsbezogen Informationen, wie z.B. Benutzernamen oder Links, in Texte einfügen.

Für das Erstellen von Benachrichtigungen wird der mit der WoltLab Suite eingeführte Hintergrundprozess (Background Queue) genutzt. Fallen viele Benachrichtigungen an, werden diese zunächst in der Datenbank gespeichert und dann sukzessive im Hintergrund abgearbeitet. Da hierfür Besuche der Webseite erforderlich sind, empfiehlt sich die Einrichtung eines richtigen Cronjobs auf dem Server, um das Abarbeiten der Benachrichtigungen zu beschleunigen. Siehe dazu z.B. hier:  [Cronjob](https://community.woltlab.com/thread/253997-sofortige-e-mail-benachrichtigung-php-smtp/?postID=1585459&amp;highlight=queue#post1585459).

Alle Aktionen des  **Community Bots**  können bei Bedarf protokolliert und im ACP eingesehen werden.

### Konfigurierbarkeit

Abhängig von gewählter Aktion und Benachrichtigung (Bots) sind unterschiedliche, teilweise sehr umfangreiche und komplexe Konfigurationsmöglichkeiten verfügbar. Eine kontextsensitive Hilfe erleichtert die Arbeit mit dem  **Community Bot**. Zudem kann die Konfiguration der Bots mittels eines Test-Modus im laufenden Betrieb überprüft werden. Die Ergebnisse werden im  **Community Bot**-Protokoll (ACP) dargestellt.

Es wird empfohlen, einem Bot-Benutzer, der Aktionen ausführt und/oder Benachrichtigungen erstellt, alle dafür nötigen Benutzerrechte zu verleihen.

### Erweiterbarkeit

Der  **Community Bot**  lässt sich durch kostenlose optionale Pakete für Woltlab Suite-Anwendungen und andere Plugins erweitern. Derzeit sind Pakete für WoltLab Suite Forum und Blog, für das VieCode Lexikon, WoltLab Suite: Chronik und News verfügbar, die bereits mit dem Hauptpaket ausgeliefert werden.