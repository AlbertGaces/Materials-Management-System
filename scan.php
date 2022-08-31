<?php
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "scan";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();

    date_default_timezone_set('Asia/Manila');

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $CodePhoto = "/image-placeholder.png";
    $Code = "";
    $Type = "";
    $Name = "";
    $Price = "";
    $Unit = "";
    $Quantity = "";
    $Status = "Storage";

    // ==================== END - QUERIES ====================

    // ==================== START - SCAN PROCESS ====================

    if(isset($_POST['btnScanProcessCode'])){
        $scanPreCode = $_POST['inputScanPreCode'];
        $scanStatus = $_POST['radioScanStatusOptions'];
        
        if ($scanPreCode[0] == 'M' && $scanPreCode[1] == '-'){
            $materialsTableQuery = $database->query("SELECT materials_tbl.m_code_photo, materials_tbl.m_code, material_type_tbl.m_type, material_name_tbl.m_name, materials_tbl.m_price, materials_tbl.m_unit, materials_tbl.m_measurement, materials_tbl.m_remaining, materials_tbl.m_status, materials_tbl.m_quality
                                                    FROM materials_tbl 
                                                    JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID 
                                                    JOIN material_type_tbl ON materials_tbl.m_type = material_type_tbl.ID 
                                                    WHERE materials_tbl.m_code = '$scanPreCode'");
            if ($materialsTableQuery->num_rows > 0) {
                while ($materialsTableQueryRow = $materialsTableQuery->fetch_assoc()) {
                    if ($materialsTableQueryRow['m_quality'] == 'Bad') {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'This material is Defective',
                                    text: '{$materialsTableQueryRow['m_name']} {$materialsTableQueryRow['m_code']} is marked as defective',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 3000
                                });
                            </script>
                        ";
                    }
                    else if ($materialsTableQueryRow['m_quality'] == 'Trash') {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'This material has been Deleted',
                                    text: '{$materialsTableQueryRow['m_name']} {$materialsTableQueryRow['m_code']} has been deleted',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 3000
                                });
                            </script>
                        ";
                    }
                    else {
                        if ($materialsTableQueryRow['m_status'] == 'Used') {
                            $_SESSION['message']= "
                                <script>
                                    Swal.fire({
                                        position: 'center',
                                        icon: 'error',
                                        title: 'This material has been Used',
                                        text: '{$materialsTableQueryRow['m_name']} {$materialsTableQueryRow['m_code']} has been used',
                                        showConfirmButton: false,
                                        timerProgressBar: true,
                                        timer: 3000
                                    });
                                </script>
                            ";
                        }
                        else {
                            $currentDate = date("Y-m-d H:i:s");
                            if ($materialsTableQueryRow['m_status'] != $scanStatus) {
                                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$currentDate','{$materialsTableQueryRow['m_name']} ({$materialsTableQueryRow['m_code']}) from {$materialsTableQueryRow['m_status']} to $scanStatus','Materials','Scanned')") or die ($database->error);
                                $database->query("UPDATE `materials_tbl` SET `m_status`='$scanStatus' WHERE `m_code`='$scanPreCode'") or die ($database->error);
                            }
                            else {
                                $_SESSION['message']= "
                                    <script>
                                        Swal.fire({
                                            position: 'center',
                                            icon: 'info',
                                            title: 'Nothing Changed',
                                            showConfirmButton: false,
                                            timerProgressBar: true,
                                            timer: 2000
                                        });
                                    </script>
                                ";
                            }
                            $CodePhoto = "codes/" . $materialsTableQueryRow['m_code_photo'];
                            $Code = $materialsTableQueryRow['m_code'];
                            $Type = $materialsTableQueryRow['m_type'];
                            $Name = $materialsTableQueryRow['m_name'];
                            $Price = $materialsTableQueryRow['m_price'];
                            $Measurement = $materialsTableQueryRow['m_measurement'];
                            if ($Measurement > 1) {
                                $Unit = $materialsTableQueryRow['m_unit']."s"; 
                            }
                            else {
                                $Unit = $materialsTableQueryRow['m_unit']; 
                            }
                            $Remaining = $materialsTableQueryRow['m_remaining']; 
                            $Status = $scanStatus;
                        }
                    }
                }
            }
            else {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Invalid QR Code',
                            text: 'This QR Code cannot be recognized by the system',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 3000
                        });
                    </script>
                ";
            }
            
        }
        else if ($scanPreCode[0] == 'P' && $scanPreCode[1] == '-'){
            $productsTableQuery = $database->query("SELECT products_tbl.p_code_photo, products_tbl.p_code, product_name_tbl.p_name, products_tbl.p_price, products_tbl.p_measurement, products_tbl.p_remaining, products_tbl.p_status, products_tbl.p_quality
                                                    FROM `products_tbl` 
                                                    JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID
                                                    WHERE `p_code` = '$scanPreCode'");
            if ($productsTableQuery->num_rows > 0) {
                while ($productsTableQueryRow = $productsTableQuery->fetch_assoc()) {
                    if ($productsTableQueryRow['p_quality'] == 'Bad') {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'This Product is Defective',
                                    text: '{$productsTableQueryRow['p_name']} {$productsTableQueryRow['p_code']} is marked as defective',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 3000
                                });
                            </script>
                        ";
                    }
                    else if ($productsTableQueryRow['p_quality'] == 'Trash') {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'This product has been Deleted',
                                    text: '{$productsTableQueryRow['p_name']} {$productsTableQueryRow['p_code']} has been deleted',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 3000
                                });
                            </script>
                        ";
                    }
                    else {
                        if ($productsTableQueryRow['p_status'] == 'Used') {
                            $_SESSION['message']= "
                                <script>
                                    Swal.fire({
                                        position: 'center',
                                        icon: 'error',
                                        title: 'This product has been Used',
                                        text: '{$productsTableQueryRow['p_name']} {$productsTableQueryRow['p_code']} has been used',
                                        showConfirmButton: false,
                                        timerProgressBar: true,
                                        timer: 3000
                                    });
                                </script>
                            ";
                        }
                        else {
                            $currentDate = date("Y-m-d H:i:s");
                            if ($productsTableQueryRow['p_status'] != $scanStatus) {
                                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$currentDate','{$productsTableQueryRow['p_name']} ({$productsTableQueryRow['p_code']}) from {$productsTableQueryRow['p_status']} to $scanStatus','Products','Scanned')") or die ($database->error);
                                $database->query("UPDATE `products_tbl` SET `p_status`='$scanStatus' WHERE `p_code`='$scanPreCode'") or die ($database->error);
                            }
                            else {
                                $_SESSION['message']= "
                                    <script>
                                        Swal.fire({
                                            position: 'center',
                                            icon: 'info',
                                            title: 'Nothing Changed',
                                            showConfirmButton: false,
                                            timerProgressBar: true,
                                            timer: 2000
                                        });
                                    </script>
                                ";
                            }
                            $CodePhoto = "codes/" . $productsTableQueryRow['p_code_photo'];
                            $Code = $productsTableQueryRow['p_code'];
                            $Type = '';
                            $Name = $productsTableQueryRow['p_name'];
                            $Price = $productsTableQueryRow['p_price'];
                            $Remaining = $productsTableQueryRow['p_remaining']; 
                            $Measurement = $productsTableQueryRow['p_measurement'];
                            if ($Measurement > 1) {
                                $Unit = "Pieces"; 
                            }
                            else {
                                $Unit = "Piece"; 
                            }
                            $Status = $scanStatus;
                        }
                    }
                }
            }
            else {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Invalid QR Code',
                            text: 'This QR Code cannot be recognized by the system',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 3000
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
                        title: 'Invalid QR Code',
                        text: 'This QR Code cannot be recognized by the system',
                        showConfirmButton: false,
                        timerProgressBar: true,
                        timer: 3000
                    });
                </script>
            ";
        }
    }

    // ==================== END - SCAN PROCESS ====================

    // ==================== START - SCAN PROCESS ====================

    if(isset($_POST['btnScanProcessManualCode'])){
        $scanPreCode = $_POST['inputManualScanCode'];
        $scanStatus = $_POST['radioScanStatusManualOptions'];
        
        if ($scanPreCode[0] == 'M' && $scanPreCode[1] == '-'){
            $materialsTableQuery = $database->query("SELECT materials_tbl.m_code_photo, materials_tbl.m_code, material_type_tbl.m_type, material_name_tbl.m_name, materials_tbl.m_price, materials_tbl.m_unit, materials_tbl.m_measurement, materials_tbl.m_remaining, materials_tbl.m_status, materials_tbl.m_quality
                                                    FROM materials_tbl 
                                                    JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID 
                                                    JOIN material_type_tbl ON materials_tbl.m_type = material_type_tbl.ID 
                                                    WHERE materials_tbl.m_code = '$scanPreCode'");
            if ($materialsTableQuery->num_rows > 0) {
                while ($materialsTableQueryRow = $materialsTableQuery->fetch_assoc()) {
                    if ($materialsTableQueryRow['m_quality'] == 'Bad') {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'This material is Defective',
                                    text: '{$materialsTableQueryRow['m_name']} {$materialsTableQueryRow['m_code']} is marked as defective',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 3000
                                });
                            </script>
                        ";
                    }
                    else if ($materialsTableQueryRow['m_quality'] == 'Trash') {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'This material has been Deleted',
                                    text: '{$materialsTableQueryRow['m_name']} {$materialsTableQueryRow['m_code']} has been deleted',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 3000
                                });
                            </script>
                        ";
                    }
                    else {
                        if ($materialsTableQueryRow['m_status'] == 'Used') {
                            $_SESSION['message']= "
                                <script>
                                    Swal.fire({
                                        position: 'center',
                                        icon: 'error',
                                        title: 'This material has been Used',
                                        text: '{$materialsTableQueryRow['m_name']} {$materialsTableQueryRow['m_code']} has been used',
                                        showConfirmButton: false,
                                        timerProgressBar: true,
                                        timer: 3000
                                    });
                                </script>
                            ";
                        }
                        else {
                            $currentDate = date("Y-m-d H:i:s");
                            if ($materialsTableQueryRow['m_status'] != $scanStatus) {
                                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$currentDate','{$materialsTableQueryRow['m_name']} ({$materialsTableQueryRow['m_code']}) from {$materialsTableQueryRow['m_status']} to $scanStatus','Materials','Scanned')") or die ($database->error);
                                $database->query("UPDATE `materials_tbl` SET `m_status`='$scanStatus' WHERE `m_code`='$scanPreCode'") or die ($database->error);
                            }
                            else {
                                $_SESSION['message']= "
                                    <script>
                                        Swal.fire({
                                            position: 'center',
                                            icon: 'info',
                                            title: 'Nothing Changed',
                                            showConfirmButton: false,
                                            timerProgressBar: true,
                                            timer: 2000
                                        });
                                    </script>
                                ";
                            }
                            $CodePhoto = "codes/" . $materialsTableQueryRow['m_code_photo'];
                            $Code = $materialsTableQueryRow['m_code'];
                            $Type = $materialsTableQueryRow['m_type'];
                            $Name = $materialsTableQueryRow['m_name'];
                            $Price = $materialsTableQueryRow['m_price'];
                            $Measurement = $materialsTableQueryRow['m_measurement'];
                            if ($Measurement > 1) {
                                $Unit = $materialsTableQueryRow['m_unit']."s"; 
                            }
                            else {
                                $Unit = $materialsTableQueryRow['m_unit']; 
                            }
                            $Remaining = $materialsTableQueryRow['m_remaining']; 
                            $Status = $scanStatus;
                        }
                    }
                }
            }
            else {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Invalid QR Code',
                            text: 'This QR Code cannot be recognized by the system',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 3000
                        });
                    </script>
                ";
            }
            
        }
        else if ($scanPreCode[0] == 'P' && $scanPreCode[1] == '-'){
            $productsTableQuery = $database->query("SELECT products_tbl.p_code_photo, products_tbl.p_code, product_name_tbl.p_name, products_tbl.p_price, products_tbl.p_measurement, products_tbl.p_remaining, products_tbl.p_status, products_tbl.p_quality
                                                    FROM `products_tbl` 
                                                    JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID
                                                    WHERE `p_code` = '$scanPreCode'");
            if ($productsTableQuery->num_rows > 0) {
                while ($productsTableQueryRow = $productsTableQuery->fetch_assoc()) {
                    if ($productsTableQueryRow['p_quality'] == 'Bad') {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'This Product is Defective',
                                    text: '{$productsTableQueryRow['p_name']} {$productsTableQueryRow['p_code']} is marked as defective',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 3000
                                });
                            </script>
                        ";
                    }
                    else if ($productsTableQueryRow['p_quality'] == 'Trash') {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'This product has been Deleted',
                                    text: '{$productsTableQueryRow['p_name']} {$productsTableQueryRow['p_code']} has been deleted',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 3000
                                });
                            </script>
                        ";
                    }
                    else {
                        if ($productsTableQueryRow['p_status'] == 'Used') {
                            $_SESSION['message']= "
                                <script>
                                    Swal.fire({
                                        position: 'center',
                                        icon: 'error',
                                        title: 'This product has been Used',
                                        text: '{$productsTableQueryRow['p_name']} {$productsTableQueryRow['p_code']} has been used',
                                        showConfirmButton: false,
                                        timerProgressBar: true,
                                        timer: 3000
                                    });
                                </script>
                            ";
                        }
                        else {
                            $currentDate = date("Y-m-d H:i:s");
                            if ($productsTableQueryRow['p_status'] != $scanStatus) {
                                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$currentDate','{$productsTableQueryRow['p_name']} ({$productsTableQueryRow['p_code']}) from {$productsTableQueryRow['p_status']} to $scanStatus','Products','Scanned')") or die ($database->error);
                                $database->query("UPDATE `products_tbl` SET `p_status`='$scanStatus' WHERE `p_code`='$scanPreCode'") or die ($database->error);
                            }
                            else {
                                $_SESSION['message']= "
                                    <script>
                                        Swal.fire({
                                            position: 'center',
                                            icon: 'info',
                                            title: 'Nothing Changed',
                                            showConfirmButton: false,
                                            timerProgressBar: true,
                                            timer: 2000
                                        });
                                    </script>
                                ";
                            }
                            $CodePhoto = "codes/" . $productsTableQueryRow['p_code_photo'];
                            $Code = $productsTableQueryRow['p_code'];
                            $Type = '';
                            $Name = $productsTableQueryRow['p_name'];
                            $Price = $productsTableQueryRow['p_price'];
                            $Remaining = $productsTableQueryRow['p_remaining']; 
                            $Measurement = $productsTableQueryRow['p_measurement'];
                            if ($Measurement > 1) {
                                $Unit = "Pieces"; 
                            }
                            else {
                                $Unit = "Piece"; 
                            }
                            $Status = $scanStatus;
                        }
                    }
                }
            }
            else {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Invalid QR Code',
                            text: 'This QR Code cannot be recognized by the system',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 3000
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
                        title: 'Invalid QR Code',
                        text: 'This QR Code cannot be recognized by the system',
                        showConfirmButton: false,
                        timerProgressBar: true,
                        timer: 3000
                    });
                </script>
            ";
        }
    }

    // ==================== END - SCAN PROCESS ====================

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
    <title>Scan</title>

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

            <!-- ==================== START - SCAN TITLE ROW ==================== -->

            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-qr-scan fs-3'></i>&emsp;Scan
                    </p>
                </div>
            </div>

            <!-- ==================== END - SCAN TITLE ROW ==================== -->

            <!-- ==================== START - SCAN ROW ==================== -->

            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="scanTabs">
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-primary active" href="#sView" data-bs-toggle="tab" id="tabView">View</a>
                                </li>
                                <li class="nav-item ms-auto">
                                    <a class="nav-link text-dark border-top-primary" href="#sScan" data-bs-toggle="tab" id="tabScan">Scan</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-primary" href="#sManual" data-bs-toggle="tab" id="tabManual">Manual</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- ==================== START - VIEW TAB ==================== -->

                                <div class="tab-pane active" id="sView">
                                    <div class="row">
                                        <div class="col-sm-12 col-md-4">
                                            <div class="card code-container d-flex align-items-center">
                                                <img src="images/<?php echo $CodePhoto?>" class="card-img-top img-fluid my-4" style="max-width: 350px;">
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-8 mt-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-sm-12 col-lg-12 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Code</label>
                                                            <input type="text" class="form-control" id="inputScanPostCode" name="inputScanPostCode" value="<?php echo $Code?>" readonly>
                                                        </div>
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Name</label>
                                                            <input type="text" class="form-control" id="inputScanName" name="inputScanName" value="<?php echo $Name?>" readonly>
                                                        </div>
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Type</label>
                                                            <input type="text" class="form-control" id="inputScanType" name="inputScanType" value="<?php echo $Type?>"readonly>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Price</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" class="form-control" id="inputScanPrice" name="inputScanPrice" value="<?php echo $Price?>" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Measurement</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="inputScanMeasurement" name="inputScanMeasurement" value="<?php echo $Measurement?>" readonly>
                                                                <input type="text" class="form-control" id="inputScanUnit" name="inputScanUnit" value="<?php echo $Unit?>" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Remaining</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="inputScanRemaining" name="inputScanRemaining" value="<?php echo $Remaining?>" readonly>
                                                                <input type="text" class="form-control" id="inputScanUnit" name="inputScanUnit" value="<?php echo $Unit?>" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <label for="radioScanStatus" class="form-label d-flex justify-content-center">Set status to:</label>
                                                        <div class="input-group d-flex justify-content-center">
                                                            <div class="btn-group w-50" role="group">
                                                                <input type="radio" class="btn-check" name="radioScanStatusOptions" id="radioScanStatusStorage" value="Storage" autocomplete="off" <?php if ($Status == "Storage") echo "checked"?>>
                                                                <label class="btn btn-outline-success" for="radioScanStatusStorage">Storage</label>
                                                            
                                                                <input type="radio" class="btn-check" name="radioScanStatusOptions" id="radioScanStatusProcessing" value="Processing" autocomplete="off" <?php if ($Status == "Processing") echo "checked"?>>
                                                                <label class="btn btn-outline-orange" for="radioScanStatusProcessing">Processing</label>
                                                            </div>
                                                        </div>
                                                        <input type="text" class="form-control form-control-sm w-25" id="inputScanPreCode" name="inputScanPreCode" style="opacity: 0; pointer-events: none; position: absolute;">
                                                        <button type="submit" name="btnScanProcessCode" id="btnScanProcessCode" style="display: none;"></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ==================== END - VIEW TAB ==================== -->

                                <!-- ==================== START - SCAN TAB ==================== -->

                                <div class="tab-pane text-center" id="sScan">
                                    <video id="sCamera"></video>
                                </div>

                                <!-- ==================== END - SCAN TAB ==================== -->

                                <!-- ==================== START - MANUAL TAB ==================== -->

                                <div class="tab-pane" id="sManual">
                                    <div class="row">
                                        <div class="col-sm-12 col-md-4">
                                            <div class="card code-container d-flex align-items-center">
                                                <img src="images/<?php echo $CodePhoto?>" class="card-img-top img-fluid my-4" style="max-width: 350px;">
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-8 mt-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <form action="" method="POST">
                                                        <div class="row mb-3">
                                                            <div class="col-sm-12 col-lg-6 mb-3">
                                                                <label class="form-label d-flex justify-content-center">Enter Code</label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control" id="inputManualScanCode" name="inputManualScanCode">
                                                                    <button type="submit" class="btn btn-primary" name="btnScanProcessManualCode" id="btnScanProcessManualCode">Find</button>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-12 col-lg-6 mb-3">
                                                                <label class="form-label d-flex justify-content-center">Set status to:</label>
                                                                <div class="btn-group w-100" role="group">
                                                                    <input type="radio" class="btn-check" name="radioScanStatusManualOptions" id="radioScanStatusManualStorage" value="Storage" autocomplete="off" <?php if ($Status == "Storage") echo "checked"?>>
                                                                    <label class="btn btn-outline-success" for="radioScanStatusManualStorage">Storage</label>
                                                                
                                                                    <input type="radio" class="btn-check" name="radioScanStatusManualOptions" id="radioScanStatusManualProcessing" value="Processing" autocomplete="off" <?php if ($Status == "Processing") echo "checked"?>>
                                                                    <label class="btn btn-outline-orange" for="radioScanStatusManualProcessing">Processing</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <div class="row">
                                                        <div class="col-sm-12 col-lg-12 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Code</label>
                                                            <input type="text" class="form-control" id="inputScanPostCode" name="inputScanPostCode" value="<?php echo $Code?>" readonly>
                                                        </div>
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Name</label>
                                                            <input type="text" class="form-control" id="inputScanName" name="inputScanName" value="<?php echo $Name?>" readonly>
                                                        </div>
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Type</label>
                                                            <input type="text" class="form-control" id="inputScanType" name="inputScanType" value="<?php echo $Type?>"readonly>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Price</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" class="form-control" id="inputScanPrice" name="inputScanPrice" value="<?php echo $Price?>" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Measurement</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="inputScanMeasurement" name="inputScanMeasurement" value="<?php echo $Measurement?>" readonly>
                                                                <input type="text" class="form-control" id="inputScanUnit" name="inputScanUnit" value="<?php echo $Unit?>" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12 col-lg-6 col-xl-4 mb-3">
                                                            <label class="form-label d-flex justify-content-center">Remaining</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" id="inputScanRemaining" name="inputScanRemaining" value="<?php echo $Remaining?>" readonly>
                                                                <input type="text" class="form-control" id="inputScanUnit" name="inputScanUnit" value="<?php echo $Unit?>" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ==================== END - MANUAL TAB ==================== -->

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - SCAN ROW ==================== -->

        </div>
    </div>

    <!-- ==================== END - MAIN CONTENT ==================== -->

    <script src="js/jquery.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.js"></script>
    <script src="js/instascan.min.js"></script>
    <script src="js/PassRequirements.js"></script>
    <script src="js/script.js"></script>
    <script>
        
        /* ==================== START - INITIALIZATION OF BUILT IN QR CODE SCANNER ==================== */

        var scanner = new Instascan.Scanner({ video: document.getElementById('sCamera'), scanPeriod: 5, mirror: false });

        document.getElementById('tabView').addEventListener("click", function(){
            scanner.stop();
        });

        document.getElementById('tabScan').addEventListener("click", function(){
            scanner.addListener('scan',function(content){
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Scanned!',
                    showConfirmButton: false,
                    timer: 2000
                });
                document.getElementById('inputScanPreCode').value = content;
                document.getElementById('btnScanProcessCode').click();
            });

            Instascan.Camera.getCameras().then(function (cameras){
                if(cameras.length>0){
                    scanner.start(cameras[0]);
                    $('[name="options"]').on('change',function(){
                        if($(this).val()==1){
                            if(cameras[0]!=""){
                                scanner.start(cameras[0]);
                            }
                            else{
                                alert('No Front camera found!');
                            }
                        }
                        else if($(this).val()==2){
                            if(cameras[1]!=""){
                                scanner.start(cameras[1]);
                            }
                            else{
                                alert('No Back camera found!');
                            }
                        }
                    });
                }
                else{
                    console.error('No cameras found.');
                    alert('No cameras found.');
                }
            }).catch(function(e){
                console.error(e);
                alert(e);
            });
        });

        /* ==================== END - INITIALIZATION OF BUILT IN QR CODE SCANNER ==================== */

        /* ==================== START - INITIALIZATION OF EXTERNAL QR CODE SCANNER ==================== */

        var inputScan = document.getElementById("inputScanPreCode");

        document.addEventListener("keydown", function(event) {
	        inputScan.focus();
        });

        /* ==================== START - INITIALIZATION OF EXTERNAL QR CODE SCANNER ==================== */
    </script>
</body>
</html>