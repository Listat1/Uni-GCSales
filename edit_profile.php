<?php
// edit_profile.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?msg=auth");
    exit;
}

require_once 'includes/dbconnection.php';
$pdo = getDatabaseConnection();

// Fetch current data and roles for the dropdown
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$roles = $pdo->query("SELECT * FROM roles ORDER BY auth_level ASC")->fetchAll();
$pageTitle = "G&C Sells - Edit Profile";
$pageID = "edit-profile";
include 'includes/header.php';
?>

<main class="container mt-5">
    <header class="mb-4">
        <h2>Edit Profile</h2>
    </header>

    <form action="api/proc_edit_profile.php" method="POST" class="card p-4 shadow-sm">
        <fieldset>
            <legend class="visually-hidden">Personal Information</legend>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <?php if ($user['level'] >= 30): ?>
                <div class="mb-4 pt-3 border-top">
                    <label class="form-label fw-bold">Management: Change Account Role</label>
                    <select name="level" class="form-select">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['auth_level']; ?>" 
                                <?php echo ($role['auth_level'] == $user['level']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </fieldset>

        <footer class="d-flex justify-content-between mt-2">
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </footer>
    </form>
</main>