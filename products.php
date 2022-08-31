<?php 
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "products";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $sqlProductsTable = "SELECT * FROM `products_tbl`";
    $products = $database->query($sqlProductsTable) or die ($database->error);

    $sqlProductsCatalogTable = "SELECT products_tbl.ID, products_tbl.p_code_photo, products_tbl.p_code, product_name_tbl.p_name, product_name_tbl.p_photo, products_tbl.p_price, products_tbl.p_measurement, products_tbl.p_remaining, products_tbl.p_description, products_tbl.p_status, products_tbl.p_completed, products_tbl.p_quality
                                FROM products_tbl 
                                INNER JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID
                                WHERE products_tbl.p_quality = 'Good' AND products_tbl.p_status != 'Sold'";
    $productsCatalog = $database->query($sqlProductsCatalogTable) or die ($database->error);

    $sqlProductsStocksTable = "SELECT products_tbl.ID, product_name_tbl.p_photo, product_name_tbl.p_name, MIN(products_tbl.p_price) AS p_low_price, MAX(products_tbl.p_price) AS p_high_price, COUNT(products_tbl.p_name) AS p_name_count
                                FROM products_tbl
                                INNER JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID
                                WHERE products_tbl.p_quality = 'Good' AND products_tbl.p_status != 'Sold'
                                GROUP BY products_tbl.p_name";
    $productsStocks = $database->query($sqlProductsStocksTable) or die ($database->error);

    $sqlProductsSoldTable = "SELECT products_tbl.ID, products_tbl.p_code_photo, products_tbl.p_code, product_name_tbl.p_name, product_name_tbl.p_photo, products_tbl.p_price, products_tbl.p_measurement, products_tbl.p_description, products_tbl.p_status, products_tbl.p_completed, products_tbl.p_quality
                                FROM products_tbl 
                                INNER JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID
                                WHERE products_tbl.p_quality = 'Good' AND products_tbl.p_status = 'Sold'";
    $productsSold = $database->query($sqlProductsSoldTable) or die ($database->error);

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();
    $maxStorage = $adminData['max_storage'];
    $productsDeleted = $adminData['total_products_deleted'];
    $adminName = $adminData['first_name']." ".$adminData['last_name'];

    include 'qrcodegenerator/qrlib.php';
    
    date_default_timezone_set('Asia/Manila');

    // ==================== END - QUERIES ====================

    // ==================== START - DELETION ====================

    $recordProductDeletion = $database->query("SELECT * FROM products_tbl WHERE p_quality = 'Trash' AND p_rejected < now() - interval 30 DAY");
    if ($recordProductDeletion->num_rows > 0) {
        while ($recordProductDeletionRows = $recordProductDeletion->fetch_assoc()) {
            $ID = $recordProductDeletionRows['ID'];
            $pCodePhoto = $recordProductDeletionRows['p_code_photo'];
            $database->query("DELETE FROM products_tbl WHERE ID = '$ID'");
            unlink("images/items/".$pCodePhoto);
        }
    }

    // ==================== END - DELETION ====================

    // ==================== START - ADDING PRODUCTS ====================

    if(isset($_POST['addProduct'])){
        $pName = $_POST['selectProductName'];
        $pPrice = $_POST['inputProductPrice'];
        $pMeasurement = $_POST['inputProductMeasurement'];
        $pDescription = mysqli_real_escape_string($database, $_POST['inputProductDescription']);
        $pCurrentDate = date("Y-m-d H:i:s");
        $pQuantity = $_POST['inputProductQuantity'];
        $pCounter = 1;

        $noMaterialsFoundCheck = 0;
        $insufficientMaterialsFoundCheck = 0;
        $sufficientMaterialsFoundCheck = 0;

        $fetchProductsCounter = $database->query("SELECT COUNT(*) AS 'products_counter' FROM products_tbl WHERE p_name = $pName AND p_quality = 'Good'")->fetch_assoc();
        $pNameCounter = ($fetchProductsCounter['products_counter']) + $pQuantity;

        if ($pNameCounter<$maxStorage) {
            $productRequirementsTable = $database->query("SELECT * FROM `product_requirement_tbl` WHERE p_name = '$pName'");
            if($productRequirementsTable->num_rows > 0){
                while ($pRowChecker = $productRequirementsTable->fetch_assoc()) {
                    $mRequirementName = $pRowChecker['m_name'];
                    $mRequirementMeasurement = $pRowChecker['m_measurement']*($pMeasurement*$pQuantity);
                    $mRequirementUnit = $pRowChecker['m_unit'];
                    
                    $materialsTable = $database->query("SELECT * FROM `materials_tbl` WHERE m_name = '$mRequirementName' AND m_unit = '$mRequirementUnit' AND m_remaining != 0 AND m_status = 'Processing' AND m_quality = 'Good'");
                    if ($materialsTable->num_rows > 0) {
                        $totalCatalogMaterialMeasurement = 0;
                        while ($materialsTableRow = $materialsTable->fetch_assoc()) {
                            $totalCatalogMaterialMeasurement = $totalCatalogMaterialMeasurement+$materialsTableRow['m_measurement'];
                        }
                        if ($totalCatalogMaterialMeasurement >= $mRequirementMeasurement) {
                            $sufficientMaterialsFoundCheck = $sufficientMaterialsFoundCheck+1;
                        }
                        else {
                            $insufficientMaterialsFoundCheck = $insufficientMaterialsFoundCheck+1;
                        }
                    }
                    else {
                        $noMaterialsFoundCheck = $noMaterialsFoundCheck+1;
                    }
                }
                
                if ($sufficientMaterialsFoundCheck > 0 && $insufficientMaterialsFoundCheck == 0) {
                    while ($pQuantity >= $pCounter) {
                        $productsTableFetch =  $database->query("SELECT ID FROM `products_tbl` ORDER BY ID DESC LIMIT 1")->fetch_assoc();
                        $productsCounterFetch = ($productsTableFetch['ID']) + 1;
                        
                        $pCodeCounter = str_pad($productsCounterFetch,5,"0",STR_PAD_LEFT);
            
                        $qrData = "P-" . $pCodeCounter;
                        $qrDataName = $qrData . '.png';
                        $qrLocation = 'images/codes/' . $qrDataName;
                        $ECC = 'H';
                        $qrPixelSize = 10;
                        $qrFrameSize = 10;
                        $qrcode = QRcode::png($qrData, $qrLocation, $ECC, $qrPixelSize, $qrFrameSize);

                        $productRequirementsTableFetch = $database->query("SELECT * FROM `product_requirement_tbl` WHERE p_name = '$pName'");
                        while ($productRequirementsTableFetchRow = $productRequirementsTableFetch->fetch_assoc()) {
                            $mRequirementNameSub = $productRequirementsTableFetchRow['m_name'];
                            $mRequirementMeasurementSub = $productRequirementsTableFetchRow['m_measurement']*$pMeasurement;
                            $mRequirementUnitSub = $productRequirementsTableFetchRow['m_unit'];
                            
                            while ($mRequirementMeasurementSub > 0) {
                                $materialsTableFetch = $database->query("SELECT * FROM `materials_tbl` WHERE m_name = '$mRequirementNameSub' AND m_unit = '$mRequirementUnitSub' AND m_remaining != 0 AND m_status = 'Processing' AND m_quality = 'Good' LIMIT 1");
                                if ($materialsTableFetch->num_rows > 0) {
                                    while ($materialsTableFetchRow = $materialsTableFetch->fetch_assoc()) {
                                        $mID = $materialsTableFetchRow['ID'];
                                        $mCode = $materialsTableFetchRow['m_code'];
                                        $mName = $materialsTableFetchRow['m_name'];
                                        $mType = $materialsTableFetchRow['m_type'];
                                        $materialsTableFetchWordName = $database->query("SELECT m_name FROM material_name_tbl WHERE ID = '$mName'")->fetch_assoc();
                                        if ($materialsTableFetchRow['m_remaining'] > $mRequirementMeasurementSub) {
                                            $calculatedPrice = ($materialsTableFetchRow['m_price']/$materialsTableFetchRow['m_measurement'])*$mRequirementMeasurementSub;
                                            $mRemainingMeasurement = $materialsTableFetchRow['m_remaining'] - $mRequirementMeasurementSub;
                                            $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$mRequirementMeasurementSub {$materialsTableFetchRow['m_unit']}(s) of {$materialsTableFetchWordName['m_name']} ($mCode) for $qrData','Materials','Used')") or die ($database->error);
                                            $materialsTableUpdate = $database->query("UPDATE materials_tbl SET m_remaining = '$mRemainingMeasurement' WHERE ID = '$mID'");
                                            $materialUsedTableInsert = $database->query("INSERT INTO `material_used_tbl`(`ID`, `m_code`, `m_name`, `m_type`, `m_price`, `m_measurement`, `m_unit`, `m_date_used`, `p_code`) VALUES (NULL,'$mCode','$mName','$mType','$calculatedPrice','$mRequirementMeasurementSub','$mRequirementUnitSub','$pCurrentDate', '$qrData')") or die ($database->error);
                                            $mRequirementMeasurementSub = 0;
                                        }
                                        else if ($materialsTableFetchRow['m_remaining'] == $mRequirementMeasurementSub) {
                                            $calculatedPrice = ($materialsTableFetchRow['m_price']/$materialsTableFetchRow['m_measurement'])*$mRequirementMeasurementSub;
                                            $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$mRequirementMeasurementSub {$materialsTableFetchRow['m_unit']}(s) of {$materialsTableFetchWordName['m_name']} ($mCode) for $qrData','Materials','Used')") or die ($database->error);
                                            $materialsTableUpdate = $database->query("UPDATE materials_tbl SET m_remaining = '0', m_status = 'Used' WHERE ID = '$mID'");
                                            $materialUsedTableInsert = $database->query("INSERT INTO `material_used_tbl`(`ID`, `m_code`, `m_name`, `m_type`, `m_price`, `m_measurement`, `m_unit`, `m_date_used`, `p_code`) VALUES (NULL,'$mCode','$mName','$mType','$calculatedPrice','$mRequirementMeasurementSub','$mRequirementUnitSub','$pCurrentDate', '$qrData')") or die ($database->error);
                                            $mRequirementMeasurementSub = 0;
                                        }
                                        else {
                                            $mRemainingUsedMaterialMeasurement = $materialsTableFetchRow['m_remaining'];
                                            $mRemainingRequirementMeasurement = $mRequirementMeasurementSub - $mRemainingUsedMaterialMeasurement;
                                            $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$mRemainingUsedMaterialMeasurement {$materialsTableFetchRow['m_unit']}(s) of {$materialsTableFetchWordName['m_name']} ($mCode) for $qrData','Materials','Used')") or die ($database->error);
                                            $materialsTableUpdate = $database->query("UPDATE materials_tbl SET m_remaining = '0', m_status = 'Used' WHERE ID = '$mID'");
                                            $calculatedPrice = ($materialsTableFetchRow['m_price']/$materialsTableFetchRow['m_measurement'])*$mRemainingUsedMaterialMeasurement;
                                            $materialUsedTableInsert = $database->query("INSERT INTO `material_used_tbl`(`ID`, `m_code`, `m_name`, `m_type`, `m_price`, `m_measurement`, `m_unit`, `m_date_used`, `p_code`) VALUES (NULL,'$mCode','$mName','$mType','$calculatedPrice','$mRemainingUsedMaterialMeasurement','$mRequirementUnitSub','$pCurrentDate', '$qrData')") or die ($database->error);
                                            $mRequirementMeasurementSub = $mRemainingRequirementMeasurement;
                                        }
                                    }
                                }
                                else {
                                    $_SESSION['message']= "
                                        <script>
                                            Swal.fire({
                                                position: 'center',
                                                icon: 'error',
                                                title: 'No Materials Left',
                                                text: 'The materials required for this product has run out, you should replenish the inventory now', 
                                                showCancelButton: true,
                                                cancelButtonColor: '#6c757d',
                                                cancelButtonText: 'OK',
                                                confirmButtonColor: '#007bff',
                                                confirmButtonText: 'Go To Materials'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    window.location.href = 'materials.php';
                                                }
                                            });
                                        </script>
                                    ";
                                }
                            }
                        }
            
                        $sqlProductsTable = "INSERT INTO `products_tbl`(`ID`, `p_code_photo`, `p_code`, `p_name`, `p_price`, `p_measurement`, `p_remaining`, `p_status`, `p_description`, `p_completed`, `p_quality`, `p_rejected`) VALUES (NULL,'$qrDataName','$qrData','$pName','$pPrice','$pMeasurement','$pMeasurement','Pending','$pDescription','$pCurrentDate','Good','0000-00-00 00:00:00')";
                        $database->query($sqlProductsTable) or die ($database->error);
                        $pCounter++;
                    }

                    $convertProductNameQuery = $database->query("SELECT p_name FROM `product_name_tbl` WHERE ID = $pName")->fetch_assoc();
                    if ($pMeasurement > 1) {
                        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$pQuantity, $pMeasurement Pieces of {$convertProductNameQuery['p_name']}','Products','Added')") or die ($database->error);
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'Added $pQuantity, $pMeasurement Pieces of {$convertProductNameQuery['p_name']}',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 2000,
                                });
                            </script>
                        ";
                    }
                    else {
                        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pCurrentDate','$pQuantity, $pMeasurement Piece of {$convertProductNameQuery['p_name']}','Products','Added')") or die ($database->error);
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'Added $pQuantity, $pMeasurement Piece of {$convertProductNameQuery['p_name']}',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                    timer: 2000,
                                });
                            </script>
                        ";
                    }
                    
                    header("Refresh:2; url=products.php");
                }
                
                else if ($insufficientMaterialsFoundCheck > 0 && $noMaterialsFoundCheck == 0) {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Insufficient Materials',
                                text: 'The materials required for this product seems to be running out, replenish the inventory first', 
                                showCancelButton: true,
                                cancelButtonColor: '#6c757d',
                                cancelButtonText: 'OK',
                                confirmButtonColor: '#007bff',
                                confirmButtonText: 'Go To Materials'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'materials.php';
                                }
                            });
                        </script>
                    ";
                }

                else if ($noMaterialsFoundCheck > 0) {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Missing Materials',
                                text: 'The materials required for this product seems to be missing something, create some in the inventory first', 
                                showCancelButton: true,
                                cancelButtonColor: '#6c757d',
                                cancelButtonText: 'OK',
                                confirmButtonColor: '#007bff',
                                confirmButtonText: 'Go To Materials'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'materials.php';
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
                            title: 'No Materials Involved',
                            text: 'This product does not contain required materials, add the materials required in the Settings Tab', 
                            showCancelButton: true,
                            cancelButtonColor: '#6c757d',
                            cancelButtonText: 'OK',
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

    // ==================== END - ADDING PRODUCTS ====================

    // ==================== START - UPDATING PRODUCTS ====================

    if(ISSET($_POST['pEdit'])){
        $pID = $_POST['inputHiddenEditProductID'];
        $pCode = $_POST['inputHiddenEditProductCode'];
        $pName = $_POST['inputHiddenEditProductName'];
        $pPrice = $_POST['inputEditProductPrice'];
        $pRemaining = $_POST['inputEditProductMeasurement'];
        $pDescription = mysqli_real_escape_string($database, $_POST['inputEditProductDescription']);
        $pEdited = date("Y-m-d H:i:s");

        $productsTableChecker = $database->query("SELECT p_price, p_remaining, p_description FROM products_tbl WHERE ID = '$pID'");
        while ($productsTableCheckerRow = $productsTableChecker->fetch_assoc()) {
            if ($productsTableCheckerRow['p_price'] == $pPrice && $productsTableCheckerRow['p_description'] == $pDescription && $productsTableCheckerRow['p_remaining'] == $pRemaining) {
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
            else if ($productsTableCheckerRow['p_price'] != $pPrice && $productsTableCheckerRow['p_description'] == $pDescription && $productsTableCheckerRow['p_remaining'] == $pRemaining) {
                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pEdited','Price of $pName ($pCode) from {$productsTableCheckerRow['p_price']} to $pPrice','Products','Edited')") or die ($database->error);
                $database->query("UPDATE `products_tbl` SET `p_price`='$pPrice' WHERE `ID`='$pID'") or die ($database->error);
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Product Price Updated!',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 2000,
                        });
                    </script>
                ";
            }
            else if ($productsTableCheckerRow['p_price'] == $pPrice && $productsTableCheckerRow['p_description'] != $pDescription && $productsTableCheckerRow['p_remaining'] == $pRemaining) {
                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pEdited','Description of $pName ($pCode) from {$productsTableCheckerRow['p_description']} to $pDescription','Products','Edited')") or die ($database->error);
                $database->query("UPDATE `products_tbl` SET `p_description`='$pDescription' WHERE `ID`='$pID'") or die ($database->error);
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Product Description Updated!',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 2000,
                        });
                    </script>
                ";
            }
            else if ($productsTableCheckerRow['p_price'] == $pPrice && $productsTableCheckerRow['p_description'] == $pDescription && $productsTableCheckerRow['p_remaining'] != $pRemaining) {
                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pEdited','Measurement of $pName ($pCode) from {$productsTableCheckerRow['p_remaining']} to $pRemaining','Products','Edited')") or die ($database->error);
                $database->query("UPDATE `products_tbl` SET `p_remaining`='$pRemaining' WHERE `ID`='$pID'") or die ($database->error);
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Product Measurement Updated!',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 2000,
                        });
                    </script>
                ";
            }
            else {
                $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pEdited','Price and Description of $pName ($pCode)','Products','Edited')") or die ($database->error);
                $database->query("UPDATE `products_tbl` SET `p_price`='$pPrice',`p_remaining`='$pRemaining', `p_description`='$pDescription' WHERE `ID`='$pID'") or die ($database->error);
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Product Price and Description Updated!',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 2000,
                        });
                    </script>
                ";
            }
        }
        header("Refresh:2; url=products.php");
	}

    // ==================== END - UPDATING PRODUCTS ====================

    // ==================== START - MARK PRODUCT AS DEFECTIVE ====================

    if(isset($_POST['pDefective'])){
        $pID = $_POST['inputHiddenDefectiveProductID'];
        $pCode = $_POST['inputHiddenDefectiveProductCode'];
        $pName = $_POST['inputHiddenDefectiveProductName'];
        $pDescription = mysqli_real_escape_string($database, $_POST['selectDefectiveProductDescription']);
        $pRejected = date("Y-m-d H:i:s");

        $sqlProductsTable = "UPDATE `products_tbl` SET `p_quality`='Bad', `p_rejected`='$pRejected', `p_description`='$pDescription'  WHERE `ID`='$pID'";
        $database->query($sqlProductsTable) or die ($database->error);

        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pRejected','$pName ($pCode)','Products','Defective')") or die ($database->error);
    
        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: '$pName ($pCode) was marked as Defective!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";

        header("Refresh:2; url=products.php");
    }

    // ==================== END - MARK PRODUCT AS DEFECTIVE ====================

    // ==================== START - DELETION OF PRODUCTS ====================

    if(isset($_POST['pDelete'])){
        $pID = $_POST['inputHiddenDeleteProductID'];
        $pCode = $_POST['inputHiddenDeleteProductCode'];
        $pName = $_POST['inputHiddenDeleteProductName'];
        $pDescription = mysqli_real_escape_string($database, $_POST['inputDeleteProductDescription']);
        $pRejected = date("Y-m-d H:i:s");
        $productsDeletedCounter = $productsDeleted + 1;

        $sqlProductsTable = "UPDATE `products_tbl` SET `p_quality`='Trash', `p_rejected`='$pRejected', `p_description`='$pDescription'  WHERE `ID`='$pID'";
        $database->query($sqlProductsTable) or die ($database->error);

        $database->query("INSERT INTO `stocks_movement_tbl`(`ID`, `sm_admin`, `sm_date`, `sm_event`, `sm_category`, `sm_method`) VALUES (NULL,'$adminID','$pRejected','$pName ($pCode)','Products','Deleted')") or die ($database->error);
        $sqlAdminsUpdateTable = $database->query("UPDATE `admins_tbl` SET `total_products_deleted`='$productsDeletedCounter'") or die ($database->error);
    
        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: '$pName ($pCode) has been Deleted!',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=products.php");
    }

    // ==================== END - DELETION OF PRODUCTS ====================

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
    <title>Products</title>

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

            <!-- ==================== START - PRODUCTS TITLE ROW ==================== -->
            
            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-selection fs-3'></i>&emsp;Products
                    </p>
                    <button class="btn btn-sm btn-primary d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalAddProduct">
                        <i class='bx bx-plus fs-5'></i>&nbsp;Add Product
                    </button>
                </div>
            </div>

            <!-- ==================== END - PRODUCTS TITLE ROW ==================== -->

            <!-- ==================== START - PRODUCTS STATISTICS ROW ==================== -->

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
                                            $sqlProductsTablePending = $database->query("SELECT COUNT(*) AS p_count_pending FROM products_tbl WHERE p_quality = 'Good' AND p_status = 'Pending'")->fetch_array();
                                            $sqlProductsTablePendingCounter = $sqlProductsTablePending['p_count_pending'];
                                            echo $sqlProductsTablePendingCounter;
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
                                            $sqlProductsTableStorage = $database->query("SELECT COUNT(*) AS p_count_storage FROM products_tbl WHERE p_quality = 'Good' AND p_status = 'Storage'")->fetch_array();
                                            $sqlProductsTableStorageCounter = $sqlProductsTableStorage['p_count_storage'];
                                            echo $sqlProductsTableStorageCounter;
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
                                            $sqlProductsTableProcessing = $database->query("SELECT COUNT(*) AS p_count_processing FROM products_tbl WHERE p_quality = 'Good' AND p_status = 'Processing'")->fetch_array();
                                            $sqlProductsTableProcessingCounter = $sqlProductsTableProcessing['p_count_processing'];
                                            echo $sqlProductsTableProcessingCounter;
                                        ?>
                                    </h5>
                                    <p class="card-text">Processing</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
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
                                                $sqlProductsTableDefective = $database->query("SELECT COUNT(*) AS p_count_defective FROM products_tbl WHERE p_quality = 'Bad'")->fetch_array();
                                                $sqlProductsTableDefectiveCounter = $sqlProductsTableDefective['p_count_defective'];
                                                echo $sqlProductsTableDefectiveCounter;
                                            ?>
                                        </h5>
                                        <p class="card-text">Defective</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
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
                                                            echo $productsDeleted;
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
                                                        echo $productsDeleted;
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
                                            $sqlProductsTableSold = $database->query("SELECT COUNT(*) AS p_count_sold FROM products_tbl WHERE p_status = 'Sold'")->fetch_array();
                                            $sqlProductsTableSoldCounter = $sqlProductsTableSold['p_count_sold'];
                                            echo $sqlProductsTableSoldCounter;
                                        ?>
                                    </h5>
                                    <p class="card-text">Sold</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - PRODUCTS STATISTICS ROW ==================== -->

            <div class="row mb-3">
                <div class="col d-flex justify-content-end">
                    <button class="btn btn-sm btn-danger d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">
                        <i class='bx bx-printer fs-5'></i>&nbsp;Print
                    </button>
                </div>
            </div>

            <!-- ==================== START - PRODUCTS ROW ==================== -->

            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs">
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-success active" href="#pCatalog" data-bs-toggle="tab">Catalog</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark border-top-success" href="#pStocks" data-bs-toggle="tab">Stocks</a>
                                </li>
                                <li class="nav-item ms-auto">
                                    <a class="nav-link text-dark border-top-secondary" href="#pSold" data-bs-toggle="tab">Sold</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">

                                <!-- ==================== START - CATALOG PRODUCTS TABLE ==================== -->

                                <div class="tab-pane active" id="pCatalog">
                                    <table class="datatable-desc-1 table table-hover responsive nowrap w-100">
                                        <thead class="bg-success text-light">
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Quantity</th>
                                            <th>Requirements</th>
                                            <th>Manufacturing Cost (in Peso)</th>
                                            <th>Product Price (in Peso)</th>
                                            <th>Completed</th>
                                            <th>Status</th>
                                            <th class="no-sort">Description</th>
                                            <th class="no-sort text-center">Action</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $productsCatalog->fetch_array()){ ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <?php echo "<img src='images/codes/{$fetch['p_code_photo']}' class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'> {$fetch['p_code']}";?>
                                                    </td>
                                                    <td class="align-middle"><img src="images/items/<?php echo $fetch['p_photo']?>" alt="" class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'><?php echo $fetch['p_name']?></td>
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
                                                    <td class="align-middle">
                                                        <div class="d-flex justify-content-between">
                                                            <div class='me-3'>
                                                                <?php
                                                                    $pRequirementsTextForm = '';
                                                                    $pCode = $fetch['p_code'];
                                                                    $pRequiredMaterialsQuery = $database->query("SELECT material_name_tbl.m_name, material_used_tbl.m_measurement, material_used_tbl.m_unit, material_used_tbl.p_code
                                                                                                                FROM `material_used_tbl` 
                                                                                                                INNER JOIN material_name_tbl ON material_used_tbl.m_name = material_name_tbl.ID
                                                                                                                WHERE material_used_tbl.p_code = '$pCode'");
                                                                    while ($row = $pRequiredMaterialsQuery->fetch_assoc()) {
                                                                        if ($row['m_unit'] == 'Piece') {
                                                                            if ($row['m_measurement'] > 1) {
                                                                                $mUnitUsed = 'Pcs';
                                                                            }
                                                                            else {
                                                                                $mUnitUsed = 'Pc';
                                                                            }
                                                                        }
                                                                        else if ($row['m_unit'] == 'Kilogram') {
                                                                            if ($row['m_measurement'] > 1) {
                                                                                $mUnitUsed = 'Kgs';
                                                                            }
                                                                            else {
                                                                                $mUnitUsed = 'Kg';
                                                                            }
                                                                        }
                                                                        else {
                                                                            if ($row['m_measurement'] > 1) {
                                                                                $mUnitUsed = 'Mts';
                                                                            }
                                                                            else {
                                                                                $mUnitUsed = 'Mt';
                                                                            }
                                                                        }
                                                                        $pRequirementsTextForm = $pRequirementsTextForm."{$row['m_measurement']} ".$mUnitUsed." {$row['m_name']} ";
                                                                    }
                                                                    echo mb_strimwidth("$pRequirementsTextForm", 0, 18, "...");
                                                                ?>
                                                            </div>
                                                            <button class="btn btn-sm btn-primary p-1" data-bs-toggle="modal" data-bs-target="#modalViewRequirements<?php echo $fetch['ID']?>">
                                                                <i class='bx bx-show fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="View Requirements"></i>
                                                            </button>
                                                        </div>
                                                    </td>

                                                    <!-- ==================== START - MODAL VIEW PRODUCT REQUIREMENTS ==================== -->
                                                            
                                                    <div class="modal fade" id="modalViewRequirements<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title"><?php echo $fetch['p_name']." (".$fetch['p_code'].")"?> Requirements List</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col fw-bold text-center">
                                                                            Material
                                                                        </div>
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
                                                                        $pCode = $fetch['p_code'];
                                                                        $pRequiredMaterialsQuery = $database->query("SELECT material_name_tbl.m_name, material_used_tbl.m_code, material_used_tbl.m_measurement, material_used_tbl.m_unit, material_used_tbl.m_price, material_used_tbl.p_code
                                                                                                                    FROM `material_used_tbl` 
                                                                                                                    INNER JOIN material_name_tbl ON material_used_tbl.m_name = material_name_tbl.ID
                                                                                                                    WHERE material_used_tbl.p_code = '$pCode'");
                                                                        while ($row = $pRequiredMaterialsQuery->fetch_assoc()) {
                                                                            echo "
                                                                                <div class='row'>
                                                                                    <div class='col text-center'>
                                                                                        {$row['m_name']}
                                                                                    </div>
                                                                                    <div class='col text-center'>
                                                                                        {$row['m_code']}
                                                                                    </div>
                                                                                    <div class='col text-center'>
                                                                                        {$row['m_measurement']} {$row['m_unit']}
                                                                                    </div>
                                                                                    <div class='col text-center'>";
                                                                                        echo "₱".number_format((float)$row['m_price'], 2, '.', ',');
                                                                                    echo "
                                                                                    </div>
                                                                                </div>
                                                                            ";
                                                                        }
                                                                    ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal" name="mEdit" id="mEdit">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL VIEW PRODUCT REQUIREMENTS ==================== -->
                                                    
                                                    <?php $mManufacturingPrice = 0;?>
                                                    <td class="align-middle">
                                                        <?php
                                                            $pCode = $fetch['p_code'];
                                                            $mManufacturingPrice = 0;
                                                            $pRequiredMaterialsQuery = $database->query("SELECT * FROM material_used_tbl WHERE p_code = '$pCode'");
                                                            while ($row = $pRequiredMaterialsQuery->fetch_assoc()) {
                                                                $mManufacturingPrice = $mManufacturingPrice+$row['m_price'];
                                                            }
                                                            echo number_format((float)$mManufacturingPrice, 2, '.', ',');
                                                        ?>
                                                    </td>
                                                    <td class='align-middle rounded
                                                    <?php if ($mManufacturingPrice*1.3 > $fetch['p_price']) {echo "bg-warning";}?>'>
                                                        <?php echo number_format((float)$fetch['p_price'], 2, '.', ',');?>
                                                    </td>
                                                    <td class="align-middle"><?php echo date('F j, Y, h:i a', strtotime($fetch['p_completed']))?></td>   
                                                    <?php 
                                                        if ($fetch['p_status'] == "Pending") {
                                                            echo "<td class='align-middle rounded text-center bg-info bg-gradient'>{$fetch['p_status']}</td>";
                                                        }
                                                        else if ($fetch['p_status'] == "Storage") {
                                                            echo "<td class='align-middle rounded text-center bg-success bg-gradient text-light'>{$fetch['p_status']}</td>";
                                                        }
                                                        else {
                                                            echo "<td class='align-middle rounded text-center bg-warning bg-gradient text-dark'>{$fetch['p_status']}</td>";
                                                        }
                                                    ?>
                                                    <td class="align-middle"><?php echo $fetch['p_description']?></td>
                                                    <td class="align-middle text-center">
                                                        
                                                        <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->

                                                        <?php
                                                            if ($fetch['p_status'] == 'Pending') {
                                                                ?>
                                                                    <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalProductEdit<?php echo $fetch['ID']?>">
                                                                        <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Product"></i>
                                                                    </button>
                                                                    <?php
                                                                        if ($_SESSION['position'] != 'User') {
                                                                            ?>
                                                                                <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalProductDeleteConfirmation<?php echo $fetch['ID']?>">
                                                                                    <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Product"></i>
                                                                                </button>
                                                                            <?php
                                                                        }
                                                                    ?>
                                                                <?php
                                                            }
                                                            else if ($fetch['p_status'] == 'Storage' && $fetch['p_measurement'] == $fetch['p_remaining']) {
                                                                ?>
                                                                    <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalProductEdit<?php echo $fetch['ID']?>">
                                                                        <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Product"></i>
                                                                    </button>
                                                                    <?php
                                                                        if ($_SESSION['position'] != 'User') {
                                                                            ?>
                                                                                <button class="btn btn-sm btn-warning p-1" data-bs-toggle="modal" data-bs-target="#modalProductDefectiveConfirmation<?php echo $fetch['ID']?>">
                                                                                    <i class='bx bx-error fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Mark as Defective"></i>
                                                                                </button>
                                                                                <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalProductDeleteConfirmation<?php echo $fetch['ID']?>">
                                                                                    <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Product"></i>
                                                                                </button>
                                                                            <?php
                                                                        }
                                                                    ?>
                                                                <?php
                                                            }
                                                            else if ($fetch['p_status'] == 'Storage' && $fetch['p_measurement'] != $fetch['p_remaining']) {
                                                                ?>
                                                                    <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalProductEdit<?php echo $fetch['ID']?>">
                                                                        <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Product"></i>
                                                                    </button>
                                                                    <?php
                                                                        if ($_SESSION['position'] != 'User') {
                                                                            ?>
                                                                                <button class="btn btn-sm btn-warning p-1" data-bs-toggle="modal" data-bs-target="#modalProductDefectiveConfirmation<?php echo $fetch['ID']?>">
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
                                                    
                                                    <div class="modal fade" id="modalProductEdit<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="pHeaderEdit"><?php echo "(".$fetch['p_code'].") ".$fetch['p_name'] ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenEditProductID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenEditProductCode" value="<?php echo $fetch['p_code']?>"/>
                                                                        <input type="hidden" name="inputHiddenEditProductName" value="<?php echo $fetch['p_name']?>"/>
                                                                        <div class="mb-2">
                                                                            <label for="inputEditProductPrice" class="form-label">Price</label>
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">₱</span>
                                                                                <input type="number" class="form-control" id="inputEditProductPrice" name="inputEditProductPrice" min="<?php echo $mManufacturingPrice?>" max="999999" value="<?php echo $fetch['p_price']?>" step="0.01">
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label for="inputEditProductMeasurement" class="form-label">Measurement</label>
                                                                            <div class="input-group">
                                                                                <input type="number" class="form-control" id="inputEditProductMeasurement" name="inputEditProductMeasurement" value="<?php echo $fetch['p_remaining']?>" min="0" max="<?php echo $fetch['p_remaining']?>" step="0.01">
                                                                                <input type="text" class="form-control" id="inputEditProductUnit" name="inputEditProductUnit" value="Piece" readonly>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label for="inputEditProductDescription" class="form-label">Description</label>
                                                                            <input type="text" class="form-control" id="inputEditProductDescription" name="inputEditProductDescription" value="<?php echo $fetch['p_description']?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-success" name="pEdit">Update</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL EDIT DATA ==================== -->

                                                    <!-- ==================== START - MODAL DEFECTIVE DATA ==================== -->
                                                    
                                                    <div class="modal fade" id="modalProductDefectiveConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Mark <?php echo $fetch['p_name']?> (<?php echo $fetch['p_code']?>) as Defective</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Are you sure you want to mark this material as Defective?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                        <button type="button" class="btn btn-success" data-bs-target="#modalProductDefectiveStatement<?php echo $fetch['ID']?>" data-bs-toggle="modal" data-bs-dismiss="modal">Yes, Mark It!</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal fade" id="modalProductDefectiveStatement<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Why is it Defective?</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenDefectiveProductID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenDefectiveProductCode" value="<?php echo $fetch['p_code']?>"/>
                                                                        <input type="hidden" name="inputHiddenDefectiveProductName" value="<?php echo $fetch['p_name']?>"/>
                                                                        <div class="mb-2">
                                                                            <label for="selectDefectiveProductDescription" class="form-label">Choose your reason why it is Defective.</label>
                                                                            <select class="form-select" id="selectDefectiveProductDescription" name="selectDefectiveProductDescription" required>
                                                                                <option value="">-- Select a Reason --</option>
                                                                                <option value="Missing Parts">Missing Parts</option>
                                                                                <option value="Faulty Product">Faulty Product</option>
                                                                                <option value="Fabrication Error">Fabrication Error</option>
                                                                                <option value="Incorrect Measurement">Incorrect Measurement</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-target="#modalProductDefectiveConfirmation<?php echo $fetch['ID']?>" data-bs-toggle="modal" data-bs-dismiss="modal">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" name="pDefective">Continue</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ==================== END - MODAL DEFECTIVE DATA ==================== -->

                                                    <!-- ==================== START - MODAL DELETE DATA ==================== -->
                                                    
                                                    <div class="modal fade" id="modalProductDeleteConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="mHeaderDelete">Delete Product</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Are you sure you want to delete this product <?php echo $fetch['p_name']." with the code ".$fetch['p_code'];?>?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel!</button>
                                                                        <button type="button" class="btn btn-success" data-bs-target="#modalProductDeleteStatement<?php echo $fetch['ID']?>" data-bs-toggle="modal" data-bs-dismiss="modal">Yes, Delete It!</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal fade" id="modalProductDeleteStatement<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <form action="" method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Delete Product</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="inputHiddenDeleteProductID" value="<?php echo $fetch['ID']?>"/>
                                                                        <input type="hidden" name="inputHiddenDeleteProductCode" value="<?php echo $fetch['p_code']?>"/>
                                                                        <input type="hidden" name="inputHiddenDeleteProductName" value="<?php echo $fetch['p_name']?>"/>
                                                                        <div class="mb-2">
                                                                            <label for="inputDeleteProductDescription" class="form-label">Enter your reason why you want to delete this product.</label>
                                                                            <input type="text" class="form-control" id="inputDeleteProductDescription" name="inputDeleteProductDescription" placeholder="eg. Accident, Human Error...">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger" data-bs-target="#modalProductDeleteConfirmation<?php echo $fetch['ID']?>" data-bs-toggle="modal" data-bs-dismiss="modal">Wait, Go Back!</button>
                                                                        <button type="submit" class="btn btn-success" data-bs-dismiss="modal" name="pDelete" id="pDelete">Continue</button>
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

                                <!-- ==================== END - CATALOG PRODUCTS TABLE ==================== -->

                                <!-- ==================== START - STOCKS PRODUCTS TABLE ==================== -->

                                <div class="tab-pane" id="pStocks">
                                    <table class="datatable-asc-1 table table-hover responsive no-wrap w-100">
                                        <thead class="bg-success text-light">
                                            <th>Name</th>
                                            <th>Price Range (in Peso)</th>
                                            <th>Quantity</th>
                                            <th>Percentage</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $productsStocks->fetch_array()){ ?>
                                                <tr>
                                                    <td class="align-middle"><img src="images/items/<?php echo $fetch['p_photo']?>" alt="" class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'><?php echo $fetch['p_name']?></td>
                                                    <td class="align-middle">
                                                        <?php 
                                                            if ($fetch['p_low_price'] == $fetch['p_high_price']) {
                                                                echo number_format((float)$fetch['p_low_price'], 2, '.', ',');
                                                            }
                                                            else {
                                                                echo number_format((float)$fetch['p_low_price'], 2, '.', ',')." - ".number_format((float)$fetch['p_high_price'], 2, '.', ',');
                                                            }
                                                        ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <?php 
                                                            echo $fetch['p_name_count']." Piece";
                                                            if ($fetch['p_name_count'] > 1) {
                                                                echo 's';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <?php $pProgressBarCount = number_format((float)($fetch['p_name_count'] / $maxStorage) * 100, 1, '.', ',');?>
                                                        <div class='row d-flex align-items-center'>
                                                            <div class='col'>
                                                                <div class='progress' style='border: 1px solid 
                                                                    <?php
                                                                        if ($pProgressBarCount > 75) {
                                                                            echo "#198754";
                                                                        }
                                                                        else if ($pProgressBarCount > 50) {
                                                                            echo "#ffc107";
                                                                        }
                                                                        else if ($pProgressBarCount > 25) {
                                                                            echo "#fd7e14";
                                                                        }
                                                                        else if ($pProgressBarCount > 0) {
                                                                            echo "#dc3545";
                                                                        }
                                                                        else if ($pProgressBarCount == 0) {
                                                                            echo "#adb5bd";
                                                                        }
                                                                    ?>'>
                                                                    <div class='progress-bar 
                                                                        <?php
                                                                            if ($pProgressBarCount > 75) {
                                                                                echo "bg-success";
                                                                            }
                                                                            else if ($pProgressBarCount > 50) {
                                                                                echo "bg-warning";
                                                                            }
                                                                            else if ($pProgressBarCount > 25) {
                                                                                echo "bg-orange";
                                                                            }
                                                                            else if ($pProgressBarCount > 0) {
                                                                                echo "bg-danger";
                                                                            }
                                                                            else if ($pProgressBarCount == 0) {
                                                                                echo "bg-gray";
                                                                            }
                                                                        ?>' role='progressbar'style='width: <?php echo $pProgressBarCount;?>%' aria-valuemin='0' aria-valuemax='100'>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class='col text-center'>
                                                                <label class='fw-bold'><?php echo $pProgressBarCount;?>%</label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - STOCKS PRODUCTS TABLE ==================== -->

                                <!-- ==================== START - SOLD PRODUCTS TABLE ==================== -->

                                <div class="tab-pane" id="pSold">
                                    <table class="datatable-desc-1 table table-hover responsive no-wrap w-100">
                                        <thead class="bg-secondary text-light">
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Quantity</th>
                                            <th>Manufacturing Cost (in Peso)</th>
                                            <th>Product Price (in Peso)</th>
                                            <th>Sold To</th>
                                        </thead>
                                        <tbody>
                                            <?php while($fetch = $productsSold->fetch_array()){ ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <?php echo "<img src='images/codes/{$fetch['p_code_photo']}' class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'> {$fetch['p_code']}";?>
                                                    </td>
                                                    <td class="align-middle"><img src="images/items/<?php echo $fetch['p_photo']?>" alt="" class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'><?php echo $fetch['p_name']?></td>
                                                    <td class="align-middle">
                                                        <?php 
                                                            echo $fetch['p_measurement']." Piece";
                                                            if ($fetch['p_measurement'] > 1) {
                                                                echo "s";
                                                            }
                                                        ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <?php
                                                            $productCode = $fetch['p_code'];
                                                            $totalManufacturingPrice = 0;
                                                            $materialsUsedTable = $database->query("SELECT m_price FROM material_used_tbl WHERE p_code = '$productCode'");
                                                            while ($materialsUsedTableRow = $materialsUsedTable->fetch_assoc()) {
                                                                $totalManufacturingPrice = $totalManufacturingPrice + $materialsUsedTableRow['m_price'];
                                                            }
                                                            echo number_format((float)$totalManufacturingPrice, 2, '.', ',');
                                                        ?>
                                                    </td>
                                                    <td class="align-middle"><?php echo number_format((float)$fetch['p_price'], 2, '.', ',');?></td>
                                                    <td class="align-middle">
                                                        <?php 
                                                            $productCode = $fetch['p_code'];
                                                            $productUsedTable = $database->query("SELECT product_used_tbl.p_code, projects_tbl.proj_code FROM product_used_tbl INNER JOIN projects_tbl ON product_used_tbl.p_sold_to = projects_tbl.ID WHERE product_used_tbl.p_code = '$productCode' GROUP BY product_used_tbl.p_code");
                                                            while ($productUsedTableRow = $productUsedTable->fetch_assoc()) {
                                                                echo $productUsedTableRow['proj_code'];
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ==================== END - SOLD PRODUCTS TABLE ==================== -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - PRODUCTS ROW ==================== -->

            <!-- ==================== START - ADD PRODUCTS MODAL ==================== -->

            <form class="needs-validation" action="" method="POST" novalidate>
                <div class="modal fade" id="modalAddProduct" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label for="selectProductName" class="form-label">Name</label>
                                    <select class="form-select" name="selectProductName" required>
                                        <option value="">Choose a Product...</option>
                                        <?php 
                                            $query = "SELECT * FROM product_name_tbl";
                                            $result = $database->query($query);
                                            if($result->num_rows > 0){
                                                while ($row = $result->fetch_assoc()){
                                                    echo "<option value='{$row["ID"]}'>{$row['p_name']}</option>";
                                                }
                                            }
                                            else {
                                                echo "<option value=''>No Available Product</option>"; 
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Choose a Product
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="inputProductPrice" class="form-label">Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="inputProductPrice" name="inputProductPrice" min="1" max="999999" step="0.01" required>
                                        <div class="invalid-feedback">
                                            Specify Product Price
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="inputProductMeasurement" class="form-label">Measurement</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control w-50" id="inputProductMeasurement" name="inputProductMeasurement" min="1" value="1" required>
                                        <input type="text" class="form-control" id="inputProductUnit" name="inputProductUnit" value="Piece" readonly>
                                        <div class="invalid-feedback">
                                            Enter A Valid Product Measurement
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="inputProductDescription" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="inputProductDescription" name="inputProductDescription">
                                </div>
                                <div class="mb-2">
                                    <label for="inputProductQuantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="inputProductQuantity" name="inputProductQuantity" min="1" step="1" value="1" required>
                                    <div class="invalid-feedback">
                                        Enter A Valid Quantity
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" id="addProduct" name="addProduct" class="btn btn-primary w-100 add-button">Add Product</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- ==================== END - ADD PRODUCTS MODAL ==================== -->

            <!-- ==================== START - PRINT RECORDS MODAL ==================== -->

            <div>
            </div>

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
                                $productsTablePending = $database->query("SELECT COUNT(*) AS pending_products FROM products_tbl WHERE p_status = 'Pending' AND p_quality = 'Good'")->fetch_assoc()['pending_products'];
                                $productsTableStorage = $database->query("SELECT COUNT(*) AS storage_products FROM products_tbl WHERE p_status = 'Storage' AND p_quality = 'Good'")->fetch_assoc()['storage_products'];
                                if ($_SESSION['position'] != 'User') {
                                    ?>
                                        <label class="fs-6 d-flex justify-content-center mb-2">Print Product Codes</label>
                                        <div class="row row-cols-1 row-cols-lg-2 g-2">
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPendingProductCodes" <?php if ($productsTablePending == 0) { echo "disabled"; } ?>>Pending</button>
                                            </div>
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintStorageProductCodes" <?php if ($productsTableStorage == 0) { echo "disabled"; } ?>>Storage</button>
                                            </div>
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintAllProductCodes">All</button>
                                            </div>
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintIndividualProductCodes">Individual</button>
                                            </div>
                                        </div>
                                        <br>
                                        <label class="fs-6 d-flex justify-content-center mb-2">Print Product Records</label>
                                        <div class="row row-cols-1 row-cols-lg-2 g-2">
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintProductCount">Product Count</button>
                                            </div>
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintMPCount">Material & Product Count</button>
                                            </div>
                                            <div class="col">
                                                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintProductRecords">Product Records</button>
                                            </div>
                                        </div>
                                    <?php
                                }
                                else {
                                    ?>
                                        <div class="row">
                                            <div class="col-12">
                                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintPendingProductCodes" <?php if ($productsTablePending == 0) { echo "disabled"; } ?>>Pending</button>
                                            </div>
                                        </div>
                                    <?php
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalPrintPendingProductCodes" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="code-canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing Pending Product Codes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print all of the QR codes of the products which have the pending status in the storage.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printPendingProductCodes" name="printPendingProductCodes">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalPrintStorageProductCodes" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="code-canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing Products in Storage Codes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print all of the QR codes of the products which have the storage status.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printStorageProductCodes" name="printStorageProductCodes">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalPrintAllProductCodes" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="code-canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing All Product Codes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print all of the QR codes including the products within the system.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printAllProductsCodes" name="printAllProductsCodes">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalPrintIndividualProductCodes" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <form action="code-canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing Individual Product Codes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <label class="d-flex justify-content-center">Choose the product you want to print the codes. </label>
                                <br>
                                <?php
                                    $productsTable = $database->query("SELECT products_tbl.ID, products_tbl.p_code, product_name_tbl.p_name, products_tbl.p_price, products_tbl.p_measurement, products_tbl.p_status FROM products_tbl INNER JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID WHERE p_quality = 'Good' ORDER BY p_code DESC");
                                    if ($productsTable->num_rows > 0) {
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
                                        while ($productsTableRow = $productsTable->fetch_assoc()){
                                            ?>
                                                <div class='row'>
                                                    <div class='col text-center'>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" value="<?php echo $productsTableRow['ID']?>" name="productChoice[]" id="checkBoxPrintProductCodeIndividual<?php echo $productsTableRow['ID']?>">
                                                            <label class="form-check-label" for="checkBoxPrintProductCodeIndividual<?php echo $productsTableRow['ID']?>"><?php echo $productsTableRow['p_code'];?></label>
                                                        </div>
                                                    </div>
                                                    <div class='col text-center'>
                                                        <?php echo $productsTableRow['p_name']?>
                                                    </div>
                                                    <div class='col text-center'>
                                                        ₱<?php echo number_format((float)$productsTableRow['p_price'], 2, '.', ',')?>
                                                    </div>
                                                    <div class='col text-center'>
                                                        <?php 
                                                            echo $productsTableRow['p_measurement']." Piece";
                                                            if ($productsTableRow['p_measurement'] > 1) {
                                                                echo "s";
                                                            }
                                                        ?>
                                                    </div>
                                                    <div class='col text-center'>
                                                        <?php echo $productsTableRow['p_status']?>
                                                    </div>
                                                </div>
                                            <?php
                                        }
                                    }
                                    else {
                                        ?>
                                            <div class="text-center fs-5">
                                                No Products Involved
                                            </div>
                                        <?php
                                    }
                                ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printIndividualProductCodes" name="printIndividualProductCodes">Continue</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalPrintProductCount" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing Product Count</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print the records of how many products left in the storage room.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printProductCount" name="printProductCount" data-bs-dismiss="modal">Yes, Print It!</button>
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

            <div class="modal fade" id="modalPrintProductRecords" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="canvas.php" method="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Printing All Product Records</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                This will print all the records of the products in the products storage.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalPrintOptions">Wait, Go Back!</button>
                                <button type="submit" class="btn btn-success" id="printProductRecords" name="printProductRecords" data-bs-dismiss="modal">Yes, Print It!</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================== END - PRINT RECORDS MODAL ==================== -->

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