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
include_once "./mail_generation_functions/mail_senden.php";
include_once "./mail_generation_functions/mailvorlagen.php";
include_once "./phpmailer/PHPMailer.php";
include_once "./phpmailer/Exception.php";
include_once "./forgot_password_functions/forgot_password_functions.php";
include_once "./datenschutzerklaerungen/datenschutzerklaerungen.php";
include_once "./kalender_functions/kalender.php";
