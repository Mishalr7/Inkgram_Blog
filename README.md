# ✒️ Inkgram

> **A thoughtful publishing platform and writing space for sharing essays, stories, and ideas.**

[![Live Demo](https://img.shields.io/badge/Live%20Demo-Online-brightgreen?style=for-the-badge&logo=google-chrome)](https://inkgram.infinityfreeapp.com)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)

---

## 🌐 Live Website

🔗 **[https://inkgram.infinityfreeapp.com](https://inkgram.infinityfreeapp.com)**

### Demo Accounts
| Role | Username | Password |
|---|---|---|
| **User** | `demo` | `demo1234` |


---

## ✨ Features

- **📖 Long-Form Reading:** Clean, distraction-free typography with interactive reading progress bar.
- **🏷️ Category Filtering & Search:** Real-time keyword search and category tag filtering with server-side pagination.
- **⚡ AJAX Username Check:** Live username availability validation during sign-up without page reload.
- **✍️ Essay Studio:** Rich writing experience with opening hooks (excerpts), character limits, and automatic URL slug generator.
- **🔐 Role-Based Access Control (RBAC):**
  - **Readers / Writers:** Write, update, and manage their personal essays.
  - **Admins:** Site-wide post management and dedicated user control panel.

---

## 🛡️ Production & Security Hardening

- **CSRF Defense:** Cryptographic token generation (`csrf_token`) verified on every state-changing POST request.
- **SQL Injection Prevention:** 100% parameterised prepared statements (`$conn->prepare`) with bound parameters across queries and searches.
- **Brute-Force Rate Limiting:** IP-based tracking via `login_attempts` table (blocks after 10 failed attempts within 15 minutes).
- **Session Hardening:** Strict cookie configurations (`HttpOnly`, `SameSite=Strict`, `Secure`) and session ID regeneration on authentication.
- **Password Security:** One-way adaptive hashing using `bcrypt` via PHP's `password_hash()` and `password_verify()`.
- **XSS Sanitization:** Global escaping wrapper `e()` applying `htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE)`.

---

## 📁 Project Architecture

```
Assignment2/
├── config.php                  # Database connection, security helpers, session initialization
├── database.sql                # Core schema (users, categories, posts) & sample dataset
├── production_migration.sql    # Security schema migration (login_attempts table)
│
├── header.php                  # Global HTML head and dynamic navigation bar
├── footer.php                  # Global page footer
├── style.css                   # Custom responsive styling
│
├── index.php                   # Home page with essay feed, search, and pagination
├── view_post.php               # Single essay reader with reading indicator
│
├── login.php                   # Secure authentication with brute-force protection
├── register.php                # User onboarding with AJAX validation
├── logout.php                  # Complete session and cookie destruction
├── change_password.php         # Authenticated credential updater
│
├── dashboard.php               # User workspace and administrator moderation console
├── create_post.php             # Essay authoring with slug generator
├── edit_post.php               # Essay updater with ownership verification
├── delete_post.php             # CSRF-protected essay deletion endpoint
├── delete_user.php             # Admin-only user removal with cascade deletion
└── check_username.php          # JSON endpoint powering AJAX username validation
```

---

## 🚀 Local Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Mishalr7/Inkgram_Blog.git
   ```

2. **Move to web server directory:**
   Copy the folder to your `htdocs` (XAMPP) or `www` (WampServer) directory.

3. **Import Database:**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Create a database named `inkwell_blog`.
   - Import `database.sql`, followed by `production_migration.sql`.

4. **Configure:**
   - Update credentials in `config.php` if different from default `root` / empty password.

5. **Run:**
   - Visit `http://localhost/Assignment2` in your browser.

---

## 🎓 Academic Info
- **Course:** Web Technologies
- **Institution:** Cochin University of Science and Technology (CUSAT)
- **Year:** 2026

