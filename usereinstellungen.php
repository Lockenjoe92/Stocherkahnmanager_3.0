<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Einstellungen - " . lade_db_einstellung('site_name');
$Settings = ['vorname', 'nachname', 'strasse', 'hausnummer', 'plz', 'stadt', 'nutzergruppe'];

#Parse input
user_settings_parser($Settings);

#Generate content
# Page Title
$PageTitle = '<h1>Persönliche Einstellungen</h1>';
$HTML .= section_builder($PageTitle);

# Settings Form
$UserID = lade_user_id();
$UserMeta = lade_user_meta($UserID);
$SettingTableItems = table_form_string_item('Vorname', 'vorname', $UserMeta['vorname'], false);
$SettingTableItems .= table_form_string_item('Nachname', 'nachname', $UserMeta['nachname'], false);
$SettingTableItems .= table_form_string_item('Straße', 'strasse', $UserMeta['strasse'], false);
$SettingTableItems .= table_form_string_item('Hausnummer', 'hausnummer', $UserMeta['hausnummer'], false);
$SettingTableItems .= table_form_string_item('Stadt', 'stadt', $UserMeta['stadt'], false);
$SettingTableItems .= table_form_string_item('Postleitzahl', 'plz', $UserMeta['plz'], false);
$SettingTableItems .= table_form_string_item('Telefon (optional)', 'telefon', $UserMeta['telefon'], false);
$SettingTable = table_builder($SettingTableItems);
$SettingTable = section_builder($SettingTable);

$NutzergruppeMeta = lade_nutzergruppe_infos($UserMeta['ist_nutzergruppe'], 'name');
$NutzergruppeHTML = "<h3 class='hide-on-med-and-down'>Nutzergruppe</h3>";
$NutzergruppeHTML .= "<h3 class='center-align hide-on-large-only'>Nutzergruppe</h3>";
$NutzergruppeTable = table_row_builder(table_header_builder('Aktuelle Nutzergruppe').table_data_builder($NutzergruppeMeta['name']));
$NutzergruppeTable .= table_row_builder(table_header_builder('Beschreibung').table_data_builder($NutzergruppeMeta['erklaertext']));
$VerifizierungErklaerung = $NutzergruppeMeta['req_verify'];
if($VerifizierungErklaerung!='false'){

    $NutzergruppeVerification = load_last_nutzergruppe_verification_user($NutzergruppeMeta['id'], $UserID);

    if($VerifizierungErklaerung == 'yearly'){
        $VerifizierungErklaerung = "Deine Zugehörigkeit zur Nutzergruppe muss jährlich verifiziert werden!";
        if($NutzergruppeVerification['erfolg'] == 'false'){
            $VerifizierungErklaerung .= "<br><b>Verifizierung abgelehnt!</b><br>Bitte wechsle deine Nutzergruppe oder kontaktiere uns, wenn du meinst, dass ein Fehler vorliegt.";
        } elseif ($NutzergruppeVerification['erfolg'] == 'true'){
            if($NutzergruppeVerification['timestamp'] < "".date('Y')."-01-01 00:00:01"){
                $VerifizierungErklaerung .= "<br><b>Verifizierung abgelaufen!</b><br>Wird bei der nächsten Schlüsselübergabe gemacht:)";
            } elseif ($NutzergruppeVerification['timestamp'] >= "".date('Y')."-01-01 00:00:01"){
                $VerifizierungErklaerung .= "<br><b>Verifizierung dieses Jahr erfolgt!:)</b>";
            }
        } elseif (empty($NutzergruppeVerification)){
            $VerifizierungErklaerung .= "<br><b>Bislang keine Verifizierung erfolgt!</b>";
        }
    } elseif ($VerifizierungErklaerung == 'once'){
        $VerifizierungErklaerung = "Deine Zugehörigkeit zur Nutzergruppe muss einmalig verifiziert werden!";
        if($NutzergruppeVerification['erfolg'] == 'false'){
            $VerifizierungErklaerung .= "<br><b>Verifizierung abgelehnt!</b><br>Bitte wechsle deine Nutzergruppe oder kontaktiere uns, wenn du meinst, dass ein Fehler vorliegt.";
        } elseif ($NutzergruppeVerification['erfolg'] == 'true'){
            $VerifizierungErklaerung .= "<br><b>Verifizierung erfolgt!:)</b>";
        } elseif (empty($NutzergruppeVerification)){
            $VerifizierungErklaerung .= "<br><b>Bislang keine Verifizierung erfolgt!</b>";
        }
    }

    $NutzergruppeTable .= table_row_builder(table_header_builder('Verifizierung').table_data_builder($VerifizierungErklaerung));
    $NutzergruppeTable .= table_form_dropdown_nutzergruppen_waehlen('Nutzergruppe wechseln', 'nutzergruppe', $_POST['nutzergruppe'], 'user');
}
$NutzergruppeTable .= table_row_builder(table_header_builder('').table_data_builder('Bitte beachte: ein Ändern der Nutzergruppe bedeutet, dass diese in jedem Fall bei der nächsten Schlüsselübergabe überprüft werden muss.'));
$NutzergruppeHTML .= table_builder($NutzergruppeTable);
$SettingTable .= section_builder($NutzergruppeHTML);

$SettingTable .= section_builder(form_button_builder('user_settings_action', 'Speichern', 'action', 'send'));

$SettingForm = form_builder($SettingTable, './usereinstellungen.php', 'post');
$HTML .= section_builder($SettingForm);

#Put it all in a container
$HTML = container_builder($HTML, 'user_settings_page');

# Output site
echo site_header($Header);
echo site_body($HTML);

?>