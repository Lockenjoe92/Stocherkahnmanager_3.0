<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Schl&uuml;ssel&uuml;bernahme vorplanen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;ssel&uuml;bernahme vorplanen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">&Uuml;bernahme vorplanen</h1>';
$HTML = section_builder($PageTitle);

$ReservierungID = $_GET['id'];
$HTML .= card_resinfos_generieren($ReservierungID);
$HTML .= seiteninhalt_generieren($ReservierungID);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


















function seiteninhalt_generieren($Reservierung){

    $link = connect_db();
    //DAU-Abfangen:
    $DAUcounter = 0;
    $DAUerror = "";

    //Keine reservierung angegeben
    if(($Reservierung == "") == ($Reservierung == "0")){
        $DAUcounter++;
        $DAUerror .= "Du hast keine Reservierung angegeben!<br>";
    } else if (intval($Reservierung) > 0) {

        //Reservierung inzwischen abgelaufen/storniert
        $ReservierungInfo = lade_reservierung($Reservierung);
        if(($ReservierungInfo['storno_user'] != "0") OR (time() > strtotime($ReservierungInfo['bis']))){
            $DAUcounter++;
            $DAUerror .= "Reservierung ist bereits abgelaufen/storniert!<br>";
        }
    }

    //DAU auswerten
        if($DAUcounter > 0){
            $HTML = zurueck_karte_generieren(FALSE, $DAUerror, 'wartwesen.php');
        } else {

            //Vollkommen egal von welcher Reservierung übernommen wird - hauptsache sie ist noch nicht abgeschlossen und hat entweder ne Übernahme oder Übergabe gebucht:)
            $HTML = "Vollkommen egal von welcher Reservierung übernommen wird - hauptsache sie ist noch nicht abgeschlossen und hat entweder ne Übernahme oder Übergabe gebucht:)";
            $HTML .= "<br>Suche nach passenden Reservierungen:";

            $ReservierungInfos = lade_reservierung($Reservierung);
            $Anfrage = "SELECT * FROM reservierungen WHERE beginn > '01-01-".date('Y')." 00:00:01' AND ende <= '".$ReservierungInfos['beginn']."' AND storno_user = '0' ORDER BY beginn ASC";
            $Abfrage = mysqli_query($link, $Anfrage);
            $Anzahl = mysqli_num_rows($Abfrage);

            if ($Anzahl == 0){
                $HTML .= "Keine passenden Reservierungen!";
            } else if ($Anzahl > 0){
                $HTML .= "".$Anzahl." unabgeschlossene/aktive reservierungen vor der gewählten Reservierung<br>";

                for ($a = 1; $a <= $Anzahl; $a++){
                    //Hat Res eine Schlüsselausgabe die bereits zurückgegeben wurde? -> Reservierung ist abgeschlossen:
                    $GefundeneReservierung = mysqli_fetch_assoc($Abfrage);
                    $AnfrageZwei = "SELECT rueckgabe FROM schluesselausgabe WHERE reservierung = '".$GefundeneReservierung['id']."' AND storno_user = '0'";
                    $AbfrageZwei = mysqli_query($link, $AnfrageZwei);
                    $AnzahlZwei = mysqli_num_rows($AbfrageZwei);

                    if ($AnzahlZwei == 0){

                        //Kein Schlüssel bislang ausgegeben -> Prüfen ob Übergabe/Übernahme geplant
                        if(res_hat_uebergabe($GefundeneReservierung['id'])){
                            $HTML .= "Reservierung ".$GefundeneReservierung['id']." kommt in Frage - Hat Übergabe gebucht<br>";
                        }
                        if(res_hat_uebernahme($GefundeneReservierung['id'])){
                            $HTML .= "Reservierung ".$GefundeneReservierung['id']." kommt in Frage - Hat Übernahme gebucht<br>";
                        }

                    } else if ($AnzahlZwei == 1){

                        $ErgebnisZwei = mysqli_fetch_assoc($AbfrageZwei);
                        if ($ErgebnisZwei['rueckgabe'] == "0000-00-00 00:00:00"){

                            $HTML .= "Reservierung ".$GefundeneReservierung['id']." kommt in Frage<br>";

                        }
                    }
                }
            }
        }
    }

?>