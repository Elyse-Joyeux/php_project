<?php

require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Get user settings
$settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id"));

// Update settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $theme = $_POST['theme'];
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    mysqli_query($conn, "UPDATE user_settings SET theme='$theme', notifications=$notifications WHERE user_id=$user_id");
    redirect('student_dashboard.php?msg=Settings updated');
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    mysqli_query($conn, "UPDATE users SET full_name='$full_name', email='$email' WHERE id=$user_id");
    $_SESSION['full_name'] = $full_name;
    redirect('student_dashboard.php?msg=Profile updated');
}

// Change password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE id=$user_id"));
    if (password_verify($current, $user['password'])) {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$new_hash' WHERE id=$user_id");
        redirect('student_dashboard.php?msg=Password changed successfully');
    } else {
        $error = "Current password is incorrect";
    }
}

// Get results
$results = mysqli_query($conn, "SELECT * FROM results WHERE student_id=$user_id ORDER BY year DESC, term DESC, created_at DESC");

// Get announcements
$announcements = mysqli_query($conn, "SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC LIMIT 10");

// Get logs
$logs = mysqli_query($conn, "SELECT * FROM logs WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        }
        body.dark {
            --bg-primary: #1a202c;
            --bg-secondary: #0f1419;
            --text-primary: #f7fafc;
            --text-secondary: #a0aec0;
            --border: #4a5568;
            --card-bg: #2d3748;
        }
        .container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
        }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; }
        .sidebar nav a {
            display: block;
            padding: 12px 15px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.2); }
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
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
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
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
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
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
        .grade-A { color: #48bb78; font-weight: bold; }
        .grade-B { color: #4299e1; font-weight: bold; }
        .grade-C { color: #ed8936; font-weight: bold; }
        .grade-D { color: #e53e3e; font-weight: bold; }
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
            z-index: 100;
        }
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #c6f6d5;
            color: #276749;
        }
        .alert-error {
            background: #fed7d7;
            color: #c53030;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h2><i class="fas fa-user-graduate"></i> Student Portal</h2>
        <nav>
            <a href="#" class="active" onclick="showSection('dashboard')"><i class="fas fa-home"></i> Dashboard</a>
            <a href="#" onclick="showSection('results')"><i class="fas fa-chart-line"></i> Results</a>
            <a href="#" onclick="showSection('announcements')"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="#" onclick="showSection('profile')"><i class="fas fa-user"></i> My Profile</a>
            <a href="#" onclick="showSection('settings')"><i class="fas fa-cog"></i> Settings</a>
            <a href="#" onclick="showSection('logs')"><i class="fas fa-history"></i> My Logs</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Dashboard Section -->
        <div id="dashboard-section">
            <h1>Welcome, <?php echo $_SESSION['full_name']; ?>!</h1>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-chart-line fa-2x"></i>
                    <h3>Results Published</h3>
                    <h2><?php echo mysqli_num_rows($results); ?></h2>
                </div>
                <div class="stat-card">
                    <i class="fas fa-bullhorn fa-2x"></i>
                    <h3>Announcements</h3>
                    <h2><?php echo mysqli_num_rows($announcements); ?></h2>
                </div>
            </div>
            
            <div class="card">
                <h3>Latest Results</h3>
                <?php
                $latest_results = mysqli_query($conn, "SELECT * FROM results WHERE student_id=$user_id ORDER BY created_at DESC LIMIT 5");
                if(mysqli_num_rows($latest_results) > 0):
                ?>
                <table>
                    <thead><tr><th>Subject</th><th>Exam</th><th>Marks</th><th>Grade</th></tr></thead>
                    <tbody>
                        <?php while($res = mysqli_fetch_assoc($latest_results)): 
                            $grade = '';
                            if($res['marks'] >= 80) $grade = 'A';
                            elseif($res['marks'] >= 70) $grade = 'B';
                            elseif($res['marks'] >= 60) $grade = 'C';
                            else $grade = 'D';
                        ?>
                        <tr>
                            <td><?php echo $res['subject']; ?></td>
                            <td><?php echo $res['exam_type']; ?></td>
                            <td><?php echo $res['marks']; ?></td>
                            <td class="grade-<?php echo $grade; ?>"><?php echo $grade; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No results available yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Results Section -->
        <div id="results-section" style="display: none;">
            <div class="card">
                <h3>All Results Slips</h3>
                <?php if(mysqli_num_rows($results) > 0): ?>
                <table>
                    <thead><tr><th>Subject</th><th>Exam Type</th><th>Marks</th><th>Grade</th><th>Term</th><th>Year</th></tr></thead>
                    <tbody>
                        <?php while($res = mysqli_fetch_assoc($results)): 
                            $grade = '';
                            if($res['marks'] >= 80) $grade = 'A';
                            elseif($res['marks'] >= 70) $grade = 'B';
                            elseif($res['marks'] >= 60) $grade = 'C';
                            else $grade = 'D';
                        ?>
                        <tr>
                            <td><?php echo $res['subject']; ?></td>
                            <td><?php echo $res['exam_type']; ?></td>
                            <td><?php echo $res['marks']; ?>/100</td>
                            <td class="grade-<?php echo $grade; ?>"><?php echo $grade; ?></td>
                            <td><?php echo $res['term']; ?></td>
                            <td><?php echo $res['year']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No results found. Contact your teacher.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Announcements Section -->
        <div id="announcements-section" style="display: none;">
            <div class="card">
                <h3>School Announcements</h3>
                <?php
                $announcements = mysqli_query($conn, "SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC");
                while($ann = mysqli_fetch_assoc($announcements)):
                ?>
                <div style="padding: 15px 0; border-bottom: 1px solid var(--border);">
                    <h4><?php echo htmlspecialchars($ann['title']); ?></h4>
                    <p style="font-size: 12px; color: var(--text-secondary);">Posted by <?php echo $ann['full_name']; ?> on <?php echo date('F j, Y', strtotime($ann['created_at'])); ?></p>
                    <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Profile Section -->
        <div id="profile-section" style="display: none;">
            <div class="card">
                <h3>My Profile</h3>
                <?php
                $user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));
                ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo $user_info['username']; ?>" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
            
            <div class="card">
                <h3>Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
        
        <!-- Settings Section -->
        <div id="settings-section" style="display: none;">
            <div class="card">
                <h3>Application Settings</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Theme Preference</label>
                        <select name="theme">
                            <option value="light" <?php echo ($settings['theme'] == 'light') ? 'selected' : ''; ?>>Light Mode</option>
                            <option value="dark" <?php echo ($settings['theme'] == 'dark') ? 'selected' : ''; ?>>Dark Mode</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="notifications" value="1" <?php echo $settings['notifications'] ? 'checked' : ''; ?>>
                            Enable Email Notifications
                        </label>
                    </div>
                    <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
        
        <!-- Logs Section -->
        <div id="logs-section" style="display: none;">
            <div class="card">
                <h3>My Activity Logs</h3>
                <table>
                    <thead><tr><th>Time</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead>
                    <tbody>
                        <?php while($log = mysqli_fetch_assoc($logs)): ?>
                        <tr>
                            <td><?php echo $log['created_at']; ?></td>
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

<button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>

<script>
    function showSection(section) {
        const sections = ['dashboard', 'results', 'announcements', 'profile', 'settings', 'logs'];
        sections.forEach(s => {
            document.getElementById(s + '-section').style.display = 'none';
        });
        document.getElementById(section + '-section').style.display = 'block';
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
</script>
</body>
</html>