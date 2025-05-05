<?php
include '../includes/auth.php';
include '../includes/config.php';

// Handle customer deletion
if (isset($_GET['delete'])) {
    $customerId = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM customer WHERE CustomerID = ?");
        $stmt->execute([$customerId]);
        $_SESSION['success'] = "Customer deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting customer: " . $e->getMessage();
    }
    header("Location: index.php");
    exit;
}

// Get all customers
$customers = $pdo->query("SELECT * FROM customer ORDER BY Name")->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Customer Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Customers</li>
    </ol>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-users me-1"></i>
                    Customer List
                </div>
                <a href="manage.php" class="btn btn-primary btn-sm">Add New Customer</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['CustomerID']; ?></td>
                                <td><?php echo htmlspecialchars($customer['Name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['ContactInfo']); ?></td>
                                <td>
                                    <a href="manage.php?id=<?php echo $customer['CustomerID']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="index.php?delete=<?php echo $customer['CustomerID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this customer?')">Delete</a>
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