<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/suppliers.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$supplier_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'add':
            if (hasPermission([ROLE_ADMIN, ROLE_HR])) {
                $data = [
                    'name' => $_POST['name'],
                    'contact_person' => $_POST['contact_person'],
                    'email' => $_POST['email'],
                    'phone' => $_POST['phone'],
                    'address' => $_POST['address']
                ];
                
                if (addSupplier($data)) {
                    $message = 'Supplier added successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to add supplier.';
                }
            } else {
                $error = 'You do not have permission to add suppliers.';
            }
            break;
            
        case 'edit':
            if (hasPermission([ROLE_ADMIN, ROLE_HR]) && $supplier_id) {
                $data = [
                    'name' => $_POST['name'],
                    'contact_person' => $_POST['contact_person'],
                    'email' => $_POST['email'],
                    'phone' => $_POST['phone'],
                    'address' => $_POST['address']
                ];
                
                if (updateSupplier($supplier_id, $data)) {
                    $message = 'Supplier updated successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to update supplier.';
                }
            }
            break;
    }
}

// Handle delete action
if ($action == 'delete' && $supplier_id && hasPermission([ROLE_ADMIN])) {
    if (deleteSupplier($supplier_id)) {
        $message = 'Supplier deleted successfully!';
    } else {
        $error = 'Failed to delete supplier.';
    }
    $action = 'list';
}

// Get data based on action
switch ($action) {
    case 'add':
    case 'edit':
        if ($action == 'edit' && $supplier_id) {
            $supplier = getSupplier($supplier_id);
            if (!$supplier) {
                $error = 'Supplier not found.';
                $action = 'list';
            }
        }
        break;
        
    default:
        $suppliers = getSuppliers();
        break;
}

$page_title = 'Suppliers';
$content_start = true;
include 'includes/header.php';
?>

<?php if ($message): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 alert-auto-hide">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
<!-- Suppliers List -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Suppliers</h1>
            <p class="text-gray-600">Manage your equipment suppliers</p>
        </div>
        <?php if (hasPermission([ROLE_ADMIN, ROLE_HR])): ?>
        <a href="?action=add" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            <i class="fas fa-plus mr-2"></i>Add Supplier
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Suppliers Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (!empty($suppliers)): ?>
        <?php foreach ($suppliers as $supplier): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($supplier['name']); ?></h3>
                </div>
                <div class="flex space-x-2">
                    <?php if (hasPermission([ROLE_ADMIN, ROLE_HR])): ?>
                    <a href="?action=edit&id=<?php echo $supplier['id']; ?>" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-edit"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (hasRole(ROLE_ADMIN)): ?>
                    <a href="?action=delete&id=<?php echo $supplier['id']; ?>" class="text-red-600 hover:text-red-800" 
                       onclick="return confirmDelete('Are you sure you want to delete this supplier?')">
                        <i class="fas fa-trash"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="space-y-2 text-sm text-gray-600">
                <?php if ($supplier['contact_person']): ?>
                <p><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($supplier['contact_person']); ?></p>
                <?php endif; ?>
                
                <?php if ($supplier['email']): ?>
                <p><i class="fas fa-envelope mr-2"></i><a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($supplier['email']); ?></a></p>
                <?php endif; ?>
                
                <?php if ($supplier['phone']): ?>
                <p><i class="fas fa-phone mr-2"></i><a href="tel:<?php echo htmlspecialchars($supplier['phone']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($supplier['phone']); ?></a></p>
                <?php endif; ?>
                
                <?php if ($supplier['address']): ?>
                <p><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($supplier['address']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="mt-4 pt-4 border-t text-xs text-gray-500">
                Added: <?php echo date('M j, Y', strtotime($supplier['created_at'])); ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full">
            <div class="text-center py-12">
                <i class="fas fa-truck text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No suppliers found</h3>
                <p class="text-gray-500 mb-4">Get started by adding your first supplier.</p>
                <?php if (hasPermission([ROLE_ADMIN, ROLE_HR])): ?>
                <a href="?action=add" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
                    Add Supplier
                </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
<!-- Add/Edit Form -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800"><?php echo $action == 'add' ? 'Add New' : 'Edit'; ?> Supplier</h1>
    <p class="text-gray-600">Enter the supplier information</p>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Company Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" required
                       value="<?php echo isset($supplier) ? htmlspecialchars($supplier['name']) : ''; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div>
                <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-2">Contact Person</label>
                <input type="text" id="contact_person" name="contact_person"
                       value="<?php echo isset($supplier) ? htmlspecialchars($supplier['contact_person']) : ''; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo isset($supplier) ? htmlspecialchars($supplier['email']) : ''; ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo isset($supplier) ? htmlspecialchars($supplier['phone']) : ''; ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
            </div>
            
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                <textarea id="address" name="address" rows="3"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"><?php echo isset($supplier) ? htmlspecialchars($supplier['address']) : ''; ?></textarea>
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="?" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                    <?php echo $action == 'add' ? 'Add Supplier' : 'Update Supplier'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
