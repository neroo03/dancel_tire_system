<?php
include '../includes/auth.php';
include '../includes/config.php';

// Get filter parameters
$typeFilter = $_GET['type'] ?? '';
$lowStockOnly = isset($_GET['low_stock']);

// Build the query with filters
$query = "SELECT p.ProductID, p.Name, p.Type, p.Size, p.Price, 
                 i.QuantityAvailable, i.LastUpdated
          FROM product p
          JOIN inventory i ON p.ProductID = i.ProductID
          WHERE 1=1";

$params = [];

if (!empty($typeFilter)) {
    $query .= " AND p.Type = ?";
    $params[] = $typeFilter;
}

if ($lowStockOnly) {
    $query .= " AND i.QuantityAvailable < 5";
}

$query .= " ORDER BY p.Type, p.Name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$inventoryItems = $stmt->fetchAll();

// Get distinct product types for filter dropdown
$productTypes = $pdo->query("SELECT DISTINCT Type FROM product ORDER BY Type")->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Inventory Report</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Inventory Report</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filters
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="type" class="form-label">Product Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($productTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type['Type']) ?>" 
                                <?= ($type['Type'] === $typeFilter) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['Type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="low_stock" name="low_stock" 
                               <?= $lowStockOnly ? 'checked' : '' ?>>
                        <label class="form-check-label" for="low_stock">
                            Show Low Stock Only (<5)
                        </label>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="inventory.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-boxes me-1"></i>
                    Inventory Status
                </div>
                <button class="btn btn-success btn-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Report
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Unit Price</th>
                            <th>In Stock</th>
                            <th>Last Updated</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryItems as $item): ?>
                            <tr>
                                <td><?= $item['ProductID'] ?></td>
                                <td><?= htmlspecialchars($item['Name']) ?></td>
                                <td><?= htmlspecialchars($item['Type']) ?></td>
                                <td><?= htmlspecialchars($item['Size']) ?></td>
                                <td>₱<?= number_format($item['Price'], 2) ?></td>
                                <td class="<?= ($item['QuantityAvailable'] < 5) ? 'text-danger fw-bold' : '' ?>">
                                    <?= $item['QuantityAvailable'] ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($item['LastUpdated'])) ?></td>
                                <td>
                                    <?php if ($item['QuantityAvailable'] == 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif ($item['QuantityAvailable'] < 5): ?>
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inventoryItems)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No inventory items found matching your criteria</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-chart-pie me-1"></i>
                    Stock Summary by Type
                </div>
                <div class="card-body">
                    <canvas id="typeChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-chart-bar me-1"></i>
                    Stock Status Overview
                </div>
                <div class="card-body">
                    <canvas id="statusChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for charts
    const types = <?= json_encode(array_column($productTypes, 'Type')) ?>;
    const typeCounts = <?= json_encode(array_count_values(array_column($inventoryItems, 'Type'))) ?>;
    
    const statusData = {
        'In Stock': <?= count(array_filter($inventoryItems, fn($item) => $item['QuantityAvailable'] >= 5)) ?>,
        'Low Stock': <?= count(array_filter($inventoryItems, fn($item) => $item['QuantityAvailable'] > 0 && $item['QuantityAvailable'] < 5)) ?>,
        'Out of Stock': <?= count(array_filter($inventoryItems, fn($item) => $item['QuantityAvailable'] == 0)) ?>
    };

    // Type Distribution Pie Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'pie',
        data: {
            labels: types,
            datasets: [{
                data: types.map(type => typeCounts[type] || 0),
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
                    '#e74a3b', '#858796', '#5a5c69', '#2e59d9'
                ],
                hoverBackgroundColor: [
                    '#2e59d9', '#17a673', '#2c9faf', '#dda20a', 
                    '#be2617', '#656776', '#3a3d4a', '#1c3fd9'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                title: {
                    display: true,
                    text: 'Product Type Distribution'
                }
            }
        }
    });

    // Stock Status Bar Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                label: 'Number of Products',
                data: Object.values(statusData),
                backgroundColor: [
                    '#1cc88a', // In Stock - green
                    '#f6c23e', // Low Stock - yellow
                    '#e74a3b'  // Out of Stock - red
                ],
                hoverBackgroundColor: [
                    '#17a673', // In Stock - darker green
                    '#dda20a', // Low Stock - darker yellow
                    '#be2617'  // Out of Stock - darker red
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Stock Status Overview'
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>