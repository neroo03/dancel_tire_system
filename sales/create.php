<?php
include '../includes/auth.php';
include '../includes/config.php';

// Initialize variables
$products = $pdo->query("SELECT p.ProductID, p.Name, p.Price, p.Type, p.Size, i.QuantityAvailable 
                        FROM product p 
                        JOIN inventory i ON p.ProductID = i.ProductID 
                        WHERE i.QuantityAvailable > 0")->fetchAll();

$customers = $pdo->query("SELECT * FROM customer")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['payment_method'])) {
            throw new Exception("Payment method is required");
        }

        if (empty($_POST['products']) || !is_array($_POST['products'])) {
            throw new Exception("Please add at least one product");
        }

        $pdo->beginTransaction();
        
        // 1. Handle customer
        $customerId = $_POST['customer_id'] ?? null;
        if ($_POST['customer_type'] == 'new' && !empty($_POST['customer_name'])) {
            if (empty(trim($_POST['customer_name']))) {
                throw new Exception("Customer name is required");
            }
            
            $stmt = $pdo->prepare("INSERT INTO customer (Name, ContactInfo) VALUES (?, ?)");
            $stmt->execute([
                trim($_POST['customer_name']),
                trim($_POST['customer_contact'] ?? '')
            ]);
            $customerId = $pdo->lastInsertId();
        }

        // 2. Create transaction
        $totalAmount = array_reduce($_POST['products'], function($carry, $item) {
            return $carry + ($item['price'] * $item['quantity']);
        }, 0);

        $stmt = $pdo->prepare("INSERT INTO salestransaction 
                             (CustomerID, EmployeeID, Date, TotalAmount, PaymentMethod) 
                             VALUES (?, ?, NOW(), ?, ?)");
        $stmt->execute([
            $customerId,
            $_SESSION['employee_id'] ?? 1, // Default to admin if not set
            $totalAmount,
            $_POST['payment_method']
        ]);
        $transactionId = $pdo->lastInsertId();

        // 3. Process products
        foreach ($_POST['products'] as $product) {
            // Validate product data
            if (empty($product['id']) || empty($product['quantity']) || empty($product['price'])) {
                throw new Exception("Invalid product data");
            }

            // Check product exists and has enough stock
            $checkStmt = $pdo->prepare("SELECT QuantityAvailable FROM inventory WHERE ProductID = ?");
            $checkStmt->execute([$product['id']]);
            $stock = $checkStmt->fetchColumn();
            
            if ($stock === false) {
                throw new Exception("Product not found in inventory");
            }
            
            if ($stock < $product['quantity']) {
                throw new Exception("Not enough stock for product ID {$product['id']}");
            }

            // Insert sales detail
            $stmt = $pdo->prepare("INSERT INTO salesdetails 
                                 (TransactionID, ProductID, Quantity, Price) 
                                 VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $transactionId,
                $product['id'],
                $product['quantity'],
                $product['price']
            ]);
            
            // Update inventory
            $stmt = $pdo->prepare("UPDATE inventory 
                                 SET QuantityAvailable = QuantityAvailable - ? 
                                 WHERE ProductID = ?");
            $stmt->execute([$product['quantity'], $product['id']]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Transaction #$transactionId completed successfully!";
        header("Location: view.php?id=$transactionId");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error processing transaction: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">New Sales Transaction</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form id="salesForm" method="POST">
        <!-- Customer Section -->
        <div class="card mb-4">
            <div class="card-header">Customer Information</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="customer_type" id="existingCustomer" value="existing" checked>
                            <label class="form-check-label" for="existingCustomer">Existing Customer</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="customer_type" id="newCustomer" value="new">
                            <label class="form-check-label" for="newCustomer">New Customer</label>
                        </div>
                    </div>
                </div>
                
                <div id="existingCustomerFields">
                    <div class="mb-3">
                        <label class="form-label">Select Customer</label>
                        <select class="form-select" name="customer_id">
                            <option value="">Walk-in Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= htmlspecialchars($customer['CustomerID']) ?>">
                                    <?= htmlspecialchars($customer['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="newCustomerFields" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="customer_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Info</label>
                        <input type="text" class="form-control" name="customer_contact">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select class="form-select" name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                        <option value="Credit Card">Credit Card</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="card mb-4">
            <div class="card-header">Products</div>
            <div class="card-body">
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="productTable"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                <td><span id="grandTotal">₱0.00</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <select class="form-select" id="productSelect">
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= htmlspecialchars($product['ProductID']) ?>" 
                                        data-name="<?= htmlspecialchars($product['Name']) ?>"
                                        data-price="<?= htmlspecialchars($product['Price']) ?>"
                                        data-stock="<?= htmlspecialchars($product['QuantityAvailable']) ?>">
                                    <?= htmlspecialchars($product['Name']) ?> - 
                                    ₱<?= number_format($product['Price'], 2) ?> (Stock: <?= $product['QuantityAvailable'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" id="productQuantity" min="1" value="1">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" id="addProduct">Add Product</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="d-grid gap-2">
            <button type="submit" name="submit" class="btn btn-success btn-lg">
                <i class="fas fa-check-circle"></i> Complete Transaction
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle customer fields
    document.querySelectorAll('input[name="customer_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('existingCustomerFields').style.display = 
                this.value === 'existing' ? 'block' : 'none';
            document.getElementById('newCustomerFields').style.display = 
                this.value === 'new' ? 'block' : 'none';
            
            // Toggle required attribute for customer name
            if (this.value === 'new') {
                document.querySelector('input[name="customer_name"]').required = true;
            } else {
                document.querySelector('input[name="customer_name"]').required = false;
            }
        });
    });
    
    // Add product to table
    document.getElementById('addProduct').addEventListener('click', function() {
        const select = document.getElementById('productSelect');
        const productId = select.value;
        const productName = select.selectedOptions[0].dataset.name;
        const productPrice = parseFloat(select.selectedOptions[0].dataset.price);
        const productStock = parseInt(select.selectedOptions[0].dataset.stock);
        const quantity = parseInt(document.getElementById('productQuantity').value);
        
        if (!productId || isNaN(quantity) || quantity < 1) {
            alert('Please select a product and enter valid quantity');
            return;
        }
        
        if (quantity > productStock) {
            alert(`Only ${productStock} available in stock`);
            return;
        }
        
        // Add to table or update quantity if exists
        const existingRow = document.querySelector(`#productTable tr[data-product-id="${productId}"]`);
        if (existingRow) {
            const currentQty = parseInt(existingRow.querySelector('input').value);
            const newQty = currentQty + quantity;
            
            if (newQty > productStock) {
                alert(`Cannot add more than available stock (${productStock})`);
                return;
            }
            
            existingRow.querySelector('input').value = newQty;
            existingRow.querySelector('.product-total').textContent = 
                `₱${(newQty * productPrice).toFixed(2)}`;
        } else {
            const row = document.createElement('tr');
            row.dataset.productId = productId;
            row.innerHTML = `
                <td>${productName}</td>
                <td>₱${productPrice.toFixed(2)}</td>
                <td><input type="number" class="form-control product-qty" 
                           name="products[${productId}][quantity]" 
                           value="${quantity}" min="1" max="${productStock}" required></td>
                <td class="product-total">₱${(quantity * productPrice).toFixed(2)}</td>
                <td><button type="button" class="btn btn-danger btn-sm remove-product">Remove</button></td>
                <input type="hidden" name="products[${productId}][id]" value="${productId}">
                <input type="hidden" name="products[${productId}][price]" value="${productPrice}">
            `;
            document.getElementById('productTable').appendChild(row);
        }
        
        updateGrandTotal();
        select.value = '';
        document.getElementById('productQuantity').value = 1;
    });
    
    // Remove product
    document.getElementById('productTable').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-product')) {
            if (confirm('Are you sure you want to remove this product?')) {
                e.target.closest('tr').remove();
                updateGrandTotal();
            }
        }
    });
    
    // Update quantity
    document.getElementById('productTable').addEventListener('change', function(e) {
        if (e.target.classList.contains('product-qty')) {
            const row = e.target.closest('tr');
            const price = parseFloat(row.querySelector('input[name*="[price]"]').value);
            const quantity = parseInt(e.target.value);
            const max = parseInt(e.target.max);
            
            if (quantity > max) {
                alert(`Cannot exceed available stock (${max})`);
                e.target.value = max;
                return;
            }
            
            row.querySelector('.product-total').textContent = `₱${(price * max).toFixed(2)}`;
            updateGrandTotal();
        }
    });
    
    // Calculate grand total
    function updateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.product-total').forEach(cell => {
            total += parseFloat(cell.textContent.replace('₱', ''));
        });
        document.getElementById('grandTotal').textContent = `₱${total.toFixed(2)}`;
    }
    
    // Form validation before submission
    document.getElementById('salesForm').addEventListener('submit', function(e) {
        if (document.getElementById('productTable').rows.length === 0) {
            e.preventDefault();
            alert('Please add at least one product');
            return false;
        }
        
        return true;
    });
});
</script>

<?php include '../includes/footer.php'; ?>