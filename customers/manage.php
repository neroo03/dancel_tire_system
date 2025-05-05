<?php
include '../includes/auth.php';
include '../includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $name = $_POST['name'];
        $contactInfo = $_POST['contact_info'];
        
        if (isset($_POST['customer_id'])) {
            // Update existing customer
            $stmt = $pdo->prepare("UPDATE customer SET Name = ?, ContactInfo = ? WHERE CustomerID = ?");
            $stmt->execute([$name, $contactInfo, $_POST['customer_id']]);
            $_SESSION['success'] = "Customer updated successfully!";
        } else {
            // Add new customer
            $stmt = $pdo->prepare("INSERT INTO customer (Name, ContactInfo) VALUES (?, ?)");
            $stmt->execute([$name, $contactInfo]);
            $_SESSION['success'] = "Customer added successfully!";
        }
        
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get customer data if editing
$customer = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE CustomerID = ?");
    $stmt->execute([$_GET['id']]);
    $customer = $stmt->fetch();
}

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo isset($customer) ? 'Edit Customer' : 'Add New Customer'; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Customers</a></li>
        <li class="breadcrumb-item active"><?php echo isset($customer) ? 'Edit' : 'Add'; ?></li>
    </ol>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST">
                <?php if (isset($customer)): ?>
                    <input type="hidden" name="customer_id" value="<?php echo $customer['CustomerID']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Customer Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo isset($customer) ? htmlspecialchars($customer['Name']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="contact_info" class="form-label">Contact Information</label>
                    <input type="text" class="form-control" id="contact_info" name="contact_info" 
                           value="<?php echo isset($customer) ? htmlspecialchars($customer['ContactInfo']) : ''; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary"><?php echo isset($customer) ? 'Update Customer' : 'Add Customer'; ?></button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>