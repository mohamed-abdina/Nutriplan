# 🚀 NutriPlan - Quick Start Guide

## 5-Minute Setup

### 1. Download & Place
- Copy the `nutriplan` folder to your XAMPP `htdocs` directory
  - Windows: `C:\xampp\htdocs\`
  - Mac: `/Applications/XAMPP/htdocs/`
  - Linux: `/opt/lampp/htdocs/`

### 2. Start Services
- Open XAMPP Control Panel
- Click **START** on Apache
- Click **START** on MySQL

### 3. Initialize Database
- Open browser: `http://localhost/nutriplan/seed.php`
- Wait for success message
- **Delete seed.php** from the folder (for security)

### 4. Access App
- Go to: `http://localhost/nutriplan/`
- Register a new account
- Start planning meals!

---

## 🧪 Testing Features

### Test Users
Create your own account via the registration page to get started.

### Sample Meals
22 meals are pre-loaded with full nutrition data:
- **Breakfast**: Ugali & Sukuma, Mandazi, Chapati & Beans, Oatmeal, Eggs & Toast
- **Lunch**: Ugali & Nyama, Rice & Stew, Githeri, Samosa & Chai, Lentil Soup
- **Supper**: Nyama Choma, Vegetable Curry, Fish & Chips, Ugali & Greens, Pilau
- **Plus more**: Kachumbari Salad, Muamba Stew, Matoke, Sukuma Wiki Fry, Bean Soup, Coconut Rice, Roasted Chicken

### Test Workflow
1. **Register** → Create new account
2. **Search Meals** → Find breakfast options
3. **Add to List** → Click "+ Add" on any meal
4. **View Shopping** → See generated shopping list
5. **Check Off** → Mark items as purchased
6. **Profile** → Update user info

---

## 📖 Folder Structure Overview

```
nutriplan/
├── 📄 index.php                → Landing page (public)
├── 📄 login.php                → Login (public)
├── 📄 register.php             → Signup (public)
├── 🔐 dashboard.php            → Home (authenticated)
├── 🔐 search.php               → Search meals
├── 🔐 meal.php                 → Meal details
├── 🔐 shopping.php             → Shopping list
├── 🔐 profile.php              → User settings
├── 🔍 api/                     → API endpoints
│   ├── search_api.php          → Meal search
│   ├── shopping_action.php     → List operations
│   ├── check_username.php      → Availability check
│   └── upload_avatar.php       → Profile picture
├── 🛠 includes/                → PHP utilities
│   ├── db_connect.php          → Database connection
│   ├── auth_check.php          → Login check
│   └── functions.php           → Helper functions
├── 🎨 assets/                  → Static files
│   ├── css/style.css           → All styles (production-ready)
│   └── js/main.js              → All interactions
├── 📦 uploads/avatars/         → User photos
├── 📋 manifest.json            → PWA config
└── ⚙️ sw.js                    → Service Worker

🔐 = Requires login
```

---

## 🎓 Learning Path

### Week 1: Database & Backend
- [ ] Review `includes/db_connect.php` - database basics
- [ ] Study `includes/functions.php` - helper functions
- [ ] Examine `api/search_api.php` - AJAX endpoints

### Week 2: Authentication
- [ ] Flow: register.php → login.php → dashboard.php
- [ ] `password_hash()` for security
- [ ] Session management patterns

### Week 3: Frontend
- [ ] CSS custom properties in `style.css`
- [ ] Responsive design patterns (mobile-first)
- [ ] Component library structure

### Week 4: JavaScript
- [ ] Event handlers in `main.js`
- [ ] Fetch API for AJAX
- [ ] DOM manipulation patterns

### Week 5: PWA & Deployment
- [ ] `manifest.json` configuration
- [ ] `sw.js` service worker logic
- [ ] Offline functionality

---

## ⚙️ Configuration

### Change Database Credentials
Edit `includes/db_connect.php`:
```php
$db_host = 'localhost';
$db_user = 'your_username';
$db_password = 'your_password';
$db_name = 'meal_planning_db';
```

### Add New Meals
Edit `seed.php`, add to `$meals` array:
```php
['Meal Name', 1, 'Description', 30, '🍱', 'Category']
```

Then add nutrition data to `$nutrition_data` array.

---

## 🐛 Common Issues

| Issue | Solution |
|-------|----------|
| Page shows error | Check XAMPP services running |
| Styles not loading | Clear browser cache (Ctrl+Shift+Del) |
| Database error | Verify MySQL running & credentials correct |
| Page blank | Check PHP error logs in XAMPP |
| Search not working | Verify `api/search_api.php` exists |

---

## 📱 Mobile Testing

### In Browser DevTools
1. Right-click → Inspect
2. Click device icon (top-left)
3. Select mobile device
4. Test responsiveness

### On Real Device
1. Get your computer IP: `ipconfig` (Windows) or `ifconfig` (Mac/Linux)
2. Open: `http://YOUR_IP/nutriplan/`
3. Tap install to add as app

---

## 🎯 Key Achievements

✅ Complete meal database with 22 African meals
✅ Full authentication system (register/login/logout)
✅ Shopping list with toggle & delete
✅ Nutrition tracking & visualization
✅ Responsive mobile-first design
✅ Dark/Light theme toggle
✅ PWA with offline support
✅ Modern CSS design system
✅ Production-ready code

---

## 📚 Next Steps

1. **Run seed.php** to populate database
2. **Create account** via registration
3. **Search meals** and add to list
4. **View shopping** and check off items
5. **Explore profile** settings
6. **Try mobile** view on different devices

---

## 💬 Support

For issues:
1. Check README.md for detailed docs
2. Review JavaScript console (F12)
3. Check XAMPP error logs
4. Verify all files are in correct folders

**Happy planning! 🍽️**
