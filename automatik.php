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

auto_delete_user();

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

function auto_delete_user(){

	$link = connect_db();
	$Users = get_sorted_user_array_with_user_meta_fields('id');
	
	foreach ($Users as $User){
		
		$StopCount=0;
		if($User['ist_wart']=='true'){
         $StopCount++;
		}
		if($User['ist_admin']=='true'){
         $StopCount++;
		}
		
		if($StopCount==0){
			
			$yearNow = date("Y", strtotime('-2 years'));
			$ZeitGrenze = $yearNow."-12-31 23:59:59";
			$Anfrage2 = "SELECT id FROM reservierungen WHERE user = ".$User['id']." AND beginn > '".$ZeitGrenze."'";
			#var_dump($Anfrage2);

			$Abfrage2 = mysqli_query($link, $Anfrage2);
			$Anzahl2 = mysqli_num_rows($Abfrage2);
			
			if($Anzahl2>0){
          echo $UserMeta['vormame'].$UserMeta['nachname']." war aktiv<br>";
			} elseif($Anzahl2==0) {
				echo $UserMeta['vorname'].$UserMeta['nachname']." war seit über einem Jahr INAKTIV - KANN GELÖSCHT WERDEN!!!!<br>";
			}
		} elseif($StopCount>0) {
         echo $UserMeta['vorname'].$UserMeta['nachname'].' ist WICHTIG!<br>';
		}
	}

	return null;
}
?>