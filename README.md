# UserSpace Admin Dashboard

A simple and modern **PHP + MySQL Admin Dashboard** for managing registered users.

## 🚀 Features

* 🔐 Admin authentication using sessions
* 📊 Dashboard statistics:

  * Total users
  * Users registered today
  * Gender distribution
* 🔍 Search functionality (by name, email, username)
* 👥 User listing with:

  * Full name
  * Username
  * Email
  * Gender
  * Join date
* 🗑️ Delete users with confirmation modal
* 🎨 Clean and responsive UI design

---

## 🛠️ Technologies Used

* **PHP (Core PHP)**
* **MySQL**
* **HTML5 & CSS3**
* **JavaScript (Vanilla)**

---

## 📂 Project Structure

```
/project-root
│── admin.php        # Main admin dashboard
│── login.html       # Admin login page
│── logout.php       # Logout handler
│── database.sql     # Database structure (you should add this)
```

---

## ⚙️ Setup Instructions

### 1. Clone the repository

```bash
git clone https://github.com/your-username/userspace-admin.git
cd userspace-admin
```

### 2. Setup Database

Create a MySQL database:

```
userSignUp
```

Create a `user` table:

```sql
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fname VARCHAR(50),
    lname VARCHAR(50),
    email VARCHAR(100),
    username VARCHAR(50),
    gender VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### 3. Configure Database Connection

In `admin.php`, update:

```php
$serverName = "localhost";
$dbUser     = "root";
$dbPass     = "your_password";
$db_name    = "userSignUp";
```

---

### 4. Run the Project

* Place the project in:

  * `htdocs` (XAMPP) or
  * `www` (WAMP)

* Start Apache & MySQL

* Open in browser:

```
http://localhost/admin.php
```

---

## 🔐 Authentication

* Admin session is required:

```php
$_SESSION['admin_id']
```

* If not logged in, user is redirected to:

```
login.html
```

---

## ⚠️ Security Notes

* Password is stored in plain text (⚠️ improve this using hashing)
* No CSRF protection (recommended to add)
* Use environment variables instead of hardcoding credentials

---

## 📸 Screenshots (Optional)

*Add screenshots of your dashboard here*

---

## 💡 Future Improvements

* ✅ Add user editing feature
* ✅ Add pagination
* ✅ Implement role-based access control
* ✅ Improve security (password hashing, CSRF protection)
* ✅ API integration

---

## 👨‍💻 Author

Elyse

---

## 📄 License

This project is open-source and available under the MIT License.
