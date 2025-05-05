<?php
include '../includes/auth.php';
include '../includes/config.php';

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Get sales transactions
$stmt = $pdo->prepare("SELECT st.*, c.Name as CustomerName, e.Name as EmployeeName 
                      FROM salestransaction st
                      LEFT JOIN customer c ON st.CustomerID = c.CustomerID
                      LEFT JOIN employee e ON st.EmployeeID = e.EmployeeID
                      WHERE st.Date BETWEEN ? AND ?
                      ORDER BY st.Date DESC, st.TransactionID DESC");
$stmt->execute([$startDate, $endDate]);
$transactions = $stmt->fetchAll();

// Calculate totals
$stmt = $pdo->prepare("SELECT 
                        SUM(TotalAmount) as total_sales,
                        COUNT(*) as transaction_count,
                        SUM(CASE WHEN PaymentMethod = 'Cash' THEN TotalAmount ELSE 0 END) as cash_sales,
                        SUM(CASE WHEN PaymentMethod = 'GCash' THEN TotalAmount ELSE 0 END) as gcash_sales
                      FROM salestransaction
                      WHERE Date BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$totals = $stmt->fetch();

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Sales Transactions</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Sales</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filters
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $startDate ?>">
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $endDate ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply</button>
                    <a href="index.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">Total Transactions</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <h4><?= $totals['transaction_count'] ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">Total Sales</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <h4>₱<?= number_format($totals['total_sales'], 2) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">Cash Sales</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <h4>₱<?= number_format($totals['cash_sales'], 2) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">GCash Sales</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <h4>₱<?= number_format($totals['gcash_sales'], 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-table me-1"></i>
                    Sales Transactions (<?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>)
                </div>
                <a href="create.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> New Transaction
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Transaction ID</th>
                            <th>Customer</th>
                            <th>Employee</th>
                            <th>Payment Method</th>
                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= $transaction['Date'] ?></td>
                                <td><?= $transaction['TransactionID'] ?></td>
                                <td><?= $transaction['CustomerName'] ?? 'Walk-in' ?></td>
                                <td><?= $transaction['EmployeeName'] ?? 'N/A' ?></td>
                                <td>
                                    <span class="badge bg-<?= $transaction['PaymentMethod'] == 'Cash' ? 'primary' : 'success' ?>">
                                        <?= $transaction['PaymentMethod'] ?>
                                    </span>
                                </td>
                                <td>₱<?= number_format($transaction['TotalAmount'], 2) ?></td>
                                <td>
                                    <a href="view.php?id=<?= $transaction['TransactionID'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="#" class="btn btn-sm btn-secondary" onclick="printReceipt(<?= $transaction['TransactionID'] ?>)">
                                        <i class="fas fa-print"></i> Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No transactions found for the selected period</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function printReceipt(transactionId) {
    window.open(`print_receipt.php?id=${transactionId}`, '_blank');
}
</script>

<?php include '../includes/footer.php'; ?>