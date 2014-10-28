<?php

/**
 * Logdatei mit Fehlerprotokollierung
 *
 * Die Klasse LogDatei protokolliert uebergebene Inhalte und
 * Texte zeilenweise in eine angegebene Logdatei in Textform. Sie kann
 * Fehlermeldungen protokollieren und per eMail Benachrichtigungen
 * absenden. Sie ist als Singleton realisiert.
 * Der Aufruf erfolgt in der Form $meinObjekt=LogDatei::singleton('Pfad/Datei');
 * oder $meinObjekt=LogDatei::singleton('Pfad/Datei', false);
 * Die Logdatei kann in dieser Form eingebunden werden 
 * require_once('Pfad/logfile.php');
 *
 * @package     Punktuelles
 * @subpackage  Logfiles
 * @author      Stephan Elter <stephan.elter(at)gmail.com>
 * @version     1.3.202
 * @license     http://wtfpl.net WTFPL
 */
class LogDatei
{

    private static $instance;

    private $datei;

    private $mail;

    private $puffer;

    private $abgefangeneFehler;

    private $fangeFehlerAb;

    private $eigenerStatus;

    /**
     * Aufruf von 'clone' ist fuer das Singleton gesperrt
     */
    private function __clone()
    {}

    /**
     * 'singleton' ersetzt den Konstruktor in der Form
     * $meinObjekt = LogDatei::singleton(Parameter); und laesst
     * so nur die Erschaffung einer Instanz zu.
     * Soll keine Fehlerprotokollierung stattfinden muss der
     * Parameter $fangeFehlerAb explizit auf 'false' gesetzt werden.
     *
     * @param string $dieLogDatei
     *            Dateiname der Logdatei, optional inklusive Pfadangabe
     * @param bool $fangeFehlerAb
     *            De-/Aktivierung des Fehlerhandlings, wird standardmaessig aktiviert
     */
    public static function singleton($dieLogDatei = 'Paniklogfile.txt', $fangeFehlerAb = true)
    {
        if (! self::$instance) {
            self::$instance = new self($dieLogDatei, $fangeFehlerAb);
        }
        return self::$instance;
    }

    /**
     * Konstruktor ist private, von aussen nicht mehr zu erreichen.
     *
     * Er wird einmalig von 'singleton' aufgerufen.
     * Es wird geprueft ob die Logdatei geoeffnet werden kann.
     * Kann die Logdatei nicht eroeffnet werden, wird ein Fehlerflag
     * gesetzt.
     */
    private function __construct($datei, $fangeFehlerAb)
    {
        $this->eigenerStatus .= 'construct,';
        
        $test = fopen($datei, "a");
        if ($test) {
            fclose($test);
            $this->datei = $datei;
        } else {
            $this->eigenerStatus .= 'Schreiben nicht moeglich,';
            if($datei != 'Paniklogfile.txt'){
                $datei = 'Paniklogfile.txt';
                $test = fopen($datei, "a");
                if ($test) {
                    fclose($test);
                    $this->datei = $datei;
                }else{
                    $this->eigenerStatus .= 'PANIK!';
                }
                
                
            }
            
        }
        
        $this->_("\r\n");
        $this->schreibeMitZeit('Beginn Logvorgang');
        if ($this->fangeFehlerAb) {
            $this->protokolliereFehler();
        }
    }

    /**
     * Beim Beenden werden noch vorhandene Inhalte und
     * Fehler geschrieben.
     */
    public function __destruct()
    {
        $this->eigenerStatus .= 'destruct,';
        
        if ($this->puffer) {
            $this->schreibePuffer();
        }
        if ($this->abgefangeneFehler) {
            $this->schreibeMitZeit($this->abgefangeneFehler);
        }
        $this->schreibeMitZeit($this->eigenerStatus);
        $this->schreibeMitZeit('Ende Logvorgang');
    }

    /**
     * Alle aktuell vorhandenen Inhalte die von 'puffere' gespeichert
     * wurden werden sofort geschrieben und danach geloescht
     */
    public function schreibePuffer()
    {
        $this->eigenerStatus .= 'schreibePuffer,';
        
        $this->schreibe($this->puffer);
        $this->puffer = '';
    }

    /**
     * Alle aktuell vorhandenen Inhalte werden sofort als eMail verschickt
     * und danach geloescht.
     * Gibt 'maile' false zurueck, wird der Inhalt des Puffers nicht
     * geleert und somit spaetestens durch den Destruktor
     * in das Logfile geschrieben.
     */
    public function schickeMail()
    {
        $this->eigenerStatus .= 'schickeMail,';
        
        if (true == $this->maile($this->puffer)) {
            $this->puffer = '';
        }
    }

    /**
     * Alias fuer die Methode 'puffere'
     *
     * Inhalte werden nicht sofort in die Logdatei geschrieben,
     * sondern zeilenweise zwischengespeichert
     *
     * @param mixed $inhalt
     *            Zu puffernde Information
     */
    public function p($inhalt)
    {
        $this->puffere($inhalt);
    }

    /**
     * Inhalte werden nicht sofort in die Logdatei geschrieben,
     * sondern zeilenweise zwischengespeichert
     *
     * @param mixed $inhalt
     *            Zu puffernde Information
     */
    public function puffere($inhalt)
    {
        $this->puffer .= "\r\n" . $inhalt;
    }

    /**
     * Setter fuer die EMail-Adresse
     *
     * @param string $mail
     *            Mailadresse
     * @return boolean Gibt an ob Mail nach RFC5321 valide scheint.
     */
    public function setMail($mail)
    {
        $this->eigenerStatus .= 'setMail,';
        
        $this->mail = $mail;
        
        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $this->eigenerStatus .= 'Mail nicht valide,';
            $mailValide = false;
        } else {
            $mailValide = true;
        }
        
        return $mailValide;
    }

    /**
     * Setter fuer SMTP
     *
     * Optional kann hier SMTP gesetzt werden
     *
     * @param string $smtp
     *            SMTP setzen
     */
    public function setSMTP($smtp)
    {
        ini_set('SMTP', $smtp);
    }

    /**
     * Der uebergebene Inhalt wird per eMail verschickt,
     * optional kann ein Betreff angegeben werden
     *
     * @param string $inhalt
     *            Inhalt der Mail
     * @param string $betreff
     *            Optionaler Betreff der zu verschickenden Mail
     * @return boolean Gibt an ob Versand erfolgreich war
     */
    public function maile($inhalt, $betreff = 'Info von LogDatei')
    {
        $this->eigenerStatus .= 'maile,';
        
        if (filter_var($this->mail, FILTER_VALIDATE_EMAIL)) {
            $ergebnis = mail($this->mail, $betreff, $inhalt, "From: {$this->mail}\nContent-Type: text/html\n");
        } else {
            $ergebnis = false;
        }
        if (false == $ergebnis) {
            $mailVersand = false;
            $this->eigenerStatus .= 'mail nicht moeglich,';
            $this->schreibeMitZeit('PANIK: eMail konnte nicht verschickt werden!');
        }
        if ($ergebnis != false) {
            $mailVersand = true;
        }
        
        return $mailVersand;
    }

    /**
     * Alias fuer die Methode 'schreiben'
     *
     * @param mixed $inhalt
     *            Zu schreibender Inhalt
     */
    public function _($inhalt)
    {
        $this->schreibe($inhalt);
    }

    /**
     * Der Inhalt wird sofort in die Logdatei geschrieben
     *
     * @param mixed $inhalt
     *            Zu schreibender Inhalt
     */
    public function schreibe($inhalt)
    {
        $datei = fopen($this->datei, "a");
        fwrite($datei, "\r\n" . $inhalt);
        fclose($datei);
    }

    /**
     * Der Inhalt wird sofort mit einer Zeitangabe in die Logdatei geschrieben
     *
     * @param mixed $inhalt
     *            Zu schreibender Inhalt
     */
    public function schreibeMitZeit($inhalt)
    {
        $datei = fopen($this->datei, "a");
        fwrite($datei, "\r\n" . date('d.m.H:i > ') . $inhalt);
        fclose($datei);
    }

    /**
     * Alle Fehlermeldungen und Warnungen werden der Methode "FehlerKanal"
     * uebergeben
     */
    public function protokolliereFehler()
    {
        $this->eigenerStatus .= 'protokolliereFehler,';
        
        set_error_handler(array(
            $this,
            'FehlerKanal'
        ));
    }

    /**
     * Setzt Fehlerhandle genaugenommen nicht auf Standard,
     * sondern auf den zuvor verwendeten Handle zurueck
     */
    public function stoppeFehlerProtokoll()
    {
        $this->eigenerStatus .= 'stoppeFehlerProtokoll,';
        
        restore_error_handler();
    }

    /**
     * Alle Fehlermeldungen werden in die Logdatei geschrieben und
     * zusaetzlich fuer einen spaeteren Versand per eMail zwischengespeichert
     */
    private function fehlerKanal($fehlerstufe, $meldung, $datei, $zeile, $detailArray)
    {
        // Parameter DetailArray wird hier nicht ausgewertet
        $fehlerMeldung = 'FEHLER: ' . $fehlerstufe . ' ' . $meldung . ' in ' . $datei . ' Zeile ' . $zeile;
        $this->_($fehlerMeldung);
        $this->abgefangeneFehler .= "\n" . date('H.i.s') . "\n" . $fehlerMeldung;
    }
}
