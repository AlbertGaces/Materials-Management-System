<?php
    // ==================== START - SESSION INITIALIZATION ====================

    if(!isset($_SESSION)){
        session_start();
    }
    $error = array();

    // ==================== END - SESSION INITIALIZATION ====================

    // ==================== START - DATABASE CONNECTION ====================

    include "include/dbconnect.php";

    require "include/mailer.php";

    // ==================== END - DATABASE CONNECTION ====================

    date_default_timezone_set('Asia/Manila');
   
    // ==================== START - FORGOT PASSWORD ====================

    $forgotPasswordMethods = "username-check";
	if(isset($_GET['forgotPasswordMethods'])){
		$forgotPasswordMethods = $_GET['forgotPasswordMethods'];
	}

	if(count($_POST) > 0){
		switch ($forgotPasswordMethods) {
			case 'username-check':
                $username = $_POST['inputUsernameForgotPassword'];
				if(!username_check($username)){
					$error[] = "We can't recognize that username";
				}
				else{
					$_SESSION['forgot']['username'] = $username;
					send_verification($username);
					header("Location: forgot-password.php?forgotPasswordMethods=code-check");
					die;
				}
				break;

			case 'code-check':
				$code = $_POST['inputCodeForgotPassword'];
				$result = code_verification($code);

                if ($result == '') {
                    $error[] = 'Please input verification code';
                }
                else if ($result == 'The verification code matched!') {
                    $_SESSION['forgot']['code'] = $code;
                    header("Location: forgot-password.php?forgotPasswordMethods=change-password");
                }
				else {
					$error[] = $result;
				}
				break;

			case 'change-password':
				$newPassword = md5($_POST['inputNewPassword']);
				$newPassword2 = md5($_POST['inputNewPassword2']);

				if($newPassword !== $newPassword2){
					$error[] = "Passwords does not match!";
				}
				else if(!isset($_SESSION['forgot']['username']) || !isset($_SESSION['forgot']['code'])){
					header("Location: signin.php");
					die;
				}
				else{
					save_password($newPassword);
					if(isset($_SESSION['forgot'])){
						unset($_SESSION['forgot']);
					}
					header("Location: signin.php");
					die;
				}
				break;
			
			default:
				break;
		}
	}

    if(isset($_POST['btnFPCodeResend'])){
        $username = $_SESSION['forgot']['username'];
		send_verification($username);
	}

    function send_verification($username){
		global $database;

        $currentTime = date("Y-m-d H:i:s");
		$codeExpiration = time() + (60 * 5);
		$code = rand(100000,999999);
        $username = addslashes($username);
        $adminsTable = $database->query("SELECT * FROM admins_tbl WHERE user_name = '$username'")->fetch_assoc();
        $emailAddress = addslashes($adminsTable['email_add']);

        $verificationsTable = $database->query("INSERT INTO `verifications_tbl`(`ID`, `v_user_name`, `v_email_add`, `v_code`, `v_time`, `v_date`) VALUES (NULL,'$username','$emailAddress','$code','$codeExpiration','$currentTime')");

		send_mail($emailAddress, $code." is your JJ Fabrication MMS verification code","
		<h3 style='color:rgb(0,0,0);font-size:15px'>
			Hello!
		</h3>
		<p style='color:rgb(0,0,0);font-size:15px'>
			You are currently trying to retrieve your account, Your verification code is: 
			<strong style='color:rgb(78,164,220);font-size:15px'>
				".$code."
			</strong>
			<br><br>
			Please complete the account verification process in 5 minutes.
			<br><br>
			JJ Fabrication MMS
		</p>
		<br>
		<span style='color:rgb(119,119,119);font-size:13px'>
			This is an automated email. Please do not reply to this email.
		</span>
		");
	}
	
	function save_password($newPassword){
		global $database;

        $username = addslashes($_SESSION['forgot']['username']);
        $adminsTable = $database->query("UPDATE `admins_tbl` SET `password`='$newPassword' WHERE user_name = '$username'");
	}
	
	function username_check($username){
		global $database;
		$username = addslashes($username);
        $adminsTable = $database->query("SELECT * FROM admins_tbl WHERE user_name = '$username' LIMIT 1");
        if ($adminsTable->num_rows > 0) {
            return true;
        }
        return false;
	}

	function code_verification($code){
		global $database;

		$code = addslashes($code);
		$codeExpiration = time();
        $username = addslashes($_SESSION['forgot']['username']);

        $verificationsTable = $database->query("SELECT * FROM verifications_tbl WHERE v_user_name = '$username' && v_code = '$code' LIMIT 1");
        if ($verificationsTable->num_rows > 0) {
            while ($verificationsTableRow = $verificationsTable->fetch_assoc()) {
                if ($verificationsTableRow['v_time'] > $codeExpiration) {
                    return "The verification code matched!";
                }
                else {
                    return "The verification code has expired!";
                }
            }
        }
        else {
            return "The verification code does not match!";
        }
        return "The verification code does not match!";
	}

    // ==================== END - FORGOT PASSWORD ====================

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
            
            <div id="containerForgotPassword" class="card-body p-5">
                <?php 
                    switch ($forgotPasswordMethods) {
                        case 'username-check':
                            ?>
                                <form action="forgot-password.php?forgotPasswordMethods=username-check" method="POST">
                                    <label for="" class="mb-2 fs-4">Find your account.</label>
                                    <label for="" class="mb-3">Enter your username below.</label>
                                    <div class="form-floating mb-2">
                                        <input type="text" class="form-control input-username" name="inputUsernameForgotPassword" id="inputUsernameForgotPassword" placeholder="Username" required>
                                        <label for="inputUsername">Username</label>
                                    </div>
                                    <p for="" class="text-center text-danger">
                                        <?php 
                                            foreach ($error as $err) {
                                                echo $err . "<br>";
                                            }
                                        ?>
                                    </p>
                                    <div class="row">
                                        <div class="col-6">
                                            <a href="signin.php">
                                                <button id="btnLogin" type="button" class="btn w-100 button-signin fw-bold">Go Back</button>
                                            </a>
                                        </div>
                                        <div class="col-6">
                                            <button type="submit" class="btn w-100 button-signin fw-bold" id="btnFPUsernameContinue" name="btnFPUsernameContinue" style="background-color: #a9e8fc;">Continue</button>
                                        </div>
                                    </div>
                                </form>
                            <?php				
                            break;

                        case 'code-check':
                            ?>
                                <form action="forgot-password.php?forgotPasswordMethods=code-check" method="POST">
                                    <label for="" class="mb-2 fs-4">Verify Account.</label>
                                    <label for="" class="mb-3">Enter the verification code that has been sent to the email address linked to this account.</label>
                                    <div class="form-floating mb-2">
                                        <input type="text" class="form-control input-username" name="inputCodeForgotPassword" id="inputCodeForgotPassword" placeholder="Verification Code">
                                        <label for="inputCodeForgotPassword">Verification Code</label>
                                    </div>
                                    <p for="" class="text-center text-danger">
                                        <?php 
                                            foreach ($error as $err) {
                                                echo $err . "<br>";
                                            }
                                        ?>
                                    </p>
                                    <div class="row">
                                        <div class="col-6">
                                            <button type="submit" class="btn w-100 button-signin fw-bold" id="btnFPCodeResend" name="btnFPCodeResend">Resend Code</button>
                                        </div>
                                        <div class="col-6">
                                            <button type="submit" class="btn w-100 button-signin fw-bold" id="btnFPCodeContinue" name="btnFPCodeContinue" style="background-color: #a9e8fc;">Continue</button>
                                        </div>
                                    </div>
                                </form>
                            <?php
                            break;

                        case 'change-password':
                            ?>
                                <form action="forgot-password.php?forgotPasswordMethods=change-password" method="POST">
                                    <label for="" class="mb-2 fs-4">Update your Password.</label>
                                    <label for="" class="mb-2">Enter your new password below and that will be the password that you will use when accessing the system.</label>
                                    <div class="form-floating mb-4">
                                        <input type="text" class="form-control input-username password-requirements" name="inputNewPassword" id="inputNewPassword" placeholder="New Password" onpaste="return false" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*_=+-]).{10,}$" maxlength="20" required>
                                        <label for="inputPassword">New Password</label>
                                    </div>
                                    <div class="form-floating mb-4">
                                        <input type="text" class="form-control input-username" name="inputNewPassword2" id="inputNewPassword2" placeholder="Re-enter Password" onpaste="return false" maxlength="20" required>
                                        <label for="inputPassword2">Re-enter Password</label>
                                    </div>
                                    <p for="" class="text-center text-danger">
                                        <?php 
                                            foreach ($error as $err) {
                                                echo $err . "<br>";
                                            }
                                        ?>
                                    </p>
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn w-100 button-signin fw-bold" id="btnFPNewPasswordContinue" name="btnFPNewPasswordContinue" style="background-color: #a9e8fc;">Update Password</button>
                                        </div>
                                    </div>
                                </form>
                            <?php
                            break;
                        
                        default:
                            break;
                    }
                ?>
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