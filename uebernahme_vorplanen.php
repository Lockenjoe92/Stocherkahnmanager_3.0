<?php
/**
 * Created by PhpStorm.
 * User: Marc Haefeker
 * Date: 07.08.17
 * Time: 17:05
 */

include_once "./ressourcen/ressourcen.php";

//Housekeeping
session_manager();

//URL auslesen:
$Reservierung = $_GET['res'];

//Überprüfen ob der User auch das recht hat auf die Seite zuzugreifen
$Benutzerrollen = benutzerrollen_laden('');
$UebergabeID = $_GET['id'];
$Uebergabe = lade_uebergabe($UebergabeID);

if ($Benutzerrollen['wart'] != true){
    header("Location: ./wartwesen.php");
    die();
}


//SEITE Initiieren
echo "<html>";
header_generieren("&Uuml;bernahme vorplanen");
echo "<body>";
navbar_generieren($Benutzerrollen, TRUE, 'uebernahme_vorplanen');
echo "<main>";

echo "<div class='section'><h3 class='center-align'>&Uuml;bernahme vorplanen</h3></div>";

seiteninhalt_generieren($Reservierung);

echo "</main>";
footer_generieren();
echo "</body>";
echo "</html>";











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
        if(($ReservierungInfo['storno_user'] == "0") OR (time() > strtotime())){
            $DAUcounter++;
            $DAUerror .= "<br>";
        } else {
            //Reservierung bereits mit Übernahme/Übergabe versorgt
            if(){
                $DAUcounter++;
                $DAUerror .= "<br>";
            }

            if(){
                $DAUcounter++;
                $DAUerror .= "<br>";
            }
        }
    }

    //DAU auswerten
        if($DAUcounter > 0){

            zurueck_karte_generieren(FALSE, $DAUerror, 'wartwesen.php');

        } else {

            //Vollkommen egal von welcher Reservierung übernommen wird - hauptsache sie ist noch nicht abgeschlossen und hat entweder ne Übernahme oder Übergabe gebucht:)
            echo "Vollkommen egal von welcher Reservierung übernommen wird - hauptsache sie ist noch nicht abgeschlossen und hat entweder ne Übernahme oder Übergabe gebucht:)";
            echo "<br>Suche nach passenden Reservierungen:";

            $ReservierungInfos = lade_reservierung($Reservierung);
            $Anfrage = "SELECT * FROM reservierungen WHERE beginn > '01-01-".date('Y')." 00:00:01' AND ende <= '".$ReservierungInfos['beginn']."' AND storno_user = '0' ORDER BY beginn ASC";
            $Abfrage = mysqli_query($link, $Anfrage);
            $Anzahl = mysqli_num_rows($Abfrage);

            if ($Anzahl == 0){
                echo "Keine passenden Reservierungen!";
            } else if ($Anzahl > 0){
                echo "".$Anzahl." unabgeschlossene/aktive reservierungen vor der gewählten Reservierung<br>";

                for ($a = 1; $a <= $Anzahl; $a++){
                    //Hat Res eine Schlüsselausgabe die bereits zurückgegeben wurde? -> Reservierung ist abgeschlossen:
                    $GefundeneReservierung = mysqli_fetch_assoc($Abfrage);
                    $AnfrageZwei = "SELECT rueckgabe FROM schluesselausgabe WHERE reservierung = '".$GefundeneReservierung['id']."' AND storno_user = '0'";
                    $AbfrageZwei = mysqli_query($link, $AnfrageZwei);
                    $AnzahlZwei = mysqli_num_rows($AbfrageZwei);

                    if ($AnzahlZwei == 0){

                        //Kein Schlüssel bislang ausgegeben -> Prüfen ob Übergabe/Übernahme geplant
                        if(res_hat_uebergabe($GefundeneReservierung['id'])){
                            echo "Reservierung ".$GefundeneReservierung['id']." kommt in Frage - Hat Übergabe gebucht<br>";
                        }
                        if(res_hat_uebernahme($GefundeneReservierung['id'])){
                            echo "Reservierung ".$GefundeneReservierung['id']." kommt in Frage - Hat Übernahme gebucht<br>";
                        }

                    } else if ($AnzahlZwei == 1){

                        $ErgebnisZwei = mysqli_fetch_assoc($AbfrageZwei);
                        if ($ErgebnisZwei['rueckgabe'] == "0000-00-00 00:00:00"){

                            echo "Reservierung ".$GefundeneReservierung['id']." kommt in Frage<br>";

                        }
                    }
                }
            }
        }
    }

?>