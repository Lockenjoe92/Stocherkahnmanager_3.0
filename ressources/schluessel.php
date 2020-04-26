<?php

function lade_schluesseldaten($ID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM schluessel WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    return mysqli_fetch_assoc($Abfrage);

}
function lade_letze_erinnerung_schluesselrueckgabe($IDres){

    $link = connect_db();
    $Reservierung = lade_reservierung($IDres);
    $Typ = "mail_erinnerung_schluesselrueckgabe_intervall-".$IDres."";
    $TypZwei = "mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt-".$IDres."";

    $Anfrage = "SELECT timestamp FROM mail_protokoll WHERE empfaenger = '".$Reservierung['user']."' AND typ = '$Typ' ORDER BY timestamp DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl == 0){

        $AnfrageZwei = "SELECT timestamp FROM mail_protokoll WHERE empfaenger = '".$Reservierung['user']."' AND typ = '$TypZwei' ORDER BY timestamp DESC";
        $AbfrageZwei = mysqli_query($link, $AnfrageZwei);
        $AnzahlZwei = mysqli_num_rows($AbfrageZwei);

        if ($AnzahlZwei == 0){
            return null;
        } else if ($AnzahlZwei > 0){
            $ErgebnisZwei = mysqli_fetch_assoc($AbfrageZwei);
            return $ErgebnisZwei['timestamp'];
        }

    } else if ($Anzahl > 0){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        return $Ergebnis['timestamp'];
    }
}

function schluesselrueckgabe_festhalten($ID){

    $link = connect_db();

    $AnfrageLadeAlleOffenenAusgaben = "SELECT id FROM schluesselausgabe WHERE schluessel = '$ID' AND ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' AND storno_user = '0'";
    $AbfrageLadeAlleOffenenAusgaben = mysqli_query($link, $AnfrageLadeAlleOffenenAusgaben);
    $AnzahlLadeAlleOffenenAusgaben = mysqli_num_rows($AbfrageLadeAlleOffenenAusgaben);

    $Error = 0;

    for ($a = 1; $a <= $AnzahlLadeAlleOffenenAusgaben; $a++){

        $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleOffenenAusgaben);#

        $AnfrageUpdate = "UPDATE schluessel SET akt_user = '0', akt_ort = 'rueckgabekasten' WHERE id = '$ID'";
        if (mysqli_query($link, $AnfrageUpdate)){

            $AnfrageRueckgabeFesthalten = "UPDATE schluesselausgabe SET rueckgabe = '".timestamp()."' WHERE id = '".$Ausgabe['id']."'";
            if (!mysqli_query($link, $AnfrageRueckgabeFesthalten)){
                $Error++;
            } else {

                add_protocol_entry(lade_user_id(), 'Schluesselr&uuml;ckgabe durch '.lade_user_id().' festgehalten.', 'mail');

            }

        } else {
            $Error++;
        }
    }

    if ($Error == 0){
        return true;
    } else {
        return false;
    }

}