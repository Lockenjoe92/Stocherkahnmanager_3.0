<?php

include_once "./ressourcen/ressourcen.php";

session_manager();
$link = connect_db();

$Benutzerrollen = benutzerrollen_laden('');
$ReservierungID = $_GET['res'];

//SEITE Initiieren
echo "<html>";
header_generieren("Neue Schl&uuml;ssel&uuml;bergabe ausmachen");
echo "<body>";
navbar_generieren($Benutzerrollen, TRUE, 'schluesseluebergabe_neu_ausmachen');
echo "<main>";

$Parser = parser_uebergabe_hinzufuegen_ueser($ReservierungID);

echo "<div class='section'><h3 class='center-align'>Neue Schl&uuml;ssel&uuml;bergabe ausmachen</h3></div>";

echo "<div class='container'>";
echo "<div class='row'>";
echo "<div class='col s12 m9 l12'>";

echo card_resinfos_generieren($ReservierungID);
if (($Parser == NULL) OR ($Parser == FALSE)){
    schluesseluebergabe_ausmachen_moeglichkeiten_anzeigen($ReservierungID);
} else if ($Parser == TRUE){
    echo uebergabe_erfolgreich_eingetragen_user();
}

echo "</div>";
echo "</div>";
echo "</div>";

echo "</main>";
footer_generieren();
echo "</body>";
echo "</html>";

function schluesseluebergabe_ausmachen_moeglichkeiten_anzeigen($IDres){

    $link = connect_db();
    $Reservierung = lade_reservierung($IDres);

    $AnfrageLadeAlteUebergabe = "SELECT id, wart FROM uebergaben WHERE res = '$IDres' AND storno_user = '0'";
    $AbfrageLadeAlteUebergabe = mysqli_query($link, $AnfrageLadeAlteUebergabe);
    $AlteUebergabe = mysqli_fetch_assoc($AbfrageLadeAlteUebergabe);

        $BefehlGrenz = "- ".lade_einstellung('max-tage-vor-abfahrt-uebergabe')." days";
        $BefehlGrenzZwei = "- ".lade_einstellung('max-minuten-vor-abfahrt-uebergabe')." minutes";
        $GrenzstampNachEinstellung = date("Y-m-d G:i:s", strtotime($BefehlGrenz, strtotime($Reservierung['beginn'])));
        $GrenzstampNachEinstellungZwei = date("Y-m-d G:i:s", strtotime($BefehlGrenzZwei, strtotime($Reservierung['beginn'])));

        //Passen zeitlich?
        $AnfrageSucheTerminangebote = "SELECT * FROM terminangebote WHERE von > '$GrenzstampNachEinstellung' AND von < '$GrenzstampNachEinstellungZwei' AND bis > '".timestamp()."' AND storno_user = '0'";
        $AbfrageSucheTerminangebote = mysqli_query($link, $AnfrageSucheTerminangebote);
        $AnzahlSucheTerminangebote = mysqli_num_rows($AbfrageSucheTerminangebote);

        echo "<div class='section'>";
        echo "<ul class='collapsible popout' data-collapsible='accordion'>";

        if ($AnzahlSucheTerminangebote == 0){

            //Für seine reservierung gibts nichts passendes
            echo "<li>";
            echo "<div class='collapsible-header'><i class='large material-icons'>error</i>Kein passender Termin verf&uuml;gbar!</div>";
            echo "<div class='collapsible-body'>";
            echo "<p>Derzeit gibt es keinen passenden Termin f&uuml;r deine Reservierung. Bitte schau daher einfach in K&uuml;rze wieder vorbei:)</p>";
            echo "</div>";
            echo "</li>";

        } else if ($AnzahlSucheTerminangebote > 0){

            $Counter = 0;

            for ($a = 1; $a <= $AnzahlSucheTerminangebote; $a++){

                $Terminangebot = mysqli_fetch_assoc($AbfrageSucheTerminangebote);
                //Hat der Wart noch Schlüssel?
                if(wart_verfuegbare_schluessel($Terminangebot['wart']) > 0){
                    $Counter++;
                    terminangebot_listenelement_buchbar_generieren($Terminangebot['id']);
                } else if ((wart_verfuegbare_schluessel($Terminangebot['wart']) == 0) AND ($Terminangebot['wart'] == $AlteUebergabe['wart'])){
                    $Counter++;
                    terminangebot_listenelement_buchbar_generieren($Terminangebot['id']);
                }
            }

            if ($Counter == 0){
                //Hier gibts nichts, aber Zeit wäre dazu - liegt an shchlüsseln
                echo "<li>";
                echo "<div class='collapsible-header'><i class='large material-icons'>error</i>Keine Schl&uuml;ssel verf&uuml;gbar!</div>";
                echo "<div class='collapsible-body'>";
                echo "<p>Derzeit sind alle Schl&uuml;ssel im Umlauf. Daher k&ouml;nnen wir dir aktuell keinen Termin anbieten. Wir arbeiten daran immer schnell wieder an welche zu kommen. Bitte schau daher einfach in K&uuml;rze wieder vorbei:)</p>";
                echo "</div>";
                echo "</li>";
            }
        }
        echo "</ul>";
        echo "</div>";
        echo "<div class='section'>";
            echo "<a href='eigene_reservierungen.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a>";
        echo "</div>";
}

function parser_uebergabe_hinzufuegen_ueser($ReservierungID){

    $link = connect_db();
    $Reservierung = lade_reservierung($ReservierungID);

    $BefehlGrenz = "- ".lade_einstellung('max-tage-vor-abfahrt-uebergabe')." days";
    $BefehlGrenzZwei = "- ".lade_einstellung('max-minuten-vor-abfahrt-uebergabe')." minutes";
    $GrenzstampNachEinstellung = date("Y-m-d G:i:s", strtotime($BefehlGrenz, strtotime($Reservierung['beginn'])));
    $GrenzstampNachEinstellungZwei = date("Y-m-d G:i:s", strtotime($BefehlGrenzZwei, strtotime($Reservierung['beginn'])));

    //Passen zeitlich?
    $AnfrageSucheTerminangebote = "SELECT * FROM terminangebote WHERE von > '$GrenzstampNachEinstellung' AND von < '$GrenzstampNachEinstellungZwei' AND bis > '".timestamp()."' AND storno_user = '0'";
    $AbfrageSucheTerminangebote = mysqli_query($link, $AnfrageSucheTerminangebote);
    $AnzahlSucheTerminangebote = mysqli_num_rows($AbfrageSucheTerminangebote);

    $Antwort = NULL;

    if ($AnzahlSucheTerminangebote == 0){
        toast_ausgeben("Leider stehen derzeit keine Terminangebote zur Verf&uuml;gung!");
    } else if ($AnzahlSucheTerminangebote > 0){

        for ($a = 1; $a <= $AnzahlSucheTerminangebote; $a++){

            $Termin = mysqli_fetch_assoc($AbfrageSucheTerminangebote);

            $Suchbefehl = "action_termin_".$Termin['id']."";
            $Terminfeld = "zeitfenster_gewaehlt_terminangebot_".$Termin['id']."";
            $Kommentarfeld = "kommentar_uebergabe_".$Termin['id']."";

            if (isset($_POST[$Suchbefehl])){

                //Alte Übergabe laden und stornieren
                $AnfrageLadeUebergabe = "SELECT id FROM uebergaben WHERE res = '$ReservierungID' AND storno_user = '0'";
                $AbfrageLadeUebergabe = mysqli_query($link, $AnfrageLadeUebergabe);
                $AndereUebergabe = mysqli_fetch_assoc($AbfrageLadeUebergabe);

                uebergabe_stornieren($AndereUebergabe['id'], 'User hat auf andere &Uuml;bergabe gewechselt.');

                $hinzufueger = uebergabe_hinzufuegen($ReservierungID, $Termin['wart'], $Termin['id'], $_POST[$Terminfeld], $_POST[$Kommentarfeld], lade_user_id());

                if ($hinzufueger['success'] == TRUE){
                    toast_ausgeben($hinzufueger['meldung']);
                    $Antwort = TRUE;
                } else if ($hinzufueger['FALSE'] == TRUE){
                    toast_ausgeben($hinzufueger['meldung']);
                    $Antwort = FALSE;
                }
            }
        }
    }

    return $Antwort;
}

function uebergabe_erfolgreich_eingetragen_user(){

    $Antwort = "<div class='card-panel " .lade_einstellung('kalender-hintergrund'). " z-depth-3'>";
    $Antwort .= "<h5 class='center-align'>Gl&uuml;ckwunsch!</h5>";
    $Antwort .= "<div class='section center-align'>";
    $Antwort .= "<p>Nun hast du erfolgreich eine Schl&uuml;ssel&uuml;bergabe ausgemacht! Jetzt muss nur noch das Treffen klappen und es steht deinem Stocherabenteuer nichts mehr im Wege!</p>";
    $Antwort .= "<p><a href='eigene_reservierungen.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a></p>";
    $Antwort .= "</div>";
    $Antwort .= "</div>";

    return $Antwort;

}

?>