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

## 📝 License

MIT — free to use, modify, and distribute.
