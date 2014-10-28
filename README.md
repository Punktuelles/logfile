logfile
=======

Eine einfache Logdatei mit optionaler Fehlerprotokollierung, realisiert als Singleton. 

Although this is work from my sparetime, I use this class at work. We use to write our comments in german, so this is also written in german, but of course you can send me an eMail and I will gladly answer your questions.

Manche Dinge sind so einfach, dass man sie mit einem Dreizeiler abhandeln könnte. Man tut es aber nicht, weil es doch immer diverse Sonderfälle gibt und man sich den Code endlich einmal als Vorlage speichern möchte. Entstanden aus einer einer sehr  einfachen Klasse, die Inhalte in eine Logdatei protokolliert oder per eMail versendet. Die hier verwendete Klasse ist noch einmal etwas aufpoliert worden, alles verchromt, tiefergelegt und zum gleichen Preis mit noch mehr Gimmicks - als Singleton und mit Fehlerbehandlung.

Die Klasse LogDatei protokolliert Einträge direkt oder gesammelt in eine angegebene Logdatei oder versendet sie per eMail.

Für einen breiteren Zugriff als in der ursprünglichen Form ist die Klasse als Singleton realisiert. Ein Singleton ist (vereinfacht) ausgedrückt eine Klasse von der nur eine einzige Instanz existieren kann. Andere Klassen können sich eine Instanz zur Verwendung erschaffen, greifen aber damit letztendlich nur auf die bereits bestehende Instanz zu. Das alte Motto "es kann nur einen geben" bekommt hier eine ganz neue, objektorientierte Bedeutung.

Darüberhinaus können Fehlermeldungen und Warnungen von PHP in eine Methode der Klasse LogDatei umgeleitet und protokolliert. Treten Fehler auf, können Sie in die Logdatei geschrieben werden.

Der Aufruf erfolgt in der Form $meinObjekt=LogDatei::singleton('Pfad/Datei'); oder $meinObjekt=LogDatei::singleton('Pfad/Datei', false); um die standardmäßig aktivierte Fehlerprotokollierung zu deaktivieren.
Die Logdatei kann in der Form require_once('Pfad/logfile.php'); eingebunden werden.

