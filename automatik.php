<?php
/**
 * Gets called from cronjob
 *
 * Jede Schlüsselübernahme wird geprüft: Wenn Anfangsres vorbei ist, passiert folgendes:
 *  - Schlüssel wird für anfangsres als zurückgegeben markiert
 *  - Schlüssel wird an folgeres ausgeteilt
 */

include_once "./ressources/ressourcen.php";

auto_update_uebernahmen();

function auto_update_uebernahmen(){

    $link = connect_db();

    $AnfrageLadeAlleUebernahmen = "SELECT * FROM uebernahmen WHERE storno_user = '0'";
    $AbfrageLadeAlleUebernahmen = mysqli_query($link, $AnfrageLadeAlleUebernahmen);
    $AnzahlLadeAlleUebernahmen = mysqli_num_rows($AbfrageLadeAlleUebernahmen);

    echo "<h3>Auto Update &Uuml;bernahmen</h3>";
    echo "<p>Anzahl aller &Uuml;bernahmen: ".$AnzahlLadeAlleUebernahmen."</p><p>";

    for ($a = 1; $a <= $AnzahlLadeAlleUebernahmen; $a++){
        $Uebernahme = mysqli_fetch_assoc($AbfrageLadeAlleUebernahmen);

        $IDReservierungDavor = $Uebernahme['reservierung_davor'];
        $IDReservierungDanach = $Uebernahme['reservierung'];
        $ReservierungDavor = lade_reservierung($IDReservierungDavor);#

        if (time() < strtotime($ReservierungDavor['ende'])){

            echo "".$Uebernahme['id'].": Reservierung davor noch nicht vorbei!<br>";

        } else if (time() > strtotime($ReservierungDavor['ende'])) {

            echo "".$Uebernahme['id'].": ";

            $SchluesselausgabeDavor = lade_schluesselausgabe_reservierung($IDReservierungDavor);

            //Nur weiter wenn Rückgabe noch nicht bereits festgehalten!
            if ($SchluesselausgabeDavor['rueckgabe'] == "0000-00-00 00:00:00"){
                //Rückgabe des Schlüssels festhalten
                schluesselrueckgabe_festhalten($SchluesselausgabeDavor['schluessel']);
                echo "R&uuml;ckgabe Reservierung davor festgehalten - ";

                //Neuausgabe an Reservierung danach festhalten
                schluessel_an_user_weitergeben($SchluesselausgabeDavor['uebergabe'], $SchluesselausgabeDavor['schluessel'], $IDReservierungDanach, $SchluesselausgabeDavor['wart']);
                echo "Schl&uuml;ssel an Reservierung danach ausgegeben.";
            } else {
                echo "R&uuml;ckgabe bereits festgehalten!";
            }

            echo "<br>";
        }
    }

    echo "</p>";

}

?>