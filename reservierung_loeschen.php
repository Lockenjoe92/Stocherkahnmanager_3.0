<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Reservierung l&ouml;schen - " . lade_db_einstellung('site_name');
$ResID = $_GET['id'];
$Mode = mode_feststellen_res_loeschen($ResID);
$Parser = parse_res_loeschen($Mode, $ResID);

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Reservierung l&ouml;schen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Reservierung l&ouml;schen</h1>';
$HTML .= section_builder($PageTitle);

# Eigene Reservierungen Normalo-user
$HTML .= reservierung_loeschen_seiteninhalt($Mode, $ResID, $Parser);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);



function reservierung_loeschen_seiteninhalt($Mode, $ResID, $Parser){

    $link = connect_db();
    $Anfrage = "SELECT * FROM reservierungen WHERE id = '$ResID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Reservierungen = mysqli_fetch_assoc($Abfrage);

    zeitformat();
    $ZeitformulierungAnfang = strftime("%A, %d. %B %G - %H:00 Uhr", strtotime($Reservierungen['beginn']));
    $ZeitformulierungEnde = strftime("%H:00 Uhr", strtotime($Reservierungen['ende']));


                $HTML = "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";

                    if ($Parser['success'] === NULL){
                        $HTML .= "<div class='section'>";
                        $HTML .= "<p>Du bist im Begriff folgende Reservierung zu l&ouml;schen:</p>";
                        $HTML .= "<ul>";
                        $HTML .= "<li>Reservierung #: ".$ResID."</li>";
                        $HTML .= "<li>Zeitraum: ".$ZeitformulierungAnfang." bis ".$ZeitformulierungEnde."</li>";
                                if ($Mode == "wart"){
                                    $User = lade_user_meta($Reservierungen['user']);
                                    $HTML .= "<li>User: ".$User['vorname']." ".$User['nachname']."</li>";
                                }
                        $HTML .= "</ul>";
                        $HTML .= "</div>";
                        $HTML .= "<div class='divider'></div>";
                        $HTML .= "<p>M&ouml;chtest du sicher fortfahren?</p>";
                        $HTML .= "<form method='post' action=''>";

                            if ($Mode == "wart"){
                                $HTML .= "<div class=\"input-field col s12\">";
                                $HTML .= "<textarea name='begruendung' id='begruendung'></textarea><label for='begruendung'>Gib bitte eine Begr&uuml;ndung f&uuml;r die Stornierung an!</label>";
                                $HTML .= "</div>";
                            }

                        $HTML .= "<div class='input-field'>";
                        $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='action' value=''><i class='material-icons tiny'>delete</i> L&ouml;schen</button>";
                        $HTML .= "</div>";
                        $HTML .= "<div class='input-field'>";

                        $HTML .= "<a href='wartwesen.php' class='btn waves-effect waves-light'>Abbrechen</a>";
                        $HTML .= "</div>";

                        $HTML .= "</form>";

                    } else {

                        if ($Parser['success'] === TRUE){
                            $HTML .= "<div class='section'>";
                            $HTML .= "<p>Die Reservierung wurde erfolgreich gel&ouml;scht!</p>";
                            $HTML .= "<p><a href='wartwesen.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a></p>";
                            $HTML .= "</div>";
                        } else if ($Parser['success'] === FALSE){
                            $HTML .= "<div class='section'>";
                            $HTML .= "<p><b>Fehler: </b><br>".$Parser['meldung']."</p>";
                            $HTML .= "<p><a href='wartwesen.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a></p>";
                            $HTML .= "</div>";
                        }
                    }
    $HTML .= "</div>";

    return $HTML;
}

function parse_res_loeschen($Mode, $Res){

    $Antwort = array();

    if(isset($_POST['action'])){
        if ($Mode == "wart"){

            if (empty($_POST['begruendung'])){
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Du musst als Wart eine Begr&uuml;ndung eingeben, wenn du eine Reservierung stornierst!";
            } else {
                $Antwort = reservierung_stornieren($Res, lade_user_id(), $_POST['begruendung']);
            }

        } else if ($Mode == "eigen"){
            $Antwort = reservierung_stornieren($Res, lade_user_id(), 'Durch User selber storniert.');
        }
    }
    return $Antwort;
}

function mode_feststellen_res_loeschen($ResID){
    $link = connect_db();

    $AnfrageLadeRes = "SELECT * FROM reservierungen WHERE id = '$ResID'";
    $AbfrageLadeRes = mysqli_query($link, $AnfrageLadeRes);
    $Reservierung = mysqli_fetch_assoc($AbfrageLadeRes);
    $UserID = lade_user_id();

    if ($UserID == $Reservierung['user']){
        //Jemand versucht seine eigene Res zu löschen
        return "eigen";
    } else {
        //jemand versucht eine fremde Res zu löschen - kontrolle ob Wart oder nicht
        $Benutzerrollen = lade_user_meta($UserID);
        if ($Benutzerrollen['wart'] == TRUE){
            //Ok wir machen weiter
            return "wart";
        } else {
            header("Location: ./my_reservations.php");
            die();
        }
    }
}

?>