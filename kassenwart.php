<?php
include_once "./ressources/ressourcen.php";
session_manager('ist_kasse');
$Header = "Vereinskasse - " . lade_db_einstellung('site_name');
$HTML = section_builder("<h1 class='center-align'>Vereinskasse</h1>");

if($_POST['year_global']!=''){
   $YearGlobal = $_POST['year_global'];
} else {
    $YearGlobal = date('Y');
}

#ParserStuff
$Parser = vereinskasse_parser($YearGlobal);
if(isset($Parser['meldung'])){
    $HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";
}

if($Parser['ansicht']==null){
    $HTML .= uebersicht_section_vereinskasse($YearGlobal);
    $HTML .= kontos_section_vereinskasse($YearGlobal, $Parser);
    $HTML .= add_transaktions_vereinskasse();
    $HTML .= choose_views_vereinskasse();
} elseif ($Parser['ansicht']=='guv'){
    $HTML .= guv_rechnung_jahr($YearGlobal);
} elseif ($Parser['ansicht']=='konto_details'){
    $HTML .= konto_details($YearGlobal, $Parser['konto_id']);
} elseif ($Parser['ansicht']=='list_all_forderungen'){
    $HTML .= forderungen_section_vereinskasse($YearGlobal);
} elseif ($Parser['ansicht']=='list_all_ausgaben'){
    $HTML .= ausgaben_section_vereinskasse($YearGlobal);
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function vereinskasse_parser($YearGlobal){

    $Antwort = array();
    $Antwort['ansicht']=null;

    for($a=1;$a<=10000;$a++){
        if(isset($_POST['konto_details_'.$a.''])){
            $Antwort['konto_id']=$a;
            $Antwort['ansicht']='konto_details';
        }
        if(isset($_POST['einnahme_stornieren_'.$a])){
            $Result = einnahme_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='list_all_forderungen';
        }
        if(isset($_POST['einnahme_storno_aufheben_'.$a])){
            $Result = undo_einnahme_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='list_all_forderungen';
        }
        if(isset($_POST['delete_forderung_'.$a])){
            $Result = forderung_stornieren($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_forderungen';
        }
        if(isset($_POST['undo_storno_forderung_'.$a])){
            $Result = undo_forderung_stornieren($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_forderungen';
        }
        if(isset($_POST['delete_ausgleich_'.$a])){
            $Result = ausgleich_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_ausgaben';
        }
        if(isset($_POST['undo_storno_ausgleich_'.$a])){
            $Result = undo_ausgleich_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_ausgaben';
        }
        if(isset($_POST['ausgabe_stornieren_'.$a])){
            $Result = ausgabe_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['ansicht']='list_all_ausgaben';
        }
        if(isset($_POST['ausgabe_stornieren_'.$a])){
            $Result = undo_ausgabe_loeschen($a);
            $Antwort['success']=$Result['success'];
            $Antwort['meldung']=$Result['meldung'];
            $Antwort['ansicht']='list_all_ausgaben';
        }
    }

    if(isset($_POST['action_add_konto'])){
        $Antwort = konto_anlegen($_POST['new_konto_name'], $_POST['new_konto_typ'], $_POST['new_konto_initial']);
        $Antwort['ansicht']=null;
    }

    if(isset($_POST['activate_guv'])){
        $Antwort['ansicht']='guv';
    }

    if(isset($_POST['activate_list_all_forderungen'])){
        $Antwort['ansicht']='list_all_forderungen';
    }

    if(isset($_POST['activate_list_all_ausgaben'])){
        $Antwort['ansicht']='list_all_ausgaben';
    }
    if(isset($_POST['reset_view'])){
        $Antwort['ansicht']=null;
    }

    return $Antwort;
}
function guv_rechnung_jahr($YearGlobal){

    $link = connect_db();

    //Einnahmenkonten
    $Anfrage5 = "SELECT * FROM finanz_konten WHERE typ = 'einnahmenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage5 = mysqli_query($link, $Anfrage5);
    $Anzahl5 = mysqli_num_rows($Abfrage5);

    $Anfrage6 = "SELECT * FROM finanz_konten WHERE typ = 'ausgabenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage6 = mysqli_query($link, $Anfrage6);
    $Anzahl6 = mysqli_num_rows($Abfrage6);

    //Berechne Zahl nötiger Zeilen
    if($Anzahl5>$Anzahl6){
        $Runs = $Anzahl5;
    }elseif ($Anzahl5<$Anzahl6){
        $Runs = $Anzahl6;
    }else{
        $Runs = $Anzahl5;
    }

    $kontoItems = table_row_builder(table_header_builder('Ausgabenkonten').table_header_builder('Ausgaben').table_header_builder('Einnahmenkonten').table_header_builder('Einnahmen'));
    //Iterate over konten
    $AusgabenSumme=0.0;
    $EinnahmenSumme=0.0;
    for($a=1;$a<=$Runs;$a++){
        //Ausgabenkonto
        if($a<=$Anzahl6){
            $Ergebnis6 = mysqli_fetch_assoc($Abfrage6);
            $Ausgleiche = ausgleiche_konto($Ergebnis6['id'], $YearGlobal);
            $AusgabenKonto=0.0;
            foreach ($Ausgleiche as $Ausgleich){
                $AusgabenKonto = $AusgabenKonto + lade_gezahlte_betraege_ausgleich($Ausgleich['id']);
            }
            $AusgabenSumme = $AusgabenSumme + $AusgabenKonto;
            $ItemsAusgabenkonten = table_data_builder($Ergebnis6['name']).table_data_builder($AusgabenKonto.'&euro;');
        }else{
            $ItemsAusgabenkonten = table_data_builder('').table_data_builder('');
        }
        //Einnahmenkonto
        if($a<=$Anzahl5){
            $Ergebnis5 = mysqli_fetch_assoc($Abfrage5);
            $Forderungen = forderungen_konto($Ergebnis5['id'], $YearGlobal);
            $EinnahmenKonto=0.0;
            foreach ($Forderungen as $Forderung){
                $EinnahmenKonto = $EinnahmenKonto + lade_einnahmen_forderung($Forderung['id']);
            }
            $EinnahmenSumme = $EinnahmenSumme + $EinnahmenKonto;
            $ItemsEinnahmenkonten = table_data_builder($Ergebnis5['name']).table_data_builder($EinnahmenKonto.'&euro;');
        }else{
            $ItemsEinnahmenkonten = table_data_builder('').table_data_builder('');
        }
        $kontoItems .= table_row_builder($ItemsAusgabenkonten.$ItemsEinnahmenkonten);
    }
    $kontoItems .= table_row_builder(table_data_builder('').table_data_builder('Summe: '.$AusgabenSumme.'&euro;').table_data_builder('').table_data_builder('Summe: '.$EinnahmenSumme.'&euro;'));
    $Differenz = $EinnahmenSumme-$AusgabenSumme;

    $contentHTML = section_builder(table_builder($kontoItems));
    $contentHTML .= section_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back')).table_header_builder('Gewinn/Verlust: '.$Differenz.'&euro;'))));
    $contentHTML .= "<input type='hidden' name='year_global' value='".$_POST['year_global']."'>";

    $HTML = "<h3 class='center-align'>GUV-Rechnung ".$YearGlobal."</h3>";
    $HTML .= form_builder($contentHTML, '#', 'post');
    return $HTML;
}
function uebersicht_section_vereinskasse($YearGlobal){

    $Gesamteinnahmen = gesamteinnahmen_jahr($YearGlobal);
    $Gesamtausgaben = gesamtausgaben_jahr($YearGlobal);
    $Differenz = $Gesamteinnahmen - $Gesamtausgaben;
    if (floatval($Differenz) >= 0){
        $StyleGUV = "class=\"green lighten-2\"";
    } else {
        $StyleGUV = "class=\"red lighten-1\"";
    }

    $HTML = "<h3 class='center-align'>Jahresstatistik ".$YearGlobal."</h3>";
    $Table = table_row_builder(table_header_builder('Einnahmen').table_header_builder('Ausgaben').table_header_builder('Überschuss').table_header_builder(form_select_item('year_global', 2017, date('Y'), $_POST['year_global'], '', 'Betrachtungsjahr', '')));
    $Table .= table_row_builder(table_data_builder($Gesamteinnahmen."&euro;").table_data_builder($Gesamtausgaben."&euro;").table_data_builder("<p ".$StyleGUV.">".$Differenz."&euro;</p>").table_data_builder(form_button_builder('change_betrachtungsjahr', 'wechseln', 'action', 'send')));
    $HTML .= form_builder(table_builder($Table), '#', 'post', 'jahresstats');

    return section_builder($HTML);
}
function kontos_section_vereinskasse($YearGlobal, $Parser){

    $BigItems = '';
    $link = connect_db();

    //Einnahmenkonten
    $Anfrage5 = "SELECT * FROM finanz_konten WHERE typ = 'einnahmenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage5 = mysqli_query($link, $Anfrage5);
    $Anzahl5 = mysqli_num_rows($Abfrage5);
    $EinnahmenkontoCounter = 0;
    $EinnahmenkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Forderungen').table_header_builder('Einnahmen').table_header_builder('Differenz').table_header_builder('Aktionen'));
    for ($e = 1; $e <= $Anzahl5;$e++) {
        $Ergebnis5 = mysqli_fetch_assoc($Abfrage5);
        $Forderungen = forderungen_konto($Ergebnis5['id'], $YearGlobal);
        $ForderungenSumme = 0.0;
        $EinnahmenSumme = 0.0;
        foreach ($Forderungen as $Forderung){
            $ForderungenSumme = $ForderungenSumme + $Forderung['betrag'];
            $Einnahme = lade_einnahmen_forderung($Forderung['id']);
            $EinnahmenSumme = $EinnahmenSumme + $Einnahme;
        }
        $Differenz = $EinnahmenSumme - $ForderungenSumme;
        if (floatval($Differenz) >= 0){
            $StyleGUV = "class=\"green lighten-2\"";
        } else {
            $StyleGUV = "class=\"red lighten-1\"";
        }
        $Buttons = form_button_builder('konto_details_'.$Ergebnis5['id'].'', 'Details', 'action', 'search');
        $EinnahmenkontoItems .= table_row_builder(table_data_builder($Ergebnis5['name']).table_data_builder($ForderungenSumme.'&euro;').table_data_builder($EinnahmenSumme.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($Buttons));
        $EinnahmenkontoCounter++;
    }
    if ($EinnahmenkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Einnahmenkonten', table_builder($EinnahmenkontoItems), 'attach_money');
    } else{
        $BigItems .= collapsible_item_builder('Einnahmenkonten', 'Bislang keine Einnahmenkonten angelegt!', 'attach_money');
    }

    //Ausgabenkonten
    $Anfrage6 = "SELECT * FROM finanz_konten WHERE typ = 'ausgabenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage6 = mysqli_query($link, $Anfrage6);
    $Anzahl6 = mysqli_num_rows($Abfrage6);
    $AusgabenkontoCounter = 0;
    $AusgabenkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Geplant').table_header_builder('Ausgegeben').table_header_builder('Differenz').table_header_builder('Aktionen'));
    for ($f = 1; $f <= $Anzahl6;$f++) {
        $Ergebnis6 = mysqli_fetch_assoc($Abfrage6);
        $Ausgleiche = ausgleiche_konto($Ergebnis6['id'], $YearGlobal);
        $AUSgleichSumme = 0.0;
        $AusgabeSumme = 0.0;
        foreach ($Ausgleiche as $Ausgleich){
            $AUSgleichSumme = $AUSgleichSumme + $Ausgleich['betrag'];
            $Ausgabe = lade_ausgaben_ausgleich($Ausgleich['id']);
            $AusgabeSumme = $AusgabeSumme + $Ausgabe;
        }
        $Differenz = $AUSgleichSumme - $AusgabeSumme;
        if (floatval($Differenz) >= 0){
            $StyleGUV = "class=\"green lighten-2\"";
        } else {
            $StyleGUV = "class=\"red lighten-1\"";
        }
        $Buttons = form_button_builder('konto_details_'.$Ergebnis6['id'].'', 'Details', 'action', 'search');
        $AusgabenkontoItems .= table_row_builder(table_data_builder($Ergebnis6['name']).table_data_builder($AUSgleichSumme.'&euro;').table_data_builder($AusgabeSumme.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($Buttons));
        $AusgabenkontoCounter++;
    }
    if ($AusgabenkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Ausgabenkonten', table_builder($AusgabenkontoItems), 'money_off');
    } else{
        $BigItems .= collapsible_item_builder('Ausgabenkonten', 'Bislang keine Ausgabenkonten angelegt!', 'money_off');
    }

    //Neutralkonten
    $Anfrage7 = "SELECT * FROM finanz_konten WHERE typ = 'neutralkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage7 = mysqli_query($link, $Anfrage7);
    $Anzahl7 = mysqli_num_rows($Abfrage7);
    $NeutralkontoCounter = 0;
    $NeutralkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Aktueller Kontostand').table_header_builder('Aktionen'));
    for ($g = 1; $g <= $Anzahl7;$g++) {
        $Ergebnis7 = mysqli_fetch_assoc($Abfrage7);
        $Buttons = form_button_builder('konto_details_'.$Ergebnis7['id'].'', 'Details', 'action', 'search');
        $NeutralkontoItems .= table_row_builder(table_data_builder($Ergebnis7['name']).table_data_builder($Ergebnis7['wert_akt'].'&euro;').table_data_builder($Buttons));
        $NeutralkontoCounter++;
    }
    if ($NeutralkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Neutralkonten', table_builder($NeutralkontoItems), 'iso');
    } else{
        $BigItems .= collapsible_item_builder('Neutralkonten', 'Bislang keine Neutralkonten angelegt!', 'iso');
    }

    //Wartkonten
    $Users = get_sorted_user_array_with_user_meta_fields('nachname');
    $WartkontoCounter = 0;
    $WartkontoItems = table_row_builder(table_header_builder('Wart!n').table_header_builder('Einnahmen').table_header_builder('Ausgaben').table_header_builder('Überschuss').table_header_builder('Aktionen'));
    foreach ($Users as $User){
        if ($User['ist_wart'] == 'true') {
            $Konto = lade_konto_user($User['id']);
            $Einnahmen = gesamteinnahmen_jahr_konto($YearGlobal,$Konto['id']);
            $Ausgaben = gesamtausgaben_jahr_konto($YearGlobal,$Konto['id']);
            $Differenz = $Einnahmen-$Ausgaben;
            if (floatval($Differenz) >= 0){
                $StyleGUV = "class=\"green lighten-2\"";
            } else {
                $StyleGUV = "class=\"red lighten-1\"";
            }
            if($Parser['highlight_user']==$User['id']){
                $Highlight = 'class="blue lighten-2"';
            } else {
                $Highlight = '';
            }
            $Buttons = form_button_builder('konto_details_'.$Konto['id'].'', 'Details', 'action', 'search');
            #$AktionLinks = form_button_builder('highlight_user_actions_'.$User['id'].'', 'hervorheben', 'action', 'highlight');
            $WartkontoItems .= table_row_builder(table_data_builder('<p '.$Highlight.'>'.$User['vorname'].'&nbsp;'.$User['nachname'].'</p>').table_data_builder($Einnahmen.'&euro;').table_data_builder($Ausgaben.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($Buttons));
            $WartkontoCounter++;
        }
    }
    if ($WartkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Wartkonten', table_builder($WartkontoItems), 'android');
    } else{
        $BigItems .= collapsible_item_builder('Wartkonten', 'Bislang keine Wartkonten angelegt!', 'android');
    }

    $BigItems .= konto_anlegen_formular();
    $BigItems .= "<input type='hidden' name='year_global' value='".$_POST['year_global']."'>";

    $HTML = '<h3 class="center-align">Konten</h3>';
    $HTML .= form_builder(collapsible_builder($BigItems), '#', 'post');

    return section_builder($HTML);
}
function forderungen_section_vereinskasse($YearGlobal){

    $Forderungen = lade_alle_forderungen_jahr($YearGlobal);

    $TableResForderungen = table_row_builder(table_header_builder('#').table_header_builder('Res.-Infos').table_header_builder('User').table_header_builder('Betrag').table_header_builder('Einnahme').table_header_builder('Betrag').table_header_builder('Empfänger!n').table_header_builder('Differenz').table_header_builder('Aktionen'));
    $TableAndereForderungen = table_row_builder(table_header_builder('#').table_header_builder('Referenz').table_header_builder('User').table_header_builder('Betrag').table_header_builder('Aktionen').table_header_builder('Einnahme').table_header_builder('Betrag').table_header_builder('Empfänger!n').table_header_builder('Differenz').table_header_builder('Aktionen'));

    foreach ($Forderungen as $Forderung){
        $UserMeta = lade_user_meta($Forderung['von_user']);

        //Parse Einnahmen
        $Einnahmen = lade_einnahmen_forderung($Forderung['id'],true);
        $EinnahmeDatum = '';
        $EinnahmeBetrag = '';
        $EinnahmeWart = '';
        $EinnahmeSumme = 0.0;
        $EinnahmeAktions = '';
        foreach ($Einnahmen as $Einnahme){
            if($Einnahme['storno_user']>0){
                $sBegin="<s>";
                $sEnd="</s>";
                $EinnahmeAktions .= form_button_builder('einnahme_storno_aufheben_'.$Einnahme['id'].'', 'Aufheben', 'action', '')."<br>";
            }else{
                $sBegin="";
                $sEnd="";
                $EinnahmeSumme = $EinnahmeSumme + $Einnahme['betrag'];
                $EinnahmeAktions .= form_button_builder('einnahme_stornieren_'.$Einnahme['id'].'', 'Storno', 'action', '')."<br>";
            }
            $KontoEinnahme = lade_konto_via_id($Einnahme['konto_id']);
            if($KontoEinnahme['typ']=='wartkonto'){
                $WartMeta = lade_user_meta($KontoEinnahme['name']);
                $EinnahmeWart .= $sBegin.$WartMeta['vorname'].'&nbsp;'.$WartMeta['nachname'].$sEnd."<br>";
            }elseif ($KontoEinnahme['typ']=='neutralkonto'){
                $EinnahmeWart .= $sBegin.$KontoEinnahme['name'].$sEnd."<br>";
            }
            $EinnahmeDatum .= $sBegin.date("d.m.Y", strtotime($Einnahme['timestamp'])).$sEnd."<br>";
            $EinnahmeBetrag .= $sBegin.$Einnahme['betrag'].'&euro;'.$sEnd."<br>";
        }
        if(sizeof($Einnahmen)==0){
            $EinnahmeDatum = '-';
            $EinnahmeBetrag = '-';
            $EinnahmeWart = '-';
            $EinnahmeAktions = '-';
        }
        if(sizeof($Einnahmen)>1){
            $EinnahmeBetrag .= "----<br>".$EinnahmeSumme.'&euro;';
            $EinnahmeDatum .= "<br><br>";
            $EinnahmeWart .= "<br><br>";
        }

        $Differenz = $EinnahmeSumme - $Forderung['betrag'];
        if (floatval($Differenz) >= 0){
            $StyleGUV = "class=\"green lighten-2\"";
        } else {
            $StyleGUV = "class=\"red lighten-1\"";
        }

        if($Forderung['referenz_res']>0){       //Forderung betrifft ne reservierung
            if($Forderung['storno_user']>0) {
                $TableResForderungen .= table_row_builder(table_data_builder('<s>'.$Forderung['id'].'</s>') . table_data_builder('<s>'.$Forderung['referenz_res'].'</s>') . table_data_builder('<s>'.$UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname'].'</s>') . table_data_builder('<s>'.$Forderung['betrag'].'&euro;</s>') . table_data_builder($EinnahmeDatum) . table_data_builder($EinnahmeBetrag) . table_data_builder($EinnahmeWart) . table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>') . table_data_builder($EinnahmeAktions));
            }else{
                $TableResForderungen .= table_row_builder(table_data_builder($Forderung['id']) . table_data_builder($Forderung['referenz_res']) . table_data_builder($UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname']) . table_data_builder($Forderung['betrag'].'&euro;') . table_data_builder($EinnahmeDatum) . table_data_builder($EinnahmeBetrag) . table_data_builder($EinnahmeWart) . table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>') . table_data_builder($EinnahmeAktions));
            }
        } else {                                //Forderung betrifft was anderes
            if($Forderung['storno_user']>0) {
                $AktionButtonForderung = form_button_builder('undo_storno_forderung_'.$Forderung['id'].'', 'Reaktivieren', 'action', '');
                $TableAndereForderungen .= table_row_builder(table_data_builder('<s>'.$Forderung['id'].'</s>').table_data_builder('<s>'.$Forderung['referenz'].'</s>').table_data_builder('<s>'.$UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname'].'</s>').table_data_builder('<s>'.$Forderung['betrag'].'&euro;</s>').table_data_builder($AktionButtonForderung).table_data_builder($EinnahmeDatum).table_data_builder($EinnahmeBetrag).table_data_builder($EinnahmeWart).table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($EinnahmeAktions));
            } else {
                $AktionButtonForderung = form_button_builder('delete_forderung_'.$Forderung['id'].'', 'Stornieren', 'action', '');
                $TableAndereForderungen .= table_row_builder(table_data_builder($Forderung['id']).table_data_builder($Forderung['referenz']).table_data_builder($UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname']).table_data_builder($Forderung['betrag'].'&euro;').table_data_builder($AktionButtonForderung).table_data_builder($EinnahmeDatum).table_data_builder($EinnahmeBetrag).table_data_builder($EinnahmeWart).table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($EinnahmeAktions));
            }
        }
    }



    $ResFordContent = table_builder($TableResForderungen);
    $AndFordContent = table_builder($TableAndereForderungen);

    $Items = collapsible_item_builder('Forderungen aus Reservierungen', $ResFordContent, 'today');
    $Items .= collapsible_item_builder('Andere Forderungen', $AndFordContent, 'toll');

    $HTML = '<h3 class="center-align">Alle Forderungen '.$YearGlobal.'</h3>';
    $HTML .= section_builder(collapsible_builder($Items));
    $HTML .= section_builder(form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back'));

    return form_builder($HTML, '#', 'post', 'forderungen_section_form');
}
function ausgaben_section_vereinskasse($YearGlobal){
    $Ausgleiche = lade_alle_ausgleiche_jahr($YearGlobal);

    $TableResAusgleiche = table_row_builder(table_header_builder('#').table_header_builder('Res.-Infos').table_header_builder('User').table_header_builder('Betrag').table_header_builder('Ausgabe').table_header_builder('Betrag').table_header_builder('Zahler!n').table_header_builder('Differenz').table_header_builder('Aktionen'));
    $TableAndereAusgleiche = table_row_builder(table_header_builder('#').table_header_builder('Referenz').table_header_builder('Betrag').table_header_builder('Aktionen').table_header_builder('Ausgabe').table_header_builder('Betrag').table_header_builder('Zahler!n').table_header_builder('Differenz').table_header_builder('Aktionen'));

    foreach ($Ausgleiche as $Ausgleich){
        $UserMeta = lade_user_meta($Ausgleich['fuer_user']);

        //Parse Einnahmen
        $Ausgaben = lade_ausgaben_ausgleich($Ausgleich['id'],true);
        $AusgabeDatum = '';
        $AusgabeBetrag = '';
        $AusgabeWart = '';
        $AusgabeSumme = 0.0;
        $AusgabeAktions = '';
        foreach ($Ausgaben as $Ausgabe){
            if($Ausgabe['storno_user']>0){
                $sBegin="<s>";
                $sEnd="</s>";
                $AusgabeAktions .= form_button_builder('ausgabe_storno_aufheben_'.$Ausgabe['id'].'', 'Aufheben', 'action', '')."<br>";
            }else{
                $sBegin="";
                $sEnd="";
                $AusgabeSumme = $AusgabeSumme + $Ausgabe['betrag'];
                $AusgabeAktions .= form_button_builder('ausgabe_stornieren_'.$Ausgabe['id'].'', 'Storno', 'action', '')."<br>";
            }
            $KontoAusgabe = lade_konto_via_id($Ausgabe['konto_id']);
            if($KontoAusgabe['typ']=='wartkonto'){
                $WartMeta = lade_user_meta($KontoAusgabe['name']);
                $AusgabeWart .= $sBegin.$WartMeta['vorname'].'&nbsp;'.$WartMeta['nachname'].$sEnd."<br>";
            }elseif ($KontoAusgabe['typ']=='neutralkonto'){
                $AusgabeWart .= $sBegin.$KontoAusgabe['name'].$sEnd."<br>";
            }
            $AusgabeDatum .= $sBegin.date("d.m.Y", strtotime($Ausgabe['timestamp'])).$sEnd."<br>";
            $AusgabeBetrag .= $sBegin.$Ausgabe['betrag'].'&euro;'.$sEnd."<br>";
        }
        if(sizeof($Ausgaben)==0){
            $AusgabeDatum = '-';
            $AusgabeBetrag = '-';
            $AusgabeWart = '-';
            $AusgabeAktions = '-';
        }
        if(sizeof($Ausgaben)>1){
            $AusgabeBetrag .= "----<br>".$AusgabeSumme.'&euro;';
            $AusgabeDatum .= "<br><br>";
            $AusgabeWart .= "<br><br>";
        }

        $Differenz = $AusgabeSumme - $Ausgabe['betrag'];
        if (floatval($Differenz) >= 0){
            $StyleGUV = "class=\"green lighten-2\"";
        } else {
            $StyleGUV = "class=\"red lighten-1\"";
        }

        if($Ausgleich['referenz_res']>0){       //Forderung betrifft ne reservierung
            if($Ausgabe['storno_user']>0) {
                $TableResAusgleiche .= table_row_builder(table_data_builder('<s>'.$Ausgleich['id'].'</s>') . table_data_builder('<s>'.$Ausgleich['referenz_res'].'</s>') . table_data_builder('<s>'.$UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname'].'</s>') . table_data_builder('<s>'.$Ausgleich['betrag'].'&euro;</s>') . table_data_builder($AusgabeDatum) . table_data_builder($AusgabeBetrag) . table_data_builder($AusgabeWart) . table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>') . table_data_builder($AusgabeAktions));
            }else{
                $TableResAusgleiche .= table_row_builder(table_data_builder($Ausgleich['id']) . table_data_builder($Ausgleich['referenz_res']) . table_data_builder($UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname']) . table_data_builder($Ausgleich['betrag'].'&euro;') . table_data_builder($AusgabeDatum) . table_data_builder($AusgabeBetrag) . table_data_builder($AusgabeWart) . table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>') . table_data_builder($AusgabeAktions));
            }
        } else {                                //Forderung betrifft was anderes
            if($Ausgabe['storno_user']>0) {
                $AktionButtonForderung = form_button_builder('undo_storno_ausgleich_'.$Ausgleich['id'].'', 'Reaktivieren', 'action', '');
                $TableAndereAusgleiche .= table_row_builder(table_data_builder('<s>'.$Ausgleich['id'].'</s>').table_data_builder('<s>'.$Ausgleich['referenz'].'</s>').table_data_builder('<s>'.$UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname'].'</s>').table_data_builder('<s>'.$Ausgleich['betrag'].'&euro;</s>').table_data_builder($AktionButtonForderung).table_data_builder($AusgabeDatum).table_data_builder($AusgabeBetrag).table_data_builder($AusgabeWart).table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($AusgabeAktions));
            } else {
                $AktionButtonForderung = form_button_builder('delete_ausgleich_'.$Ausgleich['id'].'', 'Stornieren', 'action', '');
                $TableAndereAusgleiche .= table_row_builder(table_data_builder($Ausgleich['id']).table_data_builder($Ausgleich['referenz']).table_data_builder($UserMeta['vorname'].'&nbsp;'.$UserMeta['nachname']).table_data_builder($Ausgleich['betrag'].'&euro;').table_data_builder($AktionButtonForderung).table_data_builder($AusgabeDatum).table_data_builder($AusgabeBetrag).table_data_builder($AusgabeWart).table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($AusgabeAktions));
            }
        }
    }



    $ResFordContent = table_builder($TableResAusgleiche);
    $AndFordContent = table_builder($TableAndereAusgleiche);

    $Items = collapsible_item_builder('Auszahlungen zu Reservierungen', $ResFordContent, 'today');
    $Items .= collapsible_item_builder('Andere Ausgaben', $AndFordContent, 'toll');

    $HTML = '<h3 class="center-align">Alle Ausgaben '.$YearGlobal.'</h3>';
    $HTML .= section_builder(collapsible_builder($Items));
    $HTML .= section_builder(form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back'));

    return form_builder($HTML, '#', 'post', 'ausgleiche_section_form');
}
function konto_details($YearGlobal, $Konto){
    $HTML = form_builder(form_button_builder('reset_view', 'Zurück', 'action', 'arrow_back'), '#', 'post');
    return $HTML;
}
function konto_anlegen_formular(){

    $Table = table_form_string_item('Kontoname', 'new_konto_name', $_POST['new_konto_name']);
    $Table .= table_row_builder(table_header_builder('Kontotyp').table_data_builder(dropdown_kontotyp_waehlen('new_konto_typ', $_POST['new_konto_typ'])));
    $Table .= table_form_string_item('Anfangswert', 'new_konto_initial', $_POST['new_konto_initial']);
    $Table .= table_row_builder(table_header_builder(form_button_builder('action_add_konto', 'Anlegen', 'action', 'send')).table_data_builder(''));
    $Table = table_builder($Table);
    return collapsible_item_builder('Konto anlegen', $Table, 'add_new');
}
function add_transaktions_vereinskasse(){
    $BigItems = ausgleich_anlegen_formular();
    $BigItems .= ausgabe_eintragen_formular();
    $BigItems .= forderung_anlegen_formular();
    $BigItems .= einnahmen_eintragen_formular();
    $BigItems .= umbuchen_formular();
    $HTML = '<h3 class="center-align">Transaktionen durchführen</h3>';
    $HTML .= form_builder(collapsible_builder($BigItems), '#', 'post');

    return section_builder($HTML);
}
function umbuchen_formular(){
    $Text = '';
    return collapsible_item_builder('Umbuchung eintragen', $Text, 'swap_horiz');
}
function einnahmen_eintragen_formular(){
    $Text = '';
    return collapsible_item_builder('Einnahme eintragen', $Text, 'toll');
}
function forderung_anlegen_formular(){
    $Text = '';
    return collapsible_item_builder('Forderung anlegen', $Text, 'playlist_add');
}
function ausgabe_eintragen_formular(){
    $Text = '';
    return collapsible_item_builder('Geldausgabe eintragen', $Text, 'payment');
}
function ausgleich_anlegen_formular(){
    $Text = '';
    return collapsible_item_builder('Ausgabe planen', $Text, 'playlist_add');
}
function choose_views_vereinskasse(){
    $HTML = "<h3 class='center-align'>Weitere Ansichten</h3>";
    $HTML .= form_builder(table_builder(table_row_builder(table_header_builder(form_button_builder('activate_guv', 'GUV-Rechnung', 'action', 'iso', '')).table_header_builder(form_button_builder('activate_list_all_ausgaben', 'Alle Ausgaben', 'action', 'money_off', '')).table_header_builder(form_button_builder('activate_list_all_forderungen', 'Alle Forderungen', 'action', 'attach_money', ''))))."<input type='hidden' name='year_global' value='".$_POST['year_global']."'>", '#', 'post', 'view_changer_form');
    return section_builder($HTML);
}