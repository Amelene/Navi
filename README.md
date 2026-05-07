# Navi Shipping Management System

A dual-portal PHP application for managing shipping crew and administrative operations.

## 🚀 System Overview

This system has **TWO separate portals**:

1. **Admin Portal** - For administrators and staff management
2. **Crew Portal** - For crew members to access their information

---

## 📋 Prerequisites

- **XAMPP** or **WAMP** (Apache + MySQL + PHP)
- **MySQL Workbench** (optional, for database management)
- **Web Browser** (Chrome, Firefox, Edge, etc.)

---

## 🛠️ Installation Steps

### 1. Setup Project Files

1. Copy the entire `php-project` folder to your web server directory:
   - **XAMPP**: `C:/xampp/htdocs/php-project`
   - **WAMP**: `C:/wamp64/www/php-project`

### 2. Setup Database

1. Start **Apache** and **MySQL** from XAMPP/WAMP Control Panel

2. Open **phpMyAdmin** in your browser:
   ```
   http://localhost/phpmyadmin
   ```

3. Create the database and import schema:
   - Click "New" to create a database
   - Name it: `navi_shipping`
   - Click on the database name
   - Go to "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go" to import

### 3. Configure Database Connection (Local + Live Safe Setup)

The project now supports environment-based DB config, so puwede kang may **local DB** at **live DB** nang hindi agad napu-push ang live/local secrets sa git.

1. Copy `.env.example` to `.env`
2. Edit `.env` with your **local** credentials:
   ```env
   APP_ENV=local
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=navi_shipping
   DB_USER=root
   DB_PASS=
   DB_CHARSET=utf8mb4
   DB_DEBUG=true
   ```
3. `.env` is ignored by git (via `.gitignore`), so hindi masasama sa commits.
4. On live server, you can set server environment variables or keep production defaults in `config/database.php`.

Fallback behavior:
- If `APP_ENV=local` or localhost/CLI detected → local defaults
- Otherwise → live defaults
- Any `DB_*` in `.env` overrides defaults

---

## 🌐 Accessing the System

### **Admin Portal** (Root Directory)

**URL**: `http://localhost/php-project/login.php`

**Demo Credentials**:
- **Email**: `admin@navishipping.com`
- **Password**: `admin123`

**Features**:
- Dashboard with metrics
- Crew management
- Staff management
- Vessel management
- Document tracking
- Test results
- Reports

---

### **Crew Portal** (Crew Side)

**URL**: `http://localhost/php-project/crewside/login.php`

**Demo Credentials**:
- **Crew ID**: `CRW-2025-001`
- **Password**: `crew123`

**Other Test Accounts**:
| Crew ID | Name | Position | Vessel | Password |
|---------|------|----------|--------|----------|
| CRW-2025-001 | LUCAS CRUZ | MASTER | MV FUTURE 01 | crew123 |
| CRW-2025-002 | PEDRO GARCIA | CHIEF OFFICER | MV FUTURE 01 | crew123 |
| CRW-2025-003 | RAMON LOPEZ | CHIEF ENGINEER | MV FUTURE 02 | crew123 |
| CRW-2025-004 | JOSE MENDOZA | 2ND ENGINEER | MV OCEAN 06 | crew123 |

**Features**:
- Personal dashboard
- View assigned vessel
- View position and status
- Access documents (coming soon)
- Training records (coming soon)
- Medical records (coming soon)

---

## 📁 Project Structure

```
php-project/
│
├── index.php                 # Admin Dashboard
├── login.php                 # Admin Login
├── logout.php                # Admin Logout
├── crew.php                  # Crew Management (Admin)
├── staff.php                 # Staff Management (Admin)
├── tests.php                 # Test Management (Admin)
├── application.php           # Applications (Admin)
│
├── crewside/                 # CREW PORTAL
│   ├── index.php            # Crew Dashboard
│   ├── login.php            # Crew Login
│   ├── logout.php           # Crew Logout
│   └── login.css            # Crew Login Styles
│
├── config/
│   └── database.php         # Database Configuration
│
├── database/
│   ├── schema.sql           # Database Schema
│   └── MYSQL_WORKBENCH_SETUP.md
│
├── assets/
│   ├── css/                 # Stylesheets
│   └── image/               # Images & Logos
│
├── includes/
│   ├── sidebar.php          # Admin Sidebar
│   └── footer.php           # Footer
│
└── [other modules...]
```

---

## 🔐 Session Management

The system uses **separate sessions** for Admin and Crew:

### Admin Session Variables:
- `$_SESSION['logged_in']` - Admin login status
- `$_SESSION['user_id']` - User ID
- `$_SESSION['user_email']` - Email
- `$_SESSION['user_role']` - Role (admin/staff)

### Crew Session Variables:
- `$_SESSION['crew_logged_in']` - Crew login status
- `$_SESSION['crew_id']` - Crew ID
- `$_SESSION['crew_no']` - Crew Number
- `$_SESSION['crew_name']` - Full Name
- `$_SESSION['crew_position']` - Position
- `$_SESSION['crew_vessel']` - Assigned Vessel

---

## 🔄 How to Run Both Portals

### Option 1: Use Different Browser Tabs
1. Open **Tab 1**: Admin Portal (`http://localhost/php-project/login.php`)
2. Open **Tab 2**: Crew Portal (`http://localhost/php-project/crewside/login.php`)
3. Login to each portal with respective credentials

### Option 2: Use Different Browsers
1. **Chrome**: Login as Admin
2. **Firefox**: Login as Crew

### Option 3: Use Incognito/Private Mode
1. **Normal Window**: Login as Admin
2. **Incognito Window**: Login as Crew

---

## 🧪 Testing the System

### Test Admin Portal:
1. Go to: `http://localhost/php-project/login.php`
2. Login with admin credentials
3. Navigate through different modules
4. Test logout functionality

### Test Crew Portal:
1. Go to: `http://localhost/php-project/crewside/login.php`
2. Login with crew credentials
3. View dashboard and personal information
4. Test logout functionality

### Test Session Separation:
1. Login to Admin Portal in one tab
2. Login to Crew Portal in another tab
3. Verify both sessions work independently
4. Logout from one should not affect the other

---

## 🐛 Troubleshooting

### Database Connection Error
- Check if MySQL is running in XAMPP/WAMP
- Verify database name is `navi_shipping`
- Check credentials in `config/database.php`

### Page Not Found (404)
- Verify project is in correct directory
- Check URL spelling
- Ensure Apache is running

### Login Not Working
- Clear browser cache and cookies
- Check if database has user records
- Verify password hash in database

### Session Issues
- Clear browser cookies
- Check if session is started in PHP files
- Verify session variables are set correctly

---

## 📝 Adding New Users

### Add New Admin User:
```sql
INSERT INTO users (email, password, role, user_status) VALUES
('newemail@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
```

### Add New Crew User:
1. First, add to `users` table:
```sql
INSERT INTO users (email, password, role, user_status) VALUES
('crew005@navishipping.com', '$2y$10$rBV2nP0LhCJGXqJzYqKp5eF5o5fZJZH5xGqYqKp5eF5o5fZJZH5xG', 'crew', 'active');
```

2. Then, add to `crew_master` table:
```sql
INSERT INTO crew_master (auth_user_id, crew_no, first_name, last_name, role, user_status, nationality, birth_date, sex, civil_status, phone, address, vessel_id, department_id, position_id, crew_status) VALUES
(6, 'CRW-2025-005', 'JUAN', 'DELA CRUZ', 'crew', 'active', 'Filipino', '1990-01-01', 'Male', 'Single', '+63 999 999 9999', 'Manila, Philippines', 1, 1, 3, 'on_board');
```

---

## 🔒 Security Notes

- Change default passwords in production
- Use HTTPS in production environment
- Keep database credentials secure
- Regularly update PHP and MySQL
- Implement proper input validation
- Use prepared statements (already implemented)

---

## 📞 Support

For issues or questions:
1. Check the troubleshooting section
2. Review database schema in `database/schema.sql`
3. Check PHP error logs in XAMPP/WAMP

---

## 📄 License

This project is for educational/internal use.

---

## 🎯 Quick Start Summary

1. **Install XAMPP/WAMP** → Start Apache & MySQL
2. **Copy project** to `htdocs` or `www` folder
3. **Import database** from `database/schema.sql`
4. **Access Admin**: `http://localhost/php-project/login.php`
5. **Access Crew**: `http://localhost/php-project/crewside/login.php`
6. **Login** with demo credentials provided above

---

**Happy Shipping! ⚓🚢**
