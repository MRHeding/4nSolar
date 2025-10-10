<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle the update
if ($_POST && isset($_POST['update_status'])) {
    try {
        $pdo->beginTransaction();
        
        // Update all projects with 'approved' status to 'completed'
        $stmt = $pdo->prepare("UPDATE solar_projects SET project_status = 'completed' WHERE project_status = 'approved'");
        $result = $stmt->execute();
        
        if ($result) {
            $affected_rows = $stmt->rowCount();
            $pdo->commit();
            $message = "Successfully updated $affected_rows projects from 'Approved' to 'Completed' status.";
        } else {
            $pdo->rollback();
            $error = "Failed to update project statuses.";
        }
        
    } catch (PDOException $e) {
        $pdo->rollback();
        $error = "Database error: " . $e->getMessage();
    }
}

// Get current count of approved projects
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM solar_projects WHERE project_status = 'approved'");
$stmt->execute();
$approved_count = $stmt->fetchColumn();

// Get current count of completed projects
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM solar_projects WHERE project_status = 'completed'");
$stmt->execute();
$completed_count = $stmt->fetchColumn();

$page_title = 'Update Project Status';
$content_start = true;
include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Update Project Status</h1>
        <p class="text-gray-600 dark:text-gray-400">Change all 'Approved' projects to 'Completed' status</p>
    </div>

    <?php if ($message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Current Status</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <div class="text-sm text-blue-600 dark:text-blue-400">Approved Projects</div>
                <div class="text-2xl font-bold text-blue-800 dark:text-blue-200"><?php echo $approved_count; ?></div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <div class="text-sm text-green-600 dark:text-green-400">Completed Projects</div>
                <div class="text-2xl font-bold text-green-800 dark:text-green-200"><?php echo $completed_count; ?></div>
            </div>
        </div>
    </div>

    <?php if ($approved_count > 0): ?>
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mr-3"></i>
            <div>
                <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Warning</h3>
                <p class="text-yellow-700 dark:text-yellow-300">
                    This action will change all <?php echo $approved_count; ?> approved projects to completed status. 
                    This action cannot be undone.
                </p>
            </div>
        </div>
    </div>

    <form method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Update Project Status</h3>
                <p class="text-gray-600 dark:text-gray-400">Change all approved projects to completed</p>
            </div>
            <button type="submit" name="update_status" 
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition"
                    onclick="return confirm('Are you sure you want to update all approved projects to completed status? This action cannot be undone.')">
                <i class="fas fa-check mr-2"></i>Update Status
            </button>
        </div>
    </form>
    <?php else: ?>
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-3"></i>
            <div>
                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">All Set!</h3>
                <p class="text-green-700 dark:text-green-300">
                    There are no approved projects to update. All projects are already in the correct status.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-6">
        <a href="projects.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Projects
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
