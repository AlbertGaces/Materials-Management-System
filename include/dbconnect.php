<?php
    $database = mysqli_connect("localhost", "root", "", "jj_fabrication_db");
    if(!$database){
        die("Connection Failed: " . mysqli_connect_error());
    }
?>