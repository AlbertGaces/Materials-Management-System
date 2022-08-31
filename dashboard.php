<?php 
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "dashboard";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();
    $maxStorage = $adminData['max_storage'];

    $totalProductMeasurement = 0;
    $productsTableMeasurementCount = $database->query("SELECT p_measurement FROM `products_tbl`");
    while($productsTableMeasurementCountRow = $productsTableMeasurementCount->fetch_assoc()) {
        $totalProductMeasurement = $totalProductMeasurement + $productsTableMeasurementCountRow['p_measurement'];
    }

    date_default_timezone_set('Asia/Manila');

    // ==================== END - QUERIES ====================

    // ==================== START - NOTIFICATION ====================

    $notificationActive = 0;

    $materialsNameTableCheck = $database->query("SELECT * FROM material_name_tbl");
    while ($materialsNameTableCheckRows = $materialsNameTableCheck->fetch_assoc()) {
        $materialNameID = $materialsNameTableCheckRows['ID'];
        $materialsTableCheck = $database->query("SELECT COUNT(*) AS m_count FROM materials_tbl WHERE m_name = '$materialNameID' AND m_quality = 'Good' AND m_status != 'Used'");
        if ($materialsTableCheck->num_rows > 0) {
            while ($materialsTableCheckRow = $materialsTableCheck->fetch_assoc()) {
                $mCountCheck = ($materialsTableCheckRow['m_count'] / $maxStorage) * 100;
                if ($mCountCheck < 49) {
                    $notificationActive = $notificationActive + 1;
                }
            }
        }
        else {
            $notificationActive = $notificationActive + 1;
        }
    }

    $productsNameTableCheck = $database->query("SELECT * FROM product_name_tbl");
    while ($productsNameTableCheckRows = $productsNameTableCheck->fetch_assoc()) {
        $productNameID = $productsNameTableCheckRows['ID'];
        $productsTableCheck = $database->query("SELECT COUNT(*) AS p_count FROM products_tbl WHERE p_name = '$productNameID' AND p_quality = 'Good' AND p_status != 'Sold'");
        if ($productsTableCheck->num_rows > 0) {
            while ($productsTableCheckRow = $productsTableCheck->fetch_assoc()) {
                $pCountCheck = ($productsTableCheckRow['p_count'] / $maxStorage) * 100;
                if ($pCountCheck < 49) {
                    $notificationActive = $notificationActive + 1;
                }
            }
        }
        else {
            $notificationActive = $notificationActive + 1;
        }
    }

    $projectsTableCheck = $database->query("SELECT * FROM projects_tbl WHERE proj_status = 'Active'");
    if ($projectsTableCheck->num_rows > 0) {
        while ($projectsTableCheckRows = $projectsTableCheck->fetch_assoc()) {
            $deliveryDate = new DateTime($projectsTableCheckRows['proj_delivery_date']);
            $dateDifference = $deliveryDate->diff(new DateTime("now"));
            if ($dateDifference->format("%a") < 7) {
                $notificationActive = $notificationActive + 1;
            }
        }
    }
    else {
        $notificationActive = $notificationActive + 0;
    }

    // ==================== END - NOTIFICATION ====================

    // ==================== START - ADJUST DELIVERY DATE ====================

    if(isset($_POST['btnAdjustDeliveryDate'])){
        $projID = $_POST['inputHiddenProjectID'];
        $projCode = $_POST['inputHiddenProjectCode'];
        $projDeliveryDateOld = $_POST['inputHiddenProjectDeliveryDateOld'];
        $projDescription = $_POST['selectProjectDescription'];
        $chosenDateExtension = $_POST['radioAdjustDeliveryDateOptions'.$projID];
        $projDeliveryDateNew = date('Y-m-d H:i:s', strtotime($projDeliveryDateOld.' +'.$chosenDateExtension.' day'));
        $projDeliveryDateNewText = date('F j, Y', strtotime($projDeliveryDateNew));
        $currentDate = date("Y-m-d H:i:s");

        $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$currentDate','$projCode to $projDeliveryDateNewText due to $projDescription','Adjusted')") or die ($database->error);
        $database->query("UPDATE `projects_tbl` SET `proj_delivery_date`='$projDeliveryDateNew', `proj_description`='$projDescription' WHERE ID = '$projID'") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Delivery Date Extended!',
                    text: 'The delivery date of $projCode has been adjusted to $projDeliveryDateNewText',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";

        header("Refresh:2; url=dashboard.php");
    }

    // ==================== START - ADJUST DELIVERY DATE ====================
    
    // ==================== START - SIGN OUT ====================

    if(isset($_POST['signOut'])){
        $currentTime = date("Y-m-d H:i:s");
        $adminsTable = $database->query("UPDATE `admins_tbl` SET `account_status` = 'Offline' WHERE `ID` = '{$_SESSION['ID']}'"); 
        $database->query("INSERT INTO `accounts_history_tbl`(`ID`, `ah_admin`, `ah_date`, `ah_event`, `ah_method`) VALUES (NULL,'$adminID','$currentTime','Signed Out','Out')") or die ($database->error);

        
        unset($_SESSION['ID']);
        unset($_SESSION['name']);
        unset($_SESSION['firstname']);
        unset($_SESSION['signedIn']);
        unset($_SESSION['status']);
        header("Location: signin.php");
    }

    // ==================== END - SIGN OUT ====================
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/JJ Logo.png">
    <title>Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.css"/>
    <link rel="stylesheet" href="boxicons/css/boxicons.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- ==================== START - SIDE NAVIGATION ==================== -->

    <?php 
        include "include/sidebar.php";
    ?>

    <!-- ==================== END - SIDE NAVIGATION ==================== -->

    <!-- ==================== START - MAIN CONTENT ==================== -->

    <div class="content">
        <div class="container-fluid">

            <!-- ==================== START - DASHBOARD TITLE ROW ==================== -->
            
            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center justify-content-between">
                    <p class="lead m-0 d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-grid-alt fs-3'></i>&emsp;Dashboard
                    </p>
                    <?php
                        if ($notificationActive > 0) {
                            ?>
                                <i data-bs-toggle="modal" data-bs-target="#modalNotifications">
                                    <i class='d-flex align-items-center fs-4 pointer bx bxs-bell-ring bx-tada text-danger' data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo $notificationActive." notification"; if ($notificationActive > 1) {echo "s";}?>. Click to view"></i>    
                                </i>
                            <?php
                        }
                        else {
                            ?>
                                <i class='d-flex align-items-center fs-4 pointer bx bxs-bell' data-bs-toggle="tooltip" data-bs-placement="bottom" title="No new notifications"></i>
                            <?php
                        }
                    ?>
                    <div class="lead m-0" id="liveClock"></div>
                </div>
            </div>

            <form action="" method="POST">
                <div class="modal fade" id="modalNotifications" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Notifications</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row row-cols-1 g-2">
                                    <?php
                                        $materialsNameTableCheck = $database->query("SELECT * FROM material_name_tbl");
                                        while ($materialsNameTableCheckRows = $materialsNameTableCheck->fetch_assoc()) {
                                            $materialNameID = $materialsNameTableCheckRows['ID'];
                                            $materialsTableCheck = $database->query("SELECT COUNT(*) AS m_count FROM materials_tbl WHERE m_name = '$materialNameID' AND m_quality = 'Good' AND m_status != 'Used' ORDER BY m_type");
                                            if ($materialsTableCheck->num_rows > 0) {
                                                while ($materialsTableCheckRow = $materialsTableCheck->fetch_assoc()) {
                                                    $mCountCheck = number_format((float)($materialsTableCheckRow['m_count'] / $maxStorage) * 100, 2, '.', '');
                                                    if  ($mCountCheck > 49) {}
                                                    else if ($mCountCheck > 24) {
                                                        echo "<div class='col d-flex align-items-center'><i class='bx bxs-error-circle bx-flashing text-orange fs-5'></i>&emsp;<label>{$materialsNameTableCheckRows['m_name']} stock is at $mCountCheck%.</label></div>";
                                                    }
                                                    else if ($mCountCheck > 0) {
                                                        echo "<div class='col d-flex align-items-center'><i class='bx bxs-error-circle bx-flashing text-danger fs-5'></i>&emsp;<label>{$materialsNameTableCheckRows['m_name']} stock is in critical level at $mCountCheck%.</label></div>";
                                                    }
                                                    else if ($mCountCheck == 0){
                                                        echo "<div class='col d-flex align-items-center'><i class='bx bxs-circle text-gray fs-5'></i>&emsp;<label>{$materialsNameTableCheckRows['m_name']} stock is empty.</label></div>";
                                                    }
                                                }
                                            }
                                        }

                                        $productsNameTableCheck = $database->query("SELECT * FROM product_name_tbl");
                                        while ($productsNameTableCheckRows = $productsNameTableCheck->fetch_assoc()) {
                                            $productNameID = $productsNameTableCheckRows['ID'];
                                            $productsTableCheck = $database->query("SELECT COUNT(*) AS p_count FROM products_tbl WHERE p_name = '$productNameID' AND p_quality = 'Good' AND p_status != 'Sold'");
                                            if ($productsTableCheck->num_rows > 0) {
                                                while ($productsTableCheckRow = $productsTableCheck->fetch_assoc()) {
                                                    $pCountCheck = number_format((float)($productsTableCheckRow['p_count'] / $maxStorage) * 100, 2, '.', '');
                                                    if  ($pCountCheck > 49) {}
                                                    else if ($pCountCheck > 24) {
                                                        echo "<div class='col d-flex align-items-center'><i class='bx bxs-error-circle bx-flashing text-orange fs-5'></i>&emsp;<label>{$productsNameTableCheckRows['p_name']} stock is at $pCountCheck%.</label></div>";
                                                    }
                                                    else if ($pCountCheck > 0) {
                                                        echo "<div class='col d-flex align-items-center'><i class='bx bxs-error-circle bx-flashing text-danger fs-5'></i>&emsp;<label>{$productsNameTableCheckRows['p_name']} stock is in critical level at $pCountCheck%.</label></div>";
                                                    }
                                                    else if ($pCountCheck == 0){
                                                        echo "<div class='col d-flex align-items-center'><i class='bx bxs-circle text-gray fs-5'></i>&emsp;<label>{$productsNameTableCheckRows['p_name']} stock is empty.</label></div>";
                                                    }
                                                }
                                            }
                                        }

                                        
                                        $currentTime = date("Y-m-d 00:00:00");
                                        $projectsTableCheck = $database->query("SELECT * FROM projects_tbl WHERE proj_status = 'Active' AND proj_delivery_date >= '$currentTime'");
                                        if ($projectsTableCheck->num_rows > 0) {
                                            while ($projectsTableCheckRows = $projectsTableCheck->fetch_assoc()) {
                                                $deliveryDate = new DateTime($projectsTableCheckRows['proj_delivery_date']);
                                                $dateDifference = $deliveryDate->diff(new DateTime("now"));
                                                if ($dateDifference->format("%a") >= 7) {}
                                                else if ($dateDifference->format("%a") >= 3) {
                                                    if ($_SESSION['position'] == 'Administrator') {
                                                        ?>
                                                            <div class='col d-flex align-items-center pointer' data-bs-target="#modalProjectsDateAdjustment<?php echo $projectsTableCheckRows['ID'];?>" data-bs-toggle="modal" data-bs-dismiss="modal">
                                                                <i class='bx bxs-error-circle bx-flashing text-orange fs-5'></i>&emsp;
                                                                <label class="pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="Click to see more"><?php echo $projectsTableCheckRows['proj_title']." (".$projectsTableCheckRows['proj_code'].") must be delivered this week."?></label>
                                                            </div>
                                                        <?php
                                                    }
                                                    else {
                                                        ?>
                                                            <div class='col d-flex align-items-center'>
                                                                <i class='bx bxs-error-circle bx-flashing text-orange fs-5'></i>&emsp;
                                                                <label><?php echo $projectsTableCheckRows['proj_title']." (".$projectsTableCheckRows['proj_code'].") must be delivered this week."?></label>
                                                            </div>
                                                        <?php
                                                    }
                                                }
                                                else {
                                                    if ($_SESSION['position'] == 'Administrator') {
                                                        ?>
                                                            <div class='col d-flex align-items-center pointer' data-bs-target="#modalProjectsDateAdjustment<?php echo $projectsTableCheckRows['ID'];?>" data-bs-toggle="modal" data-bs-dismiss="modal">
                                                                <i class='bx bxs-error-circle bx-flashing text-danger fs-5'></i>&emsp;
                                                                <label class="pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="Click to see more"><?php echo $projectsTableCheckRows['proj_title']." (".$projectsTableCheckRows['proj_code'].") needs to be delivered immediately."?></label>
                                                            </div>
                                                        <?php
                                                    }
                                                    else {
                                                        ?>
                                                            <div class='col d-flex align-items-center'>
                                                                <i class='bx bxs-error-circle bx-flashing text-danger fs-5'></i>&emsp;
                                                                <label><?php echo $projectsTableCheckRows['proj_title']." (".$projectsTableCheckRows['proj_code'].") needs to be delivered immediately."?></label>
                                                            </div>
                                                        <?php
                                                    }
                                                }
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <?php 
                $projectsTableCheck = $database->query("SELECT * FROM projects_tbl WHERE proj_status = 'Active'");
                if ($projectsTableCheck->num_rows > 0) {
                    while ($projectsTableCheckRows = $projectsTableCheck->fetch_assoc()) {
                        $deliveryDate = new DateTime($projectsTableCheckRows['proj_delivery_date']);
                        $dateDifference = $deliveryDate->diff(new DateTime("now"));
                        if ($dateDifference->format("%a") >= 7) {}
                        else {
                            ?>
                                <div class="modal fade" id="modalProjectsDateAdjustment<?php echo $projectsTableCheckRows['ID'];?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Adjust Delivery Date</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="inputHiddenProjectID" value="<?php echo $projectsTableCheckRows['ID']?>"/>
                                                    <input type="hidden" name="inputHiddenProjectCode" value="<?php echo $projectsTableCheckRows['proj_code']?>"/>
                                                    <input type="hidden" name="inputHiddenProjectDeliveryDateOld" value="<?php echo $projectsTableCheckRows['proj_delivery_date']?>"/>
                                                    The delivery date of <b><?php echo $projectsTableCheckRows['proj_title']." (".$projectsTableCheckRows['proj_code']?>)</b> will be on <b><?php echo date("F j, Y", strtotime($projectsTableCheckRows['proj_delivery_date']))?></b>. Do you wish to extend it?
                                                    <br><br>
                                                    <label for="selectAdjustDeliveryDate" class="form-label">Extend By:</label>
                                                    <div class="input-group d-flex justify-content-center mb-2">
                                                        <div class="btn-group w-100" role="group">
                                                            <input type="radio" class="btn-check" name="radioAdjustDeliveryDateOptions<?php echo $projectsTableCheckRows['ID'];?>" id="radioAdjust1Day<?php echo $projectsTableCheckRows['ID'];?>" value="1" autocomplete="off" checked required>
                                                            <label class="btn btn-outline-primary" for="radioAdjust1Day<?php echo $projectsTableCheckRows['ID'];?>">1 Day</label>
                                                        
                                                            <input type="radio" class="btn-check" name="radioAdjustDeliveryDateOptions<?php echo $projectsTableCheckRows['ID'];?>" id="radioAdjust2Days<?php echo $projectsTableCheckRows['ID'];?>" value="2" autocomplete="off" required>
                                                            <label class="btn btn-outline-primary" for="radioAdjust2Days<?php echo $projectsTableCheckRows['ID'];?>">2 Days</label>

                                                            <input type="radio" class="btn-check" name="radioAdjustDeliveryDateOptions<?php echo $projectsTableCheckRows['ID'];?>" id="radioAdjust3Days<?php echo $projectsTableCheckRows['ID'];?>" value="3" autocomplete="off" required>
                                                            <label class="btn btn-outline-primary" for="radioAdjust3Days<?php echo $projectsTableCheckRows['ID'];?>">3 Days</label>

                                                            <input type="radio" class="btn-check" name="radioAdjustDeliveryDateOptions<?php echo $projectsTableCheckRows['ID'];?>" id="radioAdjust7Days<?php echo $projectsTableCheckRows['ID'];?>" value="7" autocomplete="off" required>
                                                            <label class="btn btn-outline-primary" for="radioAdjust7Days<?php echo $projectsTableCheckRows['ID'];?>">7 Days</label>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label for="selectProjectDescription" class="form-label">Choose your reason why the delivery date shall be extended.</label>
                                                        <select class="form-select" id="selectProjectDescription" name="selectProjectDescription" required>
                                                            <option value="">-- Select a Reason --</option>
                                                            <option value="Lack of Manpower">Lack of Manpower</option>
                                                            <option value="High Material Cost">High Material Cost</option>
                                                            <option value="No Delivery Man Available">No Delivery Man Available</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-danger" data-bs-target="#modalNotifications" data-bs-toggle="modal" data-bs-dismiss="modal">Wait, Go Back!</button>
                                                    <button type="submit" class="btn btn-success" name="btnAdjustDeliveryDate" id="btnAdjustDeliveryDate">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php
                        }
                    }
                }
            ?>

            <!-- ==================== END - DASHBOARD TITLE ROW ==================== -->

            <!-- ==================== START - DASHBOARD STATISTICS ROW ==================== -->

            <div class="row row-cols-2 row-cols-xl-4 g-2">
                <div class="col">
                    <?php
                        if ($_SESSION['position'] == 'Administrator') {
                            ?>
                                <a href="purchases.php" class="text-decoration-none text-dark">
                                    <div class="card mb-3 border-top-primary">
                                        <div class="row g-0">
                                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                                <i class='bx bx-file bx-tada-hover fs-1 text-primary'></i>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body p-2 text-center">
                                                    <h5 class="card-title fs-4">
                                                        <?php
                                                            $currentStartTime = date("Y-m-d 00:00:00");
                                                            $currentEndTime = date("Y-m-d 23:59:59");
                                                            $purchaseTableActive = $database->query("SELECT COUNT(*) as 'pg_active' FROM `purchase_group_tbl` WHERE pg_date BETWEEN '$currentStartTime' AND '$currentEndTime'")->fetch_assoc();
                                                            echo $purchaseTableActive['pg_active'];
                                                        ?>
                                                    </h5>
                                                    <p class="card-text">Purchase Orders</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php
                        }
                        else {
                            ?>
                                <div class="card mb-3 border-top-primary">
                                    <div class="row g-0">
                                        <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                            <i class='bx bx-file bx-tada-hover fs-1 text-primary'></i>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body p-2 text-center">
                                                <h5 class="card-title fs-4">
                                                    <?php
                                                        $currentStartTime = date("Y-m-d 00:00:00");
                                                        $currentEndTime = date("Y-m-d 23:59:59");
                                                        $purchaseTableActive = $database->query("SELECT COUNT(*) as 'pg_active' FROM `purchase_group_tbl` WHERE pg_date BETWEEN '$currentStartTime' AND '$currentEndTime'")->fetch_assoc();
                                                        echo $purchaseTableActive['pg_active'];
                                                    ?>
                                                </h5>
                                                <p class="card-text">Purchase Orders</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                        }
                    ?>
                </div>
                <div class="col">
                    <?php
                        if ($_SESSION['position'] != 'Master Admin') {
                            ?>
                                <a href="materials.php" class="text-decoration-none text-dark">
                                    <div class="card mb-3 border-top-primary">
                                        <div class="row g-0">
                                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                                <i class='bx bxs-cart-add bx-tada-hover fs-1 text-primary'></i>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body p-2 text-center">
                                                    <h5 class="card-title fs-4">
                                                        <?php
                                                            $currentStartTime = date("Y-m-d 00:00:00");
                                                            $currentEndTime = date("Y-m-d 23:59:59");
                                                            $materialsTableNew = $database->query("SELECT COUNT(*) as 'm_count_new' FROM `materials_tbl` WHERE m_received BETWEEN '$currentStartTime' AND '$currentEndTime'")->fetch_assoc();
                                                            echo $materialsTableNew['m_count_new'];
                                                        ?>
                                                    </h5>
                                                    <p class="card-text">Purchased Materials</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php
                        }
                        else {
                            ?>
                                <div class="card mb-3 border-top-primary">
                                    <div class="row g-0">
                                        <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                            <i class='bx bxs-cart-add bx-tada-hover fs-1 text-primary'></i>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body p-2 text-center">
                                                <h5 class="card-title fs-4">
                                                    <?php
                                                        $currentStartTime = date("Y-m-d 00:00:00");
                                                        $currentEndTime = date("Y-m-d 23:59:59");
                                                        $materialsTableNew = $database->query("SELECT COUNT(*) as 'm_count_new' FROM `materials_tbl` WHERE m_received BETWEEN '$currentStartTime' AND '$currentEndTime'")->fetch_assoc();
                                                        echo $materialsTableNew['m_count_new'];
                                                    ?>
                                                </h5>
                                                <p class="card-text">Purchased Materials</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                        }
                    ?>
                </div>
                <div class="col">
                    <?php
                        if ($_SESSION['position'] != 'Master Admin') {
                            ?>
                                <a href="products.php" class="text-decoration-none text-dark">
                                    <div class="card mb-3 border-top-primary">
                                        <div class="row g-0">
                                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                                <i class='bx bx-check-square bx-tada-hover fs-1 text-primary'></i>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body p-2 text-center">
                                                    <h5 class="card-title fs-4">
                                                        <?php
                                                            $currentStartTime = date("Y-m-d 00:00:00");
                                                            $currentEndTime = date("Y-m-d 23:59:59");
                                                            $productsTableNew = $database->query("SELECT COUNT(*) AS p_count_new FROM products_tbl WHERE p_completed BETWEEN '$currentStartTime' AND '$currentEndTime'")->fetch_array();
                                                            echo $productsTableNew['p_count_new'];
                                                        ?>
                                                    </h5>
                                                    <p class="card-text">Products Created</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php
                        }
                        else {
                            ?>
                                <div class="card mb-3 border-top-primary">
                                    <div class="row g-0">
                                        <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                            <i class='bx bx-check-square bx-tada-hover fs-1 text-primary'></i>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body p-2 text-center">
                                                <h5 class="card-title fs-4">
                                                    <?php
                                                        $currentStartTime = date("Y-m-d 00:00:00");
                                                        $currentEndTime = date("Y-m-d 23:59:59");
                                                        $productsTableNew = $database->query("SELECT COUNT(*) AS p_count_new FROM products_tbl WHERE p_completed BETWEEN '$currentStartTime' AND '$currentEndTime'")->fetch_array();
                                                        echo $productsTableNew['p_count_new'];
                                                    ?>
                                                </h5>
                                                <p class="card-text">Products Created</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                        }
                    ?>
                </div>
                <div class="col">
                    <?php
                        if ($_SESSION['position'] != 'Master Admin') {
                            ?>
                                <a href="projects.php" class="text-decoration-none text-dark">
                                    <div class="card mb-3 border-top-primary">
                                        <div class="row g-0">
                                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                                <i class='bx bx-cog bx-spin-hover fs-1 text-primary'></i>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body p-2 text-center">
                                                    <h5 class="card-title fs-4">
                                                        <?php
                                                            $projectsTableActive = $database->query("SELECT COUNT(*) as 'proj_active' FROM `projects_tbl` WHERE proj_status = 'active'")->fetch_assoc();
                                                            echo $projectsTableActive['proj_active'];
                                                        ?>
                                                    </h5>
                                                    <p class="card-text">Ongoing Projects</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php
                        }
                        else {
                            ?>
                                <div class="card mb-3 border-top-primary">
                                    <div class="row g-0">
                                        <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                            <i class='bx bx-cog bx-spin-hover fs-1 text-primary'></i>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body p-2 text-center">
                                                <h5 class="card-title fs-4">
                                                    <?php
                                                        $projectsTableActive = $database->query("SELECT COUNT(*) as 'proj_active' FROM `projects_tbl` WHERE proj_status = 'active'")->fetch_assoc();
                                                        echo $projectsTableActive['proj_active'];
                                                    ?>
                                                </h5>
                                                <p class="card-text">Ongoing Projects</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                        }
                    ?>
                </div>
            </div>

            <!-- ==================== END - DASHBOARD STATISTICS ROW ==================== -->

            <hr>

            <!-- ==================== START - DASHBOARD MATERIALS PERCENTAGES TITLE ROW ==================== -->

            <div class="row">
                <div class="col-12">
                    <p class="lead">
                        Percentage of Materials
                    </p>
                </div>
            </div>

            <!-- ==================== END - DASHBOARD MATERIALS PERCENTAGES TITLE ROW ==================== -->

            <!-- ==================== START - DASHBOARD MATERIALS PERCENTAGES ROW ==================== -->

            <div class="row row-cols-1 row-cols-lg-3 g-2">
                <?php
                    $materialTypeTable = $database->query("SELECT * FROM material_type_tbl ORDER BY m_type ASC");
                    while ($materialTypeTableRows = $materialTypeTable->fetch_assoc()){
                        $materialsNameGroupedCount = 0;
                        $materialPercentage = 0;
                        $materialTypeID = $materialTypeTableRows['ID'];
                        $materialsTableCount = $database->query("SELECT COUNT(*) AS materials_count FROM materials_tbl WHERE m_type = '$materialTypeID' AND m_quality = 'Good' AND m_status != 'Used'")->fetch_assoc()['materials_count'];
                        $materialsTableCountGrouped = $database->query("SELECT COUNT(*) AS materials_grouped_count FROM materials_tbl WHERE m_type = '$materialTypeID' AND m_quality = 'Good' AND m_status != 'Used' GROUP BY m_name");
                        if ($materialsTableCountGrouped->num_rows > 0) {
                            while ($materialsTableCountGroupedRows = $materialsTableCountGrouped->fetch_assoc()) {
                                $materialsNameGroupedCount = $materialsNameGroupedCount + $maxStorage;
                            }
                            $materialPercentage = ($materialsTableCount / $materialsNameGroupedCount)*100;
                        }
                        else {
                            $materialPercentage = 0;
                        }
                        ?>
                            <div class="col">
                                <div class='row m-0 p-2 border rounded diagonal-img'>
                                    <img src='images/types/<?php echo $materialTypeTableRows['m_type_photo']?>'>
                                    <div class='col text-light p-2'>
                                        <div class='text-center'>
                                            <div class='progressbar'>
                                                <div class='circle' data-percent='<?php echo $materialPercentage;?>'>
                                                    <strong class='lead fw-bold'></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='col d-flex align-items-center p-2'>
                                        <div>
                                            <p class='fs-4 m-0' style='text-shadow: -1px -1px 0 #f8f9fa, 1px -1px 0 #f8f9fa, -1px 1px 0 #f8f9fa, 1px 1px 0 #f8f9fa;'>
                                                <?php echo $materialTypeTableRows['m_type']?>
                                            </p>
                                            <p class='m-0 fw-bold' style='text-shadow: -1px -1px 0 #f8f9fa, 1px -1px 0 #f8f9fa, -1px 1px 0 #f8f9fa, 1px 1px 0 #f8f9fa;'>
                                                <?php 
                                                    if ($materialsTableCount == 0) {
                                                        echo "No Available Items";
                                                    }
                                                    else {
                                                        echo $materialsTableCount." Item";
                                                        if ($materialsTableCount > 1) {
                                                            echo "s";
                                                        }
                                                        echo " out of ".$materialsNameGroupedCount;
                                                    }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class='card-body rounded pointer expand-accordion p-2
                                    <?php
                                        if ($materialPercentage > 75) {
                                            echo "bg-success text-light";
                                        }
                                        else if ($materialPercentage > 50) {
                                            echo "bg-warning";
                                        }
                                        else if ($materialPercentage > 25) {
                                            echo "bg-orange text-light";
                                        }
                                        else if ($materialPercentage > 0) {
                                            echo "bg-danger text-light";
                                        }
                                        else if ($materialPercentage == 0) {
                                            echo "bg-gray";
                                        }
                                    ?>' id='progressAccordion' data-bs-toggle='collapse' data-bs-target='#progress<?php echo $materialTypeID?>'>
                                    <div class='card-title fs-5 m-0 d-flex justify-content-between'>
                                        <?php echo $materialTypeTableRows['m_type']?>
                                        <i class='bx bxs-chevron-down chevron-down-rotate fs-5 d-flex align-items-center'></i>
                                    </div>
                                </div>
                                <div id='progress<?php echo $materialTypeID?>' class='accordion-collapse collapse border rounded' data-bs-parent='#progressAccordion'>
                                    <div class='accordion-body p-2'>
                                        <?php
                                            $materialNameTable = $database->query("SELECT * FROM material_name_tbl WHERE m_type_id = '$materialTypeID'");
                                            if ($materialNameTable->num_rows > 0) {
                                                while ($materialNameTableRows = $materialNameTable->fetch_assoc()) {
                                                    $materialNameID = $materialNameTableRows['ID'];
                                                    $materialsTableItemCount = $database->query("SELECT COUNT(*) AS m_count FROM materials_tbl WHERE m_name = '$materialNameID' AND m_quality = 'Good' AND m_status != 'Used'")->fetch_assoc()['m_count'];
                                                    $materialsTableItemCountPercentage = number_format((float)($materialsTableItemCount / $maxStorage) * 100, 1, '.', '');
                                                    ?>
                                                        <div class='my-3'>
                                                            <div class='d-flex justify-content-between'>
                                                                <label><?php echo $materialNameTableRows['m_name'];?></label>
                                                                <?php
                                                                    $materialsPiecesCount = 0;
                                                                    $materialsTablePieces = $database->query("SELECT * FROM materials_tbl WHERE m_name = '$materialNameID' AND m_status != 'Used' AND m_quality = 'Good' AND m_unit = 'Piece'");
                                                                    while ($materialsTablePiecesRow = $materialsTablePieces->fetch_assoc()) {
                                                                        $materialsPiecesCount = $materialsPiecesCount + $materialsTablePiecesRow['m_remaining'];
                                                                    }

                                                                    $materialsKilogramsCount = 0;
                                                                    $materialsTableKilograms = $database->query("SELECT * FROM materials_tbl WHERE m_name = '$materialNameID' AND m_status != 'Used' AND m_quality = 'Good' AND m_unit = 'Kilogram'");
                                                                    while ($materialsTableKilogramsRow = $materialsTableKilograms->fetch_assoc()) {
                                                                        $materialsKilogramsCount = $materialsKilogramsCount + $materialsTableKilogramsRow['m_remaining'];
                                                                    }

                                                                    $materialsMetersCount = 0;
                                                                    $materialsTableMeters = $database->query("SELECT * FROM materials_tbl WHERE m_name = '$materialNameID' AND m_status != 'Used' AND m_quality = 'Good' AND m_unit = 'Meter'");
                                                                    while ($materialsTableMetersRow = $materialsTableMeters->fetch_assoc()) {
                                                                        $materialsMetersCount = $materialsMetersCount + $materialsTableMetersRow['m_remaining'];
                                                                    }

                                                                    if ($materialsPiecesCount > 0) {
                                                                        ?>
                                                                            <label><?php echo $materialsPiecesCount." pc"; if ($materialsPiecesCount > 1) {echo "s";}?></label>
                                                                        <?php
                                                                    }

                                                                    if ($materialsKilogramsCount > 0) {
                                                                        ?>
                                                                            <label><?php echo $materialsKilogramsCount." kg"; if ($materialsKilogramsCount > 1) {echo "s";}?></label>
                                                                        <?php
                                                                    }

                                                                    if ($materialsMetersCount > 0) {
                                                                        ?>
                                                                            <label><?php echo $materialsMetersCount." mt"; if ($materialsMetersCount > 1) {echo "s";}?></label>
                                                                        <?php
                                                                    }
                                                                ?>
                                                                <label class='fw-bold'><?php echo $materialsTableItemCountPercentage;?>%</label>
                                                            </div>
                                                            <div class='progress'>
                                                                <div class='progress-bar
                                                                    <?php
                                                                        if ($materialsTableItemCountPercentage > 75) {
                                                                            echo "bg-success";
                                                                        }
                                                                        else if ($materialsTableItemCountPercentage > 50) {
                                                                            echo "bg-warning";
                                                                        }
                                                                        else if ($materialsTableItemCountPercentage > 25) {
                                                                            echo "bg-orange";
                                                                        }
                                                                        else {
                                                                            echo "bg-danger";
                                                                        }
                                                                    ?>' role='progressbar'style='width: <?php echo $materialsTableItemCountPercentage;?>%' aria-valuemin='0' aria-valuemax='100'></div>
                                                            </div>
                                                        </div>
                                                    <?php
                                                }
                                            }
                                            else {
                                                ?>
                                                    <label class="d-flex justify-content-center p-2"><?php echo "No Material Linked To This Type Yet";?></label>
                                                <?php
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php
                    }
                ?>        
            </div>
            
            <!-- ==================== END - DASHBOARD MATERIALS PERCENTAGES ROW ==================== -->

            <hr>

            <!-- ==================== START - DASHBOARD PRODUCTS PERCENTAGES TITLE ROW ==================== -->

            <div class="row">
                <div class="col-12">
                    <p class="lead">
                        Percentage of Products
                    </p>
                </div>
            </div>

            <!-- ==================== END - DASHBOARD PRODUCTS PERCENTAGES TITLE ROW ==================== -->

            <!-- ==================== START - DASHBOARD PRODUCTS PERCENTAGES ROW ==================== -->

            <div class="row row-cols-1 row-cols-lg-3 g-2">
                <?php
                    $productNameTable = $database->query("SELECT * FROM product_name_tbl ORDER BY p_name ASC");
                    while ($productNameTableRows = $productNameTable->fetch_assoc()){
                        $productNameGroupedCount = 0;
                        $productPercentage = 0;
                        $productNameID = $productNameTableRows['ID'];
                        $productsTableCount = $database->query("SELECT COUNT(*) AS products_count FROM products_tbl WHERE p_name = '$productNameID' AND p_quality = 'Good' AND p_status != 'Sold'")->fetch_assoc()['products_count'];
                        $productsTableCountGrouped = $database->query("SELECT COUNT(*) AS products_grouped_count FROM products_tbl WHERE p_name = '$productNameID' AND p_quality = 'Good' AND p_status != 'Sold' GROUP BY p_name");
                        if ($productsTableCountGrouped->num_rows > 0) {
                            while ($productsTableCountGroupedRows = $productsTableCountGrouped->fetch_assoc()) {
                                $productNameGroupedCount = $productNameGroupedCount + $maxStorage;
                            }
                            $productPercentage = ($productsTableCount / $productNameGroupedCount) * 100;
                        }
                        else {
                            $productPercentage = 0;
                        }
                        ?>
                            <div class="col">
                                <div class="card bg-light">
                                    <div class='row m-0 p-2
                                        <?php
                                            if ($productPercentage > 75) {
                                                echo "border-top-success";
                                            }
                                            else if ($productPercentage > 50) {
                                                echo "border-top-warning";
                                            }
                                            else if ($productPercentage > 25) {
                                                echo "border-top-orange";
                                            }
                                            else if ($productPercentage > 0) {
                                                echo "border-top-danger";
                                            }
                                            else if ($productPercentage == 0) {
                                                echo "border-top-gray";
                                            }
                                        ?> rounded diagonal-img'>
                                        <img src='images/items/<?php echo $productNameTableRows['p_photo']?>'>
                                        <div class='col text-light p-2'>
                                            <div class='text-center'>
                                                <div class='progressbar'>
                                                    <div class='circle' data-percent='<?php echo $productPercentage;?>'>
                                                        <strong class='lead fw-bold'></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class='col d-flex align-items-center p-2'>
                                            <div>
                                                <p class='fs-4 m-0' style='text-shadow: -1px -1px 0 #f8f9fa, 1px -1px 0 #f8f9fa, -1px 1px 0 #f8f9fa, 1px 1px 0 #f8f9fa;'>
                                                    <?php echo $productNameTableRows['p_name']?>
                                                </p>
                                                <p class='m-0 fw-bold' style='text-shadow: -1px -1px 0 #f8f9fa, 1px -1px 0 #f8f9fa, -1px 1px 0 #f8f9fa, 1px 1px 0 #f8f9fa;'>
                                                    <?php 
                                                        echo $productsTableCount." Piece";
                                                        if ($productsTableCount > 1) {
                                                            echo "s"; 
                                                        }
                                                        echo " out of ".$maxStorage;
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                    }
                ?>        
            </div>
            
            <!-- ==================== END - DASHBOARD PRODUCTS PERCENTAGES ROW ==================== -->

            <?php
                if ($_SESSION['position'] != 'User') {
                    ?>
                        <hr>

                        <!-- ==================== START - DASHBOARD STATISTICS TITLE ROW ==================== -->

                        <div class="row">
                            <div class="col-12">
                                <p class="lead">
                                    Statistics
                                </p>
                            </div>
                        </div>

                        <!-- ==================== END - DASHBOARD STATISTICS TITLE ROW ==================== -->

                        <!-- ==================== START - DASHBOARD STATISTICS ROW ==================== -->

                        <div class="row">
                            <div class="col-sm-12 col-lg-6">
                                <canvas id="materialsCountChart" class="w-100"></canvas>
                            </div>
                            <div class="col-sm-12 col-lg-6">
                                <canvas id="productsCountChart" class="w-100"></canvas>
                            </div>
                        </div>

                        <!-- ==================== END - DASHBOARD STATISTICS ROW ==================== -->

                    <?php
                }
            ?>
        </div>
    </div>

    <!-- ==================== END - MAIN CONTENT ==================== -->

    <script src="js/jquery.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="js/charts/dist/chart.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.js"></script>
    <script src="js/progress.js"></script>
    <script src="js/PassRequirements.js"></script>
    <script src="js/script.js"></script>
    <script>
        <?php $currentTime = date("Y-m-d H:i:s"); ?>

        <?php
            if ($_SESSION['position'] != 'User') {
                ?>
                    /* ==================== START - INITIALIZATION OF CHARTS ==================== */

                    /* ==================== START - MATERIALS COUNT CHART ==================== */
                    
                    var ctx = document.getElementById('materialsCountChart').getContext('2d');
                    var delayed;
                    var myChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['<?php echo date("M j")."', '".date("M j",strtotime("-1 days"))."', '".date("M j",strtotime("-2 days"))."', '".date("M j",strtotime("-3 days"))."', '".date("M j",strtotime("-4 days"))."', '".date("M j",strtotime("-5 days"))."', '".date("M j",strtotime("-6 days")); ?>'],
                            datasets: [
                                {
                                    label: "Income",
                                    data: [
                                        <?php 
                                            $incomeCountDay1 = 0;
                                            $incomeCountDay2 = 0;
                                            $incomeCountDay3 = 0;
                                            $incomeCountDay4 = 0;
                                            $incomeCountDay5 = 0;
                                            $incomeCountDay6 = 0;
                                            $incomeCountDay7 = 0;

                                            $projectsTableChartCounter = $database->query("SELECT * FROM projects_tbl WHERE proj_status = 'Completed'");
                                            while ($projectsTableChartCounterRow = $projectsTableChartCounter->fetch_assoc()) {
                                                $projectID = $projectsTableChartCounterRow['ID'];
                                                if (date("m d", strtotime($projectsTableChartCounterRow['proj_rejected'])) == date("m d")) {
                                                    $productsUsedTable1 = $database->query("SELECT p_price, p_sold_to FROM product_used_tbl WHERE p_sold_to = '$projectID'");
                                                    while ($productsUsedTable1Rows = $productsUsedTable1->fetch_assoc()) {
                                                        $incomeCountDay1 = $incomeCountDay1 + $productsUsedTable1Rows['p_price'];
                                                    }
                                                }
                                                else if (date("m d", strtotime($projectsTableChartCounterRow['proj_rejected'])) == date("m d",strtotime("-1 days"))) {
                                                    $productsUsedTable2 = $database->query("SELECT p_price, p_sold_to FROM product_used_tbl WHERE p_sold_to = '$projectID'");
                                                    while ($productsUsedTable2Rows = $productsUsedTable2->fetch_assoc()) {
                                                        $incomeCountDay2 = $incomeCountDay2 + $productsUsedTable2Rows['p_price'];
                                                    }
                                                }
                                                else if (date("m d", strtotime($projectsTableChartCounterRow['proj_rejected'])) == date("m d",strtotime("-2 days"))) {
                                                    $productsUsedTable3 = $database->query("SELECT p_price, p_sold_to FROM product_used_tbl WHERE p_sold_to = '$projectID'");
                                                    while ($productsUsedTable3Rows = $productsUsedTable3->fetch_assoc()) {
                                                        $incomeCountDay3 = $incomeCountDay3 + $productsUsedTable3Rows['p_price'];
                                                    }
                                                }
                                                else if (date("m d", strtotime($projectsTableChartCounterRow['proj_rejected'])) == date("m d",strtotime("-3 days"))) {
                                                    $productsUsedTable4= $database->query("SELECT p_price, p_sold_to FROM product_used_tbl WHERE p_sold_to = '$projectID'");
                                                    while ($productsUsedTable4Rows = $productsUsedTable4->fetch_assoc()) {
                                                        $incomeCountDay4 = $incomeCountDay4 + $productsUsedTable4Rows['p_price'];
                                                    }
                                                }
                                                else if (date("m d", strtotime($projectsTableChartCounterRow['proj_rejected'])) == date("m d",strtotime("-4 days"))) {
                                                    $productsUsedTable5 = $database->query("SELECT p_price, p_sold_to FROM product_used_tbl WHERE p_sold_to = '$projectID'");
                                                    while ($productsUsedTable5Rows = $productsUsedTable5->fetch_assoc()) {
                                                        $incomeCountDay5 = $incomeCountDay5 + $productsUsedTable5Rows['p_price'];
                                                    }
                                                }
                                                else if (date("m d", strtotime($projectsTableChartCounterRow['proj_rejected'])) == date("m d",strtotime("-5 days"))) {
                                                    $productsUsedTable6 = $database->query("SELECT p_price, p_sold_to FROM product_used_tbl WHERE p_sold_to = '$projectID'");
                                                    while ($productsUsedTable6Rows = $productsUsedTable6->fetch_assoc()) {
                                                        $incomeCountDay6 = $incomeCountDay6 + $productsUsedTable6Rows['p_price'];
                                                    }
                                                }
                                                else if (date("m d", strtotime($projectsTableChartCounterRow['proj_rejected'])) == date("m d",strtotime("-6 days"))) {
                                                    $productsUsedTable7 = $database->query("SELECT p_price, p_sold_to FROM product_used_tbl WHERE p_sold_to = '$projectID'");
                                                    while ($productsUsedTable7Rows = $productsUsedTable7->fetch_assoc()) {
                                                        $incomeCountDay7 = $incomeCountDay7 + $productsUsedTable7Rows['p_price'];
                                                    }
                                                }
                                            }
                                            echo $incomeCountDay1.", ".$incomeCountDay2.", ".$incomeCountDay3.", ".$incomeCountDay4.", ".$incomeCountDay5.", ".$incomeCountDay6.", ".$incomeCountDay7;
                                        ?>
                                    ],
                                    backgroundColor: [
                                        "#198754"
                                    ]
                                },
                                {
                                    label: "Expenses",
                                    data: [
                                        <?php 
                                            $expensesCountDay1 = 0;
                                            $expensesCountDay2 = 0;
                                            $expensesCountDay3 = 0;
                                            $expensesCountDay4 = 0;
                                            $expensesCountDay5 = 0;
                                            $expensesCountDay6 = 0;
                                            $expensesCountDay7 = 0;
                                            $materialsTableChartCounter = $database->query("SELECT * FROM materials_tbl");
                                            while ($materialsTableChartCounterRow = $materialsTableChartCounter->fetch_assoc()) {
                                                
                                                
                                                if (date("m d", strtotime($materialsTableChartCounterRow['m_received'])) == date("m d")) {
                                                    $expensesCountDay1 = $expensesCountDay1 + $materialsTableChartCounterRow['m_price'];
                                                }
                                                else if (date("m d", strtotime($materialsTableChartCounterRow['m_received'])) == date("m d",strtotime("-1 days"))) {
                                                    $expensesCountDay2 = $expensesCountDay2 + $materialsTableChartCounterRow['m_price'];
                                                }
                                                else if (date("m d", strtotime($materialsTableChartCounterRow['m_received'])) == date("m d",strtotime("-2 days"))) {
                                                    $expensesCountDay3 = $expensesCountDay3 + $materialsTableChartCounterRow['m_price'];
                                                }
                                                else if (date("m d", strtotime($materialsTableChartCounterRow['m_received'])) == date("m d",strtotime("-3 days"))) {
                                                    $expensesCountDay4 = $expensesCountDay4 + $materialsTableChartCounterRow['m_price'];
                                                }
                                                else if (date("m d", strtotime($materialsTableChartCounterRow['m_received'])) == date("m d",strtotime("-4 days"))) {
                                                    $expensesCountDay5 = $expensesCountDay5 + $materialsTableChartCounterRow['m_price'];
                                                }
                                                else if (date("m d", strtotime($materialsTableChartCounterRow['m_received'])) == date("m d",strtotime("-5 days"))) {
                                                    $expensesCountDay6 = $expensesCountDay6 + $materialsTableChartCounterRow['m_price'];
                                                }
                                                else if (date("m d", strtotime($materialsTableChartCounterRow['m_received'])) == date("m d",strtotime("-6 days"))) {
                                                    $expensesCountDay7 = $expensesCountDay7 + $materialsTableChartCounterRow['m_price'];
                                                }
                                            }
                                            echo $expensesCountDay1.", ".$expensesCountDay2.", ".$expensesCountDay3.", ".$expensesCountDay4.", ".$expensesCountDay5.", ".$expensesCountDay6.", ".$expensesCountDay7;
                                        ?>
                                    ],
                                    backgroundColor: [
                                        "#dc3545"
                                    ]
                                }
                            ]


                        },
                        options: {
                            animation: {
                                onComplete: () => {
                                    delayed = true;
                                },
                                delay: (context) => {
                                    let delay = 0;
                                    if (context.type === 'data' && context.mode === 'default' && !delayed) {
                                        delay = context.dataIndex * 700 + context.datasetIndex * 100;
                                    }
                                    return delay;
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    position: "top",
                                    text: "Sales",
                                }
                            }
                        }
                    });

                    /* ==================== END - MATERIALS COUNT CHART ==================== */

                    /* ==================== START - PRODUCTS COUNT CHART ==================== */

                    var ctx = document.getElementById('productsCountChart').getContext('2d');
                    var delayed;
                    var myChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['<?php echo date("M j")."', '".date("M j",strtotime("-1 days"))."', '".date("M j",strtotime("-2 days"))."', '".date("M j",strtotime("-3 days"))."', '".date("M j",strtotime("-4 days"))."', '".date("M j",strtotime("-5 days"))."', '".date("M j",strtotime("-6 days")); ?>'],
                            datasets: [{
                                data: [
                                    <?php 
                                        $productCountDay1 = 0;
                                        $productCountDay2 = 0;
                                        $productCountDay3 = 0;
                                        $productCountDay4 = 0;
                                        $productCountDay5 = 0;
                                        $productCountDay6 = 0;
                                        $productCountDay7 = 0;
                                        $productsTableChartCounter = $database->query("SELECT * FROM products_tbl");
                                        while ($productsTableChartCounterRow = $productsTableChartCounter->fetch_assoc()) {
                                            if (date("m d", strtotime($productsTableChartCounterRow['p_completed'])) == date("m d")) {
                                                $productCountDay1++;
                                            }
                                            else if (date("m d", strtotime($productsTableChartCounterRow['p_completed'])) == date("m d",strtotime("-1 days"))) {
                                                $productCountDay2++;
                                            }
                                            else if (date("m d", strtotime($productsTableChartCounterRow['p_completed'])) == date("m d",strtotime("-2 days"))) {
                                                $productCountDay3++;
                                            }
                                            else if (date("m d", strtotime($productsTableChartCounterRow['p_completed'])) == date("m d",strtotime("-3 days"))) {
                                                $productCountDay4++;
                                            }
                                            else if (date("m d", strtotime($productsTableChartCounterRow['p_completed'])) == date("m d",strtotime("-4 days"))) {
                                                $productCountDay5++;
                                            }
                                            else if (date("m d", strtotime($productsTableChartCounterRow['p_completed'])) == date("m d",strtotime("-5 days"))) {
                                                $productCountDay6++;
                                            }
                                            else if (date("m d", strtotime($productsTableChartCounterRow['p_completed'])) == date("m d",strtotime("-6 days"))) {
                                                $productCountDay7++;
                                            }
                                        }
                                        echo $productCountDay1.", ".$productCountDay2.", ".$productCountDay3.", ".$productCountDay4.", ".$productCountDay5.", ".$productCountDay6.", ".$productCountDay7;
                                    ?>
                                ],
                                backgroundColor: [
                                    '#0d6efd'
                                ]
                            }]
                        },
                        options: {
                            animation: {
                                onComplete: () => {
                                    delayed = true;
                                },
                                delay: (context) => {
                                    let delay = 0;
                                    if (context.type === 'data' && context.mode === 'default' && !delayed) {
                                        delay = context.dataIndex * 700 + context.datasetIndex * 100;
                                    }
                                    return delay;
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Products Manufactured'
                                }
                            }
                        }
                    });

                    /* ==================== END - PRODUCTS COUNT CHART ==================== */

                    /* ==================== END - INITIALIZATION OF CHARTS ==================== */
                <?php
            }
        ?>

        $(document).ready(function() {
            setInterval(liveClock, 1000);
        });

        function liveClock() {
            $.ajax({
                url: 'include/liveclock.php',
                success: function(data) {
                    $('#liveClock').html(data);
                },
            });
        }

    </script>
</body>
</html>