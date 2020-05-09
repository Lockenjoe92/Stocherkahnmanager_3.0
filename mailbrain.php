<?php

//Gets called every 5 minutes
include_once "./ressourcen/ressourcen.php";
echo "Mailbrain beginnt...";

mail_erinnerung_uebergabe_ausmachen_intervall_eins();
mail_erinnerung_uebergabe_ausmachen_intervall_zwei();
mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt();
mail_erinnerung_schluesselrueckgabe_intervall();
mail_erinnerung_schluesseluebergabe_eintragen_wart();

    function mail_erinnerung_uebergabe_ausmachen_intervall_eins(){
        //Erinnerung eine Übergabe ausmachen - Intervall 1
        echo "<p>Erinnerung &Uuml;bergabe ausmachen - Intervall eins:<br>";
        $TageVorherIntervallEinsUebergabeAusmachen = lade_einstellung('erinnerung-uebergabe-ausmachen-1');
        $BefehlIntervallEinsUebergabeAusmachen = "+ ".$TageVorherIntervallEinsUebergabeAusmachen." days";
        $ZeitgrenzeIntervallEinsUebergabeAusmachen = date("Y-m-d G:i:s", strtotime($BefehlIntervallEinsUebergabeAusmachen));

        $link = connect_db();
        $Anfrage = "SELECT * FROM reservierungen WHERE ende > '".$ZeitgrenzeIntervallEinsUebergabeAusmachen."' AND beginn < '".$ZeitgrenzeIntervallEinsUebergabeAusmachen."' AND storno_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl == 0){
            echo "Es stehen keine Reservierungen an!<br>";
        } else if ($Anzahl > 0){
            for ($a = 1; $a <= $Anzahl; $a++){
                $Reservierung = mysqli_fetch_assoc($Abfrage);
                $AnfrageLadeUebergaben = "SELECT * FROM uebergaben WHERE res = '".$Reservierung['id']."' AND storno_user = '0'";
                $AbfrageLadeUebergaben = mysqli_query($link, $AnfrageLadeUebergaben);
                $AnzahlLadeUebergaben = mysqli_num_rows($AbfrageLadeUebergaben);

                if ($AnzahlLadeUebergaben == 0){

                    //Hat er ne Übernahme gebucht?
                    $AnfrageUebernahmeGebucht = "SELECT * FROM uebernahmen WHERE reservierung = '".$Reservierung['id']."' AND storno_user = '0'";
                    $AbfrageUebernahmeGebucht = mysqli_query($link, $AnfrageUebernahmeGebucht);
                    $AnzahlUebernahmeGebucht = mysqli_num_rows($AbfrageUebernahmeGebucht);

                    if ($AnzahlUebernahmeGebucht == 0){

                        //Braucht er gemäß seiner Schlüsselrollen überhaupt eine Übergabe?
                        $Schluesselrollen = schluesselrollen_user_laden($Reservierung['user']);

                        if (($Schluesselrollen['hat_eig_schluessel'] == "1") OR ($Schluesselrollen['wg_hat_schluessel'] == "1")){
                            echo "Reservierung ".$Reservierung['id']." - User hat eigenen Schl&uuml;ssel<br>";
                        } else {

                            $NameVorlage = "erinnerung-uebergabe-ausmachen-intervall-eins";
                            $TypMail = "".$NameVorlage."-".$Reservierung['id']."";

                            //Mail schon gesendet worden?
                            if (mail_schon_gesendet($Reservierung['user'], $TypMail)){
                                echo "Reservierung ".$Reservierung['id']." - Mail schon gesendet!<br>";
                            } else {

                                //Angaben Mail generieren
                                if ($TageVorherIntervallEinsUebergabeAusmachen == "1"){
                                    $AngabeTage = "einem Tag";
                                } else {
                                    $AngabeTage = "".$TageVorherIntervallEinsUebergabeAusmachen." Tagen";
                                }

                                //Mail senden
                                $UserMeta = lade_user_meta($Reservierung['user']);
                                $Bausteine = array();
                                $Bausteine['vorname_user'] = $UserMeta['vorname'];
                                $Bausteine['angabe_tage'] = $AngabeTage;

                                if (mail_senden($NameVorlage, $UserMeta['mail'], $Reservierung['user'], $Bausteine, $TypMail)){
                                    echo "Reservierung ".$Reservierung['id']." - Mail gesendet!<br>";
                                } else {
                                    echo "Reservierung ".$Reservierung['id']." - Fehler beim senden der Mail!<br>";
                                }
                            }
                        }

                    } else if ($AnzahlUebernahmeGebucht > 0){
                        echo "Reservierung ".$Reservierung['id']." - &Uuml;bernahme ausgemacht!<br>";
                    }

                } else if ($AnzahlLadeUebergaben > 0){
                    echo "Reservierung ".$Reservierung['id']." - &Uuml;bergabe ausgemacht!<br>";
                }
            }
        }

        echo "</p>";
    }

    function mail_erinnerung_uebergabe_ausmachen_intervall_zwei(){
        //Erinnerung eine Übergabe ausmachen - Intervall 2
        echo "<p>Erinnerung &Uuml;bergabe ausmachen - Intervall zwei:<br>";
        $TageVorherIntervallZweiUebergabeAusmachen = lade_einstellung('erinnerung-uebergabe-ausmachen-2');
        $BefehlIntervallZweiUebergabeAusmachen = "+ ".$TageVorherIntervallZweiUebergabeAusmachen." days";
        $ZeitgrenzeIntervallZweiUebergabeAusmachen = date("Y-m-d G:i:s", strtotime($BefehlIntervallZweiUebergabeAusmachen));

        $link = connect_db();
        $Anfrage = "SELECT * FROM reservierungen WHERE ende > '".$ZeitgrenzeIntervallZweiUebergabeAusmachen."' AND beginn < '".$ZeitgrenzeIntervallZweiUebergabeAusmachen."' AND storno_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl == 0){
            echo "Es stehen keine Reservierungen an!<br>";
        } else if ($Anzahl > 0){
            for ($a = 1; $a <= $Anzahl; $a++){
                $Reservierung = mysqli_fetch_assoc($Abfrage);
                $AnfrageLadeUebergaben = "SELECT * FROM uebergaben WHERE res = '".$Reservierung['id']."' AND storno_user = '0'";
                $AbfrageLadeUebergaben = mysqli_query($link, $AnfrageLadeUebergaben);
                $AnzahlLadeUebergaben = mysqli_num_rows($AbfrageLadeUebergaben);

                if ($AnzahlLadeUebergaben == 0){

                    //Bei leuten mit eigenem Schlüssel nix machen
                    $UserMeta = lade_user_meta($Reservierung['user']);
                    $BenutzerrollenUser = schluesselrollen_user_laden($Reservierung['user']);

                    if (($BenutzerrollenUser['hat_eig_schluessel'] == "1") OR ($BenutzerrollenUser['wg_hat_schluessel'] == "1")){

                        echo "Reservierung ".$Reservierung['id']." - User hat eigenen Schl&uuml;ssel<br>";

                    } else {

                        //Hat er ne Übernahme gebucht?
                        $AnfrageUebernahmeGebucht = "SELECT * FROM uebernahmen WHERE reservierung = '".$Reservierung['id']."' AND storno_user = '0'";
                        $AbfrageUebernahmeGebucht = mysqli_query($link, $AnfrageUebernahmeGebucht);
                        $AnzahlUebernahmeGebucht = mysqli_num_rows($AbfrageUebernahmeGebucht);

                        if ($AnzahlUebernahmeGebucht == 0){

                            $NameVorlage = "erinnerung-uebergabe-ausmachen-intervall-zwei";
                            $TypMail = "".$NameVorlage."-".$Reservierung['id']."";

                            //Mail schon gesendet worden?
                            if (mail_schon_gesendet($Reservierung['user'], $TypMail)){
                                echo "Reservierung ".$Reservierung['id']." - Mail schon gesendet!<br>";
                            } else {

                                //Angaben Mail generieren
                                if (intval($TageVorherIntervallZweiUebergabeAusmachen) == 1){
                                    $AngabeTage = "Morgen";
                                } else if (intval($TageVorherIntervallZweiUebergabeAusmachen) == 2) {
                                    $AngabeTage = "&Uuml;bermorgen";
                                } else if (intval($TageVorherIntervallZweiUebergabeAusmachen) > 2){
                                    $AngabeTage = "In ".$TageVorherIntervallZweiUebergabeAusmachen." Tagen";
                                }

                                //Mail senden
                                $Bausteine = array();
                                $Bausteine['vorname_user'] = $UserMeta['vorname'];
                                $Bausteine['angabe_tage'] = $AngabeTage;

                                if (mail_senden($NameVorlage, $UserMeta['mail'], $Reservierung['user'], $Bausteine, $TypMail)){
                                    echo "Reservierung ".$Reservierung['id']." - Mail gesendet!<br>";
                                } else {
                                    echo "Reservierung ".$Reservierung['id']." - Fehler beim senden der Mail!<br>";
                                }
                            }

                        } else if ($AnzahlUebernahmeGebucht > 0){
                            echo "Reservierung ".$Reservierung['id']." - &Uuml;bernahme ausgemacht!<br>";
                        }

                    }

                } else if ($AnzahlLadeUebergaben > 0){
                    echo "Reservierung ".$Reservierung['id']." - &Uuml;bergabe ausgemacht!<br>";
                }
            }
        }
        echo "</p>";
    }

    function mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt(){

        echo "<p>Erinnerung R&uuml;ckgabe nach Fahrt<br>";
        $link = connect_db();
        $GrenzeVorZweiTagen = date("Y-m-d G:i:s", strtotime("-1 days")); //Falls cronjob ausfällt und wir sonst zig mails auf einmal senden würden

        //Suche jede Reservierung die vorbei ist
        $Anfrage = "SELECT * FROM reservierungen WHERE storno_user = '0' AND beginn > '".$GrenzeVorZweiTagen."' AND ende < '".timestamp()."'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        //Iteriere über jede Res
        for ($a = 1; $a <= $Anzahl; $a++){

            $Reservierung = mysqli_fetch_assoc($Abfrage);
            $Schluesselrollen = schluesselrollen_user_laden($Reservierung['user']);

            //Schlüsselrollen beachten
            if (($Schluesselrollen['hat_eig_schluessel'] == "1") OR ($Schluesselrollen['wg_hat_schluessel'] == "1")){
                echo "Reservierung ".$Reservierung['id']." - User hat eigenen Schl&uuml;ssel!<br>";
            } else {

                //Suche nach der Schlüsselausgabe
                $AnfrageLadeSchluesseluebergabe = "SELECT * FROM schluesselausgabe WHERE reservierung = '".$Reservierung['id']."' AND ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' AND storno_user = '0'";
                $AbfrageLadeSchluesseluebergabe = mysqli_query($link, $AnfrageLadeSchluesseluebergabe);
                $AnzahlLadeSchluesseluebergabe = mysqli_num_rows($AbfrageLadeSchluesseluebergabe);

                //Wenn es eine gibt:
                if ($AnzahlLadeSchluesseluebergabe > 0){

                    //Feststellen ob danach direkt eine Übernahme stattfindet
                    $AnfrageLadePotentielleÜbergabe = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$Reservierung['id']."' AND storno_user = '0'";
                    $AbfrageLadePotentielleÜbergabe = mysqli_query($link, $AnfrageLadePotentielleÜbergabe);
                    $AnzahlLadePotentielleÜbergabe = mysqli_num_rows($AbfrageLadePotentielleÜbergabe);

                    if ($AnzahlLadePotentielleÜbergabe > 0){
                        $NameVorlage = "mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt_mit_uebernahme";
                        echo "UEBERNAHME DANACH ";
                    } else if ($AnzahlLadePotentielleÜbergabe == 0){
                        $NameVorlage = "mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt";
                        echo "KEINE UEBERNAHME DANACH ";
                    }

                    $Typ = "".$NameVorlage."-".$Reservierung['id']."";
                    if (mail_schon_gesendet($Reservierung['user'], $Typ)){
                        //Nix machen
                        echo "Reservierung ".$Reservierung['id']." schon informiert!<br>";
                    } else {
                        //Mail senden
                        $UserMeta = lade_user_meta($Reservierung['user']);
                        $Bausteine = array();
                        $Bausteine['vorname_user'] = $UserMeta['vorname'];

                            if (mail_senden($NameVorlage, $UserMeta['mail'], $Reservierung['user'], $Bausteine, $Typ)){
                                echo "Reservierung ".$Reservierung['id']." erfolgreich gesendet!<br>";
                            } else {
                                echo "Reservierung ".$Reservierung['id']." - Fehler beim Senden der Mail!<br>";
                            }
                    }

                } else {
                    echo "Reservierung ".$Reservierung['id']." - Es ist keine Schl&uuml;ssel&uuml;bergabe erfolgt!<br>";
                }
            }
        }

        if ($Anzahl == 0){
            echo "Keine Reservierungen betroffen!";
        }

        echo "</p>";
    }

    function mail_erinnerung_schluesselrueckgabe_intervall(){

        echo "<p>Erinnerung R&uuml;ckgabe Zyklus<br>";
        $link = connect_db();

        $AbAnzahlTage = lade_einstellung('erinnerung-schluessel-zurueckgeben-intervall-beginn');
        $IntervallTage = lade_einstellung('erinnerung-schluessel-zurueckgeben-intervall-groesse');

        $AnfrageLadeAlleOffenenAusgaben = "SELECT * FROM schluesselausgabe WHERE ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' AND storno_user = '0'";
        $AbfrageLadeAlleOffenenAusgaben = mysqli_query($link, $AnfrageLadeAlleOffenenAusgaben);
        $AnzahlLadeAlleOffenenAusgaben = mysqli_num_rows($AbfrageLadeAlleOffenenAusgaben);

        if ($AnzahlLadeAlleOffenenAusgaben == 0){
            echo "Keine ausstehenden R&uuml;ckgaben!";
        } else if ($AnzahlLadeAlleOffenenAusgaben > 0){
            echo "".$AnzahlLadeAlleOffenenAusgaben." ausstehende R&uuml;ckgaben<br>";
            for ($a = 1; $a <= $AnzahlLadeAlleOffenenAusgaben; $a++){

                $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleOffenenAusgaben);
                $Reservierung = lade_reservierung($Ausgabe['reservierung']);
                $Typ = "mail_erinnerung_schluesselrueckgabe_intervall-".$Reservierung['id']."";

                //Sind wir über 2 Tage nach Res?
                $BefehlUeberZweiTage = "+ ".$AbAnzahlTage." days";
                if (time() > strtotime($BefehlUeberZweiTage, strtotime($Reservierung['ende']))){

                    $TimestampLetzteMail = timestamp_letzte_mail_gesendet($Reservierung['user'], $Typ);
                    $UserMeta = lade_user_meta($Reservierung['user']);
                    $DifferenzTage = tage_differenz_berechnen(timestamp(), $Reservierung['ende']);

                    $Bausteine = array();
                    $Bausteine['vorname_user'] = $UserMeta['vorname'];
                    $Bausteine['tage_seit_ende_res'] = $DifferenzTage;

                    if ($TimestampLetzteMail == FALSE){

                        //Es wurde noch nie eine Mail geschickt -> GO
                        if(mail_senden('mail_erinnerung_schluesselrueckgabe_intervall', $UserMeta['mail'], $Reservierung['user'], $Bausteine, $Typ)){

                            echo "Reservierung #".$Reservierung['id']." - Mail senden erfolgreich!<br>";
                        } else {
                            echo "Reservierung #".$Reservierung['id']." - Mail senden fehlgeschlagen!<br>";
                        }

                    } else {

                        //Es wurde bereits eine Mail geschickt -> Doublecheck ob wir wieder eine Senden dürfen
                        if (! ($DifferenzTage % $IntervallTage)){

                            if ($Reservierung['user'] == "188"){
                                //Es wurde noch nie eine Mail geschickt -> GO
                                //if(mail_senden('mail_erinnerung_schluesselrueckgabe_intervall', $UserMeta['mail'], $Reservierung['user'], $Bausteine, $Typ)){

                                    echo "Reservierung #".$Reservierung['id']." - Mail senden erfolgreich!<br>";
                                //} else {
                                    //echo "Reservierung #".$Reservierung['id']." - Mail senden fehlgeschlagen!<br>";
                                //}

                            } else {
                                echo "Reservierung #".$Reservierung['id']." - Mail sollte gesendet werden!<br>";
                            }

                        } else {
                            echo "Reservierung #".$Reservierung['id']." - Intervall noch nicht wieder eingetreten!<br>";
                        }
                    }
                } else {
                    echo "Reservierung #".$Reservierung['id']." - Grenze Intervallbeginn noch nicht begonnen!<br>";
                }
            }
        }

        echo "</p>";
    }

    function mail_erinnerung_schluesseluebergabe_eintragen_wart(){

        $link = connect_db();

        $Anfrage = "SELECT * FROM uebergaben WHERE storno_user = '0' AND beginn < '".timestamp()."' AND durchfuehrung = '0000-00-00 00:00:00'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        $Zeitgrenze = lade_einstellung('stunden-bis-uebergabe-eingetragen-sein-soll');
        $Zeitbefehl = "+ ".$Zeitgrenze." hours";

        echo "<p>Erinnerung Wart Schl&uuml;ssel&uuml;bergabe nachzutragen<br>";
        for ($a = 1; $a <= $Anzahl; $a++){
            $Uebergabe = mysqli_fetch_assoc($Abfrage);
            echo "&Uuml;bergabe: ".$Uebergabe['id']." - ";

            //Sind wir schon im Zeitfenster?
            $Zeitfenster = strtotime($Zeitbefehl, strtotime($Uebergabe['beginn']));
            if($Zeitfenster < time()){
                echo "Zeitfenster ist eingetreten - ";

                //Einstellung Wart
                $Benutzersettings = lade_user_settings($Uebergabe['wart']);
                if($Benutzersettings['erinnerung-wart-schluesseluebergabe-eintragen'] == "1"){

                    //Mail schon gesendet?
                    $Typ = "erinnerung-wart-schluesseluebergabe-eintragen-".$Uebergabe['id']."";
                    if(mail_schon_gesendet($Uebergabe['wart'], $Typ)){
                        echo "Mail schon gesendet<br>";
                    } else {
                        //Mail senden
                        $WartMeta = lade_user_meta($Uebergabe['wart']);
                        $Reservierung = lade_reservierung($Uebergabe['res']);
                        $UserReservierungMeta = lade_user_meta($Reservierung['user']);

                        $Bausteine = array();
                        $Bausteine['vorname_wart'] = $WartMeta['vorname'];
                        $Bausteine['uebergabe_id'] = $Uebergabe['id'];
                        $Bausteine['datum'] = date("d.m.Y", strtotime($Uebergabe['beginn']));
                        $Bausteine['zeitpunkt'] = date("G:i", strtotime($Uebergabe['beginn']));
                        $Bausteine['empfaenger'] = "".$UserReservierungMeta['vorname']." ".$UserReservierungMeta['nachname']."";

                        if(mail_senden('erinnerung-wart-schluesseluebergabe-eintragen', $WartMeta['mail'], $Uebergabe['wart'], $Bausteine, $Typ)){
                            echo "Mail gesendet<br>";
                        } else {
                            echo "Fehler beim senden der Mail<br>";
                        }
                    }

                } else {
                    echo "Wart m&ouml;chte keine Mail erhalten<br>";
                }

            } else if ($Zeitfenster > time()){
                echo "Zeitfenster ist noch nicht eingetreten<br>";
            }
        }
        echo "</p>";
    }

?>