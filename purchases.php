<?php 
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "purchases";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================
    
    // ==================== START - QUERIES ====================

    $sqlPurchaseGroupTableOngoing = "SELECT purchase_group_tbl.ID, purchase_group_tbl.pg_code, purchase_group_tbl.pg_supplier, purchase_group_tbl.pg_representative, representatives_tbl.rep_firstname, representatives_tbl.rep_lastname, purchase_group_tbl.pg_date, purchase_group_tbl.pg_status
                              FROM purchase_group_tbl 
                              JOIN representatives_tbl ON purchase_group_tbl.pg_representative = representatives_tbl.ID
                              WHERE pg_status != 'Deleted' AND pg_status != 'Completed'";
    $purchaseGroupOngoing = $database->query($sqlPurchaseGroupTableOngoing) or die ($database->error);

    $sqlPurchaseGroupTableCompleted = "SELECT purchase_group_tbl.ID, purchase_group_tbl.pg_code, purchase_group_tbl.pg_supplier, purchase_group_tbl.pg_representative, representatives_tbl.rep_firstname, representatives_tbl.rep_lastname, purchase_group_tbl.pg_date, purchase_group_tbl.pg_status, purchase_group_tbl.pg_date_deleted
                              FROM purchase_group_tbl 
                              JOIN representatives_tbl ON purchase_group_tbl.pg_representative = representatives_tbl.ID
                              WHERE pg_status = 'Completed'";
    $purchaseGroupCompleted = $database->query($sqlPurchaseGroupTableCompleted) or die ($database->error);

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();
    $maxStorage = $adminData['max_storage'];
    $adminName = $adminData['first_name']." ".$adminData['last_name'];

    date_default_timezone_set('Asia/Manila');

    // ==================== END - QUERIES ====================

    // ==================== START - CREATE PURCHASE GROUP ====================

    if(isset($_POST['createPurchaseGroup'])){
        $pgSupplier = mysqli_real_escape_string($database, $_POST['inputPurchaseGroupSupplier']);
        $pgRepresentative = $_POST['selectPurchaseGroupRepresentative'];
        $pgDateCreated = date("Y-m-d H:i:s");

        $fetchPurchaseGroupTable =  $database->query("SELECT COUNT(`ID`) AS `purchaseGroupTotal` FROM `purchase_group_tbl`")->fetch_assoc();
        $pgTotalCounter = ($fetchPurchaseGroupTable['purchaseGroupTotal']) + 1;

        $pgCodeCounter = "PG-".str_pad($pgTotalCounter,5,"0",STR_PAD_LEFT);

        $sqlPurchaseGroupTable = "INSERT INTO `purchase_group_tbl`(`ID`, `pg_code`, `pg_supplier`, `pg_representative`, `pg_date`, `pg_status`, `pg_description`, `pg_date_deleted`) VALUES (NULL,'$pgCodeCounter','$pgSupplier','$pgRepresentative','$pgDateCreated','Unlocked','','0000-00-00 00:00:00')";
        $database->query($sqlPurchaseGroupTable) or die ($database->error);

        $database->query("INSERT INTO `purchase_history_tbl`(`ID`, `ph_admin`, `ph_date`, `ph_event`,`ph_method`) VALUES (NULL,'$adminID','$pgDateCreated','$pgCodeCounter','Created')") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'New Purchase Group Created!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=purchases.php");
    }

    // ==================== END - CREATE PURCHASE GROUP ====================

    // ==================== START - UNLOCK PURCHASE GROUP ====================

    if(isset($_POST['purchaseUnlock'])){
        $pgID = $_POST['inputHiddenUnlockPurchaseGroupID'];
        $currentDate = date("Y-m-d H:i:s");

        $sqlPurchaseGroupTable = "UPDATE `purchase_group_tbl` SET `pg_status`='Unlocked' WHERE `ID`='$pgID'";
        $database->query($sqlPurchaseGroupTable) or die ($database->error);

        $purchaseGroupCodeFetch = $database->query("SELECT pg_code FROM purchase_group_tbl WHERE ID = '$pgID'")->fetch_assoc();
        $database->query("INSERT INTO `purchase_history_tbl`(`ID`, `ph_admin`, `ph_date`, `ph_event`,`ph_method`) VALUES (NULL,'$adminID','$currentDate','{$purchaseGroupCodeFetch['pg_code']}','Unlocked')") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Purchase Group Unlocked!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=purchases.php");
    }

    // ==================== END - UNLOCK PURCHASE GROUP ====================

    // ==================== START - UNLOCK PURCHASE GROUP ====================

    if(isset($_POST['purchaseLock'])){
        $pgID = $_POST['inputHiddenLockPurchaseGroupID'];
        $currentDate = date("Y-m-d H:i:s");

        $sqlPurchaseGroupTable = "UPDATE `purchase_group_tbl` SET `pg_status`='Locked' WHERE `ID`='$pgID'";
        $database->query($sqlPurchaseGroupTable) or die ($database->error);

        $purchaseGroupCodeFetch = $database->query("SELECT pg_code FROM purchase_group_tbl WHERE ID = '$pgID'")->fetch_assoc();
        $database->query("INSERT INTO `purchase_history_tbl`(`ID`, `ph_admin`, `ph_date`, `ph_event`,`ph_method`) VALUES (NULL,'$adminID','$currentDate','{$purchaseGroupCodeFetch['pg_code']}','Locked')") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Purchase Group Locked!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=purchases.php");
    }

    // ==================== END - UNLOCK PURCHASE GROUP ====================

    // ==================== START - PURCHASE GROUP EDIT ====================

    if(isset($_POST['purchaseEdit'])){
        $pgID = $_POST['inputHiddenEditPurchaseGroupID'];
        $pgSupplier = mysqli_real_escape_string($database, $_POST['inputEditPurchaseGroupSupplier']);
        $pgRepresentative = $_POST['selectEditPurchaseGroupRepresentative'];
        $pgDateEdited = date("Y-m-d H:i:s");

        $representativeTableNameFetch = $database->query("SELECT rep_firstname, rep_lastname FROM representatives_tbl WHERE ID = '$pgRepresentative'")->fetch_assoc();

        $purchaseGroupTable = $database->query("SELECT purchase_group_tbl.pg_code, purchase_group_tbl.pg_supplier, purchase_group_tbl.pg_representative, representatives_tbl.rep_firstname AS rep_firstname, representatives_tbl.rep_lastname AS rep_lastname FROM purchase_group_tbl INNER JOIN representatives_tbl ON purchase_group_tbl.pg_representative=representatives_tbl.ID WHERE purchase_group_tbl.ID = '$pgID'");
        while ($purchaseGroupTableRow = $purchaseGroupTable->fetch_assoc()) {
            if ($purchaseGroupTableRow['pg_supplier'] == $pgSupplier && $purchaseGroupTableRow['pg_representative'] == $pgRepresentative) {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'info',
                            title: 'Nothing Changed',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 2000,
                        });
                    </script>
                ";
            }
            else if ($purchaseGroupTableRow['pg_supplier'] != $pgSupplier && $purchaseGroupTableRow['pg_representative'] == $pgRepresentative) {
                $database->query("INSERT INTO `purchase_history_tbl`(`ID`, `ph_admin`, `ph_date`, `ph_event`,`ph_method`) VALUES (NULL,'$adminID','$pgDateEdited','Supplier Name of {$purchaseGroupTableRow['pg_code']} from {$purchaseGroupTableRow['pg_supplier']} to $pgSupplier','Edited')") or die ($database->error);
                $database->query("UPDATE `purchase_group_tbl` SET `pg_supplier`='$pgSupplier' WHERE `ID`='$pgID'") or die ($database->error);
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Purchase Group Supplier Updated!',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 2000,
                        });
                    </script>
                ";
                
                header("Refresh:2; url=purchases.php");
            }
            else if ($purchaseGroupTableRow['pg_supplier'] == $pgSupplier && $purchaseGroupTableRow['pg_representative'] != $pgRepresentative) {
                $database->query("INSERT INTO `purchase_history_tbl`(`ID`, `ph_admin`, `ph_date`, `ph_event`,`ph_method`) VALUES (NULL,'$adminID','$pgDateEdited','Representative of {$purchaseGroupTableRow['pg_code']} from {$purchaseGroupTableRow['rep_firstname']} {$purchaseGroupTableRow['rep_lastname']} to {$representativeTableNameFetch['rep_firstname']} {$representativeTableNameFetch['rep_lastname']}','Edited')") or die ($database->error);
                $database->query("UPDATE `purchase_group_tbl` SET `pg_representative`='$pgRepresentative' WHERE `ID`='$pgID'") or die ($database->error);
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Purchase Group Representative Updated!',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 2000,
                        });
                    </script>
                ";
                
                header("Refresh:2; url=purchases.php");
            }
            else {
                $database->query("INSERT INTO `purchase_history_tbl`(`ID`, `ph_admin`, `ph_date`, `ph_event`,`ph_method`) VALUES (NULL,'$adminID','$pgDateEdited','Details of {$purchaseGroupTableRow['pg_code']}','Edited')") or die ($database->error);
                $database->query("UPDATE `purchase_group_tbl` SET `pg_supplier`='$pgSupplier',`pg_representative`='$pgRepresentative' WHERE `ID`='$pgID'") or die ($database->error);
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Purchase Group Details Updated!',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 2000,
                        });
                    </script>
                ";
                
                header("Refresh:2; url=purchases.php");
            }
        }
    }

    // ==================== END - PURCHASE GROUP EDIT ====================

    // ==================== START - PURCHASE GROUP DELETE ====================

    if(isset($_POST['purchaseDelete'])){
        $pgID = $_POST['inputHiddenDeletePurchaseGroupID'];
        $pgCode = $_POST['inputHiddenDeletePurchaseGroupCode'];
        $pgDescription = $_POST['inputHiddenDeletePurchaseGroupDescription'];
        $pgDateDeleted = date("Y-m-d H:i:s");

        $database->query("INSERT INTO `purchase_history_tbl`(`ID`, `ph_admin`, `ph_date`, `ph_event`,`ph_method`) VALUES (NULL,'$adminID','$pgDateDeleted','$pgCode','Deleted')") or die ($database->error);

        $sqlPurchaseGroupTable = "UPDATE `purchase_group_tbl` SET `pg_status`='Deleted', `pg_description`='$pgDescription', `pg_date_deleted`='$pgDateDeleted' WHERE `ID`='$pgID'";
        $database->query($sqlPurchaseGroupTable) or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Purchase Group Deleted!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=purchases.php");
    }

    // ==================== END - PURCHASE GROUP DELETE ====================

    // ==================== START - PURCHASE GROUP DELETE ====================

    if(isset($_POST['purchaseComplete'])){
        $pgID = $_POST['inputHiddenCompletePurchaseID'];
        $pgCode = $_POST['inputHiddenCompletePurchaseIDCode'];
        $pgDateCompleted = date("Y-m-d H:i:s");

        $database->query("INSERT INTO `purchase_history_tbl`(`ID`, `ph_admin`, `ph_date`, `ph_event`,`ph_method`) VALUES (NULL,'$adminID','$pgDateCompleted','$pgCode','Completed')") or die ($database->error);

        $sqlPurchaseGroupTable = "UPDATE `purchase_group_tbl` SET `pg_status`='Completed', `pg_date_deleted`='$pgDateCompleted' WHERE `ID`='$pgID'";
        $database->query($sqlPurchaseGroupTable) or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Purchase Group Completed!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=purchases.php");
    }

    // ==================== END - PURCHASE GROUP DELETE ====================

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
    <title>Purchases</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.css"/>
    <link rel="stylesheet" href="boxicons/css/boxicons.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- ==================== START - SIDE NAVIGATION ==================== -->

    <?php include "include/sidebar.php";?>

    <!-- ==================== END - SIDE NAVIGATION ==================== -->

    <!-- ==================== START - MAIN CONTENT ==================== -->

    <div class="content">
        <div class="container-fluid">

            <!-- ==================== START - PURCHASES TITLE ROW ==================== -->

            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-cart fs-3'></i>&emsp;Purchases
                    </p>
                    <button class="btn btn-sm btn-primary d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalCreatePurchaseGroup">
                        <i class='bx bx-plus fs-5'></i>&nbsp;Add Purchase Group
                    </button>
                </div>
            </div>

            <!-- ==================== END - PURCHASES TITLE ROW ==================== -->

            <!-- ==================== START - PURCHASES STATISTICS ROW ==================== -->

            <div class="row row-cols-3 g-2">
                <div class="col">
                    <div class="card mb-3 border-top-primary">
                        <div class="row g-0">
                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                <i class='bx bx-file bx-tada-hover fs-1 text-primary'></i>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-2 text-center">
                                    <h5 class="card-title fs-4">
                                        <?php
                                            $purchasesTable = $database->query("SELECT COUNT(*) AS purchase_orders_count FROM purchase_group_tbl")->fetch_array();
                                            echo $purchasesTable['purchase_orders_count'];
                                        ?>
                                    </h5>
                                    <p class="card-text">Purchase Orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-3 border-top-primary">
                        <div class="row g-0">
                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                <i class='bx bxs-cart-add bx-tada-hover fs-1 text-primary'></i>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-2 text-center">
                                    <h5 class="card-title fs-4">
                                        <?php
                                            $materialsTable = $database->query("SELECT COUNT(*) AS materials_count FROM materials_tbl")->fetch_array();
                                            echo $materialsTable['materials_count'];
                                        ?>
                                    </h5>
                                    <p class="card-text">Purchased Materials</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-3 border-top-primary">
                        <div class="row g-0">
                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                <i class='bx bx-purchase-tag bx-tada-hover fs-1 text-primary'></i>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-2 text-center">
                                    <h5 class="card-title fs-4">
                                        <?php
                                            $materialsTable = $database->query("SELECT m_price FROM materials_tbl");
                                            $accumulatedCost = 0;
                                            while ($materialsTableRows = $materialsTable->fetch_assoc()) {
                                                $accumulatedCost = $accumulatedCost + $materialsTableRows['m_price'];
                                            }
                                            echo number_format((float)$accumulatedCost, 2, '.', ',');
                                        ?>
                                    </h5>
                                    <p class="card-text">Accumulated Cost</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - PURCHASES STATISTICS ROW ==================== -->
        
            <!-- ==================== START - PURCHASE ORDERS ROW ==================== -->

            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs">
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-primary active" href="#pgOngoing" data-bs-toggle="tab">Ongoing</a>
                                </li>
                                <li class="nav-item ms-auto">
                                    <a class="nav-link text-dark border-top-success" href="#pgCompleted" data-bs-toggle="tab">Completed</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- ==================== START - ONGOING PURCHASE ORDERS TABLE ==================== -->

                                <div class="tab-pane active" id="pgOngoing">
                                    <table class="datatable-desc-1 table table-hover responsive nowrap w-100">
                                        <thead class="bg-primary text-light">
                                            <th>Purchase Code</th>
                                            <th>Supplier</th>
                                            <th>Representative</th>
                                            <th>Materials Purchased</th>
                                            <th>Total Cost (in Peso)</th>
                                            <th class="no-sort text-center">Action</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $purchaseGroupOngoing->fetch_array()){ ?>
                                                <tr class="align-middle">
                                                    <td><?php echo $fetch['pg_code']?></td>
                                                    <td><?php echo $fetch['pg_supplier']?></td>
                                                    <td><?php echo $fetch['rep_firstname']." ".$fetch['rep_lastname']?></td>
                                                    <td>
                                                        <div class="d-flex justify-content-between">
                                                            <?php
                                                                $purchaseGroupID = $fetch['ID'];
                                                                $sqlMaterialsCountTable = $database->query("SELECT COUNT(*) AS materialsCount FROM materials_tbl WHERE m_purchase_group = $purchaseGroupID")->fetch_assoc();
                                                                echo $sqlMaterialsCountTable['materialsCount'];
                                                            ?>
                                                            <button class="btn btn-sm btn-primary p-1" data-bs-toggle="modal" data-bs-target="#modalViewMaterials<?php echo $fetch['ID']?>">
                                                                <i class='bx bx-show fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="View Materials"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $purchaseGroupID = $fetch['ID'];
                                                            $mTotalPrice = 0;
                                                            $sqlMaterialsPriceTable = $database->query("SELECT m_price FROM materials_tbl WHERE m_purchase_group = $purchaseGroupID");

                                                            while ($row = $sqlMaterialsPriceTable->fetch_assoc()){
                                                                $mTotalPrice = $mTotalPrice+$row['m_price'];
                                                            }

                                                            echo number_format((float)$mTotalPrice, 2, '.', ',');
                                                        ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php
                                                            if ($fetch['pg_status'] == 'Locked') {
                                                                ?>
                                                                    <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalUnlockPurchase<?php echo $fetch['ID']?>">
                                                                        <i class='bx bx-lock-alt fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Unlock Group"></i>
                                                                    </button>
                                                                <?php 
                                                            }
                                                            else {
                                                                ?>
                                                                    <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalLockPurchase<?php echo $fetch['ID']?>">
                                                                        <i class='bx bx-lock-open-alt fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Lock Group"></i>
                                                                    </button>
                                                                <?php 
                                                            }
                                                        ?>
                                                        <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalEditPurchase<?php echo $fetch['ID']?>">
                                                            <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Group"></i>
                                                        </button>
                                                        <?php
                                                            $purchaseGroupID = $fetch['ID'];
                                                            $sqlMaterialsCountTable = $database->query("SELECT COUNT(*) AS materialsCount FROM materials_tbl WHERE m_purchase_group = $purchaseGroupID")->fetch_assoc();
                                                            if ($sqlMaterialsCountTable['materialsCount'] == 0) {
                                                                ?>
                                                                    <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalDeleteConfirmationPurchase<?php echo $fetch['ID']?>">
                                                                        <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Group"></i>
                                                                    </button>
                                                                <?php
                                                            }
                                                            else {
                                                                ?>
                                                                    <button class="btn btn-sm btn-primary p-1" data-bs-toggle="modal" data-bs-target="#modalPrintingOption<?php echo $fetch['ID']?>">
                                                                        <i class='bx bx-printer fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Printing"></i>
                                                                    </button>
                                                                    <button class="btn btn-sm btn-orange p-1" data-bs-toggle="modal" data-bs-target="#modalCompletePurchase<?php echo $fetch['ID']?>">
                                                                        <i class='bx bx-check fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Mark as Completed"></i>
                                                                    </button>
                                                                <?php
                                                            }
                                                        ?>
                                                    </td>

                                                    <!-- ==================== START - MODAL VIEW DATA ==================== -->
                                                            
                                                    <div class="modal fade" id="modalViewMaterials<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Purchase Slip (<?php echo $fetch['pg_code']?>)</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <?php
                                                                        $purchaseGroupID = $fetch['ID'];
                                                                        $sqlMaterialsTable = $database->query("SELECT materials_tbl.m_code, material_name_tbl.m_name, materials_tbl.m_price, materials_tbl.m_unit, materials_tbl.m_measurement, materials_tbl.m_status FROM materials_tbl JOIN material_name_tbl ON materials_tbl.m_name=material_name_tbl.ID WHERE m_purchase_group = $purchaseGroupID ORDER BY materials_tbl.m_code DESC");
                                                                        if ($sqlMaterialsTable->num_rows > 0) {
                                                                            ?>
                                                                                <div class="row">
                                                                                    <div class="col fw-bold text-center">
                                                                                        Code
                                                                                    </div>
                                                                                    <div class="col fw-bold text-center">
                                                                                        Name
                                                                                    </div>
                                                                                    <div class="col fw-bold text-center">
                                                                                        Price
                                                                                    </div>
                                                                                    <div class="col fw-bold text-center">
                                                                                        Quantity
                                                                                    </div>
                                                                                    <div class="col fw-bold text-center">
                                                                                        Status
                                                                                    </div>
                                                                                </div>
                                                                            <?php
                                                                            $materialCount = 0;
                                                                            while ($row = $sqlMaterialsTable->fetch_assoc()){
                                                                                $materialCount++;
                                                                                ?>
                                                                                    <div class='row'>
                                                                                        <div class='col text-center'>
                                                                                            <?php echo $materialCount.".&emsp;".$row['m_code']?>
                                                                                        </div>
                                                                                        <div class='col text-center'>
                                                                                            <?php echo $row['m_name']?>
                                                                                        </div>
                                                                                        <div class='col text-center'>
                                                                                            ₱<?php echo number_format((float)$row['m_price'], 2, '.', ',')?>
                                                                                        </div>
                                                                                        <div class='col text-center'>
                                                                                            <?php 
                                                                                                echo $row['m_measurement']." ";

                                                                                                if ($row['m_measurement'] > 1) {
                                                                                                    echo $row['m_unit']."s";
                                                                                                }
                                                                                                else {
                                                                                                    echo $row['m_unit'];
                                                                                                }
                                                                                            ?>
                                                                                        </div>
                                                                                        <div class='col text-center'>
                                                                                            <?php echo $row['m_status']?>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php
                                                                            }
                                                                        }
                                                                        else {
                                                                            ?>
                                                                                <div class="text-center fs-5">
                                                                                    No Materials Involved
                                                                                </div>
                                                                            <?php
                                                                        }
                                                                    ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal" name="mEdit" id="mEdit">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL VIEW DATA ==================== -->

                                                    <!-- ==================== START - MODAL UNLOCK PURCHASE ==================== -->
                                                            
                                                    <div class="modal fade" id="modalUnlockPurchase<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <form action="" method="POST">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Purchase Group (<?php echo $fetch['pg_code']?>)</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenUnlockPurchaseGroupID" value="<?php echo $fetch['ID']?>"/>
                                                                        <label for="">Are you sure you want to unlock this purchase group? <br><br> This group will appear to the materials tab <span class="bi bi-arrow-right"></span> select purchase group.</label>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-success" name="purchaseUnlock" id="purchaseUnlock">Continue</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <!-- ==================== END - MODAL UNLOCK PURCHASE ==================== -->

                                                    <!-- ==================== START - MODAL LOCK PURCHASE ==================== -->
                                                            
                                                    <div class="modal fade" id="modalLockPurchase<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <form action="" method="POST">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Purchase Group (<?php echo $fetch['pg_code']?>)</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenLockPurchaseGroupID" value="<?php echo $fetch['ID']?>"/>
                                                                        <label for="">Are you sure you want to lock this purchase group? <br><br> This group will be hidden in the materials tab <span class="bi bi-arrow-right"></span> select purchase group.</label>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-success" name="purchaseLock" id="purchaseLock">Continue</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <!-- ==================== END - MODAL LOCK PURCHASE ==================== -->

                                                    <!-- ==================== START - MODAL EDIT PURCHASE ==================== -->
                                                            
                                                    <div class="modal fade" id="modalEditPurchase<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <form action="" method="POST">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Purchase Group (<?php echo $fetch['pg_code']?>)</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenEditPurchaseGroupID" value="<?php echo $fetch['ID']?>"/>
                                                                        <div class="mb-2">
                                                                            <label for="inputEditPurchaseGroupSupplier" class="form-label">Supplier</label>
                                                                            <input type="text" class="form-control" id="inputEditPurchaseGroupSupplier" name="inputEditPurchaseGroupSupplier" value="<?php echo $fetch['pg_supplier']?>" placeholder="Supplier Name" required>
                                                                            <div class="invalid-feedback">
                                                                                Specify the Supplier
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label for="selectEditPurchaseGroupRepresentative" class="form-label">Representative</label>
                                                                            <select class="form-select" name="selectEditPurchaseGroupRepresentative" required>
                                                                                <option value="">Choose representative</option>
                                                                                <?php 
                                                                                    $query = "SELECT ID, rep_firstname, rep_lastname FROM representatives_tbl";
                                                                                    $result = $database->query($query);
                                                                                    if($result->num_rows > 0){
                                                                                        while ($row = $result->fetch_assoc()){
                                                                                            ?>
                                                                                                <option value='<?php echo $row["ID"]?>' <?php if ($row["ID"] == $fetch["pg_representative"]) { echo "selected";}?>><?php echo $row['rep_firstname']." ".$row['rep_lastname']?></option>
                                                                                            <?php
                                                                                        }
                                                                                    }
                                                                                    else {
                                                                                        echo "<option value=''>No Sales Representative Found</option>"; 
                                                                                    }
                                                                                ?>
                                                                            </select>
                                                                            <div class="invalid-feedback">
                                                                                Choose a Sales Representative
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-success" name="purchaseEdit" id="purchaseEdit">Continue</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <!-- ==================== END - MODAL EDIT PURCHASE ==================== -->

                                                    <!-- ==================== START - MODAL DELETE PURCHASE ==================== -->
                                                            
                                                    <div class="modal fade" id="modalDeleteConfirmationPurchase<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <form action="" method="POST">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Purchase Group (<?php echo $fetch['pg_code']?>)</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <label for="">Are you sure you want to delete this purchase group?</label>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                        <button type="button" class="btn btn-success" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalDeletePurchase<?php echo $fetch['ID']?>">Continue</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <div class="modal fade" id="modalDeletePurchase<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <form action="" method="POST">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Purchase Group (<?php echo $fetch['pg_code']?>)</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenDeletePurchaseGroupID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenDeletePurchaseGroupCode" value="<?php echo $fetch['pg_code']?>"/>
                                                                        <label for="inputHiddenDeletePurchaseGroupDescription">Define your reason why you want to delete this purchase group.</label>
                                                                        <input type="text" class="form-control" name="inputHiddenDeletePurchaseGroupDescription" placeholder="eg. Accident, Human Error...">
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalDeleteConfirmationPurchase<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" name="purchaseDelete" id="purchaseDelete" data-bs-dismiss="modal">Yes, Delete It!</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <!-- ==================== END - MODAL DELETE PURCHASE ==================== -->

                                                    <!-- ==================== START - MODAL PRINTING ==================== -->

                                                    <div class="modal fade" id="modalPrintingOption<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Purchase Group (<?php echo $fetch['pg_code']?>)</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <label class="fs-6 d-flex justify-content-center mb-2">Print Material Codes</label>
                                                                    <div class="row row-cols-1 row-cols-lg-2 g-2">
                                                                        <?php
                                                                            $purchaseGroupID = $fetch['ID'];
                                                                            $materialsTablePending = $database->query("SELECT COUNT(*) AS pending_materials FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_status = 'Pending'")->fetch_assoc()['pending_materials'];
                                                                            $materialsTableStorage = $database->query("SELECT COUNT(*) AS storage_materials FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_status = 'Storage'")->fetch_assoc()['storage_materials'];
                                                                        ?>
                                                                        <div class="col">
                                                                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPurchasePendingMaterialCodes<?php echo $fetch['ID']?>" <?php if ($materialsTablePending == 0) { echo "disabled"; } ?>>Pending</button>
                                                                        </div>
                                                                        <div class="col">
                                                                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPurchaseStorageMaterialCodes<?php echo $fetch['ID']?>" <?php if ($materialsTableStorage == 0) { echo "disabled"; } ?>>Storage</button>
                                                                        </div>
                                                                        <div class="col">
                                                                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPurchaseAllMaterialCodes<?php echo $fetch['ID']?>">All</button>
                                                                        </div>
                                                                        <div class="col">
                                                                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPurchaseIndividualMaterialCodes<?php echo $fetch['ID']?>">Individual</button>
                                                                        </div>
                                                                    </div>
                                                                    <br>
                                                                    <label class="fs-6 d-flex justify-content-center mb-2">Print Material Records</label>
                                                                    <div class="row g-2 justify-content-center">
                                                                        <div class="col-6">
                                                                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPurchaseSlip<?php echo $fetch['ID']?>">Print Purchase Slip</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>   

                                                    <div class="modal fade" id="modalPrintPurchasePendingMaterialCodes<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <form action="code-canvas.php" method="POST">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Printing Pending Material Codes</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenPrintPendingPurchaseID" value="<?php echo $fetch['ID']?>"/>
                                                                        This will print all of the QR codes of the materials which have the pending status within this purchase group
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintingOption<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" id="printPurchasePendingMaterialCodes" name="printPurchasePendingMaterialCodes">Yes, Print It!</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <div class="modal fade" id="modalPrintPurchaseStorageMaterialCodes<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <form action="code-canvas.php" method="POST">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Printing Materials In Storage Codes</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenPrintStoragePurchaseID" value="<?php echo $fetch['ID']?>"/>
                                                                        This will print all of the QR codes of the materials which have the storage status within this purchase group
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintingOption<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" id="printPurchaseStorageMaterialCodes" name="printPurchaseStorageMaterialCodes">Yes, Print It!</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <div class="modal fade" id="modalPrintPurchaseAllMaterialCodes<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <form action="code-canvas.php" method="POST">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Printing All Material Codes</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenPrintAllPurchaseID" value="<?php echo $fetch['ID']?>"/>
                                                                        This will print all of the QR codes including the defective and deleted materials within this purchase group
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintingOption<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" id="printPurchaseAllMaterialCodes" name="printPurchaseAllMaterialCodes">Yes, Print It!</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <div class="modal fade" id="modalPrintPurchaseIndividualMaterialCodes<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                            <form action="code-canvas.php" method="POST">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Printing Individual Material Codes</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenPrintIndividualPurchaseID" value="<?php echo $fetch['ID']?>"/>
                                                                        <label class="d-flex justify-content-center">Choose the material you want to print the codes. </label>
                                                                        <br>
                                                                        <?php
                                                                            $purchaseGroupID = $fetch['ID'];
                                                                            $sqlMaterialsTable = $database->query("SELECT materials_tbl.ID, materials_tbl.m_code, material_name_tbl.m_name, materials_tbl.m_price, materials_tbl.m_unit, materials_tbl.m_measurement, materials_tbl.m_status FROM materials_tbl JOIN material_name_tbl ON materials_tbl.m_name=material_name_tbl.ID WHERE m_purchase_group = $purchaseGroupID AND m_quality = 'Good' ORDER BY materials_tbl.m_code DESC");
                                                                            if ($sqlMaterialsTable->num_rows > 0) {
                                                                                ?>
                                                                                    <div class="row">
                                                                                        <div class="col fw-bold text-center">
                                                                                            Code
                                                                                        </div>
                                                                                        <div class="col fw-bold text-center">
                                                                                            Name
                                                                                        </div>
                                                                                        <div class="col fw-bold text-center">
                                                                                            Price
                                                                                        </div>
                                                                                        <div class="col fw-bold text-center">
                                                                                            Quantity
                                                                                        </div>
                                                                                        <div class="col fw-bold text-center">
                                                                                            Status
                                                                                        </div>
                                                                                    </div>
                                                                                <?php
                                                                                while ($row = $sqlMaterialsTable->fetch_assoc()){
                                                                                    ?>
                                                                                        <div class='row'>
                                                                                            <div class='col text-center'>
                                                                                                <div class="form-check">
                                                                                                    <input class="form-check-input" type="checkbox" value="<?php echo $row['ID']?>" name="materialChoice[]" id="checkBoxPrintPurchaseCodeIndividual<?php echo $row['ID']?>">
                                                                                                    <label class="form-check-label" for="checkBoxPrintPurchaseCodeIndividual<?php echo $row['ID']?>"><?php echo $row['m_code'];?></label>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class='col text-center'>
                                                                                                <?php echo $row['m_name']?>
                                                                                            </div>
                                                                                            <div class='col text-center'>
                                                                                                ₱<?php echo number_format((float)$row['m_price'], 2, '.', ',')?>
                                                                                            </div>
                                                                                            <div class='col text-center'>
                                                                                                <?php 
                                                                                                    echo $row['m_measurement']." ";

                                                                                                    if ($row['m_measurement'] > 1) {
                                                                                                        echo $row['m_unit']."s";
                                                                                                    }
                                                                                                    else {
                                                                                                        echo $row['m_unit'];
                                                                                                    }
                                                                                                ?>
                                                                                            </div>
                                                                                            <div class='col text-center'>
                                                                                                <?php echo $row['m_status']?>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php
                                                                                }
                                                                            }
                                                                            else {
                                                                                ?>
                                                                                    <div class="text-center fs-5">
                                                                                        No Materials Involved
                                                                                    </div>
                                                                                <?php
                                                                            }
                                                                        ?>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintingOption<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" id="printPurchaseIndividualMaterialCodes" name="printPurchaseIndividualMaterialCodes">Continue</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="modal fade" id="modalPrintPurchaseSlip<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <form action="canvas.php" method="POST">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Printing Purchase Slip</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenPrintPurchaseSlipID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenPrintPurchaseSlipCode" value="<?php echo $fetch['pg_code']?>"/>
                                                                        <input type="hidden" name="inputHiddenPrintPurchaseSlipSupplier" value="<?php echo $fetch['pg_supplier']?>"/>
                                                                        <input type="hidden" name="inputHiddenPrintPurchaseSlipRepresentative" value="<?php echo $fetch['rep_firstname']." ".$fetch['rep_lastname']?>"/>
                                                                        <input type="hidden" name="inputHiddenPrintPurchaseSlipDate" value="<?php echo $fetch['pg_date']?>"/>
                                                                        This will print the purchase slip of this whole purchase group.
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintingOption<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" id="printPurchaseSlip" name="printPurchaseSlip" data-bs-dismiss="modal">Yes, Print It!</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL PRINTING ==================== -->

                                                    <!-- ==================== START - MODAL COMPLETE PURCHASE ==================== -->

                                                    <div class="modal fade" id="modalCompletePurchase<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <form action="" method="POST">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Complete Purchase</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenCompletePurchaseID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenCompletePurchaseIDCode" value="<?php echo $fetch['pg_code']?>"/>
                                                                        Do you want to mark this purchase order as completed?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintingOption<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" id="purchaseComplete" name="purchaseComplete" data-bs-dismiss="modal">Yes, Mark It!</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL COMPLETE PURCHASE ==================== -->

                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - ONGOING PURCHASE ORDERS TABLE ==================== -->

                                <!-- ==================== START - COMPLETED PURCHASE ORDERS TABLE ==================== -->

                                <div class="tab-pane" id="pgCompleted">
                                    <div class="row mb-3">
                                        <div class="col d-flex justify-content-end">
                                            <button class="btn btn-sm btn-danger d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalPrintRecords">
                                                <i class='bx bx-printer fs-5'></i>&nbsp;Print Records
                                            </button>
                                        </div>
                                    </div>
                                    <div class="modal fade" id="modalPrintRecords" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form action="canvas.php" method="POST">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Specify dates</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row row-cols-1 row-cols-lg-2 g-2">
                                                            <div class="col">
                                                                <label class="form-label">Start Date</label>
                                                                <input type="date" class="form-control" id="purchasesRecordStartDate" name="purchasesRecordStartDate" required>
                                                            </div>
                                                            <div class="col">
                                                                <label class="form-label">End Date</label>
                                                                <input type="date" class="form-control" id="purchasesRecordEndDate" name="purchasesRecordEndDate" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary" id="purchasesPrintCompletedRecords" name="purchasesPrintCompletedRecords">Proceed</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <table class="datatable-desc-1 table table-hover responsive nowrap w-100">
                                        <thead class="bg-success text-light">
                                            <th>Purchase Code</th>
                                            <th>Supplier</th>
                                            <th>Representative</th>
                                            <th>Materials Purchased</th>
                                            <th>Total Cost (in Peso)</th>
                                            <th>Date Completed</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $purchaseGroupCompleted->fetch_array()){ ?>
                                                <tr class="align-middle">
                                                    <td><?php echo $fetch['pg_code']?></td>
                                                    <td><?php echo $fetch['pg_supplier']?></td>
                                                    <td><?php echo $fetch['rep_firstname']." ".$fetch['rep_lastname']?></td>
                                                    <td>
                                                        <div class="d-flex justify-content-between">
                                                            <?php
                                                                $purchaseGroupID = $fetch['ID'];
                                                                $sqlMaterialsCountTable = $database->query("SELECT COUNT(*) AS materialsCount FROM materials_tbl WHERE m_purchase_group = $purchaseGroupID")->fetch_assoc();
                                                                echo $sqlMaterialsCountTable['materialsCount'];
                                                            ?>
                                                            <button class="btn btn-sm btn-primary p-1" data-bs-toggle="modal" data-bs-target="#modalViewMaterials<?php echo $fetch['ID']?>">
                                                                <i class='bx bx-show fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="View Materials"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $purchaseGroupID = $fetch['ID'];
                                                            $mTotalPrice = 0;
                                                            $sqlMaterialsPriceTable = $database->query("SELECT m_price FROM materials_tbl WHERE m_purchase_group = $purchaseGroupID");

                                                            while ($row = $sqlMaterialsPriceTable->fetch_assoc()){
                                                                $mTotalPrice = $mTotalPrice+$row['m_price'];
                                                            }

                                                            echo number_format((float)$mTotalPrice, 2, '.', ',');
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('F j, Y', strtotime($fetch['pg_date_deleted']))?></td>    

                                                    <!-- ==================== START - MODAL VIEW DATA ==================== -->
                                                            
                                                    <div class="modal fade" id="modalViewMaterials<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Purchase Slip (<?php echo $fetch['pg_code']?>)</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <?php
                                                                        $purchaseGroupID = $fetch['ID'];
                                                                        $sqlMaterialsTable = $database->query("SELECT materials_tbl.m_code, material_name_tbl.m_name, materials_tbl.m_price, materials_tbl.m_unit, materials_tbl.m_measurement FROM materials_tbl JOIN material_name_tbl ON materials_tbl.m_name=material_name_tbl.ID WHERE m_purchase_group = $purchaseGroupID ORDER BY materials_tbl.m_code DESC");
                                                                        if ($sqlMaterialsTable->num_rows > 0) {
                                                                            ?>
                                                                                <div class="row">
                                                                                    <div class="col fw-bold text-center">
                                                                                        Code
                                                                                    </div>
                                                                                    <div class="col fw-bold text-center">
                                                                                        Name
                                                                                    </div>
                                                                                    <div class="col fw-bold text-center">
                                                                                        Price
                                                                                    </div>
                                                                                    <div class="col fw-bold text-center">
                                                                                        Quantity
                                                                                    </div>
                                                                                </div>
                                                                            <?php
                                                                            $materialCount = 0;
                                                                            while ($row = $sqlMaterialsTable->fetch_assoc()){
                                                                                $materialCount++;
                                                                                ?>
                                                                                    <div class='row'>
                                                                                        <div class='col text-center'>
                                                                                            <?php echo $materialCount.".&emsp;".$row['m_code']?>
                                                                                        </div>
                                                                                        <div class='col text-center'>
                                                                                            <?php echo $row['m_name']?>
                                                                                        </div>
                                                                                        <div class='col text-center'>
                                                                                            ₱<?php echo number_format((float)$row['m_price'], 2, '.', ',')?>
                                                                                        </div>
                                                                                        <div class='col text-center'>
                                                                                            <?php 
                                                                                                echo $row['m_measurement']." ";

                                                                                                if ($row['m_measurement'] > 1) {
                                                                                                    echo $row['m_unit']."s";
                                                                                                }
                                                                                                else {
                                                                                                    echo $row['m_unit'];
                                                                                                }
                                                                                            ?>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php
                                                                            }
                                                                        }
                                                                        else {
                                                                            ?>
                                                                                <div class="text-center fs-5">
                                                                                    No Materials Involved
                                                                                </div>
                                                                            <?php
                                                                        }
                                                                    ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal" name="mEdit" id="mEdit">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL VIEW DATA ==================== -->

                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - COMPLETED PURCHASE ORDERS TABLE ==================== -->

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - PURCHASE ORDERS ROW ==================== -->
            
            <!-- ==================== START - CREATE NEW PURCHASE GROUP MODAL ==================== -->

            <form action="" method="POST" class="needs-validation" novalidate>
                <div class="modal fade" id="modalCreatePurchaseGroup" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Create New Purchase Group</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label for="inputPurchaseGroupSupplier" class="form-label">Supplier</label>
                                    <input type="text" class="form-control" id="inputPurchaseGroupSupplier" name="inputPurchaseGroupSupplier" placeholder="Supplier Name" required>
                                    <div class="invalid-feedback">
                                        Specify the Supplier
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="inputPurchaseGroupRepresentative" class="form-label">Representative</label>
                                    <select class="form-select" name="selectPurchaseGroupRepresentative" required>
                                        <option value="">Choose representative</option>
                                        <?php 
                                            $query = "SELECT * FROM representatives_tbl";
                                            $result = $database->query($query);
                                            if($result->num_rows > 0){
                                                while ($row = $result->fetch_assoc()){
                                                    echo "<option value='{$row["ID"]}'>{$row['rep_firstname']} {$row['rep_lastname']}</option>";
                                                }
                                            }
                                            else {
                                                echo "<option value=''>No Sales Representative Found</option>"; 
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Choose a Sales Representative
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" id="createPurchaseGroup" name="createPurchaseGroup" class="btn btn-primary w-100">Create</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- ==================== END - CREATE NEW PURCHASE GROUP MODAL ==================== -->
            
        </div>
    </div>

    <!-- ==================== END - MAIN CONTENT ==================== -->
    
    <script src="js/jquery.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.js"></script>
    <script src="js/PassRequirements.js"></script>
    <script src="js/script.js"></script>
</body>
</html>