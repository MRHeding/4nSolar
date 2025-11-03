<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/projects.php';
require_once 'includes/inventory.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$project_id = $_GET['id'] ?? null;

// Check for success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'item_added':
            $message = 'Item added to project successfully!';
            break;
        case 'item_removed':
            $message = 'Item removed from project successfully!';
            break;
        case 'status_updated':
            $message = 'Project status updated successfully!';
            break;
    }
}

// Handle form submissions
if ($_POST) {
    // Check for specific form actions first
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_project':
                if (isset($_POST['project_id'])) {
                    $project_id = $_POST['project_id'];
                    if (deleteSolarProject($project_id)) {
                        $message = 'Project deleted successfully!';
                        // Redirect to avoid form resubmission
                        header("Location: ?success=project_deleted");
                        exit();
                    } else {
                        $error = 'Failed to delete project.';
                    }
                }
                break;
                
            case 'update_status':
                if (isset($_POST['project_id']) && isset($_POST['new_status'])) {
                    $project_id = $_POST['project_id'];
                    $new_status = $_POST['new_status'];
                    
                    // Validate status
                    $valid_statuses = ['draft', 'quoted', 'approved', 'in_progress', 'completed', 'cancelled'];
                    if (in_array($new_status, $valid_statuses)) {
                        error_log("Attempting to update project $project_id status to $new_status");
                        if (updateProjectStatus($project_id, $new_status)) {
                            $message = 'Project status updated successfully!';
                            error_log("Project status updated successfully for project $project_id");
                            // Redirect to avoid form resubmission
                            header("Location: ?action=view&id=$project_id&success=status_updated");
                            exit();
                        } else {
                            $error = 'Failed to update project status.';
                            error_log("Failed to update project status for project $project_id");
                        }
                    } else {
                        $error = 'Invalid project status.';
                        error_log("Invalid project status: $new_status");
                    }
                }
                break;
                
            case 'update_quantity':
                if (isset($_POST['project_item_id']) && isset($_POST['new_quantity'])) {
                    if (updateProjectItemQuantity($_POST['project_item_id'], $_POST['new_quantity'])) {
                        $message = 'Quantity updated successfully!';
                    } else {
                        $error = 'Failed to update quantity.';
                    }
                }
                break;
        }
    }
}



// Get data based on action
switch ($action) {
    case 'view':
        if ($project_id) {
            $project = getSolarProject($project_id);
            if (!$project) {
                $error = 'Project not found.';
                $action = 'list';
            } else {
                $inventory_items = getInventoryItems();
            }
        }
        break;
        
    default:
        $status_filter = $_GET['status'] ?? null;
        $projects = getSolarProjects($status_filter);
        break;
}

$page_title = 'Approved Projects';
$content_start = true;
include 'includes/header.php';
?>

<style>
/* Compact table styling for better fit */
.compact-table {
    table-layout: auto;
    width: 100%;
}

/* Ensure table columns have proper constraints */
.compact-table th:nth-child(1), .compact-table td:nth-child(1) { max-width: 200px; min-width: 150px; } /* Project */
.compact-table th:nth-child(2), .compact-table td:nth-child(2) { max-width: 220px; min-width: 180px; } /* Customer */
.compact-table th:nth-child(3), .compact-table td:nth-child(3) { width: 100px; } /* System Size */
.compact-table th:nth-child(4), .compact-table td:nth-child(4) { width: 110px; } /* Status */
.compact-table th:nth-child(5), .compact-table td:nth-child(5) { width: 120px; } /* Total Amount */
.compact-table th:nth-child(6), .compact-table td:nth-child(6) { max-width: 200px; min-width: 120px; } /* Remarks */
.compact-table th:nth-child(7), .compact-table td:nth-child(7) { width: 100px; } /* Created */
.compact-table th:nth-child(8), .compact-table td:nth-child(8) { width: 100px; } /* Actions */

/* Make table more compact */
.compact-table td {
    padding: 8px 10px;
    vertical-align: top;
}

.compact-table th {
    padding: 8px 10px;
}

/* Truncate long text with proper overflow handling */
.compact-table td > div {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

/* Allow customer column to wrap to 2 lines */
.compact-table td:nth-child(2) > div {
    white-space: normal;
    line-height: 1.4;
}

/* Project items table specific styling */
.project-items-table {
    table-layout: auto;
    width: 100%;
}

.project-items-table th:nth-child(1), .project-items-table td:nth-child(1) { min-width: 200px; } /* Item */
.project-items-table th:nth-child(2), .project-items-table td:nth-child(2) { width: 60px; }  /* Qty */
.project-items-table th:nth-child(3), .project-items-table td:nth-child(3) { width: 110px; } /* Unit Price */
.project-items-table th:nth-child(4), .project-items-table td:nth-child(4) { width: 110px; } /* Discount */
.project-items-table th:nth-child(5), .project-items-table td:nth-child(5) { width: 110px; } /* Total */

/* Responsive table wrapper */
.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Receipt Styling */
.company-header {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.invoice-table th,
.invoice-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

.invoice-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.total-section {
    border: 2px solid #1e40af;
    background-color: #f8f9fa;
}

/* Print styles */
@media print {
    body * {
        visibility: hidden;
    }
    #printableReceipt {
        display: block !important;
        visibility: visible;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    #printableReceipt * {
        visibility: visible;
    }
    .no-print {
        display: none !important;
    }
    body { 
        font-size: 12px;
        color: black !important;
        background: white !important;
        margin: 0;
        padding: 0;
    }
    .company-header {
        background: #1e40af !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
        page-break-inside: avoid !important;
        page-break-after: avoid !important;
    }
    .bg-blue-600 {
        background: #3b82f6 !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .bg-gray-50 {
        background: #f9fafb !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .bg-blue-50 {
        background: #eff6ff !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .bg-blue-100 {
        background: #dbeafe !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .bg-yellow-50 {
        background: #fefce8 !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    table {
        border-collapse: collapse !important;
        page-break-inside: auto !important;
    }
    thead {
        display: table-header-group !important;
    }
    tr {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
    }
    th, td {
        border: 1px solid #333 !important;
        padding: 6px 8px !important;
        font-size: 11px !important;
    }
    .rounded-lg {
        border-radius: 0 !important;
    }
    .max-w-4xl {
        max-width: 100% !important;
        margin: 0 !important;
    }
    .shadow-lg {
        box-shadow: none !important;
    }
    .overflow-hidden {
        overflow: visible !important;
    }
    .p-8 {
        padding: 10px !important;
    }
    .p-4 {
        padding: 6px !important;
    }
    .p-3 {
        padding: 4px !important;
    }
    .mb-6 {
        margin-bottom: 6px !important;
    }
    .mb-4 {
        margin-bottom: 4px !important;
    }
    .mb-2 {
        margin-bottom: 2px !important;
    }
    .mt-4 {
        margin-top: 4px !important;
    }
    .gap-8 {
        gap: 8px !important;
    }
    .text-3xl {
        font-size: 18px !important;
    }
    .text-2xl {
        font-size: 16px !important;
    }
    .text-lg {
        font-size: 13px !important;
    }
    .text-xl {
        font-size: 14px !important;
    }
    .text-sm {
        font-size: 11px !important;
    }
    .text-xs {
        font-size: 10px !important;
    }
    .customer-details-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 0.5rem !important;
        page-break-inside: avoid !important;
    }
    .header-section {
        page-break-inside: avoid !important;
        page-break-after: avoid !important;
    }
    .details-section {
        page-break-before: avoid !important;
    }
    .items-section {
        page-break-before: avoid !important;
        page-break-inside: auto !important;
        page-break-after: auto !important;
        margin-bottom: 8px !important;
    }
    .totals-section {
        page-break-before: auto !important;
        page-break-inside: avoid !important;
        margin-top: 8px !important;
        margin-bottom: 8px !important;
    }
    .invoice-table {
        page-break-inside: auto !important;
    }
    .total-section {
        page-break-inside: avoid !important;
        border: 2px solid #1e40af !important;
    }
    .border-t {
        page-break-before: avoid !important;
        margin-top: 6px !important;
        padding-top: 4px !important;
    }
    .w-64 {
        width: auto !important;
        max-width: 250px !important;
    }
    .grid-cols-2 {
        grid-template-columns: 1fr 1fr !important;
    }
}

@page {
    margin: 0.4in 0.5in;
    size: A4;
}
</style>

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

<!-- Delete Project Modal -->
<div id="deleteProjectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-900">Confirm Deletion</h3>
            <p class="text-sm text-gray-500 mt-2">Are you sure you want to delete the project: <span id="projectNameToDelete" class="font-medium"></span>?</p>
            <p class="text-sm text-red-500 mt-2">This action cannot be undone.</p>
        </div>
        <form id="deleteProjectForm" method="POST" action="">
            <input type="hidden" name="action" value="delete_project">
            <input type="hidden" name="project_id" id="projectIdToDelete">
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                    Delete Project
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmDeleteProject(projectId, projectName) {
        document.getElementById('projectIdToDelete').value = projectId;
        document.getElementById('projectNameToDelete').textContent = projectName;
        document.getElementById('deleteProjectModal').classList.remove('hidden');
        document.getElementById('deleteProjectModal').classList.add('flex');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteProjectModal').classList.remove('flex');
        document.getElementById('deleteProjectModal').classList.add('hidden');
    }
</script>

<?php if ($action == 'list'): ?>
<!-- Projects List -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Solar Projects</h1>
            <p class="text-gray-600 dark:text-gray-400">View approved quotes converted to projects</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <div class="flex flex-wrap gap-4 items-center">
        <div class="flex gap-2">
            <a href="?" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">All Projects</a>
            <a href="?status=draft" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">Draft</a>
            <a href="?status=quoted" class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-md hover:bg-yellow-200 transition">Quoted</a>
            <a href="?status=approved" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition">Approved</a>
            <a href="?status=in_progress" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 transition">Ongoing</a>
            <a href="?status=completed" class="px-4 py-2 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition">Completed</a>
            <a href="?status=cancelled" class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition">Cancelled</a>
        </div>
        <div class="ml-auto">
            <button onclick="exportToCSV('projects-table', 'projects')" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                <i class="fas fa-download mr-2"></i>Export CSV
            </button>
        </div>
    </div>
</div>

<!-- Projects Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow">
    <div class="table-wrapper">
        <table id="projects-table" class="min-w-full divide-y divide-gray-200 compact-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">System Size</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-3">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></div>
                    </td>
                    <td class="px-3 py-3">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($project['customer_name']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($project['customer_email']); ?></div>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $project['system_size_kw'] ? $project['system_size_kw'] . ' kW' : 'N/A'; ?>
                    </td>
                    <td class="px-3 py-3">
                        <span class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap
                            <?php 
                            switch($project['project_status']) {
                                case 'completed': echo 'bg-green-100 text-green-800'; break;
                                case 'quoted': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'approved': echo 'bg-blue-100 text-blue-800'; break;
                                case 'in_progress': echo 'bg-purple-100 text-purple-800'; break;
                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                default: echo 'bg-gray-100 text-gray-800 dark:text-gray-200';
                            }
                            ?>">
                            <?php 
                            $status_display = $project['project_status'];
                            if ($status_display === 'in_progress') {
                                $status_display = 'Ongoing';
                            } else {
                                $status_display = ucfirst(str_replace('_', ' ', $status_display));
                            }
                            echo $status_display;
                            ?>
                        </span>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($project['final_amount']); ?>
                    </td>
                    <td class="px-3 py-3 text-sm text-gray-500">
                        <?php if (!empty($project['remarks'])): ?>
                            <div title="<?php echo htmlspecialchars($project['remarks']); ?>">
                                <?php echo htmlspecialchars(substr($project['remarks'], 0, 40)) . (strlen($project['remarks']) > 40 ? '...' : ''); ?>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                    </td>
                    <td class="px-2 py-3 whitespace-nowrap text-center">
                        <div class="flex justify-center items-center space-x-1">
                            <a href="?action=view&id=<?php echo $project['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50 transition" title="View">
                                <i class="fas fa-eye text-sm"></i>
                            </a>
                            <button type="button" 
                               class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition" 
                               title="Delete" 
                               onclick="confirmDeleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['project_name'], ENT_QUOTES); ?>')">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No projects found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php if ($action == 'view' && isset($project)): ?>
<!-- View Project -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($project['project_name']); ?></h1>
            <p class="text-gray-600 dark:text-gray-400">Project #<?php echo $project['id']; ?> - <?php echo htmlspecialchars($project['customer_name']); ?></p>
        </div>
        <div class="space-x-2">
            <button onclick="printReceipt()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-print mr-2"></i>Print Receipt
            </button>
            <a href="?" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Project Items -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Project Items</h2>
            </div>
            
            <?php if (!empty($project['items'])): ?>
            <div class="table-wrapper">
                <table class="min-w-full divide-y divide-gray-200 compact-table project-items-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-0">Item</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Qty</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Unit Price</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Discount</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($project['items'] as $item): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo formatCurrency($item['unit_selling_price']); ?>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo formatCurrency($item['discount_amount']); ?>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                <?php echo formatCurrency($item['total_amount']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-8">No items added to this project yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Project Summary -->
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Project Summary</h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Status:</span>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php 
                            switch($project['project_status']) {
                                case 'completed': echo 'bg-green-100 text-green-800'; break;
                                case 'quoted': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'in_progress': echo 'bg-purple-100 text-purple-800'; break;
                                case 'approved': echo 'bg-blue-100 text-blue-800'; break;
                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                default: echo 'bg-gray-100 text-gray-800 dark:text-gray-200';
                            }
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $project['project_status'])); ?>
                        </span>
                        <button type="button" class="text-blue-600 hover:text-blue-800 text-sm" onclick="toggleStatusForm()" title="Change Status">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Status Update Form (Hidden by default) -->
                <div id="statusUpdateForm" class="hidden mt-3 p-3 bg-gray-50 rounded-lg">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                        <div class="flex items-center space-x-2">
                            <label for="new_status" class="text-sm font-medium text-gray-700">New Status:</label>
                            <select name="new_status" id="new_status" class="text-sm border border-gray-300 rounded px-2 py-1" required>
                                <option value="draft" <?php echo $project['project_status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="quoted" <?php echo $project['project_status'] === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                                <option value="approved" <?php echo $project['project_status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="in_progress" <?php echo $project['project_status'] === 'in_progress' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo $project['project_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $project['project_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                <i class="fas fa-save mr-1"></i>Update
                            </button>
                            <button type="button" onclick="toggleStatusForm()" class="px-3 py-1 bg-gray-500 text-white text-sm rounded hover:bg-gray-600">
                                <i class="fas fa-times mr-1"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="border-t pt-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                        <span class="font-medium"><?php echo formatCurrency($project['total_selling_price']); ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Discount:</span>
                        <span class="font-medium text-green-600">-<?php echo formatCurrency($project['total_discount']); ?></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2">
                        <span>Total:</span>
                        <span class="text-solar-blue"><?php echo formatCurrency($project['final_amount']); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($project['remarks'])): ?>
                <div class="border-t pt-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Remarks</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 p-3 rounded-md">
                        <?php echo nl2br(htmlspecialchars($project['remarks'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="border-t pt-4 text-sm text-gray-600 dark:text-gray-400">
                    <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($project['created_at'])); ?></p>
                    <p><strong>By:</strong> <?php echo htmlspecialchars($project['created_by_name']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Printable Receipt (Hidden on screen, visible on print) -->
<div id="printableReceipt" style="display: none;">
    <div class="quotation-content max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Company Header -->
        <div class="header-section company-header text-white p-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></h1>
                    <p class="text-blue-100 text-lg"><?php echo htmlspecialchars(getSystemSetting('company_subtitle', 'Solar Power Installation Services')); ?></p>
                    <p class="text-blue-100 text-lg">Your Trusted Partner in Solar Solutions</p>
                    <p class="text-blue-100 text-lg">NON VAT Reg TIN: 247-334-690-00001</p>
                    <div class="mt-4 text-sm text-blue-100">
                        <p>📧 info@4nsolar.com | 📞 +63 906 386 1728 | 📍 Zamboanga City, Philippines</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold mb-2">PROJECT RECEIPT</h2>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <p class="text-sm opacity-90">Receipt Number</p>
                        <p class="text-xl font-bold"><?php echo str_pad($project['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        <p class="text-sm opacity-90 mt-2"><?php echo date('F j, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Details -->
        <div class="details-section p-8">
            <div class="customer-details-grid grid grid-cols-2 gap-6 mb-4">
                <!-- Customer Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Bill To:</h3>
                    <div class="space-y-1">
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($project['customer_name']); ?></p>
                        <?php if ($project['customer_email']): ?>
                        <p class="text-gray-600 text-sm">📧 <?php echo htmlspecialchars($project['customer_email']); ?></p>
                        <?php endif; ?>
                        <?php if ($project['customer_phone']): ?>
                        <p class="text-gray-600 text-sm">📞 <?php echo htmlspecialchars($project['customer_phone']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Project Details:</h3>
                    <div class="space-y-1 text-sm">
                        <div>
                            <span class="text-gray-600">Project: </span>
                            <span class="font-medium"><?php echo htmlspecialchars($project['project_name']); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">System Size: </span>
                            <span class="font-medium"><?php echo $project['system_size_kw'] ? $project['system_size_kw'] . ' kW' : 'N/A'; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Status: </span>
                            <span class="font-medium capitalize 
                                <?php 
                                switch($project['project_status']) {
                                    case 'completed': echo 'text-green-600'; break;
                                    case 'in_progress': echo 'text-purple-600'; break;
                                    case 'approved': echo 'text-blue-600'; break;
                                    case 'cancelled': echo 'text-red-600'; break;
                                    default: echo 'text-gray-600';
                                }
                                ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $project['project_status'])); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600">Created: </span>
                            <span class="font-medium"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Prepared by: </span>
                            <span class="font-medium"><?php echo htmlspecialchars($project['created_by_name']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <?php if (!empty($project['items'])): ?>
            <div class="items-section mb-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Items:</h3>
                <div class="overflow-x-auto">
                    <table class="invoice-table w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-medium text-gray-700" style="width: 5%">#</th>
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-medium text-gray-700" style="width: 50%">Description</th>
                                <th class="border border-gray-300 px-4 py-3 text-center text-sm font-medium text-gray-700" style="width: 10%">Qty</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700" style="width: 15%">Unit Price</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700" style="width: 20%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($project['items'] as $index => $item): ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900"><?php echo $index + 1; ?></td>
                                <td class="border border-gray-300 px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($item['size_specification']); ?>
                                    </div>
                                    <?php if ($item['discount_amount'] > 0): ?>
                                    <div class="text-xs text-green-600">
                                        Discount: <?php echo formatCurrency($item['discount_amount']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center text-sm text-gray-900">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm text-gray-900">
                                    <?php echo formatCurrency($item['unit_selling_price']); ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-900">
                                    <?php echo formatCurrency($item['total_amount']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Totals -->
            <div class="totals-section flex justify-end mb-4">
                <div class="w-64">
                    <div class="total-section bg-gray-50 rounded-lg p-4 border-2">
                        <h4 class="text-sm font-bold text-gray-800 mb-2 text-center">RECEIPT SUMMARY</h4>
                        <div class="space-y-1">
                            <div class="flex justify-between text-sm border-b pb-1">
                                <span class="text-gray-600 font-medium">Subtotal:</span>
                                <span class="font-bold"><?php echo formatCurrency($project['total_selling_price']); ?></span>
                            </div>
                            <?php if ($project['total_discount'] > 0): ?>
                            <div class="flex justify-between text-sm text-green-600 border-b pb-1">
                                <span class="font-medium">Total Discount:</span>
                                <span class="font-bold">-<?php echo formatCurrency($project['total_discount']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between text-lg font-bold bg-blue-100 p-2 rounded">
                                <span class="text-gray-900">GRAND TOTAL:</span>
                                <span class="text-blue-600"><?php echo formatCurrency($project['final_amount']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($project['remarks'])): ?>
            <!-- Remarks -->
            <div class="border-t pt-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Remarks:</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                    <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($project['remarks'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment Information -->
            <div class="border-t pt-4 mb-4">
                <h4 class="text-sm font-semibold text-gray-800 mb-2">Payment Options:</h4>
                <div class="grid grid-cols-2 gap-4 text-xs text-gray-600">
                    <div>
                        <strong>Bank Transfer:</strong><br>
                        • <strong>BPI:</strong> 952926574 (Novie G. Mohadsa)<br>
                        • <strong>MayBank:</strong> 02015000094 (Novie G. Mohadsa)<br>
                        • <strong>PSBank:</strong> 193110014214 (Novie G. Mohadsa)
                    </div>
                    <div>
                        <strong>Digital Payments:</strong><br>
                        • <strong>GCASH:</strong> 09063861729<br>
                        • <strong>Maya:</strong> 09063861728<br>
                        <em>All payments via bank transfer preferred</em>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="border-t pt-4 mb-4 text-center">
                <div class="bg-blue-50 rounded p-3">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Contact Us</h3>
                    <div class="text-xs text-gray-600">
                        📧 info@4nsolar.com • 📞 +63 906 386 1728 • Mon-Sat 8AM-6PM
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="border-t pt-4">
                <div class="flex justify-between items-center">
                    <div class="text-left">
                        <div class="border-t border-gray-400 w-48 pt-1">
                            <p class="text-xs font-medium">Customer Signature / Date</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="border-t border-gray-400 w-48 pt-1">
                            <p class="text-xs font-medium">4nSolar Representative</p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($project['created_by_name']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-2 text-center text-xs text-gray-500">
                    Generated on <?php echo date('M j, Y \a\t g:i A'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function toggleStatusForm() {
    const form = document.getElementById('statusUpdateForm');
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
    } else {
        form.classList.add('hidden');
    }
}

function printReceipt() {
    // Simply trigger the print dialog
    // The CSS @media print rules will handle showing only the receipt
    window.print();
}
</script>

<?php include 'includes/footer.php'; ?>
