<?php
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "settings";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $adminID = $_SESSION['ID'];
    $sqlAdminsTable = $database->query("SELECT max_storage FROM admins_tbl WHERE ID =  $adminID")->fetch_array();
    $sqlAdminsTableFetch = $sqlAdminsTable['max_storage'];

    $sqlMaterialTypes = "SELECT * FROM material_type_tbl";
    $materialTypesFetch = $database->query($sqlMaterialTypes) or die ($database->error);

    $sqlProductNames = "SELECT * FROM product_name_tbl";
    $productNamesFetch = $database->query($sqlProductNames) or die ($database->error);
    
    $sqlMaterialNames = "SELECT material_name_tbl.ID, material_name_tbl.m_name, material_type_tbl.m_type, material_name_tbl.m_photo FROM material_name_tbl INNER JOIN material_type_tbl ON material_name_tbl.m_type_id=material_type_tbl.ID";
    $materialNamesFetch = $database->query($sqlMaterialNames) or die ($database->error);

    $sqlProductRequirements = "SELECT * FROM product_name_tbl";
    $productRequirementsFetch = $database->query($sqlProductRequirements) or die ($database->error);

    $sqlProjectRepresentatives = "SELECT * FROM representatives_tbl";
    $projRepresentativeFetch = $database->query($sqlProjectRepresentatives) or die ($database->error);



    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();



    // ==================== END - QUERIES ====================

    // ==================== START - CHANGE MAX STORAGE CAPACITY ====================

    if(isset($_POST['btnSettingsMaxStorageSave'])){
        $maxStorage = $_POST['inputStorageSpace'];

        if ($maxStorage == $sqlAdminsTableFetch) {
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'info',
                        title: 'Nothing Changed',
                        text: 'Max Storage Capacity is still $maxStorage',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
        else {
            $sqlMaterialsCounter = $database->query("SELECT COUNT(*) AS materialsCounter, material_name_tbl.m_name 
                                                     FROM `materials_tbl` 
                                                     INNER JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID
                                                     WHERE materials_tbl.m_quality != 'Trash' AND materials_tbl.m_status != 'Used' 
                                                     GROUP BY materials_tbl.m_name");
            $sqlProductsCounter = $database->query("SELECT COUNT(*) AS productsCounter, product_name_tbl.p_name 
                                                    FROM `products_tbl` 
                                                    INNER JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID
                                                    WHERE products_tbl.p_quality != 'Trash' AND products_tbl.p_status != 'Sold' 
                                                    GROUP BY products_tbl.p_name");
            $mLargestNumber = 0;
            $pLargestNumber = 0;
            while ($mRow = $sqlMaterialsCounter->fetch_assoc()){
                if ($mRow['materialsCounter'] > $mLargestNumber) {
                    $mLargestNumber = $mRow['materialsCounter'];
                    $mLargestNumberName = $mRow['m_name'];
                }
            }
            while ($pRow = $sqlProductsCounter->fetch_assoc()){
                if ($pRow['productsCounter'] > $pLargestNumber) {
                    $pLargestNumber = $pRow['productsCounter'];
                    $pLargestNumberName = $pRow['p_name'];
                }
            }

            if ($mLargestNumber > $pLargestNumber) {
                if ($mLargestNumber > $maxStorage) {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Unable to Change Storage Capacity',
                                text: 'There are $mLargestNumber $mLargestNumberName in the stock room, cannot adjust to $maxStorage',
                                confirmButtonColor: '#007bff',
                            });
                        </script>
                    ";
                }
                else if ($mLargestNumber <= $maxStorage) {
                    $sqlAdminsUpdateTable = "UPDATE `admins_tbl` SET `max_storage`='$maxStorage'";
                    $database->query($sqlAdminsUpdateTable) or die ($database->error);
        
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Max Storage Settings Updated!',
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 2000
                            });
                        </script>
                    ";
                    header("Refresh:2; url=settings.php");
                }
            }
            else if ($mLargestNumber < $pLargestNumber) {
                if ($pLargestNumber > $maxStorage) {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Unable to Change Storage Capacity',
                                text: 'There are $pLargestNumber $pLargestNumberName in the stock room, cannot adjust to $maxStorage',
                                confirmButtonColor: '#007bff',
                            });
                        </script>
                    ";
                }
                else if ($pLargestNumber == $maxStorage || $pLargestNumber < $maxStorage) {
                    $sqlAdminsUpdateTable = "UPDATE `admins_tbl` SET `max_storage`='$maxStorage'";
                    $database->query($sqlAdminsUpdateTable) or die ($database->error);
        
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Max Storage Settings Updated!',
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 2000
                            });
                        </script>
                    ";
                    header("Refresh:2; url=settings.php");
                }
            }
            else {
                if ($pLargestNumber > $maxStorage) {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Unable to Change Storage Capacity',
                                text: 'There are $mLargestNumber $mLargestNumberName and $pLargestNumber $pLargestNumberName in the stock room, cannot adjust to $maxStorage',
                                confirmButtonColor: '#007bff',
                            });
                        </script>
                    ";
                }
                else if ($pLargestNumber == $maxStorage || $pLargestNumber < $maxStorage) {
                    $sqlAdminsUpdateTable = "UPDATE `admins_tbl` SET `max_storage`='$maxStorage'";
                    $database->query($sqlAdminsUpdateTable) or die ($database->error);
        
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Max Storage Settings Updated!',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        </script>
                    ";
                    header("Refresh:2; url=settings.php");
                }
            }
        }
    }

    // ==================== END - CHANGE MAX STORAGE CAPACITY ====================

    // ==================== START - ADD NEW MATERIAL TYPE ====================

    if(isset($_POST['btnSettingsNewMaterialTypeSave'])){
        $newMaterialType = mysqli_real_escape_string($database, $_POST['inputNewMaterialType']);

        $sqlMaterialTypes = $database->query("SELECT m_type FROM `material_type_tbl`");
        $mTypesBoolean = 0;
        while ($mTypesRow = $sqlMaterialTypes->fetch_array()) {
            if ($newMaterialType == $mTypesRow['m_type'] || $newMaterialType == $mTypesRow['m_type']."s" || $newMaterialType == substr($mTypesRow['m_type'], 0, -1)) {
                $mTypesBoolean = 1;
            }
        }
        if ($mTypesBoolean == 1) {
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'Unable to Add $newMaterialType',
                        text: 'There is an existing $newMaterialType in the database',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
        else {
            $imgFolder = "images/types/";
            $imgName = basename($_FILES["inputNewMaterialTypeImage"]["name"]);
            $imgDirectory = $imgFolder.$imgName;
            $imgType = pathinfo($imgDirectory,PATHINFO_EXTENSION);
            $imgValidExtension = array('jpg','png','jpeg');
    
    
            if(in_array($imgType, $imgValidExtension)){
                if ($_FILES['inputNewMaterialTypeImage']['size'] < 1000000) {
                    if (move_uploaded_file($_FILES['inputNewMaterialTypeImage']['tmp_name'], $imgDirectory)) {
        
                        $sqlMaterialTypesTable = "INSERT INTO `material_type_tbl`(`ID`, `m_type`, `m_type_photo`) VALUES (NULL,'$newMaterialType', '$imgName')";
                        $database->query($sqlMaterialTypesTable) or die ($database->error);
    
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'New Material Type Added!',
                                    text: 'Added $newMaterialType in the Material Types!',
                                    showConfirmButton: false,
                                    timer: 2000
                                });
                            </script>
                        ";
                        header("Refresh:2; url=settings.php");
                    } 
                    else {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'Unable to add $newMaterialType',
                                    text: 'There seems to be a problem with uploading the photo',
                                    showConfirmButton: false,
                                    timer: 2000
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
                                title: 'File Size Too Large',
                                text: 'The photo of $newMaterialType should not exceed 1 Megabyte',
                                confirmButtonColor: '#007bff',
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
                            title: 'Incorrect File Type',
                            text: 'The photo of $newMaterialType should only be .jpg, .png or .jpeg',
                            confirmButtonColor: '#007bff',
                        });
                    </script>
                ";
            }
        }
    };

    // ==================== END - ADD NEW MATERIAL TYPE ====================

    // ==================== START - ADD NEW MATERIAL NAME ====================

    if(isset($_POST['btnSettingsNewMaterialNameSave'])){
        
        $selectMaterialType = $_POST['selectMaterialType'];
        $newMaterialName = mysqli_real_escape_string($database, $_POST['inputNewMaterialName']);
        
        
        $imgFolder = "images/items/";
        $imgName = basename($_FILES["inputNewMaterialImage"]["name"]);
        $imgDirectory = $imgFolder.$imgName;
        $imgType = pathinfo($imgDirectory,PATHINFO_EXTENSION);
        $imgValidExtension = array('jpg','png','jpeg');


        if(in_array($imgType, $imgValidExtension)){
            if ($_FILES['inputNewMaterialImage']['size'] < 1000000) {
                if (move_uploaded_file($_FILES['inputNewMaterialImage']['tmp_name'], $imgDirectory)) {
    
                    $sqlMaterialNamesTable = "INSERT INTO `material_name_tbl`(`ID`, `m_type_id`, `m_name`, `m_photo`) VALUES (NULL,'$selectMaterialType','$newMaterialName', '$imgName')";
                    $database->query($sqlMaterialNamesTable) or die ($database->error);
            
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'New Material Item Added!',
                                text: 'Added $newMaterialName in the Material Items!',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        </script>
                    ";
                    header("Refresh:2; url=settings.php");
                } 
                else {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Unable to add $newMaterialName',
                                text: 'There seems to be a problem with uploading the photo',
                                showConfirmButton: false,
                                timer: 2000
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
                            title: 'File Size Too Large',
                            text: 'The photo of $newMaterialName should not exceed 1 Megabyte',
                            confirmButtonColor: '#007bff',
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
                        title: 'Incorrect File Type',
                        text: 'The photo of $newMaterialName should only be .jpg, .png or .jpeg',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
    }

    // ==================== END - ADD NEW MATERIAL NAME ====================

    // ==================== START - EDIT MATERIAL TYPE ====================

    if(isset($_POST['mTypeEdit'])){
        if(empty($_POST['inputEditMaterialTypeImage'])){
            $mTypeID = $_POST['inputHiddenEditMaterialTypeID'];
            $mNewType = mysqli_real_escape_string($database, $_POST['inputEditMaterialType']);

            $sqlMaterialTypesTable = "UPDATE `material_type_tbl` SET `m_type` = '$mNewType' WHERE `ID` = $mTypeID";
            $database->query($sqlMaterialTypesTable) or die ($database->error);
    
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Material Type Renamed!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                </script>
            ";
            header("Refresh:2; url=settings.php");
        }
        else {
            $mTypeID = $_POST['inputHiddenEditMaterialTypeID'];
            $mNewType = mysqli_real_escape_string($database, $_POST['inputEditMaterialType']);

            $imgFolder = "images/types/";
            $imgName = basename($_FILES["inputEditMaterialTypeImage"]["name"]);
            $imgDirectory = $imgFolder.$imgName;
            $imgType = pathinfo($imgDirectory,PATHINFO_EXTENSION);
            $imgValidExtension = array('jpg','png','jpeg');

            if(in_array($imgType, $imgValidExtension)){
                if ($_FILES['inputEditMaterialTypeImage']['size'] < 1000000) {
                    if (move_uploaded_file($_FILES['inputEditMaterialTypeImage']['tmp_name'], $imgDirectory)) {

                        $sqlMaterialTypesTable = "UPDATE `material_type_tbl` SET `m_type` = '$mNewType', `m_type_photo` = '$imgName' WHERE `ID` = $mTypeID";
                        $database->query($sqlMaterialTypesTable) or die ($database->error);
                
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'Material Type Updated!',
                                    showConfirmButton: false,
                                    timer: 2000
                                });
                            </script>
                        ";
                        header("Refresh:2; url=settings.php");
                    } 
                    else {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'Unable to add $mNewType',
                                    text: 'There seems to be a problem with uploading the photo',
                                    showConfirmButton: false,
                                    timer: 2000
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
                                title: 'File Size Too Large',
                                text: 'The photo of $mNewType should not exceed 1 Megabyte',
                                confirmButtonColor: '#007bff',
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
                            title: 'Incorrect File Type',
                            text: 'The photo of $mNewType should only be .jpg, .png or .jpeg',
                            confirmButtonColor: '#007bff',
                        });
                    </script>
                ";
            }
        }
    }

    // ==================== END - EDIT MATERIAL TYPE ====================

    // ==================== START - DELETE MATERIAL TYPE ====================

    if(isset($_POST['mTypeDelete'])){
        $mTypeID = $_POST['inputHiddenDeleteMaterialTypeID'];
        $mType = $_POST['inputHiddenDeleteMaterialType'];

        $sqlMaterialTypes = $database->query("SELECT * FROM `materials_tbl` WHERE m_type = '$mTypeID'");
        if ($sqlMaterialTypes->num_rows > 0) {
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'Cannot Be Deleted',
                        text: 'There are currently active materials with this material type in the system',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
        else {
            $sqlMaterialItems = $database->query("SELECT * FROM `material_name_tbl` WHERE m_type_id = '$mTypeID'");
            if ($sqlMaterialItems->num_rows > 0) {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Cannot Be Deleted',
                            text: 'There are items linked to this type, try to delete them first',
                            confirmButtonColor: '#007bff',
                        });
                    </script>
                ";
            }
            else {
                $sqlMaterialTypesTable = "DELETE FROM `material_type_tbl` WHERE ID = $mTypeID";
                $database->query($sqlMaterialTypesTable) or die ($database->error);
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Successfully Deleted',
                            text: '$mType can no longer be used, unless you create it again',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    </script>
                ";
                header("Refresh:2; url=settings.php");
            }
        }
    }

    // ==================== END - DELETE MATERIAL TYPE ====================

    // ==================== START - RENAME MATERIAL NAME ====================

    if(isset($_POST['mNameEdit'])){
        if(empty($_POST['inputEditMaterialImage'])){
            $mNameID = $_POST['inputHiddenEditMaterialNameID'];
            $mOldName = $_POST['inputHiddenOldMaterialName'];
            $mEditName = mysqli_real_escape_string($database, $_POST['inputEditMaterialName']);

            $sqlMaterialNamesTable = "UPDATE `material_name_tbl` SET `m_name` = '$mEditName' WHERE `ID` = $mNameID";
            $database->query($sqlMaterialNamesTable) or die ($database->error);

            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: '$mOldName has been changed to $mEditName',
                        showConfirmButton: false,
                        timer: 2000
                    });
                </script>
            ";
            header("Refresh:2; url=settings.php");
        }
        else {
            $mNameID = $_POST['inputHiddenEditMaterialNameID'];
            $mOldName = $_POST['inputHiddenOldMaterialName'];
            $mEditName = mysqli_real_escape_string($database, $_POST['inputEditMaterialName']);

            $imgFolder = "images/items/";
            $imgName = basename($_FILES["inputEditMaterialImage"]["name"]);
            $imgDirectory = $imgFolder.$imgName;
            $imgType = pathinfo($imgDirectory,PATHINFO_EXTENSION);
            $imgValidExtension = array('jpg','png','jpeg');

            if(in_array($imgType, $imgValidExtension)){
                if ($_FILES['inputEditMaterialImage']['size'] < 1000000) {
                    if (move_uploaded_file($_FILES['inputEditMaterialImage']['tmp_name'], $imgDirectory)) {
        
                        $sqlMaterialNamesTable = "UPDATE `material_name_tbl` SET `m_name` = '$mEditName', `m_photo`='$imgName' WHERE `ID` = $mNameID";
                        $database->query($sqlMaterialNamesTable) or die ($database->error);
                
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'Update Successful!',
                                    text: '$mOldName has been changed to $mEditName',
                                    showConfirmButton: false,
                                    timer: 2000
                                });
                            </script>
                        ";
                        header("Refresh:2; url=settings.php");

                    } 
                    else {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'Unable to update $mOldName',
                                    text: 'There seems to be a problem with uploading the photo',
                                    showConfirmButton: false,
                                    timer: 2000
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
                                title: 'File Size Too Large',
                                text: 'The photo of $mEditName should not exceed 1 Megabyte',
                                confirmButtonColor: '#007bff',
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
                            title: 'Incorrect File Type',
                            text: 'The photo of $mEditName should only be .jpg, .png or .jpeg',
                            confirmButtonColor: '#007bff',
                        });
                    </script>
                ";
            }
        }
    }

    // ==================== END - RENAME MATERIAL NAME ====================

    // ==================== START - DELETE MATERIAL NAME ====================

    if(isset($_POST['mNameDelete'])){
        $mNameID = $_POST['inputHiddenDeleteMaterialNameID'];
        $mName = $_POST['inputHiddenDeleteMaterialName'];
        $mPhoto = $_POST['inputHiddenDeleteMaterialPhoto'];
        $mImageFolder = "images/items/";

        $materialsTable = $database->query("SELECT * FROM materials_tbl WHERE m_name = '$mNameID'");
        if ($materialsTable->num_rows > 0) {
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'Cannot be Deleted',
                        text: 'There are currently active materials with this material name in the system',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
        else {
            $sqlMaterialNamesTable = "DELETE FROM `material_name_tbl` WHERE ID = $mNameID";
            $database->query($sqlMaterialNamesTable) or die ($database->error);
    
            unlink($mImageFolder . $mPhoto);
    
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Deleting Complete!',
                        text: '$mName can no longer be used, unless you create it again',
                        showConfirmButton: false,
                        timer: 2000
                    });
                </script>
            ";
            header("Refresh:2; url=settings.php");
        }
    }

    // ==================== END - DELETE MATERIAL NAME ====================

    // ==================== START - ADD NEW PRODUCT ====================

    if(isset($_POST['btnSettingsNewProductSave'])){
        $newProductName = mysqli_real_escape_string($database, $_POST['inputNewProductName']);
        $imgProductFolder = "images/items/";
        $imgProductName = basename($_FILES["inputNewProductImage"]["name"]);
        $imgProductDirectory = $imgProductFolder.$imgProductName;
        $imgProductType = pathinfo($imgProductDirectory,PATHINFO_EXTENSION);
        $imgProductValidExtension = array('jpg','png','jpeg');

        if(in_array($imgProductType, $imgProductValidExtension)){
            if ($_FILES['inputNewProductImage']['size'] < 1000000) {
                if (move_uploaded_file($_FILES['inputNewProductImage']['tmp_name'], $imgProductDirectory)) {

                    $sqlProductNamesTable = "INSERT INTO `product_name_tbl`(`ID`, `p_name`, `p_photo`) VALUES (NULL,'$newProductName', '$imgProductName')";
                    $database->query($sqlProductNamesTable) or die ($database->error);
            
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'New Product Added!',
                                text: 'Added $newProductName in the Products Database!',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        </script>
                    ";
                    header("Refresh:2; url=settings.php");
                }
                else {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Unable To Add $mOldName',
                                text: 'There seems to be a problem with uploading the photo',
                                showConfirmButton: false,
                                timer: 2000
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
                            title: 'File Size Too Large',
                            text: 'The photo of $newProductName should not exceed 1 Megabyte',
                            confirmButtonColor: '#007bff',
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
                        title: 'Incorrect File Type',
                        text: 'The photo of $newProductName should only be .jpg, .png or .jpeg',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
    };

    // ==================== START - ADD NEW PRODUCT ====================

    // ==================== START - ADD PRODUCT REQUIREMENTS ====================

    if(isset($_POST['btnSettingsProductRequirementsSave'])){
        $cloneCounter = $_POST['inputCloneCounter'];

        $pRequired = $_POST['selectProductName'];       
        $requiredMaterial = $_POST['selectRequiredMaterial'];
        $requiredMaterialMeasurement = $_POST['inputRequiredMaterialMeasurement'];
        $requiredMaterialUnit = $_POST['selectRequiredMaterialUnit'];

        $productRequirementsTable = $database->query("SELECT * FROM `product_requirement_tbl` WHERE p_name = '$pRequired' AND m_name = '$requiredMaterial' AND m_unit = '$requiredMaterialUnit'");
        if ($productRequirementsTable->num_rows > 0) {
            while ($pRequirementsTableRow = $productRequirementsTable->fetch_assoc()) {
                $existingRequirementMeasurement = $pRequirementsTableRow['m_measurement'] + $requiredMaterialMeasurement;
                $sqlProductRequirementsTable = "UPDATE `product_requirement_tbl` SET `m_measurement`='$existingRequirementMeasurement' WHERE p_name = '$pRequired' AND m_name = '$requiredMaterial' AND m_unit = '$requiredMaterialUnit'";
                $database->query($sqlProductRequirementsTable) or die ($database->error);
            }
        }
        else {
            $sqlProductRequirementsTable = "INSERT INTO `product_requirement_tbl`(`ID`, `p_name`, `m_name`, `m_measurement`, `m_unit`) VALUES (NULL, '$pRequired', '$requiredMaterial', '$requiredMaterialMeasurement', '$requiredMaterialUnit')";
            $database->query($sqlProductRequirementsTable) or die ($database->error);
        }
        
        for ($a = 1; $a < $cloneCounter; $a++) {
            ${'selectRequiredMaterial'.$a} = $_POST['selectRequiredMaterial'.$a];
            ${'inputRequiredMaterialMeasurement'.$a} = $_POST['inputRequiredMaterialMeasurement'.$a];
            ${'selectRequiredMaterialUnit'.$a} = $_POST['selectRequiredMaterialUnit'.$a];

            ${'productRequirementsTable'.$a} = $database->query("SELECT * FROM `product_requirement_tbl` WHERE p_name = '$pRequired' AND m_name = '${'selectRequiredMaterial'.$a}' AND m_unit = '${'selectRequiredMaterialUnit'.$a}'");
            if (${'productRequirementsTable'.$a}->num_rows > 0) {
                while (${'pRequirementsTableRow'.$a} = ${'productRequirementsTable'.$a}->fetch_assoc()) {
                    ${'existingRequirementMeasurement'.$a} = ${'pRequirementsTableRow'.$a}['m_measurement'] + ${'inputRequiredMaterialMeasurement'.$a};
                    ${'sqlProductRequirementsTable'.$a} = "UPDATE `product_requirement_tbl` SET `m_measurement`='${'existingRequirementMeasurement'.$a}' WHERE p_name = '$pRequired' AND m_name = '${'selectRequiredMaterial'.$a}' AND m_unit = '${'selectRequiredMaterialUnit'.$a}'";
                    $database->query(${'sqlProductRequirementsTable'.$a}) or die ($database->error);
                }
            }
            else {
                ${'sqlProductRequirementsTable'.$a} = "INSERT INTO `product_requirement_tbl`(`ID`, `p_name`, `m_name`, `m_measurement`, `m_unit`) VALUES (NULL, '$pRequired', '${'selectRequiredMaterial'.$a}', '${'inputRequiredMaterialMeasurement'.$a}', '${'selectRequiredMaterialUnit'.$a}')";
                $database->query(${'sqlProductRequirementsTable'.$a}) or die ($database->error);
            }
        }

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Product Requirements Saved!',
                    text: 'Added $cloneCounter Product Requirement',
                    showConfirmButton: false,
                    timer: 2000
                });
            </script>
        ";
        header("Refresh:2; url=settings.php");
    };

    // ==================== END - ADD PRODUCT REQUIREMENTS ====================

    // ==================== START - RENAME PRODUCT ====================

    if(isset($_POST['pNameEdit'])){
        if(empty($_POST['inputEditProductImage'])){
            $pNameID = $_POST['inputHiddenEditProductNameID'];
            $pOldName = $_POST['inputHiddenOldProductName'];
            $pEditName = mysqli_real_escape_string($database, $_POST['inputEditProductName']);

            $sqlProductNamesTable = "UPDATE `product_name_tbl` SET `p_name` = '$pEditName' WHERE `ID` = $pNameID";
            $database->query($sqlProductNamesTable) or die ($database->error);

            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: '$pOldName is renamed to $pEditName',
                        showConfirmButton: false,
                        timer: 2000
                    });
                </script>
            ";
            header("Refresh:2; url=settings.php");
        }
        else {
            $pNameID = $_POST['inputHiddenEditProductNameID'];
            $pOldName = $_POST['inputHiddenOldProductName'];
            $pEditName = mysqli_real_escape_string($database, $_POST['inputEditProductName']);
    
            $imgEditProductFolder = "images/items/";
            $imgEditProductName = basename($_FILES["inputEditProductImage"]["name"]);
            $imgEditProductDirectory = $imgEditProductFolder.$imgEditProductName;
            $imgEditProductType = pathinfo($imgEditProductDirectory,PATHINFO_EXTENSION);
            $imgEditProductValidExtension = array('jpg','png','jpeg');
    
            if(in_array($imgEditProductType, $imgEditProductValidExtension)){
                if ($_FILES['inputEditProductImage']['size'] < 1000000) {
                    if (move_uploaded_file($_FILES['inputEditProductImage']['tmp_name'], $imgEditProductDirectory)) {
                        
                        $sqlProductNamesTable = "UPDATE `product_name_tbl` SET `p_name` = '$pEditName', `p_photo` = '$imgEditProductName' WHERE `ID` = $pNameID";
                        $database->query($sqlProductNamesTable) or die ($database->error);
                
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'Update Complete!',
                                    text: '$pOldName is renamed to $pEditName',
                                    showConfirmButton: false,
                                    timer: 2000
                                });
                            </script>
                        ";
                        header("Refresh:2; url=settings.php");
                    }
                    else {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'Unable To Update $pOldName',
                                    text: 'There seems to be a problem with uploading the photo',
                                    showConfirmButton: false,
                                    timer: 2000
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
                                title: 'File Size Too Large',
                                text: 'The photo of $pEditName should not exceed 1 Megabyte',
                                confirmButtonColor: '#007bff',
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
                            title: 'Incorrect File Type',
                            text: 'The photo of $pEditName should only be .jpg, .png or .jpeg',
                            confirmButtonColor: '#007bff',
                        });
                    </script>
                ";
            }
        }
    };

    // ==================== END - RENAME PRODUCT ====================

    // ==================== START - DELETE PRODUCT ====================

    if(isset($_POST['pNameDelete'])){
        $pNameID = $_POST['inputHiddenDeleteProductNameID'];
        $pName = $_POST['inputHiddenDeleteProductName'];

        $productsTable = $database->query("SELECT * FROM products_tbl WHERE p_name = '$pNameID'");
        if ($productsTable->num_rows > 0){
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'Cannot be Deleted',
                        text: 'There are currently active products with this product name in the system',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
        else {
            $sqlProductNamesTable = "DELETE FROM `product_name_tbl` WHERE ID = $pNameID";
            $database->query($sqlProductNamesTable) or die ($database->error);
    
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Successfully Deleted',
                        text: '$pName can no longer be used, unless you create it again',
                        showConfirmButton: false,
                        timer: 2000
                    });
                </script>
            ";
            header("Refresh:2; url=settings.php");
        }
    }

    // ==================== END - DELETE PRODUCT ====================

    // ==================== START - DELETE SINGLE PRODUCT REQUIREMENT ====================

    if(isset($_POST['btnDeleteSingleProductRequirement'])){
        
        $pSingleMaterialID = $_POST['inputHiddenSingleProductRequirementID'];
        $pSingleMaterialName = $_POST['inputMaterialName'];
        
        $pSingleMaterialNameFetch = $database->query("SELECT m_name FROM material_name_tbl WHERE m_name = '$pSingleMaterialName'")->fetch_assoc();
        $pSingleMaterialQuantity = $_POST['inputSingleProductRequirementQuantity'];
        $pProductName = $_POST['inputHiddenProductName'];
        

        $sqlProductRequirementsTable = "DELETE FROM `product_requirement_tbl` WHERE ID = $pSingleMaterialID";
        $database->query($sqlProductRequirementsTable) or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Removing Complete!',
                    text: '$pSingleMaterialQuantity ".$pSingleMaterialNameFetch['m_name']." has been removed from $pProductName',
                    showConfirmButton: false,
                    timer: 2000
                });
            </script>
        ";

        
        header("Refresh:2; url=settings.php");
    }

    // ==================== END - DELETE SINGLE PRODUCT REQUIREMENT ====================


    // ==================== START - ADD NEW SALES REPRESENTATIVE ====================

    if(isset($_POST['btnSettingsNewRepresentative'])){
        
        $projSRFirstname = mysqli_real_escape_string($database, $_POST['inputSRFirstname']);
        $projSRLastname = mysqli_real_escape_string($database, $_POST['inputSRLastname']);
        $projSRSex = $_POST['selectSRSex'];

        $sqlProjectRepresentativesTable = "INSERT INTO `representatives_tbl`(`ID`, `rep_firstname`, `rep_lastname`, `rep_sex`) VALUES (NULL,'$projSRFirstname','$projSRLastname','$projSRSex')";
        $database->query($sqlProjectRepresentativesTable) or die ($database->error);

        if ($projSRSex == "Male") {
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'New Sales Representative Added!',
                        text: 'Mr. $projSRLastname can now be chosen as a Sales Representative',
                        showConfirmButton: false,
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
                        title: 'New Sales Representative Added!',
                        text: 'Ms. $projSRLastname can now be chosen as a Sales Representative',
                        showConfirmButton: false,
                        timer: 2000
                    });
                </script>
            ";
        }

        header("Refresh:2; url=settings.php");
    }

    // ==================== END - ADD NEW SALES REPRESENTATIVE ====================

    // ==================== START - EDIT SALES REPRESENTATIVE ====================

    if(isset($_POST['projRepresentativeEdit'])){   
        $projSRIDEdit = $_POST['inputHiddenEditProjRepEditID'];
        $projSRFirstnameEdit = mysqli_real_escape_string($database, $_POST['inputEditProjRepFirstname']);
        $projSRLastnameEdit = mysqli_real_escape_string($database, $_POST['inputEditProjRepLastname']);
        $projSRSexEdit = $_POST['selectSRSexEdit'];

        $sqlProjectRepresentativesTable = "UPDATE `representatives_tbl` SET `rep_firstname`='$projSRFirstnameEdit',`rep_lastname`='$projSRLastnameEdit',`rep_sex`='$projSRSexEdit' WHERE `ID` = '$projSRIDEdit'";
        $database->query($sqlProjectRepresentativesTable) or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Sales Representative Info Updated',
                    showConfirmButton: false,
                    timer: 2000
                });
            </script>
        ";

        header("Refresh:2; url=settings.php");
    }

    // ==================== END - EDIT SALES REPRESENTATIVE ====================

    // ==================== START - DELETE SALES REPRESENTATIVE ====================

    if(isset($_POST['projRepresentativeDelete'])){
        $projSRIDDelete = $_POST['inputHiddenDeleteProjRepID'];
        $projSRNameDelete = $_POST['inputHiddenDeleteProjRepName'];

        $sqlProjectRepresentativesTable = "DELETE FROM `representatives_tbl` WHERE `ID` = '$projSRIDDelete'";
        $database->query($sqlProjectRepresentativesTable) or die ($database->error);

        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Sales Representative Removed!',
                    text: '$projSRNameDelete has been removed as a Sales Representative',
                    showConfirmButton: false,
                    timer: 2000
                });
            </script>
        ";

        header("Refresh:2; url=settings.php");
    }

    // ==================== END - DELETE SALES REPRESENTATIVE ====================

    





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
    <title>Settings</title>

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

            <!-- ==================== START - SETTINGS TITLE ROW ==================== -->

            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bx-cog fs-3'></i>&emsp;Settings
                    </p>
                </div>
            </div>

            <!-- ==================== END - SETTINGS TITLE ROW ==================== -->

            <!-- ==================== START - SETTINGS ROW ==================== -->

            <div class="row">

                <!-- ==================== START - STORAGE CAPACITY ==================== -->

                <div class="col-sm-12 col-lg-6">
                    <form action="" method="POST">
                        <div class="card">
                            <h5 class="card-header bg-primary text-light">Storage Capacity</h5>
                            <div class="card-body">
                                <div class="input-group mb-3">
                                    <input type="number" class="form-control" placeholder="e.g. 100 or 500" min="1" max="10000" name="inputStorageSpace">
                                    <button type="submit" class="btn btn-primary" name="btnSettingsMaxStorageSave">Save</button>
                                </div>
                                <p class="card-text">Set the amount of each materials that the stock room can hold. This can affect the progress bars at the Dashboard, Materials and Products Modules.</p>
                                <p class="fw-bold mb-0">Current Setting: <?php echo $sqlAdminsTableFetch;?> per Material</p>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- ==================== END - STORAGE CAPACITY ==================== -->

                <!-- ==================== START - SALES REPRESENTATIVE ==================== -->

                <div class="col-sm-12 col-lg-6">
                    <div class="card">
                        <h5 class="card-header bg-primary text-light">Sales Representatives</h5>
                        <div class="card-body">
                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary active" href="#settingsAddRepresentative" data-bs-toggle="tab">Add</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary" href="#settingsModifyRepresentative" data-bs-toggle="tab">Modify</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">

                                        <!-- ==================== START - ADD SALES REPRESENTATIVE ==================== -->

                                        <div class="tab-pane active" id="settingsAddRepresentative">
                                            <form action="" method="POST">
                                                <label class="mb-2">Sales Representative:</label>
                                                <div class="row mb-3">
                                                    <div class="col-sm-12 col-md-6">
                                                        <input type="text" class="form-control" placeholder="Firstname" name="inputSRFirstname" required>
                                                    </div>
                                                    <div class="col-sm-12 col-md-6">
                                                        <input type="text" class="form-control" placeholder="Lastname" name="inputSRLastname" required>
                                                    </div>
                                                </div>
                                                <div class="input-group">
                                                    <select class="form-select" name="selectSRSex" required>
                                                        <option value="">Select Sex...</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                    <button class="btn btn-primary" type="submit" id="btnSettingsNewRepresentative" name="btnSettingsNewRepresentative">Add Representative</button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- ==================== END - ADD SALES REPRESENTATIVE ==================== -->

                                        <!-- ==================== START - EDIT SALES REPRESENTATIVE ==================== -->

                                        <div class="tab-pane" id="settingsModifyRepresentative">
                                            <table class="datatable-asc-2-paging-off table table-hover responsive text-center nowrap w-100">
                                                <thead class="bg-primary text-light">
                                                    <th>Firstname</th>
                                                    <th>Lastname</th>
                                                    <th>Sex</th>
                                                    <th class="no-sort">Action</th>
                                                </thead>
                                                <tbody>
                                                    <?php while($fetch = $projRepresentativeFetch->fetch_array()){ ?>
                                                        <tr class="align-middle">
                                                            <td><?php echo $fetch['rep_firstname']?></td>
                                                            <td><?php echo $fetch['rep_lastname']?></td>
                                                            <td><?php echo $fetch['rep_sex']?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalProjRepEdit<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalProjRepDeleteConfirmation<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"></i>
                                                                </button>
                                                            </td>

                                                            <!-- ==================== START - MODAL RENAME DATA ==================== -->
                                                                
                                                            <div class="modal fade" id="modalProjRepEdit<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="false">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Edit Sales Representative</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="inputHiddenEditProjRepEditID" value="<?php echo $fetch['ID']?>"/>

                                                                                <label class="mb-2">Firstname: </label>
                                                                                <input type="text" class="form-control mb-3" id="inputEditProjRepFirstname" name="inputEditProjRepFirstname" value="<?php echo $fetch['rep_firstname']?>" required>
                                                                                
                                                                                <label class="mb-2">Lastname: </label>
                                                                                <input type="text" class="form-control mb-3" id="inputEditProjRepLastname" name="inputEditProjRepLastname" value="<?php echo $fetch['rep_lastname']?>" required>
                                                                                
                                                                                <label class="mb-2">Sex: </label>
                                                                                <select class="form-select" name="selectSRSexEdit" required>
                                                                                    <option value="Male" <?php if($fetch['rep_sex'] == "Male") echo "selected";?>>Male</option>
                                                                                    <option value="Female" <?php if($fetch['rep_sex'] == "Female") echo "selected";?>>Female</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" class="btn btn-success" name="projRepresentativeEdit" id="projRepresentativeEdit">Update</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- ==================== END - MODAL RENAME DATA ==================== -->

                                                            <!-- ==================== START - MODAL DELETE DATA ==================== -->
                                                                
                                                            <div class="modal fade" id="modalProjRepDeleteConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Remove Sales Representative?</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="inputHiddenDeleteProjRepID" value="<?php echo $fetch['ID']?>">
                                                                                <input type="hidden" name="inputHiddenDeleteProjRepName" value="<?php echo $fetch['rep_firstname']." ".$fetch['rep_lastname']?>">
                                                                                Are you sure you want to remove <?php echo $fetch['rep_firstname']." ".$fetch['rep_lastname']?> as Sales Representative? You can't revert this change.
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, cancel!</button>
                                                                                <button type="submit" class="btn btn-success" name="projRepresentativeDelete" id="projRepresentativeDelete">Yes, Remove it!</button>
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

                                        <!-- ==================== END - EDIT SALES REPRESENTATIVE ==================== -->
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== END - SALES REPRESENTATIVE ==================== -->

            </div>

            <hr class="my-5">

            <div class="row">
                <div class="col-sm-12 col-lg-6">
                    <div class="card">
                        <h5 class="card-header bg-primary text-light">Add New Materials</h5>
                        <div class="card-body">
                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary active" href="#settingsAddMaterialType" data-bs-toggle="tab">Add Material Type</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary" href="#settingsAddMaterialName" data-bs-toggle="tab">Add Material Item</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">

                                        <!-- ==================== START - ADD MATERIAL TYPE ==================== -->

                                        <div class="tab-pane active" id="settingsAddMaterialType">
                                            <form action="" method="POST" enctype="multipart/form-data">
                                                <label class="mb-2">New Material Type:</label>
                                                <input type="text" class="form-control mb-3" placeholder="Enter New Material Type..." name="inputNewMaterialType" required>
                                                <label class="mb-2">Material Type Image:</label>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="inputNewMaterialTypeImage" name="inputNewMaterialTypeImage" required>
                                                    <button class="btn btn-primary" type="submit" id="btnSettingsNewMaterialTypeSave" name="btnSettingsNewMaterialTypeSave">Add New Type</button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- ==================== END - ADD MATERIAL TYPE ==================== -->

                                        <!-- ==================== START - ADD MATERIAL NAME ==================== -->

                                        <div class="tab-pane" id="settingsAddMaterialName">
                                            <form action="" method="POST" enctype="multipart/form-data">
                                                <label class="mb-2">Material Type:</label>
                                                <select class="form-select mb-3" name="selectMaterialType" required>
                                                    <option value="">Choose a Material Type...</option>
                                                    <?php 
                                                        $query = "SELECT * FROM material_type_tbl";
                                                        $result = $database->query($query);
                                                        if($result->num_rows > 0){
                                                            while ($row = $result->fetch_assoc()){
                                                                echo "<option value='{$row["ID"]}'>{$row['m_type']}</option>";
                                                            }
                                                        }
                                                        else {
                                                            echo "<option value=''>No Available Material Type</option>"; 
                                                        }
                                                    ?>
                                                </select>
                                                <label class="mb-2">Material Name:</label>
                                                <input type="text" class="form-control mb-3" placeholder="Enter New Material Name..." name="inputNewMaterialName" required>
                                                <label class="mb-2">Material Image:</label>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="inputNewMaterialImage" name="inputNewMaterialImage" required>
                                                    <button class="btn btn-primary" type="submit" id="btnSettingsNewMaterialNameSave" name="btnSettingsNewMaterialNameSave">Add New Item</button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- ==================== START - ADD MATERIAL NAME ==================== -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-12 col-lg-6">
                    <div class="card">
                        <h5 class="card-header bg-primary text-light">Modify Existing Materials</h5>
                        <div class="card-body">
                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary active" href="#settingsModifyMaterialType" data-bs-toggle="tab">Modify Material Type</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary" href="#settingsModifyMaterialName" data-bs-toggle="tab">Modify Material Item</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">

                                        <!-- ==================== START - EDIT MATERIAL TYPE ==================== -->

                                        <div class="tab-pane active" id="settingsModifyMaterialType">
                                            <table class="datatable-asc-1 table table-hover responsive nowrap text-center w-100">
                                                <thead class="bg-primary text-light">
                                                    <th>Material Type</th>
                                                    <th>Photo</th>
                                                    <th class="no-sort">Action</th>
                                                </thead>
                                                <tbody>
                                                    <?php while($fetch = $materialTypesFetch->fetch_array()){ ?>
                                                        <tr class="align-middle">
                                                            <td><?php echo $fetch['m_type']?></td>
                                                            <td><img src="<?php echo "images/types/".$fetch['m_type_photo']?>" alt="" class="rounded" style="object-fit: cover; height: 50px; width: 50px;"></td>
                                                            <td>

                                                                <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->

                                                                <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialTypeEditConfirmation<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialTypeDeleteConfirmation<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"></i>
                                                                </button>

                                                                <!-- ==================== END - ACTION BUTTONS COLUMN ==================== -->

                                                            </td>
                                                            <!-- ==================== START - MODAL EDIT DATA ==================== -->
                                                                
                                                            <div class="modal fade" id="modalMaterialTypeEditConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="false">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST" enctype="multipart/form-data">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Edit <?php echo $fetch['m_type'] ?></h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="inputHiddenEditMaterialTypeID" value="<?php echo $fetch['ID']?>"/>
                                                                                <div class="row mb-3">
                                                                                    <div class="col">
                                                                                        <label for="inputEditMaterialType" class="mb-2">New Material Type</label>
                                                                                        <input type="text" class="form-control" id="inputEditMaterialType" name="inputEditMaterialType" value="<?php echo $fetch['m_type']?>" required>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="row">
                                                                                    <div class="col">
                                                                                        <label for="inputEditMaterialTypeImage" class="mb-2">New Material Type Photo</label>
                                                                                        <input type="file" class="form-control" id="inputEditMaterialTypeImage" name="inputEditMaterialTypeImage" <?php if ($fetch['m_type_photo'] == '') {echo "required";}?>>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" class="btn btn-success" name="mTypeEdit" id="mTypeEdit">Update</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- ==================== END - MODAL EDIT DATA ==================== -->

                                                            <!-- ==================== START - MODAL DELETE DATA ==================== -->
                                                                
                                                            <div class="modal fade" id="modalMaterialTypeDeleteConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Delete "<?php echo $fetch['m_type']?>" Material?</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="inputHiddenDeleteMaterialTypeID" value="<?php echo $fetch['ID']?>"/>
                                                                                <input type="hidden" name="inputHiddenDeleteMaterialType" value="<?php echo $fetch['m_type']?>"/>
                                                                                Are you sure you want to delete this material type? You can't revert this change.
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, cancel!</button>
                                                                                <button type="submit" class="btn btn-success" name="mTypeDelete" id="mTypeDelete">Yes, delete it!</button>
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

                                        <!-- ==================== END - EDIT MATERIAL TYPE ==================== -->

                                        <!-- ==================== START - EDIT MATERIAL NAME ==================== -->

                                        <div class="tab-pane" id="settingsModifyMaterialName">
                                            <table class="datatable-asc-1 table table-hover responsive nowrap text-center w-100">
                                                <thead class="bg-primary text-light">
                                                    <th>Material Name</th>
                                                    <th>Material Type</th>
                                                    <th>Photo</th>
                                                    <th class="no-sort">Action</th>
                                                </thead>
                                                <tbody class="table-primary">
                                                    <?php while($fetch = $materialNamesFetch->fetch_array()){ ?>
                                                        <tr class="align-middle">
                                                            <td><?php echo $fetch['m_name']?></td>
                                                            <td><?php echo $fetch['m_type']?></td>
                                                            <td><img src="<?php echo "images/items/".$fetch['m_photo']?>" alt="" class="rounded" style="object-fit: cover; height: 50px; width: 50px;"></td>
                                                            <td>

                                                                <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->

                                                                <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialNameEditConfirmation<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalMaterialNameDeleteConfirmation<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"></i>
                                                                </button>

                                                                <!-- ==================== END - ACTION BUTTONS COLUMN ==================== -->

                                                            </td>
                                                            <!-- ==================== START - MODAL EDIT DATA ==================== -->
                                                                
                                                            <div class="modal fade" id="modalMaterialNameEditConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="false">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST" enctype="multipart/form-data">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Edit <?php echo $fetch['m_name'] ?></h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="inputHiddenEditMaterialNameID" value="<?php echo $fetch['ID']?>"/>
                                                                                <input type="hidden" name="inputHiddenOldMaterialName" value="<?php echo $fetch['m_name']?>"/>

                                                                                <div class="row">
                                                                                    <div class="col">
                                                                                        <label for="inputEditMaterialName" class="mb-2">New Material Name</label>
                                                                                        <input type="text" class="form-control mb-3" id="inputEditMaterialName" name="inputEditMaterialName" value="<?php echo $fetch['m_name']?>" required>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="row">
                                                                                    <div class="col">
                                                                                        <label for="inputEditMaterialImage" class="mb-2">New Material Photo</label>
                                                                                        <input type="file" class="form-control" id="inputEditMaterialImage" name="inputEditMaterialImage" <?php if ($fetch['m_photo'] == '') {echo "required";}?>>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" class="btn btn-success" name="mNameEdit" id="mNameEdit">Update</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- ==================== END - MODAL RENAME DATA ==================== -->

                                                            <!-- ==================== START - MODAL DELETE DATA ==================== -->
                                                                
                                                            <div class="modal fade" id="modalMaterialNameDeleteConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Delete "<?php echo $fetch['m_name']?>" Material?</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="inputHiddenDeleteMaterialNameID" value="<?php echo $fetch['ID']?>"/>
                                                                                <input type="hidden" name="inputHiddenDeleteMaterialName" value="<?php echo $fetch['m_name']?>"/>
                                                                                <input type="hidden" name="inputHiddenDeleteMaterialPhoto" value="<?php echo $fetch['m_photo']?>"/>
                                                                                Are you sure you want to delete this material item? You can't revert this change.
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, cancel!</button>
                                                                                <button type="submit" class="btn btn-success" name="mNameDelete" id="mNameDelete">Yes, delete it!</button>
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

                                        <!-- ==================== START - EDIT MATERIAL NAME ==================== -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <div class="row">
                <div class="col-sm-12 col-lg-6">
                    <div class="card">
                        <h5 class="card-header bg-primary text-light">Add New Products</h5>
                        <div class="card-body">
                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary active" href="#settingsAddProduct" data-bs-toggle="tab">Add Product</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary" href="#settingsAddProductRequirements" data-bs-toggle="tab">Add Product Requirements</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">

                                        <!-- ==================== START - ADD PRODUCT ==================== -->

                                        <div class="tab-pane active" id="settingsAddProduct">
                                            <form action="" method="POST" enctype="multipart/form-data">
                                                <label class="mb-2">New Product Name:</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control" placeholder="Enter New Product Name..." name="inputNewProductName" required>
                                                </div>
                                                <label class="mb-2">Product Image:</label>
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="inputNewProductImage" name="inputNewProductImage" required>
                                                    <button class="btn btn-primary" type="submit" id="btnSettingsNewProductSave" name="btnSettingsNewProductSave">Add New Product</button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- ==================== END - ADD PRODUCT ==================== -->

                                        <!-- ==================== START - ADD PRODUCT REQUIREMENTS ==================== -->

                                        <div class="tab-pane" id="settingsAddProductRequirements">
                                            <form action="" method="POST">
                                                <label class="mb-2">Product Name:</label>
                                                <select class="form-select mb-3" name="selectProductName" required>
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
                                                <label class="mb-2">Required Materials:</label>
                                                <div id="requiredMaterialsContainer" class="input-group">
                                                    <select id="selectRequiredMaterial" name="selectRequiredMaterial" class="form-select mb-3 w-50" required>
                                                        <option value="">Choose a Material...</option>
                                                        <?php 
                                                            $query = "SELECT * FROM material_name_tbl";
                                                            $result = $database->query($query);
                                                            if($result->num_rows > 0){
                                                                while ($row = $result->fetch_assoc()){
                                                                    echo "<option value='{$row["ID"]}'>{$row['m_name']}</option>";
                                                                }
                                                            }
                                                            else {
                                                                echo "<option value=''>No Available Material</option>"; 
                                                            }
                                                        ?>
                                                    </select>
                                                    <input type="number" id="inputRequiredMaterialMeasurement" name="inputRequiredMaterialMeasurement" class="form-control mb-3" min="0.01" step="0.01" required>
                                                    <select class="form-select mb-3" id="selectRequiredMaterialUnit" name="selectRequiredMaterialUnit">
                                                        <option value="Piece">Piece</option>
                                                        <option value="Kilogram">Kilogram</option>
                                                        <option value="Meter">Meter</option>
                                                    </select>
                                                </div>
                                                <input type="hidden" id="inputCloneCounter" name="inputCloneCounter" value="">
                                                <div class="d-flex bd-highlight">
                                                    <button type="button" class="btn btn-sm btn-success bd-highlight me-1 p-1" id="btnCloneRequiredMaterial" name="btnCloneRequiredMaterial" onclick="cloneRequiredMaterial()" data-bs-toggle="tooltip" data-bs-placement="top" title="Add A Material Row">
                                                        <i class='bx bx-plus fs-5 d-flex align-items-center'></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger bd-highlight ms-1 p-1" id="btnDeleteRequiredMaterial" name="btnDeleteRequiredMaterial" onclick="deleteRequiredMaterial()" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete A Material Row">
                                                        <i class='bx bx-minus fs-5 d-flex align-items-center'></i>
                                                    </button>
                                                    <button type="submit" class="btn btn-sm btn-primary bd-highlight ms-auto" id="btnSettingsProductRequirementsSave" name="btnSettingsProductRequirementsSave">Save Product Requirements</button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- ==================== END - ADD PRODUCT REQUIREMENTS ==================== -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-12 col-lg-6">
                    <div class="card">
                        <h5 class="card-header bg-primary text-light">Modify Existing Products</h5>
                        <div class="card-body">
                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary active" href="#settingsModifyProductName" data-bs-toggle="tab">Modify Product Name</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link text-dark border-top-primary" href="#settingsModifyProductRequirements" data-bs-toggle="tab">Modify Product Requirements</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">

                                        <!-- ==================== START - EDIT PRODUCT ==================== -->

                                        <div class="tab-pane active" id="settingsModifyProductName">
                                            <table class="datatable-asc-1 table table-hover responsive nowrap text-center w-100">
                                                <thead class="bg-primary text-light">
                                                    <th>Product Name</th>
                                                    <th>Photo</th>
                                                    <th class="no-sort">Action</th>
                                                </thead>
                                                <tbody>
                                                    <?php while($fetch = $productNamesFetch->fetch_array()){ ?>
                                                        <tr class="align-middle">
                                                            <td><?php echo $fetch['p_name']?></td>
                                                            <td><img src="images/items/<?php echo $fetch['p_photo']?>" alt="" class="rounded" style="object-fit: cover; height: 50px; width: 50px;"></td>
                                                            <td>

                                                                <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->

                                                                <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalProductEditConfirmation<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalProductNameDeleteConfirmation<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"></i>
                                                                </button>

                                                                <!-- ==================== END - ACTION BUTTONS COLUMN ==================== -->

                                                            </td>
                                                            <!-- ==================== START - MODAL RENAME DATA ==================== -->
                                                                
                                                            <div class="modal fade" id="modalProductEditConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="false">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST" enctype="multipart/form-data">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Edit <?php echo $fetch['p_name']?></h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="inputHiddenEditProductNameID" value="<?php echo $fetch['ID']?>"/>
                                                                                <input type="hidden" name="inputHiddenOldProductName" value="<?php echo $fetch['p_name']?>"/>
                                                                                <label class="mb-2">New Product Name:</label>
                                                                                <input type="text" class="form-control mb-3" id="inputEditProductName" name="inputEditProductName" value="<?php echo $fetch['p_name']?>" required>
                                                                                <label class="mb-2">New Product Image:</label>
                                                                                <input type="file" class="form-control" id="inputEditProductImage" name="inputEditProductImage" <?php if ($fetch['p_photo'] == '') {echo "required";}?>>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" class="btn btn-success" name="pNameEdit" id="pNameEdit">Update</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- ==================== END - MODAL RENAME DATA ==================== -->

                                                            <!-- ==================== START - MODAL DELETE DATA ==================== -->
                                                                
                                                            <div class="modal fade" id="modalProductNameDeleteConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Delete "<?php echo $fetch['p_name']?>" Product?</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="inputHiddenDeleteProductNameID" value="<?php echo $fetch['ID']?>"/>
                                                                                <input type="hidden" name="inputHiddenDeleteProductName" value="<?php echo $fetch['p_name']?>"/>
                                                                                Are you sure you want to delete this product? You can't revert this change.
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, cancel!</button>
                                                                                <button type="submit" class="btn btn-success" name="pNameDelete" id="pNameDelete">Yes, delete it!</button>
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

                                        <!-- ==================== END - EDIT PRODUCT ==================== -->

                                        <!-- ==================== START - EDIT PRODUCT REQUIREMENTS ==================== -->

                                        <div class="tab-pane" id="settingsModifyProductRequirements">
                                            <table class="datatable-asc-1 table table-hover responsive text-center nowrap w-100">
                                                <thead class="bg-primary text-light">
                                                    <th>Product Name</th>
                                                    <th>Requirements (per Piece)</th>
                                                    <th class="no-sort">Action</th>
                                                </thead>
                                                <tbody>
                                                    <?php while($fetch = $productRequirementsFetch->fetch_array()){ ?>
                                                        <tr class="align-middle">
                                                            <td><?php echo $fetch['p_name']?></td>
                                                            <td><?php 
                                                                $pNameIDContainer = $fetch['ID'];
                                                                $pRequirements = "SELECT material_name_tbl.m_name, product_requirement_tbl.m_measurement, product_requirement_tbl.m_unit
                                                                                  FROM product_requirement_tbl
                                                                                  INNER JOIN material_name_tbl ON product_requirement_tbl.m_name = material_name_tbl.ID
                                                                                  INNER JOIN product_name_tbl ON product_requirement_tbl.p_name = product_name_tbl.ID
                                                                                  WHERE product_name_tbl.ID = '$pNameIDContainer'";
                                                                $pRequirementsResult = $database->query($pRequirements);
                                                                if ($pRequirementsResult->num_rows > 0) {
                                                                    while ($pRequirementsRow = $pRequirementsResult->fetch_assoc()) {
                                                                        echo "<span class='bi bi-arrow-right-short'></span> {$pRequirementsRow['m_measurement']} {$pRequirementsRow['m_unit']}";
                                                                        
                                                                        if ($pRequirementsRow['m_measurement'] > 1) {
                                                                            echo "s";
                                                                        }

                                                                        echo " - {$pRequirementsRow['m_name']}<br>";
                                                                    }
                                                                }
                                                                else {
                                                                    echo "No Materials Required Yet";
                                                                }
                                                                
                                                            ?></td>
                                                            <td>

                                                                <!-- ==================== START - ACTION BUTTONS COLUMN ==================== -->

                                                                <button class="btn btn-sm btn-success p-1" data-bs-toggle="modal" data-bs-target="#modalProductRequirementsEditConfirmation<?php echo $fetch['ID']?>">
                                                                    <i class='bx bx-edit fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"></i>
                                                                </button>

                                                                <!-- ==================== END - ACTION BUTTONS COLUMN ==================== -->

                                                            </td>
                                                            <!-- ==================== START - MODAL PRODUCT REQUIREMENT EDIT ==================== -->
                                                                
                                                            <div class="modal fade" id="modalProductRequirementsEditConfirmation<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="false">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <form action="" method="POST">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Edit "<?php echo $fetch['p_name'] ?>" Requirements</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <?php
                                                                                    $pNameIDEditContainer = $fetch['ID'];
                                                                                    $pRequirementsEdit = "SELECT material_name_tbl.m_name, product_requirement_tbl.m_measurement, product_requirement_tbl.m_unit, product_requirement_tbl.ID
                                                                                                      FROM product_requirement_tbl
                                                                                                      INNER JOIN material_name_tbl ON product_requirement_tbl.m_name = material_name_tbl.ID
                                                                                                      INNER JOIN product_name_tbl ON product_requirement_tbl.p_name = product_name_tbl.ID
                                                                                                      WHERE product_name_tbl.ID = '$pNameIDEditContainer'";
                                                                                    $pRequirementsEditResult = $database->query($pRequirementsEdit);
                                                                                    while ($pRequirementsEditRow = $pRequirementsEditResult->fetch_assoc()) {
                                                                                        echo "
                                                                                            <form action='' method='POST'>
                                                                                                <div class='input-group mb-3'>
                                                                                                    <input type='text' name='inputMaterialName' class='form-control' value='{$pRequirementsEditRow['m_name']}' readonly>
                                                                                                    <input type='hidden' name='inputHiddenProductName' value='{$fetch['p_name']}'/>
                                                                                                    <input type='hidden' name='inputHiddenSingleProductRequirementID' value='{$pRequirementsEditRow['ID']}'/>
                                                                                                    <input type='text' name='inputSingleProductRequirementQuantity' class='form-control' value='{$pRequirementsEditRow['m_measurement']} {$pRequirementsEditRow['m_unit']}";
                                                                                                    if ($pRequirementsEditRow['m_measurement'] > 1) {
                                                                                                        echo "s";
                                                                                                    }
                                                                                                    echo "' readonly>
                                                                                                    <button class='btn btn-danger' id='btnDeleteSingleProductRequirement' name='btnDeleteSingleProductRequirement' data-bs-toggle='tooltip' data-bs-placement='right' title='Remove'>
                                                                                                        <i class='bx bx-trash fs-6'></i>
                                                                                                    </button>
                                                                                                </div>
                                                                                            </form>
                                                                                        ";
                                                                                    }
                                                                                ?>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- ==================== END - MODAL PRODUCT REQUIREMENT EDIT ==================== -->

                                                        </tr>
                                                    <?php }?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- ==================== END - EDIT PRODUCT REQUIREMENTS ==================== -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== END - SETTINGS ROW ==================== -->
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