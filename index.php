<?php
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    // ==================== END - DATABASE CONNECTION ====================

    date_default_timezone_set('Asia/Manila');
   
    // ==================== START - SIGNING IN ====================

    if(isset($_POST['btnSignIn'])){
        $inputUsername = $_POST['inputUsername'];
        $inputPassword = md5($_POST['inputPassword']);
        $lastAttemptTime = date("Y-m-d H:i:s");

        $accountChecker = 0;

        if (empty($inputUsername) && empty($inputPassword)) {
            $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Input Fields Are Empty!',
                    text: 'Please enter your Username and Password',
                });
            </script>
            ";
        }
        else if (!empty($inputUsername) && empty($inputPassword)) {
            $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Password is Empty',
                    text: 'Please enter your Password first ".$inputUsername."',
                });
            </script>
            ";
        }
        else if (empty($inputUsername) && !empty($inputPassword)) {
            $_SESSION['message']= "
            <script>
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Username is Empty',
                    text: 'Please enter your Username first!',
                });
            </script>
            ";
        }
        else {    
            $adminsTableQuery = $database->query("SELECT * FROM admins_tbl");
            while ($adminsTableRow = $adminsTableQuery->fetch_assoc()) {
                if ($adminsTableRow['user_name'] == $inputUsername) {
                    $accountChecker = $accountChecker + 1;
                    if ($adminsTableRow['attempts'] <= 0) {
                        $lastAttempt = new DateTime($adminsTableRow['last_attempt_time']);
                        $currentTime = $lastAttempt->diff(new DateTime(date("F j, Y, g:i:s a")));
                        $seconds = $currentTime->days * 24 * 60;
                        $seconds += $currentTime->h * 60;
                        $seconds += $currentTime->i * 60;
                        $seconds += $currentTime->s - 120;
                        $seconds = $seconds * -1;

                        if ($seconds >= 0) {
                            $_SESSION['message']= "
                                <script>
                                    Swal.fire({
                                        position: 'center',
                                        icon: 'error',
                                        title: 'Account Locked',
                                        text: 'Your account has been locked. Please try again in $seconds Second(s)',
                                    });
                                </script>
                            ";
                        }
                        else {
                            if ($adminsTableRow['password'] == $inputPassword) {
                                $adminsTableAttemptsQuery = $database->query("UPDATE `admins_tbl` SET `attempts` = '3', `last_attempt_time` = '$lastAttemptTime', `account_status` = 'Online' WHERE `user_name` = '$inputUsername'"); 
                                $database->query("INSERT INTO `accounts_history_tbl`(`ID`, `ah_admin`, `ah_date`, `ah_event`, `ah_method`) VALUES (NULL,'{$adminsTableRow['ID']}','$lastAttemptTime','Signed In','In')") or die ($database->error);
            
                                $_SESSION['ID'] = $adminsTableRow['ID'];
                                $_SESSION['name'] = $adminsTableRow['first_name']." ".$adminsTableRow['last_name'];
                                $_SESSION['firstname'] = $adminsTableRow['first_name'];
                                $_SESSION['position'] = $adminsTableRow['position'];
                                $_SESSION['signedIn'] = 'true';
                                $_SESSION['status'] = 'Online';
                                $_SESSION['message'] = "
                                    <script>
                                        Swal.fire({
                                            position: 'center',
                                            icon: 'success',
                                            title: 'Sign In Successful',
                                            text: 'Hello {$adminsTableRow['first_name']}',
                                            showConfirmButton: false,
                                            timer: 2000
                                        });
                                    </script>
                                ";
                                header("Refresh:2; url=dashboard.php");
                            }
                            else {
                                $attemptsCounter = 2;
                                $adminsTableAttemptsQuery = $database->query("UPDATE `admins_tbl` SET `attempts`='$attemptsCounter', `last_attempt_time` = '$lastAttemptTime' WHERE `user_name` = '$inputUsername'"); 
        
                                $_SESSION['message']= "
                                    <script>
                                        Swal.fire({
                                            position: 'center',
                                            icon: 'error',
                                            title: 'Incorrect Password',
                                            text: 'You have entered a wrong password {$adminsTableRow['user_name']}. You only have $attemptsCounter attempts left.',
                                        });
                                    </script>
                                ";
                            }
                        }
                    }
                    else {
                        if ($adminsTableRow['password'] == $inputPassword) {
                            $adminsTableAttemptsQuery = $database->query("UPDATE `admins_tbl` SET `attempts` = '3', `last_attempt_time` = '$lastAttemptTime', `account_status` = 'Online' WHERE `user_name` = '$inputUsername'"); 
                            $database->query("INSERT INTO `accounts_history_tbl`(`ID`, `ah_admin`, `ah_date`, `ah_event`, `ah_method`) VALUES (NULL,'{$adminsTableRow['ID']}','$lastAttemptTime','Signed In','In')") or die ($database->error);
    
                            $_SESSION['ID'] = $adminsTableRow['ID'];
                            $_SESSION['name'] = $adminsTableRow['first_name']." ".$adminsTableRow['last_name'];
                            $_SESSION['firstname'] = $adminsTableRow['first_name'];
                            $_SESSION['position'] = $adminsTableRow['position'];
                            $_SESSION['signedIn'] = 'true';
                            $_SESSION['status'] = 'Online';
                            $_SESSION['message'] = "
                                <script>
                                    Swal.fire({
                                        position: 'center',
                                        icon: 'success',
                                        title: 'Sign In Successful',
                                        text: 'Hello {$adminsTableRow['first_name']}',
                                        showConfirmButton: false,
                                        timer: 2000
                                    });
                                </script>
                            ";
                            header("Refresh:2; url=dashboard.php");
                        }
                        else {
                            $attemptsCounter = $adminsTableRow['attempts'] - 1;
                            $adminsTableAttemptsQuery = $database->query("UPDATE `admins_tbl` SET `attempts`='$attemptsCounter', `last_attempt_time` = '$lastAttemptTime' WHERE `user_name` = '$inputUsername'"); 
    
                            $_SESSION['message']= "
                                <script>
                                    Swal.fire({
                                        position: 'center',
                                        icon: 'error',
                                        title: 'Incorrect Password',
                                        text: 'You have entered a wrong password {$adminsTableRow['user_name']}. You only have $attemptsCounter attempts left.',
                                    });
                                </script>
                            ";
                        }
                    }
                }
                else {
                    $accountChecker = $accountChecker;
                }
            }

            if ($accountChecker == 0) {
                $_SESSION['message']= "
                    <script>
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Unknown Credentials',
                            text: 'The username $inputUsername is not registered in our database.',
                        });
                    </script>
                ";
            }
        }
    }

    // ==================== END - SIGNING IN ====================
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/JJ Logo.png">
    <title>Sign In</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.css"/>

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="body-login">

    <!-- ==================== START - LOGIN CONTENT ==================== -->

    <div class="container-fluid login-container">
        <?php 
            if(isset($_SESSION['message'])){
                echo $_SESSION['message'];
                unset($_SESSION['message']);
            }
        ?>

        <div class="card shadow">
            <div class="p-5 d-flex justify-content-center align-items-center" style="background-color: #a9e8fc;">
                <img src='images/JJ Logo.png' style='height: 80px; width: 80px;'>
                <div class="px-3">
                    <span style='font-size:2rem'>JJ Fabrication</span>
                    <br>
                    <span style='font-size:1.1rem'>Material Management System</span>
                </div>
            </div>
            
            <div id="containerLogin" class="card-body p-5">
                <form action="" method="POST">
                    <div class="form-floating mb-4">
                        <input type="text" class="form-control input-username" name="inputUsername" id="inputUsername" placeholder="Username">
                        <label for="inputUsername">Username</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control input-password" name="inputPassword" id="inputPassword" placeholder="Password">
                        <label for="inputPassword">Password</label>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <a href="forgot-password.php">
                                <button id="btnForgotPassword" type="button" class="btn w-100 button-signin fw-bold">Forgot Password?</button>
                            </a>
                            
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn w-100 button-signin fw-bold" id="btnSignIn" name="btnSignIn" style="background-color: #a9e8fc;">Sign In</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ==================== END - LOGIN CONTENT ==================== -->

    <script src="js/jquery.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="js/PassRequirements.js"></script>
    <script src="js/script.js"></script>
</body>
</html>