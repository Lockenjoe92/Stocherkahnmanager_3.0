<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Mode = $_GET['mode'];
if($Mode == 'dse'){
    $Erklaerungheader = 'Datenschutzerklärung';
} elseif ($Mode == 'mv'){
    $Erklaerungheader = 'Ausleihvertrag';
} else {
    header('Location: ./index.php');
    die();
}

$Parser = renew_dse_mv_parser($Mode);
$Header = $Erklaerungheader." erneuert- " . lade_db_einstellung('site_name');

#Generate content
# Page Title
if($Mode == 'dse'){
    $PageTitle = '<h1 class="hide-on-med-and-down">Die '.$Erklaerungheader.' hat sich erneuert</h1>';
    $PageTitle .= '<h1 class="hide-on-large-only">'.$Erklaerungheader.' hat sich erneuert</h1>';
} elseif ($Mode == 'mv'){
    $PageTitle = '<h1 class="hide-on-med-and-down">Der '.$Erklaerungheader.' hat sich erneuert</h1>';
    $PageTitle .= '<h1 class="hide-on-large-only">'.$Erklaerungheader.' hat sich erneuert</h1>';
}
$HTML .= section_builder($PageTitle);

if($Mode == 'dse'){
    $Infos = lade_ds(aktuelle_ds_id_laden());
} elseif ($Mode == 'mv'){
    $Infos = lade_mietvertrag(aktuellen_mietvertrag_id_laden());
}

if(($Parser == FALSE) OR ($Parser == NULL)){

    $HTML .= renew_dse_mv_form($Mode, $Erklaerungheader, $Infos);

} elseif ($Parser == TRUE){
    $HTML .= section_builder(zurueck_karte_generieren(true, 'Dein Eintrag wurde erfolgreich festgehalten!', './wartwesen.php'));
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function renew_dse_mv_parser($Mode){

    if($Mode == 'dse'){
        $Continiue = user_needs_dse();
    } elseif($Mode == 'mv'){
        $Continiue = user_needs_mv();
    } else {
        $Continiue = false;
    }

    if($Continiue == false){
        header("Location: ./wartwesen.php");
        die();
    } else {
        $Antwort = null;

        if(isset($_POST['action_dse'])){
            if($_POST['ds']){
                $Antwort = ds_unterschreiben(lade_user_id(), aktuelle_ds_id_laden());
            }
        }

        if(isset($_POST['action_mv'])){
            if($_POST['vertrag']) {
                $Antwort = mietvertrag_unterschreiben(lade_user_id(), aktuellen_mietvertrag_id_laden());
            }
        }

        return $Antwort;
    }
}

function renew_dse_mv_form($Mode, $Erklaerungheader, $Infos){

    if($Mode == 'dse'){
        $Icon = 'security';
        $TableHTML = table_form_swich_item('Ich stimme den Nutzungsbedingungen, sowie der Speicherung und Verarbeitung gem&auml;&szlig; der Datenschutzerkl&auml;rung zu', 'ds', 'Nein', 'Ja', $Checked, false);
    } else {
        $Icon = 'assignment';
        $TableHTML = table_form_swich_item('Ich stimme dem Nutzungsvertrag, sowie der Haftungs- und Sicherungsvereinbarung zu', 'vertrag', 'Nein', 'Ja', $Checkedvertrag, false);
    }

    $Inhalt = "<h5>".$Infos['erklaerung']."</h5>";
    $Inhalt .= section_builder($Infos['inhalt']);

    $CollapsibleItems = collapsible_item_builder($Erklaerungheader, $Inhalt, $Icon, '');
    $HTML = collapsible_builder($CollapsibleItems);
    $TableHTML .= table_row_builder(table_header_builder(form_button_builder('action_'.$Mode.'', 'Absenden', 'action', 'send', '')).table_data_builder(''));
    $HTML .= table_builder($TableHTML);
    $HTML = form_builder($HTML, '#', 'post');

    return $HTML;
}