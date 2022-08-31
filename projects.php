<?php
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "projects";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $sqlProjectsOngoingTable = "SELECT projects_tbl.ID,
                                projects_tbl.proj_code,
                                projects_tbl.proj_title,
                                projects_tbl.proj_client,
                                projects_tbl.proj_delivery_date,
                                projects_tbl.proj_representative,
                                representatives_tbl.rep_firstname,
                                representatives_tbl.rep_lastname,
                                projects_tbl.proj_description,
                                projects_tbl.proj_status,
                                projects_tbl.proj_created,
                                projects_tbl.proj_rejected
                         FROM `projects_tbl` 
                         JOIN representatives_tbl ON projects_tbl.proj_representative = representatives_tbl.ID
                         WHERE projects_tbl.proj_status = 'Active' ORDER BY projects_tbl.proj_delivery_date";
    $projectsOngoingFetch = $database->query($sqlProjectsOngoingTable) or die ($database->error);

    $sqlProjectsCompletedTable = "SELECT projects_tbl.ID,
                                projects_tbl.proj_code,
                                projects_tbl.proj_title,
                                projects_tbl.proj_client,
                                projects_tbl.proj_delivery_date,
                                projects_tbl.proj_representative,
                                representatives_tbl.rep_firstname,
                                representatives_tbl.rep_lastname,
                                projects_tbl.proj_description,
                                projects_tbl.proj_status,
                                projects_tbl.proj_created,
                                projects_tbl.proj_rejected
                         FROM `projects_tbl` 
                         JOIN representatives_tbl ON projects_tbl.proj_representative = representatives_tbl.ID
                         WHERE projects_tbl.proj_status = 'Completed' ORDER BY projects_tbl.proj_delivery_date";
    $projectsCompletedFetch = $database->query($sqlProjectsCompletedTable) or die ($database->error);

    date_default_timezone_set('Asia/Manila');

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();

    // ==================== END - QUERIES ====================

    // ==================== START - ADDING PROJECT ====================

    if(isset($_POST['addProject'])){
        $projTitle = mysqli_real_escape_string($database, $_POST['projectTitle']);
        $projClient = mysqli_real_escape_string($database, $_POST['projectClient']);
        $projDelivery = $_POST['projectDelivery'];
        $projRepresentative = $_POST['selectProjectSR'];
        $projCreated = date("Y-m-d H:i:s");

        $fetchTotalProjects =  $database->query("SELECT COUNT(`ID`) AS `projectsTotal` FROM `projects_tbl`")->fetch_assoc();
        $totalProjectsCounter = str_pad(($fetchTotalProjects['projectsTotal']) + 1,5,"0",STR_PAD_LEFT);

        $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$projCreated','SG-$totalProjectsCounter','Created')") or die ($database->error);

        $sqlProjectsTable = "INSERT INTO `projects_tbl`(`ID`, `proj_code`, `proj_title`, `proj_client`, `proj_delivery_date`, `proj_representative`, `proj_description`, `proj_status`, `proj_created`, `proj_rejected`) VALUES (NULL, 'SG-$totalProjectsCounter', '$projTitle','$projClient','$projDelivery', '$projRepresentative', '', 'Active', '$projCreated', '0000-00-00 00:00:00')";
        $database->query($sqlProjectsTable) or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'New Project Created!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=projects.php");
    };

    // ==================== END - ADDING PROJECT ====================

    // ==================== START - EDIT PROJECT ====================

    if(isset($_POST['projEdit'])){
        $projIDEdit = $_POST['inputHiddenEditProjectID'];
        $projTitleEdit = mysqli_real_escape_string($database, $_POST['projTitleEdit']);
        $projClientEdit = mysqli_real_escape_string($database, $_POST['projClientEdit']);
        $projDeliveryEdit = $_POST['projDeliveryEdit'];
        $projRepresentativeEdit = $_POST['selectProjectSREdit'];
        $currentDate = date("Y-m-d H:i:s");
        
        $projectsTableFetchCode = $database->query("SELECT proj_code FROM projects_tbl WHERE ID = '$projIDEdit'")->fetch_assoc();
        $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$currentDate','{$projectsTableFetchCode['proj_code']}','Edited')") or die ($database->error);

        $sqlProjectsTable = "UPDATE `projects_tbl` SET `proj_title`='$projTitleEdit',`proj_client`='$projClientEdit',`proj_delivery_date`='$projDeliveryEdit', `proj_representative` = '$projRepresentativeEdit' WHERE ID = '$projIDEdit'";
        $database->query($sqlProjectsTable) or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Project Updated!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=projects.php");
    };

    // ==================== END - EDIT PROJECT ====================

    // ==================== START - DELETE PROJECT ====================

    if(isset($_POST['projDelete'])){
        $projIDDelete = $_POST['inputHiddenDeleteProjID'];
        $projTitleDelete = $_POST['inputHiddenDeleteProjTitle'];
        $projDescriptionDelete = mysqli_real_escape_string($database, $_POST['inputDeleteProjectDescription']);
        $projArchived = date("Y-m-d H:i:s");

        $projectsTableFetchCode = $database->query("SELECT proj_code FROM projects_tbl WHERE ID = '$projIDDelete'")->fetch_assoc();
        $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$projArchived','{$projectsTableFetchCode['proj_code']}','Deleted')") or die ($database->error);

        $sqlProjectsTable = "UPDATE `projects_tbl` SET `proj_status`='Deleted', `proj_rejected` = '$projArchived', `proj_description`='$projDescriptionDelete' WHERE ID = '$projIDDelete'";
        $database->query($sqlProjectsTable) or die ($database->error);
        
        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: '$projTitleDelete Deleted!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=projects.php");
    };

    // ==================== END - DELETE PROJECT ====================

    // ==================== START - ADDING ORDERS ====================

    if(isset($_POST['addOrders'])){
        $projectID = $_POST['inputHiddenProjectID'];
        $orderedProduct = $_POST['selectOrderedProduct'];
        $orderedProductQuantity = $_POST['orderedProductQuantity'];
        $pCurrentDate = date("Y-m-d H:i:s");
        $orderedProductQuantitySet = $orderedProductQuantity;

        $productsTableQuery = $database->query("SELECT * FROM products_tbl WHERE p_name = '$orderedProduct' AND p_status = 'Processing' AND p_quality = 'Good'");
        if ($productsTableQuery->num_rows > 0) {
            $productPiecesCounter = 0;
            while ($productsTableCheckRow = $productsTableQuery->fetch_assoc()) {
                $productPiecesCounter = $productPiecesCounter + $productsTableCheckRow['p_remaining'];
            }
            if ($productPiecesCounter >= $orderedProductQuantity) {
                while ($orderedProductQuantity > 0) {
                    $productsTableQueryBrowse = $database->query("SELECT * FROM products_tbl WHERE p_name = '$orderedProduct' AND p_status = 'Processing' AND p_quality = 'Good'");
                    while ($productsTableUpdateRow = $productsTableQueryBrowse->fetch_assoc()) {
                        if ($orderedProductQuantity > 0) {
                            $pID = $productsTableUpdateRow['ID'];
                            $pCode = $productsTableUpdateRow['p_code'];
                            $pName = $productsTableUpdateRow['p_name'];
                            $productsTableFetchWordName = $database->query("SELECT p_name FROM product_name_tbl WHERE ID = '$pName'")->fetch_assoc();
                            $projectsTableFetchCode = $database->query("SELECT proj_code FROM projects_tbl WHERE ID = '$projectID'")->fetch_assoc();
                            if ($productsTableUpdateRow['p_remaining'] > $orderedProductQuantity) {
                                $pRemainingProductMeasurement = $productsTableUpdateRow['p_remaining'] - $orderedProductQuantity;



                                $calculatedPrice = ($productsTableUpdateRow['p_price']/$productsTableUpdateRow['p_measurement'])*$orderedProductQuantity;



                                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$orderedProductQuantity Piece(s) of {$productsTableFetchWordName['p_name']} ($pCode) for {$projectsTableFetchCode['proj_code']}','Products','Used')") or die ($database->error);
                                $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$pCurrentDate','$orderedProductQuantity Piece(s) of {$productsTableFetchWordName['p_name']} ($pCode) to {$projectsTableFetchCode['proj_code']}','Added')") or die ($database->error);
                                $productsTableUpdate = $database->query("UPDATE `products_tbl` SET `p_remaining`='$pRemainingProductMeasurement' WHERE ID = '$pID'");
                                $productUsedTableInsert = $database->query("INSERT INTO `product_used_tbl`(`ID`, `p_code`, `p_name`, `p_price`, `p_measurement`, `p_sold`, `p_sold_to`, `p_used_status`) VALUES (NULL,'$pCode','$pName','$calculatedPrice','$orderedProductQuantity','$pCurrentDate','$projectID','Active')");
                                $orderedProductQuantity = 0;
                            }
                            else if ($productsTableUpdateRow['p_remaining'] == $orderedProductQuantity) {

                                $calculatedPrice = ($productsTableUpdateRow['p_price']/$productsTableUpdateRow['p_measurement'])*$orderedProductQuantity;


                                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$orderedProductQuantity Piece(s) of {$productsTableFetchWordName['p_name']} ($pCode) for {$projectsTableFetchCode['proj_code']}','Products','Used')") or die ($database->error);
                                $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$pCurrentDate','$orderedProductQuantity Piece(s) of {$productsTableFetchWordName['p_name']} ($pCode) to {$projectsTableFetchCode['proj_code']}','Added')") or die ($database->error);
                                $productsTableUpdate = $database->query("UPDATE `products_tbl` SET `p_remaining`='0', p_status = 'Sold' WHERE ID = '$pID'");
                                $productUsedTableInsert = $database->query("INSERT INTO `product_used_tbl`(`ID`, `p_code`, `p_name`, `p_price`, `p_measurement`, `p_sold`, `p_sold_to`, `p_used_status`) VALUES (NULL,'$pCode','$pName','$calculatedPrice','$orderedProductQuantity','$pCurrentDate','$projectID','Active')");
                                $orderedProductQuantity = 0;
                            }
                            else {
                                $pRemainingProductMeasurement = $productsTableUpdateRow['p_remaining'];
                                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$orderedProductQuantity Piece(s) of {$productsTableFetchWordName['p_name']} ($pCode) for {$projectsTableFetchCode['proj_code']}','Products','Used')") or die ($database->error);
                                $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$pCurrentDate','$orderedProductQuantity Piece(s) of {$productsTableFetchWordName['p_name']} ($pCode) to {$projectsTableFetchCode['proj_code']}','Added')") or die ($database->error);
                                $productsTableUpdate = $database->query("UPDATE `products_tbl` SET `p_remaining`='0', p_status = 'Sold' WHERE ID = '$pID'");
                                $productUsedTableInsert = $database->query("INSERT INTO `product_used_tbl`(`ID`, `p_code`, `p_name`, `p_price`, `p_measurement`, `p_sold`, `p_sold_to`, `p_used_status`) VALUES (NULL,'$pCode','$pName','{$productsTableUpdateRow['p_price']}','$pRemainingProductMeasurement','$pCurrentDate','$projectID','Active')");
                                $orderedProductQuantity = $orderedProductQuantity-$pRemainingProductMeasurement;
                            }
                        }
                    }
                }
                $sqlProductNameTableFetch = $database->query("SELECT p_name FROM `product_name_tbl` WHERE ID = '$orderedProduct'")->fetch_assoc();
                $productNameFetch = $sqlProductNameTableFetch['p_name'];

                $sqlProjectsTableFetch = $database->query("SELECT proj_code FROM `projects_tbl` WHERE ID = '$projectID'")->fetch_assoc();
                $projectsTableFetch = $sqlProjectsTableFetch['proj_code'];

                if ($orderedProductQuantity > 1) {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: '$orderedProductQuantitySet {$productNameFetch}s Added to $projectsTableFetch',
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 2000
                            });
                        </script>
                    ";
                }
                else {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: '$orderedProductQuantitySet $productNameFetch Added to $projectsTableFetch',
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 2000
                            });
                        </script>
                    ";
                }
                header("Refresh:2; url=projects.php");
            }
            else {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Insufficient Products',
                            text: 'The products ordered are insufficient, create more in the products tab', 
                            showCancelButton: true,
                            cancelButtonColor: '#6c757d',
                            cancelButtonText: 'Ok',
                            confirmButtonColor: '#007bff',
                            confirmButtonText: 'Go To Products'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'products.php';
                            }
                        });
                    </script>
                ";
            }
        }
        else {
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'No Products Available',
                        text: 'There are currently no available products in the storage, create one first in the products tab', 
                        showCancelButton: true,
                        cancelButtonColor: '#6c757d',
                        cancelButtonText: 'Ok',
                        confirmButtonColor: '#007bff',
                        confirmButtonText: 'Go To Products'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'products.php';
                        }
                    });
                </script>
            ";
        }
    }

    // ==================== END - ADDING ORDERS ====================

    // ==================== START - DELETE ORDERS ====================

    if(isset($_POST['projectProductRemoval'])){
        $projectID = $_POST['inputHiddenDeleteProjectID'];
        $projectCode = $_POST['inputHiddenDeleteProjectCode'];
        $productNameDefault = $_POST['inputHiddenDeleteProductNameDefault'];
        $productName = $_POST['inputHiddenDeleteProductName'];
        $pCurrentDate = date("Y-m-d H:i:s");

        $productUsedTable = $database->query("SELECT * FROM product_used_tbl WHERE p_sold_to = '$projectID' AND p_name = '$productNameDefault' AND p_used_status = 'Active'");
        while ($productUsedTableRow = $productUsedTable->fetch_assoc()) {
            $productCode = $productUsedTableRow['p_code'];
            $productUsedMeasurement = $productUsedTableRow['p_measurement'];
            $productsTable = $database->query("SELECT * FROM products_tbl WHERE p_code = '$productCode'");
            while ($productsTableRow = $productsTable->fetch_assoc()) {
                $productMeasurement = $productUsedMeasurement + $productsTableRow['p_remaining'];
                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$productMeasurement Piece(s) of $productName ($productCode)','Products','Returned')") or die ($database->error);
                $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$pCurrentDate','$productMeasurement $productName Removed from Project $projectCode','Removed')") or die ($database->error);
                $database->query("UPDATE `products_tbl` SET `p_remaining`='$productMeasurement',`p_status`='Processing' WHERE p_code = '$productCode'");
            }
            $database->query("UPDATE `product_used_tbl` SET `p_used_status`='Removed' WHERE p_sold_to = '$projectID' AND p_name = '$productNameDefault' AND p_used_status = 'Active'");
        }

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Removing Complete!',
                    text: '$productName has been removed from Project $projectCode',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";

        header("Refresh:2; url=projects.php");
    }

    // ==================== END - DELETE ORDERS ====================

    // ==================== START - COMPLETE PROJECT ====================

    if(isset($_POST['projectComplete'])){
        $projectID = $_POST['inputHiddenCompleteProjectID'];
        $projectCode = $_POST['inputHiddenCompleteProjectCode'];
        $pCurrentDate = date("Y-m-d H:i:s");

        $database->query("UPDATE `projects_tbl` SET `proj_status`='Completed', `proj_rejected` = '$pCurrentDate' WHERE ID = '$projectID'");
        $database->query("INSERT INTO `sales_history_tbl`(`ID`, `sh_admin`, `sh_date`, `sh_event`,`sh_method`) VALUES (NULL,'$adminID','$pCurrentDate','$projectCode as Completed','Completed')") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Project Completed',
                    text: '$projectCode has been marked as completed',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";

        header("Refresh:2; url=projects.php");
    }

    // ==================== END - COMPLETE PROJECT ====================

    // ==================== SIGN OUT ====================

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
?> 





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/JJ Logo.png">
    <title>Projects</title>

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

            <!-- ==================== START - PROJECTS TITLE ROW ==================== -->

            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-check-square fs-3'></i>&emsp;Projects
                    </p>
                    <?php
                        if ($_SESSION['position'] != 'User') {
                            ?>
                                <button class="btn btn-sm btn-primary d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalNewProject">
                                    <i class='bx bx-plus fs-5'></i>&nbsp;New Project
                                </button>
                            <?php
                        }
                    ?>
                </div>
            </div>

            <!-- ==================== END - PROJECTS TITLE ROW ==================== -->

            <!-- ==================== START - PROJECTS ROW ==================== -->

            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs">
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-primary active" href="#projOngoing" data-bs-toggle="tab">Ongoing</a>
                                </li>
                                <li class="nav-item ms-auto">
                                    <a class="nav-link text-dark border-top-success" href="#projCompleted" data-bs-toggle="tab">Completed</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- ==================== START - ONGOING PROJECTS TAB ==================== -->

                                <div class="tab-pane active" id="projOngoing">
                                    <div class="row">
                                        <?php
                                            if($projectsOngoingFetch->num_rows > 0){
                                                while($fetch = $projectsOngoingFetch->fetch_array()){ 
                                                    ?>
                                                        <div class="col-12">
                                                            <div class="card mb-3">
                                                                <div class="card-header text-light d-flex bd-highlight position-relative" style="overflow: hidden;">
                                                                    <?php 
                                                                        $deliveryDate = new DateTime($fetch['proj_delivery_date']);
                                                                        $dateDifference = $deliveryDate->diff(new DateTime("now"));
                                                                        if ($fetch['proj_delivery_date'] < date("Y-m-d")) {
                                                                            ?>
                                                                                <h5 class=" me-auto m-0 d-flex align-items-center text-light" style="z-index: 1;"><?php echo mb_strimwidth($fetch['proj_title'], 0, 13, "..."); ?></h5>
                                                                                <div class="position-absolute bg-secondary rounded proj-diagonal-shape"></div>
                                                                            <?php
                                                                        }
                                                                        else {
                                                                            if ($dateDifference->format("%a") >= 7) {
                                                                                ?>
                                                                                    <h5 class=" me-auto m-0 d-flex align-items-center text-light" style="z-index: 1;"><?php echo mb_strimwidth($fetch['proj_title'], 0, 13, "..."); ?></h5>
                                                                                    <div class="position-absolute bg-primary rounded proj-diagonal-shape"></div>
                                                                                <?php
                                                                            }
                                                                            else if ($dateDifference->format("%a") >= 3) {
                                                                                ?>
                                                                                    <h5 class=" me-auto m-0 d-flex align-items-center text-dark" style="z-index: 1;"><?php echo mb_strimwidth($fetch['proj_title'], 0, 13, "..."); ?></h5>
                                                                                    <div class="position-absolute bg-warning rounded proj-diagonal-shape"></div>
                                                                                <?php
                                                                            }
                                                                            else {
                                                                                ?>
                                                                                    <h5 class=" me-auto m-0 d-flex align-items-center text-light" style="z-index: 1;"><?php echo mb_strimwidth($fetch['proj_title'], 0, 13, "..."); ?></h5>
                                                                                    <div class="position-absolute bg-danger rounded proj-diagonal-shape"></div>
                                                                                <?php
                                                                            }
                                                                        }

                                                                        if ($_SESSION['position'] != 'User') {
                                                                            ?>
                                                                                <button type="button" class="btn btn-sm btn-success p-1 d-flex align-items-center me-1 border border-light" data-bs-toggle="modal" data-bs-target="#modalEditProject<?php echo $fetch['ID']?>">
                                                                                    <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Project"></i>
                                                                                </button>
                                                                                <?php
                                                                                    $totalProductsInProject = 0;
                                                                                    $projectID = $fetch['ID'];

                                                                                    $productsUsedTableCounter = $database->query("SELECT * FROM product_used_tbl WHERE p_sold_to = '$projectID' AND p_used_status = 'Active'");
                                                                                    while ($productsUsedTableCounterRow = $productsUsedTableCounter->fetch_assoc()) {
                                                                                        $totalProductsInProject = $totalProductsInProject+1;
                                                                                    }

                                                                                    if ($totalProductsInProject <= 0) {
                                                                                        ?>
                                                                                            <button type="button" class="btn btn-sm btn-danger p-1 d-flex align-items-center ms-1 border border-light" data-bs-toggle="modal" data-bs-target="#modalDeleteProjectConfirmation<?php echo $fetch['ID']?>">
                                                                                                <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Project"></i>
                                                                                            </button>
                                                                                        <?php
                                                                                    }
                                                                                ?>
                                                                            <?php
                                                                        }
                                                                    ?>
                                                                </div>
                                                                
                                                                <div class="card-body">
                                                                    <div class="card">
                                                                        <div class="card-header text-center">
                                                                            <ul class="nav nav-tabs card-header-tabs">
                                                                                <li class="nav-item">
                                                                                    <a class="nav-link text-dark border-top-primary active" href="#projectDescription<?php echo $fetch['ID']?>" data-bs-toggle="tab">Description</a>
                                                                                </li>
                                                                                <li class="nav-item">
                                                                                    <a class="nav-link text-dark border-top-primary" href="#projectOrders<?php echo $fetch['ID']?>" data-bs-toggle="tab">Orders</a>
                                                                                </li>
                                                                                <?php
                                                                                    if ($fetch['proj_delivery_date'] < date("Y-m-d")) {
                                                                                        ?>
                                                                                            <i class='ms-auto bx bx-info-circle bx-burst-hover fs-3 text-danger d-flex align-items-center pointer' data-bs-toggle="tooltip" data-bs-placement="top" title="This project passed the due date of its delivery, update its delivery date to continue this project."></i>
                                                                                        <?php
                                                                                    }
                                                                                ?>
                                                                            </ul>
                                                                        </div>
                                                                        <div class="card-body">
                                                                            <div class="tab-content">
                                    
                                                                                <!-- ==================== START - PROJECT DESCRIPTION ROW ==================== -->
                                                                                
                                                                                <div class="tab-pane active" id="projectDescription<?php echo $fetch['ID']?>">
                                                                                    <?php $projID = $fetch['ID'];?>
                                                                                    <div class="input-group mb-3">
                                                                                        <span class="input-group-text bg-primary text-light">Project Code</span>
                                                                                        <input type="text" class="form-control" value="<?php echo $fetch['proj_code']?>" readonly>
                                                                                    </div>
                                                                                    <div class="input-group mb-3">
                                                                                        <span class="input-group-text bg-primary text-light">Project Title</span>
                                                                                        <input type="text" class="form-control" value="<?php echo $fetch['proj_title']?>" readonly>
                                                                                    </div>
                                                                                    <div class="input-group mb-3">
                                                                                        <span class="input-group-text bg-primary text-light">Client Name</span>
                                                                                        <input type="text" class="form-control" value="<?php echo $fetch['proj_client']?>" readonly>
                                                                                    </div>
                                                                                    <div class="input-group mb-3">
                                                                                        <span class="input-group-text bg-primary text-light">Ordered Products</span>
                                                                                        <?php 
                                                                                            $totalProductsOrdered = 0;
                                                                                            $productUsedTableCount = $database->query("SELECT p_sold_to, p_measurement FROM product_used_tbl WHERE p_sold_to = '$projID' AND p_used_status = 'Active'");
                                                                                            while ($productUsedTableCountRow = $productUsedTableCount->fetch_assoc()) {
                                                                                                $totalProductsOrdered = $totalProductsOrdered + $productUsedTableCountRow['p_measurement'];
                                                                                            }
                                                                                            if ($totalProductsOrdered > 0) {
                                                                                                ?>
                                                                                                    <input type="text" class="form-control" value="<?php echo $totalProductsOrdered." Piece"; if ($totalProductsOrdered > 1) {echo "s";}?>" readonly>
                                                                                                <?php
                                                                                            }
                                                                                            else {
                                                                                                ?>
                                                                                                    <input type="text" class="form-control" value="None" readonly>
                                                                                                <?php
                                                                                            }
                                                                                        ?>
                                                                                    </div>
                                                                                    <div class="input-group mb-3">
                                                                                        <span class="input-group-text bg-primary text-light">Project Price</span>
                                                                                        <?php
                                                                                            $totalProductsOrderedPrice = 0;
                                                                                            $productUsedTablePrice = $database->query("SELECT p_sold_to, p_price FROM product_used_tbl WHERE p_sold_to = '$projID' AND p_used_status = 'Active'");
                                                                                            while ($productUsedTablePriceRow = $productUsedTablePrice->fetch_assoc()) {
                                                                                                $totalProductsOrderedPrice = $totalProductsOrderedPrice + $productUsedTablePriceRow['p_price'];
                                                                                            }
                                                                                            if ($totalProductsOrderedPrice > 0) {
                                                                                                ?>
                                                                                                    <input type="text" class="form-control" value="<?php echo "₱".number_format((float)$totalProductsOrderedPrice, 2, '.', ',')?>" readonly>
                                                                                                <?php
                                                                                            }
                                                                                            else {
                                                                                                ?>
                                                                                                    <input type="text" class="form-control" value="Not Available" readonly>
                                                                                                <?php
                                                                                            }
                                                                                        ?>
                                                                                    </div>
                                                                                    <div class="input-group mb-3">
                                                                                        <span class="input-group-text bg-primary text-light">Delivery Date</span>
                                                                                        <?php 
                                                                                            $deliveryDate = new DateTime($fetch['proj_delivery_date']);
                                                                                            $dateDifference = $deliveryDate->diff(new DateTime("now"));
                                                                                            if ($fetch['proj_delivery_date'] < date("Y-m-d")) {
                                                                                                ?>
                                                                                                    <input type="text" class="form-control bg-secondary text-light" value="Project Expired!" readonly>
                                                                                                <?php
                                                                                            }
                                                                                            else {
                                                                                                if ($dateDifference->format("%a") >= 7) {
                                                                                                    ?>
                                                                                                        <input type="text" class="form-control" value="<?php echo date("F j, Y", strtotime($fetch['proj_delivery_date']))?>" readonly>
                                                                                                    <?php
                                                                                                }
                                                                                                else if ($dateDifference->format("%a") >= 3) {
                                                                                                    ?>
                                                                                                        <input type="text" class="form-control bg-warning text-dark" value="<?php echo date("F j, Y", strtotime($fetch['proj_delivery_date']))?>" readonly>
                                                                                                    <?php
                                                                                                }
                                                                                                else {
                                                                                                    ?>
                                                                                                        <input type="text" class="form-control bg-danger text-light" value="<?php echo date("F j, Y", strtotime($fetch['proj_delivery_date']))?>" readonly>
                                                                                                    <?php
                                                                                                }
                                                                                            }
                                                                                        ?>
                                                                                    </div>
                                                                                    <div class="input-group mb-3">
                                                                                        <span class="input-group-text bg-primary text-light">Sales Representative</span>
                                                                                        <input type="text" class="form-control" value="<?php echo $fetch['rep_firstname']." ".$fetch['rep_lastname']?>" readonly>
                                                                                    </div>
                                                                                    <div class="d-flex justify-content-between">
                                                                                        <div>
                                                                                            <label class="fw-bold">Created On: </label>
                                                                                            <label><?php echo date('F j, Y', strtotime($fetch['proj_created']))?></label>
                                                                                        </div>
                                                                                        <?php 
                                                                                            $totalProductsOrdered = 0;
                                                                                            $productUsedTableCount = $database->query("SELECT p_sold_to, p_measurement FROM product_used_tbl WHERE p_sold_to = '$projID' AND p_used_status = 'Active'");
                                                                                            while ($productUsedTableCountRow = $productUsedTableCount->fetch_assoc()) {
                                                                                                $totalProductsOrdered = $totalProductsOrdered + $productUsedTableCountRow['p_measurement'];
                                                                                            }
                                                                                            if ($totalProductsOrdered > 0) {
                                                                                                if ($_SESSION['position'] != 'User') {
                                                                                                    if ($fetch['proj_delivery_date'] > date("Y-m-d",strtotime("-1 days"))) {
                                                                                                        ?>
                                                                                                            <button type='button' class='btn btn-sm btn-success p-1 d-flex align-items-center' data-bs-toggle="modal" data-bs-target="#modalCompleteProject<?php echo $fetch['ID']?>">
                                                                                                                <i class='bx bx-check fs-6'></i>&nbsp;Complete Project
                                                                                                            </button>
                                                                                                        <?php
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        ?>
                                                                                    </div>

                                                                                    <div class="modal fade" id="modalCompleteProject<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                                        <div class="modal-dialog modal-dialog-centered">
                                                                                            <div class="modal-content">
                                                                                                <form action="" method="POST">
                                                                                                    <div class="modal-header">
                                                                                                        <h5 class="modal-title">Complete Project</h5>
                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                    </div>
                                                                                                    <div class="modal-body">
                                                                                                        Do you want to mark this project as completed?
                                                                                                        <input type="hidden" name="inputHiddenCompleteProjectID" value="<?php echo $fetch['ID']?>"/>
                                                                                                        <input type="hidden" name="inputHiddenCompleteProjectCode" value="<?php echo $fetch['proj_code']?>"/>
                                                                                                    </div>
                                                                                                    <div class="modal-footer">
                                                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                                                        <button type="submit" class="btn btn-success" name="projectComplete" id="projectComplete">Yes, Mark It!</button>
                                                                                                    </div>
                                                                                                </form>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>

                                                                                <!-- ==================== END - PROJECT DESCRIPTION ROW ==================== -->

                                                                                <!-- ==================== START - ORDERED PRODUCTS ROW ==================== -->
                                                                                
                                                                                <div class="tab-pane" id="projectOrders<?php echo $fetch['ID']?>">
                                                                                    <div class="row mb-3">
                                                                                        <div class="col d-flex justify-content-end">
                                                                                            



                                                                                            <button class="btn btn-danger btn-sm p-1 d-flex align-items-center me-1" data-bs-toggle="modal" data-bs-target="#modalPrintBOM<?php echo $fetch['ID']?>">
                                                                                                <i class='bx bx-printer fs-6'></i>&nbsp;Print BOM
                                                                                            </button>
                                                                                            <button class="btn btn-primary btn-sm p-1 d-flex align-items-center ms-1" data-bs-toggle="modal" data-bs-target="#modalAddOrders<?php echo $fetch['ID']?>">
                                                                                                <i class='bx bx-plus fs-6'></i>&nbsp;Add Orders
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>
                                                                                    <table class="datatable-asc-1 table table table-hover responsive nowrap w-100 ">
                                                                                        <thead class="bg-primary text-light">
                                                                                            <th>Product Name</th>
                                                                                            <th>Quantity</th>
                                                                                            <th>Materials Used</th>
                                                                                            <th>Manufacturing Cost</th>
                                                                                            <th>Unit Price</th>
                                                                                            <th>Total Price</th>
                                                                                            <th class="no-sort text-center">Action</th>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            <?php 
                                                                                                $projID = $fetch['ID'];
                                                                                                $productUsedTableFetch = $database->query("SELECT product_used_tbl.ID, product_used_tbl.p_name AS p_name_default, product_name_tbl.p_name, product_name_tbl.p_photo FROM product_used_tbl INNER JOIN product_name_tbl ON product_used_tbl.p_name = product_name_tbl.ID WHERE product_used_tbl.p_sold_to = '$projID' AND p_used_status = 'Active' GROUP BY p_name");
                                                                                                while ($productUsedTableFetchRow = $productUsedTableFetch->fetch_assoc()) {
                                                                                                    $productName = $productUsedTableFetchRow['p_name_default'];
                                                                                                    ?>
                                                                                                        <tr>
                                                                                                            <td class="align-middle">
                                                                                                                <img src="images/items/<?php echo $productUsedTableFetchRow['p_photo']?>" alt="" class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'><?php echo $productUsedTableFetchRow['p_name']?>
                                                                                                            </td>
                                                                                                            <td class="align-middle">
                                                                                                                <div class="d-flex justify-content-between">
                                                                                                                    <?php 
                                                                                                                        $totalProductMeasurement = 0;
                                                                                                                        $productUsedTableMeasurementFetch = $database->query("SELECT p_measurement FROM product_used_tbl WHERE p_name = '$productName' AND p_sold_to = '$projID' AND p_used_status = 'Active'");
                                                                                                                        while ($productUsedTableMeasurementFetchRow = $productUsedTableMeasurementFetch->fetch_assoc()) {
                                                                                                                            $totalProductMeasurement = $totalProductMeasurement + $productUsedTableMeasurementFetchRow['p_measurement'];
                                                                                                                        }
                                                                                                                        echo $totalProductMeasurement;
                                                                                                                        if ($totalProductMeasurement > 1) {
                                                                                                                            echo " Pieces";
                                                                                                                        }
                                                                                                                        else {
                                                                                                                            echo " Piece";
                                                                                                                        }
                                                                                                                    ?> 
                                                                                                                    <button class="btn btn-sm btn-primary p-1" data-bs-toggle="modal" data-bs-target="#modalProductBreakdown<?php echo $productUsedTableFetchRow['ID']?>">
                                                                                                                        <i class='bx bx-show fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Product Breakdown"></i>
                                                                                                                    </button>
                                                                                                                </div>
                                                                                                            </td>

                                                                                                            <!-- ==================== START - MODAL PRODUCT BREAKDOWN ==================== -->
                                                                                                            
                                                                                                            <div class="modal fade" id="modalProductBreakdown<?php echo $productUsedTableFetchRow['ID']?>" tabindex="-1" aria-hidden="true">
                                                                                                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                                                                                    <div class="modal-content">
                                                                                                                        <div class="modal-header">
                                                                                                                            <h5 class="modal-title"><?php echo $productUsedTableFetchRow['p_name']?></h5>
                                                                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                        </div>
                                                                                                                        <div class="modal-body">
                                                                                                                            <div class="row">
                                                                                                                                <div class="col fw-bold text-center">
                                                                                                                                    Code
                                                                                                                                </div>
                                                                                                                                <div class="col fw-bold text-center">
                                                                                                                                    Measurement
                                                                                                                                </div>
                                                                                                                                <div class="col fw-bold text-center">
                                                                                                                                    Price
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                            <?php
                                                                                                                                $pName = $productUsedTableFetchRow['p_name_default'];
                                                                                                                                $productUsedTableBreakdown = $database->query("SELECT p_code, p_measurement, p_name, p_price, p_sold_to FROM product_used_tbl WHERE p_name = '$pName' AND p_sold_to = '$projID' AND p_used_status = 'Active'");
                                                                                                                                while ($productUsedTableBreakdownRow = $productUsedTableBreakdown->fetch_assoc()) {
                                                                                                                                    ?>
                                                                                                                                        <div class='row'>
                                                                                                                                            <div class='col text-center'><?php echo $productUsedTableBreakdownRow['p_code']?></div>
                                                                                                                                            <div class='col text-center'><?php echo $productUsedTableBreakdownRow['p_measurement']." Piece"; if ($productUsedTableBreakdownRow['p_measurement'] > 1){echo "s";}?></div>
                                                                                                                                            <div class='col text-center'><?php echo "₱".number_format((float)$productUsedTableBreakdownRow['p_price'], 2, '.', ',');?></div>
                                                                                                                                        </div>
                                                                                                                                    <?php
                                                                                                                                }
                                                                                                                            ?>
                                                                                                                        </div>
                                                                                                                        <div class="modal-footer">
                                                                                                                            <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>

                                                                                                            <!-- ==================== END - MODAL PRODUCT BREAKDOWN ==================== -->

                                                                                                            <td class="align-middle">
                                                                                                                <div class="d-flex justify-content-between">
                                                                                                                    <?php 
                                                                                                                        $projectID = $fetch['ID'];
                                                                                                                        $materialUsedTextContainer = '';
                                                                                                                        $productUsedTableProductFetch = $database->query("SELECT p_code FROM product_used_tbl WHERE p_name = '$productName' AND p_sold_to = '$projectID' AND p_used_status = 'Active'");
                                                                                                                        while ($productUsedTableProductFetchhRow = $productUsedTableProductFetch->fetch_assoc()) {
                                                                                                                            $productCode = $productUsedTableProductFetchhRow['p_code'];
                                                                                                                            $materialsUsedTableMaterialFetch = $database->query("SELECT material_used_tbl.m_code, material_name_tbl.m_name, material_used_tbl.m_price, material_used_tbl.m_measurement, material_used_tbl.m_unit FROM material_used_tbl INNER JOIN material_name_tbl ON material_used_tbl.m_name = material_name_tbl.ID WHERE material_used_tbl.p_code = '$productCode'");
                                                                                                                            while ($materialsUsedTableMaterialFetchRow = $materialsUsedTableMaterialFetch->fetch_assoc()) {
                                                                                                                                if ($materialsUsedTableMaterialFetchRow['m_measurement'] > 1) {
                                                                                                                                    $materialUsedTextContainer = $materialUsedTextContainer."{$materialsUsedTableMaterialFetchRow['m_measurement']} {$materialsUsedTableMaterialFetchRow['m_unit']}s {$materialsUsedTableMaterialFetchRow['m_name']} ";
                                                                                                                                }
                                                                                                                                else {
                                                                                                                                    $materialUsedTextContainer = $materialUsedTextContainer."{$materialsUsedTableMaterialFetchRow['m_measurement']} {$materialsUsedTableMaterialFetchRow['m_unit']} {$materialsUsedTableMaterialFetchRow['m_name']} ";
                                                                                                                                }
                                                                                                                            }
                                                                                                                        }
                                                                                                                        echo mb_strimwidth("$materialUsedTextContainer", 0, 30, "...");
                                                                                                                    ?> 
                                                                                                                    <button class="btn btn-sm btn-primary p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialBreakdown<?php echo $productUsedTableFetchRow['ID']?>">
                                                                                                                        <i class='bx bx-show fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Material Breakdown"></i>
                                                                                                                    </button>
                                                                                                                </div>
                                                                                                            </td>

                                                                                                            <!-- ==================== START - MODAL MATERIAL BREAKDOWN ==================== -->
                                                                                                            
                                                                                                            <div class="modal fade" id="modalMaterialBreakdown<?php echo $productUsedTableFetchRow['ID']?>" tabindex="-1" aria-hidden="true">
                                                                                                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                                                                                    <div class="modal-content">
                                                                                                                        <div class="modal-header">
                                                                                                                            <h5 class="modal-title"><?php echo $productUsedTableFetchRow['p_name']?> Material Breakdown</h5>
                                                                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                        </div>
                                                                                                                        <div class="modal-body">
                                                                                                                            <div class="row">
                                                                                                                                <div class="col fw-bold text-center">
                                                                                                                                    Materials
                                                                                                                                </div>
                                                                                                                                <div class="col fw-bold text-center">
                                                                                                                                    Measurements
                                                                                                                                </div>
                                                                                                                                <div class="col fw-bold text-center">
                                                                                                                                    Cost
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                            <?php
                                                                                                                                $pName = $productUsedTableFetchRow['p_name_default'];
                                                                                                                                $projectID = $fetch['ID'];
                                                                                                                                $productUsedTableBreakdown = $database->query("SELECT p_code FROM product_used_tbl WHERE p_name = '$pName' AND p_sold_to = '$projID' AND p_used_status = 'Active' GROUP BY p_code");
                                                                                                                                while ($productUsedTableBreakdownRow = $productUsedTableBreakdown->fetch_assoc()) {
                                                                                                                                    $productCode = $productUsedTableBreakdownRow['p_code'];
                                                                                                                                    $materialsUsedTableBreakdown = $database->query("SELECT * FROM material_used_tbl WHERE p_code = '$productCode'");
                                                                                                                                    while ($materialsUsedTableBreakdownRow = $materialsUsedTableBreakdown->fetch_assoc()) {
                                                                                                                                        ?>
                                                                                                                                            <div class='row'>
                                                                                                                                                <div class='col text-center'><?php echo $materialsUsedTableBreakdownRow['m_code']?></div>
                                                                                                                                                <div class='col text-center'><?php echo $materialsUsedTableBreakdownRow['m_measurement']." ".$materialsUsedTableBreakdownRow['m_unit']; if ($materialsUsedTableBreakdownRow['m_measurement'] > 1){echo "s";}?></div>
                                                                                                                                                <div class='col text-center'><?php echo "₱".number_format((float)$materialsUsedTableBreakdownRow['m_price'], 2, '.', ',');?></div>
                                                                                                                                            </div>
                                                                                                                                        <?php
                                                                                                                                    }
                                                                                                                                }
                                                                                                                            ?>
                                                                                                                        </div>
                                                                                                                        <div class="modal-footer">
                                                                                                                            <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>

                                                                                                            <!-- ==================== END - MODAL MATERIAL BREAKDOWN ==================== -->

                                                                                                            <td class="align-middle">
                                                                                                                <?php
                                                                                                                    $projectID = $fetch['ID'];
                                                                                                                    $pName = $productUsedTableFetchRow['p_name_default'];
                                                                                                                    $totalManufacturingCost = 0;
                                                                                                                    $productUsedTableCost = $database->query("SELECT p_code FROM product_used_tbl WHERE p_name = '$pName' AND p_sold_to = '$projID' AND p_used_status = 'Active' GROUP BY p_code");
                                                                                                                    while ($productUsedTableCostRow = $productUsedTableCost->fetch_assoc()) {
                                                                                                                        $productCode = $productUsedTableCostRow['p_code'];
                                                                                                                        $materialsUsedTableCost = $database->query("SELECT * FROM material_used_tbl WHERE p_code = '$productCode'");
                                                                                                                        while ($materialsUsedTableCostRow = $materialsUsedTableCost->fetch_assoc()) {
                                                                                                                            $totalManufacturingCost = $totalManufacturingCost + $materialsUsedTableCostRow['m_price'];
                                                                                                                        }
                                                                                                                    }
                                                                                                                    echo number_format((float)$totalManufacturingCost, 2, '.', ',');
                                                                                                                ?>
                                                                                                            </td>
                                                                                                            <td class="align-middle">
                                                                                                                <?php 
                                                                                                                    $productUsedTablePriceFetch = $database->query("SELECT MIN(p_price) AS p_low_price, MAX(p_price) AS p_high_price FROM product_used_tbl WHERE p_name = '$productName' AND p_sold_to = '$projID' AND p_used_status = 'Active'")->fetch_assoc();
                                                                                                                    echo number_format((float)$productUsedTablePriceFetch['p_low_price'], 2, '.', ',')." - ".number_format((float)$productUsedTablePriceFetch['p_high_price'], 2, '.', ',');
                                                                                                                ?>
                                                                                                            </td>
                                                                                                            <td class="align-middle">
                                                                                                                <?php 
                                                                                                                    $totalProductsOrderedPrice = 0;
                                                                                                                    $productUsedTablePrice = $database->query("SELECT p_sold_to, p_price FROM product_used_tbl WHERE p_name = '$productName' AND p_sold_to = '$projID' AND p_used_status = 'Active'");
                                                                                                                    while ($productUsedTablePriceRow = $productUsedTablePrice->fetch_assoc()) {
                                                                                                                        $totalProductsOrderedPrice = $totalProductsOrderedPrice + $productUsedTablePriceRow['p_price'];
                                                                                                                    }
                                                                                                                    echo number_format((float)$totalProductsOrderedPrice, 2, '.', ',')
                                                                                                                ?>
                                                                                                            </td>
                                                                                                            <td class="align-middle text-center">

                                                                                                                <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->

                                                                                                                <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalProductRemoval<?php echo $productUsedTableFetchRow['ID']?>">
                                                                                                                    <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Remove Product"></i>
                                                                                                                </button>
                                                                                                                
                                                                                                                <!-- ==================== END - ACTION BUTTONS COLUMN ==================== -->

                                                                                                            </td>
                                                                                                            
                                                                                                            <!-- ==================== START - MODAL DELETE DATA ==================== -->

                                                                                                            <div class="modal fade" id="modalProductRemoval<?php echo $productUsedTableFetchRow['ID']?>" tabindex="-1" aria-hidden="true">
                                                                                                                <div class="modal-dialog modal-dialog-centered">
                                                                                                                    <div class="modal-content">
                                                                                                                        <form action="" method="POST">
                                                                                                                            <div class="modal-header">
                                                                                                                                <h5 class="modal-title">Remove Product</h5>
                                                                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                            </div>
                                                                                                                            <div class="modal-body">
                                                                                                                                Are you sure you want to remove this product?
                                                                                                                                <input type="hidden" name="inputHiddenDeleteProjectID" value="<?php echo $fetch['ID']?>"/>
                                                                                                                                <input type="hidden" name="inputHiddenDeleteProjectCode" value="<?php echo $fetch['proj_code']?>"/>
                                                                                                                                <input type="hidden" name="inputHiddenDeleteProductNameDefault" value="<?php echo $productUsedTableFetchRow['p_name_default']?>"/>
                                                                                                                                <input type="hidden" name="inputHiddenDeleteProductName" value="<?php echo $productUsedTableFetchRow['p_name']?>"/>
                                                                                                                            </div>
                                                                                                                            <div class="modal-footer">
                                                                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                                                                                <button type="submit" class="btn btn-success" name="projectProductRemoval" id="projectProductRemoval">Yes, Remove It!</button>
                                                                                                                            </div>
                                                                                                                        </form>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>

                                                                                                            <!-- ==================== END - MODAL DELETE DATA ==================== -->

                                                                                                        </tr>
                                                                                                    <?php
                                                                                                }
                                                                                            ?>
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                                
                                                                                <!-- ==================== END - ORDERED PRODUCTS ROW ==================== -->

                                                                                <!-- ==================== START - ADD ORDERS ROW ==================== -->

                                                                                <form action="" method="POST">
                                                                                    <div class="modal fade" id="modalAddOrders<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                                        <div class="modal-dialog modal-dialog-centered">
                                                                                            <div class="modal-content">
                                                                                                <div class="modal-header">
                                                                                                    <h5 class="modal-title">Add Orders For (<?php echo $fetch['proj_code']?>)</h5>
                                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                                                </div>
                                                                                                <div class="modal-body">
                                                                                                    <input type="hidden" name="inputHiddenProjectID" value="<?php echo $fetch['ID']?>"/>
                                                                                                        <div class="input-group">
                                                                                                            <select class="form-select w-50" name="selectOrderedProduct" required>
                                                                                                                <option value="">Choose a Product...</option>
                                                                                                                <?php 
                                                                                                                    $productNameTableFetch = $database->query("SELECT * FROM product_name_tbl");
                                                                                                                    if($productNameTableFetch->num_rows > 0){
                                                                                                                        while ($productNameTableFetchRow = $productNameTableFetch->fetch_assoc()){
                                                                                                                            echo "<option value='{$productNameTableFetchRow["ID"]}'>{$productNameTableFetchRow['p_name']}</option>";
                                                                                                                        }
                                                                                                                    }
                                                                                                                    else {
                                                                                                                        echo "<option value=''>No Available Product</option>"; 
                                                                                                                    }
                                                                                                                ?>
                                                                                                            </select>
                                                                                                            <input type="number" class="form-control" id="orderedProductQuantity" name="orderedProductQuantity" min="1" value="1" required>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                <div class="modal-footer">
                                                                                                    <button type="submit" id="addOrders" name="addOrders" class="btn btn-primary w-100">Save Ordered Product</button>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </form>

                                                                                <!-- ==================== END - ADD ORDERS ROW ==================== -->

                                                                                <!-- ==================== START - PRINT BOM ==================== -->

                                                                                <form action="canvas.php" method="POST">
                                                                                    <div class="modal fade" id="modalPrintBOM<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                                        <div class="modal-dialog modal-dialog-centered">
                                                                                            <div class="modal-content">
                                                                                                <div class="modal-header">
                                                                                                    <h5 class="modal-title">Printing Bill of Materials for <?php echo $fetch['proj_code']?></h5>
                                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                                                </div>
                                                                                                <div class="modal-body">
                                                                                                    <input type="hidden" name="inputHiddenPrintingProjectID" value="<?php echo $fetch['ID']?>"/>
                                                                                                    <input type="hidden" name="inputHiddenPrintingProjectCode" value="<?php echo $fetch['proj_code']?>"/>
                                                                                                    <label>Do you want you want to print the entire Bill of Materials for this project?</label>
                                                                                                </div>
                                                                                                <div class="modal-footer">
                                                                                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                                                    <button type="submit" class="btn btn-success" name="btnPrintBOM" id="btnPrintBOM">Yes, Print It!</button>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </form>

                                                                                <!-- ==================== END - PRINT BOM ==================== -->
                                                                                
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                    
                                                                <!-- ==================== START - EDIT PROJECT ==================== -->
                                    
                                                                <div class="modal fade" id="modalEditProject<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content">
                                                                            <form action="" method="POST">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title">Edit Project</h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                </div>
                                                                                <div class="modal-body">
                                                                                    <input type="hidden" name="inputHiddenEditProjectID" value="<?php echo $fetch['ID']?>"/>
                                                                                    <div class="mb-3">
                                                                                        <label for="" class="form-label">Project Title</label>
                                                                                        <input type="text" class="form-control" name="projTitleEdit" value="<?php echo $fetch['proj_title']?>">
                                                                                    </div>
                                                                                    <div class="mb-3">
                                                                                        <label for="" class="form-label">Client Name</label>
                                                                                        <input type="text" class="form-control" name="projClientEdit" value="<?php echo $fetch['proj_client']?>">
                                                                                    </div>
                                                                                    <div class="mb-3">
                                                                                        <label for="" class="form-label">Delivery Date</label>
                                                                                        <input type="date" class="form-control limitedDate" name="projDeliveryEdit" value="<?php echo $fetch['proj_delivery_date']?>">
                                                                                    </div>
                                                                                    <div class="mb-3">
                                                                                        <label for="" class="form-label">Sales Representative</label>
                                                                                        <select id="selectProjectSREdit" name="selectProjectSREdit" class="form-select" required>
                                                                                            <?php 
                                                                                                $query = "SELECT * FROM representatives_tbl";
                                                                                                $result = $database->query($query);
                                                                                                if($result->num_rows > 0){
                                                                                                    while ($row = $result->fetch_assoc()){
                                                                                                        echo "<option value='{$row["ID"]}' ";
                                                                                                        if ($row["ID"] == $fetch['proj_representative']) {
                                                                                                            echo "selected";
                                                                                                        }
                                                                                                        echo ">{$row['rep_firstname']} {$row['rep_lastname']}</option>";
                                                                                                    }
                                                                                                }
                                                                                                else {
                                                                                                    echo "<option value=''>No Sales Representative Found</option>"; 
                                                                                                }
                                                                                            ?>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                                    <button type="submit" class="btn btn-success" name="projEdit" id="projEdit">Update</button>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                    
                                                                <!-- ==================== END - EDIT PROJECT ==================== -->
                                    
                                    
                                                                <!-- ==================== START - DELETE PROJECT ==================== -->
                                    
                                                                <div class="modal fade" id="modalDeleteProjectConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content">
                                                                            <form action="" method="POST">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title">Drop Project</h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                </div>
                                                                                <div class="modal-body">
                                                                                    <label class="form-label">Are you sure you want to drop <?php echo $fetch['proj_title']?> Project?</label>
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                                    <button type="button" class="btn btn-success" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalDeleteProject<?php echo $fetch['ID']?>">Yes, Drop It!</button>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal fade" id="modalDeleteProject<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content">
                                                                            <form action="" method="POST">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title">Drop Project</h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                </div>
                                                                                <div class="modal-body">
                                                                                    <input type="hidden" name="inputHiddenDeleteProjID" value="<?php echo $fetch['ID']?>"/>
                                                                                    <input type="hidden" name="inputHiddenDeleteProjTitle" value="<?php echo $fetch['proj_title']?>"/>
                                                                                    <div class="mb-2">
                                                                                        <label for="inputDeleteProjectDescription" class="form-label">Enter your reason why you want to Delete this project.</label>
                                                                                        <input type="text" class="form-control" id="inputDeleteProjectDescription" name="inputDeleteProjectDescription" placeholder="eg. Accident, Human Error..." required>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalDeleteProjectConfirmation<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                                    <button type="submit" class="btn btn-success" name="projDelete" id="projDelete">Continue</button>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                    
                                                                <!-- ==================== END - DELETE PROJECT ==================== -->
                                                                
                                                            </div>
                                                        </div>
                                                    <?php 
                                                }
                                            }
                                            else { 
                                                ?>
                                                    <div class="col text-center my-3">
                                                        <div class="row">
                                                            <h1 class="bi bi-clipboard-x"></h1>
                                                        </div>
                                                        <div class="row">
                                                            <h4>There are no active projects available</h4>
                                                        </div>
                                                    </div>
                                                <?php 
                                            }
                                        ?>
                                    </div>
                                </div>

                                <!-- ==================== END - ONGOING PROJECTS TAB ==================== -->

                                <!-- ==================== START - COMPLETED PROJECTS TAB ==================== -->

                                <div class="tab-pane" id="projCompleted">
                                    <?php
                                        $projectsTableCompletedCheck = $database->query("SELECT * FROM projects_tbl WHERE proj_status = 'Completed'");
                                        if ($projectsTableCompletedCheck->num_rows >= 1) {
                                            ?>
                                                <div class="row mb-3">
                                                    <div class="col d-flex justify-content-end">
                                                        <button class="btn btn-sm btn-danger d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalPrintRecords">
                                                            <i class='bx bx-printer fs-5'></i>&nbsp;Print Records
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php
                                        }
                                    ?>
                                    <div class="row">
                                        <?php           
                                            if($projectsCompletedFetch->num_rows > 0){
                                                while($fetch = $projectsCompletedFetch->fetch_array()){ 
                                                    $projID = $fetch['ID'];
                                                    ?>
                                                        <div class="col-12">
                                                            <div class="card mb-3">
                                                                <div class="card-header bg-success d-flex">
                                                                    <label class="m-0 d-flex text-light me-auto"><?php echo $fetch['proj_code']?>: "<?php echo mb_strimwidth($fetch['proj_title'], 0, 30, "..."); ?>"</label>
                                                                    <label class="text-light"><?php echo date("F j, Y", strtotime($fetch['proj_rejected']))?></label>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-sm-12 col-lg-6">
                                                                            <div class="row">
                                                                                <div class="col-6">
                                                                                    <label>Client:</label><br>
                                                                                    <label>Sales Representative:</label><br>
                                                                                    <label>Project Price:</label><br>
                                                                                    <label>Delivery Date:</label><br>
                                                                                </div>
                                                                                <div class="col-6">
                                                                                    <label><?php echo $fetch['proj_client']?></label><br> 
                                                                                    <label><?php echo $fetch['rep_firstname']." ".$fetch['rep_lastname']?></label><br>
                                                                                    <label>
                                                                                        <?php
                                                                                            $totalProductsOrderedPrice = 0;
                                                                                            $productsTableCompletedProject = $database->query("SELECT p_sold_to, p_price FROM product_used_tbl WHERE p_sold_to = '$projID' AND p_used_status = 'Active'");
                                                                                            while ($productsTableCompletedProjectRow = $productsTableCompletedProject->fetch_assoc()) {
                                                                                                $totalProductsOrderedPrice = $totalProductsOrderedPrice +$productsTableCompletedProjectRow['p_price'];
                                                                                            }
                                                                                            echo "₱".number_format((float)$totalProductsOrderedPrice, 2, '.', ',');
                                                                                        ?>
                                                                                    </label><br>
                                                                                    <label><?php echo date("F j, Y", strtotime($fetch['proj_delivery_date']))?></label><br>
                                                                                </div>
                                                                            </div>
                                                                            
                                                                        </div>
                                                                        <div class="col-sm-12 col-lg-6">
                                                                            <div class="row">
                                                                                <div class="col-6">
                                                                                    <label>Ordered Products:</label><br>
                                                                                    <label>Products List:</label><br>
                                                                                </div>
                                                                                <div class="col-6">
                                                                                    <label for="">
                                                                                        <?php 
                                                                                            $totalProductsOrdered = 0;
                                                                                            $productsTableCompletedProject = $database->query("SELECT p_sold_to, p_measurement FROM product_used_tbl WHERE p_sold_to = '$projID' AND p_used_status = 'Active'");
                                                                                            if ($productsTableCompletedProject->num_rows > 0) {
                                                                                                while ($productsTableCompletedProjectRow = $productsTableCompletedProject->fetch_assoc()) {
                                                                                                    $totalProductsOrdered = $totalProductsOrdered + $productsTableCompletedProjectRow['p_measurement'];
                                                                                                }
                                                                                                echo $totalProductsOrdered." Piece"; if ($totalProductsOrdered > 1) {echo "s";};
                                                                                            }
                                                                                            else {
                                                                                                echo "None";
                                                                                            }
                                                                                        ?>
                                                                                    </label><br>
                                                                                    <div class="overflow-auto" style="max-width: 100%; max-height: 70px;">
                                                                                        <?php
                                                                                            $productUsedTableName = $database->query("SELECT product_name_tbl.p_name AS p_name_word, product_used_tbl.p_name AS p_name_number, product_used_tbl.p_sold_to FROM product_used_tbl INNER JOIN product_name_tbl ON product_used_tbl.p_name = product_name_tbl.ID WHERE product_used_tbl.p_sold_to = '$projID' GROUP BY product_used_tbl.p_name");
                                                                                            if ($productUsedTableName->num_rows > 0) {
                                                                                                while ($productUsedTableNameRow = $productUsedTableName->fetch_assoc()) {
                                                                                                    $productNameNumber = $productUsedTableNameRow['p_name_number'];
                                                                                                    $totalProductCount = 0;
                                                                                                    $totalProductPrice = 0;
                                                                                                    $productUsedTableCountNames = $database->query("SELECT * FROM product_used_tbl WHERE p_sold_to = '$projID' AND p_name = '$productNameNumber'");
                                                                                                    while ($productUsedTableCountNamesRow = $productUsedTableCountNames->fetch_assoc()) {
                                                                                                        $totalProductCount++;
                                                                                                        $totalProductPrice = $totalProductPrice + $productUsedTableCountNamesRow['p_price'];
                                                                                                    }
                                                                                                    echo "x".$totalProductCount." - ".$productUsedTableNameRow['p_name_word']." (₱".number_format((float)$totalProductPrice, 2, '.', ',').") <br>";
                                                                                                }
                                                                                            }
                                                                                            else {
                                                                                                echo "None";
                                                                                            }
                                                                                        ?>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php 
                                                }
                                            }
                                            else { 
                                                ?>
                                                    <div class="col text-center my-3">
                                                        <div class="row">
                                                            <h1 class="bi bi-clipboard-check"></h1>
                                                        </div>
                                                        <div class="row">
                                                            <h4>There are no completed projects yet.</h4>
                                                        </div>
                                                    </div>
                                                <?php 
                                            }
                                        ?>
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
                                                                <input type="date" class="form-control" id="projRecordStartDate" name="projRecordStartDate" required>
                                                            </div>
                                                            <div class="col">
                                                                <label class="form-label">End Date</label>
                                                                <input type="date" class="form-control" id="projRecordEndDate" name="projRecordEndDate" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary" id="projPrintCompletedRecords" name="projPrintCompletedRecords">Proceed</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- ==================== END - COMPLETED PROJECTS TAB ==================== -->

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - PROJECTS ROW ==================== -->

            <!-- ==================== START - ADD PROJECT ROW ==================== -->

            <div class="row">
                <div class="col">
                    <form class="needs-validation" action="" method="POST" novalidate>
                        <div class="modal fade" id="modalNewProject" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">New Project</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label class="form-label">Project Title</label>
                                            <input type="text" class="form-control" id="projectTitle" name="projectTitle" required>
                                            <div class="invalid-feedback">
                                                Please Input a Project Title
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Client Name</label>
                                            <input type="text" class="form-control" id="projectClient" name="projectClient" required>
                                            <div class="invalid-feedback">
                                                Please Input the Client Name
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Delivery Date</label>
                                            <input type="date" class="form-control limitedDate" id="projectDelivery" name="projectDelivery" required>
                                            <div class="invalid-feedback">
                                                Specify expected Delivery Date
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">Sales Representative</label>
                                            <select id="selectProjectSR" name="selectProjectSR" class="form-select" required>
                                                <option value="">Choose A Sales Representative...</option>
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
                                        <div class="w-100">
                                            <div class="col-12">
                                                <button type="submit" id="addProject" name="addProject" class="btn btn-primary w-100">Create Project</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================== END - ADD PROJECT ROW ==================== -->

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