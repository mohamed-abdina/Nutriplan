<?php
require_once __DIR__ . '/includes/session.php';
secure_session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NutriPlan - Smart meal planning system to reduce waste and plan better meals">
    <title>NutriPlan - Smart Meal Planning System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#030712">
</head>
<body>
    <!-- Navbar -->
    <nav class="responsive-navbar">
        <div style="font-size: var(--text-xl); font-weight: 800;">🍽 NutriPlan</div>
        <div style="display: flex; gap: var(--sp-6); align-items: center;">
            <a href="login.php" style="color: var(--text-1); text-decoration: none;">Login</a>
            <a href="register.php" class="btn btn-primary btn-sm">Get Started Free</a>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section style="min-height: 100dvh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: var(--sp-8) var(--sp-4); text-align: center;">
        <div style="max-width: 600px;">
            <h1 class="hero-headline text-gradient" style="margin-bottom: var(--sp-6);">Smart Meal Planning, Zero Waste</h1>
            <p style="font-size: var(--text-lg); color: var(--text-2); margin-bottom: var(--sp-8);">Plan your meals for the week, reduce food waste, and eat healthier. It's simpler than you think.</p>
            
            <div class="hero-button-group">
                <a href="register.php" class="btn btn-primary" style="animation: pulse 2s ease-in-out infinite;">Start Planning Free →</a>
                <button class="btn btn-outline" onclick="alert('Demo video coming soon!')">▶ Watch Demo</button>
            </div>
            
            <!-- Social Proof -->
            <div class="social-proof-grid">
                <div class="stagger-item">
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--primary);" data-count="500">0</div>
                    <div style="color: var(--text-2); font-size: var(--text-sm); margin-top: var(--sp-2);">Meals in Library</div>
                </div>
                <div class="stagger-item">
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--success);" data-count="100">0</div>
                    <div style="color: var(--text-2); font-size: var(--text-sm); margin-top: var(--sp-2);">% Waste Reduction</div>
                </div>
                <div class="stagger-item">
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--accent);" data-count="1000">0</div>
                    <div style="color: var(--text-2); font-size: var(--text-sm); margin-top: var(--sp-2);">Active Users</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section style="padding: var(--sp-12) var(--sp-8); background: var(--surface); border-top: 1px solid var(--border);">
        <div style="max-width: 1000px; margin: 0 auto;">
            <h2 style="text-align: center; margin-bottom: var(--sp-12);">Why Choose NutriPlan?</h2>
            
            <div class="grid-2 stagger-container">
                <div class="card stagger-item">
                    <div style="font-size: 2.5rem; margin-bottom: var(--sp-4);">📋</div>
                    <h3>Smart Planning</h3>
                    <p style="color: var(--text-2); margin-top: var(--sp-2);">Plan weekly meals in minutes. Our algorithm suggests diverse, nutritious combinations.</p>
                </div>
                
                <div class="card stagger-item">
                    <div style="font-size: 2.5rem; margin-bottom: var(--sp-4);">🧾</div>
                    <h3>Automatic Lists</h3>
                    <p style="color: var(--text-2); margin-top: var(--sp-2);">Your shopping list is generated automatically from meal plans. No more forgotten items.</p>
                </div>
                
                <div class="card stagger-item">
                    <div style="font-size: 2.5rem; margin-bottom: var(--sp-4);">💪</div>
                    <h3>Nutrition Tracking</h3>
                    <p style="color: var(--text-2); margin-top: var(--sp-2);">See full nutrition breakdown for every meal. Meet your dietary goals easily.</p>
                </div>
                
                <div class="card stagger-item">
                    <div style="font-size: 2.5rem; margin-bottom: var(--sp-4);">📱</div>
                    <h3>Works Offline</h3>
                    <p style="color: var(--text-2); margin-top: var(--sp-2);">As a PWA, NutriPlan works seamlessly on any device, online or offline.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section style="padding: var(--sp-12) var(--sp-8); text-align: center;">
        <div style="max-width: 600px; margin: 0 auto;">
            <h2 style="margin-bottom: var(--sp-4);">Ready to transform your meals?</h2>
            <p style="color: var(--text-2); margin-bottom: var(--sp-8);">Join thousands of students planning smarter, wasting less, and eating better.</p>
            <a href="register.php" class="btn btn-primary" style="font-size: var(--text-lg);">Get Started Free 🚀</a>
        </div>
    </section>
    
    <!-- Footer -->
    <footer style="background: var(--surface); border-top: 1px solid var(--border); padding: var(--sp-8) var(--sp-6); text-align: center; color: var(--text-2); font-size: var(--text-sm); margin-top: var(--sp-12);">
        <p>© 2024-2026 NutriPlan. Made with 🍽️ for better living.</p>
    </footer>
    
    <script src="assets/js/main.js" defer></script>
    <script>
        // Navbar glass effect
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.style.backdropFilter = 'blur(16px)';
                nav.style.background = 'rgba(3,7,18,0.8)';
            } else {
                nav.style.backdropFilter = 'none';
                nav.style.background = 'var(--bg)';
            }
        });
        
        // Animate on load
        document.addEventListener('DOMContentLoaded', () => {
            animateCounters();
        });
    </script>
</body>
</html>
