<?php
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "history";

    date_default_timezone_set('Asia/Manila');

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $stocksMovementQuery = "SELECT admins_tbl.first_name, admins_tbl.last_name, stocks_movement_tbl.sm_date, stocks_movement_tbl.sm_event, stocks_movement_tbl.sm_category, stocks_movement_tbl.sm_method FROM `stocks_movement_tbl` INNER JOIN admins_tbl ON stocks_movement_tbl.sm_admin = admins_tbl.ID ORDER BY stocks_movement_tbl.ID DESC";
    $stocksMovement = $database->query($stocksMovementQuery) or die ($database->error);

    $purchaseHistoryQuery = "SELECT admins_tbl.first_name, admins_tbl.last_name, purchase_history_tbl.ph_date, purchase_history_tbl.ph_event, purchase_history_tbl.ph_method FROM `purchase_history_tbl` INNER JOIN admins_tbl ON purchase_history_tbl.ph_admin = admins_tbl.ID ORDER BY purchase_history_tbl.ID DESC";
    $purchaseHistory = $database->query($purchaseHistoryQuery) or die ($database->error);

    $salesHistoryQuery = "SELECT admins_tbl.first_name, admins_tbl.last_name, sales_history_tbl.sh_date, sales_history_tbl.sh_event, sales_history_tbl.sh_method FROM `sales_history_tbl` INNER JOIN admins_tbl ON sales_history_tbl.sh_admin = admins_tbl.ID ORDER BY sales_history_tbl.ID DESC";
    $salesHistory = $database->query($salesHistoryQuery) or die ($database->error);

    $accountsHistoryQuery = "SELECT admins_tbl.first_name, admins_tbl.last_name, accounts_history_tbl.ah_date, accounts_history_tbl.ah_event, accounts_history_tbl.ah_method FROM `accounts_history_tbl` INNER JOIN admins_tbl ON accounts_history_tbl.ah_admin = admins_tbl.ID ORDER BY accounts_history_tbl.ID DESC";
    $accountsHistory = $database->query($accountsHistoryQuery) or die ($database->error);
    

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();

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
    <title>History</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    
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

            <!-- ==================== START - PROJECTS TITLE ROW ==================== -->

            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-history fs-3'></i>&emsp;History
                    </p>
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
                                    <a class="nav-link text-dark border-top-primary active" href="#stocksMovement" data-bs-toggle="tab">Stocks Movement</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-primary" href="#purchaseHistory" data-bs-toggle="tab">Purchase History</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-primary" href="#salesHistory" data-bs-toggle="tab">Sales History</a>
                                </li>
                                <li class="nav-item ms-auto">
                                    <a class="nav-link text-dark border-top-primary" href="#accountsHistory" data-bs-toggle="tab">Accounts</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content history-tables">
                                <div class="tab-pane active" id="stocksMovement">
                                    <label class="mb-2">Legend: </label>
                                    <div class="row mb-4" style="font-size: 14px;">
                                        <div class="col-sm-7 col-md-5 col-xxl-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-success fs-5'></i>&nbsp;- Added, Recovered, Returned
                                        </div>
                                        <div class="col-sm-5 col-md-3 col-xxl-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-info fs-5'></i>&nbsp;- Modified
                                        </div>
                                        <div class="col-sm-7 col-md-4 col-xxl-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-warning fs-5'></i>&nbsp;- Marked as Defective
                                        </div>
                                        <div class="col-sm-5 col-md-5 col-xxl-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-danger fs-5'></i>&nbsp;- Deleted
                                        </div>
                                        <div class="col-sm-7 col-md-3 col-xxl-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-purple fs-5'></i>&nbsp;- Updated
                                        </div>
                                        <div class="col-sm-5 col-md-4 col-xxl-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-secondary fs-5'></i>&nbsp;- Used
                                        </div>
                                    </div>
                                    <table class="datatable-no-sort table table-sm table-hover responsive nowrap w-100">
                                        <thead class="bg-primary text-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Event</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $stocksMovement->fetch_array()){ ?>
                                                <tr class="
                                                    <?php
                                                        if ($fetch['sm_method'] == 'Added' || $fetch['sm_method'] == 'Recovered' || $fetch['sm_method'] == 'Returned') {
                                                            echo "table-row-success";
                                                        }
                                                        else if ($fetch['sm_method'] == 'Edited'){
                                                            echo "table-row-info";
                                                        }
                                                        else if ($fetch['sm_method'] == 'Defective'){
                                                            echo "table-row-warning";
                                                        }
                                                        else if ($fetch['sm_method'] == 'Deleted'){
                                                            echo "table-row-danger";
                                                        }
                                                        else if ($fetch['sm_method'] == 'Scanned') {
                                                            echo "table-row-purple";
                                                        }
                                                        else if ($fetch['sm_method'] == 'Used') {
                                                            echo "table-row-secondary";
                                                        }
                                                    ?>">
                                                    <td><?php echo date('F j, Y, h:i a', strtotime($fetch['sm_date']))?></td>
                                                    <td>
                                                        <?php 
                                                            if ($fetch['sm_method'] == 'Added') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Added {$fetch['sm_event']} to the {$fetch['sm_category']} Storage";
                                                            }
                                                            else if ($fetch['sm_method'] == 'Recovered'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Recovered the {$fetch['sm_event']} from the {$fetch['sm_category']} Storage";
                                                            }
                                                            else if ($fetch['sm_method'] == 'Returned') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Returned {$fetch['sm_event']} to {$fetch['sm_category']} Storage";
                                                            }
                                                            else if ($fetch['sm_method'] == 'Edited'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Modified the {$fetch['sm_event']} from the {$fetch['sm_category']} Storage";
                                                            }
                                                            else if ($fetch['sm_method'] == 'Defective'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Marked the {$fetch['sm_event']} as Defective from the {$fetch['sm_category']} Storage";
                                                            }
                                                            else if ($fetch['sm_method'] == 'Deleted'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Deleted the {$fetch['sm_event']} from the {$fetch['sm_category']} Storage";
                                                            }
                                                            else if ($fetch['sm_method'] == 'Scanned'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Updated the status of {$fetch['sm_event']}";
                                                            }
                                                            else if ($fetch['sm_method'] == 'Used') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Used {$fetch['sm_event']}";
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="tab-pane" id="purchaseHistory">
                                    <label class="mb-2">Legend: </label>
                                    <div class="row mb-4" style="font-size: 14px;">
                                        <div class="col-sm-6 col-md-4 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-success fs-5'></i>&nbsp;- Created
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-info fs-5'></i>&nbsp;- Edited
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-danger fs-5'></i>&nbsp;- Deleted
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-purple fs-5'></i>&nbsp;- Unlocked
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-orange fs-5'></i>&nbsp;- Locked
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-primary fs-5'></i>&nbsp;- Completed
                                        </div>
                                    </div>
                                    <table class="datatable-no-sort table table-sm table-hover responsive nowrap w-100">
                                        <thead class="bg-primary text-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Event</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $purchaseHistory->fetch_array()){ ?>
                                                <tr class="
                                                    <?php
                                                        if ($fetch['ph_method'] == 'Created') {
                                                            echo "table-row-success";
                                                        }
                                                        else if ($fetch['ph_method'] == 'Edited'){
                                                            echo "table-row-info";
                                                        }
                                                        else if ($fetch['ph_method'] == 'Deleted') {
                                                            echo "table-row-danger";
                                                        }
                                                        else if ($fetch['ph_method'] == 'Unlocked'){
                                                            echo "table-row-purple";
                                                        }
                                                        else if ($fetch['ph_method'] == 'Locked'){
                                                            echo "table-row-orange";
                                                        }
                                                        else if ($fetch['ph_method'] == 'Completed'){
                                                            echo "table-row-primary";
                                                        }
                                                    ?>
                                                ">
                                                    <td><?php echo date('F j, Y, h:i a', strtotime($fetch['ph_date']))?></td>
                                                    <td>
                                                        <?php 
                                                            if ($fetch['ph_method'] == 'Created') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Created a new Purchase Group ({$fetch['ph_event']})";
                                                            }
                                                            else if ($fetch['ph_method'] == 'Edited'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Edited the {$fetch['ph_event']}";
                                                            }
                                                            else if ($fetch['ph_method'] == 'Deleted'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Deleted the Purchase Group {$fetch['ph_event']}";
                                                            }
                                                            else if ($fetch['ph_method'] == 'Unlocked'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Unlocked the Group {$fetch['ph_event']}";
                                                            }
                                                            else if ($fetch['ph_method'] == 'Locked'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Locked the Group {$fetch['ph_event']}";
                                                            }
                                                            else if ($fetch['ph_method'] == 'Completed'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Marked the Purchase Group {$fetch['ph_event']} as Completed";
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="tab-pane" id="salesHistory">
                                    <label class="mb-2">Legend: </label>
                                    <div class="row mb-4" style="font-size: 14px;">
                                        <div class="col-sm-6 col-md-3 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-success fs-5'></i>&nbsp;- Created
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-lg-3 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-info fs-5'></i>&nbsp;- Edited, Adjusted
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-lg-3 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-danger fs-5'></i>&nbsp;- Deleted, Removed
                                        </div>
                                        <div class="col-sm-6 col-md-3 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-purple fs-5'></i>&nbsp;- Added
                                        </div>
                                        <div class="col-sm-6 col-md-4 col-lg-2 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-orange fs-5'></i>&nbsp;- Completed
                                        </div>
                                    </div>
                                    <table class="datatable-no-sort table table-sm table-hover responsive nowrap w-100">
                                        <thead class="bg-primary text-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Event</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $salesHistory->fetch_array()){ ?>
                                                <tr class="
                                                    <?php
                                                        if ($fetch['sh_method'] == 'Created') {
                                                            echo "table-row-success";
                                                        }
                                                        else if ($fetch['sh_method'] == 'Edited' || $fetch['sh_method'] == 'Adjusted'){
                                                            echo "table-row-info";
                                                        }
                                                        else if ($fetch['sh_method'] == 'Deleted' || $fetch['sh_method'] == 'Removed') {
                                                            echo "table-row-danger";
                                                        }
                                                        else if ($fetch['sh_method'] == 'Added') {
                                                            echo "table-row-warning";
                                                        }
                                                        else if ($fetch['sh_method'] == 'Completed') {
                                                            echo "table-row-primary";
                                                        }
                                                    ?>
                                                ">
                                                    <td><?php echo date('F j, Y, h:i a', strtotime($fetch['sh_date']))?></td>
                                                    <td>
                                                        <?php 
                                                            if ($fetch['sh_method'] == 'Created') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Created a new Project ({$fetch['sh_event']})";
                                                            }
                                                            else if ($fetch['sh_method'] == 'Edited'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Edited the details of {$fetch['sh_event']}";
                                                            }
                                                            else if ($fetch['sh_method'] == 'Adjusted') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Adjusted the delivery date of {$fetch['sh_event']}";
                                                            }
                                                            else if ($fetch['sh_method'] == 'Deleted'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Deleted the project {$fetch['sh_event']}";
                                                            }
                                                            else if ($fetch['sh_method'] == 'Removed') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Removed {$fetch['sh_event']}";
                                                            }
                                                            else if ($fetch['sh_method'] == 'Added'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Added {$fetch['sh_event']}";
                                                            }
                                                            else if ($fetch['sh_method'] == 'Completed') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} marked the {$fetch['sh_event']} as Completed";
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="tab-pane" id="accountsHistory">
                                    <label class="mb-2">Legend: </label>
                                    <div class="row mb-4" style="font-size: 14px;">
                                        <div class="col-sm-6 col-md-3 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-success fs-5'></i>&nbsp;- Signed In
                                        </div>
                                        <div class="col-sm-6 col-md-3 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-info fs-5'></i>&nbsp;- Signed Out
                                        </div>
                                        <div class="col-sm-6 col-md-3 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-danger fs-5'></i>&nbsp;- Created
                                        </div>
                                        <div class="col-sm-6 col-md-3 d-flex align-items-center">
                                            <i class='bx bxs-square text-table-purple fs-5'></i>&nbsp;- Deleted
                                        </div>
                                    </div>
                                    <table class="datatable-no-sort table table-sm table-hover responsive nowrap w-100">
                                        <thead class="bg-primary text-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Event</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $accountsHistory->fetch_array()){ ?>
                                                <tr class="
                                                    <?php
                                                        if ($fetch['ah_method'] == 'In') {
                                                            echo "table-row-success";
                                                        }
                                                        else if ($fetch['ah_method'] == 'Out'){
                                                            echo "table-row-danger";
                                                        }
                                                        else if ($fetch['ah_method'] == 'Created') {
                                                            echo "table-row-primary";
                                                        }
                                                        else if ($fetch['ah_method'] == 'Deleted') {
                                                            echo "table-row-warning";
                                                        }
                                                    ?>
                                                ">
                                                    <td><?php echo date('F j, Y, h:i a', strtotime($fetch['ah_date']))?></td>
                                                    <td>
                                                        <?php 
                                                            if ($fetch['ah_method'] == 'In') {
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Signed into the System";
                                                            }
                                                            else if ($fetch['ah_method'] == 'Out'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Signed out from the System";
                                                            }
                                                            else if ($fetch['ah_method'] == 'Created'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Created a new account named {$fetch['ah_event']}";
                                                            }
                                                            else if ($fetch['ah_method'] == 'Deleted'){
                                                                echo "{$fetch['first_name']} {$fetch['last_name']} Deleted the account of {$fetch['ah_event']}";
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - PROJECTS ROW ==================== -->

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