# 🍽️ NutriPlan - Progressive Web App for Meal Planning

> A modern, fast, and beautiful meal planning application built with PHP, MySQL, and vanilla JavaScript. Plan meals, track nutrition, and manage shopping lists offline.

**Version:** 1.0  
**Status:** Production Ready ✅  
**Last Updated:** March 2026

---

## 🎯 Features

### Core Functionality
- ✅ **User Authentication** - Secure registration and login with BCRYPT hashing
- ✅ **Meal Discovery** - Search and filter 22+ African meals by category
- ✅ **Nutrition Tracking** - Complete nutritional information with visualization
- ✅ **Shopping Lists** - Auto-generate and manage shopping lists
- ✅ **User Profiles** - Avatar upload, personal settings, account management
- ✅ **Responsive Design** - Mobile-first, works on all devices
- ✅ **dark/Light Themes** - Toggle between dark and light UI
- ✅ **PWA Enabled** - Install as app, works offline

### Technical Features
- ✅ **AJAX Operations** - No page reloads, instant feedback
- ✅ **Real-time Validation** - Username availability checking
- ✅ **Service Worker** - Offline caching and background sync
- ✅ **Lazy Loading** - Optimized image rendering
- ✅ **Progressive Enhancement** - Works without JavaScript
- ✅ **Session-based Auth** - Secure user sessions
- ✅ **RESTful APIs** - 4 endpoint system for extensibility

---

## 📋 Prerequisites

Before installing, ensure you have:

| Component | Requirement |
|-----------|-------------|
| **Server** | XAMPP / LAMP / LEMP (with Apache, PHP 7.4+, MySQL 5.7+) |
| **Browser** | Chrome, Firefox, Safari, Edge (modern versions) |
| **Disk Space** | Minimum 100MB |
| **Memory** | Minimum 512MB RAM |
| **Network** | Internet connection for initial setup |

### Installation Links
- **XAMPP**: https://www.apachefriends.org/
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Modern Browser**: Any Chromium-based, Firefox 60+, Safari 12+

---

## 🚀 Quick Start (5 Minutes)

### Step 1: Download and Extract
```bash
# Clone or download the project
cd /path/to/your/projects
git clone <repository> nutriplan
# OR extract the zip file to create nutriplan folder
```

### Step 2: Place in Web Root
```bash
# Copy to XAMPP htdocs (Linux)
sudo cp -r nutriplan /opt/lampp/htdocs/

# On Windows: Copy nutriplan folder to C:\xampp\htdocs\
# On Mac: Copy nutriplan folder to /Applications/XAMPP/htdocs/
```

### Step 3: Start Services
```bash
# Start XAMPP (Linux)
sudo /opt/lampp/lampp start

# On Windows: Use XAMPP Control Panel (click Start next to Apache & MySQL)
# On Mac: Start from XAMPP Manager application
```

### Step 4: Initialize Database
```bash
# Open in browser:
http://localhost/nutriplan/seed.php

# Wait for success message, then delete seed.php for security:
rm /opt/lampp/htdocs/nutriplan/seed.php
```

### Step 5: Access Application
```bash
# Open in browser:
http://localhost/nutriplan/

# You should see the landing page
```

### Step 6: Create Account
1. Click "Get Started" or "Start Planning Free"
2. Register with first name, last name, username, email, password
3. Login with your credentials
4. Start planning meals!

---

## 📁 Project Structure

```
nutriplan/
│
├── 📄 Landing & Auth Pages
│   ├── index.php              ← Landing page (public)
│   ├── login.php              ← Login form
│   ├── register.php           ← Registration form
│   ├── logout.php             ← Logout handler
│
├── 🏠 Dashboard & Main Pages
│   ├── dashboard.php          ← User dashboard (authenticated)
│   ├── search.php             ← Meal search & filtering
│   ├── meal.php               ← Meal detail with nutrition
│   ├── shopping.php           ← Shopping list management
│   ├── profile.php            ← User profile settings
│   └── deregister.php         ← Account deletion handler
│
├── 🔌 API Endpoints (JSON)
│   └── api/
│       ├── search_api.php     ← Search meals by query/category
│       ├── shopping_action.php    ← Shopping list CRUD operations
│       ├── check_username.php     ← Username availability check
│       └── upload_avatar.php      ← Profile picture upload
│
├── 🛠️ Backend Utilities
│   └── includes/
│       ├── db_connect.php     ← Database connection & config
│       ├── auth_check.php     ← Authentication middleware
│       └── functions.php      ← 20+ helper functions
│
├── 🎨 Frontend Assets
│   └── assets/
│       ├── css/style.css      ← Design system (3500+ lines)
│       ├── js/main.js         ← All interactions (1200+ lines)
│       └── icons/             ← PWA icons
│
├── 🧩 Reusable Components
│   └── components/
│       └── sidebar.php        ← Navigation sidebar
│
├── 📤 User Uploads
│   └── uploads/
│       └── avatars/           ← Profile pictures storage
│
├── 📚 Documentation
│   ├── README.md              ← This file
│   ├── QUICKSTART.md          ← Quick reference
│   ├── API.md                 ← API documentation
│   ├── PROJECT_SUMMARY.md     ← Project overview
│   └── COMPLETION_CHECKLIST.md ← Feature checklist
│
├── 🔧 Configuration
│   ├── manifest.json          ← PWA manifest
│   ├── sw.js                  ← Service Worker
│   ├── .htaccess              ← Apache configuration
│   └── seed.php               ← Database initialization
│
└── 📊 Database (Created at Runtime)
    └── meal_planning_db
        ├── users              ← User accounts
        ├── categories         ← Meal categories
        ├── meals              ← 22 pre-loaded meals
        ├── nutrition          ← Nutritional data
        ├── shopping_lists     ← User shopping lists
        └── shopping_items     ← Items in lists
```

---

## 🎨 Design System

### Color Palette (CSS Variables)
```css
--primary: #60a5fa (Blue - Main accent)
--secondary: #8b5cf6 (Purple - Secondary)
--accent: #c084fc (Lighter purple - Accents)
--success: #34d399 (Green - Success states)
--warning: #fb923c (Orange - Warnings)
--danger: #f87171 (Red - Errors)
--info: #06b6d4 (Cyan - Information)
--surface: #1a1a2e (Dark background)
--surface-light: #16213e (Lighter surface)
--text-primary: #ffffff (Main text)
--text-secondary: #b4b4b4 (Secondary text)
```

### Typography Scale
```
--text-xs: 0.75rem (Small labels)
--text-sm: 0.875rem (Body small)
--text-base: 1rem (Body default)
--text-lg: 1.125rem (Slightly large)
--text-xl: 1.25rem (Headings)
--text-2xl: 1.5rem (Section headings)
--text-3xl: 2rem (Page headings)
--text-hero: 3.5rem (Hero text)
```

### Spacing System (8px Grid)
```
--sp-1: 0.5rem (4px)
--sp-2: 1rem (8px)
--sp-3: 1.5rem (12px)
--sp-4: 2rem (16px)
--sp-5: 2.5rem (20px)
... up to --sp-24: 6rem (48px)
```

### Component Styles
- **.btn-primary**: Gradient blue button with spring hover
- **.btn-outline**: Border-based button
- **.meal-card**: Meal preview with accent strip
- **.field**: Floating label input form
- **.chip**: Filter/tag button
- **.modal**: Centered dialog with backdrop
- **.toast**: Fixed notification popup
- **25+ Utility classes** for spacing, display, text formatting

---

## 🔐 Security Features

### Authentication & Authorization
- ✅ BCRYPT password hashing
- ✅ Session-based authentication
- ✅ Secure password validation
- ✅ HttpOnly session cookies
- ✅ CSRF token patterns

### Data Protection
- ✅ Input sanitization on all forms
- ✅ SQL injection prevention with mysqli
- ✅ XSS prevention via output encoding
- ✅ File upload validation
- ✅ Secure file permissions

### API Security
- ✅ Session requirement for protected endpoints
- ✅ Input validation before processing
- ✅ Error messages without SQL details
- ✅ Rate limiting ready (implement in production)

---

## 📱 Responsive Design

### Breakpoints
```
Mobile (< 480px)
  └─ Full-width layouts, single column
  └─ Hamburger menu for navigation
  └─ Touch-friendly button sizes (44px+)

Tablet (480px - 768px)
  └─ 2-column layouts possible
  └─ Sidebar partially visible
  └─ Balanced spacing

Desktop (768px - 1024px)
  └─ Full sidebar visible
  └─ 2-3 column layouts
  └─ Optimized spacing

Wide Screen (1024px+)
  └─ Maximum content width
  └─ Optimal reading width
  └─ Full feature set
```

### CSS Features
- **Flexbox**: For component layouts
- **CSS Grid**: For page layouts
- **Custom Properties**: For theming
- **clamp()**: Fluid typography
- **@media queries**: Responsive breakpoints

---

## 🎬 Key Interactions

### Search Meals
1. User types in search box
2. **300ms debounce** prevents excess API calls
3. Fetch to `/api/search_api.php` with query & category
4. Results render with **stagger animation** (60ms per item)
5. Click meal to view details

### Shopping List
1. Click "Add to Shopping List" on meal
2. AJAX POST to `/api/shopping_action.php`
3. Item appears in shopping list instantly
4. Toggle checkbox to mark purchased (opacity change)
5. Delete button removes item

### User Authentication
1. Register form validates all fields
2. Username checked against database (real-time)
3. Password strength meter shows requirements
4. On success: Account created, auto-logged in
5. Session stored, user redirected to dashboard

### Avatar Upload
1. Click profile image area
2. File dialog opens
3. Select image (JPG/PNG/GIF, max 2MB)
4. Preview shown before upload
5. FormData upload to `/api/upload_avatar.php`
6. Avatar stored and displayed app-wide

---

## 🚀 API Endpoints

### Search Meals
```
GET /api/search_api.php?q=query&cat=category_id

Response: {
  success: boolean,
  count: number,
  meals: [{ meal_id, meal_name, calories, proteins_g, ... }]
}
```

### Shopping Actions
```
POST /api/shopping_action.php

Actions:
- add: Add meal to list
- toggle: Mark item purchased
- delete: Remove item
- add_custom: Add custom item

Response: { success: boolean, message: string }
```

### Check Username
```
GET /api/check_username.php?username=value

Response: {
  available: boolean,
  message: string
}
```

### Upload Avatar
```
POST /api/upload_avatar.php

Body: FormData { avatar: file }

Response: {
  success: boolean,
  url: string (if successful)
}
```

---

## 🐛 Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| **Blank page** | Apache not running | Start XAMPP: `sudo /opt/lampp/lampp start` |
| **Database error** | seed.php not run | Visit `http://localhost/nutriplan/seed.php` |
| **Styles not loading** | CSS cache | Ctrl+Shift+Del → Clear cache → Refresh |
| **Can't login** | MySQL not running | Restart MySQL service |
| **Search not working** | API endpoint missing | Verify `/api/search_api.php` exists |
| **Upload fails** | Permission denied | `chmod 755 uploads/avatars/` |
| **Session expires** | Cookie settings | Check browser cookie settings |
| **500 error** | PHP error | Check `/opt/lampp/logs/php_error.log` |

### Debug Mode
Edit `includes/db_connect.php` to enable error reporting:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## 📚 File References

### Key Files to Know

**Database Setup**
- `seed.php` - Run once to initialize database
- `includes/db_connect.php` - Database connection + helpers

**Authentication**
- `login.php` - User login page
- `register.php` - User registration page
- `includes/auth_check.php` - Session verification

**Core Features**
- `search.php` - Meal search interface
- `meal.php` - Meal detail page
- `shopping.php` - Shopping list page
- `profile.php` - User profile

**Frontend Logic**
- `assets/js/main.js` - All JavaScript (22 sections)
- `assets/css/style.css` - All styling (3500+ lines)

**APIs**
- `api/search_api.php` - Meal search endpoint
- `api/shopping_action.php` - Shopping operations
- `api/check_username.php` - Username validation
- `api/upload_avatar.php` - File upload

---

## ✅ Testing Checklist

Before deploying, verify:

- [ ] Database initializes successfully
- [ ] Can register new account
- [ ] Can login with credentials
- [ ] Dashboard loads with stats
- [ ] Search works and shows results
- [ ] Can view meal details
- [ ] Can add meals to shopping list
- [ ] Can view shopping list
- [ ] Can toggle items purchased
- [ ] Can upload avatar
- [ ] Can logout and login again
- [ ] Theme toggle works
- [ ] Mobile layout responsive
- [ ] All links functional
- [ ] No console errors (F12)

---

## 🌐 PWA Features

### Installation
1. Visit application on compatible browser
2. Look for "Install" prompt (or click menu → "Install app")
3. Click "Install" or "Add to home screen"
4. App icon appears on device
5. Launch app like native application

### Offline Functionality
- Service Worker caches critical assets
- App workable without internet
- Shopping list operations queued offline
- Syncs when connection returns
- Fallback pages for offline state

### Browser Support
- ✅ Chrome/Chromium 57+
- ✅ Firefox 55+
- ✅ Safari 15.1+
- ✅ Edge 79+
- ❌ Internet Explorer (not supported)

---

## 🎓 Learning Value

This project demonstrates:
- ✅ Full-stack web development (PHP + JavaScript)
- ✅ Database design (normalized schema)
- ✅ User authentication (secure login)
- ✅ REST API design (JSON endpoints)
- ✅ Responsive design (mobile-first)
- ✅ Progressive Web Apps (offline-capable)
- ✅ Modern CSS (Grid, Flexbox, Variables)
- ✅ Vanilla JavaScript (ES6+)
- ✅ SQL & Database operations
- ✅ Security best practices

---

## 📞 Support & Documentation

### Built-in Documentation
- **README.md** - This comprehensive guide
- **QUICKSTART.md** - Quick reference (5 minutes)
- **API.md** - API endpoint documentation
- **PROJECT_SUMMARY.md** - Project overview
- **COMPLETION_CHECKLIST.md** - Feature checklist
- **Inline Comments** - Code documentation

### Getting Help
- Check **TROUBLESHOOTING** section above
- Review **API.md** for endpoint details
- Check browser console (F12) for errors
- Review PHP error logs in XAMPP

---

## 🎉 Success Indicators

You've successfully set up NutriPlan when:

1. ✅ Landing page loads at `http://localhost/nutriplan/`
2. ✅ Can register a new account
3. ✅ Can login with your account
4. ✅ Dashboard shows with stats and meals
5. ✅ Search functionality returns results
6. ✅ Can add meals to shopping list
7. ✅ Can view and manage shopping list
8. ✅ Can upload profile picture
9. ✅ Dark/light theme toggle works
10. ✅ Mobile layout is responsive

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | March 2026 | Initial release, all features complete |
| 0.9 | March 2026 | Beta with full feature set |
| 0.1 | March 2026 | Project initialization |

---

## 📄 License

This project is built as part of the **DSE-01-8686/2024 Final Year Project**.

---

## 👨‍💻 Developer

**Created by:** Mohamed Abdinasir  
**Project:** NutriPlan - Meal Planning & Diversification PWA  
**Institution:** Final Year Project - 2026

---

## 🙏 Acknowledgments

- East African culinary traditions (meals & recipes)
- Modern design practices (Linear, Stripe)
- Open-source community (PHP, MySQL, JavaScript)
- Progressive Web App standards (W3C)

---

**Thank you for using NutriPlan! Enjoy meal planning! 🍽️**

---

*Last Updated: March 2026*  
*Status: Production Ready ✅*  
*Documentation Version: 1.0*
