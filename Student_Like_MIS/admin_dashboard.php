<?php

require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $role = $_POST['role'];
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        mysqli_query($conn, "INSERT INTO users (username, email, password, full_name, role) VALUES ('$username', '$email', '$password', '$full_name', '$role')");
        $user_id = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO user_settings (user_id) VALUES ($user_id)");
        logAction($_SESSION['user_id'], 'Add User', "Added user: $username");
        redirect('admin_dashboard.php?msg=User added successfully');
    }
    
    if (isset($_POST['edit_user'])) {
        $id = $_POST['user_id'];
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $role = $_POST['role'];
        
        mysqli_query($conn, "UPDATE users SET full_name='$full_name', email='$email', role='$role' WHERE id=$id");
        logAction($_SESSION['user_id'], 'Edit User', "Edited user ID: $id");
        redirect('admin_dashboard.php?msg=User updated successfully');
    }
    
    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role != 'admin'");
        logAction($_SESSION['user_id'], 'Delete User', "Deleted user ID: $id");
        redirect('admin_dashboard.php?msg=User deleted successfully');
    }
    
    if (isset($_POST['add_announcement'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        mysqli_query($conn, "INSERT INTO announcements (title, content, created_by) VALUES ('$title', '$content', {$_SESSION['user_id']})");
        redirect('admin_dashboard.php?msg=Announcement posted');
    }
}

// Get statistics
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='student'"))['count'];
$total_results = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM results"))['count'];
$users = mysqli_query($conn, "SELECT * FROM users WHERE role='student' ORDER BY created_at DESC");
$announcements = mysqli_query($conn, "SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s;
        }
        
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f7f9fc;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --sidebar: #667eea;
        }
        
        body.dark {
            --bg-primary: #1a202c;
            --bg-secondary: #0f1419;
            --text-primary: #f7fafc;
            --text-secondary: #a0aec0;
            --border: #4a5568;
            --card-bg: #2d3748;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .sidebar nav a {
            display: block;
            padding: 12px 15px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        
        .sidebar nav a:hover, .sidebar nav a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card h3 {
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            margin: 2px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-warning {
            background: #ed8936;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--card-bg);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 100;
        }
        
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #c6f6d5;
            color: #276749;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h2><i class="fas fa-school"></i> SMS Admin</h2>
        <nav>
            <a href="#" class="active" onclick="showSection('dashboard')"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="#" onclick="showSection('users')"><i class="fas fa-users"></i> Manage Students</a>
            <a href="#" onclick="showSection('results')"><i class="fas fa-chart-line"></i> Manage Results</a>
            <a href="#" onclick="showSection('announcements')"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="#" onclick="showSection('logs')"><i class="fas fa-history"></i> System Logs</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        
        <!-- Dashboard Section -->
        <div id="dashboard-section">
            <h1>Welcome, <?php echo $_SESSION['full_name']; ?>!</h1>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Results</h3>
                    <div class="number"><?php echo $total_results; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Recent Logins</h3>
                    <div class="number">24</div>
                </div>
            </div>
            
            <div class="card">
                <h3>Recent Announcements</h3>
                <?php while($ann = mysqli_fetch_assoc($announcements)): ?>
                    <div style="padding: 10px 0; border-bottom: 1px solid var(--border);">
                        <strong><?php echo htmlspecialchars($ann['title']); ?></strong>
                        <p style="font-size: 14px; color: var(--text-secondary);">By <?php echo $ann['full_name']; ?> | <?php echo $ann['created_at']; ?></p>
                        <p><?php echo htmlspecialchars(substr($ann['content'], 0, 100)); ?>...</p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Users Section -->
        <div id="users-section" style="display: none;">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Student Management</h3>
                    <button class="btn btn-primary" onclick="openModal('addUserModal')"><i class="fas fa-plus"></i> Add Student</button>
                </div>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php while($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <button class="btn btn-warning" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['role']; ?>')"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user?')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Results Section -->
        <div id="results-section" style="display: none;">
            <div class="card">
                <h3>Student Results Management</h3>
                <form method="POST" action="manage_results.php">
                    <div class="form-group">
                        <select name="student_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $students = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='student'");
                            while($s = mysqli_fetch_assoc($students)) {
                                echo "<option value='{$s['id']}'>{$s['full_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="text" name="subject" placeholder="Subject" required>
                    </div>
                    <div class="form-group">
                        <input type="number" name="marks" placeholder="Marks" required>
                    </div>
                    <div class="form-group">
                        <select name="exam_type">
                            <option>Mid-Term</option>
                            <option>Final</option>
                            <option>Quiz</option>
                        </select>
                    </div>
                    <button type="submit" name="add_result" class="btn btn-primary">Add Result</button>
                </form>
            </div>
            
            <div class="card">
                <h3>All Results</h3>
                <table>
                    <thead><tr><th>Student</th><th>Subject</th><th>Marks</th><th>Exam</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php
                        $all_results = mysqli_query($conn, "SELECT r.*, u.full_name FROM results r JOIN users u ON r.student_id = u.id ORDER BY r.created_at DESC");
                        while($res = mysqli_fetch_assoc($all_results)):
                        ?>
                        <tr>
                            <td><?php echo $res['full_name']; ?></td>
                            <td><?php echo $res['subject']; ?></td>
                            <td><?php echo $res['marks']; ?></td>
                            <td><?php echo $res['exam_type']; ?></td>
                            <td>
                                <form method="POST" action="manage_results.php" style="display:inline;">
                                    <input type="hidden" name="result_id" value="<?php echo $res['id']; ?>">
                                    <button type="submit" name="delete_result" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Announcements Section -->
        <div id="announcements-section" style="display: none;">
            <div class="card">
                <h3>Post Announcement</h3>
                <form method="POST">
                    <div class="form-group">
                        <input type="text" name="title" placeholder="Title" required>
                    </div>
                    <div class="form-group">
                        <textarea name="content" rows="4" placeholder="Content" required></textarea>
                    </div>
                    <button type="submit" name="add_announcement" class="btn btn-primary">Post</button>
                </form>
            </div>
        </div>
        
        <!-- Logs Section -->
        <div id="logs-section" style="display: none;">
            <div class="card">
                <h3>System Activity Logs</h3>
                <table>
                    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
                    <tbody>
                        <?php
                        $logs = mysqli_query($conn, "SELECT l.*, u.username FROM logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50");
                        while($log = mysqli_fetch_assoc($logs)):
                        ?>
                        <tr>
                            <td><?php echo $log['created_at']; ?></td>
                            <td><?php echo $log['username']; ?></td>
                            <td><?php echo $log['action']; ?></td>
                            <td><?php echo $log['details']; ?></td>
                            <td><?php echo $log['ip_address']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <h3>Add New Student</h3>
        <form method="POST">
            <div class="form-group"><input type="text" name="username" placeholder="Username" required></div>
            <div class="form-group"><input type="email" name="email" placeholder="Email" required></div>
            <div class="form-group"><input type="text" name="full_name" placeholder="Full Name" required></div>
            <div class="form-group"><select name="role"><option value="student">Student</option><option value="admin">Admin</option></select></div>
            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            <button type="button" class="btn" onclick="closeModal('addUserModal')">Cancel</button>
        </form>
    </div>
</div>

<div id="editUserModal" class="modal">
    <div class="modal-content">
        <h3>Edit User</h3>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group"><input type="text" name="full_name" id="edit_full_name" placeholder="Full Name" required></div>
            <div class="form-group"><input type="email" name="email" id="edit_email" placeholder="Email" required></div>
            <div class="form-group"><select name="role" id="edit_role"><option value="student">Student</option><option value="admin">Admin</option></select></div>
            <button type="submit" name="edit_user" class="btn btn-primary">Update</button>
            <button type="button" class="btn" onclick="closeModal('editUserModal')">Cancel</button>
        </form>
    </div>
</div>

<button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>

<script>
    function showSection(section) {
        document.getElementById('dashboard-section').style.display = 'none';
        document.getElementById('users-section').style.display = 'none';
        document.getElementById('results-section').style.display = 'none';
        document.getElementById('announcements-section').style.display = 'none';
        document.getElementById('logs-section').style.display = 'none';
        
        document.getElementById(section + '-section').style.display = 'block';
    }
    
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function editUser(id, name, email, role) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_full_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_role').value = role;
        openModal('editUserModal');
    }
    
    function toggleTheme() {
        document.body.classList.toggle('dark');
        const theme = document.body.classList.contains('dark') ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        fetch('save_theme.php?theme=' + theme);
    }
    
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>
</body>
</html>