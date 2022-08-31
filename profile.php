<?php 
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    if(!$_SESSION['signedIn']){
        header("Location: signin.php");
    }

    $activePage = "profile";

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    // ==================== START - QUERIES ====================

    $adminID = $_SESSION['ID'];
    $adminData = $database->query("SELECT * FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();
    $maxStorage = $adminData['max_storage'];
    $materialsDeleted = $adminData['total_materials_deleted'];
    $productsDeleted = $adminData['total_products_deleted'];


    if ($_SESSION['position'] == 'Master Admin') {
        $adminDataQuery = $database->query("SELECT * FROM admins_tbl");
    }
    else {
        $adminDataQuery = $database->query("SELECT * FROM admins_tbl WHERE position != 'Master Admin'");
    }
    

    date_default_timezone_set('Asia/Manila');

    // ==================== END - QUERIES ====================

    // ==================== START - UPDATE BASIC INFORMATION ====================

    if(isset($_POST['btnAdminSaveInformation'])){
        $aID = $_POST['inputHiddenAdminID'];
        $aFirstName = mysqli_real_escape_string($database, $_POST['adminNewFirstname']);
        $aLastName = mysqli_real_escape_string($database, $_POST['adminNewLastname']);
        $aContactNumber = $_POST['adminNewContactNumber'];
        $aEmailAddress = mysqli_real_escape_string($database, $_POST['adminNewEmailAddress']);
        $aStreetAddress = mysqli_real_escape_string($database, $_POST['adminNewStreetAddress']);
        $aTown = mysqli_real_escape_string($database, $_POST['adminNewTown']);
        $aCity = mysqli_real_escape_string($database, $_POST['adminNewCity']);
        $aProvince = mysqli_real_escape_string($database, $_POST['adminNewProvince']);

        $aPasswordVerification = $_POST['inputAccountVerification'];

        if ($aPasswordVerification == $adminData['password']) {
            $adminsTableQuery = "UPDATE `admins_tbl` SET `first_name`='$aFirstName',`last_name`='$aLastName',`contact_num`='$aContactNumber',`email_add`='$aEmailAddress',`street_address`='$aStreetAddress',`town`='$aTown',`city`='$aCity',`province`='$aProvince' WHERE `ID`='$aID'";
            $database->query($adminsTableQuery) or die ($database->error);
    
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Information Updated!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                </script>
            ";
            header("Refresh:2; url=profile.php");
        }
        else {
            $_SESSION['message']= "
                <script>
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'Verification Error',
                        text: 'Verification password does not match current password',
                        showConfirmButton: false,
                        timer: 2000
                    });
                </script>
            ";
        }
    }

    // ==================== END - UPDATE BASIC INFORMATION ====================

    // ==================== START - CHANGE ACCOUNT PASSWORD ====================

    if(isset($_POST['btnAdminSaveChangePassword'])){
        $aID = $_POST['inputHiddenAdminID'];
        $aCurrentPassword = md5($_POST['adminCurrentPassword']);
        $aNewPassword = md5($_POST['adminNewPassword']);
        $aConfirmNewPassword = md5($_POST['adminConfirmNewPassword']);

        if ($aCurrentPassword == $adminData['password']) {
            if ($aNewPassword == $aConfirmNewPassword) {
                $adminsTableQuery = "UPDATE `admins_tbl` SET `password`='$aNewPassword' WHERE `ID`='$aID'";
                $database->query($adminsTableQuery) or die ($database->error);
    
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Account Password Updated!',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    </script>
                ";
                header("Refresh:2; url=profile.php");
            }
            else {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Unable to Change Account Password',
                            text: 'Your new password does not match the confirmation password.',
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
                        title: 'Unable to Change Account Password',
                        text: 'Your current password does not match with the one you have entered.',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
    }

    // ==================== END - CHANGE ACCOUNT PASSWORD ====================

    // ==================== START - CHANGE ACCOUNT PHOTO ====================

    if(isset($_POST['btnUploadProfilePhoto'])){

        $aID = $_POST['inputHiddenAdminID'];
        $imgFolder = "images/profiles/";
        $imgName = basename($_FILES["inputAdminNewProfilePhoto"]["name"]);
        $imgDirectory = $imgFolder.$imgName;
        $imgType = pathinfo($imgDirectory,PATHINFO_EXTENSION);
        $imgValidExtension = array('jpg','png','jpeg');

        if(in_array($imgType, $imgValidExtension)){
            if ($_FILES['inputAdminNewProfilePhoto']['size'] < 1000000) {
                if (move_uploaded_file($_FILES['inputAdminNewProfilePhoto']['tmp_name'], $imgDirectory)) {
    
                    $sqlAdminsTable = "UPDATE `admins_tbl` SET `photo`='$imgName' WHERE `ID` = $aID";
                    $database->query($sqlAdminsTable) or die ($database->error);
            
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Update Successful!',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        </script>
                    ";
                    header("Refresh:2; url=profile.php");

                } 
                else {
                    $_SESSION['message']= "
                        <script>
                            Swal.fire({
                                position: 'center',
                                icon: 'error',
                                title: 'Unable to Update Profile Picture',
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
                            text: 'Your profile picture should not exceed 1 Megabyte',
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
                        text: 'Your profile picture should only be in .jpg, .png or .jpeg format',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
    }

    // ==================== END - CHANGE PROFILE PHOTO ====================

    // ==================== START - CHANGE ACCOUNT PHOTO ====================

    if(isset($_POST['btnAddNewAccount'])){
        $newAdminFirstname = mysqli_real_escape_string($database, $_POST['inputNewAdminFirstName']);
        $newAdminLastname = mysqli_real_escape_string($database, $_POST['inputNewAdminLastName']);
        $newAdminCompanyID = $_POST['inputNewAdminCompanyID'];
        $newAdminPosition = $_POST['selectNewAdminPosition'];
        $newAdminContactNumber = $_POST['inputNewAdminContactNumber'];
        $newAdminEmailAddress = mysqli_real_escape_string($database, $_POST['inputNewAdminEmailAddress']);

        $newAdminStreetAddress = mysqli_real_escape_string($database, $_POST['inputNewAdminStreetAddress']);
        $newAdminTown = mysqli_real_escape_string($database, $_POST['inputNewAdminTown']);
        $newAdminCity = mysqli_real_escape_string($database, $_POST['inputNewAdminCity']);
        $newAdminProvince = mysqli_real_escape_string($database, $_POST['inputNewAdminProvince']);

        $newAdminUsername = $_POST['inputNewAdminUsername'];
        $newAdminPassword = md5($_POST['inputNewAdminPassword']);

        $newAdminCreated = date("Y-m-d H:i:s");

        $imgFolder = "images/profiles/";
        $imgName = basename($_FILES["inputNewAdminProfilePhoto"]["name"]);
        $imgDirectory = $imgFolder.$imgName;
        $imgType = pathinfo($imgDirectory,PATHINFO_EXTENSION);
        $imgValidExtension = array('jpg','png','jpeg');

        $adminNameCloneCounter = 0;
        $adminTableCheckerQuery = $database->query("SELECT * FROM admins_tbl");

        while ($fetch = $adminTableCheckerQuery->fetch_assoc()) {
            if ($fetch['user_name'] == $newAdminUsername) {
                $adminNameCloneCounter++;
            }
        }

        if ($adminNameCloneCounter == 0) {
            if(in_array($imgType, $imgValidExtension)){
                if ($_FILES['inputNewAdminProfilePhoto']['size'] < 1000000) {
                    if (move_uploaded_file($_FILES['inputNewAdminProfilePhoto']['tmp_name'], $imgDirectory)) {
        
                        $sqlAdminsTable = "INSERT INTO `admins_tbl`(`ID`, `photo`, `company_id`, `first_name`, `last_name`, `position`, `contact_num`, `email_add`, `street_address`, `town`, `city`, `province`, `user_name`, `password`, `account_created`, `max_storage`, `total_materials_deleted`, `total_products_deleted`, `attempts`, `last_attempt_time`) VALUES (NULL,'$imgName','$newAdminCompanyID','$newAdminFirstname','$newAdminLastname','$newAdminPosition','$newAdminContactNumber','$newAdminEmailAddress','$newAdminStreetAddress','$newAdminTown','$newAdminCity','$newAdminProvince','$newAdminUsername','$newAdminPassword','$newAdminCreated','$maxStorage','$materialsDeleted','$productsDeleted','3', '')";
                        $database->query($sqlAdminsTable) or die ($database->error);
                        $database->query("INSERT INTO `accounts_history_tbl`(`ID`, `ah_admin`, `ah_date`, `ah_event`, `ah_method`) VALUES (NULL,'{$_SESSION['ID']}','$newAdminCreated','$newAdminFirstname $newAdminLastname','Created')") or die ($database->error);
                
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'success',
                                    title: 'New Account Saved!',
                                    text: 'An account for $newAdminFirstname $newAdminLastname has been created',
                                    showConfirmButton: false,
                                    timer: 2000
                                });
                            </script>
                        ";
                        header("Refresh:2; url=profile.php");
    
                    } 
                    else {
                        $_SESSION['message']= "
                            <script>
                                Swal.fire({
                                    position: 'center',
                                    icon: 'error',
                                    title: 'Unable to Update Profile Picture',
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
                                text: 'Your profile picture should not exceed 1 Megabyte',
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
                            text: 'Your profile picture should only be in .jpg, .png or .jpeg format',
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
                        title: 'Username Exist',
                        text: 'The Username $newAdminUsername already exist in the database',
                        confirmButtonColor: '#007bff',
                    });
                </script>
            ";
        }
    }

    // ==================== END - CHANGE PROFILE PHOTO ====================

    // ==================== START - ACCOUNT DELETION ====================

    if(isset($_POST['btnAccountDelete'])){
        $accountID = $_POST['inputHiddenDeleteAccountID'];
        $accountName = $_POST['inputHiddenDeleteAccountName'];
        $currentTime = date("Y-m-d H:i:s");

        $adminsTable = $database->query("DELETE FROM `admins_tbl` WHERE ID = '$accountID'") or die ($database->error);
        $database->query("INSERT INTO `accounts_history_tbl`(`ID`, `ah_admin`, `ah_date`, `ah_event`, `ah_method`) VALUES (NULL,'{$_SESSION['ID']}','$currentTime','$accountName','Deleted')") or die ($database->error);
        
        $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Account Deleted!',
                    text: 'The account of $accountName has been deleted permanently',
                    showConfirmButton: false,
                    timerProgressBar: true,
                    timer: 2000,
                });
            </script>
        ";
        header("Refresh:2; url=profile.php");
    }

    // ==================== END - ACCOUNT DELETION ====================
    
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
    <title><?php echo $adminData['first_name']." ".$adminData['last_name'];?></title>

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

            <!-- ==================== START - PROFILE TITLE ROW ==================== -->

            <div class="row sticky-top bg-light tab-header-title mb-2">
                <div class="col d-flex align-items-center">
                    <p class="lead m-0 me-auto d-flex align-items-center">
                        <i class='bx bx-menu fs-3 pointer'></i>&emsp;<i class='bx bxs-user-detail fs-3'></i>&emsp;Profiles
                    </p>
                    <button class="btn btn-sm btn-danger d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#modalAccountSignOutConfirmation">
                        <i class='bx bx-log-out fs-5'></i>&nbsp;Sign Out
                    </button>
                </div>
            </div>

            <!-- ==================== END - PROFILE TITLE ROW ==================== -->

            <div class="row">
                <div class="col-sm-12 col-lg-4">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="inputHiddenAdminID" value="<?php echo $adminData['ID']?>"/>
                        <input type="file" class="form-control profile-photo-upload" id="inputAdminNewProfilePhoto" name="inputAdminNewProfilePhoto" hidden>
                        <div class="profile-photo-main">
                            <img id="adminNewProfilePhoto" name="adminNewProfilePhoto" class="rounded shadow" src="images/profiles/<?php echo $adminData['photo'];?>">
                            <div class="profile-photo-overlay rounded text-light fs-4">
                                Change Profile Picture
                            </div>
                        </div>
                        <button type="submit" name="btnUploadProfilePhoto" id="btnUploadProfilePhoto" hidden></button>
                    </form>
                </div>
                <div class="col-sm-12 col-lg-8">
                    <div class="profile-main mt-3">
                        <p class="fs-3 m-0"><?php echo $adminData['first_name']." ".$adminData['last_name'];?></p>
                        <p class="text-primary m-0"><?php echo $adminData['position'];?> </p>
                    </div>
                    <hr>
                    <div>
                        <p><span class="bi bi-person-lines-fill"></span> About</p>
                        <div class="row">
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-5 fw-bold">
                                        <p>Company ID: </p>
                                    </div>
                                    <div class="col">
                                        <p><?php echo $adminData['company_id'];?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-5 fw-bold">
                                        <p>Contact Number: </p>
                                    </div>
                                    <div class="col">
                                        <p><?php echo $adminData['contact_num'];?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-5 fw-bold">
                                        <p>Email Address: </p>
                                    </div>
                                    <div class="col">
                                        <p><?php echo $adminData['email_add'];?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-5 fw-bold">
                                        <p>Address: </p>
                                    </div>
                                    <div class="col">
                                        <p><?php echo $adminData['street_address']." ".$adminData['town']." ".$adminData['city']." ".$adminData['province'];?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-5 fw-bold">
                                        <p>Joined: </p>
                                    </div>
                                    <div class="col">
                                        <p><?php echo $adminData['account_created'];?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>  
            </div>

            <hr class="my-5">
            
            <div class="row">

                <!-- ==================== START - BASIC INFORMATION ==================== -->

                <div class="col-sm-12 col-lg-6">
                    <form action="" method="POST">
                        <input type="hidden" name="inputHiddenAdminID" value="<?php echo $adminData['ID']?>"/>
                        <div class="card mb-3">
                            <h5 class="card-header bg-primary text-white">Basic Information</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Firstname</label>
                                            <input type="text" class="form-control" placeholder="Firstname" name="adminNewFirstname" value="<?php echo $adminData['first_name'];?>" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Lastname</label>
                                            <input type="text" class="form-control" placeholder="Lastname" name="adminNewLastname" value="<?php echo $adminData['last_name'];?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Contact Number</label>
                                            <input type="number" class="form-control" placeholder="Contact Number" name="adminNewContactNumber" value="<?php echo $adminData['contact_num'];?>" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="text" class="form-control" placeholder="Email Address" name="adminNewEmailAddress" value="<?php echo $adminData['email_add'];?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Address</label>
                                    <div class="row">
                                        <div class="col-sm-12 col-lg-6">
                                            <div class="mb-2">
                                                <label class="form-label">Street Address</label>
                                                <input type="text" class="form-control" placeholder="House #/Street" name="adminNewStreetAddress" value="<?php echo $adminData['street_address'];?>" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-lg-6">
                                            <div class="mb-2">
                                                <label class="form-label">Town</label>
                                                <input type="text" class="form-control" placeholder="Town" name="adminNewTown" value="<?php echo $adminData['town'];?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-12 col-lg-6">
                                            <div class="mb-2">
                                                <label class="form-label">City</label>
                                                <input type="text" class="form-control" placeholder="City" name="adminNewCity" value="<?php echo $adminData['city'];?>" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-lg-6">
                                            <div class="mb-2">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-control" placeholder="Province" name="adminNewProvince" value="<?php echo $adminData['province'];?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="d-flex justify-content-end">
                                        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalVerifyAccount">Update Information</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="modalVerifyAccount" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Verify Account User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label for="inputAccountVerification" class="form-label">To verify if it is you, please enter your password...</label>
                                            <input type="password" class="form-control" id="inputAccountVerification" name="inputAccountVerification" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" id="btnAdminSaveInformation" name="btnAdminSaveInformation" class="btn btn-primary w-100">Continue</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ==================== END - BASIC INFORMATION ==================== -->

                <!-- ==================== START - CHANGE ACCOUNT PASSWORD ==================== -->

                <div class="col-sm-12 col-lg-6">
                    <form action="" method="POST">
                        <input type="hidden" name="inputHiddenAdminID" value="<?php echo $adminData['ID']?>"/>
                        <div class="card mb-3">
                            <h5 class="card-header bg-primary text-white">Change Account Password</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" placeholder="Enter Your Current Password" name="adminCurrentPassword" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control password-requirements" placeholder="Enter Your New Password" name="adminNewPassword" id="adminNewPassword" onpaste="return false" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*_=+-]).{10,}$" maxlength="20" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" placeholder="Confirm New Password" name="adminConfirmNewPassword" onpaste="return false" maxlength="20" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="d-flex justify-content-end">
                                        <button class="btn btn-primary btn-sm" type="submit" id="btnAdminSaveChangePassword" name="btnAdminSaveChangePassword">Change Password</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ==================== END - CHANGE ACCOUNT PASSWORD ==================== -->
            </div>
            
            <?php
                if ($_SESSION['position'] != 'User') {
                    ?>
                        <hr class="my-5">

                        <div class="row">
                            <div class="col d-flex bd-highlight">
                                <button class="btn btn-sm btn-primary mb-3 ms-1 ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#modalCreateNewAccount">
                                    <span class="bi bi-plus-lg"></span> Create New Account
                                </button>
                            </div>
                        </div>

                        <!-- ==================== START - CREATE NEW ACCOUNT MODAL ==================== -->

                        <div class="modal fade" id="modalCreateNewAccount" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Create New Account</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="fs-4">Basic Information</label>
                                            <br><br>
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminPhoto" class="form-label">Photo</label>
                                                        <input type="file" class="form-control" id="inputNewAdminProfilePhoto" name="inputNewAdminProfilePhoto" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12 col-lg-6">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminFirstName" class="form-label">Firstname</label>
                                                        <input type="text" class="form-control" id="inputNewAdminFirstName" name="inputNewAdminFirstName" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 col-lg-6">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminLastName" class="form-label">Lastname</label>
                                                        <input type="text" class="form-control" id="inputNewAdminLastName" name="inputNewAdminLastName" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12 col-lg-4">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminCompanyID" class="form-label">Company ID</label>
                                                        <input type="text" class="form-control" id="inputNewAdminCompanyID" name="inputNewAdminCompanyID" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 col-lg-4">
                                                    <div class="mb-2">
                                                        <label for="selectNewAdminPosition" class="form-label">Position</label>
                                                        <select class="form-select" id="selectNewAdminPosition" name="selectNewAdminPosition" required>
                                                            <?php
                                                                if ($_SESSION['position'] == "Master Admin") {
                                                                    ?>
                                                                        <option value='Administrator'>Admin</option>
                                                                        <option value='User'>User</option>
                                                                    <?php
                                                                }
                                                                else {
                                                                    ?>
                                                                        <option value='User'>User</option>
                                                                    <?php
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 col-lg-4">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminContactNumber" class="form-label">Contact Number</label>
                                                        <input type="number" class="form-control" id="inputNewAdminContactNumber" name="inputNewAdminContactNumber" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12 col-lg-6">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminStreetAddress" class="form-label">Street Address</label>
                                                        <input type="text" class="form-control" id="inputNewAdminStreetAddress" name="inputNewAdminStreetAddress" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 col-lg-6">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminTown" class="form-label">Town</label>
                                                        <input type="text" class="form-control" id="inputNewAdminTown" name="inputNewAdminTown" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12 col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="inputNewAdminProvince" class="form-label">Province</label>
                                                        <input type="text" class="form-control" id="inputNewAdminProvince" name="inputNewAdminProvince" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="inputNewAdminCity" class="form-label">City</label>
                                                        <input type="text" class="form-control" id="inputNewAdminCity" name="inputNewAdminCity" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <label class="fs-4">Account Setup</label>
                                            <br><br>
                                            <div class="row">
                                                <div class="col-sm-12 col-lg-6">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminUsername" class="form-label">Username</label>
                                                        <input type="text" class="form-control" id="inputNewAdminUsername" name="inputNewAdminUsername" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 col-lg-6">
                                                    <div class="mb-2">
                                                        <label for="inputNewAdminPassword" class="form-label">Password</label>
                                                        <input type="password" class="form-control password-requirements" id="inputNewAdminPassword" name="inputNewAdminPassword" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*_=+-]).{10,}$" maxlength="20" onpaste="return false" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="mb-3">
                                                        <label for="inputNewAdminEmailAddress" class="form-label">Email Address</label>
                                                        <input type="text" class="form-control" id="inputNewAdminEmailAddress" name="inputNewAdminEmailAddress" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" id="btnAddNewAccount" name="btnAddNewAccount" class="btn btn-success"><span class="bi bi-plus-lg"></span> Create Account</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== END - CREATE NEW ACCOUNT MODAL ==================== -->

                        <div class="row">
                            <div class="col">
                                <div class="card">
                                    <div class="card-body">
                                        <table class="datatable-asc-2 table table-hover responsive nowrap w-100">
                                            <thead class="bg-primary text-white">
                                                <th class="no-sort">Active</th>
                                                <th>Company ID</th>
                                                <th class="no-sort">Photo</th>
                                                <th>Name</th>
                                                <th>Position</th>
                                                <th class="no-sort">Contact Number</th>
                                                <th class="no-sort">Email Address</th>
                                                <th class="no-sort">Address</th>
                                                <?php
                                                    if ($_SESSION['position'] == 'Master Admin') {
                                                        ?>
                                                            <th>Username</th>
                                                            <th>Password</th>
                                                        <?php
                                                    }
                                                ?>
                                                <th>Account Created</th>
                                                <th class="no-sort">Actions</th>
                                            </thead>
                                            <tbody>
                                                <?php while($fetch = $adminDataQuery->fetch_array()){ ?>
                                                    <tr class="align-middle">
                                                        <td class="text-center">
                                                            <?php
                                                                if ($fetch['account_status'] == 'Online') {
                                                                    ?>
                                                                        <i class='bx bxs-circle fs-6 text-success'></i>
                                                                    <?php
                                                                }
                                                                else {
                                                                    ?>
                                                                        <i class='bx bxs-circle fs-6 text-danger'></i>
                                                                    <?php
                                                                }
                                                            ?>
                                                        </td>
                                                        <td><?php echo $fetch['company_id']?></td>
                                                        <td>
                                                            <?php 
                                                                echo "<img src='images/profiles/{$fetch['photo']}' class='rounded me-1' style='height: 50px; width: 50px; object-fit: cover;'>";
                                                            ?>
                                                        </td>
                                                        <td><?php echo $fetch['first_name']." ".$fetch['last_name']?></td>
                                                        <td><?php echo $fetch['position']?></td>
                                                        <td><?php echo $fetch['contact_num']?></td>
                                                        <td><?php echo $fetch['email_add']?></td>
                                                        <td><?php echo $fetch['street_address']." ".$fetch['town']." ".$fetch['city']." ".$fetch['province']?></td>
                                                        <?php
                                                            if ($_SESSION['position'] == 'Master Admin') {
                                                                ?>
                                                                    <td><?php echo $fetch['user_name']?></td>
                                                                    <td><?php echo $fetch['password']?></td>
                                                                <?php
                                                            }
                                                        ?>
                                                        <td><?php echo $fetch['account_created']?></td>
                                                        <td>
                                                            <?php
                                                                if ($fetch['position'] != 'Master Admin' && $_SESSION['position'] == 'Master Admin') {
                                                                    ?>
                                                                       <button class="btn btn-sm btn-danger p-1" data-bs-toggle="modal" data-bs-target="#modalAccountDelete<?php echo $fetch['ID']?>">
                                                                            <i class='bx bx-trash fs-6 d-flex align-items-center' data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Account"></i>
                                                                        </button> 
                                                                    <?php
                                                                }
                                                            ?>      
                                                        </td>
                                                        

                                                        <!-- ==================== START - MODAL DELETE ACCOUNT ==================== -->
                                                        
                                                        <div class="modal fade" id="modalAccountDelete<?php echo $fetch['ID']?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <form action="" method="POST">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Delete Account</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="inputHiddenDeleteAccountID" value="<?php echo $fetch['ID']?>"/>
                                                                            <input type="hidden" name="inputHiddenDeleteAccountName" value="<?php echo $fetch['first_name']." ".$fetch['last_name']?>"/>
                                                                            <label class="form-label">Are you sure that you want to delete the account of <?php echo $fetch['first_name']." ".$fetch['last_name'];?>? This action cannot be reversed.</label>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Wait</button>
                                                                            <button type="submit" class="btn btn-success" name="btnAccountDelete" id="btnAccountDelete">Yes, Delete It!</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- ==================== END - MODAL DELETE ACCOUNT ==================== -->
                                                    </tr>
                                                <?php }?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            ?>
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