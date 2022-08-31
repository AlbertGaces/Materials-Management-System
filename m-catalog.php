<?php 
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "materials";
    $subActivePage = "catalog";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================
    
    // ==================== START - QUERIES ====================

    $sqlMaterialsCatalogTable = "SELECT materials_tbl.ID, materials_tbl.m_code, materials_tbl.m_code_photo, purchase_group_tbl.pg_code, materials_tbl.m_purchase_group, material_name_tbl.m_name, material_name_tbl.m_photo, material_type_tbl.m_type, materials_tbl.m_price, materials_tbl.m_measurement, materials_tbl.m_remaining, materials_tbl.m_unit, materials_tbl.m_description, materials_tbl.m_status, materials_tbl.m_received
                                FROM materials_tbl
                                INNER JOIN purchase_group_tbl ON materials_tbl.m_purchase_group=purchase_group_tbl.ID
                                INNER JOIN material_type_tbl ON materials_tbl.m_type=material_type_tbl.ID
                                INNER JOIN material_name_tbl ON materials_tbl.m_name=material_name_tbl.ID 
                                WHERE m_quality = 'Good' AND m_status != 'Used' ORDER BY materials_tbl.m_received DESC";
    $materialsCatalog = $database->query($sqlMaterialsCatalogTable) or die ($database->error);

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();
    $maxStorage = $adminData['max_storage'];
    $materialsDeleted = $adminData['total_materials_deleted'];
    $adminName = $adminData['first_name']." ".$adminData['last_name'];

    include 'qrcodegenerator/qrlib.php';

    date_default_timezone_set('Asia/Manila');

    // ==================== END - QUERIES ====================

    // ==================== START - ADDING MATERIALS ====================

    if(isset($_POST['addMaterial'])){

        $mPurchaseGroup = $_POST['selectPurchaseGroup'];
        $mType = $_POST['selectMaterialType'];
        $mName = $_POST['selectMaterialName'];
        $mPrice = $_POST['inputMaterialPrice'];
        $mUnit = $_POST['selectMaterialUnit'];
        $mMeasurement = $_POST['inputMaterialMeasurement'];
        $mDescription = mysqli_real_escape_string($database, $_POST['inputMaterialDescription']);
        $mReceived = date("Y-m-d H:i:s");
        $mQuantity = $_POST['inputMaterialQuantity'];
        $mCounter = 1;

        if ($mUnit == "Piece") {
            if (fmod($mMeasurement,1) == 0.0) {
                $fetchMaterialsNameCounter = $database->query("SELECT COUNT(*) AS 'materialsNameCounter' FROM materials_tbl WHERE m_name = $mName AND m_quality = 'Good'")->fetch_assoc();
                $mNameCounter = ($fetchMaterialsNameCounter['materialsNameCounter']) + $mQuantity;

                if ($mNameCounter<$maxStorage) {
                    while ($mQuantity >= $mCounter) {
                        $fetchTotalMaterials =  $database->query("SELECT ID FROM `materials_tbl` ORDER BY ID DESC LIMIT 1")->fetch_assoc();
                        $mTotalMaterialsCounter = ($fetchTotalMaterials['ID']) + 1;
            
                        $mCodeCounter = str_pad($mTotalMaterialsCounter,5,"0",STR_PAD_LEFT);
                        $qrData = "M-" . $mCodeCounter;
                        $qrDataName = $qrData . '.png';
                        $qrLocation = 'images/codes/' . $qrDataName;
                        $ECC = 'H';
                        $qrPixelSize = 10;
                        $qrFrameSize = 10;
                        $qrcode = QRcode::png($qrData, $qrLocation, $ECC, $qrPixelSize, $qrFrameSize);
            
                        $sqlMaterialsTable = "INSERT INTO `materials_tbl` (`ID`, `m_code_photo`, `m_code`, `m_purchase_group`, `m_type`, `m_name`, `m_price`, `m_unit`, `m_measurement`, `m_remaining`, `m_description`, `m_status`, `m_quality`, `m_received`, `m_rejected`) VALUES (NULL, '$qrDataName', '$qrData', '$mPurchaseGroup', '$mType', '$mName', '$mPrice', '$mUnit', '$mMeasurement', '$mMeasurement', '$mDescription', 'Pending', 'Good', '$mReceived', '0000-00-00 00:00:00')";
                        $database->query($sqlMaterialsTable) or die ($database->error);

                        $mCounter++;
                    }

                    $convertMaterialNameQuery = $database->query("SELECT m_name FROM `material_name_tbl` WHERE ID = $mName")->fetch_assoc();

                    if ($mMeasurement > 1) {
                        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mReceived','$mQuantity, $mMeasurement {$mUnit}s of {$convertMaterialNameQuery['m_name']}','Materials','Added')") or die ($database->error);
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'Added $mQuantity, $mMeasurement {$mUnit}s of {$convertMaterialNameQuery['m_name']}',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 2000,
                                });
                            </script>
                        ";
                    }
                    else {
                        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mReceived','$mQuantity, $mMeasurement $mUnit of {$convertMaterialNameQuery['m_name']}','Materials','Added')") or die ($database->error);
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'Added $mQuantity, $mMeasurement $mUnit of {$convertMaterialNameQuery['m_name']}',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 2000,
                                });
                            </script>
                        ";
                    }
            
                    header("Refresh:2; url=materials.php");
                }
                else {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Maximum Storage Capacity Reached',
                                text: 'The max number of storage capacity has been reached, proceed to the settings if you want to expand the max storage capacity', 
                                showCancelButton: true,
                                cancelButtonColor: '#6c757d',
                                cancelButtonText: 'Ok',
                                confirmButtonColor: '#007bff',
                                confirmButtonText: 'Go To Settings'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'settings.php';
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
                            title: 'Invalid Measurement',
                            text: 'Pieces cannot have decimals',
                            showConfirmButton: false,
                            showCancelButton: true,
                            cancelButtonColor: '#6c757d',
                            cancelButtonText: 'Ok',
                        });
                    </script>
                ";
            }
            
        }
        else {
            $fetchMaterialsNameCounter = $database->query("SELECT COUNT(*) AS 'materialsNameCounter' FROM materials_tbl WHERE m_name = $mName AND m_quality = 'Good'")->fetch_assoc();
            $mNameCounter = ($fetchMaterialsNameCounter['materialsNameCounter']) + $mQuantity;

            if ($mNameCounter<$maxStorage) {
                while ($mQuantity >= $mCounter) {

                    $fetchTotalMaterials =  $database->query("SELECT ID FROM `materials_tbl` ORDER BY ID DESC LIMIT 1")->fetch_assoc();
                    $mTotalMaterialsCounter = ($fetchTotalMaterials['ID']) + 1;
        
                    $mCodeCounter = str_pad($mTotalMaterialsCounter,5,"0",STR_PAD_LEFT);
                    $qrData = "M-" . $mCodeCounter;
                    $qrDataName = $qrData . '.png';
                    $qrLocation = 'images/codes/' . $qrDataName;
                    $ECC = 'H';
                    $qrPixelSize = 10;
                    $qrFrameSize = 10;
                    $qrcode = QRcode::png($qrData, $qrLocation, $ECC, $qrPixelSize, $qrFrameSize);
        
                    $sqlMaterialsTable = "INSERT INTO `materials_tbl` (`ID`, `m_code_photo`, `m_code`, `m_purchase_group`, `m_type`, `m_name`, `m_price`, `m_unit`, `m_measurement`, `m_remaining`, `m_description`, `m_status`, `m_quality`, `m_received`, `m_rejected`) VALUES (NULL, '$qrDataName', '$qrData', '$mPurchaseGroup', '$mType', '$mName', '$mPrice', '$mUnit', '$mMeasurement', '$mMeasurement', '$mDescription', 'Pending', 'Good', '$mReceived', '0000-00-00 00:00:00')";
                        $database->query($sqlMaterialsTable) or die ($database->error);

                    $mCounter++;
                }

                $convertMaterialNameQuery = $database->query("SELECT m_name FROM `material_name_tbl` WHERE ID = $mName")->fetch_assoc();
                if ($mMeasurement > 1) {
                    $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mReceived','$mQuantity, $mMeasurement {$mUnit}s of {$convertMaterialNameQuery['m_name']}','Materials','Added')") or die ($database->error);
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Added $mQuantity, $mMeasurement {$mUnit}s of {$convertMaterialNameQuery['m_name']}',
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 2000,
                            });
                        </script>
                    ";
                }
                else {
                    $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mReceived','$mQuantity, $mMeasurement $mUnit of {$convertMaterialNameQuery['m_name']}','Materials','Added')") or die ($database->error);
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Added $mQuantity, $mMeasurement $mUnit of {$convertMaterialNameQuery['m_name']}',
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 2000,
                            });
                        </script>
                    ";
                }
        
                header("Refresh:2; url=materials.php");
            }

            else {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Maximum Storage Capacity Reached',
                            text: 'The max number of storage capacity has been reached, proceed to the settings if you want to expand the max storage capacity', 
                            showCancelButton: true,
                            cancelButtonColor: '#6c757d',
                            cancelButtonText: 'Ok',
                            confirmButtonColor: '#007bff',
                            confirmButtonText: 'Go To Settings'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'settings.php';
                            }
                        });
                    </script>
                ";
            }
        }
    }

    // ==================== END - ADDING MATERIALS ====================

    // ==================== START - UPDATING MATERIALS ====================

    if(ISSET($_POST['mEdit'])){
        $mID = $_POST['inputHiddenEditMaterialID'];
        $mCode = $_POST['inputHiddenEditMaterialCode'];
        $mName = $_POST['inputHiddenEditMaterialName'];
        $mPrice = $_POST['inputEditMaterialPrice'];
        $mRemaining = $_POST['inputEditMaterialMeasurement'];
        $mOriginalPurchaseGroup = $_POST['inputHiddenEditPurchaseGroup'];
        $mDescription = mysqli_real_escape_string($database, $_POST['inputEditMaterialDescription']);
        $mEdited = date("Y-m-d H:i:s");

        $PurchaseGroupTable = $database->query("SELECT * FROM purchase_group_tbl WHERE ID = '$mOriginalPurchaseGroup' AND pg_status = 'Locked'");
        if ($PurchaseGroupTable->num_rows > 0) {
            $materialsTableChecker = $database->query("SELECT ID, m_code, m_name, m_price, m_purchase_group, m_description FROM materials_tbl WHERE ID = '$mID'");
            while ($materialsTableCheckerRow = $materialsTableChecker->fetch_assoc()) {
                if ($materialsTableCheckerRow['m_price'] == $mPrice && $materialsTableCheckerRow['m_description'] == $mDescription && $materialsTableCheckerRow['m_remaining'] == $mRemaining) {
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
                else {
                    $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mEdited','Details of $mName ($mCode)','Materials','Edited')") or die ($database->error);
                    $database->query("UPDATE `materials_tbl` SET `m_price`='$mPrice', `m_remaining`='$mRemaining', `m_description`='$mDescription' WHERE `ID`='$mID'") or die ($database->error);
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: '$mName ($mCode) Updated!',
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 2000,
                            });
                        </script>
                    ";
                    header("Refresh:2; url=materials.php");
                }
            }
        }
        else {
            $mPurchaseGroup = $_POST['selectEditPurchaseGroup'];
            $materialsTableChecker1 = $database->query("SELECT ID, m_code, m_name, m_price, m_purchase_group, m_description FROM materials_tbl WHERE ID = '$mID'");
            while ($materialsTableCheckerRow1 = $materialsTableChecker1->fetch_assoc()) {
                if ($materialsTableCheckerRow1['m_purchase_group'] == $mPurchaseGroup && $materialsTableCheckerRow1['m_price'] == $mPrice && $materialsTableCheckerRow1['m_description'] == $mDescription) {
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
                else {
                    $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mEdited','Details of $mName ($mCode)','Materials','Edited')") or die ($database->error);
                    $database->query("UPDATE `materials_tbl` SET `m_purchase_group`='$mPurchaseGroup',`m_price`='$mPrice',`m_remaining`='$mRemaining',`m_description`='$mDescription' WHERE `ID`='$mID'") or die ($database->error);
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: '$mName ($mCode) Updated!',
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 2000,
                            });
                        </script>
                    ";
                    header("Refresh:2; url=materials.php");
                }
            }
        }
	}

    // ==================== END - UPDATING MATERIALS ====================

    // ==================== START - MARK MATERIAL AS DEFECTIVE ====================

    if(isset($_POST['mDefective'])){
        $mID = $_POST['inputHiddenDefectiveMaterialID'];
        $mCode = $_POST['inputHiddenDefectiveMaterialCode'];
        $mName = $_POST['inputHiddenDefectiveMaterialName'];
        $mDescription = mysqli_real_escape_string($database, $_POST['selectDefectiveMaterialDescription']);
        $mRejected = date("Y-m-d H:i:s");

        $sqlMaterialsTable = "UPDATE `materials_tbl` SET `m_quality`='Bad', `m_rejected`='$mRejected', `m_description` = '$mDescription' WHERE `ID`='$mID'";
        $database->query($sqlMaterialsTable) or die ($database->error);

        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mRejected','$mName ($mCode)','Materials','Defective')") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: '$mName ($mCode) has been marked as Defective',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000,
                });
            </script>
        ";
        header("Refresh:2; url=materials.php");
    }

    // ==================== END - MARK MATERIAL AS DEFECTIVE ====================

    // ==================== START - DELETION OF MATERIALS ====================

    if(isset($_POST['mDelete'])){
        $mID = $_POST['inputHiddenDeleteMaterialID'];
        $mCode = $_POST['inputHiddenDeleteMaterialCode'];
        $mName = $_POST['inputHiddenDeleteMaterialName'];
        $mDescription = mysqli_real_escape_string($database, $_POST['inputDeleteMaterialDescription']);
        $mRejected = date("Y-m-d H:i:s");
        $materialsDeletedCounter = $materialsDeleted + 1;

        $sqlMaterialsTable = "UPDATE `materials_tbl` SET `m_quality`='Trash', `m_rejected`='$mRejected', `m_description`='$mDescription'  WHERE `ID`='$mID'";
        $database->query($sqlMaterialsTable) or die ($database->error);

        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mRejected','$mName ($mCode)','Materials','Deleted')") or die ($database->error);
        $sqlAdminsUpdateTable = $database->query("UPDATE `admins_tbl` SET `total_materials_deleted`='$materialsDeletedCounter'") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: '$mName ($mCode) has been deleted',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000,
                });
            </script>
        ";
        header("Refresh:2; url=materials.php");
    }

    // ==================== END - DELETION OF MATERIALS ====================

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
    <title>Materials</title>

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

            <!-- ==================== START - MATERIALS TITLE ROW ==================== -->

            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-package fs-3'></i>&emsp;Materials
                    </p>
                    <button class="btn btn-sm btn-primary d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalAddMaterial">
                        <i class='bx bx-plus fs-5'></i>&nbsp;Add Material
                    </button>
                </div>
            </div>

            <!-- ==================== END - MATERIALS TITLE ROW ==================== -->

            <!-- ==================== START - MATERIALS STATISTICS ROW ==================== -->

            <div class="row row-cols-3 row-cols-xl-6 g-2">
                <div class="col">
                    <div class="card mb-3 border-top-info">
                        <div class="row g-0">
                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                <i class='bx bxs-hourglass bx-spin-hover fs-1 text-info'></i>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-2 text-center">
                                    <h5 class="card-title fs-4">
                                        <?php
                                            $sqlMaterialsTablePending = $database->query("SELECT COUNT(*) AS m_count_pending FROM materials_tbl WHERE m_quality = 'Good' AND m_status = 'Pending'")->fetch_array();
                                            $sqlMaterialsTablePendingCounter = $sqlMaterialsTablePending['m_count_pending'];
                                            echo $sqlMaterialsTablePendingCounter;
                                        ?>
                                    </h5>
                                    <p class="card-text">Pending</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-3 border-top-success">
                        <div class="row g-0">
                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                <i class='bx bx-data bx-tada-hover fs-1 text-success'></i>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-2 text-center">
                                    <h5 class="card-title fs-4">
                                        <?php
                                            $sqlMaterialsTableStorage = $database->query("SELECT COUNT(*) AS m_count_storage FROM materials_tbl WHERE m_quality = 'Good' AND m_status = 'Storage'")->fetch_array();
                                            $sqlMaterialsTableStorageCounter = $sqlMaterialsTableStorage['m_count_storage'];
                                            echo $sqlMaterialsTableStorageCounter;
                                        ?>
                                    </h5>
                                    <p class="card-text">Storage</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-3 border-top-orange">
                        <div class="row g-0">
                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                <i class='bx bx-cog bx-spin-hover fs-1 text-orange'></i>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-2 text-center">
                                    <h5 class="card-title fs-4">
                                        <?php
                                            $sqlMaterialsTableProcessing = $database->query("SELECT COUNT(*) AS m_count_processing FROM materials_tbl WHERE m_quality = 'Good' AND m_status = 'Processing'")->fetch_array();
                                            $sqlMaterialsTableProcessingCounter = $sqlMaterialsTableProcessing['m_count_processing'];
                                            echo $sqlMaterialsTableProcessingCounter;
                                        ?>
                                    </h5>
                                    <p class="card-text">Processing</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <?php
                        if ($_SESSION['position'] != 'User') {
                            ?>
                                <a href="defectives.php" class="text-decoration-none text-dark">
                                    <div class="card mb-3 border-top-warning">
                                        <div class="row g-0">
                                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                                <i class='bx bx-error bx-tada-hover fs-1 text-warning'></i>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body p-2 text-center">
                                                    <h5 class="card-title fs-4">
                                                        <?php
                                                            $sqlMaterialsTableDefective = $database->query("SELECT COUNT(*) AS m_count_defective FROM materials_tbl WHERE m_quality = 'Bad'")->fetch_array();
                                                            $sqlMaterialsTableDefectiveCounter = $sqlMaterialsTableDefective['m_count_defective'];
                                                            echo $sqlMaterialsTableDefectiveCounter;
                                                        ?>
                                                    </h5>
                                                    <p class="card-text">Defective</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php
                        }
                        else {
                            ?>
                                <div class="card mb-3 border-top-warning">
                                    <div class="row g-0">
                                        <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                            <i class='bx bx-error bx-tada-hover fs-1 text-warning'></i>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body p-2 text-center">
                                                <h5 class="card-title fs-4">
                                                    <?php
                                                        $sqlMaterialsTableDefective = $database->query("SELECT COUNT(*) AS m_count_defective FROM materials_tbl WHERE m_quality = 'Bad'")->fetch_array();
                                                        $sqlMaterialsTableDefectiveCounter = $sqlMaterialsTableDefective['m_count_defective'];
                                                        echo $sqlMaterialsTableDefectiveCounter;
                                                    ?>
                                                </h5>
                                                <p class="card-text">Defective</p>
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
                        if ($_SESSION['position'] != 'User') {
                            ?>
                                <a href="archives.php" class="text-decoration-none text-dark">
                                    <div class="card mb-3 border-top-danger">
                                        <div class="row g-0">
                                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                                <i class='bx bx-trash bx-tada-hover fs-1 text-danger'></i>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body p-2 text-center">
                                                    <h5 class="card-title fs-4">
                                                        <?php
                                                            echo $materialsDeleted;
                                                        ?>
                                                    </h5>
                                                    <p class="card-text">Deleted</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php
                        }
                        else {
                            ?>
                                <div class="card mb-3 border-top-danger">
                                    <div class="row g-0">
                                        <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                            <i class='bx bx-trash bx-tada-hover fs-1 text-danger'></i>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body p-2 text-center">
                                                <h5 class="card-title fs-4">
                                                    <?php
                                                        echo $materialsDeleted;
                                                    ?>
                                                </h5>
                                                <p class="card-text">Deleted</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                        }
                    ?>
                </div>
                <div class="col">
                    <div class="card mb-3 border-top-secondary">
                        <div class="row g-0">
                            <div class="col-md-4 py-2 d-flex align-items-center justify-content-center rounded">
                                <i class='bx bx-check-circle bx-tada-hover fs-1 text-secondary'></i>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-2 text-center">
                                    <h5 class="card-title fs-4">
                                        <?php
                                            $sqlMaterialsTableUsed = $database->query("SELECT COUNT(*) AS m_count_used FROM materials_tbl WHERE m_status = 'Used'")->fetch_array();
                                            $sqlMaterialsTableUsedCounter = $sqlMaterialsTableUsed['m_count_used'];
                                            echo $sqlMaterialsTableUsedCounter;
                                        ?>
                                    </h5>
                                    <p class="card-text">Used</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - MATERIALS STATISTICS ROW ==================== -->
        
            <div class="row mb-3">
                <div class="col d-flex justify-content-end">
                    <button class="btn btn-sm btn-danger d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">
                        <i class='bx bx-printer fs-5'></i>&nbsp;Print
                    </button>
                </div>
            </div>

            <!-- ==================== START - MATERIALS ROW ==================== -->

            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <table class="datatable-desc-2 table table-hover responsive nowrap w-100">
                                <thead class="bg-success text-light">
                                    <th>Group</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Price (in Peso)</th>
                                    <th>Measurement</th>
                                    <th>Status</th>
                                    <th>Received</th>
                                    <th class="no-sort">Description</th>
                                    <th class="no-sort text-center">Action</th>
                                </thead>
                                <tbody>
                                    <?php while($fetch = $materialsCatalog->fetch_array()){ ?>
                                        <tr>
                                            <td class="align-middle"><?php echo $fetch['pg_code']?></td>
                                            <td class="align-middle"><?php echo "<img src='images/codes/{$fetch['m_code_photo']}' class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'> {$fetch['m_code']}";?></td>
                                            <td class="align-middle"><?php echo "<img src='images/items/{$fetch['m_photo']}' class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'> {$fetch['m_name']}";?></td>
                                            <td class="align-middle"><?php echo $fetch['m_type']?></td>
                                            <td class="align-middle"><?php echo number_format((float)$fetch['m_price'], 2, '.', ',');?></td>
                                            <td class="align-middle">
                                                <?php 
                                                    echo $fetch['m_measurement']." ";
                                                    if ($fetch['m_measurement'] > 1) {
                                                        echo $fetch['m_unit']."s";
                                                    }
                                                    else {
                                                        echo $fetch['m_unit'];
                                                    }
                                                ?>
                                                <br>
                                                <?php 
                                                    if ($fetch['m_measurement'] != $fetch['m_remaining']) {
                                                        echo $fetch['m_remaining']." ";
                                                        if ($fetch['m_remaining'] > 1) {
                                                            echo $fetch['m_unit']."s";
                                                        }
                                                        else {
                                                            echo $fetch['m_unit'];
                                                        }
                                                        echo " (Remaining)";
                                                    }
                                                ?>
                                            </td>
                                            <?php 
                                                if ($fetch['m_status'] == "Pending") {
                                                    echo "<td class='align-middle rounded text-center bg-info bg-gradient'>{$fetch['m_status']}</td>";
                                                }
                                                else if ($fetch['m_status'] == "Storage") {
                                                    echo "<td class='align-middle rounded text-center bg-success bg-gradient text-light'>{$fetch['m_status']}</td>";
                                                }
                                                else {
                                                    echo "<td class='align-middle rounded text-center bg-orange bg-gradient text-light'>{$fetch['m_status']}</td>";
                                                }
                                            ?>
                                            <td class="align-middle"><?php echo date('F j, Y, h:i a', strtotime($fetch['m_received']))?></td>     
                                            <td class="align-middle"><?php echo $fetch['m_description']?></td>      
                                            <td class="align-middle text-center">

                                                <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->
                                                
                                                <?php
                                                    if ($fetch['m_status'] == 'Pending') {
                                                        ?>
                                                            <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialEdit<?php echo $fetch['ID']?>">
                                                                <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Update Material"></i>
                                                            </button>
                                                            <?php
                                                                if ($_SESSION['position'] != 'User') {
                                                                    ?>
                                                                        <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialDeleteConfirmation<?php echo $fetch['ID']?>">
                                                                            <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Material"></i>
                                                                        </button>
                                                                    <?php
                                                                }   
                                                            ?>
                                                        <?php
                                                    }
                                                    else if ($fetch['m_status'] == 'Storage' && $fetch['m_measurement'] == $fetch['m_remaining'] ) {
                                                        ?>
                                                            <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialEdit<?php echo $fetch['ID']?>">
                                                                <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Update Material"></i> 
                                                            </button>
                                                            <?php
                                                                if ($_SESSION['position'] != 'User') {
                                                                    ?>
                                                                        <button class="btn btn-sm btn-warning p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialDefectiveConfirmation<?php echo $fetch['ID']?>">
                                                                            <i class='bx bx-error fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Mark as Defective"></i>
                                                                        </button>
                                                                        <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialDeleteConfirmation<?php echo $fetch['ID']?>">
                                                                            <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Material"></i>
                                                                        </button>
                                                                    <?php
                                                                }   
                                                            ?>
                                                        <?php
                                                    }
                                                    else if ($fetch['m_status'] == 'Storage' && $fetch['m_measurement'] != $fetch['m_remaining']) {
                                                        ?>
                                                            <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialEdit<?php echo $fetch['ID']?>">
                                                                <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Update Material"></i> 
                                                            </button>
                                                            <?php
                                                                if ($_SESSION['position'] != 'User') {
                                                                    ?>
                                                                        <button class="btn btn-sm btn-warning p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialDefectiveConfirmation<?php echo $fetch['ID']?>">
                                                                            <i class='bx bx-error fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Mark as Defective"></i>
                                                                        </button>
                                                                    <?php
                                                                }   
                                                            ?>
                                                        <?php
                                                    }
                                                ?>

                                                <!-- ==================== END - ACTION BUTTONS COLUMN ==================== -->

                                            </td>
                                            <!-- ==================== START - MODAL EDIT DATA ==================== -->
                                                
                                            <div class="modal fade" id="modalMaterialEdit<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form action="" method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="mHeaderEdit"><?php echo "(".$fetch['m_code'].") ".$fetch['m_name'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="inputHiddenEditMaterialID" value="<?php echo $fetch['ID']?>"/>
                                                                <input type="hidden" name="inputHiddenEditMaterialCode" value="<?php echo $fetch['m_code']?>"/>
                                                                <input type="hidden" name="inputHiddenEditMaterialName" value="<?php echo $fetch['m_name']?>"/>
                                                                <input type="hidden" name="inputHiddenEditPurchaseGroup" value="<?php echo $fetch['m_purchase_group']?>"/>
                                                                <div class="mb-2">
                                                                    <label for="selectEditPurchaseGroup" class="form-label">Purchase Group</label>
                                                                    <?php
                                                                        $mPurchaseGroup = $fetch['m_purchase_group'];
                                                                        $PurchaseGroupTable = $database->query("SELECT * FROM purchase_group_tbl WHERE ID = '$mPurchaseGroup' AND pg_status = 'Locked'");
                                                                        if ($PurchaseGroupTable->num_rows > 0) {
                                                                            while ($rowPurchaseGroupTable = $PurchaseGroupTable->fetch_assoc()) {
                                                                                ?>
                                                                                    <input type="text" class="form-control" name="inputEditPurchaseGroup" value="(<?php echo $rowPurchaseGroupTable['pg_code'].") ".$rowPurchaseGroupTable['pg_supplier']." - ".date('F j, Y, h:i a', strtotime($rowPurchaseGroupTable['pg_date']))?>" readonly/>
                                                                                <?php 
                                                                            }
                                                                        }
                                                                        else {
                                                                            ?>
                                                                                <select class="form-select" id="selectEditPurchaseGroup" name="selectEditPurchaseGroup" required>
                                                                                    <option value=''>Choose purchase group</option>
                                                                                    <?php
                                                                                        $query = "SELECT * FROM purchase_group_tbl WHERE pg_status != 'Locked' AND pg_status != 'Completed' ORDER BY pg_code DESC";
                                                                                        $result = $database->query($query);
                                                                                        if($result->num_rows > 0){
                                                                                            while ($row = $result->fetch_assoc()){
                                                                                                ?>
                                                                                                    <option value='<?php echo $row["ID"]?>' <?php if ($row["ID"] == $fetch['m_purchase_group']){echo "selected ";}?>>(<?php echo $row['pg_code'].") ".$row['pg_supplier']." - ".date('F j, Y, h:i a', strtotime($row['pg_date']))?></option>
                                                                                                <?php
                                                                                            }
                                                                                        }
                                                                                        else {
                                                                                            echo "<option value=''>No available purchase group</option>"; 
                                                                                        }
                                                                                    ?>
                                                                                </select>
                                                                            <?php
                                                                        }
                                                                    ?>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label for="inputEditMaterialPrice" class="form-label">Price</label>
                                                                    <div class="input-group">
                                                                        <span class="input-group-text">₱</span>
                                                                        <input type="number" class="form-control" id="inputEditMaterialPrice" name="inputEditMaterialPrice" min="1" max="999999" step="0.01" value="<?php echo $fetch['m_price']?>">
                                                                    </div>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label for="inputEditMaterialMeasurement" class="form-label">Measurement</label>
                                                                    <div class="input-group">
                                                                        <input type="number" class="form-control w-50" id="inputEditMaterialMeasurement" name='inputEditMaterialMeasurement' value="<?php echo $fetch['m_remaining']?>" min="0" max="<?php echo $fetch['m_remaining']?>" step="0.01">
                                                                        <input type="text" class="form-control w-50" id="inputEditEditMaterialUnit" name='inputEditEditMaterialUnit' value="<?php echo $fetch['m_unit']; if ($fetch['m_measurement'] > 1) { echo "s";}?>" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label for="inputEditMaterialDescription" class="form-label">Description</label>
                                                                    <input type="text" class="form-control" id="inputEditMaterialDescription" name="inputEditMaterialDescription" value="<?php echo $fetch['m_description']?>">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success" name="mEdit" id="mEdit">Update</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ==================== END - MODAL EDIT DATA ==================== -->

                                            <!-- ==================== START - MODAL DEFECTIVE DATA ==================== -->
                                                
                                            <div class="modal fade" id="modalMaterialDefectiveConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form action="" method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Mark <?php echo $fetch['m_name']?> (<?php echo $fetch['m_code']?>) as Defective</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to mark this material as Defective?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                <button type="button" class="btn btn-success" data-bs-target="#modalMaterialDefectiveStatement<?php echo $fetch['ID']?>" data-bs-toggle="modal" data-bs-dismiss="modal">Yes, Mark It!</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="modalMaterialDefectiveStatement<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form action="" method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Why is it Defective?</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="inputHiddenDefectiveMaterialID" value="<?php echo $fetch['ID']?>"/>
                                                                <input type="hidden" name="inputHiddenDefectiveMaterialCode" value="<?php echo $fetch['m_code']?>"/>
                                                                <input type="hidden" name="inputHiddenDefectiveMaterialName" value="<?php echo $fetch['m_name']?>"/>
                                                                <div class="mb-2">
                                                                    <label for="selectDefectiveMaterialDescription" class="form-label">Choose your reason why it is Defective.</label>
                                                                    <select class="form-select" id="selectDefectiveMaterialDescription" name="selectDefectiveMaterialDescription" required>
                                                                        <option value="">-- Select a Reason --</option>
                                                                        <option value="Poor Quality">Poor Quality</option>
                                                                        <option value="Wrong Variation">Wrong Variation</option>
                                                                        <option value="Incorrect Drawing">Incorrect Drawing</option>
                                                                        <option value="Damaged On Storage">Damaged On Storage</option>
                                                                        <option value="Incorrect Measurement">Incorrect Measurement</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger" data-bs-target="#modalMaterialDefectiveConfirmation<?php echo $fetch['ID']?>" data-bs-toggle="modal" data-bs-dismiss="modal">Wait, Go Back!</button>
                                                                <button type="submit" class="btn btn-success" name="mDefective" id="mDefective">Continue</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ==================== END - MODAL DEFECTIVE DATA ==================== -->

                                            <!-- ==================== START - MODAL DELETE DATA ==================== -->
                                                
                                            <div class="modal fade" id="modalMaterialDeleteConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form action="" method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="mHeaderDelete">Delete Material</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to delete <?php echo $fetch['m_name']." with the code ".$fetch['m_code'];?>?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                <button type="button" class="btn btn-success" data-bs-target="#modalMaterialDeleteStatement<?php echo $fetch['ID']?>" data-bs-toggle="modal" data-bs-dismiss="modal">Yes, Delete It!</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="modalMaterialDeleteStatement<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form action="" method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="mHeaderDelete">Delete Material</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="inputHiddenDeleteMaterialID" value="<?php echo $fetch['ID']?>"/>
                                                                <input type="hidden" name="inputHiddenDeleteMaterialCode" value="<?php echo $fetch['m_code']?>"/>
                                                                <input type="hidden" name="inputHiddenDeleteMaterialName" value="<?php echo $fetch['m_name']?>"/>
                                                                <div class="mb-2">
                                                                    <label for="inputDeleteMaterialDescription" class="form-label">Enter your reason why you want to Delete this material.</label>
                                                                    <input type="text" class="form-control" id="inputDeleteMaterialDescription" name="inputDeleteMaterialDescription" placeholder="eg. Accident, Human Error..." required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalMaterialDeleteConfirmation<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                <button type="submit" class="btn btn-success" name="mDelete" id="mDelete">Continue</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ==================== END - MODAL DELETE DATA ==================== -->

                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - MATERIALS ROW ==================== -->
            
            <!-- ==================== START - ADD MATERIALS MODAL ==================== -->

            <form class="needs-validation" action="" method="POST" novalidate>
                <div class="modal fade" id="modalAddMaterial" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Material</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label for="selectPurchaseGroup" class="form-label">Purchase Group</label>
                                    <select class="form-select" id="selectPurchaseGroup" name="selectPurchaseGroup" required>
                                        <option value="">Choose purchase group</option>
                                        <?php
                                            $query = "SELECT * FROM purchase_group_tbl WHERE pg_status = 'Unlocked' ORDER BY pg_code DESC";
                                            $result = $database->query($query);
                                            if($result->num_rows > 0){
                                                while ($row = $result->fetch_assoc()){
                                                    echo "<option value='{$row["ID"]}'>{$row['pg_code']} ({$row['pg_supplier']} - ";
                                                    echo date('F j, Y, h:i a', strtotime($row['pg_date']));
                                                    echo ")</option>";
                                                }
                                            }
                                            else {
                                                echo "<option value='' disabled>No available purchase group</option>"; 
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Choose A Purchase Group
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="selectMaterialType" class="form-label">Type</label>
                                    <select class="form-select" id="selectMaterialType" name="selectMaterialType" required>
                                        <option value="">Choose material type</option>
                                        <?php
                                            $query = "SELECT * FROM material_type_tbl";
                                            $result = $database->query($query);
                                            if($result->num_rows > 0){
                                                while ($row = $result->fetch_assoc()){
                                                    echo "<option value='{$row["ID"]}'>{$row['m_type']}</option>";
                                                }
                                            }
                                            else {
                                                echo "<option value='' disabled>No Available Material Type</option>"; 
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Choose A Material Type
                                    </div>
                                </div>
                                <div class="mb-2" id="selectMaterialNameContainer">
                                    <label for="selectMaterialName" class="form-label">Name</label>
                                    <select class="form-select" id="selectMaterialName" name="selectMaterialName" required>
                                        <option value="">Choose material type first</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Choose A Material Item
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="inputMaterialPrice" class="form-label">Total Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="inputMaterialPrice" name="inputMaterialPrice" min="1" max="999999" step="0.01" required>
                                        <div class="invalid-feedback">
                                            Specify Material Price
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="inputMaterialMeasurement" class="form-label">Measurement</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control w-50" id="inputMaterialMeasurement" name="inputMaterialMeasurement" min="0.01" step="0.01" value="1" required>
                                        <select class="form-select" name="selectMaterialUnit">
                                            <option value="Piece">Piece</option>
                                            <option value="Kilogram">Kilogram</option>
                                            <option value="Meter">Meter</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Enter A Valid Material Measurement
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="inputMaterialDescription" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="inputMaterialDescription" name="inputMaterialDescription">
                                </div>
                                <div class="mb-2">
                                    <label for="inputMaterialQuantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="inputMaterialQuantity" name="inputMaterialQuantity" min="1" value="1" required>
                                    <div class="invalid-feedback">
                                        Enter A Valid Quantity
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" id="addMaterial" name="addMaterial" class="btn btn-primary w-100 add-button">Add Material</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- ==================== END - ADD MATERIALS MODAL ==================== -->

            <!-- ==================== START - PRINT MODAL ==================== -->

            <div class="modal fade" id="modalPrintOptions" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <?php
                                if ($_SESSION['position'] != 'User') {
                                    ?>
                                        <h5 class="modal-title">Choose what to Print...</h5>
                                    <?php
                                }
                                else {
                                    ?>
                                        <h5 class="modal-title">Print Codes</h5>
                                    <?php
                                }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php
                                $materialsTablePending = $database->query("SELECT COUNT(*) AS pending_materials FROM materials_tbl WHERE m_status = 'Pending' AND m_quality = 'Good'")->fetch_assoc()['pending_materials'];
                                $materialsTableStorage = $database->query("SELECT COUNT(*) AS storage_materials FROM materials_tbl WHERE m_status = 'Storage' AND m_quality = 'Good'")->fetch_assoc()['storage_materials'];
                                if ($_SESSION['position'] != 'User') {
                                    ?>
                                        <label class="fs-6 d-flex justify-content-center mb-2">Print Material Codes</label>
                                        <div class="row row-cols-1 row-cols-lg-2 g-2">
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPendingMaterialCodes" <?php if ($materialsTablePending == 0) { echo "disabled"; } ?>>Pending</button>
                                            </div>
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintStorageMaterialCodes" <?php if ($materialsTableStorage == 0) { echo "disabled"; } ?>>Storage</button>
                                            </div>
                                        </div>
                                        <br>
                                        <label class="fs-6 d-flex justify-content-center mb-2">Print Material Records</label>
                                        <div class="row row-cols-1 row-cols-lg-2 g-2">
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintMaterialCount">Material Count</button>
                                            </div>
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintMPCount">Material & Product Count</button>
                                            </div>
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintMaterialRecords">Material Records</button>
                                            </div>
                                        </div>
                                    <?php
                                }
                                else {
                                    ?>
                                        <div class="row">
                                            <div class="col-12">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPendingMaterialCodes" <?php if ($materialsTablePending == 0) { echo "disabled"; } ?>>Pending</button>
                                            </div>
                                        </div>
                                    <?php
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="modalPrintPendingMaterialCodes" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="code-canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing Pending Material Codes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print all of the QR codes of the materials which have the pending status in the storage.
                                <br><br>
                                <div class="mb-2">
                                    <label for="selectPrintPendingPurchaseGroup" class="form-label">Choose Purchase Group</label>
                                    <select class="form-select" id="selectPrintPendingPurchaseGroup" name="selectPrintPendingPurchaseGroup" required>
                                        <option value="">Choose purchase group</option>
                                        <?php
                                            $query = "SELECT * FROM purchase_group_tbl WHERE pg_status = 'Locked' OR pg_status = 'Unlocked'";
                                            $result = $database->query($query);
                                            if($result->num_rows > 0){
                                                while ($row = $result->fetch_assoc()){
                                                    $purchaseGroupID = $row['ID'];
                                                    $materialsPendingCheck = $database->query("SELECT m_status FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_status = 'Pending' AND m_quality = 'Good'");
                                                    if ($materialsPendingCheck->num_rows > 0) {
                                                        echo "<option value='{$row["ID"]}'>{$row['pg_code']} ({$row['pg_supplier']} - ";
                                                        echo date('F j, Y, h:i a', strtotime($row['pg_date']));
                                                        echo ")</option>";
                                                    }
                                                }
                                            }
                                            else {
                                                echo "<option value='' disabled>No available purchase group</option>"; 
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Choose A Purchase Group
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printPendingMaterialCodes" name="printPendingMaterialCodes">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="modal fade" id="modalPrintStorageMaterialCodes" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="code-canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing Materials in Storage Codes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print all of the QR codes of the materials which have the storage status.
                                <br><br>
                                <div class="mb-2">
                                    <label for="selectPrintStoragePurchaseGroup" class="form-label">Choose Purchase Group</label>
                                    <select class="form-select" id="selectPrintStoragePurchaseGroup" name="selectPrintStoragePurchaseGroup" required>
                                        <option value="">Choose purchase group</option>
                                        <?php
                                            $query = "SELECT * FROM purchase_group_tbl WHERE pg_status = 'Locked' OR pg_status = 'Unlocked'";
                                            $result = $database->query($query);
                                            if($result->num_rows > 0){
                                                while ($row = $result->fetch_assoc()){
                                                    $purchaseGroupID = $row['ID'];
                                                    $materialsPendingCheck = $database->query("SELECT m_status FROM materials_tbl WHERE m_purchase_group = '$purchaseGroupID' AND m_status = 'Storage' AND m_quality = 'Good'");
                                                    if ($materialsPendingCheck->num_rows > 0) {
                                                        echo "<option value='{$row["ID"]}'>{$row['pg_code']} ({$row['pg_supplier']} - ";
                                                        echo date('F j, Y, h:i a', strtotime($row['pg_date']));
                                                        echo ")</option>";
                                                    }
                                                }
                                            }
                                            else {
                                                echo "<option value='' disabled>No available purchase group</option>"; 
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Choose A Purchase Group
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printStorageMaterialCodes" name="printStorageMaterialCodes">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalPrintMaterialCount" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing Material Count</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print the records of how many materials left in the storage room.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printMaterialCount" name="printMaterialCount" data-bs-dismiss="modal">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalPrintMPCount" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing Material and Product Count</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print both the records of materials and products left in the storage room.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printMPCount" name="printMPCount" data-bs-dismiss="modal">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalPrintMaterialRecords" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing All Material Records</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print all the records of the materials in the materials storage.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printMaterialRecords" name="printMaterialRecords" data-bs-dismiss="modal">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>


            <!-- ==================== END - PRINT MODAL ==================== -->

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