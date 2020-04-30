<?php

function aktuellen_mietvertrag_id_laden(){

    $link = connect_db();

    $Anfrage = "SELECT id FROM ausleihvertraege WHERE archivar = '0' ORDER BY create_time DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis['id'];
}

function mietvertrag_unterschreiben($User, $DSid){

    $link = connect_db();
    $Timestamp = timestamp();

    if (!($stmt = $link->prepare("INSERT INTO ausleihvertrag_unterzeichnungen (vertrag, user_id, timestamp) VALUES (?,?,?)"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("iis",$DSid, $User, $Timestamp)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return false;
    } else {
        return true;
    }

}

function lade_mietvertrag($ID){

    $link = connect_db();

    if (!($stmt = $link->prepare("SELECT * FROM ausleihvertraege WHERE archivar = '0' AND id = ? ORDER BY create_time DESC"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        return $Antwort['erfolg'] = false;
    }

    if (!$stmt->bind_param("i",$ID)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        return $Antwort['erfolg'] = false;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return $Antwort['erfolg'] = false;
    } else {
        $res = $stmt->get_result();
        $num_results = mysqli_fetch_assoc($res);

        return $num_results;
    }
}

function user_needs_mv(){

    $link = connect_db();
    $UserID = lade_user_id();
    $AktDSEid = aktuelle_ds_id_laden();

    $Anfrage = "SELECT id FROM ausleihvertrag_unterzeichnungen WHERE vertrag = ".$AktDSEid." AND user_id = ".$UserID."";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl == 1){
        return false;
    } else {
        return true;
    }
}