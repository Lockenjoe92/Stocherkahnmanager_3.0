<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Usermanagement - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Usermanagement</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Usermanagement</h1>';
$HTML .= section_builder($PageTitle);

# Eigene Reservierungen Normalo-user
$HTML .= seiteninhalt_liste_user('nachname');

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function seiteninhalt_liste_user($Sortierung){

    $HTML = "";

    //Liste generieren
    $UserIDchosen = $_GET['user'];
    if ($UserIDchosen != ""){
        $UserMetaToast = lade_user_meta($UserIDchosen);
        $HTML .= "<h5 class='center-align'>Um die Kontaktdaten von ".$UserMetaToast['vorname']." ".$UserMetaToast['nachname']." zu sehen, musst du einfach runter scrollen:)</h5>";
    }

    $AllUsers = get_sorted_user_array_with_user_meta_fields($Sortierung);
    $ListHTML = "";
    foreach ($AllUsers as $User){
        $ListHTML .= listenobjekt_user_generieren($User, $UserIDchosen);
    }

    $HTML .= "<h5 class='header hide-on-med-and-down center-align'>".count($AllUsers)." Aktive Nutzeraccounts</h5>";
    $HTML .= "<h5 class='header hide-on-large-only center-align'>".count($AllUsers)." Nutzeraccounts</h5>";
    $HTML .= collapsible_builder($ListHTML);

    return $HTML;
}

function listenobjekt_user_generieren($UserID, $UserIDchosen){

    $UserMeta = $UserID;
    $HTML = "";
    zeitformat();

    if ($UserID == $UserIDchosen){
        $Active = " active";
    }

    $AnzahleRes = count(lade_alle_reservierungen_eines_users($UserMeta['id']));

    //Registrierungsdatum
    $Registrierungsdatum = strftime("%A, %d. %B %G", strtotime($UserMeta['registrierung']));

    $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header".$Active."'><i class='large material-icons'>perm_identity</i>".$UserMeta['vorname']." ".$UserMeta['nachname']."</div>";
        $HTML .= "<div class='collapsible-body'>";
            $HTML .= "<div class='container'>";
                $HTML .= "<form method='post'>";
                    $HTML .= "<ul class='collection'>";

                    if(isset($UserMeta['telefon'])){
                        $HTML .= "<li class='collection-item'><i class='tiny material-icons'>phone</i> <a href='tel:".$UserMeta['telefon']."'>".$UserMeta['telefon']."</a></li>";
                    }
                        $HTML .= "<li class='collection-item'><i class='tiny material-icons'>email</i> <a href='mailto:".$UserMeta['mail']."'>".$UserMeta['mail']."</a></li>";
                        $HTML .= "<li class='collection-item'>Reservierungen dieses Jahr: ".$AnzahleRes."</li>";
                        $HTML .= "<li class='collection-item'>Registrierung: ".$Registrierungsdatum."</li>";
                    $HTML .= "</ul>";
                $HTML .= "</form>";
            $HTML .= "</div>";
        $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;

}

?>