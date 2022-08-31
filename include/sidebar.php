<?php 
    $adminID = $_SESSION['ID'];
    $maxStorageData = $database->query("SELECT max_storage FROM admins_tbl WHERE ID = $adminID")->fetch_assoc();
    $maxStorage = $maxStorageData['max_storage'];

    if(isset($_SESSION['message'])){
        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
?>
<div class="sidebar close">
    <div class="logo-details">
        <img src="images/JJ Logo.png" alt="">
        <span class="logo-name">JJ Fabrication</span>
    </div>
    <hr class="text-light">
    <ul class="nav-links">
        <li class="tooltips-toggle <?php if ($activePage === 'dashboard') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
            <a href="dashboard.php">
                <i class='bx bx-grid-alt fs-5'></i>
                <span class="link-name">Dashboard</span>
            </a>
        </li>
        
        <?php
            if ($_SESSION['position'] != 'Master Admin') {
                ?>
                    <hr class="text-light">
                <?php
            }

            if ($_SESSION['position'] == 'Administrator') {
                ?>
                    <li class="tooltips-toggle <?php if ($activePage === 'purchases') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Purchases">
                        <a href="purchases.php">
                            <i class='bx bx-cart fs-5'></i>
                            <span class="link-name">Purchases</span>
                        </a>
                    </li>
                <?php
            }

            if ($_SESSION['position'] != 'Master Admin') {
                ?>
                    <li class="tooltips-toggle <?php if ($activePage === 'materials') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Materials">
                        <div class="icon-link">
                            <a href="materials.php">
                                <i class='bx bx-package fs-5'>
                                    <?php
                                        $materialsTableQuery = $database->query("SELECT COUNT(*) AS materials_count FROM `materials_tbl` WHERE m_quality = 'Good' AND m_status != 'Used' GROUP BY m_name ORDER BY materials_count LIMIT 1")->fetch_assoc();
                                        if (isset($materialsTableQuery['materials_count'])) {
                                            $mCountChecker = ($materialsTableQuery['materials_count'] / $maxStorage) * 100;
                                            if ($mCountChecker > 49) {}
                                            else if ($mCountChecker > 24) {
                                                echo "<span class='position-absolute bg-warning border border-light rounded-circle' style='top: 5px; padding: 5px'></span>";
                                            }
                                            else {
                                                echo "<span class='position-absolute bg-danger border border-light rounded-circle' style='top: 5px; padding: 5px'></span>";
                                            }
                                        }
                                    ?>
                                </i>
                                <span class="link-name">Materials</span>
                            </a>
                            <i class='bx bxs-chevron-down arrow fs-5'></i>
                        </div>
                        <ul class="sub-menu">
                            <li class="<?php if ($activePage === 'materials' && $subActivePage === 'catalog') {echo "sub-active";} ?>"><a href="m-catalog.php">Catalog</a></li>
                            <li class="<?php if ($activePage === 'materials' && $subActivePage === 'stocks') {echo "sub-active";} ?>"><a href="m-stocks.php">Stocks</a></li>
                            <li class="<?php if ($activePage === 'materials' && $subActivePage === 'used') {echo "sub-active";} ?>"><a href="m-used.php">Used</a></li>
                        </ul>
                    </li>
                    <li class="tooltips-toggle <?php if ($activePage === 'products') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Products">
                        <div class="icon-link">
                            <a href="products.php">
                                <i class='bx bx-selection fs-5'>
                                    <?php
                                        $productsTableQuery = $database->query("SELECT COUNT(*) AS products_count FROM `products_tbl` WHERE p_quality = 'Good' AND p_status != 'Sold' GROUP BY p_name ORDER BY products_count LIMIT 1")->fetch_assoc();
                                        if (isset($productsTableQuery['products_count'])) {
                                            $pCountChecker = ($productsTableQuery['products_count'] / $maxStorage) * 100;
                                            if ($pCountChecker > 49) {}
                                            else if ($pCountChecker > 24) {
                                                echo "<span class='position-absolute bg-warning border border-light rounded-circle' style='top: 5px; padding: 5px'></span>";
                                            }
                                            else {
                                                echo "<span class='position-absolute bg-danger border border-light rounded-circle' style='top: 5px; padding: 5px'></span>";
                                            }
                                        }
                                    ?>
                                </i>
                                <span class="link-name">Products</span>
                            </a>
                            <i class='bx bxs-chevron-down arrow fs-5'></i>
                        </div>
                        <ul class="sub-menu">
                            <li class="<?php if ($activePage === 'products' && $subActivePage === 'catalog') {echo "sub-active";} ?>"><a href="p-catalog.php">Catalog</a></li>
                            <li class="<?php if ($activePage === 'products' && $subActivePage === 'stocks') {echo "sub-active";} ?>"><a href="p-stocks.php">Stocks</a></li>
                            <li class="<?php if ($activePage === 'products' && $subActivePage === 'sold') {echo "sub-active";} ?>"><a href="p-sold.php">Sold</a></li>
                        </ul>
                    </li>
                    <li class="tooltips-toggle <?php if ($activePage === 'projects') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Projects">
                        <a href="projects.php">
                            <i class='bx bx-check-square fs-5'>
                                <?php
                                    $projectsTableCheck = $database->query("SELECT * FROM projects_tbl WHERE proj_status = 'Active'");
                                    if ($projectsTableCheck->num_rows > 0) {
                                        $projWarning = false;
                                        $projDanger = false;
                                        while ($projectsTableCheckRows = $projectsTableCheck->fetch_assoc()) {
                                            $deliveryDate = new DateTime($projectsTableCheckRows['proj_delivery_date']);
                                            $dateDifference = $deliveryDate->diff(new DateTime("now"));
                                            if ($dateDifference->format("%a") >= 7) {}
                                            else if ($dateDifference->format("%a") >= 3) {
                                                $projWarning = true;
                                            }
                                            else {
                                                $projDanger = true;
                                            }
                                        }

                                        if ($projWarning == true && $projDanger == false) {
                                            echo "<span class='position-absolute bg-warning border border-light rounded-circle' style='top: 5px; padding: 5px'></span>";
                                        }
                                        else if ($projWarning == false && $projDanger == true || $projWarning == true && $projDanger == true) {
                                            echo "<span class='position-absolute bg-danger border border-light rounded-circle' style='top: 5px; padding: 5px'></span>";
                                        }
                                    }
                                ?>
                            </i>
                            <span class="link-name">Projects</span>
                        </a>
                    </li>
                    <hr class="text-light">
                    <li class="tooltips-toggle <?php if ($activePage === 'scan') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Scan">
                        <a href="scan.php">
                            <i class='bx bx-qr-scan fs-5'></i>
                            <span class="link-name">Scan</span>
                        </a>
                    </li>
                <?php
            }
        ?>
        <?php
            if ($_SESSION['position'] != 'User') {
                ?>
                    <hr class="text-light">
                    <li class="tooltips-toggle <?php if ($activePage === 'history') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="History">
                        <a href="history.php">
                            <i class='bx bx-history fs-5'></i>
                            <span class="link-name">History</span>
                        </a>
                    </li>
                <?php
            }

            if ($_SESSION['position'] == 'Administrator') {
                ?>
                    <li class="tooltips-toggle <?php if ($activePage === 'defectives') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Defectives">
                        <a href="defectives.php">
                            <i class='bx bx-error fs-5'></i>
                            <span class="link-name">Defectives</span>
                        </a>
                    </li>



                    <li class="tooltips-toggle <?php if ($activePage === 'archives') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Archives">
                        <a href="archives.php">
                            <i class='bx bx-trash fs-5'>
                                <?php
                                    $currentDate = date("Y-m-d");
                                    $deletionDate = date("Y-m-d",strtotime("-28 days",strtotime($currentDate)));
                                    $purchasesTable = $database->query("SELECT COUNT(*) AS pg_count FROM purchase_group_tbl WHERE pg_status = 'Deleted' AND pg_date_deleted <= '$deletionDate'")->fetch_assoc();
                                    $materialsTable = $database->query("SELECT COUNT(*) AS m_count FROM materials_tbl WHERE m_quality = 'Trash' AND m_rejected <= '$deletionDate'")->fetch_assoc();
                                    $productsTable = $database->query("SELECT COUNT(*) AS p_count FROM products_tbl WHERE p_quality = 'Trash' AND p_rejected <= '$deletionDate'")->fetch_assoc();
                                    $projectsTable = $database->query("SELECT COUNT(*) AS proj_count FROM projects_tbl WHERE proj_status = 'Deleted' AND proj_rejected <= '$deletionDate'")->fetch_assoc();

                                    if ($purchasesTable['pg_count'] >= 1 || $materialsTable['m_count'] >= 1 || $productsTable['p_count'] >= 1 || $projectsTable['proj_count'] >= 1) {
                                        echo "<span class='position-absolute bg-danger border border-light rounded-circle' style='top: 5px; padding: 5px'></span>";
                                    }
                                ?>
                            </i>
                            <span class="link-name">Archives</span>
                        </a>
                    </li>



                    
                    <hr class="text-light">
                    <li class="tooltips-toggle <?php if ($activePage === 'settings') {echo "active";} ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Settings">
                        <a href="settings.php">
                            <i class='bx bx-cog fs-5'></i>
                            <span class="link-name">Settings</span>
                        </a>
                    </li>
                <?php
            }
        ?>
        <li>
            <div class="profile-details">
                <div class="profile-content">
                    <a href="profile.php"><img src="images/profiles/<?php echo $adminData['photo'];?>"></a>
                </div>
                <div class="name-job">
                    <div class="profile-name">
                        <a href="profile.php" class="profile-name-content" data-bs-toggle="tooltip" data-bs-placement="right" title="View Profile"><?php echo $_SESSION['name'] ?></a>
                    </div>
                    <div class="btn-sign-out">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#modalAccountSignOutConfirmation"><span class='bx bx-log-out fs-5'></span>&nbsp; Sign Out</a>
                    </div>
                </div>
            </div>
        </li>
    </ul>
</div>

<div class="modal fade" id="modalAccountSignOutConfirmation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Signing Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Do you want to sign out now <?php echo $_SESSION['firstname'] ?>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Wait!</button>
                    <button type="submit" class="btn btn-success" data-bs-dismiss="modal" name="signOut">Yes, Sign Me Out</button>
                </div>
            </form>
        </div>
    </div>
</div>