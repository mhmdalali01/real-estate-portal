# 🏠 EstateHub — Real Estate Listing Portal

A fully-featured, production-quality real estate listing portal built with **Pure PHP**, **MySQL**, and **Vanilla JS**.

---

## ✨ Features

| Category | Features |
|---|---|
| **Listings** | Card grid with images, price, location, beds/baths/area; detail page with gallery slider, map embed, similar listings |
| **Search** | Filter by type, price range, location, bedrooms; sort by price/date; pagination |
| **Auth** | Register/Login/Logout with PHP sessions; bcrypt password hashing; role-based access control |
| **Roles** | Guest, User, Agent, Admin — each with appropriate permissions |
| **Favorites** | AJAX favorite toggle; saved properties page |
| **Inquiries** | Contact form on each listing; stored in DB; viewable by agent |
| **Agent Panel** | Dashboard with stats; create/edit/delete own listings; view inquiries |
| **Admin Panel** | Full platform overview; manage all listings (quick status change); manage users (role change, delete); view all inquiries |
| **Design** | Light mode only; Poppins font; card layout with shadows; responsive; animated hero with search bar; CSS transitions |

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.0+ (no frameworks)
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, Vanilla JS
- **Icons**: Lucide Icons (CDN)
- **Fonts**: Poppins (Google Fonts CDN)
- **Security**: PDO prepared statements, `password_hash()`, input sanitization, session-based auth

---

## 📁 Project Structure

```
real-estate-portal/
├── index.php                  # Homepage with hero + featured listings
├── config/
│   └── db.php                 # PDO connection + site constants
├── includes/
│   ├── functions.php          # Auth, helpers, utilities
│   ├── header.php             # HTML head + sticky navbar
│   └── footer.php             # Footer + scripts
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── profile.php
├── listings/
│   ├── search.php             # Browse + filter + paginate
│   ├── view.php               # Detail page: gallery, map, inquiry form
│   ├── create.php             # Agent: create listing with image upload
│   ├── edit.php               # Agent/Admin: edit listing
│   ├── delete.php             # Agent/Admin: delete listing
│   ├── favorites.php          # User: saved properties
│   └── toggle_favorite.php    # AJAX: add/remove favorite
├── agent/
│   ├── dashboard.php          # Agent stats + listing table
│   └── inquiries.php          # Agent's received inquiries
├── admin/
│   ├── dashboard.php          # Platform overview stats
│   ├── listings.php           # Manage all listings
│   ├── users.php              # Manage users & roles
│   ├── inquiries.php          # All inquiries overview
│   └── partials/sidebar.php   # Admin sidebar
├── assets/
│   ├── css/style.css          # Full responsive stylesheet
│   ├── js/main.js             # Gallery, AJAX favorites, nav, alerts
│   └── images/
│       └── placeholder.svg    # Fallback image
└── uploads/                   # User-uploaded property images
```

---

## ⚙️ Setup Instructions

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- A web server: Apache (XAMPP/WAMP/Laragon) or Nginx
- mod_rewrite enabled (for Apache)

### 1. Place the project

Copy the `real-estate-portal/` folder to your web server root:

```bash
# XAMPP / WAMP
C:/xampp/htdocs/real-estate-portal/

# Laragon
C:/laragon/www/real-estate-portal/
```

### 2. Configure the database connection

Open `config/db.php` and update:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'real_estate_portal');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('SITE_URL', 'http://localhost/real-estate-portal');
```

### 3. Run the setup script

Use your existing database setup or import the SQL manually if needed.

### 4. Ensure the uploads directory is writable

```bash
# Linux/Mac
chmod 775 uploads/

# Windows (XAMPP): right-click uploads/ → Properties → Security → Full Control
```

---

## 👥 Demo Accounts

All accounts use the password: **`password123`**

| Role  | Email                    |
|-------|--------------------------|
| Admin | admin@estatehub.com      |
| Agent | agent1@estatehub.com     |
| Agent | agent2@estatehub.com     |
| User  | user1@estatehub.com      |
| User  | user2@estatehub.com      |

---

## 🔒 Security Measures

- All database queries use **PDO prepared statements** — no raw string interpolation
- Passwords hashed with **`password_hash(PASSWORD_DEFAULT)`** (bcrypt)
- All user input sanitized with `strip_tags()` + `trim()`
- HTML output escaped with `htmlspecialchars()` everywhere
- Role-based access control enforced on every protected route
- File uploads validated by MIME type and size limit (5 MB)
- Session-based authentication with clean session destruction on logout

---

## 🎨 Design Tokens

| Token | Value | Usage |
|---|---|---|
| Deep Blue | `#1E3A5F` | Primary brand, navbar, buttons |
| Blue Mid  | `#2C5282` | Hover states, gradients |
| Terracotta| `#C65D3B` | Accent CTA, badges, icons |
| White     | `#FFFFFF` | Card backgrounds |
| Gray 50   | `#F8F9FA` | Page background |

---

## 🐛 Troubleshooting

| Issue | Solution |
|---|---|
| Blank page | Enable `display_errors = On` in `php.ini` |
| DB connect error | Check credentials in `config/db.php` |
| Images not uploading | Verify `uploads/` is writable by the web server |
| 404 on pages | Ensure `SITE_URL` matches your actual URL in `config/db.php` |
| Session issues | Ensure `session.save_path` is writable in `php.ini` |

---

## � Running the Application

### Step 1: Verify MySQL is Running
- Open MySQL/MariaDB service
- Ensure it's active and accessible on `localhost:3306`
- You can test this with your preferred MySQL client (HeidiSQL, phpMyAdmin, etc.)

### Step 2: Create the Database
Using HeidiSQL or MySQL command line:
```sql
CREATE DATABASE real_estate_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or simply verify the database name matches `DB_NAME` in `config/db.php`:
```php
define('DB_NAME', 'real_estate_portal');
```

### Step 3: Run the Setup Script
In PowerShell/Terminal in your project directory:
```powershell
php setup.php
```

This single command will:
- Create all tables: `users`, `listings`, `listing_images`, `favorites`, `inquiries`
- Seed 5 demo users with different roles (admin, agents, regular users)
- Seed 12 sample property listings with images
- Add sample inquiries and favorite relationships
- All demo accounts use password: `password123`

### Step 4: Start the PHP Development Server
In PowerShell/Terminal:
```powershell
php -S localhost:8000
```

Expected output:
```
[Thu Apr 17 XX:XX:XX 2026] PHP 8.5.1 Development Server started
Listening on http://localhost:8000
Document root is C:\Users\MYcom\real-estate-portal
Press Ctrl-C to quit.
```

### Step 5: Open the App
- Launch your browser
- Navigate to: **`http://localhost:8000`**
- The homepage should load with all 12 seeded listings and images

### Step 6: Test Features

#### As a Regular User:
1. Click **Register** → Select "👤 Buyer/Renter"
2. Create a new account with any email/password (min 8 chars)
3. Log in with your credentials
4. Browse listings with the search/filter interface
5. Click heart icon on any property to favorite it
6. View your favorites: click "❤️ My Favorites" in navbar
7. Click any listing → view details, gallery, map, and submit inquiry

#### As an Agent:
1. Click **Register** → Select "🏢 Agent"
2. Create an agent account
3. Log in → access **Agent Dashboard**
4. View stats: your listings, total inquiries, recent messages
5. Click **Create Listing** to post a new property
6. Fill in property details, upload images (up to 5MB each)
7. View **My Inquiries** to see buyer messages about your listings

#### As an Admin:
1. Log in with demo admin account:
   - Email: `admin@estatehub.com`
   - Password: `password123`
2. Access **Admin Dashboard**
3. View platform stats: total listings, users, inquiries
4. Go to **Listings** → manage all properties (quick status change: active/pending/removed)
5. Go to **Users** → change user roles, delete accounts
6. Go to **Inquiries** → view all platform inquiries

### Step 7: Stop the Server
Press **`Ctrl + C`** in PowerShell/Terminal to stop the development server

---

## 📊 Demo Accounts (Ready to Use)

All passwords: **`password123`**

| Role  | Email                    | Purpose |
|-------|--------------------------|---------|
| Admin | admin@estatehub.com      | Manage platform, all users, all listings |
| Agent | agent1@estatehub.com     | Create/edit own listings, view inquiries |
| Agent | agent2@estatehub.com     | Create/edit own listings, view inquiries |
| User  | user1@estatehub.com      | Browse, search, favorite, inquire |
| User  | user2@estatehub.com      | Browse, search, favorite, inquire |

---

## �📝 License

MIT — free to use, modify, and distribute.

