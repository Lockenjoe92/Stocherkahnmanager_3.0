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
$Settings = ['vorname', 'nachname', 'strasse', 'hausnummer', 'plz', 'stadt'];

#Parse input
user_settings_parser($Settings);

#Generate content
# Page Title
$PageTitle = '<h1>Persönliche Einstellungen</h1>';
$HTML .= section_builder($PageTitle);

# Settings Form
$UserMeta = lade_user_meta(lade_user_id());
$SettingTableItems = table_form_string_item('Vorname', 'vorname', $UserMeta['vorname'], false);
$SettingTableItems .= table_form_string_item('Nachname', 'nachname', $UserMeta['nachname'], false);
$SettingTableItems .= table_form_string_item('Straße', 'strasse', $UserMeta['strasse'], false);
$SettingTableItems .= table_form_string_item('Hausnummer', 'hausnummer', $UserMeta['hausnummer'], false);
$SettingTableItems .= table_form_string_item('Stadt', 'stadt', $UserMeta['stadt'], false);
$SettingTableItems .= table_form_string_item('Postleitzahl', 'plz', $UserMeta['plz'], false);

#Complete Settings Form
$SettingTable = table_builder($SettingTableItems);
$SettingTable = section_builder($SettingTable);
$SettingTable .= section_builder(form_button_builder('user_settings_action', 'Speichern', 'action', 'send'));

$SettingForm = form_builder($SettingTable, './usereinstellungen.php', 'post');
$HTML .= section_builder($SettingForm);

#Put it all in a container
$HTML = container_builder($HTML, 'user_settings_page');

# Output site
echo site_header($Header);
echo site_body($HTML);

?>