<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.06.18
 * Time: 20:46
 */

include_once "./website_skeleton_functions/website_skeleton.php";              //Enthält alle funktionen, die die Grundbausteine einer Seite generieren (Header, NavBar, Footer, etc.)
include_once "./website_skeleton_functions/startseite_inhalte.php";
include_once "./website_skeleton_functions/bausteine.php";
include_once "./database_functions/datenbank.php";
include_once "./settings_functions/settings.php";
include_once "./time_functions/timestamp.php";
include_once "./time_functions/zeit.php";
include_once "./login_functions/login_system_parts.php";
include_once "./user_functions/user.php";
include_once "./log_functions/protocol.php";
include_once "./helper_view_functions/helper_view_functions.php";
include_once "./task_force_view_functions/task_force_view_functions.php";
include_once "./qualification_functions/qualification_functions.php";
include_once "./csv_import_functions/csv_upload.php";
include_once "./mail_generation_functions/mail_senden.php";
include_once "./mail_generation_functions/mailvorlagen.php";
include_once "./phpmailer/PHPMailer.php";
include_once "./phpmailer/Exception.php";
include_once "./mission_functions/mission_manage.php";
include_once "./location_functions/location_functions.php";
include_once "./aufenthaltsorte_functions/aufenthaltsorte_functions.php";
include_once "./helpers_functions/helfersuche_params.php";
include_once "./helpers_functions/helfersuche_queries.php";
include_once "./einsatzkategorie_functions/einsatzkategorie_functions.php";
include_once "./info_fuer_stelle_functions/info_fuer_stelle_functions.php";
include_once "./forgot_password_functions/forgot_password_functions.php";
include_once "./taskforce_edit_user_functions/taskforce_edit_user_functions.php";
include_once "./datenschutzerklaerungen/datenschutzerklaerungen.php";
include_once "./einteilungen_functions/einteilungen_edit_view.php";
include_once "./einteilungen_functions/einteilungen_list_view.php";
include_once "./einteilungen_functions/einteilungen_queries.php";
include_once "./einteilungen_functions/einteilungen_params.php";
