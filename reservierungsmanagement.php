<?php

include_once "./ressourcen/ressourcen.php";

session_manager();
$link = connect_db();

$Benutzerrollen = benutzerrollen_laden('');

//Überprüfen ob der User auch das recht hat auf die Seite zuzugreifen
if ($Benutzerrollen['wart'] != true){
    header("Location: ./wartwesen.php");
    die();
}

//Parser auswerten
$Meldung = parser();

//SEITE Initiieren
echo "<html>";
header_generieren("Reservierungsmanagement");
echo "<body>";
navbar_generieren($Benutzerrollen, TRUE, 'Reservierungsmanagement');
echo "<main>";

echo "<div class='section hide-on-med-and-down'><h3 class='center-align'>Reservierungsmanagement</h3></div>";
echo "<div class='section hide-on-large-only'><h3 class='center-align'>Reservierungen verwalten</h3></div>";

echo "<div class='container'>";
echo "<div class='row'>";
echo "<div class='col s12 m9 l12'>";

//Parser ausgeben
if ($Meldung != ""){
    toast_ausgeben($Meldung);
}

//Stats (Durchgeführte Reservierungen diese Saison, Reservierungen insgesamt)
spalte_stats();

//Aktive Reservierungen
    //Objekt: ID, Datum, Von-Bis, User, Übergabestatus, Schlüsselstatus, Zahlstatus; Funktionen: bearbeiten, stornieren
    //Reservierung hinzufügen
    spalte_aktive_reservierungen();
//Vergangene Reservierungen
    //Objekt: ID, Datum, Von-Bis, User, Übergabestatus, Schlüsselstatus, Zahlstatus; Funktionen: bearbeiten, stornieren
    spalte_vergangene_reservierungen();
//Stornierte Reservierungen
    //Objekt: ID, Datum, Von-Bis, User, Übergabestatus, Schlüsselstatus, Zahlstatus; Funktionen: storno-aufheben
    spalte_stornierte_reservierungen();

echo "</div>";
echo "</div>";
echo "</div>";

echo "</main>";
footer_generieren();
echo "</body>";
echo "</html>";

function reservierungsobjekt_generieren($ResID, $Bearbeiten, $Stornieren, $StornoAufheben){

    //Allgemeines laden
    $link = connect_db();
    zeitformat();
    $Reservierung = lade_reservierung($ResID);
    $UserReservierung = lade_user_meta($Reservierung['user']);

    //Inhaltspunkte generieren
    $AngabenUser = "".$UserReservierung['vorname']." ".$UserReservierung['nachname']."";
    $FahrtDatum = strftime("%A, %d. %B %G", strtotime($Reservierung['beginn']));
    $Fahrzeiten = "".date("G", strtotime($Reservierung['beginn']))." bis ".date("G", strtotime($Reservierung['ende']))." Uhr";

        //Finanzen:
        if ($Reservierung['gratis_fahrt'] == "1"){
            $Finanzen = "<b>Gratisfahrt</b>";
            $Zahlungen = "nicht notwendig";
        } else if (intval($Reservierung['preis_geaendert']) > 0){

            //Nachsehen ob schon gezahlt wurde
            $Forderung = lade_forderung_res($ResID);
            $Zahlungen = lade_gezahlte_summe_forderung($Forderung['id']);

            if ($Zahlungen == 0){

                $Finanzen = "Preis&auml;nderung: ".$Reservierung['preis_geaendert']."&euro;";
                $Zahlungen = "keine";

            } else {

                if ($Zahlungen >= intval($Forderung['betrag'])){

                    $Finanzen = "Preis&auml;nderung: ".$Reservierung['preis_geaendert']."&euro;";
                    $Zahlungen = "bezahlt";

                } else if ($Zahlungen < intval($Forderung['betrag'])){

                    $Finanzen = "Preis&auml;nderung: ".$Reservierung['preis_geaendert']."&euro;";
                    $Zahlungen = "unvollst&auml;ndig: ".$Zahlungen."&euro;";

                }

            }

        } else if (($Reservierung['gratis_fahrt'] == "0") AND ($Reservierung['preis_geaendert'] == "0")){

            //Rollen
            $Schluesselrollen = schluesselrollen_user_laden($Reservierung['user']);
            $Benutzerrollen = benutzerrollen_laden($UserReservierung['username']);

            if (($Schluesselrollen['ehemaliger'] ==  "1") OR ($Schluesselrollen['gratis_fahrt'] ==  "1")){

                if ($Schluesselrollen['ehemaliger'] ==  "1"){
                    $Finanzen = "Nutzer ist ein Ehemaliger";
                    $Zahlungen = "nicht notwendig";
                } else if ($Schluesselrollen['gratis_fahrt'] ==  "1"){
                    $Finanzen = "Nutzer darf immer gratis fahren";
                    $Zahlungen = "nicht notwendig";
                }

            } else if ($Benutzerrollen['wart'] == true) {

                $Finanzen = "User ist ein Wart";
                $Zahlungen = "nicht notwendig";

            } else {

                //Nachsehen ob schon gezahlt wurde
                $Forderung = lade_forderung_res($ResID);
                $Zahlungen = lade_gezahlte_summe_forderung($Forderung['id']);

                if ($Zahlungen == 0){

                    $Finanzen = "".kosten_reservierung($ResID)."&euro;";
                    $Zahlungen = "keine";

                } else {

                    if ($Zahlungen >= intval($Forderung['betrag'])){

                        $Finanzen = "".kosten_reservierung($ResID)."&euro;";
                        $Zahlungen = "bezahlt";

                    } else if ($Zahlungen < intval($Forderung['betrag'])){

                        $Finanzen = "".kosten_reservierung($ResID)."&euro;";
                        $Zahlungen = "unvollst&auml;ndig: ".$Zahlungen."&euro;";

                    }

                }

            }

        }

        //Verknüpfungsfähigkeit
        $Anfrage = "SELECT id FROM reservierungen WHERE ((beginn = '".$Reservierung['ende']."') OR (ende = '".$Reservierung['beginn']."')) AND user = '".$Reservierung['user']."' AND storno_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if($Anzahl > 0){
            $ReservierungVerknuepfung = mysqli_fetch_assoc($Abfrage);

            //Span generieren
            $SpanUebergabeNotwendig = "<span class=\"new badge green darken-2\" data-badge-caption=\"Verkn&uuml;pfung m&ouml;glich\"></span>";

            //Button generieren
            $VerknuepfenButton = "<a class='btn waves-effect waves-light' href='reservierung_verknuepfen.php?res=".$Reservierung['id']."&res2=".$ReservierungVerknuepfung['id']."'><i class='small material-icons'>call_merge</i> Verkn&uuml;pfen</a>";
        }

    $HTML = "<li>";
        $HTML .= "<div class='collapsible-header'>".$SpanUebergabeNotwendig."<i class='large material-icons'>today</i>".$ResID." - ".$FahrtDatum." - ".$Fahrzeiten." - ".$AngabenUser."</div>";
        $HTML .= "<div class='collapsible-body'>";
            $HTML .= "<div class='container'>";
                $HTML .= "<form method='post'>";
                    $HTML .= "<ul class='collection'>";
                        $HTML .= "<li class='collection-item'>User: ".$AngabenUser."</li>";
                        $HTML .= "<li class='collection-item'>Datum: ".$FahrtDatum."</li>";
                        $HTML .= "<li class='collection-item'>Fahrzeit: ".$Fahrzeiten."</li>";
                        $HTML .= "<li class='collection-item'>Kosten: ".$Finanzen."</li>";
                        $HTML .= "<li class='collection-item'>Zahlungen: ".$Zahlungen."</li>";
                        $HTML .= "<li class='collection-item'>&Uuml;bergabestatus: ".$LetzeErinnerung."</li>";
                        $HTML .= "<li class='collection-item'>Schl&uuml;sselstatus: ".$LetzeErinnerung."</li>";

                        if ($StornoAufheben == TRUE){
                            $StornoUser = lade_user_meta($Reservierung['storno_user']);
                            $HTML .= "<li class='collection-item'>Storniert am ".strftime("%A, %d. %b. %G", strtotime($Reservierung['storno_zeit']))." durch ".$StornoUser['vorname']." ".$StornoUser['nachname']."</li>";
                        }

                        $HTML .= "<div class='input-field'>";

                            $HTML .= $VerknuepfenButton;

                            if ($Bearbeiten == TRUE){
                                $HTML .= "<div class='input-field'><button class='btn waves-effect waves-light' type='submit' name='action_reservierung_".$Reservierung['id']."_bearbeiten'><i class='small material-icons'>mode_edit</i> Bearbeiten</button></div>";
                            }
                            if ($Stornieren == TRUE){
                                $HTML .= "<div class='input-field'><button class='btn waves-effect waves-light' type='submit' name='action_reservierung_".$Reservierung['id']."_stornieren'><i class='small material-icons'>delete</i> Stornieren</button></div>";
                            }
                            if ($StornoAufheben == TRUE){
                                $HTML .= "<div class='input-field'><button class='btn waves-effect waves-light' type='submit' name='action_reservierung_".$Reservierung['id']."_storno_aufheben'><i class='small material-icons'>replay</i> Storno aufheben</button></div>";
                            }

                        $HTML .= "</li>";
                    $HTML .= "</ul>";
                $HTML .= "</form>";
            $HTML .= "</div>";
        $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}

function spalte_stats(){

    $link = connect_db();
    $AnfangDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $EndeDiesesJahr = "".date("Y")."-12-31 23:59:59";

    echo "<div class='section'>";
    echo "<h5 class='header hide-on-med-and-down'>Jahresst&auml;ts</h5>";
    echo "<h5 class='header hide-on-large-only center-align'>Jahresst&auml;ts</h5>";

    //Reservierungen Laden
    $AnfrageReservierungenLaden = "SELECT id FROM reservierungen WHERE storno_user = '0' AND beginn > '$AnfangDiesesJahr' AND ende < '$EndeDiesesJahr'";
    $AbfrageReservierungenLaden = mysqli_query($link, $AnfrageReservierungenLaden);
    $AnzahlReservierungenLaden = mysqli_num_rows($AbfrageReservierungenLaden);

    //Übergaben
    $AnfrageUebergabenLaden = "SELECT id FROM uebergaben WHERE storno_user = '0' AND durchfuehrung <> '0000-00-00 00:00:00' AND beginn > '$AnfangDiesesJahr' AND beginn < '$EndeDiesesJahr'";
    $AbfrageUebergabenLaden = mysqli_query($link, $AnfrageUebergabenLaden);
    $AnzahlUebergabenLaden = mysqli_num_rows($AbfrageUebergabenLaden);

    //Einnahmen-Ausgaben-Rechner
    $Gesamtdifferenz = gesamteinnahmen_jahr(date("Y")) - gesamtausgaben_jahr(date("y"));

    echo "<p><table>";
    echo "<tr><th>Reservierungen</th><th>&Uuml;bergaben</th><th>Einnahmen</th></tr>";
    echo "<tr><td>".$AnzahlReservierungenLaden."</td><td>".$AnzahlUebergabenLaden."</td><td>".$Gesamtdifferenz."&euro;</td></tr>";
    echo "</table></p>";

    echo "</div>";
}

function spalte_aktive_reservierungen(){

    $HTML = "";
    $link = connect_db();
    $ErsterDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $LetzterDiesesJahr = "".date("Y")."-12-31 23:59:59";

    //Lade Alle anstehenden Reservierungen
    $Anfrage = "SELECT id FROM reservierungen WHERE ende > '".timestamp()."' AND storno_user = '0' AND beginn > '$ErsterDiesesJahr' AND ende < '$LetzterDiesesJahr' ORDER BY ende ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>report_problem</i>Keine Reservierungen</div>";
        $HTML .= "</li>";

    } else if ($Anzahl > 0){

        for ($a = 1; $a <= $Anzahl; $a++){
            $Reservierung = mysqli_fetch_assoc($Abfrage);
            $HTML .= reservierungsobjekt_generieren($Reservierung['id'], TRUE, TRUE, FALSE);
        }

    }

    $HTML .= "<li>";
    $HTML .= "<div class='collapsible-header'><a href='reservierung_hinzufuegen.php'><i class='large material-icons'>note_add</i>Reservierung hinzuf&uuml;gen</a></div>";
    $HTML .= "</li>";

    echo "<div class='section'>";
        echo "<h5 class='header hide-on-med-and-down'>Anstehende Reservierungen</h5>";
        echo "<h5 class='header hide-on-large-only center-align'>Anstehende Reservierungen</h5>";
        echo "<ul class='collapsible popout' data-collapsible='accordion'>";
        echo $HTML;
        echo "</ul>";
    echo "</div>";

}
function spalte_vergangene_reservierungen(){
    $HTML = "";
    $link = connect_db();
    $ErsterDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $LetzterDiesesJahr = "".date("Y")."-12-31 23:59:59";

    //Lade Alle anstehenden Reservierungen
    $Anfrage = "SELECT id FROM reservierungen WHERE ende < '".timestamp()."' AND user <> '188' AND storno_user = '0' AND beginn > '$ErsterDiesesJahr' AND ende < '$LetzterDiesesJahr' ORDER BY ende ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>report_problem</i>Keine Reservierungen</div>";
        $HTML .= "</li>";

    } else if ($Anzahl > 0){

        for ($a = 1; $a <= $Anzahl; $a++){
            $Reservierung = mysqli_fetch_assoc($Abfrage);
            $HTML .= reservierungsobjekt_generieren($Reservierung['id'], TRUE, TRUE, FALSE);
        }

    }

    echo "<div class='section'>";
    echo "<h5 class='header hide-on-med-and-down'>Vergangene Reservierungen</h5>";
    echo "<h5 class='header hide-on-large-only center-align'>Vergangene Reservierungen</h5>";
    echo "<ul class='collapsible popout' data-collapsible='accordion'>";
    echo $HTML;
    echo "</ul>";
    echo "</div>";

}
function spalte_stornierte_reservierungen(){

    $HTML = "";
    $link = connect_db();
    $ErsterDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $LetzterDiesesJahr = "".date("Y")."-12-31 23:59:59";

    //Lade Alle anstehenden Reservierungen
    $Anfrage = "SELECT id FROM reservierungen WHERE storno_user <> '0' AND beginn > '$ErsterDiesesJahr' AND ende < '$LetzterDiesesJahr' AND user <> '188' ORDER BY beginn ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>report_problem</i>Keine Reservierungen</div>";
        $HTML .= "</li>";

    } else if ($Anzahl > 0){

        for ($a = 1; $a <= $Anzahl; $a++){
            $Reservierung = mysqli_fetch_assoc($Abfrage);
            $HTML .= reservierungsobjekt_generieren($Reservierung['id'], FALSE, FALSE, TRUE);
        }

    }

    echo "<div class='section'>";
    echo "<h5 class='header hide-on-med-and-down'>Stornierte Reservierungen</h5>";
    echo "<h5 class='header hide-on-large-only center-align'>Stornierte Reservierungen</h5>";
    echo "<ul class='collapsible popout' data-collapsible='accordion'>";
    echo $HTML;
    echo "</ul>";
    echo "</div>";


}
function parser(){

    $link = connect_db();
    $UserMeta = lade_user_meta(lade_user_id());
    $ErsterDiesesJahr = "".date("Y")."-01-01 00:00:01";
    $LetzterDiesesJahr = "".date("Y")."-12-31 23:59:59";
    $AnfrageLadeAlleReservierungenDiesesJahr = "SELECT id FROM reservierungen WHERE beginn > '$ErsterDiesesJahr' AND ende < '$LetzterDiesesJahr'";
    $AbfrageLadeAlleReservierungenDiesesJahr = mysqli_query($link, $AnfrageLadeAlleReservierungenDiesesJahr);
    $AnzahlLadeAlleReservierungenDiesesJahr = mysqli_num_rows($AbfrageLadeAlleReservierungenDiesesJahr);

    for ($a = 1; $a <= $AnzahlLadeAlleReservierungenDiesesJahr; $a++){

        $Reservierung = mysqli_fetch_assoc($AbfrageLadeAlleReservierungenDiesesJahr);

        //Stornieren?
        $HTMLstornieren = "action_reservierung_".$Reservierung['id']."_stornieren";
        if(isset($_POST[$HTMLstornieren])){
            $Begruendung = "Durch den Stocherkahnwart ".$UserMeta['vorname']." ".$UserMeta['nachname']." aus betrieblichen Gr&uuml;nden storniert.";
            $Ergebnis = reservierung_stornieren($Reservierung['id'], lade_user_id(), $Begruendung);
            return $Ergebnis['meldung'];
        }

        //Bearbeiten?
        $HTMLbearbeiten = "action_reservierung_".$Reservierung['id']."_bearbeiten";
        if(isset($_POST[$HTMLbearbeiten])){
            header("Location: ./reservierung_bearbeiten.php?id=".$Reservierung['id']."");
            die();
        }

        //Storno aufheben?
        $HTMLstornoaufheben = "action_reservierung_".$Reservierung['id']."_storno_aufheben";
        if(isset($_POST[$HTMLstornoaufheben])){
            return 'Funktion wird noch implementiert!';
        }

    }
}

?>