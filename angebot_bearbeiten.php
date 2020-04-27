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

$IDangebot = $_GET['id'];

//SEITE Initiieren
echo "<html>";
header_generieren("Terminangebot bearbeiten");
echo "<body>";
navbar_generieren($Benutzerrollen, TRUE, 'angebot_bearbeiten');
echo "<main>";

echo "<div class='section'><h3 class='center-align'>Terminangebot bearbeiten</h3></div>";

echo "<div class='container'>";
echo "<div class='row'>";
echo "<div class='col s12 m9 l12'>";

$Parser = parser_angebot_bearbeiten($IDangebot);

if ($Parser['success'] === NULL){
    infos_section($IDangebot);
    angebot_bearbeiten_karte($IDangebot);
} else if ($Parser['success'] === FALSE){
    zurueck_karte_generieren(FALSE, $Parser['meldung'], 'termine.php');
} else if ($Parser['success'] === TRUE){
    zurueck_karte_generieren(TRUE, 'Terminangebot wurde erfolgreich ge&auml;ndert!', 'termine.php');
}

echo "</div>";
echo "</div>";
echo "</div>";

echo "</main>";
footer_generieren();
echo "</body>";
echo "</html>";


function infos_section($IDangebot){

    $Angebot = lade_terminangebot($IDangebot);
    zeitformat();

    echo "<div class='section'>";
    echo "<div class='card-panel " .lade_einstellung('kalender-hintergrund'). " z-depth-3'>";
    echo "<h5>Informationen zum &Uuml;bergabeangebot</h5>";
    echo "<ul class='collection'>";
    echo "<li class='collection-item'>Datum: ".strftime("%A, %d. %B %G", strtotime($Angebot['von']))."</li>";
    echo "<li class='collection-item'>Zeitraum: ".date("G:i", strtotime($Angebot['von']))." bis ".date("G:i", strtotime($Angebot['bis']))." Uhr</li>";
    echo "<li class='collection-item'>Treffpunkt: ".$Angebot['ort']."</li>";
    echo "<li class='collection-item'>Entstandene &Uuml;bergaben: ".lade_entstandene_uebergaben($IDangebot)."</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
}
function angebot_bearbeiten_karte($IDangebot){

    $Angebot = lade_terminangebot($IDangebot);

    if ($Angebot['terminierung'] != "0000-00-00 00:00:00"){
        $CheckboxTermin = "checked";
    } else if ($Angebot['terminierung'] == "0000-00-00 00:00:00"){
        $CheckboxTermin = "unchecked";
    }

    echo "<div class='card-panel " .lade_einstellung('kalender-hintergrund'). " z-depth-3'>";
        echo "<p>Du kannst den Zeitraum und die Terminierung des Angebots ver&auml;ndern - um den Ort zu &auml;ndern lege bitte ein neues Angebot an!</p>";
        echo "<p>Bereits entstandene &Uuml;bergaben und Termine werden von der &Auml;nderung nicht betroffen! Storniere diese ggf. direkt.</p>";

            echo "<div class='section'>";
            echo "<form method='POST'>";
            echo "<div class='container'>";

            echo "<div class='row'>";
                echo "<div class=\"input-field col s3\">";
                echo "<i class=\"material-icons prefix\">schedule</i>";
                echo dropdown_stunden('stunde_beginn_terminangebot_anlegen', date("G", strtotime($Angebot['von'])));
                echo "<label for=\"stunde_beginn_terminangebot_anlegen\">Uhrzeit Beginn</label>";
                echo "</div>";
                echo "<div class=\"input-field col s2\">";
                echo dropdown_zentel_minuten('minute_beginn_terminangebot_anlegen', date("i", strtotime($Angebot['von'])), 00);
                echo "<label for=\"minute_beginn_terminangebot_anlegen\">Minuten</label>";
                echo "</div>";
                echo "<div class=\"input-field col s3\">";
                echo "<i class=\"material-icons prefix\">schedule</i>";
                echo dropdown_stunden('stunde_ende_terminangebot_anlegen', date("G", strtotime($Angebot['bis'])));
                echo "<label for=\"stunde_ende_terminangebot_anlegen\">Uhrzeit Ende</label>";
                echo "</div>";
                echo "<div class=\"input-field col s2\">";
                echo dropdown_zentel_minuten('minute_ende_terminangebot_anlegen', date("i", strtotime($Angebot['bis'])), 00);
                echo "<label for=\"minute_ende_terminangebot_anlegen\">Minuten</label>";
                echo "</div>";
            echo "</div>";
            echo "<div class='row'>";
                echo "<div class=\"input-field col s6\">";
                echo "<i class=\"material-icons prefix\">alarm_on</i>";
                echo "<input type='checkbox' name='terminierung_terminangebot_anlegen' id='terminierung_terminangebot_anlegen' " .$CheckboxTermin. ">";
                echo "<label for=\"terminierung_terminangebot_anlegen\">Terminierung aktivieren</label>";
                echo "</div>";
                echo "<div class=\"input-field col s6\">";
                echo dropdown_stunden('stunden_terminierung_terminangebot_anlegen', date("G", strtotime($Angebot['terminierung'])));
                echo "<label for='stunden_terminierung_terminangebot_anlegen'>Terminierung Stunden</label>";
                echo "</div>";
            echo "</div>";

            echo "<div class='divider'></div>";

            echo "<div class='row'>";
            echo "<div class=\"input-field col s12\">";
            echo "<i class=\"material-icons prefix\">comment</i>";
            echo "<textarea name='kommentar_terminangebot_anlegen' id='kommentar_terminangebot_anlegen' class='materialize-textarea'>".$Angebot['kommentar']."</textarea>";
            echo "<label for=\"kommentar_terminangebot_anlegen\">Optional: weiterer Kommentar</label>";
            echo "</div>";
            echo "</div>";

            echo "<div class='divider'></div>";

            echo "<div class='row'>";
            echo "<div class=\"input-field\">";
            echo "<button class='btn waves-effect waves-light' type='submit' name='action_terminangebot_bearbeiten' value=''><i class=\"material-icons left\">send</i>Bearbeiten</button>";
            echo "</div>";
            echo "<div class=\"input-field\">";
            echo "<button class='btn waves-effect waves-light' type='reset' name='reset_terminangebot_bearbeiten' value=''><i class=\"material-icons left\">clear_all</i>Reset</button>";
            echo "</div>";
            echo "</div>";

            echo "</div>";
            echo "</form>";
            echo "</div>";

    echo "</div>";

}
function parser_angebot_bearbeiten($IDangebot){

    $DAUcounter = 0;
    $DAUerror = "";
    $Antwort = array();
    $Angebot = lade_terminangebot($IDangebot);

    //Terminangebot bereits gelöscht?
    if($Angebot['storno_user'] === "1"){
        $DAUcounter++;
        $DAUerror .= "Das Angebot wurde bereits storniert!<br>";
    }

    //Keine ID in URL
    if($IDangebot === ""){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine zu l&ouml;schende &Uuml;bergabe ausgew&auml;hlt!<br>";
    }

    if (isset($_POST['action_terminangebot_bearbeiten'])){
        //Zeiten verdreht?
        $EingabeAnfang = "".date("Y-m-d", strtotime($Angebot['von']))." ".$_POST['stunde_beginn_terminangebot_anlegen'].":".$_POST['minute_beginn_terminangebot_anlegen'].":00";
        $EingabeEnde = "".date("Y-m-d", strtotime($Angebot['von']))." ".$_POST['stunde_ende_terminangebot_anlegen'].":".$_POST['minute_ende_terminangebot_anlegen'].":00";

        if (strtotime($EingabeAnfang) > strtotime($EingabeEnde)){
            $DAUcounter++;
        $DAUerror .= "Der Anfang darf nicht nach dem Ende liegen!<br>";
        }

        if (strtotime($EingabeAnfang) == strtotime($EingabeEnde)){
            $DAUcounter++;
            $DAUerror .= "Die Zeiten d&uuml;rfen nicht identisch sein!<br>";
        }

        if ((isset($_POST['terminierung_terminangebot_anlegen'])) AND ($_POST['stunden_terminierung_terminangebot_anlegen'] == "")){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Angabe zur Dauer der Terminierung angeben, wenn du diese einschalten m&oumlchtest!<br>";
        }
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){
        $link = connect_db();
        if(isset($_POST['action_terminangebot_bearbeiten'])){

            if (isset($_POST['terminierung_terminangebot_anlegen'])){
                $Befehl = "- ".$_POST['stunden_terminierung_terminangebot_anlegen']." hours";
                $Terminierung = date('Y-m-d G:i:s', strtotime($Befehl, strtotime($EingabeAnfang)));
            } else {
                $Terminierung = "0000-00-00 00:00:00";
            }

            $Anfrage = "UPDATE terminangebote SET von = '$EingabeAnfang', bis = '$EingabeEnde', terminierung = '$Terminierung', kommentar = '".$_POST['kommentar_terminangebot_anlegen']."' WHERE id = '$IDangebot'";
            if(mysqli_query($link, $Anfrage)){
                $Antwort['success'] = TRUE;
                $Antwort['meldung'] = "Terminangebot erfolgreich bearbeitet!";
                toast_ausgeben('Terminangebot erfolgreich bearbeitet!');
            } else {
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Datenbankfehler!";
            }
        } else if (!isset($_POST['action_terminangebot_bearbeiten'])) {
            $Antwort['success'] = NULL;
        }
    }

    return $Antwort;
}
?>