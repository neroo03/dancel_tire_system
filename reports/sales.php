<?php
include '../includes/auth.php';
include '../includes/config.php';

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Get sales data
$stmt = $pdo->prepare("SELECT st.*, c.Name as CustomerName 
                      FROM salestransaction st 
                      LEFT JOIN customer c ON st.CustomerID = c.CustomerID 
                      WHERE st.Date BETWEEN ? AND ? 
                      ORDER BY st.Date DESC");
$stmt->execute([$startDate, $endDate]);
$sales = $stmt->fetchAll();

// Calculate totals
$stmt = $pdo->prepare("SELECT SUM(TotalAmount) as total, PaymentMethod 
                      FROM salestransaction 
                      WHERE Date BETWEEN ? AND ? 
                      GROUP BY PaymentMethod");
$stmt->execute([$startDate, $endDate]);
$totals = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Sales Reports</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Sales Reports</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filter
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row mb-4">
        <?php foreach ($totals as $total): ?>
            <div class="col-md-4">
                <div class="card text-white <?php echo $total['PaymentMethod'] == 'Cash' ? 'bg-primary' : 'bg-success'; ?> mb-3">
                    <div class="card-header"><?php echo $total['PaymentMethod']; ?> Sales</div>
                    <div class="card-body">
                        <h5 class="card-title">₱<?php echo number_format($total['total'], 2); ?></h5>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Sales Transactions (<?php echo date('M j, Y', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?>)
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction ID</th>
                            <th>Customer</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo $sale['Date']; ?></td>
                                <td><?php echo $sale['TransactionID']; ?></td>
                                <td><?php echo $sale['CustomerName'] ?? 'Walk-in Customer'; ?></td>
                                <td><?php echo $sale['PaymentMethod']; ?></td>
                                <td>₱<?php echo number_format($sale['TotalAmount'], 2); ?></td>
                                <td>
                                    <a href="../sales/view.php?id=<?php echo $sale['TransactionID']; ?>" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>