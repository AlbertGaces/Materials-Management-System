<?php
    // ==================== START - SESSION INITIALIZATION ====================
    
    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "defectives";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $sqlMaterialsDefectivesTable = "SELECT 
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
                                    INNER JOIN material_type_tbl ON materials_tbl.m_type = material_type_tbl.ID
                                    INNER JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID 
                                    INNER JOIN purchase_group_tbl ON materials_tbl.m_purchase_group = purchase_group_tbl.ID
                                    WHERE `m_quality` = 'Bad'";
    $materialsDefectives = $database->query($sqlMaterialsDefectivesTable) or die ($database->error);

    $sqlProductsDefectivesTable = "SELECT 
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
                                   WHERE products_tbl.p_quality = 'Bad'";
    $productsDefectives = $database->query($sqlProductsDefectivesTable) or die ($database->error);

    date_default_timezone_set('Asia/Manila');

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();
    $materialsDeleted = $adminData['total_materials_deleted'];
    $productsDeleted = $adminData['total_products_deleted'];
    $adminName = $adminData['first_name']." ".$adminData['last_name'];

    // ==================== END - QUERIES ====================

    // ==================== START - MARK MATERIAL AS GOOD ====================

    if(isset($_POST['mRecoverDefectiveMaterial'])){
        $mID = $_POST['inputHiddenRecoverDefectiveMaterialID'];
        $mName = $_POST['inputHiddenRecoverDefectiveMaterialName'];
        $mCode = $_POST['inputHiddenRecoverDefectiveMaterialCode'];
        $mDescription = mysqli_real_escape_string($database, $_POST['inputRecoverDefectiveMaterialDescription']);
        $mRecovered = date("Y-m-d H:i:s");

        $sqlMaterialsTable = "UPDATE `materials_tbl` SET `m_quality`='Good', `m_status`='Pending', `m_description`='$mDescription' WHERE `ID`='$mID'";
        $database->query($sqlMaterialsTable) or die ($database->error);

        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mRecovered','$mName ($mCode)','Defectives','Recovered')") or die ($database->error);
        
        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Defective $mName ($mCode) Recovered',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=defectives.php");
    }

    // ==================== END - MARK MATERIAL AS GOOD ====================

    // ==================== START - MARK MATERIAL AS TRASH ====================

    if(isset($_POST['mDeleteDefectiveMaterial'])){
        $mID = $_POST['inputHiddenDeleteDefectiveMaterialID'];
        $mName = $_POST['inputHiddenDeleteDefectiveMaterialName'];
        $mCode = $_POST['inputHiddenDeleteDefectiveMaterialCode'];
        $mDescription = mysqli_real_escape_string($database, $_POST['inputDeleteDefectiveMaterialDescription']);
        $mRejected = date("Y-m-d H:i:s");
        $materialsDeletedCounter = $materialsDeleted + 1;

        $sqlMaterialsTable = "UPDATE `materials_tbl` SET `m_description`='$mDescription', `m_quality`='Trash', `m_rejected`='$mRejected' WHERE `ID`='$mID'";
        $database->query($sqlMaterialsTable) or die ($database->error);

        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$mRejected','$mName ($mCode)','Defectives','Deleted')") or die ($database->error);
        $sqlAdminsUpdateTable = $database->query("UPDATE `admins_tbl` SET `total_materials_deleted`='$materialsDeletedCounter'") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: '$mName ($mCode) has been deleted',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=defectives.php");
    }

    // ==================== END - MARK MATERIAL AS TRASH ====================

    // ==================== START - MARK PRODUCT AS GOOD ====================

    if(isset($_POST['pRecoverDefectiveProduct'])){
        $pID = $_POST['inputHiddenRecoverDefectiveProductID'];
        $pName = $_POST['inputHiddenRecoverDefectiveProductName'];
        $pCode = $_POST['inputHiddenRecoverDefectiveProductCode'];
        $pDescription = mysqli_real_escape_string($database, $_POST['inputRecoverDefectiveProductDescription']);
        $pRecovered = date("Y-m-d H:i:s");

        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pRecovered','$pName ($pCode)','Defectives','Recovered')") or die ($database->error);
        
        $sqlProductsTable = "UPDATE `products_tbl` SET `p_quality`='Good', `p_status`='Pending', `p_description`='$pDescription'  WHERE `ID`='$pID'";
        $database->query($sqlProductsTable) or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Defective $pName ($pCode) Recovered!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=defectives.php");
    }

    // ==================== END - MARK MATERIAL AS TRASH ====================

    if(isset($_POST['pDeleteDefectiveProduct'])){
        $pID = $_POST['inputHiddenDeleteDefectiveProductID'];
        $pName = $_POST['inputHiddenDeleteDefectiveProductName'];
        $pCode = $_POST['inputHiddenDeleteDefectiveProductCode'];
        $pDescription = mysqli_real_escape_string($database, $_POST['inputDeleteDefectiveProductDescription']);
        $pRejected = date("Y-m-d H:i:s");
        $productsDeletedCounter = $productsDeleted + 1;

        $sqlProductsTable = "UPDATE `products_tbl` SET `p_quality`='Trash', `p_rejected`='$pRejected', `p_description`='$pDescription' WHERE `ID`='$pID'";
        $database->query($sqlProductsTable) or die ($database->error);

        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pRejected','$pName ($pCode)','Defectives','Deleted')") or die ($database->error);
        $sqlAdminsUpdateTable = $database->query("UPDATE `admins_tbl` SET `total_products_deleted`='$productsDeletedCounter'") or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Defective $pName ($pCode) Deleted!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=defectives.php");
    }

    // ==================== END - MARK MATERIAL AS TRASH ====================

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
    <title>Defective Items</title>

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

            <!-- ==================== START - DEFECTIVES TITLE ROW ==================== -->

            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-error fs-3'></i>&emsp;Defectives
                    </p>
                </div>
            </div>

            <!-- ==================== END - DEFECTIVES TITLE ROW ==================== -->

            <!-- ==================== START - DEFECTIVES ROW ==================== -->

            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs">
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-warning active" href="#dMaterials" data-bs-toggle="tab">Materials</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-warning" href="#dProducts" data-bs-toggle="tab">Products</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- ==================== START - DEFECTIVE MATERIALS ROW ==================== -->

                                <div class="tab-pane active" id="dMaterials">
                                    <table class="datatable-desc-2 table table-hover responsive nowrap w-100">
                                        <thead class="bg-warning">
                                            <th>Group</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Price (in Peso)</th>
                                            <th>Measurement</th>
                                            <th>Received</th>
                                            <th>Rejected</th>
                                            <th>Reason</th>
                                            <th class="no-sort text-center">Action</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $materialsDefectives->fetch_array()){ ?>
                                                <tr>
                                                    <td><?php echo $fetch['pg_code']?></td>
                                                    <td><?php echo $fetch['m_code']?></td>
                                                    <td><?php echo $fetch['m_name']?></td>
                                                    <td><?php echo $fetch['m_type']?></td>
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
                                                    <td class="text-center">

                                                        <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->

                                                        <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialRecoverConfirmation<?php echo $fetch['ID']?>">
                                                            <i class='bx bx-recycle fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Recover Material"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialDeleteConfirmation<?php echo $fetch['ID']?>">
                                                            <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Material"></i>
                                                        </button>

                                                        <!-- ==================== END - ACTION BUTTONS COLUMN ==================== -->

                                                    </td>

                                                    <!-- ==================== START - MODAL RECOVER DEFECTIVE MATERIAL DATA ==================== -->
                                        
                                                    <div class="modal fade" id="modalMaterialRecoverConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Recover Defective Material</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Are you sure that this material is in a good condition?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, It Isn't!</button>
                                                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMaterialRecoverStatement<?php echo $fetch['ID']?>" data-bs-dismiss="modal">Yes, It Is!</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal fade" id="modalMaterialRecoverStatement<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">New Material Description</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenRecoverDefectiveMaterialID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenRecoverDefectiveMaterialName" value="<?php echo $fetch['m_name']?>"/>
                                                                        <input type="hidden" name="inputHiddenRecoverDefectiveMaterialCode" value="<?php echo $fetch['m_code']?>"/>
                                                                        <div class="mb-2">
                                                                            <label for="inputRecoverDefectiveMaterialDescription" class="form-label">Enter new material description</label>
                                                                            <input type="text" class="form-control" id="inputRecoverDefectiveMaterialDescription" name="inputRecoverDefectiveMaterialDescription" placeholder="eg. Color Red, Shiny..." required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalMaterialRecoverConfirmation<?php echo $fetch['ID']?>" data-bs-dismiss="modal">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" name="mRecoverDefectiveMaterial" id="mRecoverDefectiveMaterial">Continue</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL RECOVER DEFECTIVE MATERIAL DATA ==================== -->

                                                    <!-- ==================== START - MODAL DELETE DEFECTIVE MATERIAL DATA ==================== -->
                                        
                                                    <div class="modal fade" id="modalMaterialDeleteConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Delete Defective Material</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Are you sure that this material will no longer be usable?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                        <button type="button" class="btn btn-success" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalMaterialDelete<?php echo $fetch['ID']?>">Yes, Delete It!</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal fade" id="modalMaterialDelete<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Delete Defective Material</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenDeleteDefectiveMaterialID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenDeleteDefectiveMaterialName" value="<?php echo $fetch['m_name']?>"/>
                                                                        <input type="hidden" name="inputHiddenDeleteDefectiveMaterialCode" value="<?php echo $fetch['m_code']?>"/>
                                                                        <div class="mb-2">
                                                                            <label for="inputDeleteDefectiveMaterialDescription" class="form-label">Enter your reason why you want to Delete this material.</label>
                                                                            <input type="text" class="form-control" id="inputDeleteDefectiveMaterialDescription" name="inputDeleteDefectiveMaterialDescription" placeholder="eg. Accident, Human Error..." required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalMaterialDeleteConfirmation<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" name="mDeleteDefectiveMaterial" id="mDeleteDefectiveMaterial">Continue</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL DELETE DEFECTIVE MATERIAL DATA ==================== -->

                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - DEFECTIVE MATERIALS ROW ==================== -->

                                <!-- ==================== START - DEFECTIVE PRODUCTS ROW ==================== -->

                                <div class="tab-pane" id="dProducts">
                                    <table class="datatable-desc-1 table table-hover responsive nowrap w-100">
                                        <thead class="bg-warning">
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Price (in Peso)</th>
                                            <th>Measurement</th>
                                            <th>Received</th>
                                            <th>Rejected</th>
                                            <th>Reason</th>
                                            <th class="no-sort text-center">Action</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $productsDefectives->fetch_array()){ ?>
                                                <tr class="align-middle">
                                                    <td><?php echo $fetch['p_code']?></td>
                                                    <td><?php echo $fetch['p_name']?></td>
                                                    <td><?php echo number_format((float)$fetch['p_price'], 2, '.', '');?></td>
                                                    <td>
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
                                                    <td><?php echo date('F j, Y', strtotime($fetch['p_completed']))?></td>
                                                    <td><?php echo date('F j, Y', strtotime($fetch['p_rejected']))?></td>
                                                    <td><?php echo $fetch['p_description']?></td>
                                                    <td class="text-center">

                                                        <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->

                                                        <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalProductRecoverConfirmation<?php echo $fetch['ID']?>">
                                                            <i class='bx bx-recycle fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Recover Product"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalProductDeleteConfirmation<?php echo $fetch['ID']?>">
                                                            <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Product"></i>
                                                        </button>
                                                        
                                                        <!-- ==================== END - ACTION BUTTONS COLUMN ==================== -->

                                                    </td>

                                                    <!-- ==================== START - MODAL RECOVER DEFECTIVE PRODUCT DATA ==================== -->
                                        
                                                    <div class="modal fade" id="modalProductRecoverConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Recover Defective Product</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Are you sure that this product is in a good condition?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, It Isn't!</button>
                                                                        <button type="button" class="btn btn-success" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalProductRecover<?php echo $fetch['ID']?>">Yes, It Is!</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal fade" id="modalProductRecover<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">New Product Description</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenRecoverDefectiveProductID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenRecoverDefectiveProductName" value="<?php echo $fetch['p_name']?>"/>
                                                                        <input type="hidden" name="inputHiddenRecoverDefectiveProductCode" value="<?php echo $fetch['p_code']?>"/>
                                                                        <div class="mb-2">
                                                                            <label for="inputRecoverDefectiveProductDescription" class="form-label">Enter new product description</label>
                                                                            <input type="text" class="form-control" id="inputRecoverDefectiveProductDescription" name="inputRecoverDefectiveProductDescription" placeholder="eg. Color Red, Shiny..." required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalProductRecoverConfirmation<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" name="pRecoverDefectiveProduct" id="pRecoverDefectiveProduct">Continue</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL RECOVER DEFECTIVE PRODUCT DATA ==================== -->

                                                    <!-- ==================== START - MODAL DELETE DEFECTIVE PRODUCT DATA ==================== -->
                                        
                                                    <div class="modal fade" id="modalProductDeleteConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Delete Defective Product</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenDeleteDefectiveProductID" value="<?php echo $fetch['ID']?>"/>
                                                                        Are you sure that this product will no longer be usable?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                        <button type="button" class="btn btn-success" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalProductDelete<?php echo $fetch['ID']?>">Yes, Delete It!</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal fade" id="modalProductDelete<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Delete Defective Product</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenDeleteDefectiveProductID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenDeleteDefectiveProductName" value="<?php echo $fetch['p_name']?>"/>
                                                                        <input type="hidden" name="inputHiddenDeleteDefectiveProductCode" value="<?php echo $fetch['p_code']?>"/>
                                                                        <div class="mb-2">
                                                                            <label for="inputDeleteDefectiveProductDescription" class="form-label">Enter your reason why you want to Delete this product.</label>
                                                                            <input type="text" class="form-control" id="inputDeleteDefectiveProductDescription" name="inputDeleteDefectiveProductDescription" placeholder="eg. Accident, Human Error..." required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalProductDeleteConfirmation<?php echo $fetch['ID']?>">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" name="pDeleteDefectiveProduct" id="pDeleteDefectiveProduct">Continue</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL DELETE DEFECTIVE PRODUCT DATA ==================== -->
                                                    
                                                <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - DEFECTIVE PRODUCTS ROW ==================== -->

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - DEFECTIVES ROW ==================== -->

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