<?php
include '../includes/auth.php';
include '../includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$transactionId = $_GET['id'];

// Get transaction header
$stmt = $pdo->prepare("SELECT st.*, c.Name as CustomerName, c.ContactInfo, e.Name as EmployeeName
                      FROM salestransaction st
                      LEFT JOIN customer c ON st.CustomerID = c.CustomerID
                      LEFT JOIN employee e ON st.EmployeeID = e.EmployeeID
                      WHERE st.TransactionID = ?");
$stmt->execute([$transactionId]);
$transaction = $stmt->fetch();

if (!$transaction) {
    $_SESSION['error'] = "Transaction not found";
    header('Location: index.php');
    exit;
}

// Get transaction details
$stmt = $pdo->prepare("SELECT sd.*, p.Name as ProductName, p.Type, p.Size
                      FROM salesdetails sd
                      JOIN product p ON sd.ProductID = p.ProductID
                      WHERE sd.TransactionID = ?");
$stmt->execute([$transactionId]);
$details = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Sales Transaction #<?= $transactionId ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Sales</a></li>
        <li class="breadcrumb-item active">Transaction Details</li>
    </ol>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Transaction Information
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8"><?= $transaction['Date'] ?></dd>

                        <dt class="col-sm-4">Customer</dt>
                        <dd class="col-sm-8"><?= $transaction['CustomerName'] ?? 'Walk-in Customer' ?></dd>

                        <?php if ($transaction['CustomerName']): ?>
                        <dt class="col-sm-4">Contact Info</dt>
                        <dd class="col-sm-8"><?= $transaction['ContactInfo'] ?? 'N/A' ?></dd>
                        <?php endif; ?>

                        <dt class="col-sm-4">Employee</dt>
                        <dd class="col-sm-8"><?= $transaction['EmployeeName'] ?? 'N/A' ?></dd>

                        <dt class="col-sm-4">Payment Method</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?= $transaction['PaymentMethod'] == 'Cash' ? 'primary' : 'success' ?>">
                                <?= $transaction['PaymentMethod'] ?>
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-money-bill-wave me-1"></i>
                    Payment Summary
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th>Subtotal</th>
                                <td class="text-end">₱<?= number_format($transaction['TotalAmount'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Payment Method</th>
                                <td class="text-end"><?= $transaction['PaymentMethod'] ?></td>
                            </tr>
                            <tr class="table-active">
                                <th>Total Amount</th>
                                <td class="text-end fw-bold">₱<?= number_format($transaction['TotalAmount'], 2) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-list me-1"></i>
            Purchased Items
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['ProductName']) ?></td>
                                <td><?= htmlspecialchars($item['Type']) ?></td>
                                <td><?= htmlspecialchars($item['Size']) ?></td>
                                <td class="text-end">₱<?= number_format($item['Price'], 2) ?></td>
                                <td class="text-end"><?= $item['Quantity'] ?></td>
                                <td class="text-end">₱<?= number_format($item['Price'] * $item['Quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="5" class="text-end">Grand Total</th>
                            <th class="text-end">₱<?= number_format($transaction['TotalAmount'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-4">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Transactions
        </a>
        <div>
            <a href="create.php?copy=<?= $transactionId ?>" class="btn btn-primary me-2">
                <i class="fas fa-copy me-1"></i> Duplicate Transaction
            </a>
            <button class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print Receipt
            </button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>