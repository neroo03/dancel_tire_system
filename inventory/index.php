<?php
include '../includes/auth.php';
include '../includes/config.php';

// Handle inventory updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_inventory'])) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['quantities'] as $productId => $quantity) {
            $stmt = $pdo->prepare("UPDATE inventory SET QuantityAvailable = ?, LastUpdated = CURDATE() WHERE ProductID = ?");
            $stmt->execute([$quantity, $productId]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Inventory updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating inventory: " . $e->getMessage();
    }
}

// Get inventory data
$inventory = $pdo->query("SELECT p.ProductID, p.Name, p.Type, p.Size, i.QuantityAvailable, i.LastUpdated 
                         FROM product p 
                         JOIN inventory i ON p.ProductID = i.ProductID 
                         ORDER BY p.Name")->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Inventory Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Inventory</li>
    </ol>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Current Inventory
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Quantity Available</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['Name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($item['Size']); ?></td>
                                    <td>
                                        <input type="number" class="form-control" 
                                               name="quantities[<?php echo $item['ProductID']; ?>]" 
                                               value="<?php echo $item['QuantityAvailable']; ?>" min="0">
                                    </td>
                                    <td><?php echo $item['LastUpdated']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="update_inventory" class="btn btn-primary">Update Inventory</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>