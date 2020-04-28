<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Schl&uuml;sselverwaltung - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;sselverwaltung</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Schl&uuml;ssel verwalten</h1>';
$HTML = section_builder($PageTitle);

#ParserStuff
$Parser = parser_schluesselmanagement();
if(isset($Parser['meldung'])){
    $HTML .= "<h5>".$Parser['meldung']."</h5>";
}

# Content
$HTML .= spalte_anstehende_rueckgaben();
$HTML .= spalte_verfuegbare_schluessel();
$HTML .= spalte_dir_zugeteilte_schluessel();
$HTML .= spalte_schluessel_verwalten();

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function spalte_anstehende_rueckgaben(){

    $link = connect_db();
    zeitformat();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Anstehende R&uuml;ckgaben</h5>";
    $HTML .= "<h5 class='header center-align hide-on-large-only'>Anstehende R&uuml;ckgaben</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' ORDER BY schluessel ASC";
    $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
    $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);

    if ($AnzahlLadeAlleSchluesselausgaben == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Keine anstehenden R&uuml;ckgaben!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeAlleSchluesselausgaben > 0){

        $Counter = 0;

        for($a = 1; $a <= $AnzahlLadeAlleSchluesselausgaben; $a++){

            $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);

            //Reservierung vorbei oder storniert?
            $Reservierung = lade_reservierung($Ausgabe['reservierung']);

            if ((strtotime($Reservierung['ende']) < time()) OR ($Reservierung['storno_user'] != "0")){
                //darf er dan Schlüssel weiter behalten?
                $AnfrageWeitereReservierungenMitDiesemSchluessel = "SELECT id, reservierung FROM schluesselausgabe WHERE user = '".$Ausgabe['user']."' AND schluessel = '".$Ausgabe['schluessel']."' AND storno_user = '0' AND rueckgabe = '0000-00-00 00:00:00' AND id <> '".$Ausgabe['id']."'";
                $AbfrageWeitereReservierungenMitDiesemSchluessel = mysqli_query($link, $AnfrageWeitereReservierungenMitDiesemSchluessel);
                $AnzahlWeitereReservierungenMitDiesemSchluessel = mysqli_num_rows($AbfrageWeitereReservierungenMitDiesemSchluessel);

                if ($AnzahlWeitereReservierungenMitDiesemSchluessel > 0){

                    //Er darf den schlüssel noch weiter behalte

                } else if ($AnzahlWeitereReservierungenMitDiesemSchluessel == 0){

                    $Counter++;

                    $Schluessel = lade_schluesseldaten($Ausgabe['schluessel']);

                    $FahrtZuendeSeit = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($Reservierung['ende']));
                    $LetzteUsererinnerungLaden = lade_letze_erinnerung_schluesselrueckgabe($Ausgabe['user']);
                    if($LetzteUsererinnerungLaden == NULL){
                        $LetzeErinnerung = "Nie erfolgt.";
                    } else {
                        $LetzeErinnerung = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($LetzteUsererinnerungLaden));
                    }

                    //Er soll den schlüssel zurück geben
                    $HTML .= "<li>";
                    $HTML .= "<div class='collapsible-header'><i class='large material-icons ".$Schluessel['farbe_materialize']."'>vpn_key</i>Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."</div>";
                    $HTML .= "<div class='collapsible-body'>";
                    $HTML .= "<div class='container'>";
                    $HTML .= "<form method='post'>";
                    $HTML .= "<ul class='collection'>";
                    $HTML .= "<li class='collection-item'>Fahrtende: ".$FahrtZuendeSeit."</li>";
                    $HTML .= "<li class='collection-item'>Letzte Erinnerung: ".$LetzeErinnerung."</li>";
                    $HTML .= collection_item_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_rueckgabe_festhalten', 'Rückgabe', 'action', 'send', ''));
                    $HTML .= collection_item_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_rueckgabe_und_mitnehmen', 'Mitnehmen', 'action', 'send', ''));
                    $HTML .= collection_item_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_erinnerung_senden', 'Erinnerung', 'action', 'send', ''));
                    $HTML .= "</ul>";
                    $HTML .= "</form>";
                    $HTML .= "</div>";
                    $HTML .= "</div>";
                    $HTML .= "</li>";
                }
            }
        }

        if ($Counter == 0){
            $HTML .= "<li>";
            $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Keine anstehenden R&uuml;ckgaben!</div>";
            $HTML .= "</li>";
        }
    }

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_verfuegbare_schluessel(){

    $link = connect_db();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Verf&uuml;gbare Schl&uuml;ssel</h5>";
    $HTML .= "<h5 class='header center-align hide-on-large-only'>Verf&uuml;gbare Schl&uuml;ssel</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id, farbe, farbe_materialize FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    if ($AnzahlLadeVerfuegbareSchluessel == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons icon-red'>info</i>Derzeit keine Schl&uuml;ssel im R&uuml;ckgabekasten!</div>";
        $HTML .= "</li>";
        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons icon-red'>info</i>R&uuml;ckgabekasten leer!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeVerfuegbareSchluessel > 0){

        for ($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);

            $TitleString = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."";
            $Content = form_builder(table_builder(table_header_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_herausnehmen', 'Herausnehmen', 'action', 'send'))), '#', 'post', '','');
            $HTML .= collapsible_item_builder($TitleString, $Content, 'vpn_key', $Schluessel['farbe_materialize']);

        }

    }

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_dir_zugeteilte_schluessel(){

    $link = connect_db();

    $VerfuegbareSchluessel = wart_verfuegbare_schluessel(lade_user_id());

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Dir zugeteilte Schl&uuml;ssel</h5>";
    $HTML .= "<h5 class='header center-align hide-on-large-only'>Dir zugeteilte Schl&uuml;ssel</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeZugeteilteSchluessel = "SELECT id, farbe, farbe_materialize FROM schluessel WHERE akt_user = '".lade_user_id()."' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeZugeteilteSchluessel = mysqli_query($link, $AnfrageLadeZugeteilteSchluessel);
    $AnzahlLadeZugeteilteSchluessel = mysqli_num_rows($AbfrageLadeZugeteilteSchluessel);

    if ($AnzahlLadeZugeteilteSchluessel == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons icon-red'>info</i>Derzeit sind dir keine Schl&uuml;ssel zugeteilt!</div>";
        $HTML .= "</li>";
        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons icon-red'>info</i>Du bist Schl&uuml;ssellos!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeZugeteilteSchluessel > 0){

        for ($a = 1; $a <= $AnzahlLadeZugeteilteSchluessel; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeZugeteilteSchluessel);

            $Titel = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."";
            if ($VerfuegbareSchluessel > 0) {
                $Content = form_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('action_schluessel_' . $Schluessel['id'] . '_zuruecklegen', 'Zurücklegen', 'action', 'send', '')))), '#', 'post', '', '');
            } else {
                $Content = "Schlüssel bereits verplant!";
            }
            $HTML .= collapsible_item_builder($Titel, $Content, 'vpn_key', $Schluessel['farbe_materialize']);

        }

    }

    if($AnzahlLadeZugeteilteSchluessel > wart_verfuegbare_schluessel(lade_user_id())){

        $Differenz = $AnzahlLadeZugeteilteSchluessel - wart_verfuegbare_schluessel(lade_user_id());
        if ($Differenz == 1){
            $Grammatik = "ist bereits einer ";
        } else if ($Differenz > 1){
            $Grammatik = "sind bereits ".$Differenz." ";
        }


        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Achtung!</div>";
        $HTML .= "<div class='collapsible-body'>";

            if ($AnzahlLadeZugeteilteSchluessel == 1){
                $HTML .= "<p>Der dir zugeteilte Schl&uuml;ssel ist bereits f&uuml;r eine &Uuml;bergabe eingeplant!</p>";
            } else if ($AnzahlLadeZugeteilteSchluessel > 1){
                $HTML .= "<p>Von den ".$AnzahlLadeZugeteilteSchluessel." dir zugeteilten Schl&uuml;sseln, ".$Grammatik."f&uuml;r &Uuml;bergaben eingeplant!</p>";
            }

        $HTML .= "</div>";
        $HTML .= "</li>";

    }


    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_schluessel_verwalten(){

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Schl&uuml;ssel verwalten</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $HTML .= schluessel_umbuchen_listenelement_generieren();
    $HTML .= schluessel_bearbeiten_listenelement_generieren();
    $HTML .= schluessel_hinzufuegen_listenelement_generieren();

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}


function schluessel_umbuchen_listenelement_generieren(){

    $FormTable = table_row_builder(table_header_builder('Schlüssel auswählen').table_data_builder(dropdown_aktive_schluessel('id_schluessel_umbuchen')));
    $FormTable .= table_row_builder(table_header_builder('An Wart umbuchen').table_data_builder(dropdown_menu_wart('an_wart_umbuchen', $_POST['an_wart_umbuchen'])));
    $FormTable .= table_row_builder(table_header_builder('An Ort umbuchen').table_data_builder(dropdown_schluesselorte('an_ort_umbuchen', $_POST['an_ort_umbuchen'])));
    $FormTable .= table_row_builder(table_header_builder(form_button_builder('action_schluessel_umbuchen', 'Umbuchen', 'action', 'send', '')).table_data_builder(''));
    $FormTable = table_builder($FormTable);
    $FormTable = form_builder($FormTable, '#', 'post', '', '');
    $HTML = collapsible_item_builder('Schlüssel umbuchen', $FormTable, 'swap_calls');

    return $HTML;
}
function schluessel_bearbeiten_listenelement_generieren(){

    $FormHTML = table_row_builder(table_header_builder('Schlüssel auswählen').table_data_builder(dropdown_aktive_schluessel('id_schluessel_bearbeiten')));
    $FormHTML .= table_form_select_item('Schlüsselnummer ändern', 'schluessel_id', 1, 50, $_POST['schluessel_id'], '', 'Schlüsselnummer', '', false);
    $FormHTML .= table_form_string_item('Farbe', 'farbe_schluessel', $_POST['farbe_schluessel'], false);
    $FormHTML .= table_form_string_item('Farbe in materialize.css', 'farbe_schluessel_mat', $_POST['farbe_schluessel_mat'], false);
    $FormHTML .= table_form_string_item('RFID Code', 'rfid_code', $_POST['rfid_code'], false);
    $FormHTML .= table_row_builder(table_header_builder(form_button_builder('action_schluessel_bearbeiten', 'Eintragen', 'submit', 'send', '')." ".form_button_builder('action_schluessel_loeschen', 'Löschen', 'action', 'delete', '')).table_data_builder(''));
    $FormHTML = table_builder($FormHTML);
    $FormHTML = form_builder($FormHTML, '#', 'post', '', '');

    $HTML = collapsible_item_builder('Schlüssel bearbeiten', $FormHTML, 'edit');

    return $HTML;
}
function schluessel_hinzufuegen_listenelement_generieren(){

    if(isset($_POST['is_wartschluessel'])){
        $AnAusWart = 'on';
    } else {
        $AnAusWart = 'off';
    }

    $FormHTML = table_form_select_item('Schlüsselnummer', 'schluessel_id', 1, 50, $_POST['schluessel_id'], '', 'Schlüsselnummer', '', false);
    $FormHTML .= table_form_string_item('Farbe', 'farbe_schluessel', $_POST['farbe_schluessel'], false);
    $FormHTML .= table_form_string_item('Farbe in materialize.css', 'farbe_schluessel_mat', $_POST['farbe_schluessel_mat'], false);
    $FormHTML .= table_form_string_item('RFID Code', 'rfid_code', $_POST['rfid_code'], false);
    $FormHTML .= table_form_swich_item('Ist ein Wartschlüssel', 'is_wartschluessel', 'Nein', 'Ja', $AnAusWart, false);
    $FormHTML .= table_row_builder(table_header_builder(form_button_builder('action_schluessel_hinzufuegen', 'Eintragen', 'submit', 'send', '')).table_data_builder(''));
    $FormHTML = table_builder($FormHTML);
    $FormHTML = form_builder($FormHTML, '#', 'post', '', '');

    $HTML = collapsible_item_builder('Schlüssel anlegen', $FormHTML, 'library_add');

    return $HTML;
}


function parser_schluesselmanagement(){

    $Parser = spalte_anstehende_rueckgaben_parser();

    if($Parser['success'] == NULL){
        $Parser = spalte_dir_zugeteilte_schluessel_parser();
    }

    spalte_verfuegbare_schluessel_parser();

    if (isset($_POST['action_schluessel_hinzufuegen'])){
        $Parser = schluessel_hinzufuegen($_POST['schluessel_id'], $_POST['farbe_schluessel'], $_POST['farbe_schluessel_mat'], $_POST['rfid_code']);
    }

    if(isset($_POST['action_schluessel_umbuchen'])){
        $Parser = schluessel_umbuchen_listenelement_parser($_POST['id_schluessel_umbuchen'], $_POST['an_wart_umbuchen'], $_POST['an_ort_umbuchen']);
    }

    if (isset($_POST['action_schluessel_bearbeiten'])){
        $Parser = schluessel_bearbeiten($_POST['id_schluessel_bearbeiten'], $_POST['schluessel_id'], $_POST['farbe_schluessel_bearbeiten'], $_POST['farbe_schluessel_mat_bearbeiten'], $_POST['rfid_code_bearbeiten']);
    }

    if (isset($_POST['action_schluessel_loeschen'])){
        $Parser = schluessel_loeschen($_POST['id_schluessel_bearbeiten']);
    }

    return $Parser;
}

function spalte_anstehende_rueckgaben_parser(){

    $link = connect_db();

    $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' ORDER BY schluessel ASC";
    $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
    $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);
    $UserID = lade_user_id();

    for ($a = 1; $a <= $AnzahlLadeAlleSchluesselausgaben; $a++){

        $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);

        $ActionName = "action_schluessel_".$Ausgabe['schluessel']."_rueckgabe_festhalten";
        $ErinnerungName = "action_schluessel_".$Ausgabe['schluessel']."_erinnerung_senden";
        $PostNameGenerierenHerausnehmen = "action_schluessel_".$Ausgabe['schluessel']."_rueckgabe_und_mitnehmen";

        if (isset($_POST[$ActionName])){

            $Antwort = schluessel_umbuchen($Ausgabe['schluessel'], '', 'rueckgabekasten', $UserID);
            $Event = "Schl&uuml;ssel ".$Ausgabe['schluessel']." von ".$UserID." als zurückgegeben vermerkt";
            add_protocol_entry($UserID, $Event, 'schluessel');

        }

        if(isset($_POST[$PostNameGenerierenHerausnehmen])){
            $Antwort = schluessel_umbuchen($Ausgabe['schluessel'], $UserID, '', $UserID);
            $Event = "Schl&uuml;ssel ".$Ausgabe['schluessel']." von ".$UserID." aus R&uuml;ckgabekasten genommen und die Rückgabe gespeichert";
            add_protocol_entry($UserID, $Event, 'schluessel');
        }


        if (isset($_POST[$ErinnerungName])){
            $Antwort['success'] = false;
            $Antwort['meldung'] = 'Diese Funktion muss noch implementiert werden!';
        }

    }

    return $Antwort;
}
function spalte_verfuegbare_schluessel_parser(){

    $link = connect_db();

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);
    $UserID = lade_user_id();

    for($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

        $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
        $PostNameGenerieren = "action_schluessel_".$Schluessel['id']."_herausnehmen";

        if(isset($_POST[$PostNameGenerieren])){
            $Antwort = schluessel_umbuchen($Schluessel['id'], $UserID, '', $UserID);
            $Event = "Schl&uuml;ssel ".$Schluessel['id']." von ".$UserID." aus R&uuml;ckgabekasten genommen";
            add_protocol_entry($UserID, $Event, 'schluessel');
        }
    }

    return $Antwort;
}
function spalte_dir_zugeteilte_schluessel_parser(){

    $link = connect_db();
    $Antwort = array();

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id FROM schluessel WHERE akt_user = '".lade_user_id()."' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    for($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

        $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
        $PostNameGenerieren = "action_schluessel_".$Schluessel['id']."_zuruecklegen";
        if(isset($_POST[$PostNameGenerieren])){
            $Antwort = schluessel_umbuchen($Schluessel['id'],'', 'rueckgabekasten', lade_user_id());
        }
    }

    return $Antwort;
}







?>