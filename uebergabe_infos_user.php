<?php

include_once "./ressourcen/ressourcen.php";

session_manager();
$link = connect_db();

$Benutzerrollen = benutzerrollen_laden('');
$UebergabeID = $_GET['id'];
$Uebergabe = lade_uebergabe($UebergabeID);
$Terminangebot = lade_terminangebot($Uebergabe['terminangebot']);
$WartMeta = lade_user_meta($Uebergabe['wart']);

//Warteinstellungen bezgl. pers Daten laden
if(1==1){
    $Wartinfos = "".$WartMeta['vorname']." ".$WartMeta['nachname']."";
}

zeitformat();

//SEITE Initiieren
echo "<html>";
header_generieren("Deine Schl&uuml;ssel&uuml;bergabe");
echo "<body>";
navbar_generieren($Benutzerrollen, TRUE, 'schluesseluebergabe_info_user');
echo "<main>";

$Parser = parser_uebergabe_infos_ueser($UebergabeID);

echo "<div class='section'><h3 class='center-align'>Deine Schl&uuml;ssel&uuml;bergabe</h3></div>";

echo "<div class='container'>";
echo "<div class='row'>";
echo "<div class='col s12 m9 l12'>";

echo "<div class='card-panel " .lade_einstellung('kalender-hintergrund'). " z-depth-3'>";
echo "<h5 class='center-align'>Informationen</h5>";
echo "<div class='container'>";
    echo "<table>";
        echo "<tr><th><i class='material-icons tiny'>today</i> Datum:</th><td> ".strftime("%A, %d. %B %G", strtotime($Uebergabe['beginn']))."</td></tr>";
        echo "<tr><th><i class='material-icons tiny'>alarm_on</i> Beginn:</th><td> ".strftime("%H:%M Uhr", strtotime($Uebergabe['beginn']))."</td></tr>";
        echo "<tr><th><i class='material-icons tiny'>schedule</i> Dauer:</th><td> ca. ".lade_einstellung('dauer-uebergabe-minuten')." Minuten</td></tr>";
        echo "<tr><th><i class='material-icons tiny'>room</i> Treffpunkt:</th><td> ".$Terminangebot['ort']."</td></tr>";
        echo "<tr><th><i class='material-icons tiny'>toll</i> Kosten deiner Reservierung:</th><td> ".kosten_reservierung($Uebergabe['res'])."&euro;</td></tr>";
        echo "<tr><th><i class='material-icons tiny'>android</i> Zust&auml;ndiger Wart:</th><td> ".$Wartinfos."</td></tr>";
echo "</table>";
    echo "<div class='section'>";
        echo "<div class='input-field'><a href='eigene_reservierungen.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a></div>";
        echo "<div class='input-field'><a href='neue_uebergabe_ausmachen.php?res=".$Uebergabe['res']."' class='btn waves-effect waves-light'>Andere &Uuml;bergabe ausmachen</a></div>";
        echo "<div class='input-field'><a href='uebergabe_stornieren_user.php?id=".$UebergabeID."' class='btn waves-effect waves-light'>L&ouml;schen</a></div>";
    echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='section'>";
echo "<ul class='collapsible popout' data-collapsible='accordion'>";
    echo "<li>";
    echo "<div class='collapsible-header'><i class='large material-icons'>info</i>Was muss ich dabei haben?</div>";
    echo "<div class='collapsible-body'>";
    echo "<div class='container'>";
        echo html_entity_decode(lade_einstellung('text-info-uebergabe-dabei-haben'));
    echo "</div>";
    echo "</div>";
    echo "</li>";
    echo "<li>";
    echo "<div class='collapsible-header'><i class='large material-icons'>info</i>Ablauf?</div>";
    echo "<div class='collapsible-body'>";
    echo "<div class='container'>";
        echo html_entity_decode(lade_einstellung('text-info-uebergabe-ablauf'));
    echo "</div>";
    echo "</div>";
    echo "</li>";
    echo "<li>";
    echo "<div class='collapsible-header'><i class='large material-icons'>info</i>Einweisung?</div>";
    echo "<div class='collapsible-body'>";
    echo "<div class='container'>";
        echo html_entity_decode(lade_einstellung('text-info-uebergabe-einweisung'));
    echo "</div>";
    echo "</div>";
    echo "</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";

echo "</main>";
footer_generieren();
echo "</body>";
echo "</html>";

function parser_uebergabe_infos_ueser($UebergabeID){
    return null;
}

?>