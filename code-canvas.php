<?php
    if(!isset($_SESSION)){
        session_start();
    }
    include "include/dbconnect.php";
    date_default_timezone_set('Asia/Manila');
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="images/JJ Logo.png">
        <title>Canvas</title>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.css"/>
    </head>
    <body>
        <div class="row row-cols-6 g-2">
            <?php
                if(isset($_POST['printPurchasePendingMaterialCodes'])){
                    $purchaseGroupID = $_POST['inputHiddenPrintPendingPurchaseID'];
                    $materialsTableCodes = $database->query("SELECT m_code_photo, m_code FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_quality = 'Good' AND m_status = 'Pending'");
                    if ($materialsTableCodes->num_rows > 0) {
                        while ($materialsTableCodesRow = $materialsTableCodes->fetch_assoc()) {
                            ?>
                                <div class="col">
                                    <div class="text-center">
                                        <img src="images/codes/<?php echo $materialsTableCodesRow['m_code_photo']?>" width="100" height="100">
                                        <br>
                                        <?php echo $materialsTableCodesRow['m_code']?>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Pending Materials for this Purchase Group
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printPurchaseStorageMaterialCodes'])){
                    $purchaseGroupID = $_POST['inputHiddenPrintStoragePurchaseID'];
                    $materialsTableCodes = $database->query("SELECT m_code_photo, m_code FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_quality = 'Good' AND m_status = 'Storage'");
                    if ($materialsTableCodes->num_rows > 0) {
                        while ($materialsTableCodesRow = $materialsTableCodes->fetch_assoc()) {
                            ?>
                                <div class="col">
                                    <div class="text-center">
                                        <img src="images/codes/<?php echo $materialsTableCodesRow['m_code_photo']?>" width="100" height="100">
                                        <br>
                                        <?php echo $materialsTableCodesRow['m_code']?>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Storage Materials for this Purchase Group
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printPurchaseAllMaterialCodes'])){
                    $purchaseGroupID = $_POST['inputHiddenPrintAllPurchaseID'];
                    $materialsTableCodes = $database->query("SELECT m_code_photo, m_code FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_quality = 'Good'");
                    if ($materialsTableCodes->num_rows > 0) {
                        while ($materialsTableCodesRow = $materialsTableCodes->fetch_assoc()) {
                            ?>
                                <div class="col">
                                    <div class="text-center">
                                        <img src="images/codes/<?php echo $materialsTableCodesRow['m_code_photo']?>" width="100" height="100">
                                        <br>
                                        <?php echo $materialsTableCodesRow['m_code']?>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Materials for this Purchase Group
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printPurchaseIndividualMaterialCodes'])){
                    $purchaseGroupID = $_POST['inputHiddenPrintIndividualPurchaseID'];
                    if (isset($_POST['materialChoice'])) {
                        $materialChoice = $_POST['materialChoice'];
                        foreach ($materialChoice as $materialsID) {
                            $materialsTableCodes = $database->query("SELECT m_code_photo, m_code FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND ID = '$materialsID'");
                            if ($materialsTableCodes->num_rows > 0) {
                                while ($materialsTableCodesRow = $materialsTableCodes->fetch_assoc()) {
                                    ?>
                                        <div class="col">
                                            <div class="text-center">
                                                <img src="images/codes/<?php echo $materialsTableCodesRow['m_code_photo']?>" width="100" height="100">
                                                <br>
                                                <?php echo $materialsTableCodesRow['m_code']?>
                                            </div>
                                        </div>
                                    <?php
                                }
                            }
                            else {
                                ?>
                                    <div class="text-center">
                                        No Materials for this Purchase Group
                                    </div>
                                <?php
                            }
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Materials was chosen for this Purchase Group
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printPendingMaterialCodes'])){
                    $purchaseGroupID = $_POST['selectPrintPendingPurchaseGroup'];
                    $materialsTableCodes = $database->query("SELECT m_code_photo, m_code FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_quality = 'Good' AND m_status = 'Pending'");
                    if ($materialsTableCodes->num_rows > 0) {
                        while ($materialsTableCodesRow = $materialsTableCodes->fetch_assoc()) {
                            ?>
                                <div class="col">
                                    <div class="text-center">
                                        <img src="images/codes/<?php echo $materialsTableCodesRow['m_code_photo']?>" width="100" height="100">
                                        <br>
                                        <?php echo $materialsTableCodesRow['m_code']?>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Pending Materials for this Purchase Group
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printStorageMaterialCodes'])){
                    $purchaseGroupID = $_POST['selectPrintStoragePurchaseGroup'];
                    $materialsTableCodes = $database->query("SELECT m_code_photo, m_code FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_quality = 'Good' AND m_status = 'Storage'");
                    if ($materialsTableCodes->num_rows > 0) {
                        while ($materialsTableCodesRow = $materialsTableCodes->fetch_assoc()) {
                            ?>
                                <div class="col">
                                    <div class="text-center">
                                        <img src="images/codes/<?php echo $materialsTableCodesRow['m_code_photo']?>" width="100" height="100">
                                        <br>
                                        <?php echo $materialsTableCodesRow['m_code']?>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No materials in storage for this Purchase Group
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printPendingProductCodes'])){
                    $productsTableCodes = $database->query("SELECT p_code_photo, p_code FROM products_tbl WHERE p_quality = 'Good' AND p_status = 'Pending'");
                    if ($productsTableCodes->num_rows > 0) {
                        while ($productsTableCodesRow = $productsTableCodes->fetch_assoc()) {
                            ?>
                                <div class="col">
                                    <div class="text-center">
                                        <img src="images/codes/<?php echo $productsTableCodesRow['p_code_photo']?>" width="100" height="100">
                                        <br>
                                        <?php echo $productsTableCodesRow['p_code']?>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Pending Products Available
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printStorageProductCodes'])){
                    $productsTableCodes = $database->query("SELECT p_code_photo, p_code FROM products_tbl WHERE p_quality = 'Good' AND p_status = 'Storage'");
                    if ($productsTableCodes->num_rows > 0) {
                        while ($productsTableCodesRow = $productsTableCodes->fetch_assoc()) {
                            ?>
                                <div class="col">
                                    <div class="text-center">
                                        <img src="images/codes/<?php echo $productsTableCodesRow['p_code_photo']?>" width="100" height="100">
                                        <br>
                                        <?php echo $productsTableCodesRow['p_code']?>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Pending Products Available
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printAllProductsCodes'])){
                    $productsTableCodes = $database->query("SELECT p_code_photo, p_code FROM products_tbl WHERE p_quality = 'Good'");
                    if ($productsTableCodes->num_rows > 0) {
                        while ($productsTableCodesRow = $productsTableCodes->fetch_assoc()) {
                            ?>
                                <div class="col">
                                    <div class="text-center">
                                        <img src="images/codes/<?php echo $productsTableCodesRow['p_code_photo']?>" width="100" height="100">
                                        <br>
                                        <?php echo $productsTableCodesRow['p_code']?>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Products Available
                            </div>
                        <?php
                    }
                }

                if(isset($_POST['printIndividualProductCodes'])){
                    if (isset($_POST['productChoice'])) {
                        $productChoice = $_POST['productChoice'];
                        foreach ($productChoice as $productsID) {
                            $productsTableCodes = $database->query("SELECT p_code_photo, p_code FROM products_tbl WHERE ID = '$productsID'");
                            if ($productsTableCodes->num_rows > 0) {
                                while ($productsTableCodesRow = $productsTableCodes->fetch_assoc()) {
                                    ?>
                                        <div class="col">
                                            <div class="text-center">
                                                <img src="images/codes/<?php echo $productsTableCodesRow['p_code_photo']?>" width="100" height="100">
                                                <br>
                                                <?php echo $productsTableCodesRow['p_code']?>
                                            </div>
                                        </div>
                                    <?php
                                }
                            }
                            else {
                                ?>
                                    <div class="text-center">
                                        No Products Available
                                    </div>
                                <?php
                            }
                        }
                    }
                    else {
                        ?>
                            <div class="text-center">
                                No Products was chosen
                            </div>
                        <?php
                    }
                }
            ?>

        </div>

        <script>window.print();</script>
        <script src="js/jquery.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.js"></script>
        <script src="js/PassRequirements.js"></script>
        <script src="js/script.js"></script>
    </body>
</html>



    




