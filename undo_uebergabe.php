<?php

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
zeitformat();
$Header = "Storno Übergabe - " . lade_db_einstellung('site_name');
$Uebergabe = $_GET['uebergabe'];
$Parser = null;

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align">Schlüsselübergabe stornieren</h1>';
$HTML .= section_builder($PageTitle);

if($Parser == null){
    $Uebergabe = lade_uebergabe($Uebergabe);
    $Res = lade_reservierung($Uebergabe['res']);
    $UsrRes = lade_user_meta($Res['user']);
    $TextPrompt = 'Willst du deine Übergabe an '.$UsrRes['vorname'].' '.$UsrRes['nachname'].' vom '.strftime("%A, den %d. %B %G", strtotime($Uebergabe['durchfuehrung'])).' sicher rückgängig machen?';
    $HTML .= section_builder(prompt_karte_generieren('delete_uebergabe', 'Stornieren', 'termine.php', 'Abbrechen', $TextPrompt, true, 'storno_kommentar'));
} elseif ($Parser == true){
    $HTML .= section_builder(zurueck_karte_generieren(true, 'Übergabe erfolgreich rückgängig gemacht!', 'termine.php'));
} elseif ($Parser == false){
    $HTML .= section_builder(zurueck_karte_generieren(false, 'Fehler beim Stornieren der Übergabe!', 'termine.php'));
}

#Put it all in a container
$HTML = container_builder($HTML, 'admin_settings_page');

# Output site
echo site_header($Header);
echo site_body($HTML);