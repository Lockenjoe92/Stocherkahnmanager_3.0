<?php

include_once "./ressourcen/ressourcen.php";

session_manager();
$link = connect_db();

$UebergabeID = $_GET['id'];
if (isset($_POST['id'])){
    $UebergabeID = $_POST['id'];
}

$Benutzerrollen = benutzerrollen_laden('');

//Überprüfen ob der User auch das recht hat auf die Seite zuzugreifen
if ($Benutzerrollen['wart'] != true){
    header("Location: ./wartwesen.php");
    die();
}

//SEITE Initiieren
echo "<html>";
header_generieren("Schl&uuml;ssel&uuml;bergabe absagen");
echo "<body>";
navbar_generieren($Benutzerrollen, TRUE, 'uebergabe_absagen_wart');
echo "<main>";

$Parser = parser($UebergabeID);

echo "<div class='section'><h3 class='center-align'>Schl&uuml;ssel&uuml;bergabe absagen</h3></div>";

echo "<div class='container'>";
echo "<div class='row'>";
echo "<div class='col s12 m9 l12'>";

    if ($Parser == NULL){
        prompt_karte_generieren('absagen', 'Absagen', 'termine.php', 'Abbrechen', 'M&ouml;chtest du diese &Uuml;bergabe wirklich absagen? Wenn ja, gib bitte einen Kommentar f&uuml;r den User an.', TRUE, 'kommentar');
    } else {
        if($Parser == TRUE){
            zurueck_karte_generieren(TRUE, '', 'termine.php');
        } else if ($Parser == FALSE){
            zurueck_karte_generieren(FALSE, '', 'termine.php');
        }
    }

echo "</div>";
echo "</div>";
echo "</div>";

echo "</main>";
footer_generieren();
echo "</body>";
echo "</html>";

function parser($UebergabeID){

    if (isset($_POST['absagen'])){

        $Ergebnis = uebergabe_stornieren($UebergabeID, $_POST['kommentar']);
        if ($Ergebnis['success'] == TRUE){
            return true;
        } else if ($Ergebnis['success'] == FALSE){
            toast_ausgeben($Ergebnis['meldung']);
            return false;
        }

    } else {
        return null;
    }

}

?>