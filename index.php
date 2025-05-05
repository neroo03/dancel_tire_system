<?php
include 'includes/auth.php';
include 'includes/header.php';
include 'includes/config.php';  
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard</h1>
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">Today's Sales</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <h4>₱<?php 
                        $stmt = $pdo->query("SELECT SUM(TotalAmount) as total FROM salestransaction WHERE Date = CURDATE()");
                        $row = $stmt->fetch();
                        echo number_format($row['total'] ?? 0, 2);
                    ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">Inventory Items</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <h4><?php 
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product");
                        $row = $stmt->fetch();
                        echo $row['count'];
                    ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">Low Stock Items</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <h4><?php 
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE QuantityAvailable < 5");
                        $row = $stmt->fetch();
                        echo $row['count'];
                    ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">Total Customers</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <h4><?php 
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM customer");
                        $row = $stmt->fetch();
                        echo $row['count'];
                    ?></h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Recent Sales
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM salestransaction ORDER BY Date DESC LIMIT 5");
                                while ($row = $stmt->fetch()) {
                                    echo "<tr>
                                        <td>{$row['Date']}</td>
                                        <td>₱" . number_format($row['TotalAmount'], 2) . "</td>
                                        <td>{$row['PaymentMethod']}</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Low Inventory
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT p.Name, i.QuantityAvailable 
                                                    FROM inventory i 
                                                    JOIN product p ON i.ProductID = p.ProductID 
                                                    WHERE i.QuantityAvailable < 5 
                                                    ORDER BY i.QuantityAvailable ASC 
                                                    LIMIT 5");
                                while ($row = $stmt->fetch()) {
                                    echo "<tr>
                                        <td>{$row['Name']}</td>
                                        <td>{$row['QuantityAvailable']}</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>