<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "&Uuml;bergabewesen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Deine &Uuml;bergaben und Angebote</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">&Uuml;bergaben</h1>';
$HTML .= section_builder($PageTitle);

# Content
$HTML .= spalte_uebergabeangebote();
#$HTML .= spalte_uebergaben();
#$HTML .= spalte_termine();

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);




function spalte_uebergabeangebote(){

    //Grundsätzliches
    $link = connect_db();
    $Timestamp = timestamp();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Deine Terminangebote</h5>";

    $HTML .= "<div class='section'>";
    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $HTML .= terminangebot_hinzufuegen_listenelement_generieren();

    //Lade aktive Terminangebote
    $AnfrageLadeAktiveUebergabeangebote = "SELECT id FROM terminangebote WHERE bis > '$Timestamp' AND wart = '".lade_user_id()."' AND storno_user = '0' ORDER BY von ASC";
    $AbfrageLadeAktiveUebergabeangebote = mysqli_query($link, $AnfrageLadeAktiveUebergabeangebote);
    $AnzahlLadeAktiveUebergabeangebote = mysqli_num_rows($AbfrageLadeAktiveUebergabeangebote);

    if ($AnzahlLadeAktiveUebergabeangebote == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>error</i>Keine aktiven &Uuml;bergabeangebote!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeAktiveUebergabeangebote > 0){
        for ($a = 1; $a <= $AnzahlLadeAktiveUebergabeangebote; $a ++) {
            $Angebot = mysqli_fetch_assoc($AbfrageLadeAktiveUebergabeangebote);
            #$HTML .= terminangebot_listenelement_generieren($Angebot['id']);
        }
    }

    $HTML .= "</ul>";
    $HTML .= "</div>";

    $HTML .= "</div>";

    return $HTML;
}

function terminangebot_hinzufuegen_listenelement_generieren(){


    //Checkbox Schalter

    if(isset($_POST['terminierung_terminangebot_anlegen'])){
        $CheckboxTermin = "checked";
    } else {
        $CheckboxTermin = "unchecked";
    }

    if(isset($_POST['terminangebot_taeglich_wiederholen'])){
        $CheckboxTaeglich = "checked";
    } else {
        $CheckboxTaeglich = "unchecked";
    }

    if(isset($_POST['terminangebot_woechentlich_wiederholen'])){
        $CheckboxWoechentlich = "checked";
    } else {
        $CheckboxWoechentlich = "unchecked";
    }

    #terminangebot_hinzufuegen_listenelement_parser();

    //Bigscreen
    //DATUM UND TERMINIERUNG
    $BigscreenContent = "<h3 class='center-align'>Zeiten und Terminierung</h3>";
    $BigscreenContent .= table_form_datepicker_reservation_item('Datum', 'datum_terminangebot_anlegen', $_POST['datum_terminangebot_anlegen'], false, true, '');
    $BigscreenContent .= table_form_timepicker_item('Beginn', 'beginn_terminangebot_anlegen', $_POST['beginn_terminangebot_anlegen'], false, true, '');
    $BigscreenContent .= table_form_timepicker_item('Ende', 'ende_terminangebot_anlegen', $_POST['ende_terminangebot_anlegen'], false, true, '');
    $BigscreenContent .= table_form_swich_item('Terminierung aktivieren', 'terminierung_terminangebot_anlegen', 'Nein', 'Ja', $CheckboxTermin, false);
    $BigscreenContent .= table_form_select_item('Terminierung für', 'stunden_terminierung_terminangebot_anlegen', 1, 24, $_POST['stunden_terminierung_terminangebot_anlegen'], 'h', 'Terminierung', '', false);
    $BigscreenContent = table_builder($BigscreenContent);
    $BigscreenContent .= divider_builder();

    //ORTSANGABE
    $BigscreenContent .= "<h3 class='center-align'>Ortsangabe</h3>";
    $OrtsangabenContent = table_row_builder(table_header_builder('Ortsvorlage verwenden').table_data_builder(dropdown_vorlagen_ortsangaben('ortsangabe_terminangebot_anlegen', lade_user_id(), $_POST['ortsangabe_terminangebot_anlegen'])));
    $OrtsangabenContent .= table_form_string_item('Ortsangabe', 'ortsangabe_schriftlich_terminangebot_anlegen', $_POST['ortsangabe_schriftlich_terminangebot_anlegen'], false);
    $BigscreenContent .= table_builder($OrtsangabenContent);
    $BigscreenContent .= divider_builder();

    //KOMMENTAR
    $BigscreenContent .= "<h3 class='center-align'>Kommentar</h3>";
    $KommentarContent = table_form_string_item('Kommentar (optional)', 'kommentar_terminangebot_anlegen', $_POST['kommentar_terminangebot_anlegen'], false);
    $BigscreenContent .= table_builder($KommentarContent);
    $BigscreenContent .= divider_builder();

    //REPEAT
    $BigscreenContent .= "<h3 class='center-align'>Angebot wiederholen</h3>";
    $RepeatContent = table_form_swich_item('Täglich wiederholen', 'terminangebot_taeglich_wiederholen', 'Nein', 'Ja', $CheckboxTaeglich, false);
    $RepeatContent .= table_form_select_item('Anzahl Tage', 'terminangebot_taeglich_wiederholen_tage', 1, 14, $_POST['terminangebot_taeglich_wiederholen_tage'], 'Tage', 'Terminierung', '', false);
    $RepeatContent .= table_form_swich_item('Wöchentlich wiederholen', 'terminangebot_woechentlich_wiederholen', 'Nein', 'Ja', $CheckboxWoechentlich, false);
    $RepeatContent .= table_form_select_item('Anzahl Wochen', 'terminangebot_woechentlich_wiederholen_wochen', 1, 12, $_POST['terminangebot_woechentlich_wiederholen_wochen'], 'Wochen', 'Terminierung', '', false);
    $BigscreenContent .= table_builder($RepeatContent);
    $BigscreenContent .= divider_builder();

    //KNÖPFE
    $KnoepfeContent = table_row_builder(table_header_builder(form_button_builder('action_terminangebot_anlegen', 'Anlegen', 'action', 'send', '')." ".form_button_builder('reset_terminangebot_anlegen', 'Reset', 'reset', 'clear_all', '')).table_data_builder(''));
    $BigscreenContent .= table_builder($KnoepfeContent);

    $CollapsibleContent = form_builder($BigscreenContent, '#', 'post', '', '');
    $Collapsible = collapsible_item_builder('Terminangebot hinzuf&uuml;gen', $CollapsibleContent, 'note_add');

    return $Collapsible;

}


?>