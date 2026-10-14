# SJPIICD Clinic System - Test Credentials

## 🔐 Login Information

All default passwords are: **`password`**

---

## 1️⃣ ADMIN Account
**Login Page:** `http://localhost/sjp2cd-clinic/admin/login.php`

| Field | Value |
|-------|-------|
| **Username** | `admin` |
| **Password** | `password` |
| **Email** | `admin@sjpiicd.edu.ph` |
| **Role** | Administrator |
| **Dashboard** | Admin Dashboard with full system access |

**Access:** Full admin panel, user management, patient records, medicines, reports, settings

### New Easy Admin Account
| Field | Value |
|-------|-------|
| **Username** | `admin2` |
| **Password** | `clinic123` |
| **Email** | `admin2@sjpiicd.edu.ph` |
| **Role** | Administrator |

---

## 2️⃣ NURSE Account
**Login Page:** `http://localhost/sjp2cd-clinic/admin/login.php`

| Field | Value |
|-------|-------|
| **Username** | `nurse` |
| **Password** | `password` |
| **Email** | `nurse@sjpiicd.edu.ph` |
| **Role** | Nurse |
| **Dashboard** | Nurse Dashboard with limited access |

**Access:** Patient records, medical records, medicines, nurse scheduling

---

## 3️⃣ STUDENT Account
**Login Page:** `http://localhost/sjp2cd-clinic/student/login.php`
**Admission Page:** `http://localhost/sjp2cd-clinic/student/signup.php`

### Pre-registered Test Accounts (for testing)

| Student Number | Full Name | Email | Password | Status |
|-------|-------|-------|-------|-------|
| `20231001` | Juan dela Cruz | juan@sjpiicd.edu.ph | `password` | Active |
| `20231002` | Maria Santos | maria@sjpiicd.edu.ph | `password` | Active |
| `20231003` | Pedro Garcia | pedro@sjpiicd.edu.ph | `password` | Active |

**Access:** View personal dashboard and medical records only

### How to Create a New Student Account (Admission)
1. Go to `http://localhost/sjp2cd-clinic/student/signup.php`
2. Fill in the registration form:
   - **Student Number:** 8-digit number (e.g., 20231004)
   - **Full Name:** Your complete name
   - **Email:** Your email address
   - **Password:** Create a secure password (minimum 6 characters)
   - **Confirm Password:** Re-enter your password
3. Click **Create Account**
4. You will be redirected to the login page
5. Use your student number and password to log in

---

## 📝 How to Test Each Dashboard

### Admin Dashboard
1. Go to `http://localhost/sjp2cd-clinic/admin/login.php`
2. Enter username: `admin` and password: `password`
3. Click **Sign In**
4. Access: Dashboard, Users, Patients, Medicines, Reports, Settings

### Nurse Dashboard
1. Go to `http://localhost/sjp2cd-clinic/admin/login.php`
2. Enter username: `nurse` and password: `password`
3. Click **Sign In**
4. Access: Limited admin functions, patient care features

### Student Dashboard - Using Pre-registered Account
1. Go to `http://localhost/sjp2cd-clinic/student/login.php`
2. Enter student number: `20231001` and password: `password`
3. Click **Sign In**
4. Access: Student portal, view your records

### Student Dashboard - Using New Admission Account
1. Go to `http://localhost/sjp2cd-clinic/student/signup.php`
2. Fill in the admission form with your details:
   - Student Number: Enter an 8-digit number
   - Full Name: Enter your name
   - Email: Enter your email
   - Password: Create a password
3. Click **Create Account**
4. You will be automatically redirected to `http://localhost/sjp2cd-clinic/student/login.php`
5. Use your new credentials to log in

---

## ✅ Quick Test Checklist

- [ ] Admin login works and shows full dashboard
- [ ] Nurse login works and shows nurse-specific features
- [ ] Student login works with pre-registered account (20231001 / password)
- [ ] Student admission/signup form works
- [ ] New student account can be created through admission form
- [ ] New student account can be used to log in
- [ ] Logout works on all three dashboards
- [ ] Each role has correct access levels

---

## 📋 Student Admission Process

### For New Students
Students can create their own accounts through the admission portal:

1. **Access the Admission Form**
   - URL: `http://localhost/sjp2cd-clinic/student/signup.php`

2. **Fill in Required Information**
   - Student Number (8 digits)
   - Full Name
   - Email Address
   - Create a Password

3. **Submit the Form**
   - Click "Create Account"
   - System validates the information
   - Account is created in the `student_accounts` table with "active" status

4. **Log In**
   - Go to `http://localhost/sjp2cd-clinic/student/login.php`
   - Use your student number and password
   - Access your student dashboard

### For Testing/Pre-registration
Three pre-registered student accounts are available for testing:

```
Student 1: 20231001 / Juan dela Cruz / password
Student 2: 20231002 / Maria Santos / password  
Student 3: 20231003 / Pedro Garcia / password
```

These accounts are created in the database during initial setup (see `database/clinic_db.sql`).

### Account Status
- **Active:** Student can log in and access the portal
- **Inactive:** Student account is disabled (set by admin)

---

## ⚠️ Security Notes

- Passwords are bcrypt hashed in the database
- Use `password_verify()` to check passwords (PHP)
- Consider changing default passwords in production
- Never hardcode credentials in your code
- Use prepared statements to prevent SQL injection

---

## 🔄 Change Password

To update credentials, modify the database:

```sql
-- Update admin password (bcrypt hash example)
UPDATE users SET password='$2y$10$...' WHERE username='admin';

-- Update student password
UPDATE student_accounts SET password='$2y$10$...' WHERE student_number='20231001';
```

Use PHP to hash: `password_hash('newpassword', PASSWORD_BCRYPT)`

