<?php
/**
 * User: David
 * Date: 06.04.2020
 * Time: 15:04
 */

include_once "./ressourcen.php";

function lade_xml_einstellung($NameEinstellung, $mode='global'){

    if($mode == 'global'){
        $xml = simplexml_load_file("./settings_functions/settings.xml");
    } elseif ($mode == 'db'){
        $xml = simplexml_load_file("./database_functions/local_db_settings.xml");
    } elseif ($mode == 'local-db'){
        $local_db_file = "./database_functions/test_db_settings.xml";
        if (file_exists($local_db_file)) {
            $xml = simplexml_load_file($local_db_file);
        } else {
            $xml = false;
        }
    }

    if (false === $xml) {
        // throw new Exception("Cannot load xml source.\n");
        $StrValue = false;

    } else {

        $Value = $xml->$NameEinstellung;
        $StrValue = (string) $Value;
        $StrValue = ($StrValue);

    }

    return $StrValue;

}

function reset_setting($type) {
    if ($type == 'settings') {
        return(copy('./settings_functions/settings.default.xml', './settings_functions/settings.xml'));
    } elseif ($type == 'mails') {
        return(copy('./mail_generation_functions/mailvorlagen.default.xml','./mail_generation_functions/mailvorlagen.xml'));
    } else {
        return false;
    }
}

function update_xml_einstellung($NameEinstellung, $WertEinstellung, $mode='global'){

    $WertEinstellung = utf8_encode($WertEinstellung);

    if($mode == 'global'){
        $xml = simplexml_load_file("./settings_functions/settings.xml");
        $xml->$NameEinstellung = $WertEinstellung;
        $xml->asXML("./settings_functions/settings.xml");
    } elseif ($mode == 'db'){
        $xml = simplexml_load_file("./database_functions/local_db_settings.xml");
        $xml->$NameEinstellung = $WertEinstellung;
        $xml->asXML("./database_functions/local_db_settings.xml");
    } elseif ($mode == 'cdata'){
        $xml = simplexml_load_file("./settings_functions/settings.xml");
        $Einstellung = $xml->$NameEinstellung;
        $xmlDoc = new DOMDocument();
        $xmlDoc->load("./settings_functions/settings.xml");
        $y=$xmlDoc->getElementsByTagName($NameEinstellung)[0];
        $cdata = $y->firstChild;
        $cdata->replaceData(0,strlen($Einstellung),utf8_decode($WertEinstellung));
        $xmlDoc->save("./settings_functions/settings.xml");
    }
}

function settings_content_generator (){
    $Parser = settings_taskforce_parser();
    $HTML = settings_view($Parser);
    return $HTML;
};

function settings_view($ParserMessage){

    $HTML = section_builder("<h1>Einstellungen bearbeiten</h1>");

    if($ParserMessage != 'null'){
        if($ParserMessage === 'erfolg'){
            $HTML .= section_builder(button_link_creator('Einstellungen erfolgreich bearbeitet','#','','green'));
        } elseif ($ParserMessage != 'erfolg'){
            $HTML .= section_builder(error_button_creator($ParserMessage, '', ''));
        }
    }

    #Settings laden

    # Startseitentext
    $HTML_startseite = form_html_area_item('text-startseite-html', lade_xml_einstellung('text-startseite-html'), '');
    $HTML_collapsible = collapsible_item_builder('Begrüßungstext Startseite', $HTML_startseite, '');

    #Infotext fehlender Vertrag
    $HTML_fehlender_Vertrag = form_html_area_item('infotext-fehlender-vertrag', lade_xml_einstellung('infotext-fehlender-vertrag'), '');
    $HTML_collapsible .= collapsible_item_builder('Infotext für fehlende Verträge', $HTML_fehlender_Vertrag, '');

    # Settings dropout
    $HTML_dropout = form_html_area_item('helper_info_on_dropout', lade_xml_einstellung('helper_info_on_dropout'), '');
    $HTML_collapsible .= collapsible_item_builder('Information zur Abmeldung aus dem Helferpool', $HTML_dropout,'');


    # titeltext-fehlendes-geburtsdatum
    $HTML_fehlendes_geburtsdatum = form_html_area_item('titeltext-fehlendes-geburtsdatum', lade_xml_einstellung('titeltext-fehlendes-geburtsdatum'),'');
    $HTML_collapsible .= collapsible_item_builder('Infotext für fehlendes Geburtsdatum', $HTML_fehlendes_geburtsdatum, '');

    # Umfrage Semesterverfügbarkeit
    $HTML_fehlendes_geburtsdatum = form_html_area_item('titeltext-umfrage-verfuegbarkeit-semester', lade_xml_einstellung('titeltext-umfrage-verfuegbarkeit-semester'),'');
    $HTML_fehlendes_geburtsdatum .= form_html_area_item('infotext-umfrage-verfuegbarkeit-semester', lade_xml_einstellung('infotext-umfrage-verfuegbarkeit-semester'),'');
    $HTML_collapsible .= collapsible_item_builder('Infotext für Umfrage Semesterverfügbarkeit - oben Titeltext, unten Inhalt des Infotextes', $HTML_fehlendes_geburtsdatum, '');


    # Impressum bearbeiten
    $HTML_impressum = form_html_area_item('impressum-content', lade_xml_einstellung('impressum-content'), '');
    $HTML_collapsible .= collapsible_item_builder('Impressum', $HTML_impressum, '');

    #DSE bearbeiten
    $HTML_dse = form_html_area_item('ds-erklaerung-content', lade_xml_einstellung('ds-erklaerung-content'), '');
    $HTML_collapsible .= collapsible_item_builder('Datenschutzerklärung', $HTML_dse, '');

    #Einwilligung Datenweitergabe externe
    $HTML_daten_weitergabe = form_html_area_item('ds-erklaerung-externe-einwilligen', lade_xml_einstellung('ds-erklaerung-externe-einwilligen'), '');
    $HTML_collapsible .= collapsible_item_builder('Einwilligung Datenweitergabe', $HTML_daten_weitergabe, '');

    #Selbstregistrierung aktivieren
    $HTML_register_active = form_select_from_array('register_active',array('erlauben'=>'true','nicht erlauben'=>'false',),'false',lade_xml_einstellung('register_active'));
    $HTML_collapsible .= collapsible_item_builder('Selbstregistrierung aktivieren',$HTML_register_active,'');

    #Emailtexte bearbeiten

        #automatische registrierung
        $Vorlage_automatische_registrierung = lade_mailvorlage('registrierung-erfolgreich-auto-helfer');

        if(isset($_POST['registrierung-erfolgreich-auto-helfer-betreff'])){
            $Value_betreff_automatische_registrierung = $_POST['registrierung-erfolgreich-auto-helfer-betreff'];
        } else {
            $Value_betreff_automatische_registrierung = $Vorlage_automatische_registrierung['betreff'];
        }

        if(isset($_POST['registrierung-erfolgreich-auto-helfer-text'])){
            $Value_text_automatische_registrierung = $_POST['registrierung-erfolgreich-auto-helfer-text'];
        } else {
            $Value_text_automatische_registrierung = $Vorlage_automatische_registrierung['text'];
        }
        
        $HTML_automatische_registrierung = 'Betreff:';
        $HTML_automatische_registrierung .= form_string_item('registrierung-erfolgreich-auto-helfer-betreff', $Value_betreff_automatische_registrierung, '');
        $HTML_automatische_registrierung .= '<br>Inhalt:';
        $HTML_automatische_registrierung .= form_html_area_item('registrierung-erfolgreich-auto-helfer-text', $Value_text_automatische_registrierung, '');
        $HTML_emails_collapsible = collapsible_item_builder("Automatische Registrierung", $HTML_automatische_registrierung, '');

        #formular manuelle Registrierung
        $Vorlage_formular_registrierung = lade_mailvorlage('registrierung-erfolgreich-register-formular');
        if(isset($_POST['registrierung-erfolgreich-register-formular-betreff'])){
            $Value_betreff_formular_registrierung = $_POST['registrierung-erfolgreich-register-formular-betreff'];
        } else {
            $Value_betreff_formular_registrierung = $Vorlage_formular_registrierung['betreff'];
        }

        if(isset($_POST['registrierung-erfolgreich-register-formular-text'])){
            $Value_text_formular_registrierung = $_POST['registrierung-erfolgreich-register-formular-text'];
        } else {
            $Value_text_formular_registrierung = $Vorlage_formular_registrierung['text'];
        }
        
        $HTML_formular_registrierung = 'Betreff:';
        $HTML_formular_registrierung .= form_string_item('registrierung-erfolgreich-register-formular-betreff', $Value_betreff_formular_registrierung, '', '');
        $HTML_formular_registrierung .= '<br>Inhalt:';
        $HTML_formular_registrierung .= form_html_area_item('registrierung-erfolgreich-register-formular-text', $Value_text_formular_registrierung, '');
        $HTML_emails_collapsible .= collapsible_item_builder("Manuelle Registrierung", $HTML_formular_registrierung, '');

        #Einteilung zu einer Einsatzstelle
        $Vorlage_einteilung_einsatzstelle = lade_mailvorlage('einteilung-einsatzstelle');
        if(isset($_POST['einteilung-einsatzstelle-betreff'])){
            $Value_betreff_einteilung_einsatzstelle = $_POST['einteilung-einsatzstelle-betreff'];
        } else {
            $Value_betreff_einteilung_einsatzstelle = $Vorlage_einteilung_einsatzstelle['betreff'];
        }

        if(isset($_POST['einteilung-einsatzstelle-text'])){
            $Value_text_einteilung_einsatzstelle = $_POST['einteilung-einsatzstelle-text'];
        } else {
            $Value_text_einteilung_einsatzstelle = $Vorlage_einteilung_einsatzstelle['text'];
        }
        
        $HTML_einteilung_einsatzstelle = 'Betreff:';
        $HTML_einteilung_einsatzstelle .= form_string_item('einteilung-einsatzstelle-betreff', $Value_betreff_einteilung_einsatzstelle, '', '');
        $HTML_einteilung_einsatzstelle .= '<br>Inhalt:';
        $HTML_einteilung_einsatzstelle .= form_html_area_item('einteilung-einsatzstelle-text', $Value_text_einteilung_einsatzstelle, '');
        $HTML_emails_collapsible .= collapsible_item_builder("neue Einteilung zu einer Stelle", $HTML_einteilung_einsatzstelle, '');


        #Email Anlegen einer Stelle
        $Vorlage_stelle_angelegt = lade_mailvorlage('stelle_angelegt');
        if(isset($_POST['stelle_angelegt-betreff'])){
            $Value_betreff_stelle_angelegt = $_POST['stelle_angelegt-betreff'];
        } else {
            $Value_betreff_stelle_angelegt = $Vorlage_stelle_angelegt['betreff'];
        }

        if(isset($_POST['stelle_angelegt-text'])){
            $Value_text_stelle_angelegt = $_POST['stelle_angelegt-text'];
        } else {
            $Value_text_stelle_angelegt = $Vorlage_stelle_angelegt['text'];
        }
        
        $HTML_anlegen_einsatzstelle = 'Betreff:';
        $HTML_anlegen_einsatzstelle .= form_string_item('stelle_angelegt-betreff', $Value_betreff_stelle_angelegt, '', '');
        $HTML_anlegen_einsatzstelle .= '<br>Inhalt:';
        $HTML_anlegen_einsatzstelle .= form_html_area_item('stelle_angelegt-text', $Value_text_stelle_angelegt, '');
        $HTML_emails_collapsible .= collapsible_item_builder("Eine Stelle wurde angelegt", $HTML_anlegen_einsatzstelle, '');

        #Neue Helfer zu einer Stelle zugeordnet
        $Vorlage_stelle_neue_Helfer = lade_mailvorlage('stelle_neue_helfer');
        if(isset($_POST['stelle_neue_helfer-betreff'])){
            $Value_betreff_stelle_neue_helfer = $_POST['stelle_neue_helfer-betreff'];
        } else {
            $Value_betreff_stelle_neue_helfer = $Vorlage_stelle_neue_Helfer['betreff'];
        }

        if(isset($_POST['stelle_neue_helfer-text'])){
            $Value_text_stelle_neue_helfer = $_POST['stelle_neue_helfer-text'];
        } else {
            $Value_text_stelle_neue_helfer = $Vorlage_stelle_neue_Helfer['text'];
        }

        $HTML_stelle_neue_Helfer = 'Betreff:';
        $HTML_stelle_neue_Helfer .= form_string_item('stelle_neue_helfer-betreff', $Value_betreff_stelle_neue_helfer, '', '');
        $HTML_stelle_neue_Helfer .= '<br>Inhalt:';
        $HTML_stelle_neue_Helfer .= form_html_area_item('stelle_neue_helfer-text', $Value_text_stelle_neue_helfer, '');
        $HTML_emails_collapsible .= collapsible_item_builder("neue Helfer zu einer Stelle zugeordnet", $HTML_stelle_neue_Helfer, '');

        #Passwort reset
        $Vorlage_password_reset_stelle = lade_mailvorlage('stelle_pswd_rst');
        if(isset($_POST['password-reset-stelle-betreff'])){
            $Value_betreff_password_reset_stelle = $_POST['password-reset-stelle-betreff'];
        } else {
            $Value_betreff_password_reset_stelle = $Vorlage_password_reset_stelle['betreff'];
        }

        if(isset($_POST['password-reset-stelle-text'])){
            $Value_text_password_reset_stelle = $_POST['password-reset-stelle-text'];
        } else {
            $Value_text_password_reset_stelle = $Vorlage_password_reset_stelle['text'];
        }
        
        $HTML_password_reset_stelle = 'Betreff:';
        $HTML_password_reset_stelle .= form_string_item('password-reset-stelle-betreff', $Value_betreff_password_reset_stelle, '', '');
        $HTML_password_reset_stelle .= '<br>Inhalt:';
        $HTML_password_reset_stelle .= form_html_area_item('password-reset-stelle-text', $Value_text_password_reset_stelle, '');
        $HTML_emails_collapsible .= collapsible_item_builder("Passwort einer Stelle wurde zurückgesetzt", $HTML_password_reset_stelle, '');     


        #Passwort reset
        $Vorlage_password_reset_user = lade_mailvorlage('password_reset_user');
        if(isset($_POST['password_reset_user-betreff'])){
            $Value_betreff_password_reset_user = $_POST['password_reset_user-betreff'];
        } else {
            $Value_betreff_password_reset_user = $Vorlage_password_reset_user['betreff'];
        }

        if(isset($_POST['password_reset_user-text'])){
            $Value_text_password_reset_user = $_POST['password_reset_user-text'];
        } else {
            $Value_text_password_reset_user = $Vorlage_password_reset_user['text'];
        }
        
        $HTML_password_reset_user = 'Betreff:';
        $HTML_password_reset_user .= form_string_item('password_reset_user-betreff', $Value_betreff_password_reset_user, '', '');
        $HTML_password_reset_user .= '<br>Inhalt:';
        $HTML_password_reset_user .= form_html_area_item('password_reset_user-text', $Value_text_password_reset_user, '');
        $HTML_emails_collapsible .= collapsible_item_builder("User-Passwort wurde zurückgesetzt", $HTML_password_reset_user, '');     

        #einteilung_bearbeitet
        $Vorlage_einteilung_bearbeitet = lade_mailvorlage('einteilung_bearbeitet');
        if(isset($_POST['einteilung_bearbeitet-betreff'])){
            $Value_betreff_einteilung_bearbeitet = $_POST['einteilung_bearbeitet-betreff'];
        } else {
            $Value_betreff_einteilung_bearbeitet = $Vorlage_einteilung_bearbeitet['betreff'];
        }

        if(isset($_POST['einteilung_bearbeitet-text'])){
            $Value_text_einteilung_bearbeitet = $_POST['einteilung_bearbeitet-text'];
        } else {
            $Value_text_einteilung_bearbeitet = $Vorlage_einteilung_bearbeitet['text'];
        }
        
        $HTML_einteilung_bearbeitet = 'Betreff:';
        $HTML_einteilung_bearbeitet .= form_string_item('einteilung_bearbeitet-betreff', $Value_betreff_einteilung_bearbeitet, '', '');
        $HTML_einteilung_bearbeitet .= '<br>Inhalt:';
        $HTML_einteilung_bearbeitet .= form_html_area_item('einteilung_bearbeitet-text', $Value_text_einteilung_bearbeitet, '');
        $HTML_emails_collapsible .= collapsible_item_builder("Eine Einteilung wurde bearbeitet", $HTML_einteilung_bearbeitet, '');  
        
        #einteilung_gelöscht
        $Vorlage_einteilung_geloescht = lade_mailvorlage('einteilung_geloescht');
        if(isset($_POST['einteilung_geloescht-betreff'])){
            $Value_betreff_einteilung_geloescht = $_POST['einteilung_geloescht-betreff'];
        } else {
            $Value_betreff_einteilung_geloescht = $Vorlage_einteilung_geloescht['betreff'];
        }

        if(isset($_POST['einteilung_geloescht-text'])){
            $Value_text_einteilung_geloescht = $_POST['einteilung_geloescht-text'];
        } else {
            $Value_text_einteilung_geloescht = $Vorlage_einteilung_geloescht['text'];
        }
        
        $HTML_einteilung_geloescht = 'Betreff:';
        $HTML_einteilung_geloescht .= form_string_item('einteilung_geloescht-betreff', $Value_betreff_einteilung_geloescht, '', '');
        $HTML_einteilung_geloescht .= '<br>Inhalt:';
        $HTML_einteilung_geloescht .= form_html_area_item('einteilung_geloescht-text', $Value_text_einteilung_geloescht, '');
        $HTML_emails_collapsible .= collapsible_item_builder("Eine Einteilung wurde gelöscht", $HTML_einteilung_geloescht, ''); 
        $HTML_emails = collapsible_builder($HTML_emails_collapsible);

    $HTML_emails = collapsible_item_builder("Email-Vorlagen",$HTML_emails,'');
    $HTML_collapsible .= $HTML_emails;
    $HTML .= section_builder(collapsible_builder($HTML_collapsible));

    #Buttons
    $HTML .= section_builder(table_builder(table_row_builder(table_data_builder(form_button_builder('change_taskforce_settings', 'Speichern', 'action', 'send')).table_data_builder(button_link_creator('Zurück', './taskforce_main_view.php', 'arrow_back', '')))));
    $HTML = form_builder($HTML, './settings.php', 'post', 'edit_settings_form', '');

    #ResetButtons hinzufügen
    $HTML_reset = "<h5>ACHTUNG! Das Zurücksetzen kann nicht rückgängig gemacht werden</h5>";
    $HTML_reset .= table_builder(table_row_builder(table_data_builder(form_button_builder('settings_to_default', 'Einstellungen zurücksetzen','reset_settings','','')).table_data_builder(form_button_builder('mails_to_default', 'Emails zurücksetzen', 'reset_mails',''))));
    $HTML_reset = section_builder($HTML_reset,'','');
    $HTML .= form_builder($HTML_reset, './settings.php', 'post', 'reset_settings_form', '');

    return $HTML;   
}

function settings_taskforce_parser(){

    if(isset($_POST['change_taskforce_settings'])){

        $ChangeCounter = 0;
        $ErrCounter = 0;
        $ErrMessage = "";

        #Seiteninhalte
        #Parser Startseitentext
        if(lade_xml_einstellung('text-startseite-html') != $_POST['text-startseite-html']){
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if($_POST['text-startseite-html'] == ''){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Startseiteninhalt darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('text-startseite-html', $_POST['text-startseite-html'], $mode='cdata');
                $ChangeCounter++;
            }
        }

        #Parser helper_info_on_dropout
        if(lade_xml_einstellung('helper_info_on_dropout') != $_POST['helper_info_on_dropout']){
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if($_POST['helper_info_on_dropout'] == ''){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Infotext zur Abmeldung aus dem Helferpool darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('helper_info_on_dropout', $_POST['helper_info_on_dropout'], $mode='cdata');
                $ChangeCounter++;
            }
        }

        #Parser infotext-fehlender-vertrag
        if(lade_xml_einstellung('infotext-fehlender-vertrag') != $_POST['infotext-fehlender-vertrag']){
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if($_POST['infotext-fehlender-vertrag'] == ''){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Infotext für fehlende Verträge darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('infotext-fehlender-vertrag', $_POST['infotext-fehlender-vertrag'], $mode='cdata');
                $ChangeCounter++;
            }
        }        

        #Parser infotext fehlendes-geburtsdatum
        if(lade_xml_einstellung('titeltext-fehlendes-geburtsdatum') != $_POST['titeltext-fehlendes-geburtsdatum']){
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if($_POST['titeltext-fehlendes-geburtsdatum'] == ''){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Infotext zu einem fehlendem Geburtsdatum darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('titeltext-fehlendes-geburtsdatum', $_POST['titeltext-fehlendes-geburtsdatum'], $mode='cdata');
                $ChangeCounter++;
            }
        }

        #Parser infotext fehlendes-geburtsdatum
        if(lade_xml_einstellung('titeltext-umfrage-verfuegbarkeit-semester') != $_POST['titeltext-umfrage-verfuegbarkeit-semester']){
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if($_POST['titeltext-umfrage-verfuegbarkeit-semester'] == ''){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Titeltext zur Umfrage zur Semesterverfügbarkeit darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('titeltext-umfrage-verfuegbarkeit-semester', $_POST['titeltext-umfrage-verfuegbarkeit-semester'], $mode='cdata');
                $ChangeCounter++;
            }
        }

        #Parser infotext fehlendes-geburtsdatum
        if(lade_xml_einstellung('infotext-umfrage-verfuegbarkeit-semester') != $_POST['infotext-umfrage-verfuegbarkeit-semester']){
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if($_POST['infotext-umfrage-verfuegbarkeit-semester'] == ''){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Infotext zur Umfrage zur Semesterverfügbarkeit darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('infotext-umfrage-verfuegbarkeit-semester', $_POST['infotext-umfrage-verfuegbarkeit-semester'], $mode='cdata');
                $ChangeCounter++;
            }
        }

        #Parser impressum
        if(lade_xml_einstellung('impressum-content') != $_POST['impressum-content']){
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if($_POST['impressum-content'] == ''){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Das Impressum darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('impressum-content', $_POST['impressum-content'], $mode='cdata');
                $ChangeCounter++;
            }
        }        

        #Parser DSE
        if(lade_xml_einstellung('ds-erklaerung-content') != $_POST['ds-erklaerung-content']){
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if($_POST['ds-erklaerung-content'] == ''){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Die Datenschutzerklärung darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('ds-erklaerung-content', $_POST['ds-erklaerung-content'], $mode='cdata');
                $ChangeCounter++;
            }
        }    
        
        #Parser Einwilligung Datenweitergabe
        if(lade_xml_einstellung('ds-erklaerung-externe-einwilligen') != $_POST['ds-erklaerung-externe-einwilligen']){
            if($_POST['ds-erklaerung-externe-einwilligen' == '']){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Text Einwilligung Datenweitergabe darf nicht leer sein!<br>";
            } else {
                update_xml_einstellung('ds-erklaerung-externe-einwilligen', $_POST['ds-erklaerung-externe-einwilligen'], $mode='cdata');
                $ChangeCounter++;
            }
        }

        #Parser RegisterActive

        if(lade_xml_einstellung('register_active') !== $_POST['register_active']){
            if (!(($_POST['register_active'] == 'false') or ($_POST['register_active'] == 'true'))){
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Fehler beim Abspeichern der Einstellung <b>Selbstregistrierung erlauben</b>!<br>";
            } else {
                update_xml_einstellung('register_active', $_POST['register_active']);
                $ChangeCounter++;
            }
        }    

        #Registrierung erfolgreich automatisch
        $MailRegErfolgreichAuto = lade_mailvorlage('registrierung-erfolgreich-auto-helfer');

        if ($MailRegErfolgreichAuto['betreff'] != $_POST['registrierung-erfolgreich-auto-helfer-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['registrierung-erfolgreich-auto-helfer-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>Automatische Registrierung</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('registrierung-erfolgreich-auto-helfer', 'betreff', utf8_decode($_POST['registrierung-erfolgreich-auto-helfer-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>Automatische Registrierung</b>!<br>";
                }
            }
        }
        if ($MailRegErfolgreichAuto['text'] != $_POST['registrierung-erfolgreich-auto-helfer-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['registrierung-erfolgreich-auto-helfer-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>Automatische Registrierung</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('registrierung-erfolgreich-auto-helfer', 'text', utf8_decode($_POST['registrierung-erfolgreich-auto-helfer-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>Automatische Registrierung</b>!<br>";
                }
            }
        }

        #manuelle Registrierung
        $MailReg_manuell = lade_mailvorlage('registrierung-erfolgreich-register-formular');

        if ($MailReg_manuell['betreff'] != $_POST['registrierung-erfolgreich-register-formular-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['registrierung-erfolgreich-register-formular-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>Manuelle Registrierung</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('registrierung-erfolgreich-register-formular', 'betreff', utf8_decode($_POST['registrierung-erfolgreich-register-formular-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>Manuelle Registrierung</b>!<br>";
                }
            }
        }
        if ($MailReg_manuell['text'] != $_POST['registrierung-erfolgreich-register-formular-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['registrierung-erfolgreich-register-formular-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>Manuelle Registrierung</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('registrierung-erfolgreich-register-formular', 'text', utf8_decode($_POST['registrierung-erfolgreich-register-formular-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>Manuelle Registrierung</b>!<br>";
                }
            }
        }

        #neue Einteilung zu einer Stelle
        $Mail_neue_Einteilung_Stelle = lade_mailvorlage('einteilung-einsatzstelle');

        if ($Mail_neue_Einteilung_Stelle['betreff'] != $_POST['einteilung-einsatzstelle-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['einteilung-einsatzstelle-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>neue Einteilung zu einer Stelle</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('einteilung-einsatzstelle', 'betreff', utf8_decode($_POST['einteilung-einsatzstelle-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>neue Einteilung zu einer Stelle</b>!<br>";
                }
            }
        }
        if ($Mail_neue_Einteilung_Stelle['text'] != $_POST['einteilung-einsatzstelle-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['einteilung-einsatzstelle-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>neue Einteilung zu einer Stelle</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('einteilung-einsatzstelle', 'text', utf8_decode($_POST['einteilung-einsatzstelle-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>neue Einteilung zu einer Stelle</b>!<br>";
                }
            }
        }
       
        #Eine Stelle wurde angelegt
        $Mail_Stelle_angelegt = lade_mailvorlage('stelle_angelegt');

        if ($Mail_Stelle_angelegt['betreff'] != $_POST['stelle_angelegt-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['stelle_angelegt-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>Eine Stelle wurde angelegt</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('stelle_angelegt', 'betreff', utf8_decode($_POST['stelle_angelegt-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>Eine Stelle wurde angelegt</b>!<br>";
                }
            }
        }
        if ($Mail_Stelle_angelegt['text'] != $_POST['stelle_angelegt-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['stelle_angelegt-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>Eine Stelle wurde angelegt</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('stelle_angelegt', 'text', utf8_decode($_POST['stelle_angelegt-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>Eine Stelle wurde angelegt</b>!<br>";
                }
            }
        }
       

        #neue Helfer zu einer Stelle zugeordnet
        $Mail_Stelle_neue_Helfer = lade_mailvorlage('stelle_neue_helfer');

        if ($Mail_Stelle_neue_Helfer['betreff'] != $_POST['stelle_neue_helfer-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['stelle_neue_helfer-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>neue Helfer zu einer Stelle zugeordnet</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('stelle_neue_helfer', 'betreff', utf8_decode($_POST['stelle_angelegt-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>neue Helfer zu einer Stelle zugeordnet</b>!<br>";
                }
            }
        }
        if ($Mail_Stelle_neue_Helfer['text'] != $_POST['stelle_neue_helfer-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['stelle_neue_helfer-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>neue Helfer zu einer Stelle zugeordnet</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('stelle_neue_helfer', 'text', utf8_decode($_POST['stelle_neue_helfer-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>neue Helfer zu einer Stelle zugeordnet</b>!<br>";
                }
            }
        }

        #Passwort einer Stelle wurde zurückgesetzt
        $Mail_Stelle_psw_reset = lade_mailvorlage('stelle_pswd_rst');

        if ($Mail_Stelle_psw_reset['betreff'] != $_POST['password-reset-stelle-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['password-reset-stelle-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>Passwort einer Stelle wurde zurückgesetzt</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('stelle_pswd_rst', 'betreff', utf8_decode($_POST['password-reset-stelle-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>Passwort einer Stelle wurde zurückgesetzt</b>!<br>";
                }
            }
        }
        if ($Mail_Stelle_psw_reset['text'] != $_POST['password-reset-stelle-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['password-reset-stelle-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>Passwort einer Stelle wurde zurückgesetzt</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('stelle_pswd_rst', 'text', utf8_decode($_POST['password-reset-stelle-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>Passwort einer Stelle wurde zurückgesetzt</b>!<br>";
                }
            }
        }

        #User-Passwort wurde zurückgesetzt
        $Mail_User_psw_reset = lade_mailvorlage('password_reset_user');

        if ($Mail_User_psw_reset['betreff'] != $_POST['password_reset_user-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['password_reset_user-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>User-Passwort wurde zurückgesetzt</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('password_reset_user', 'betreff', utf8_decode($_POST['password_reset_user-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>User-Passwort wurde zurückgesetzt</b>!<br>";
                }
            }
        }
        if ($Mail_User_psw_reset['text'] != $_POST['password_reset_user-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['password_reset_user-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>User-Passwort wurde zurückgesetzt</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('password_reset_user', 'text', utf8_decode($_POST['password_reset_user-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>User-Passwort wurde zurückgesetzt</b>!<br>";
                }
            }
        }


        #Eine Einteilung wurde bearbeitet
        $Mail_Einteilung_bearbeitet = lade_mailvorlage('einteilung_bearbeitet');

        if ($Mail_Einteilung_bearbeitet['betreff'] != $_POST['einteilung_bearbeitet-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['einteilung_bearbeitet-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>Eine Einteilung wurde bearbeitet</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('einteilung_bearbeitet', 'betreff', utf8_decode($_POST['einteilung_bearbeitet-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>Eine Einteilung wurde bearbeitet</b>!<br>";
                }
            }
        }
        if ($Mail_Einteilung_bearbeitet['text'] != $_POST['einteilung_bearbeitet-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['einteilung_bearbeitet-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>Eine Einteilung wurde bearbeitet</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('einteilung_bearbeitet', 'text', utf8_decode($_POST['einteilung_bearbeitet-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>Eine Einteilung wurde bearbeitet</b>!<br>";
                }
            }
        }

        #Eine Einteilung wurde gelöscht
        $Mail_Einteilung_geloescht = lade_mailvorlage('einteilung_geloescht');

        if ($Mail_Einteilung_geloescht['betreff'] != $_POST['einteilung_geloescht-betreff']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['einteilung_geloescht-betreff'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Betreff der Mail <b>Eine Einteilung wurde gelöscht</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('einteilung_geloescht', 'betreff', utf8_decode($_POST['einteilung_geloescht-betreff']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Betreffs der Mail <b>Eine Einteilung wurde gelöscht</b>!<br>";
                }
            }
        }
        if ($Mail_Einteilung_geloescht['text'] != $_POST['einteilung_geloescht-text']) {
            #Hier könnte man auch mal prüfen auf sinnhaftigkeit prüfen, zB
            if ($_POST['einteilung_geloescht-text'] == '') {
                $ChangeCounter++;
                $ErrCounter++;
                $ErrMessage .= "Der Inhalt der Mail <b>Eine Einteilung wurde gelöscht</b> darf nicht leer sein!<br>";
            } else {
                if (edit_mailvorlage('einteilung_geloescht', 'text', utf8_decode($_POST['einteilung_geloescht-text']))) {
                    $ChangeCounter++;
                } else {
                    $ChangeCounter++;
                    $ErrCounter++;
                    $ErrMessage .= "Fehler beim Ändern des Inhalts der Mail <b>Eine Einteilung wurde gelöscht</b>!<br>";
                }
            }
        }

        ##### DONT TOUCH ########

        #Wurde was verändert?
        if($ChangeCounter > 0){
            #Gab es einen Fehler?
            if($ErrCounter == 0){
                return 'erfolg';
            } elseif ($ErrCounter > 0){
                return $ErrMessage;
            }
        } else {
            return 'null';
        }
    }

    #Parser für reset-Buttons
    if (isset($_POST['settings_to_default'])) {
        if (reset_setting('settings')) {
            add_protocol_entry('settings','Einstellungen zurückgesetzt durch User'.lade_user_id());
            return 'erfolg';
        } else {
            $ErrMessage = "Fehler beim Zurücksetzen der <b>Einstellungen</b>!<br>";
            return $ErrMessage;
        }
    } elseif (isset($_POST['mails_to_default'])) {
        if (reset_setting('mails')) {
            add_protocol_entry('settings','Mailvorlagen zurückgesetzt durch User'.lade_user_id());
            return 'erfolg';
        } else {
            $ErrMessage = "Fehler beim Zurücksetzen der <b>Mail-Vorlagen</b>!<br>";
            return $ErrMessage;
        }
    } else {
        return 'null';
    }
}


