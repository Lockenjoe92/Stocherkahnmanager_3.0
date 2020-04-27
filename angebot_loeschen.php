<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Terminangebot l&ouml;schen - " . lade_db_einstellung('site_name');
$IDangebot = $_GET['id'];
$AngebotLoeschenParser = parser_angebot_loeschen($IDangebot);

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Terminangebot l&ouml;schen</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Terminangebot l&ouml;schen</h1>';
$HTML .= section_builder($PageTitle);

if ($AngebotLoeschenParser['success'] === NULL){
    $HTML .= infos_section($IDangebot);
    $HTML .= prompt_karte_generieren('action', 'L&ouml;schen', 'termine.php', 'Abbrechen', 'M&ouml;chtest du das Terminangebot l&ouml;schen? Bereits entstandene &Uuml;bergaben werden hiervon nicht betroffen!', FALSE, '');
} else if ($AngebotLoeschenParser['success'] === FALSE){
    $HTML .= zurueck_karte_generieren(FALSE, $AngebotLoeschenParser['meldung'], 'termine.php');
} else if ($AngebotLoeschenParser['success'] === TRUE){
    $HTML .= zurueck_karte_generieren(TRUE, 'Terminangebot wurde erfolgreich gel&ouml;scht!', 'termine.php');
}

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);




function infos_section($IDangebot){

    $Angebot = lade_terminangebot($IDangebot);
    zeitformat();

    $HTML = "<div class='section'>";
    $HTML .= "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $HTML .= "<h5>Informationen zum &Uuml;bergabeangebot</h5>";
    $HTML .= "<ul class='collection'>";
    $HTML .= "<li class='collection-item'>Datum: ".strftime("%A, %d. %B %G", strtotime($Angebot['von']))."</li>";
    $HTML .= "<li class='collection-item'>Zeitraum: ".date("G:i", strtotime($Angebot['von']))." bis ".date("G:i", strtotime($Angebot['bis']))." Uhr</li>";
    $HTML .= "<li class='collection-item'>Treffpunkt: ".$Angebot['ort']."</li>";
    $HTML .= "<li class='collection-item'>Entstandene &Uuml;bergaben: ".lade_entstandene_uebergaben($IDangebot)."</li>";
    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function parser_angebot_loeschen($IDangebot){

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

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){
        $link = connect_db();
        if(isset($_POST['action'])){
            $Anfrage = "UPDATE terminangebote SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."' WHERE id = '$IDangebot'";
            if(mysqli_query($link, $Anfrage)){
                $Antwort['success'] = TRUE;
                $Antwort['meldung'] = "Terminangebot erfolgreich storniert!";
            } else {
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Datenbankfehler!";
            }
        } else if (!isset($_POST['action'])) {
            $Antwort['success'] = NULL;
        }
    }

    return $Antwort;
}
function prompt_karte_generieren($NameActionButton, $TextJA, $URIzurueck, $TextZurueck, $TextPrompt, $KommentarFeld, $NameKommentarfeld){

    $HTML = "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $HTML .= "<p>".$TextPrompt."</p>";

    $HTML .= "<form method='post'>";

    if ($KommentarFeld == TRUE){

        $HTML .= "<div class='input-field'>";
        $HTML .= "<textarea name='".$NameKommentarfeld."' id='".$NameKommentarfeld."' data-length='500'></textarea><label for='".$NameKommentarfeld."'>Platz f&uuml;r Kommentar</label>";
        $HTML .= "</div>";

        $HTML .= divider_builder();
    }

    $HTML .= "<div class='section'>";

    $HTML .= "<div class='input-field'>";
    $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='".$NameActionButton."'>".$TextJA."</button>";
    $HTML .= "</div>";
    $HTML .= "<div class='input-field'>";
    $HTML .= "<a class='btn waves-effect waves-light' href='".$URIzurueck."'>".$TextZurueck."</a>";
    $HTML .= "</div>";

    $HTML .= "</div>";


    $HTML .= "</form>";

    $HTML .= "</div>";

    return $HTML;

}

?>