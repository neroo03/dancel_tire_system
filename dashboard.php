<?php
// Initialize session and database connection
include 'includes/config.php';
include 'includes/auth.php';
include 'includes/header.php';

// Get today's sales
$todaySales = 0;
$stmt = $pdo->query("SELECT SUM(TotalAmount) as total FROM salestransaction WHERE Date = CURDATE()");
if ($stmt) {
    $row = $stmt->fetch();
    $todaySales = $row['total'] ?? 0;
}

// Get recent sales (last 5)
$recentSales = $pdo->query("SELECT * FROM salestransaction ORDER BY Date DESC LIMIT 5")->fetchAll();

// Get inventory count
$inventoryCount = $pdo->query("SELECT COUNT(*) as count FROM product")->fetch()['count'];

// Get low stock items
$lowStockItems = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE QuantityAvailable < 5")->fetch()['count'];

// Get customer count
$customerCount = $pdo->query("SELECT COUNT(*) as count FROM customer")->fetch()['count'];

// Get low inventory items
$lowInventory = $pdo->query("SELECT p.Name, i.QuantityAvailable 
                            FROM inventory i 
                            JOIN product p ON i.ProductID = p.ProductID 
                            WHERE i.QuantityAvailable < 5 
                            ORDER BY i.QuantityAvailable ASC 
                            LIMIT 5")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard</h1>
    
    <div class="row mt-4">
        <!-- Today's Sales Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Today's Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($todaySales, 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Items Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Inventory Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $inventoryCount ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Items Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Low Stock Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $lowStockItems ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Customers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $customerCount ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Sales</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td><?= $sale['Date'] ?></td>
                                    <td>₱<?= number_format($sale['TotalAmount'], 2) ?></td>
                                    <td><?= $sale['PaymentMethod'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Inventory Row -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Low Inventory</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowInventory as $item): ?>
                                <tr>
                                    <td><?= $item['Name'] ?></td>
                                    <td><?= $item['QuantityAvailable'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>