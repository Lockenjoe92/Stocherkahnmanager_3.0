<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.06.18
 * Time: 20:27
 */

include_once "./ressourcen.php";

function site_header($PageTitle, $LoginCheckActive=Null){

    #Redirect wenn Login erforderlich und login nicht erfolgt
    if($LoginCheckActive == True){
        return null;
    }

    #Initialize HTML
    $HTML = '<!DOCTYPE html>';
    $HTML .= '<html lang="de">';

    #Initialize header
    $HTML .= '<head>';

    #Meta infos
    $HTML .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    $HTML .= '<meta name="viewport" content="width=device-width, initial-scale=1"/>';

    #Page Title
    if(!isset($PageTitle)){$PageTitle = lade_xml_einstellung('site_name');}
    $HTML .= '<title>'.$PageTitle.'</title>';

    #CSS
    $HTML .= '  <!-- CSS  -->';
    $HTML .= '<link href="/materialize/css/materialize.min.css" type="text/css" rel="stylesheet" media="screen,projection"/>';
    $HTML .= '<link href="/materialize/css/style.css" type="text/css" rel="stylesheet" media="screen,projection"/>';

    #End header
    $HTML .= '</head>';

    return $HTML;
}

function site_body($BodyHTMLcontent, $sessionmanager = null){

    # Initialize body
    $HTML = '<!--  body  -->';
    $HTML .= '<body>';

    # Add navbar
    $HTML .= site_navbar($sessionmanager);

    # Add content
    $HTML .= $BodyHTMLcontent;

    # Add footer
    $HTML .= site_footer();

    # Run skripts
    $HTML .= site_skripts();

    # Close body
    $HTML .= '</body>';

    # End html
    $HTML .= '</html>';

    return $HTML;
}

function site_skripts(){

    $HTML = '  <!--  Scripts-->';
    $HTML .= "<script src='/einteilungen_functions/einteilung_edit.js'></script>";
    $HTML .= '<script src="/materialize/js/jquery.min.js"></script>';
    $HTML .= '<script src="/materialize/js/materialize.js"></script>';
    $HTML .= '<script src="/materialize/js/sisyphus.min.js"></script>';
    $HTML .= '<script src="/materialize/js/main.js"></script>';

    return $HTML;
}

function site_footer(){

    # Initialize Footer
    $HTML .= '<!--  footer-->';
    $HTML .= '<footer class="page-footer '.lade_xml_einstellung('site_footer_color').'">';
    $HTML .= footer_container();

    #Close footer
    $HTML .= '</footer>';

    return $HTML;
}

function footer_container(){

    #Initialize container
    $HTML = '  <!--  footer container -->';
    $HTML_cookie = '<div class="cookie-notice">';
    $HTML_cookie .= row_builder(center_builder(lade_xml_einstellung('cookie_notice'),''));
    $HTML_cookie .= '</div>';
    $HTML .= container_builder(section_builder($HTML_cookie),'','sticky-bottom');


    # Display big Footer if so chosen
    if (lade_xml_einstellung('display_big_footer') == 'on'){
        $HTML .= footer_content_left_column();
        $HTML .= footer_content_right_column();
        $HTML = row_builder($HTML, 'big_footer_row');
        $HTML = container_builder($HTML, 'big_footer_container');
    }

    # Copyright
    $HTML .= footer_content_copyright();

    return $HTML;
}

function footer_content_right_column(){

    # Initialize container
    $HTML = '  <!--  content footer about -->';
    $HTML .= '<div class="col l3 s12">';

    # Title
    $HTML .= lade_xml_einstellung('big_footer_right_column_html');

    # Close container
    $HTML .= '</div>';

    return $HTML;

}

function footer_content_left_column(){

    # Initialize column
    $HTML = '  <!--  content footer connect -->';
    $HTML .= '<div class="col l6 s12">';

    # Content
    $HTML .= lade_xml_einstellung('big_footer_left_column_html');

    # Close column
    $HTML .= '</div>';

    return $HTML;

}

function footer_content_copyright(){

    # Initialize copyright div
    $HTML = '  <!--  content footer copyright -->';
    $HTML .= '<div class="footer-copyright">';

    # Open copyright container
    $HTML .= '<div class="container">';
    $HTML .= lade_xml_einstellung('site_footer_name');
    $HTML .= '</div>';

    # Close copyright div
    $HTML .= '</div>';

    return $HTML;
}

function site_navbar($sessionmanager){

    $HTML = '<!--  navbar    -->';

    $HTML .= "<ul id='dropdown1' class='dropdown-content'>";
    $HTML .= '<li><a href="./index.php">Startseite</a></li>';
    $HTML .= "</ul>";

    $HTML .= '<nav class="'.lade_xml_einstellung('site_menue_color').'" role="navigation">';
    $HTML .= '<div class="nav-wrapper container" style="width:85%;"'.lade_xml_einstellung('site_menue_color').'">';
    $HTML .= '<span class="hide-on-small-only">';
    $HTML .= navbar_links_big($sessionmanager);
    $HTML .= '</span>';
    $HTML .= '<span class="hide-on-med-and-up">';
    $HTML .= navbar_links_mobile($sessionmanager);
    $HTML .= '</span>';
    $HTML .= '</div>';
    $HTML .= '</nav>';

    return $HTML;
}

function navbar_links_big($sessionmanager){

    if (($sessionmanager == null) OR ($sessionmanager == false)){
        $HTML = '<a id="logo-container" href="./index.php" class="brand-logo  show-on-large hide-on-med-and-down left">'.lade_xml_einstellung('site_name').'</a>';
        $HTML .= '<a id="logo-container" href="./index.php" class="brand-logo  show-medium-and-down hide-on-large-only left">'.lade_xml_einstellung('site_name_mobile').'</a>';       
        $HTML .= '<ul class="right">';
        $HTML .= '<li><a href="./index.php">Informationen</a></li>';
        $HTML .= '<li><a href="./login.php">Login</a></li>';
        $HTML .= '</ul>';
    } elseif ($sessionmanager == true){

        $UserMeta = lade_user_meta(lade_user_id());

        if($UserMeta['ist_taskforce'] == 'true'){
            $HTML = '<a id="logo-container left" href="./taskforce_main_view.php" class="brand-logo">'.lade_xml_einstellung('site_name').'</a>';
            $HTML .= '<ul class="right">';
            $HTML .= '<li><a href="./logout.php">Logout</a></li>';
            $HTML .= '</ul>';
        } else {
            $HTML = '<a id="logo-container left" href="./helper_view.php" class="brand-logo">'.lade_xml_einstellung('site_name').'</a>';
            $HTML .= '<ul class="right">';
            $HTML .= '<li><a href="./logout.php">Logout</a></li>';
            $HTML .= '</ul>';
        }
    }

    return $HTML;
}

function navbar_links_mobile($sessionmanager){

    if (($sessionmanager == null) OR ($sessionmanager == false)) {
        $HTML = '<ul id="nav-mobile" class="sidenav ' . lade_xml_einstellung('site_menue_color') . '">';
        $HTML .= '<li><a href="./index.php">Informationen</a></li>';
        $HTML .= '<li><a href="./login.php">Login</a></li>';
        $HTML .= '</ul>';
        $HTML .= '<a href="./index.php" data-target="nav-mobile" class="sidenav-trigger"><i class="material-icons">menu</i></a>';
    } elseif ($sessionmanager == true){

        $UserMeta = lade_user_meta(lade_user_id());

        if($UserMeta['ist_taskforce'] == 'true'){
            $HTML = '<ul id="nav-mobile" class="sidenav ' . lade_xml_einstellung('site_menue_color') . '">';
            $HTML .= '<li><a href="./logout.php">Logout</a></li>';
            $HTML .= '</ul>';
            $HTML .= '<a href="./taskforce_main_view.php" data-target="nav-mobile" class="sidenav-trigger"><i class="material-icons">menu</i></a>';
        } else {
            $HTML = '<ul id="nav-mobile" class="sidenav ' . lade_xml_einstellung('site_menue_color') . '">';
            $HTML .= '<li><a href="./logout.php">Logout</a></li>';
            $HTML .= '</ul>';
            $HTML .= '<a href="./helper_view.php" data-target="nav-mobile" class="sidenav-trigger"><i class="material-icons">menu</i></a>';
        }
    }


    return $HTML;
}
