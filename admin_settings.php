<?php

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Header = "Admin Einstellungen - " . lade_db_einstellung('site_name');
$DBSettings = ['site_name', 'site_footer_name', 'earliest_begin', 'latest_begin', 'site_menue_color',
    'site_footer_color', 'site_buttons_color', 'site_error_buttons_color', 'display_big_footer',
    'big_footer_left_column_html', 'big_footer_right_column_html', 'max_size_file_upload'];
admin_db_settings_parser($DBSettings);

$XMLsettings = ['site_url', 'absender_mail', 'absender_name', 'reply_mail', 'sms-active', 'user-sms', 'key-sms',
    'absender-sms', 'max-kosten-einer-reservierung', 'max-dauer-einer-reservierung', 'max-stunden-vor-abfahrt-buchbar',
    'max-tage-vor-abfahrt-uebergabe', 'max-minuten-vor-abfahrt-uebergabe', 'zeit-ab-wann-zukuenftige-uebergaben-in-schluesselverfuegbarkeitskalkulation-einfliessen-tage',
    'tage-spontanuebergabe-reservierungen-zukunft-dropdown', 'kritischer-abstand-storno-vor-beginn', 'uebernahmefunktion-global-aktiv', 'erinnerung-uebergabe-ausmachen-1',
    'erinnerung-uebergabe-ausmachen-2', 'erinnerung-schluessel-zurueckgeben-intervall-beginn', 'erinnerung-schluessel-zurueckgeben-intervall-groesse', 'stunden-bis-uebergabe-eingetragen-sein-soll',
    'zeit-tage-nach-res-ende-zahlen', 'card_panel_hintergrund'];
admin_xml_settings_parser($XMLsettings);

$CDATAxmlSETTINGS = ['titelinfo-reservierung-hinzufuegen', 'inhalt-dokumente-und-nuetzliches', 'html-faq-user-hauptansicht', 'text-info-uebergabe-dabei-haben', 'text-info-uebergabe-ablauf',
    'text-info-uebergabe-einweisung', 'erklaerung_schluesseluebernahme', 'erklaerung-forderung-zahlen-user'];
admin_xml_cdata_settings_parser($CDATAxmlSETTINGS);


#Generate content
# Page Title
$PageTitle = '<h1>Admineinstellungen</h1>';
$PageTitle = '<h1 class="hide-on-med-and-down">Admineinstellungen</h1>';
$PageTitle .= '<h1 class="hide-on-large-only">Admin Settings</h1>';
$HTML .= section_builder($PageTitle);

#Settings Form
$Items="";

#Website Skeleton
$SettingTableItems = table_form_string_item('Website Name', 'site_name', lade_db_einstellung('site_name'), false);
$SettingTableItems .= table_form_string_item('Website Footer Name', 'site_footer_name', lade_db_einstellung('site_footer_name'), false);
$SettingTableItems .= table_form_swich_item('Website Big Footer', 'display_big_footer', 'deaktiviert', 'aktiviert', lade_db_einstellung('display_big_footer'), false);
$SettingTableItems .= table_form_html_area_item('Big Footer Left Column', 'big_footer_left_column_html', lade_db_einstellung('big_footer_left_column_html'), slider_setting_interpreter(lade_db_einstellung('display_big_footer')));
$SettingTableItems .= table_form_html_area_item('Big Footer Right Column', 'big_footer_right_column_html', lade_db_einstellung('big_footer_right_column_html'), slider_setting_interpreter(lade_db_einstellung('display_big_footer')));
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Website Skeleton', $SettingTable, 'colorize');

# Farbenkram
$SettingTableItems = table_form_string_item('Website Men&uuml;farbe', 'site_menue_color', lade_db_einstellung('site_menue_color'), false);
$SettingTableItems .= table_form_string_item('Website Footerfarbe', 'site_footer_color', lade_db_einstellung('site_footer_color'), false);
$SettingTableItems .= table_form_string_item('Farbe Link Buttons', 'site_buttons_color', lade_db_einstellung('site_buttons_color'), false);
$SettingTableItems .= table_form_string_item('Farbe Error Buttons', 'site_error_buttons_color', lade_db_einstellung('site_error_buttons_color'), false);
$SettingTableItems .= table_form_string_item('Farbe Button Kalender: buchbar', 'farbe-button-kalender-buchbar', lade_db_einstellung('farbe-button-kalender-buchbar'), false);
$SettingTableItems .= table_form_string_item('Farbe Button Kalender: nicht buchbar', 'farbe-button-kalender-nicht-buchbar', lade_db_einstellung('farbe-button-kalender-nicht-buchbar'), false);
$SettingTableItems .= table_form_string_item('Farbe Button Kalender: reserviert', 'farbe-button-kalender-reserviert', lade_db_einstellung('farbe-button-kalender-reserviert'), false);
$SettingTableItems .= table_form_string_item('Hintergrundfarbe für Prompt-Karten', 'card_panel_hintergrund', lade_xml_einstellung('card_panel_hintergrund'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Farbschema', $SettingTable, 'color_lens');

#Mailkram
$SettingTableItems = table_form_string_item('Website URL', 'site_url', lade_xml_einstellung('site_url'), false);
$SettingTableItems .= table_form_string_item('Absender: Mail Adresse', 'absender_mail', lade_xml_einstellung('absender_mail'), false);
$SettingTableItems .= table_form_string_item('Absender: Name', 'absender_name', lade_xml_einstellung('absender_name'), false);
$SettingTableItems .= table_form_string_item('Reply to: Mail', 'reply_mail', lade_xml_einstellung('reply_mail'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Mailfunktion', $SettingTable, 'mail');

#SMS kram
$SettingTableItems = table_form_swich_item('SMS-Funktion aktivieren', 'sms-active', 'deaktiviert', 'aktiviert', lade_xml_einstellung('sms-active'), false);
$SettingTableItems .= table_form_string_item('Benutzername sms77', 'user-sms', lade_xml_einstellung('user-sms'), false);
$SettingTableItems .= table_form_string_item('Shared Secret sms77', 'key-sms', lade_xml_einstellung('key-sms'), false);
$SettingTableItems .= table_form_string_item('Angezeigter Absender in SMS', 'absender-sms', lade_xml_einstellung('absender-sms'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('SMS-Funktion', $SettingTable, 'sms');

#Reservierungsgedöns
$SettingTableItems = table_form_select_item('Fr&uuml;hester Verleihbeginn', 'earliest_begin', 0, 23,intval(lade_db_einstellung('earliest_begin')), 'h', '', '');
$SettingTableItems .= table_form_select_item('Sp&auml;tester Verleihbeginn', 'latest_begin', 0, 23,intval(lade_db_einstellung('latest_begin')), 'h', '', '');
$SettingTableItems .= table_form_select_item('Max. Kosten einer Reservierung', 'max-kosten-einer-reservierung', 0, 500,intval(lade_xml_einstellung('max-kosten-einer-reservierung')), '&euro;', '', '');
$SettingTableItems .= table_form_select_item('Max. Dauer einer Reservierung', 'max-dauer-einer-reservierung', 0, 23,intval(lade_xml_einstellung('max-dauer-einer-reservierung')), 'h', '', '');
$SettingTableItems .= table_form_select_item('Min. Anz. Stunden die zwischen Buchung und<br>  Reservierungsbeginn liegen dürfen <br>(gilt nur für User ohne das Recht *darf last_minute*)', 'max-stunden-vor-abfahrt-buchbar', 0, 23,intval(lade_xml_einstellung('max-stunden-vor-abfahrt-buchbar')), 'h', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Reservierungen', $SettingTable, 'flight');

# Übergabekram
$SettingTableItems = table_form_select_item('Dauer einer Übergabe ca.', 'dauer-uebergabe-minuten', 0, 60,intval(lade_xml_einstellung('dauer-uebergabe-minuten')), 'min', '', '');
$SettingTableItems .= table_form_select_item('Tage die Übergabe frühestens vor Reservierungsbeginn<br>  gebucht werden darf', 'max-tage-vor-abfahrt-uebergabe', 0, 30,intval(lade_xml_einstellung('max-tage-vor-abfahrt-uebergabe')), '', '', '');
$SettingTableItems .= table_form_select_item('Minuten die mindestens zwischen Übergabetermin und <br> Reservierungsbeginn liegen müssen', 'max-minuten-vor-abfahrt-uebergabe', 0, 60,intval(lade_xml_einstellung('max-minuten-vor-abfahrt-uebergabe')), 'min', '', '');
$SettingTableItems .= table_form_select_item('Minuten die mindestens zwischen Übergabetermin und <br> Reservierungsbeginn liegen müssen', 'max-minuten-vor-abfahrt-uebergabe', 0, 60,intval(lade_xml_einstellung('max-minuten-vor-abfahrt-uebergabe')), 'min', '', '');
$SettingTableItems .= table_form_select_item('Anzahl Tage ab wann zukünftige Übergaben in die Berechnung <br> der Schlüsselverfügbarkeit eines/r Wart!n einfließt', 'zeit-ab-wann-zukuenftige-uebergaben-in-schluesselverfuegbarkeitskalkulation-einfliessen-tage', 0, 30,intval(lade_xml_einstellung('zeit-ab-wann-zukuenftige-uebergaben-in-schluesselverfuegbarkeitskalkulation-einfliessen-tage')), '', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Übergaben', $SettingTable, 'arrow_forward');

# Spontanübergabekram
$SettingTableItems = table_form_select_item('Dropdown Reservierungen Sponatnübergabe: <br> Anz. Tage in die Zukunft', 'tage-spontanuebergabe-reservierungen-zukunft-dropdown', 0, 30,intval(lade_xml_einstellung('tage-spontanuebergabe-reservierungen-zukunft-dropdown')), '', '', '');
$SettingTableItems .= table_form_select_item('Dropdown Reservierungen Sponatnübergabe: <br> Anz. Tage in die Vergangenheit', 'tage-spontanuebergabe-reservierungen-vergangenheit-dropdown', 0, 7,intval(lade_xml_einstellung('tage-spontanuebergabe-reservierungen-vergangenheit-dropdown')), '', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Spontanübergaben', $SettingTable, 'flash_on');

# Übernahmekram
$SettingTableItems = table_form_swich_item('Übernahme-Funktion aktivieren', 'uebernahmefunktion-global-aktiv', 'deaktiviert', 'aktiviert', lade_xml_einstellung('uebernahmefunktion-global-aktiv'), false);
$SettingTableItems .= table_form_select_item('Kritischer Abstand bei Stornierung einer Übernahme', 'kritischer-abstand-storno-vor-beginn', 0, 120,intval(lade_xml_einstellung('kritischer-abstand-storno-vor-beginn')), 'min', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Übernahmen', $SettingTable, 'beach_access');

# Terminekram
$SettingTableItems = table_form_string_item('Mögliche Termintypen <br> (getrennt durch Kommata)', 'termin_typen', lade_xml_einstellung('termin_typen'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Termine', $SettingTable, 'date_range');

# Schlüsselkram
$SettingTableItems = table_form_string_item('Mögliche Schlüsselorte <br> (getrennt durch Kommata)', 'moegliche_schluesselorte', lade_xml_einstellung('moegliche_schluesselorte'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Schlüssel', $SettingTable, 'vpn_key');

# Wartinformationen
$SettingTableItems = table_form_select_item('Eigene Übergaben: <br> Anz. Tage in die Vergangenheit', 'wochen-vergangenheit-durchgefuehrte-uebergaben', 0, 7,intval(lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-uebergaben')), '', '', '');
$SettingTableItems .= table_form_select_item('Eigene Finanztransaktionen: <br> Anz. Tage in die Vergangenheit', 'wochen-vergangenheit-durchgefuehrte-transaktionen', 0, 7,intval(lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen')), '', '', '');
$SettingTableItems .= table_form_select_item('Erinnerungsmail an Wart eine Schlüsselübergabe nachzutragen<br>nach x Stunden', 'stunden-bis-uebergabe-eingetragen-sein-soll', 0, 23,intval(lade_xml_einstellung('stunden-bis-uebergabe-eingetragen-sein-soll')), 'h', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Wartinformationen', $SettingTable, 'info');

# Erinnerungsmails
$SettingTableItems = table_form_select_item('Erste Erinnerung eine Schlüsselübergabe auszumachen (Tage)', 'erinnerung-uebergabe-ausmachen-1', 0, 7, intval(lade_xml_einstellung('erinnerung-uebergabe-ausmachen-1')), '', '', '');
$SettingTableItems .= table_form_select_item('Zweite Erinnerung eine Schlüsselübergabe auszumachen (Tage)', 'erinnerung-uebergabe-ausmachen-2', 0, 7,intval(lade_xml_einstellung('erinnerung-uebergabe-ausmachen-2')), '', '', '');
$SettingTableItems .= table_form_select_item('Früheste Erinnerung an Schlüsselrückgabe (Tage)', 'erinnerung-schluessel-zurueckgeben-intervall-beginn', 0, 7,intval(lade_xml_einstellung('erinnerung-schluessel-zurueckgeben-intervall-beginn')), '', '', '');
$SettingTableItems .= table_form_select_item('Intervall Erinnerung an Schlüsselrückgabe (Tage)', 'erinnerung-schluessel-zurueckgeben-intervall-groesse', 0, 7,intval(lade_xml_einstellung('erinnerung-schluessel-zurueckgeben-intervall-groesse')), '', '', '');
$SettingTableItems .= table_form_select_item('Früheste Erinnerung Fehlende Geldbeträge nachzahlen (Tage)', 'zeit-tage-nach-res-ende-zahlen', 0, 30,intval(lade_xml_einstellung('zeit-tage-nach-res-ende-zahlen')), '', '', '');
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Erinnerungsmails', $SettingTable, 'mail');

# Media
$SettingTableItems = table_form_string_item('Maximale Dateigröße Uploads', 'max_size_file_upload', lade_db_einstellung('max_size_file_upload'), false);
$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Medien', $SettingTable, 'perm_media');

# TODOs Infotexte für User als cdata
$SettingTableItems = table_form_html_area_item('Titelinfo beim Anlegen einer neuen Reservierung', 'titelinfo-reservierung-hinzufuegen', lade_xml_einstellung('titelinfo-reservierung-hinzufuegen'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Dokumente und Nützliches*', 'inhalt-dokumente-und-nuetzliches', lade_xml_einstellung('inhalt-dokumente-und-nuetzliches'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *FAQ für User*', 'html-faq-user-hauptansicht', lade_xml_einstellung('html-faq-user-hauptansicht'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Was soll ich <br> bei der Übergabe dabei haben?*', 'text-info-uebergabe-dabei-haben', lade_db_einstellung('text-info-uebergabe-dabei-haben'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Wie läuft eine Übergabe ab?*', 'text-info-uebergabe-ablauf', lade_xml_einstellung('text-info-uebergabe-ablauf'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Einweisung bei einer Übergabe?*', 'text-info-uebergabe-einweisung', lade_xml_einstellung('text-info-uebergabe-einweisung'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Wie funktioniert eine <br> Schlüsselübernahme?*', 'erklaerung_schluesseluebernahme', lade_xml_einstellung('erklaerung_schluesseluebernahme'));
$SettingTableItems .= table_form_html_area_item('Inhalt Element *Erklärung wie man <br> seine ausstehenden Zahlungen begleicht*', 'erklaerung-forderung-zahlen-user', lade_xml_einstellung('erklaerung-forderung-zahlen-user'));

$SettingTable = table_builder($SettingTableItems);
$Items.=collapsible_item_builder('Infotexte für User', $SettingTable, 'info_outline');

#Complete Settings Form
$SettingTable = section_builder(collapsible_builder($Items));
$Buttons = row_builder(form_button_builder('admin_settings_action', 'Speichern', 'action', 'send', ''));
$Buttons .= row_builder(button_link_creator('Zurück', './administration.php', 'arrow_back', ''));
$SettingTable .= section_builder($Buttons);

$SettingForm = form_builder($SettingTable, './admin_settings.php', 'post');
$HTML .= section_builder($SettingForm);

#Put it all in a container
$HTML = container_builder($HTML, 'admin_settings_page');

# Output site
echo site_header($Header);
echo site_body($HTML);

?>