<?php

include_once "./ressourcen/ressourcen.php";

session_manager();
$link = connect_db();

$Typ = $_GET['typ'];
$ID = $_GET['id'];

//Überprüfen ob der User auch das recht hat auf die Seite zuzugreifen
$Benutzerrollen = benutzerrollen_laden('');
if (!$Benutzerrollen['wart'] == true){
    header("Location: ./wartwesen.php");
    die();
}

//DAU Typ & ID abfangen
if (!isset($Typ)){
    $Typ = $_POST['typ'];
} else {
    $Typ = $_GET['typ'];
}
if (!isset($ID)){
    $ID = $_POST['id'];
} else {
    $ID = $_GET['id'];
}
if ($Typ === "pause"){
    $Modename = "Betriebspause";

    //Daten des Ausfalls laden
    $Anfrage = "SELECT * FROM pausen WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Daten = mysqli_fetch_assoc($Abfrage);
} else if ($Typ === "sperrung"){
    $Modename = "Sperrung";

    //Daten des Ausfalls laden
    $Anfrage = "SELECT * FROM sperrungen WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Daten = mysqli_fetch_assoc($Abfrage);
} else {
    //Fuck them
    header("Location: ./wartwesen.php");
    die();
}

//SEITE Initiieren
echo "<html>";
header_generieren("Kahnausfall l&ouml;schen");
echo "<body>";
navbar_generieren($Benutzerrollen, TRUE, 'ausfall_loeschen');
echo "<main>";

echo "<div class='container'>";
echo "<div class='row'>";
echo "<div class='col s12 m9 l12'>";

//Einstellungen laden
$Farbe = lade_einstellung('farbemenucard');
$FarbeText = lade_einstellung('farbemenutext');

//PARSER
$Parser = parser($Typ, $ID);

//SEITENINHALT
echo "<div class='section'><h3 class='center-align'>".$Modename." l&ouml;schen</h3></div>";

if ($Parser['success'] === TRUE){

    echo "<form action='ausfall_loeschen.php' method='post'>";
    echo "<div class='row'>";
    echo "<div class='col s12 m10 l8 offset-l2 offset-m1'>";
        echo "<div class=\"card " .$Farbe. "\">";
            echo "<div class=\"card-content ".$FarbeText."\">";
                echo "<span class=\"card-title\">Erfolg!</span>";
                echo "<div class='section'>";
                    echo "<p>Die ".$Modename." wurde erfolgreich gel&ouml;scht!</p>";
                echo "</div>";
                echo "<div class='section'>";
                    echo "<div class='input-field'>
                            <a href='ausfaelle.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a>
                            </div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</form>";

} else if ($Parser['success'] === FALSE){

    echo "<form action='ausfall_loeschen.php' method='post'>";
    echo "<div class='row'>";
    echo "<div class='col s12 m10 l8 offset-l2 offset-m1'>";
        echo "<div class=\"card " .$Farbe. "\">";
            echo "<div class=\"card-content ".$FarbeText."\">";
                echo "<span class=\"card-title\">Fehler</span>";
                echo "<div class='section'>";
                    echo "<p>Fehler beim L&ouml;schen der ".$Modename."!<br>".$Parser['meldung']."</p>";
                echo "</div>";
                echo "<div class='section'>";
                echo "<div class='input-field'>
                      <a href='ausfaelle.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a>
                      </div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</form>";

} else if ($Parser['success'] === NULL){

    echo "<form action='ausfall_loeschen.php' method='post'>";
    echo "<div class='row'>";
    echo "<div class='col s12 m10 l8 offset-l2 offset-m1'>";
        echo "<div class=\"card " .$Farbe. "\">";
            echo "<div class=\"card-content ".$FarbeText."\">";
                echo "<span class=\"card-title\">Achtung!</span>";
                echo "<div class='section'>";
                    echo "<input type='hidden' name='id' value='$ID'>";
                    echo "<input type='hidden' name='typ' value='$Typ'>";
                    echo "<p>M&ouml;chtest du die ".$Modename." '".$Daten['titel']."' wirklich l&ouml;schen?</p>";
                echo "</div>";
                echo "<div class='section'>";
                    echo "<div class='input-field'>
                                        <button class='btn waves-effect waves-light' type='submit' name='action'>L&ouml;schen
                                        <i class='material-icons left'>delete</i>
                                        </button>
                                        <a href='ausfaelle.php' class='btn waves-effect waves-light'>Abbruch</a>
                     </div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</form>";
}

echo "</div>";
echo "</div>";
echo "</div>";

echo "</main>";
footer_generieren();
echo "</body>";
echo "</html>";

function parser($Typ, $ID){

    $User = lade_user_id();
    $Ergebnis = NULL;

    if (isset($_POST['action'])){
        if ($Typ === "pause"){
            $Ergebnis = pause_stornieren($ID, $User);
        } else if ($Typ === "sperrung"){
            $Ergebnis = sperrung_stornieren($ID, $User);
        }
    }

    return $Ergebnis;
}
?>