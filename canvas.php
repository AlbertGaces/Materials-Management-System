<?php
    if(!isset($_SESSION)){
        session_start();
    }
    include "include/dbconnect.php";
    date_default_timezone_set('Asia/Manila');
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="images/JJ Logo.png">
        <title>Canvas</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.css"/>
    </head>
    <body>
        <div class="position-absolute" style="top: 30px; left: 30px">
            <img class="img-fluid" src="images/JJ Logo.png" alt="" width="90px" height="90px"> <br>
        </div>
        <div class="row">
            <div class="col text-center fs-6">
                <b class="fs-3">JJ RUBBER PRODUCTS</b> <br>
                <div style="font-size: small;">
                    Purok 6, Palo-Alto, Calamba City, Laguna <br>
                    MODESTO B. LAGRAZON JR. - Prop. <br>
                    VAT Reg. TIN 167-204-210-000 <br>
                    Telefax (049) 306-0715 <span class="bi bi-dot"></span> Tel. No. (049) 576-7824 * 576-8183 * 576-8190 <br>
                    Cell. No. 0917-872-6210 * 0998-983-0427 <br>
                </div>
            </div>
        </div>
        <hr>
        <div class="row" style="font-size: 12px;">
            <div class="col">
                <?php
                    $currentDate = date("Y-m-d H:i:s");
                    if(isset($_POST['printMaterialCount'])){
                        echo "<b>Materials Tally:</b> As of ".date('F j, Y, h:i a', strtotime($currentDate)).", there are: <br><br>";
                        ?>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th class="w-50">Material Name</th>
                                    <th class="w-50">Measurement</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $materialsTableName = $database->query("SELECT materials_tbl.m_name, material_name_tbl.m_name AS m_name_word FROM materials_tbl INNER JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID GROUP BY materials_tbl.m_name");
                                        if ($materialsTableName->num_rows > 0) {
                                            while ($materialsTableNameRow = $materialsTableName->fetch_assoc()) {
                                                $materialName = $materialsTableNameRow['m_name'];
                                                $materialsTableUnit = $database->query("SELECT m_unit FROM materials_tbl WHERE m_name = '$materialName' GROUP BY m_unit");
                                                if ($materialsTableUnit->num_rows > 0) {
                                                    while ($materialsTableUnitRow = $materialsTableUnit->fetch_assoc()) {
                                                        $materialUnit = $materialsTableUnitRow['m_unit'];
                                                        $materialsTableMeasurement = $database->query("SELECT m_remaining FROM materials_tbl WHERE m_name = '$materialName' AND m_unit = '$materialUnit'");
                                                        if ($materialsTableMeasurement->num_rows > 0) {
                                                            $materialMeasurement = 0;
                                                            while ($materialsTableMeasurementRow = $materialsTableMeasurement->fetch_assoc()) {
                                                                $materialMeasurement = $materialMeasurement + $materialsTableMeasurementRow['m_remaining'];
                                                            }
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $materialsTableNameRow['m_name_word'];?></td>
                                                                    <td>
                                                                        <?php
                                                                            if ($materialMeasurement > 1) {
                                                                                echo $materialMeasurement." ".$materialUnit."s <br>";
                                                                            }
                                                                            else {
                                                                                echo $materialMeasurement." ".$materialUnit." <br>";
                                                                            }
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                        <?php
                    }

                    if(isset($_POST['printProductCount'])){
                        echo "<b>Products Tally:</b> As of ".date('F j, Y, h:i a', strtotime($currentDate)).", there are: <br><br>";
                        ?>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th class="w-50">Product Name</th>
                                    <th class="w-50">Quantity</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $productsTableCountStorage = $database->query("SELECT product_name_tbl.p_name, COUNT(products_tbl.ID) AS product_count FROM products_tbl INNER JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID WHERE products_tbl.p_status = 'Storage' GROUP BY p_name");
                                        while ($productsTableCountStorageRow = $productsTableCountStorage->fetch_assoc()) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $productsTableCountStorageRow['p_name'];?></td>
                                                    <td>
                                                        <?php
                                                            if ($productsTableCountStorageRow['product_count'] > 1) {
                                                                echo $productsTableCountStorageRow['product_count']." Pieces";
                                                            }
                                                            else {
                                                                echo $productsTableCountStorageRow['product_count']." Piece";
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php
                                        }
                                    ?>
                                </tbody>
                            </table>
                        <?php
                    }
                    
                    if (isset($_POST['printMPCount'])) {
                        echo "As of ".date('F j, Y, h:i a', strtotime($currentDate)).", there are: <br><br>";
                        echo "<b>Materials Tally:</b>"
                        ?>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th class="w-50">Material Name</th>
                                    <th class="w-50">Measurement</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $materialsTableName = $database->query("SELECT materials_tbl.m_name, material_name_tbl.m_name AS m_name_word FROM materials_tbl INNER JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID GROUP BY materials_tbl.m_name");
                                        if ($materialsTableName->num_rows > 0) {
                                            while ($materialsTableNameRow = $materialsTableName->fetch_assoc()) {
                                                $materialName = $materialsTableNameRow['m_name'];
                                                $materialsTableUnit = $database->query("SELECT m_unit FROM materials_tbl WHERE m_name = '$materialName' GROUP BY m_unit");
                                                if ($materialsTableUnit->num_rows > 0) {
                                                    while ($materialsTableUnitRow = $materialsTableUnit->fetch_assoc()) {
                                                        $materialUnit = $materialsTableUnitRow['m_unit'];
                                                        $materialsTableMeasurement = $database->query("SELECT m_remaining FROM materials_tbl WHERE m_name = '$materialName' AND m_unit = '$materialUnit'");
                                                        if ($materialsTableMeasurement->num_rows > 0) {
                                                            $materialMeasurement = 0;
                                                            while ($materialsTableMeasurementRow = $materialsTableMeasurement->fetch_assoc()) {
                                                                $materialMeasurement = $materialMeasurement + $materialsTableMeasurementRow['m_remaining'];
                                                            }
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $materialsTableNameRow['m_name_word'];?></td>
                                                                    <td>
                                                                        <?php
                                                                            if ($materialMeasurement > 1) {
                                                                                echo $materialMeasurement." ".$materialUnit."s <br>";
                                                                            }
                                                                            else {
                                                                                echo $materialMeasurement." ".$materialUnit." <br>";
                                                                            }
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                            <br><br>
                            <b>Products Tally:</b>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th class="w-50">Product Name</th>
                                    <th class="w-50">Quantity</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $productsTableCountStorage = $database->query("SELECT product_name_tbl.p_name, COUNT(products_tbl.ID) AS product_count FROM products_tbl INNER JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID WHERE products_tbl.p_status = 'Storage' GROUP BY p_name");
                                        while ($productsTableCountStorageRow = $productsTableCountStorage->fetch_assoc()) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $productsTableCountStorageRow['p_name'];?></td>
                                                    <td>
                                                        <?php
                                                            if ($productsTableCountStorageRow['product_count'] > 1) {
                                                                echo $productsTableCountStorageRow['product_count']." Pieces";
                                                            }
                                                            else {
                                                                echo $productsTableCountStorageRow['product_count']." Piece";
                                                            }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php
                                        }
                                    ?>
                                </tbody>
                            </table>
                        <?php
                    }

                    if (isset($_POST['printMaterialRecords'])) {
                        ?>
                            <div class="text-center">
                                <b>Material Records</b><br>
                                <?php echo date('F j, Y, h:i a', strtotime($currentDate))?>
                            </div>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th>Group</th>
                                    <th>Name</th>
                                    <th>Measurement</th>
                                    <th>Remaining</th>
                                    <th>Price</th>
                                    <th>Received</th>
                                    <th>Status</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $materialsTable = $database->query("SELECT purchase_group_tbl.pg_code, material_name_tbl.m_name AS m_name_word, materials_tbl.m_measurement, materials_tbl.m_remaining, materials_tbl.m_unit, materials_tbl.m_price, materials_tbl.m_received, materials_tbl.m_status FROM materials_tbl INNER JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID INNER JOIN purchase_group_tbl ON materials_tbl.m_purchase_group = purchase_group_tbl.ID ORDER BY m_received DESC");
                                        if ($materialsTable->num_rows > 0) {
                                            while ($materialsTableRow = $materialsTable->fetch_assoc()) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo $materialsTableRow['pg_code'];?></td>
                                                        <td><?php echo $materialsTableRow['m_name_word'];?></td>
                                                        <td>
                                                            <?php 
                                                                echo $materialsTableRow['m_measurement']." ";
                                                                if ($materialsTableRow['m_unit'] == 'Piece') {
                                                                    if ($materialsTableRow['m_measurement'] > 1) {
                                                                        echo "pcs";
                                                                    }
                                                                    else {
                                                                        echo "pc";
                                                                    }
                                                                }
                                                                else if ($materialsTableRow['m_unit'] == 'Kilogram') {
                                                                    if ($materialsTableRow['m_measurement'] > 1) {
                                                                        echo "kgs";
                                                                    }
                                                                    else {
                                                                        echo "kg";
                                                                    }
                                                                }
                                                                else {
                                                                    if ($materialsTableRow['m_measurement'] > 1) {
                                                                        echo "mts";
                                                                    }
                                                                    else {
                                                                        echo "mt";
                                                                    }
                                                                }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                                echo $materialsTableRow['m_remaining']." ";
                                                                if ($materialsTableRow['m_unit'] == 'Piece') {
                                                                    if ($materialsTableRow['m_remaining'] > 1) {
                                                                        echo "pcs";
                                                                    }
                                                                    else {
                                                                        echo "pc";
                                                                    }
                                                                }
                                                                else if ($materialsTableRow['m_unit'] == 'Kilogram') {
                                                                    if ($materialsTableRow['m_remaining'] > 1) {
                                                                        echo "kgs";
                                                                    }
                                                                    else {
                                                                        echo "kg";
                                                                    }
                                                                }
                                                                else {
                                                                    if ($materialsTableRow['m_remaining'] > 1) {
                                                                        echo "mts";
                                                                    }
                                                                    else {
                                                                        echo "mt";
                                                                    }
                                                                }
                                                            ?>
                                                        </td>
                                                        <td><?php echo "₱".number_format((float)$materialsTableRow['m_price'], 2, '.', ',');?></td>
                                                        <td><?php echo date('F j, Y', strtotime($materialsTableRow['m_received']))?></td>    
                                                        <td><?php echo $materialsTableRow['m_status']?></td> 
                                                    </tr>
                                                <?php
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                        <?php
                    }

                    if (isset($_POST['printProductRecords'])) {
                        ?>
                            <div class="text-center">
                                <b>Product Records</b><br>
                                <?php echo date('F j, Y, h:i a', strtotime($currentDate))?>
                            </div>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th>Name</th>
                                    <th>Quantity</th>
                                    <th>Remaining</th>
                                    <th>Price</th>
                                    <th>Completed</th>
                                    <th>Status</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $productsTable = $database->query("SELECT product_name_tbl.p_name, products_tbl.p_measurement, products_tbl.p_remaining, products_tbl.p_price, products_tbl.p_completed, products_tbl.p_status FROM products_tbl INNER JOIN product_name_tbl ON products_tbl.p_name = product_name_tbl.ID ORDER BY p_completed DESC");
                                        if ($productsTable->num_rows > 0) {
                                            while ($productsTableRow = $productsTable->fetch_assoc()) {
                                                ?>
                                                    <tr>
                                                        <td><?php echo $productsTableRow['p_name'];?></td>
                                                        <td>
                                                            <?php 
                                                                echo $productsTableRow['p_measurement']." ";
                                                                if ($productsTableRow['p_measurement'] > 1) {
                                                                    echo "pcs";
                                                                }
                                                                else {
                                                                    echo "pc";
                                                                }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                                echo $productsTableRow['p_remaining']." ";
                                                                if ($productsTableRow['p_remaining'] > 1) {
                                                                    echo "pcs";
                                                                }
                                                                else {
                                                                    echo "pc";
                                                                }
                                                            ?>
                                                        </td>
                                                        <td><?php echo "₱".number_format((float)$productsTableRow['p_price'], 2, '.', ',');?></td>
                                                        <td><?php echo date('F j, Y', strtotime($productsTableRow['p_completed']))?></td>    
                                                        <td><?php echo $productsTableRow['p_status']?></td> 
                                                    </tr>
                                                <?php
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                        <?php
                    }

                    if (isset($_POST['printPurchaseSlip'])) {
                        $pgCode = $_POST['inputHiddenPrintPurchaseSlipCode'];
                        $pgSupplier = $_POST['inputHiddenPrintPurchaseSlipSupplier'];
                        $pgRepresentative = $_POST['inputHiddenPrintPurchaseSlipRepresentative'];
                        $pgDate = $_POST['inputHiddenPrintPurchaseSlipDate'];

                        $totalPrice = 0;
                        ?>
                            <div class="text-center">
                                <b><?php echo $pgCode?> - Purchase Slip</b><br>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-6 text-end">
                                            Supplier: <u>&emsp;&emsp; <?php echo $pgSupplier;?> &emsp;&emsp;</u><br>
                                            Representative: <u>&emsp;&emsp; <?php echo $pgRepresentative;?> &emsp;&emsp;</u> 
                                        </div>
                                        <div class="col-6">
                                            Date: <u>&emsp;&emsp; <?php echo date('F j, Y', strtotime($pgDate))?> &emsp;&emsp;</u><br>
                                            Purchase Code: <u>&emsp;&emsp; <?php echo $pgCode;?> &emsp;&emsp;</u> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th>Measurement</th>
                                    <th>Item</th>
                                    <th>Code</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $materialsTable = $database->query("SELECT material_name_tbl.m_name AS m_name_word, materials_tbl.m_code, materials_tbl.m_unit, materials_tbl.m_measurement, materials_tbl.m_price, purchase_group_tbl.pg_code FROM materials_tbl INNER JOIN purchase_group_tbl ON materials_tbl.m_purchase_group = purchase_group_tbl.ID INNER JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID WHERE pg_code = '$pgCode'");
                                        if ($materialsTable->num_rows > 0) {
                                            while ($materialsTableRow = $materialsTable->fetch_assoc()) {
                                                $totalPrice = $totalPrice + $materialsTableRow['m_price'];
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <?php 
                                                                echo $materialsTableRow['m_measurement']." ";
                                                                if ($materialsTableRow['m_unit'] == 'Piece') {
                                                                    if ($materialsTableRow['m_measurement'] > 1) {
                                                                        echo "pcs";
                                                                    }
                                                                    else {
                                                                        echo "pc";
                                                                    }
                                                                }
                                                                else if ($materialsTableRow['m_unit'] == 'Kilogram') {
                                                                    if ($materialsTableRow['m_measurement'] > 1) {
                                                                        echo "kgs";
                                                                    }
                                                                    else {
                                                                        echo "kg";
                                                                    }
                                                                }
                                                                else {
                                                                    if ($materialsTableRow['m_measurement'] > 1) {
                                                                        echo "mts";
                                                                    }
                                                                    else {
                                                                        echo "mt";
                                                                    }
                                                                }
                                                            ?>
                                                        </td>
                                                        <td><?php echo $materialsTableRow['m_name_word'];?></td>
                                                        <td><?php echo $materialsTableRow['m_code'];?></td>
                                                        <td>
                                                            <?php 
                                                                $unitPrice = $materialsTableRow['m_price'] / $materialsTableRow['m_measurement'];
                                                                echo "₱".number_format((float)$unitPrice, 2, '.', ',')."/".$materialsTableRow['m_unit'];
                                                            ?>
                                                        </td>
                                                        <td><?php echo "₱".number_format((float)$materialsTableRow['m_price'], 2, '.', ',');?></td>
                                                    </tr>
                                                <?php
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                            <br>
                            <div class="row justify-content-end">
                                <div class="col-auto">
                                    Total: <u><b>₱<?php echo number_format((float)$totalPrice, 2, '.', ',');?></b></u> 
                                </div>

                            </div>
                        <?php
                    }

                    if (isset($_POST['projPrintCompletedRecords'])) {
                        $projStartDate = $_POST['projRecordStartDate'];
                        $projEndDate = $_POST['projRecordEndDate'];

                        $projStartDateText = date('F j, Y', strtotime($projStartDate));
                        $projEndDateText = date('F j, Y', strtotime($projEndDate));

                        $totalProjectPrice = 0;

                        ?>
                            <div class="text-center">
                                Completed Projects <br>
                                (<?php echo $projStartDateText?> - <?php echo $projEndDateText?>)</b><br>
                            </div>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th>Client</th>
                                    <th>Product Ordered</th>
                                    <th>Quantity</th>
                                    <th>Total Price</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $projectsTable = $database->query("SELECT * FROM projects_tbl WHERE proj_status = 'Completed' AND proj_rejected >= '$projStartDate 00:00:00' AND proj_rejected <= '$projEndDate 23:59:00'");
                                        if ($projectsTable->num_rows >= 1) {
                                            while ($projectsTableRows = $projectsTable->fetch_assoc()) {
                                                $projectID = $projectsTableRows['ID'];
                                                $productUsedTable = $database->query("SELECT SUM(product_used_tbl.p_measurement) AS total_product_measurement, product_name_tbl.p_name, SUM(product_used_tbl.p_price) AS total_product_price FROM product_used_tbl INNER JOIN product_name_tbl ON product_used_tbl.p_name = product_name_tbl.ID WHERE p_sold_to = '$projectID' GROUP BY p_name");
                                                while ($productUsedTableRows = $productUsedTable->fetch_assoc()) {
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $projectsTableRows['proj_client'];?></td>
                                                            <td><?php echo $productUsedTableRows['p_name'];?></td>
                                                            <td><?php echo "x".$productUsedTableRows['total_product_measurement'];?></td>
                                                            <td><?php echo "₱".number_format((float)$productUsedTableRows['total_product_price'], 2, '.', ',');?></td>
                                                        </tr>
                                                    <?php
                                                    $totalProjectPrice = $totalProjectPrice + $productUsedTableRows['total_product_price']; 
                                                }
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                            <br>
                            <div class="row justify-content-end">
                                <div class="col-auto">
                                    Total: <u><b>₱<?php echo number_format((float)$totalProjectPrice, 2, '.', ',');?></b></u> 
                                </div>

                            </div>
                        <?php
                    }

                    if (isset($_POST['purchasesPrintCompletedRecords'])) {
                        $purchasesStartDate = $_POST['purchasesRecordStartDate'];
                        $purchasesEndDate = $_POST['purchasesRecordEndDate'];

                        $purchasesStartDateText = date('F j, Y', strtotime($purchasesStartDate));
                        $purchasesEndDateText = date('F j, Y', strtotime($purchasesEndDate));

                        $totalPurchasePrice = 0;

                        ?>
                            <div class="text-center">
                                Completed Purchases <br>
                                (<?php echo $purchasesStartDateText?> - <?php echo $purchasesEndDateText?>)</b><br>
                            </div>
                            <table class="datatable-no-all table table-sm" style="font-size: 12px;">
                                <thead>
                                    <th>Supplier</th>
                                    <th>Materials Ordered</th>
                                    <th>Unit</th>
                                    <th>Quantity</th>
                                    <th>Total Price</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $purchasesTable = $database->query("SELECT * FROM purchase_group_tbl WHERE pg_status = 'Completed' AND pg_date_deleted >= '$purchasesStartDate 00:00:00' AND pg_date_deleted <= '$purchasesEndDate 23:59:59'");
                                        if ($purchasesTable->num_rows >= 1) {
                                            while ($purchasesTableRows = $purchasesTable->fetch_assoc()) {
                                                $purchasesID = $purchasesTableRows['ID'];
                                                $materialsTableUnit = $database->query("SELECT m_unit FROM materials_tbl WHERE m_purchase_group = '$purchasesID' GROUP BY m_unit");
                                                if ($materialsTableUnit->num_rows >= 1) {
                                                    while ($materialsTableUnitRows = $materialsTableUnit->fetch_assoc()) {
                                                        $materialUnit = $materialsTableUnitRows['m_unit'];
                                                        $materialsTable = $database->query("SELECT material_name_tbl.m_name, SUM(materials_tbl.m_measurement) AS total_material_measurement, SUM(materials_tbl.m_price) AS total_material_price FROM materials_tbl INNER JOIN material_name_tbl ON materials_tbl.m_name = material_name_tbl.ID WHERE m_unit = '$materialUnit' AND m_purchase_group = '$purchasesID'");
                                                        while ($materialsTableRows = $materialsTable->fetch_assoc()) {
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $purchasesTableRows['pg_supplier'];?></td>
                                                                    <td><?php echo $materialsTableRows['m_name'];?></td>
                                                                    <td><?php echo $materialsTableUnitRows['m_unit'];?></td>
                                                                    <td><?php echo "x".$materialsTableRows['total_material_measurement'];?></td>
                                                                    <td><?php echo "₱".number_format((float)$materialsTableRows['total_material_price'], 2, '.', ',');?></td>
                                                                </tr>
                                                            <?php
                                                            $totalPurchasePrice = $totalPurchasePrice + $materialsTableRows['total_material_price']; 
                                                        }
                                                    }
                                                }
                                                
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                            <br>
                            <div class="row justify-content-end">
                                <div class="col-auto">
                                    Total: <u><b>₱<?php echo number_format((float)$totalPurchasePrice, 2, '.', ',');?></b></u> 
                                </div>

                            </div>
                        <?php
                    }

                    if (isset($_POST['btnPrintBOM'])) {
                        $projectID = $_POST['inputHiddenPrintingProjectID'];
                        $projectCode = $_POST['inputHiddenPrintingProjectCode'];
                        $currentDate = date("F j, Y");
                        
                        ?>
                            <div class="text-center">
                                <label>Bill of Materials for <?php echo $projectCode;?></label>
                                <br>
                                <label>Print Date: <?php echo $currentDate;?></label>
                            </div>
                            <br>
                            <table class="datatable-no-all table table-sm text-center" style="font-size: 12px;">
                                <thead>
                                    <th>Item</th>
                                    <th>Code</th>
                                    <th>Measurement</th>
                                    <th>Price</th>
                                </thead>
                                <tbody>
                                    <?php 
                                        $productsUsedTable = $database->query("SELECT * FROM product_used_tbl WHERE p_sold_to = '$projectID'");
                                        if ($productsUsedTable->num_rows >= 1) {
                                            while ($productsUsedTableRows = $productsUsedTable->fetch_assoc()) {
                                                $productCode = $productsUsedTableRows['p_code'];
                                                $materialUsedTable = $database->query("SELECT * FROM material_used_tbl INNER JOIN material_name_tbl ON material_used_tbl.m_name = material_name_tbl.ID WHERE material_used_tbl.p_code = '$productCode'");
                                                if ($materialUsedTable->num_rows >= 1) {
                                                    while ($materialUsedTableRows = $materialUsedTable->fetch_assoc()) {
                                                        ?>
                                                            <tr>
                                                                <td><?php echo $materialUsedTableRows['m_name'];?></td>
                                                                <td><?php echo $materialUsedTableRows['m_code'];?></td>
                                                                <td><?php echo $materialUsedTableRows['m_measurement']." ".$materialUsedTableRows['m_unit'];?></td>
                                                                <td><?php echo "₱".number_format((float)$materialUsedTableRows['m_price'], 2, '.', ',');?></td>
                                                            </tr>
                                                        <?php
                                                    }
                                                }
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                        <?php
                    }
                ?>
            </div>
        </div>


        <script>window.print();</script>
        <script src="js/jquery.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.3/af-2.3.7/b-2.1.1/cr-1.5.5/date-1.1.1/fc-4.0.1/fh-3.2.1/kt-2.6.4/r-2.2.9/rg-1.1.4/rr-1.2.8/sc-2.0.5/sb-1.3.0/sp-1.4.0/sl-1.3.4/sr-1.0.1/datatables.min.js"></script>
        <script src="js/PassRequirements.js"></script>
        <script src="js/script.js"></script>
    </body>
</html>



    




