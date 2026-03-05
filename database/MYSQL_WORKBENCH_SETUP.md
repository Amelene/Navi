# PAANO I-SETUP ANG DATABASE SA MYSQL WORKBENCH

## STEP 1: I-INSTALL ANG MYSQL AT MYSQL WORKBENCH

1. **Download MySQL Community Server**
   - Pumunta sa: https://dev.mysql.com/downloads/mysql/
   - I-download at i-install ang MySQL Server
   - I-set ang root password (tandaan ito!)

2. **Download MySQL Workbench**
   - Pumunta sa: https://dev.mysql.com/downloads/workbench/
   - I-download at i-install

## STEP 2: I-OPEN ANG MYSQL WORKBENCH

1. Buksan ang MySQL Workbench
2. I-click ang connection (usually "Local instance MySQL80" o "localhost")
3. I-enter ang root password na ginawa mo

## STEP 3: I-CREATE ANG DATABASE

### Option A: Gamit ang SQL Script (RECOMMENDED)

1. Sa MySQL Workbench, i-click ang **File** → **Open SQL Script**
2. Piliin ang file: `e:/php-project/database/schema.sql`
3. I-click ang **Execute** button (lightning icon) o press `Ctrl+Shift+Enter`
4. Makikita mo ang message: "Action Output: ... rows affected"
5. I-refresh ang Schemas panel (right-click → Refresh All)
6. Makikita mo na ang `navi_shipping` database

### Option B: Manual na Pag-create

1. I-click ang **Create New Schema** icon (cylinder with plus sign)
2. I-type ang schema name: `navi_shipping`
3. I-click **Apply** → **Apply** → **Finish**
4. I-copy paste ang buong content ng `schema.sql` sa query window
5. I-execute (lightning icon)

## STEP 4: I-VERIFY ANG TABLES

1. Sa left panel (Navigator), i-expand ang `navi_shipping` database
2. I-expand ang **Tables** folder
3. Dapat makita mo ang:
   - ✓ users
   - ✓ vessels
   - ✓ departments
   - ✓ positions
   - ✓ categories
   - ✓ staff
   - ✓ crew_master

4. I-expand ang **Views** folder
5. Dapat makita mo ang:
   - ✓ vw_staff_details
   - ✓ vw_crew_details

## STEP 5: I-CHECK ANG SAMPLE DATA

I-run ang mga query na ito para i-verify ang data:

```sql
-- Check users
SELECT * FROM users;

-- Check vessels
SELECT * FROM vessels;

-- Check departments
SELECT * FROM departments;

-- Check positions
SELECT * FROM positions;

-- Check staff
SELECT * FROM vw_staff_details;

-- Check crew
SELECT * FROM vw_crew_details;
```

## STEP 6: I-UPDATE ANG PHP CONFIG

1. Buksan ang file: `config/database.php`
2. I-update ang mga values:

```php
define('DB_HOST', 'localhost');        // MySQL server
define('DB_PORT', '3306');             // MySQL port
define('DB_NAME', 'navi_shipping');    // Database name
define('DB_USER', 'root');             // Your MySQL username
define('DB_PASS', 'your_password');    // Your MySQL password
```

**IMPORTANTE:** I-replace ang `'your_password'` ng actual password mo!

## STEP 7: I-TEST ANG CONNECTION

1. I-create ang test file: `test_connection.php`
2. I-run sa browser: `http://localhost/php-project/test_connection.php`
3. Dapat makita mo: "✓ Database connection successful!"

## COMMON ISSUES AT SOLUTIONS

### Issue 1: "Access denied for user 'root'@'localhost'"
**Solution:** Mali ang password. I-check ang password sa `config/database.php`

### Issue 2: "Unknown database 'navi_shipping'"
**Solution:** Hindi pa na-create ang database. Ulitin ang Step 3.

### Issue 3: "Could not find driver"
**Solution:** I-enable ang PDO MySQL extension sa `php.ini`:
```
extension=pdo_mysql
```

### Issue 4: Port 3306 is already in use
**Solution:** May running na MySQL service. I-stop muna ang iba o i-change ang port.

## MYSQL WORKBENCH SHORTCUTS

- **Execute Query:** `Ctrl + Enter` (current statement)
- **Execute All:** `Ctrl + Shift + Enter`
- **Format Query:** `Ctrl + B`
- **Comment Line:** `Ctrl + /`
- **New Query Tab:** `Ctrl + T`

## NEXT STEPS

After successful setup:

1. ✓ Database created
2. ✓ Tables created
3. ✓ Sample data inserted
4. ✓ PHP config updated
5. → Test ang PHP pages (staff.php, crew.php)
6. → I-update ang login.php para gumamit ng database

## ADDITIONAL NOTES

### Default Admin Login
- Email: `admin@navishipping.com`
- Password: `admin123`

### Sample Data Included
- 3 Staff members
- 4 Crew members
- 12 Vessels
- 7 Departments
- 20 Positions

### Database Views
Ginawa ko ng views para mas madali ang queries:
- `vw_staff_details` - Staff with department/position names
- `vw_crew_details` - Crew with vessel/department/position names

## SUPPORT

Kung may problema, i-check ang:
1. MySQL service is running
2. Correct password sa config
3. PHP PDO extension is enabled
4. Port 3306 is not blocked by firewall
