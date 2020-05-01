<?php

    include_once "./ressourcen/ressourcen.php";

    session_manager();
    $link = connect_db();

    $Benutzerrollen = benutzerrollen_laden('');
    $ReservierungID = $_GET['res'];

    //PARSER
    $Parser = parse_uebernahme_ausmachen($ReservierungID);

    //SEITE Initiieren
    echo "<html>";
        header_generieren("Schl&uuml;ssel&uuml;bernahme ausmachen");
        echo "<body>";
        navbar_generieren($Benutzerrollen, TRUE, 'schluesseluebenahme_ausmachen');
        echo "<main>";

        echo "<div class='section'><h3 class='center-align'>Schl&uuml;ssel&uuml;bernahme ausmachen</h3></div>";

        echo "<div class='container'>";
        echo "<div class='row'>";
        echo "<div class='col s12 m9 l12'>";

            if ($Parser['success'] === NULL){

                //Normale Erklärungsseite anzeigen!
                erklaerung_schluesseluebernahme_element();
                promt_schluesseluebernahme_element($Parser);

            } else if ($Parser['success'] === TRUE){

                //Erfolgsmeldung anzeigen!
                if ($Parser['wartmode'] == TRUE){
                    zurueck_karte_generieren(TRUE, 'Die Schl&uuml;ssel&uuml;bergabe wurde erfolgreich eingetragen und die Gruppe davor, als auch der User informiert.', './reservierungsmanagement.php');
                } else if ($Parser['wartmode'] == FALSE){
                    zurueck_karte_generieren(TRUE, 'Deine Schl&uuml;ssel&uuml;bergabe wurde erfolgreich eingetragen und die Gruppe vor dir informiert. Bitte schaue ab jetzt &ouml;fters in deine Mail falls sich doch etwas &auml;ndern sollte und sei bitte p&uuml;nktlich an der Anlegestelle!:) Wir w&uuml;nschen eine gute Fahrt!:)', './eigene_reservierungen.php');
                }

            } else if ($Parser['success'] === FALSE){

                //Fehlermeldung anzeigen!
                if ($Parser['wartmode'] == TRUE){
                    zurueck_karte_generieren(FALSE, $Parser['meldung'], './reservierungsmanagement.php');
                } else if ($Parser['wartmode'] == FALSE){
                    zurueck_karte_generieren(FALSE, $Parser['meldung'], './eigene_reservierungen.php');
                }

            }

        echo "</div>";
        echo "</div>";
        echo "</div>";

        echo "</main>";
        footer_generieren();
        echo "</body>";
    echo "</html>";

function parse_uebernahme_ausmachen($ReservierungID){

        $link = connect_db();
        $Reservierung = lade_reservierung($ReservierungID);

        //Instant DAU checks:

            $DAUcounter = 0;
            $DAUerror = "";
            $Wartmode = FALSE;

            //Keine Reservierung übermittelt
            if ($ReservierungID == ""){
                $DAUcounter++;
                $DAUerror .= "Es wurde keine Reservierung gew&auml;hlt!<br>";
            }

            //Reservierung gehört nicht dem User - ist es noch ein Wart?
            if (lade_user_id() != intval($Reservierung['user'])){

                $UserAktuell = lade_user_meta(lade_user_id());
                $Benutzerrollen = benutzerrollen_laden($UserAktuell['username']);

                if ($Benutzerrollen['wart'] != TRUE){
                    $DAUcounter++;
                    $DAUerror .= "Du hast nicht die n&ouml;tigen Rechte um diese Reservierung zu bearbeiten!<br>";
                } else if ($Benutzerrollen['wart'] == TRUE){
                    $Wartmode = TRUE;
                }
            }

        if ($DAUcounter > 0){

            //Check High priority Fails
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = $DAUerror;

        } else {

            //User darf noch keine Übernahme machen
            $Benutzereinstellungen = benutzereinstellung_laden(lade_user_id());

            if (intval($Benutzereinstellungen['uebernahme']) != 1){

                //Bei StoKaWart egal
                $UserAktuell = lade_user_meta(lade_user_id());
                $Benutzerrollen = benutzerrollen_laden($UserAktuell['username']);

                if ($Benutzerrollen['wart'] != TRUE){
                    $DAUcounter++;
                    $DAUerror .= "Du hast nicht die n&ouml;tigen Einweisungen um eine Schl&uuml;ssel&uuml;bernahme auszumachen!<br>";
                } else if ($Benutzerrollen['wart'] == TRUE){
                    $Wartmode = TRUE;
                }
            }

            //Reservierung hat schon ne gültige Schlüsselübergabe
            $AnfrageUebergabestatusDieseRes = "SELECT id FROM uebergaben WHERE res = '$ReservierungID' AND storno_user = '0'";
            $AbfrageUebergabestatusDieseRes = mysqli_query($link, $AnfrageUebergabestatusDieseRes);
            $AnzahlUebergabestatusDieseRes = mysqli_num_rows($AbfrageUebergabestatusDieseRes);

            if ($AnzahlUebergabestatusDieseRes > 0){

                $Uebergabe = mysqli_fetch_assoc($AbfrageUebergabestatusDieseRes);

                $DAUcounter++;
                $DAUerror .= "Du hast f&uuml;r diese Reservierung bereits eine Schl&uuml;ssel&uuml;bergabe ausgemacht! Falls du lieber den Schl&uuml;ssel der Vorgruppe &uuml;bernehmen m&ouml;chtest, <a href='uebergabe_stornieren_user.php?id=".$Uebergabe['id']."'>storniere bitte zuerst die &Uuml;bergabe!</a><br>";
            }

            //Reservierung hat schon eine Übernahme ausgemacht
            $AnfrageUebernahmeResSchonVorhanden = "SELECT id FROM uebernahmen WHERE reservierung = '$ReservierungID' AND storno_user = '0'";
            $AbfrageUebernahmeResSchonVorhanden = mysqli_query($link, $AnfrageUebernahmeResSchonVorhanden);
            $AnzahlUebernahmeResSchonVorhanden = mysqli_num_rows($AbfrageUebernahmeResSchonVorhanden);

            if ($AnzahlUebernahmeResSchonVorhanden > 0){
                $DAUcounter++;
                $DAUerror .= "Du hast hast f&uuml;r diese Reservierung bereits eine &Uuml;bergabe ausgemacht!<br>";
            }

            //Es gibt keine Vorfahrende Reservierung mit ausgemachter Übergabe mehr!
            $AnfrageLadeResVorher = "SELECT id FROM reservierungen WHERE ende = '".$Reservierung['beginn']."' AND storno_user = '0'";
            $AbfrageLadeResVorher = mysqli_query($link, $AnfrageLadeResVorher);
            $AnfrageLadeResVorher = mysqli_num_rows($AbfrageLadeResVorher);

            if ($AnfrageLadeResVorher == 0){
                $DAUcounter++;
                $DAUerror .= "Es gibt leider keine Reservierung mehr vor dir! <a href='./uebernahme_ausmachen.php?res=".$ReservierungID."'>Buche dir einfach eine Schl&uuml;ssel&uuml;bergabe</a> durch einen unserer Stocherkahnw&auml;rte:)<br>";
            } else if ($AnfrageLadeResVorher > 0){

                $ReservierungVorher = mysqli_fetch_assoc($AbfrageLadeResVorher);

                //Es gibt ne Res, aber hat sie auch eine ausgemachte/durchgeführte Schlüsselübergabe?
                $AnfrageUebergabestatus = "SELECT id FROM uebergaben WHERE res = '".$ReservierungVorher['id']."' AND storno_user = '0'";
                $AbfrageUebergabestatus = mysqli_query($link, $AnfrageUebergabestatus);
                $AnzahlUebergabestatus = mysqli_num_rows($AbfrageUebergabestatus);

                if ($AnzahlUebergabestatus == 0){

                    //Hat die reservierung vielleicht eine Schlüsselübernahme gebucht? -> Wenn ja, einstellung Checken ob man Schlüssel über mehrere Reservierungen weitergeben darf:
                    if (lade_einstellung('schluesseluebernahme-ueber-mehrere-res') == "TRUE"){

                        $AnfrageHatVorfahrendeReservierungUebernahme = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$ReservierungVorher['id']."' AND storno_user = '0'";
                        $AbfrageHatVorfahrendeReservierungUebernahme = mysqli_query($link, $AnfrageHatVorfahrendeReservierungUebernahme);
                        $AnzahlHatVorfahrendeReservierungUebernahme = mysqli_num_rows($AbfrageHatVorfahrendeReservierungUebernahme);

                        if ($AnzahlHatVorfahrendeReservierungUebernahme == 0){
                            $DAUcounter++;
                            $DAUerror .= "Leider hat die Reservierung vor dir noch keinen zugeteilten Schl&uuml;ssel! Entweder du wartest noch ein wenig, oder <a href='./uebernahme_ausmachen.php?res=".$ReservierungID."'>du buchst dir einfach eine eigeneSchl&uuml;ssel&uuml;bergabe</a>!<br>";
                        }

                    } else {
                        $DAUcounter++;
                        $DAUerror .= "Leider hat die Reservierung vor dir noch keinen zugeteilten Schl&uuml;ssel! Entweder du wartest noch ein wenig, oder <a href='./uebernahme_ausmachen.php?res=".$ReservierungID."'>du buchst dir einfach eine eigeneSchl&uuml;ssel&uuml;bergabe</a>!<br>";
                    }
                }
            }

            if ($DAUcounter > 0){

                //Check low priority fails

                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = $DAUerror;
            } else {

                if (isset($_POST['eintragen'])){

                    $Antwort = uebernahme_eintragen($ReservierungID, $_POST['kommentar']);

                } else {
                    $Antwort['success'] = NULL;
                    $Antwort['meldung'] = "";
                    $Antwort['wartmode'] = $Wartmode;
                }
            }

        }

        return $Antwort;
    }

    function erklaerung_schluesseluebernahme_element(){

        echo "<div class='section'>";
            echo "<p><table><tr><th><i class='large material-icons'>new_releases</i> </th><td>Du bist im Begriff eine Schl&uuml;ssel&uuml;bernahme f&uuml;r deine Reservierung auszumachen. Damit kannst du dir und uns Stocherkahnw&auml;rten ein bisschen Arbeit ersparen, indem du den Schl&uuml;ssel nicht pers&ouml;nlich bei uns abholen kommst. Dennoch gibt es folgende Dinge zu beachten:</td></tr></table></p>";
            echo "<p><ul class='collection'>";
                echo "<li class='collection-item'><table><tr><th><i class='small material-icons'>schedule</i> </th><td>Eine Schl&uuml;ssel&uuml;bernahme h&auml;ngt sowohl von deiner P&uuml;nktlichkeit, als auch der der Gruppe vor dir ab. Sei daher bitte p&uuml;nktlich an der Anlegestelle zu Beginn deiner Reservierung damit die Gruppe vor dir nicht warten muss.<br>Im Gegenzug erh&auml;t die Gruppe vor dir von uns nochmal den Hinweis ebenfalls p&uuml;nktlich an die Anlegestelle zur&uuml;ckzukehren.</td></tr></table></li>";
                echo "<li class='collection-item'><table><tr><th><i class='small material-icons'>info</i> </th><td>Die Gruppe vor dir wird eine eMail erhalten, die sie &uuml;ber dein Vorhaben in Kenntnis setzt. Sollte die Gruppe dies nicht w&uuml;nschen (z.B. wenn sie den Kahn nicht bis ganz ans Ende der Fahrt nutzen m&ouml;chte), kann diese die geplante Schl&uuml;ssel&uuml;bernahme absagen und du erh&auml;ltst eine Mail.</td></tr></table></li>";
                echo "<li class='collection-item'><table><tr><th><i class='small material-icons'>info</i> </th><td>Wir Stocherkahnw&auml;rte k&ouml;nnen keine Garantie daf&uuml;r &Uuml;bernehmen, dass die &Uuml;bernahme klappt. Falls also kurzfristig sich doch etwas &auml;ndern sollte, kann es daher sein, dass du doch keinen Schl&uuml;ssel bekommst.</td></tr></table></li>";
                echo "<li class='collection-item'><table><tr><th><i class='small material-icons'>loyalty</i> </th><td>Da du deine Fahrt nicht pers&ouml;hnlich bei einem unserer W&auml;rte bezahlen kannst, verlassen wir uns darauf, dass du die Ausleihgeb&uuml;hr und den Schl&uuml;ssel zuverl&auml;ssig direkt nach deiner Fahrt in den R&uuml;ckgabebriefkasten wirfst.</td></tr></table></li>";
            echo "</ul></p>";
        echo "</div>";

    }

    function promt_schluesseluebernahme_element($Parser){

        echo "<div class='section'>";

        if ($Parser['wartmode'] == TRUE){
            prompt_karte_generieren('eintragen', 'Eintragen', './reservierungsmanagement.php', 'Abbrechen', 'M&ouml;chtest du eine Schl&uuml;ssel&uuml;bernahme eintragen?', TRUE, 'kommentar');
        } else if ($Parser['wartmode'] == FALSE) {
            prompt_karte_generieren('eintragen', 'Eintragen', './eigene_reservierungen.php', 'Abbrechen', 'M&ouml;chtest du eine Schl&uuml;ssel&uuml;bernahme eintragen?', TRUE, 'kommentar');
        }

        echo "</div>";

    }

?>