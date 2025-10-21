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
    table-layout: fixed;
}

.compact-table th:last-child,
.compact-table td:last-child {
    width: 140px;
}

/* Ensure table columns have proper widths */
.compact-table th:nth-child(1), .compact-table td:nth-child(1) { width: 200px; } /* Project */
.compact-table th:nth-child(2), .compact-table td:nth-child(2) { width: 200px; } /* Customer */
.compact-table th:nth-child(3), .compact-table td:nth-child(3) { width: 100px; } /* System Size */
.compact-table th:nth-child(4), .compact-table td:nth-child(4) { width: 100px; } /* Status */
.compact-table th:nth-child(5), .compact-table td:nth-child(5) { width: 120px; } /* Total Amount */
.compact-table th:nth-child(6), .compact-table td:nth-child(6) { width: 150px; } /* Remarks */
.compact-table th:nth-child(7), .compact-table td:nth-child(7) { width: 100px; } /* Created */
.compact-table th:nth-child(8), .compact-table td:nth-child(8) { width: 140px; } /* Actions */

/* Make table more compact */
.compact-table td {
    padding: 8px 12px;
}

.compact-table th {
    padding: 8px 12px;
}

/* Truncate long text */
.compact-table td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Project items table specific styling */
.project-items-table {
    table-layout: fixed;
}

.project-items-table th:nth-child(1), .project-items-table td:nth-child(1) { width: 300px; } /* Item */
.project-items-table th:nth-child(2), .project-items-table td:nth-child(2) { width: 60px; }  /* Qty */
.project-items-table th:nth-child(3), .project-items-table td:nth-child(3) { width: 100px; } /* Unit Price */
.project-items-table th:nth-child(4), .project-items-table td:nth-child(4) { width: 100px; } /* Discount */
.project-items-table th:nth-child(5), .project-items-table td:nth-child(5) { width: 100px; } /* Total */
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
    <div>
        <table id="projects-table" class="min-w-full divide-y divide-gray-200 compact-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">System Size</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($project['customer_name']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($project['customer_email']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $project['system_size_kw'] ? $project['system_size_kw'] . ' kW' : 'N/A'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($project['final_amount']); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                        <?php if (!empty($project['remarks'])): ?>
                            <div class="truncate" title="<?php echo htmlspecialchars($project['remarks']); ?>">
                                <?php echo htmlspecialchars(substr($project['remarks'], 0, 50)) . (strlen($project['remarks']) > 50 ? '...' : ''); ?>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                    </td>
                    <td class="px-3 py-4 whitespace-nowrap text-center">
                        <div class="flex justify-center items-center space-x-2">
                            <a href="?action=view&id=<?php echo $project['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 p-1.5 rounded hover:bg-blue-50 transition" title="View">
                                <i class="fas fa-eye text-sm"></i>
                            </a>
                            <button type="button" 
                               class="text-red-600 hover:text-red-900 p-1.5 rounded hover:bg-red-50 transition" 
                               title="Delete" 
                               onclick="confirmDeleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['project_name'], ENT_QUOTES); ?>')">
                                <i class="fas fa-trash-alt text-sm"></i>
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
            <div>
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
</script>

<?php include 'includes/footer.php'; ?>
