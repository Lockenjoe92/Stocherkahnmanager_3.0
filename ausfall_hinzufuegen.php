<?php

    include_once "./ressourcen/ressourcen.php";

    session_manager();
    $link = connect_db();

    //Überprüfen ob der User auch das recht hat auf die Seite zuzugreifen
    $Benutzerrollen = benutzerrollen_laden('');
    if (!$Benutzerrollen['wart'] == true){
        header("Location: ./wartwesen.php");
        die();
    }

    //DAU Seitenmodus
    if (isset($_GET['typ'])){
        $TypURL = $_GET['typ'];
    } else {
        $TypURL = $_POST['typ'];
    }

    if ($TypURL === "pause"){
        $Modename = "Betriebspause";
    } else if ($TypURL === "sperrung"){
        $Modename = "Sperrung";
    } else {
        //Fuck them
        header("Location: ./wartwesen.php");
        die();
    }

    //SEITE Initiieren
    echo "<html>";
    header_generieren("Kahnausfall hinzuf&uuml;gen");
    echo "<body>";
    navbar_generieren($Benutzerrollen, TRUE, 'ausfall_hinzufuegen');
    echo "<main>";

    echo "<div class='section'><h3 class='center-align'>Neue ".$Modename." hinzuf&uuml;gen</h3></div>";

    echo "<div class='container'>";
    echo "<div class='row'>";
    echo "<div class='col s12 m9 l12'>";

        //Parser
        $Buttonmode = parser($TypURL, $Modename);

        //Formular Anzeigen
        $Von = "".$_POST['jahr_von']."-".$_POST['monat_von']."-".$_POST['tag_von']." ".$_POST['stunde_von'].":00:00";
        $Bis = "".$_POST['jahr_bis']."-".$_POST['monat_bis']."-".$_POST['tag_bis']." ".$_POST['stunde_bis'].":00:00";
        formular($_POST['typus'], $_POST['titel'], $_POST['erklaerung'], $Von, $Bis, $Modename, $Buttonmode, $TypURL);

    echo "</div>";
    echo "</div>";
    echo "</div>";

    echo "</main>";
    footer_generieren();
    echo "</body>";
    echo "</html>";

    function formular($Typ, $Titel, $Erklaerung, $Von, $Bis, $Modename, $Buttonmode, $TypURL){

        $Farbe = lade_einstellung('farbemenucard');
        $FarbeText = lade_einstellung('farbemenutext');

        if (empty($Von)){
            $JahrVon = NULL;
            $MonatVon = NULL;
            $TagVon = NULL;
            $StundeVon = NULL;

        } else {
            $DatumVon = strtotime($Von);
            $JahrVon = date("Y", $DatumVon);
            $MonatVon = date("m", $DatumVon);
            $TagVon = date("d", $DatumVon);
            $StundeVon = date("G", $DatumVon);
        }

        if (empty($Bis)){
            $JahrBis = NULL;
            $MonatBis = NULL;
            $TagBis = NULL;
            $StundeBis = NULL;

        } else {
            $DatumBis = strtotime($Bis);
            $JahrBis = date("Y", $DatumBis);
            $MonatBis = date("m", $DatumBis);
            $TagBis = date("d", $DatumBis);
            $StundeBis = date("G", $DatumBis);
        }

        echo "<form action='ausfall_hinzufuegen.php' method='post'>";
        echo "<div class='row'>";
            echo "<div class='col s12 m10 l8 offset-l2 offset-m1'>";
                echo "<div class=\"card " .$Farbe. "\">";
                    echo "<div class=\"card-content ".$FarbeText."\">";

                        if (($Buttonmode == NULL) OR ($Buttonmode === "error")){

                            echo "<span class=\"card-title\">".$Modename." hinzuf&uuml;gen</span>";

                            echo "<div class='section'>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">description</i>
                            <input id=\"titel\" name='titel' type=\"text\" length='25' class=\"validate ".$FarbeText."\" value='" .$Titel. "'>
                            <input name='typ' type='hidden' value='$TypURL'>
                            <label for=\"titel\">Titel der ".$Modename."</label>
                            </div>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">class</i>
                            <input id=\"typus\" name='typus' type=\"text\" length='25' class=\"validate ".$FarbeText."\" value='" .$Typ. "'>
                            <label for=\"typus\">Typ der ".$Modename."</label>
                            </div>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">schedule</i>
                            ".dropdown_jahre('jahr_von', $JahrVon, TRUE)." ".dropdown_monate('monat_von', $MonatVon)." ".dropdown_tage('tag_von', $TagVon)." ".dropdown_stunden('stunde_von', $StundeVon)."
                            <label for=\"jahr_von\">Beginn der ".$Modename."</label>
                            </div>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">schedule</i>
                            ".dropdown_jahre('jahr_bis',$JahrBis, TRUE)." ".dropdown_monate('monat_bis', $MonatBis)." ".dropdown_tage('tag_bis', $TagBis)." ".dropdown_stunden('stunde_bis', $StundeBis)."
                            <label for=\"jahr_bis\">Ende der ".$Modename."</label>
                            </div>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">info_outline</i>
                            <textarea id=\"erklaerung\" name='erklaerung' class=\"materialize-textarea ".$FarbeText."\">".$Erklaerung."</textarea>
                            <label for=\"erklaerung\">Erkl&auml;rungstext</label>
                            </div>";

                            echo "</div>";
                            echo "<div class='section'>";

                            echo "<div class='input-field'>
                            <button class='btn waves-effect waves-light' type='submit' name='action'>Eintragen
                            <i class='material-icons left'>send</i>
                            </button>
                            <a href='ausfaelle.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a>
                            </div>
                            </div>";


                        } else if ($Buttonmode === "override"){

                            echo "<span class=\"card-title\">".$Modename." hinzuf&uuml;gen</span>";

                            echo "<div class='section'>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">description</i>
                            <input id=\"titel\" name='titel' type=\"text\" length='25' class=\"validate ".$FarbeText."\" value='" .$Titel. "'>
                            <input name='typ' type='hidden' value='$TypURL'>
                            <label for=\"titel\">Titel der ".$Modename."</label>
                            </div>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">class</i>
                            <input id=\"typus\" name='typus' type=\"text\" length='25' class=\"validate ".$FarbeText."\" value='" .$Typ. "'>
                            <label for=\"typus\">Typ der ".$Modename."</label>
                            </div>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">schedule</i>
                            ".dropdown_jahre('jahr_von', $JahrVon, TRUE)." ".dropdown_monate('monat_von', $MonatVon)." ".dropdown_tage('tag_von', $TagVon)." ".dropdown_stunden('stunde_von', $StundeVon)."
                            <label for=\"jahr_von\">Beginn der ".$Modename."</label>
                            </div>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">schedule</i>
                            ".dropdown_jahre('jahr_bis',$JahrBis, TRUE)." ".dropdown_monate('monat_bis', $MonatBis)." ".dropdown_tage('tag_bis', $TagBis)." ".dropdown_stunden('stunde_bis', $StundeBis)."
                            <label for=\"jahr_bis\">Ende der ".$Modename."</label>
                            </div>";

                            echo "<div class=\"input-field\">
                            <i class=\"material-icons prefix\">info_outline</i>
                            <textarea id=\"erklaerung\" name='erklaerung' class=\"materialize-textarea ".$FarbeText."\">".$Erklaerung."</textarea>
                            <label for=\"erklaerung\">Erkl&auml;rungstext</label>
                            </div>";

                            echo "</div>";
                            echo "<div class='section'>";

                            echo "<div class='input-field'>
                            <button class='btn waves-effect waves-light' type='submit' name='override'>Trotzdem eintragen
                            <i class='material-icons left'>send</i>
                            </button>
                            <a href='ausfaelle.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a>
                            </div>
                            </div>";

                        } else if ($Buttonmode === "success"){
                            echo "<div class='section'>";
                            echo "<a href='ausfaelle.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a></div>";
                        }

                      echo "</div>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
        echo "</form>";
    }

    function parser($Modus, $Modename){

        $Farbe = lade_einstellung('farbemenucard');
        $FarbeText = lade_einstellung('farbemenutext');

        $Antwort = NULL;

        if (isset($_POST['action'])){

            echo "<div class='row'>";
            echo "<div class='col s12 m10 l8 offset-l2 offset-m1'>";
            echo "<div class=\"card " .$Farbe. "\">";
            echo "<div class=\"card-content ".$FarbeText."\">";
            echo "<div class='section'>";

            //DAU Check
            $Titel = $_POST['titel'];
            $Typ = $_POST['typus'];
            $Erklaerung = $_POST['erklaerung'];
            $Von = "".$_POST['jahr_von']."-".$_POST['monat_von']."-".$_POST['tag_von']." ".$_POST['stunde_von'].":00:00";
            $Bis = "".$_POST['jahr_bis']."-".$_POST['monat_bis']."-".$_POST['tag_bis']." ".$_POST['stunde_bis'].":00:00";

            if ($Modus == "pause"){
                $Eintrag = pause_anlegen($Von, $Bis, $Typ, $Titel, $Erklaerung, lade_user_id(), FALSE);
            } else if ($Modus == "sperrung"){
                $Eintrag = sperrung_anlegen($Von, $Bis, $Typ, $Titel, $Erklaerung, lade_user_id(), FALSE);
            }

            if ($Eintrag['erfolg'] == FALSE){

                if ($Eintrag['reservierungen_betroffen'] > 0){

                    //Text generieren
                    if ($Eintrag['reservierungen_betroffen'] == 1){
                        $ReservierungText = "ist eine Reservierung";
                    } else if ($Eintrag['reservierungen_betroffen'] > 1){
                        $ReservierungText = "sind ".$Eintrag['reservierungen_betroffen']." Reservierungen";
                    }

                    $Antwort = "override";
                    echo "<h5>Achtung!</h5><br>Von dieser ".$Modename." ".$ReservierungText." betroffen!<br>Bitte l&ouml;se den Vorgang erneut aus um die Pause trotzdem einzutragen - betroffene Nutzer werden dann per Mail oder SMS benachrichtigt.";
                } else {
                    $Antwort = "error";
                    echo "<h5>Fehler!</h5><br>".$Eintrag['meldung']."";
                }
            } else if ($Eintrag['erfolg'] == TRUE){
                $Antwort = "success";
                echo "<h5>Erfolg!</h5><br>".$Eintrag['meldung']."";
            }

            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }

        if (isset($_POST['override'])){

            echo "<div class='row'>";
            echo "<div class='col s12 m10 l8 offset-l2 offset-m1'>";
            echo "<div class=\"card " .$Farbe. "\">";
            echo "<div class=\"card-content ".$FarbeText."\">";
            echo "<div class='section'>";

            //DAU Check
            $Titel = $_POST['titel'];
            $Typ = $_POST['typus'];
            $Erklaerung = $_POST['erklaerung'];
            $Von = "".$_POST['jahr_von']."-".$_POST['monat_von']."-".$_POST['tag_von']." ".$_POST['stunde_von'].":00:00";
            $Bis = "".$_POST['jahr_bis']."-".$_POST['monat_bis']."-".$_POST['tag_bis']." ".$_POST['stunde_bis'].":00:00";

            if ($Modus == "pause"){
                $Eintrag = pause_anlegen($Von, $Bis, $Typ, $Titel, $Erklaerung, lade_user_id(), TRUE);
            } else if ($Modus == "sperrung"){
                $Eintrag = sperrung_anlegen($Von, $Bis, $Typ, $Titel, $Erklaerung, lade_user_id(), TRUE);
            }

            if ($Eintrag['erfolg'] == FALSE){
                $Antwort = "error";
                echo "<h5>Fehler!</h5><br>".$Eintrag['meldung']."";
            } else if ($Eintrag['erfolg'] == TRUE){
                $Antwort = "success";
                echo "<h5>Erfolg!</h5><br>".$Eintrag['meldung']."";
            }

            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }

        return $Antwort;
    }

?>