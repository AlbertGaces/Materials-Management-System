<?php
    // ==================== START - SESSION INITIALIZATION ====================
    
    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "archives";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $sqlMaterialsArchiveTable = "SELECT 
                                    materials_tbl.ID,
                                    purchase_group_tbl.pg_code,
                                    materials_tbl.m_code,
                                    material_name_tbl.m_name, 
                                    material_type_tbl.m_type,
                                    materials_tbl.m_price,
                                    materials_tbl.m_measurement,
                                    materials_tbl.m_remaining,
                                    materials_tbl.m_unit,
                                    materials_tbl.m_received,
                                    materials_tbl.m_rejected,
                                    materials_tbl.m_description
                                FROM materials_tbl
                                INNER JOIN material_type_tbl ON materials_tbl.m_type=material_type_tbl.ID
                                INNER JOIN material_name_tbl ON materials_tbl.m_name=material_name_tbl.ID 
                                INNER JOIN purchase_group_tbl ON materials_tbl.m_purchase_group=purchase_group_tbl.ID
                                WHERE `m_quality` = 'Trash'";
    $materialsArchive = $database->query($sqlMaterialsArchiveTable) or die ($database->error);

    $sqlProductsArchiveTable = "SELECT 
                                    products_tbl.ID,
                                    products_tbl.p_code, 
                                    product_name_tbl.p_name, 
                                    products_tbl.p_price, 
                                    products_tbl.p_measurement, 
                                    products_tbl.p_remaining,
                                    products_tbl.p_completed, 
                                    products_tbl.p_rejected, 
                                    products_tbl.p_description
                                FROM `products_tbl`
                                JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID
                                WHERE products_tbl.p_quality = 'Trash'";
    $productsArchive = $database->query($sqlProductsArchiveTable) or die ($database->error);

    $sqlProjectsArchiveTable = "SELECT * FROM `projects_tbl` WHERE proj_status = 'Deleted'";
    $projectsArchive = $database->query($sqlProjectsArchiveTable) or die ($database->error);

    $sqlPurchasesArchiveTable = "SELECT purchase_group_tbl.ID,
                                        purchase_group_tbl.pg_code,
                                        purchase_group_tbl.pg_supplier,
                                        representatives_tbl.rep_firstname,
                                        representatives_tbl.rep_lastname,
                                        purchase_group_tbl.pg_date_deleted,
                                        purchase_group_tbl.pg_description
                                FROM `purchase_group_tbl` 
                                INNER JOIN representatives_tbl ON purchase_group_tbl.pg_representative=representatives_tbl.ID 
                                WHERE purchase_group_tbl.pg_status = 'Deleted'";
    $purchasesArchive = $database->query($sqlPurchasesArchiveTable) or die ($database->error);


    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();

    date_default_timezone_set('Asia/Manila');

    // ==================== END - QUERIES ====================

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
    <title>Archive</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.css"/>
    <link rel="stylesheet" href="boxicons/css/boxicons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ==================== START - SIDE NAVIGATION ==================== -->

    <?php include "include/sidebar.php";?>

    <!-- ==================== END - SIDE NAVIGATION ==================== -->


    <!-- ==================== START - MAIN CONTENT ==================== -->

    <div class="content">
        <div class="container-fluid">

            <!-- ==================== START - ARCHIVES TITLE ROW ==================== -->
            
            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-package fs-3'></i>&emsp;Archives
                    </p>
                    <i class='bx bx-info-circle fs-3 pointer' data-bs-toggle="tooltip" data-bs-placement="left" title="All records that are stored here will be permanently deleted after 30 days"></i>
                </div>
            </div>

            <!-- ==================== END - ARCHIVES TITLE ROW ==================== -->

            <!-- ==================== START - ARCHIVES ROW ==================== -->

            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs">
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-danger active" href="#aMaterials" data-bs-toggle="tab">Materials</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-danger" href="#aProducts" data-bs-toggle="tab">Products</a>
                                </li>
                                <li class="nav-item ms-auto">
                                    <a class="nav-link text-dark border-top-danger" href="#aPurchases" data-bs-toggle="tab">Purchases</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-danger" href="#aProjects" data-bs-toggle="tab">Projects</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                            
                                <!-- ==================== START - ARCHIVED MATERIALS ROW ==================== -->

                                <div class="tab-pane active" id="aMaterials">
                                    <table class="datatable-asc-1 table table-hover responsive nowrap w-100">
                                        <thead class="bg-danger text-light">
                                            <th>Days Left</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Price (in Peso)</th>
                                            <th>Measurement</th>
                                            <th>Received</th>
                                            <th>Deleted</th>
                                            <th>Reason</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $materialsArchive->fetch_array()){ ?>
                                                <tr class="
                                                    <?php
                                                        $currentDate = date("Y-m-d");
                                                        $deletionDate = date("Y-m-d",strtotime("+30 days",strtotime($fetch['m_rejected'])));
                                                        $daysLeft = abs(strtotime($currentDate) - strtotime($deletionDate))/(60 * 60 * 24);

                                                        if ($daysLeft <= 1) {
                                                            echo "table-row-danger";
                                                        }
                                                    ?>">
                                                    <td>
                                                        <?php 
                                                            if ($daysLeft > 1) {
                                                                echo $daysLeft." Days";
                                                            }
                                                            else {
                                                                echo "1 Day";
                                                            }
                                                        ?>
                                                    </td>
                                                    <td><?php echo $fetch['m_code']?></td>
                                                    <td><?php echo $fetch['m_name']?></td>
                                                    <td><?php echo number_format((float)$fetch['m_price'], 2, '.', '');?></td>
                                                    <td>
                                                        <?php 
                                                            echo $fetch['m_measurement'] . ' ' . $fetch['m_unit'];
                                                            if ($fetch['m_measurement'] > 1) {
                                                                echo "s";
                                                            }
                                                        ?>
                                                        <br>
                                                        <?php 
                                                            if ($fetch['m_measurement'] != $fetch['m_remaining']) {
                                                                echo $fetch['m_remaining'] . ' ' . $fetch['m_unit'];
                                                                if ($fetch['m_measurement'] > 0) {
                                                                    echo "s";
                                                                }
                                                                echo " (Remaining)";
                                                            }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('F j, Y', strtotime($fetch['m_received']))?></td>
                                                    <td><?php echo date('F j, Y', strtotime($fetch['m_rejected']))?></td>
                                                    <td><?php echo $fetch['m_description']?></td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - ARCHIVED MATERIALS ROW ==================== -->

                                <!-- ==================== START - ARCHIVED PRODUCTS ROW ==================== -->

                                <div class="tab-pane" id="aProducts">
                                    <table class="datatable-desc-1 table table-hover responsive nowrap w-100">
                                        <thead class="bg-danger text-light">
                                            <th>Days Left</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Price (in Peso)</th>
                                            <th>Measurement</th>
                                            <th>Received</th>
                                            <th>Deleted</th>
                                            <th>Reason</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $productsArchive->fetch_array()){ ?>
                                                <tr class="
                                                    <?php
                                                        $currentDate = date("Y-m-d");
                                                        $deletionDate = date("Y-m-d",strtotime("+30 days",strtotime($fetch['p_rejected'])));
                                                        $daysLeft = abs(strtotime($currentDate) - strtotime($deletionDate))/(60 * 60 * 24);

                                                        if ($daysLeft <= 1) {
                                                            echo "table-row-danger";
                                                        }
                                                    ?>">
                                                    <td>
                                                        <?php 
                                                            if ($daysLeft > 1) {
                                                                echo $daysLeft." Days";
                                                            }
                                                            else {
                                                                echo "1 Day";
                                                            }
                                                        ?>
                                                    </td>
                                                    <td class="align-middle"><?php echo $fetch['p_code']?></td>
                                                    <td class="align-middle"><?php echo $fetch['p_name']?></td>
                                                    <td class="align-middle"><?php echo number_format((float)$fetch['p_price'], 2, '.', '');?></td>
                                                    <td class="align-middle">
                                                        <?php 
                                                            echo $fetch['p_measurement']." Piece";
                                                            if ($fetch['p_measurement'] > 1) {
                                                                echo "s";
                                                            }
                                                        ?>
                                                        <br>
                                                        <?php 
                                                            if ($fetch['p_measurement'] != $fetch['p_remaining']) {
                                                                echo $fetch['p_remaining']." Piece";
                                                                if ($fetch['p_remaining'] > 1) {
                                                                    echo "s";
                                                                }
                                                                echo " (Remaining)";
                                                            }
                                                        ?>
                                                    </td>
                                                    <td class="align-middle"><?php echo date('F j, Y', strtotime($fetch['p_completed']))?></td>
                                                    <td class="align-middle"><?php echo date('F j, Y', strtotime($fetch['p_rejected']))?></td>
                                                    <td class="align-middle"><?php echo $fetch['p_description']?></td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - ARCHIVED PRODUCTS ROW ==================== -->

                                <!-- ==================== START - ARCHIVED PURCHASE GROUPS ROW ==================== -->

                                <div class="tab-pane" id="aPurchases">
                                    <table class="datatable-desc-1 table table-hover responsive nowrap w-100">
                                        <thead class="bg-danger text-light">
                                            <th>Days Left</th>
                                            <th>Code</th>
                                            <th>Supplier</th>
                                            <th>Representative</th>
                                            <th>Deleted</th>
                                            <th>Reason</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $purchasesArchive->fetch_array()){ ?>
                                                <tr class="
                                                    <?php
                                                        $currentDate = date("Y-m-d");
                                                        $deletionDate = date("Y-m-d",strtotime("+30 days",strtotime($fetch['pg_date_deleted'])));
                                                        $daysLeft = abs(strtotime($currentDate) - strtotime($deletionDate))/(60 * 60 * 24);

                                                        if ($daysLeft <= 1) {
                                                            echo "table-row-danger";
                                                        }
                                                    ?>">
                                                    <td>
                                                        <?php 
                                                            if ($daysLeft > 1) {
                                                                echo $daysLeft." Days";
                                                            }
                                                            else {
                                                                echo "1 Day";
                                                            }
                                                        ?>
                                                    </td>
                                                        <td><?php echo $fetch['pg_code']?></td>
                                                        <td><?php echo $fetch['pg_supplier']?></td>
                                                        <td><?php echo $fetch['rep_firstname']." ".$fetch['rep_lastname']?></td>
                                                        <td><?php echo date('F j, Y', strtotime($fetch['pg_date_deleted']))?></td>
                                                        <td><?php echo $fetch['pg_description']?></td>
                                                    </tr>
                                                <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - ARCHIVED PURCHASE GROUPS ROW ==================== -->

                                <!-- ==================== START - ARCHIVED PROJECTS ROW ==================== -->

                                <div class="tab-pane" id="aProjects">
                                    <table class="datatable-desc-1 table table-hover responsive nowrap w-100">
                                        <thead class="bg-danger text-light">
                                            <th>Days Left</th>
                                            <th>Project ID</th>
                                            <th>Project Title</th>
                                            <th>Client Name</th>
                                            <th>Rejected</th>
                                            <th>Reason</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $projectsArchive->fetch_array()){ ?>
                                                <tr class="
                                                    <?php
                                                        $currentDate = date("Y-m-d");
                                                        $deletionDate = date("Y-m-d",strtotime("+30 days",strtotime($fetch['proj_rejected'])));
                                                        $daysLeft = abs(strtotime($currentDate) - strtotime($deletionDate))/(60 * 60 * 24);

                                                        if ($daysLeft <= 1) {
                                                            echo "table-row-danger";
                                                        }
                                                    ?>">
                                                    <td>
                                                        <?php 
                                                            if ($daysLeft > 1) {
                                                                echo $daysLeft." Days";
                                                            }
                                                            else {
                                                                echo "1 Day";
                                                            }
                                                        ?>
                                                    </td>
                                                        <td><?php echo $fetch['ID']?></td>
                                                        <td><?php echo $fetch['proj_title']?></td>
                                                        <td><?php echo $fetch['proj_client']?></td>
                                                        <td><?php echo date('F j, Y', strtotime($fetch['proj_rejected']))?></td>
                                                        <td><?php echo $fetch['proj_description']?></td>
                                                    </tr>
                                                <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - ARCHIVED PROJECTS ROW ==================== -->

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - ARCHIVES ROW ==================== -->

        </div>
    </div>

    <!-- ==================== END - MAIN CONTENT ==================== -->

    <?php
        // ==================== START - DELETION ====================

        $recordPurchasesDeletion = $database->query("SELECT * FROM purchase_group_tbl WHERE pg_status = 'Deleted' AND pg_date_deleted < now() - interval 30 DAY");
        if ($recordPurchasesDeletion->num_rows > 0) {  
            while ($recordPurchasesDeletionRows = $recordPurchasesDeletion->fetch_assoc()) {
                $ID = $recordPurchasesDeletionRows['ID'];
                $database->query("DELETE FROM purchase_group_tbl WHERE ID = '$ID'");
            }
        }

        $recordMaterialDeletion = $database->query("SELECT * FROM materials_tbl WHERE m_quality = 'Trash' AND m_rejected < now() - interval 30 DAY");
        if ($recordMaterialDeletion->num_rows > 0) {
            while ($recordMaterialDeletionRows = $recordMaterialDeletion->fetch_assoc()) {
                $ID = $recordMaterialDeletionRows['ID'];
                $mCodePhoto = $recordMaterialDeletionRows['m_code_photo'];
                $database->query("DELETE FROM materials_tbl WHERE ID = '$ID'");
            }
        }

        $recordProductDeletion = $database->query("SELECT * FROM products_tbl WHERE p_quality = 'Trash' AND p_rejected < now() - interval 30 DAY");
        if ($recordProductDeletion->num_rows > 0) {
            while ($recordProductDeletionRows = $recordProductDeletion->fetch_assoc()) {
                $ID = $recordProductDeletionRows['ID'];
                $pCodePhoto = $recordProductDeletionRows['p_code_photo'];
                $database->query("DELETE FROM products_tbl WHERE ID = '$ID'");
            }
        }

        $recordProjectsDeletion = $database->query("SELECT * FROM projects_tbl WHERE proj_status = 'Deleted' AND proj_rejected < now() - interval 30 DAY");
        if ($recordProjectsDeletion->num_rows > 0) {  
            while ($recordProjectsDeletionRows = $recordProjectsDeletion->fetch_assoc()) {
                $ID = $recordProjectsDeletionRows['ID'];
                $database->query("DELETE FROM projects_tbl WHERE ID = '$ID'");
            }
        }

        // ==================== END - DELETION ====================
    ?>

    <script src="js/jquery.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.js"></script>
    <script src="js/PassRequirements.js"></script>
    <script src="js/script.js"></script>
</body>
</html>