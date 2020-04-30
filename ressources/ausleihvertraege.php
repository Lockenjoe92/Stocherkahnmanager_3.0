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