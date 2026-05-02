# API Documentation - NutriPlan

## Endpoints Overview

All API endpoints return JSON responses. Most require authentication via PHP sessions.

---

## 🔍 Search Meals API

**Endpoint:** `GET /api/search_api.php`

**Description:** Search and filter meals by query, category, and nutrients.

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` | string | No | Search query (meal name or description) |
| `cat` | int | No | Category ID (1=Breakfast, 2=Lunch, 3=Supper) |
| `nut` | string | No | Nutrition filter (reserved for future use) |

### Example Requests
```javascript
// Search all meals starting with 'u'
fetch('/api/search_api.php?q=u')

// Get breakfast meals only
fetch('/api/search_api.php?cat=1')

// Search and filter by category
fetch('/api/search_api.php?q=rice&cat=2')
```

### Response
```json
{
  "success": true,
  "count": 3,
  "meals": [
    {
      "meal_id": 2,
      "meal_name": "Ugali & Nyama",
      "meal_icon": "🍖",
      "category_name": "Lunch",
      "category_id": 2,
      "calories": 520,
      "proteins_g": 28,
      "carbs_g": 55,
      "fats_g": 18,
      "fiber_g": 6
    }
  ]
}
```

---

## 🛒 Shopping List API

**Endpoint:** `POST /api/shopping_action.php`

**Description:** Manage shopping list items (add, toggle purchase, delete).

**Authentication:** Required (session must be active)

### Actions

#### Add Meal to List
```javascript
const formData = new URLSearchParams();
formData.append('action', 'add');
formData.append('meal_id', 5);

fetch('/api/shopping_action.php', {
  method: 'POST',
  body: formData
})
```

**Response:**
```json
{
  "success": true,
  "message": "Added to list"
}
```

#### Toggle Item as Purchased
```javascript
const formData = new URLSearchParams();
formData.append('action', 'toggle');
formData.append('item_id', 12);

fetch('/api/shopping_action.php', {
  method: 'POST',
  body: formData
})
```

#### Delete Item
```javascript
const formData = new URLSearchParams();
formData.append('action', 'delete');
formData.append('item_id', 12);

fetch('/api/shopping_action.php', {
  method: 'POST',
  body: formData
})
```

#### Add Custom Item
```javascript
const formData = new URLSearchParams();
formData.append('action', 'add_custom');
formData.append('name', 'Milk');
formData.append('qty', '1L');

fetch('/api/shopping_action.php', {
  method: 'POST',
  body: formData
})
```

**Response:**
```json
{
  "success": true,
  "message": "Custom item added"
}
```

---

## ✓ Username Availability Check

**Endpoint:** `GET /api/check_username.php`

**Description:** Check if a username is available for registration.

**Authentication:** Not required

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `username` | string | Yes | Username to check (min 3 chars) |

### Example Request
```javascript
fetch('/api/check_username.php?username=john_doe')
```

### Response
```json
{
  "available": true,
  "message": "Username available"
}
```

Or if taken:
```json
{
  "available": false,
  "message": "Username taken"
}
```

---

## 📸 Avatar Upload API

**Endpoint:** `POST /api/upload_avatar.php`

**Description:** Upload user profile picture.

**Authentication:** Required (session must be active)

**Content-Type:** `multipart/form-data`

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `avatar` | file | Yes | Image file (jpg, jpeg, png, gif, max 2MB) |

### Example Request
```javascript
const fileInput = document.getElementById('avatar-input');
const formData = new FormData();
formData.append('avatar', fileInput.files[0]);

fetch('/api/upload_avatar.php', {
  method: 'POST',
  body: formData
})
```

### Response
```json
{
  "success": true,
  "url": "/uploads/avatars/avatar_123_1234567890.jpg"
}
```

Or error:
```json
{
  "success": false,
  "message": "File too large"
}
```

---

## 🔐 Authentication Flow

### 1. Register
- POST to `/register.php`
- Parameters: `first_name`, `last_name`, `username`, `email`, `password`, `confirm`
- Creates user and starts session
- Redirects to dashboard

### 2. Login
- POST to `/login.php`
- Parameters: `email`, `password`
- Verifies credentials and starts session
- Redirects to dashboard

### 3. Authenticated Requests
- All subsequent requests can access `$_SESSION['user_id']`
- API endpoints check session in `/api/shopping_action.php`, `/api/upload_avatar.php`

### 4. Logout
- GET `/logout.php`
- Destroys session
- Redirects to login

### 5. Deregister
- POST `/deregister.php`
- Deletes user account and all related data
- Destroys session

---

## 📊 Data Models

### User
```php
{
  user_id: int,
  username: string (unique),
  email: string (unique),
  password_hash: string,
  first_name: string,
  last_name: string,
  avatar_url: string (nullable),
  created_at: timestamp,
  updated_at: timestamp
}
```

### Meal
```php
{
  meal_id: int,
  meal_name: string,
  category_id: int,
  description: string,
  preparation_time: int (minutes),
  meal_icon: string (emoji)
}
```

### Nutrition
```php
{
  nutrition_id: int,
  meal_id: int,
  calories: int,
  proteins_g: decimal,
  carbs_g: decimal,
  fats_g: decimal,
  fiber_g: decimal,
  iron_mg: decimal,
  calcium_mg: decimal,
  vitamins: string
}
```

### Shopping List
```php
{
  list_id: int,
  user_id: int,
  list_name: string,
  created_at: timestamp
}
```

### Shopping Item
```php
{
  item_id: int,
  list_id: int,
  meal_id: int (nullable),
  item_name: string,
  quantity: string,
  purchased: boolean,
  custom_item: boolean,
  created_at: timestamp
}
```

---

## ⚠️ Error Handling

All endpoints return consistent error format:

```json
{
  "success": false,
  "message": "Error description here"
}
```

### Common Error Messages
- "Invalid email or password" - Login failed
- "Username or email already taken" - Registration failure
- "All fields required" - Missing form data
- "Password must be at least 8 characters" - Weak password
- "No file uploaded" - Avatar upload empty
- "File too large" - Avatar exceeds 2MB
- "Invalid file type" - Avatar not an image

---

## 🔑 Security Measures

1. **Password Hashing:** Uses `password_hash()` with BCRYPT
2. **Input Sanitization:** All user input cleaned with `sanitize_input()`
3. **SQL Injection Prevention:** Using parameterized queries
4. **Session Security:** PHP session tokens
5. **CORS:** Not enabled (same-origin only)
6. **Rate Limiting:** Not currently implemented (consider for production)

---

## 📝 Usage Examples

### JavaScript Fetch Examples

#### Search Meals
```javascript
async function searchMeals(query) {
  const response = await fetch(`/api/search_api.php?q=${encodeURIComponent(query)}`);
  const data = await response.json();
  console.log(data.meals);
}
```

#### Add to Shopping List
```javascript
async function addMealToList(mealId) {
  const formData = new URLSearchParams();
  formData.append('action', 'add');
  formData.append('meal_id', mealId);
  
  const response = await fetch('/api/shopping_action.php', {
    method: 'POST',
    body: formData
  });
  
  const data = await response.json();
  if (data.success) {
    showToast('Added to list!', 'success');
  }
}
```

#### Check Username
```javascript
async function checkUsername(username) {
  const response = await fetch(`/api/check_username.php?username=${username}`);
  const data = await response.json();
  
  if (data.available) {
    console.log('Username is available');
  } else {
    console.log('Username already taken');
  }
}
```

---

## 🧪 Testing

Use Postman or similar tools to test:

1. **Search API** (GET):
   - `http://localhost/nutriplan/api/search_api.php?q=ugali`

2. **Login First** (POST):
   - URL: `http://localhost/nutriplan/login.php`
   - Body: `email=user@example.com&password=Password123`

3. **Add to List** (POST):
   - URL: `http://localhost/nutriplan/api/shopping_action.php`
   - Body: `action=add&meal_id=1`
   - (Requires active session from login)

---

## ⭐ Meal Ratings & Favorites API

**Endpoint:** `POST /api/meal_ratings.php`

**Description:** Rate meals, write reviews, and manage favorites.

**Authentication:** Required (session must be active)

### Actions

#### Submit Rating
```javascript
const formData = new URLSearchParams();
formData.append('action', 'rate');
formData.append('meal_id', 5);
formData.append('rating', 4);  // 1-5 stars
formData.append('review', 'Delicious and nutritious!');

fetch('/api/meal_ratings.php', {
  method: 'POST',
  body: formData
})
```

**Response:**
```json
{
  "success": true,
  "message": "Rating saved"
}
```

#### Toggle Favorite
```javascript
const formData = new URLSearchParams();
formData.append('action', 'toggle_favorite');
formData.append('meal_id', 5);

fetch('/api/meal_ratings.php', {
  method: 'POST',
  body: formData
})
```

**Response:**
```json
{
  "success": true,
  "is_favorite": true
}
```

#### Get User Rating
```javascript
const formData = new URLSearchParams();
formData.append('action', 'get_rating');
formData.append('meal_id', 5);

fetch('/api/meal_ratings.php', {
  method: 'POST',
  body: formData
})
```

**Response:**
```json
{
  "success": true,
  "rating": 4,
  "review": "Great meal!",
  "is_favorite": true
}
```

#### Get All Favorites
```javascript
const formData = new URLSearchParams();
formData.append('action', 'get_favorites');

fetch('/api/meal_ratings.php', {
  method: 'POST',
  body: formData
})
```

**Response:**
```json
{
  "success": true,
  "favorites": [
    {
      "meal_id": 5,
      "meal_name": "Ugali & Nyama",
      "meal_icon": "🍖",
      "category_name": "Lunch",
      "calories": 520
    }
  ]
}
```

---

## ⚙️ User Preferences API

**Endpoint:** `POST /api/user_preferences.php`

**Description:** Manage user preferences like portion sizes, dietary restrictions, and allergies.

**Authentication:** Required (session must be active)

### Actions

#### Get Preferences
```javascript
const formData = new URLSearchParams();
formData.append('action', 'get');

fetch('/api/user_preferences.php', {
  method: 'POST',
  body: formData
})
```

**Response:**
```json
{
  "success": true,
  "preferences": {
    "preference_id": 1,
    "user_id": 5,
    "portion_size": "normal",
    "dietary_restrictions": "Vegetarian",
    "allergies": "Nuts, Dairy",
    "preferred_cuisine": "African",
    "notifications_enabled": true,
    "theme_preference": "dark",
    "created_at": "2026-03-19 10:30:00",
    "updated_at": "2026-03-19 14:20:00"
  }
}
```

#### Update Preferences
```javascript
const formData = new URLSearchParams();
formData.append('action', 'update');
formData.append('portion_size', 'large');  // small, normal, large, extra-large
formData.append('dietary_restrictions', 'Vegetarian');
formData.append('allergies', 'Peanuts');
formData.append('preferred_cuisine', 'Asian');
formData.append('notifications_enabled', '1');
formData.append('theme_preference', 'dark');  // light or dark

fetch('/api/user_preferences.php', {
  method: 'POST',
  body: formData
})
```

**Response:**
```json
{
  "success": true,
  "message": "Preferences updated"
}
```

---

## 🔐 Rate Limiting

All API endpoints now include rate limiting to prevent abuse:

| Endpoint | Limit | Window |
|----------|-------|--------|
| `search_api.php` | 20 requests | 60 seconds |
| `shopping_action.php` | 10 requests | 60 seconds |
| `meal_ratings.php` | 15 requests | 60 seconds |
| `user_preferences.php` | 10 requests | 60 seconds |
| `check_username.php` | 5 requests | 60 seconds |
| `upload_avatar.php` | 3 uploads | 60 seconds |

**Rate Limit Exceeded Response (429):**
```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "remaining": 0
}
```

---

## 📊 Testing APIs

| Code | Meaning |
|------|---------|
| 200 | Request successful |
| 201 | Resource created |
| 400 | Bad request (missing parameters) |
| 401 | Unauthorized (login required) |
| 404 | Resource not found |
| 405 | Method not allowed |
| 429 | Too many requests (rate limit exceeded) |
| 500 | Server error |

---

**Last Updated:** March 2026
**Version:** 1.0
