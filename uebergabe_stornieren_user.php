<?php

include_once "./ressourcen/ressourcen.php";

session_manager();
$link = connect_db();

$Benutzerrollen = benutzerrollen_laden('');
$UebergabeID = $_GET['id'];

//SEITE Initiieren
echo "<html>";
header_generieren("Schl&uuml;ssel&uuml;bergabe absagen");
echo "<body>";
navbar_generieren($Benutzerrollen, TRUE, 'schluesseluebergabe_absagen');
echo "<main>";

$Parser = parser_uebergabe_absagen_user($UebergabeID);

echo "<div class='section'><h3 class='center-align'>Schl&uuml;ssel&uuml;bergabe absagen</h3></div>";

echo "<div class='container'>";
echo "<div class='row'>";
echo "<div class='col s12 m9 l12'>";

    if ($Parser == NULL){
        $PromptText = "M&ouml;chtest du wirklich die Schl&uuml;ssel&uuml;bergabe f&uuml;r deine Reservierung l&ouml;schen?";
        prompt_karte_generieren('uebergabe_absagen', 'Absagen', 'uebergabe_infos_user.php?id=".."', 'Zur&uuml;ck', $PromptText, TRUE, 'kommentar_absage');
    } else if ($Parser == TRUE){
        zurueck_karte_generieren(TRUE, '', 'eigene_reservierungen.php');
    } else if ($Parser == FALSE){
        zurueck_karte_generieren(FALSE, '', 'eigene_reservierungen.php');
    }

echo "</div>";
echo "</div>";
echo "</div>";

echo "</main>";
footer_generieren();
echo "</body>";
echo "</html>";

function parser_uebergabe_absagen_user($UebergabeID){

    $link = connect_db();

    if (isset($_POST['uebergabe_absagen'])){

        $Uebergabe = lade_uebergabe($UebergabeID);
        $Reservierung = lade_reservierung($Uebergabe['res']);

        if (lade_user_id() != $Reservierung['user']){
            return false;
        } else {

            $Ergebnis = uebergabe_stornieren($UebergabeID, $_POST['kommentar_absage']);
            return $Ergebnis['success'];
        }
    }
}

?>