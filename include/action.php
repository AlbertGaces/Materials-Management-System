<?php

    include "dbconnect.php";

    $typeID = $_POST['typeID'];

    if (!empty($typeID)) {
        $query = "SELECT * FROM material_name_tbl WHERE m_type_id = {$typeID}";
        $result = $database->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<option value="'.$row['ID'].'">'.$row['m_name'].'</option>'; 
            }
        }
        else {
            echo '<option value="">No available material</option>'; 
        }
    }
?>