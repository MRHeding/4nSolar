<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Check if user has permission to manage users
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'includes/auth.php';

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'add':
            if (hasRole(ROLE_ADMIN)) {
                $username = $_POST['username'];
                $password = $_POST['password'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                $full_name = $_POST['full_name'];
                
                if (createUser($username, $password, $email, $role, $full_name)) {
                    $message = 'User created successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to create user. Username or email may already exist.';
                }
            } else {
                $error = 'Only administrators can create users.';
            }
            break;
            
        case 'edit':
            if ($user_id) {
                $username = $_POST['username'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                $full_name = $_POST['full_name'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // HR can only edit non-admin users
                if (hasRole(ROLE_HR)) {
                    $existing_user = getUserById($user_id);
                    if ($existing_user && $existing_user['role'] === ROLE_ADMIN) {
                        $error = 'HR users cannot edit administrator accounts.';
                        break;
                    }
                }
                
                if (updateUser($user_id, $username, $email, $role, $full_name, $is_active)) {
                    $message = 'User updated successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to update user.';
                }
            }
            break;
            
        case 'change_password':
            if ($user_id && isset($_POST['new_password'])) {
                $new_password = $_POST['new_password'];
                
                // HR can only change passwords for non-admin users
                if (hasRole(ROLE_HR)) {
                    $existing_user = getUserById($user_id);
                    if ($existing_user && $existing_user['role'] === ROLE_ADMIN) {
                        $error = 'HR users cannot change administrator passwords.';
                        break;
                    }
                }
                
                if (changePassword($user_id, $new_password)) {
                    $message = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password.';
                }
            }
            break;
    }
}

// Get data based on action
switch ($action) {
    case 'add':
    case 'edit':
        if ($action == 'edit' && $user_id) {
            $user = getUserById($user_id);
            if (!$user) {
                $error = 'User not found.';
                $action = 'list';
            }
        }
        break;
        
    default:
        $users = getUsers();
        break;
}

$page_title = 'User Management';
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
<!-- Users List -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
            <p class="text-gray-600">Manage system users and their permissions</p>
        </div>
        <?php if (hasRole(ROLE_ADMIN)): ?>
        <a href="?action=add" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            <i class="fas fa-plus mr-2"></i>Add User
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php 
                            switch($user['role']) {
                                case ROLE_ADMIN: echo 'bg-red-100 text-red-800'; break;
                                case ROLE_HR: echo 'bg-blue-100 text-blue-800'; break;
                                case ROLE_SALES: echo 'bg-green-100 text-green-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <?php
                        // Check if current user can edit this user
                        $can_edit = hasRole(ROLE_ADMIN) || (hasRole(ROLE_HR) && $user['role'] !== ROLE_ADMIN);
                        ?>
                        
                        <?php if ($can_edit): ?>
                        <a href="?action=edit&id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="showChangePasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" 
                                class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-key"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No users found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($action == 'add'): ?>
<!-- Add User Form -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Add New User</h1>
    <p class="text-gray-600">Create a new user account</p>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username <span class="text-red-500">*</span></label>
                    <input type="text" id="username" name="username" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="full_name" name="full_name" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                        <option value="">Select Role</option>
                        <option value="<?php echo ROLE_SALES; ?>">Sales Representative</option>
                        <option value="<?php echo ROLE_HR; ?>">HR</option>
                        <option value="<?php echo ROLE_ADMIN; ?>">Administrator</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" required minlength="6"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                <p class="text-sm text-gray-500 mt-1">Password must be at least 6 characters long.</p>
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="?" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action == 'edit' && isset($user)): ?>
<!-- Edit User Form -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Edit User</h1>
    <p class="text-gray-600">Update user information</p>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username <span class="text-red-500">*</span></label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo htmlspecialchars($user['username']); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="full_name" name="full_name" required
                           value="<?php echo htmlspecialchars($user['full_name']); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                        <option value="<?php echo ROLE_SALES; ?>" <?php echo $user['role'] == ROLE_SALES ? 'selected' : ''; ?>>Sales Representative</option>
                        <option value="<?php echo ROLE_HR; ?>" <?php echo $user['role'] == ROLE_HR ? 'selected' : ''; ?>>HR</option>
                        <?php if (hasRole(ROLE_ADMIN)): ?>
                        <option value="<?php echo ROLE_ADMIN; ?>" <?php echo $user['role'] == ROLE_ADMIN ? 'selected' : ''; ?>>Administrator</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="is_active" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>
                       class="h-4 w-4 text-solar-blue focus:ring-solar-blue border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-700">
                    Active User
                </label>
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="?" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<!-- Change Password Modal -->
<div id="password-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
            <form method="POST" id="password-form">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="id" id="password-user-id">
                
                <div class="mb-4">
                    <p id="password-user-name" class="text-sm text-gray-600 mb-2"></p>
                </div>
                
                <div class="mb-4">
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Password must be at least 6 characters long.</p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideChangePasswordModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showChangePasswordModal(userId, userName) {
    document.getElementById('password-user-id').value = userId;
    document.getElementById('password-user-name').textContent = 'Changing password for: ' + userName;
    document.getElementById('new_password').value = '';
    document.getElementById('password-modal').classList.remove('hidden');
}

function hideChangePasswordModal() {
    document.getElementById('password-modal').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
