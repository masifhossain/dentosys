<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DentoSys Dental Clinic - Your Smile, Our Priority</title>
    <link rel="icon" type="image/png" href="assets/images/DentoSys_Logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Header Section */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: 700;
            gap: 0.5rem;
        }

        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .login-btn {
            background: white;
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
        }

        /* Portal Dropdown */
        .portal-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 180px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            z-index: 1000;
            overflow: hidden;
            margin-top: 0.25rem;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .dropdown-content a {
            color: #2c3e50;
            padding: 1rem 1.5rem;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
            font-weight: 500;
            white-space: nowrap;
        }

        .dropdown-content a:hover {
            background: #f8f9fa;
            color: #667eea;
            transform: translateX(5px);
        }

        .portal-dropdown:hover .dropdown-content,
        .dropdown-content:hover {
            display: block;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* Keep dropdown open when hovering over the entire dropdown area */
        .portal-dropdown:hover .dropdown-content {
            display: block;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* Portal Buttons */
        .portal-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8rem 2rem 6rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,0 1000,100 1000,100 0,100"/></svg>');
            background-size: cover;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-btn {
            padding: 1rem 2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cta-primary {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
        }

        .cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(255, 255, 255, 0.4);
        }

        .cta-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .cta-secondary:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }

        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .section-title p {
            font-size: 1.2rem;
            color: #7f8c8d;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .feature-card p {
            color: #7f8c8d;
            line-height: 1.6;
        }

        /* About Section */
        .about {
            padding: 6rem 2rem;
            background: white;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-text h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .about-text p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: #7f8c8d;
            line-height: 1.8;
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            display: block;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .about-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            text-align: center;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Services Section */
        .services {
            padding: 6rem 2rem;
            background: #f8f9fa;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .service-item {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .service-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .service-item h4 {
            color: #667eea;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .service-item p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 3rem 2rem 1rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #ecf0f1;
        }

        .footer-section p,
        .footer-section li {
            color: #bdc3c7;
            margin-bottom: 0.5rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: #667eea;
        }

        .footer-bottom {
            border-top: 1px solid #34495e;
            padding-top: 1rem;
            text-align: center;
            color: #bdc3c7;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .about-content {
                grid-template-columns: 1fr;
            }

            .nav-links {
                gap: 1rem;
            }

            .nav-container {
                padding: 0 1rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card,
        .service-item {
            animation: fadeInUp 0.6s ease forwards;
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <img src="assets/images/DentoSys_Logo.png" alt="DentoSys Logo">
                <span>DentoSys</span>
            </div>
            <div class="nav-links">
                <a href="#about" class="nav-link">About</a>
                <a href="#services" class="nav-link">Services</a>
                <a href="#contact" class="nav-link">Contact</a>
                <div class="portal-dropdown">
                    <button class="login-btn">Portal Login ▼</button>
                    <div class="dropdown-content">
                        <a href="auth/staff_login.php">👨‍⚕️ Staff Portal</a>
                        <a href="auth/patient_portal.php">👤 Patient Portal</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Your Smile, Our Priority</h1>
            <p>Experience exceptional dental care with our state-of-the-art facility and compassionate team of professionals dedicated to your oral health.</p>
            <div class="cta-buttons">
                <div class="portal-buttons">
                    <a href="auth/staff_login.php" class="cta-btn cta-primary">
                        👨‍⚕️ Staff Portal
                    </a>
                    <a href="auth/patient_portal.php" class="cta-btn cta-primary">
                        👤 Patient Portal
                    </a>
                </div>
                <a href="#services" class="cta-btn cta-secondary">
                    📋 Our Services
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose DentoSys?</h2>
                <p>We combine advanced technology with personalized care to deliver the best dental experience</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">🦷</span>
                    <h3>Advanced Technology</h3>
                    <p>State-of-the-art equipment and digital systems for precise diagnosis and treatment planning.</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">👨‍⚕️</span>
                    <h3>Expert Dentists</h3>
                    <p>Highly qualified dental professionals with years of experience in comprehensive oral care.</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">💙</span>
                    <h3>Compassionate Care</h3>
                    <p>Patient-centered approach ensuring comfort and understanding throughout your treatment journey.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About DentoSys Dental Clinic</h2>
                    <p>For over a decade, DentoSys has been at the forefront of dental care innovation, providing comprehensive oral health services to our community. Our commitment to excellence and patient satisfaction has made us a trusted name in dental care.</p>
                    <p>We believe that every smile tells a story, and we're here to ensure yours is a story of health, confidence, and happiness. Our modern facility is equipped with the latest technology, and our team stays current with the latest advances in dental medicine.</p>
                    <div class="about-stats">
                        <div class="stat">
                            <span class="stat-number">10+</span>
                            <span class="stat-label">Years Experience</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">5000+</span>
                            <span class="stat-label">Happy Patients</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">15+</span>
                            <span class="stat-label">Dental Services</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">24/7</span>
                            <span class="stat-label">Emergency Care</span>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <h3>🏥 Modern Facility</h3>
                    <p>Our clinic features cutting-edge dental technology and a comfortable, welcoming environment designed to make your visit as pleasant as possible.</p>
                    <br>
                    <h3>🔬 Digital Dentistry</h3>
                    <p>From digital X-rays to 3D imaging, we use the latest technology for accurate diagnosis and efficient treatment.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="container">
            <div class="section-title">
                <h2>Our Dental Services</h2>
                <p>Comprehensive oral care for all your dental needs</p>
            </div>
            <div class="services-grid">
                <div class="service-item">
                    <h4>🔍 General Dentistry</h4>
                    <p>Regular checkups, cleanings, and preventive care</p>
                </div>
                <div class="service-item">
                    <h4>✨ Cosmetic Dentistry</h4>
                    <p>Teeth whitening, veneers, and smile makeovers</p>
                </div>
                <div class="service-item">
                    <h4>🦷 Restorative Dentistry</h4>
                    <p>Fillings, crowns, bridges, and dental implants</p>
                </div>
                <div class="service-item">
                    <h4>🦷 Orthodontics</h4>
                    <p>Braces and clear aligners for straight teeth</p>
                </div>
                <div class="service-item">
                    <h4>🚨 Emergency Care</h4>
                    <p>24/7 emergency dental services</p>
                </div>
                <div class="service-item">
                    <h4>👶 Pediatric Dentistry</h4>
                    <p>Specialized care for children and teens</p>
                </div>
                <div class="service-item">
                    <h4>🦷 Oral Surgery</h4>
                    <p>Extractions and surgical procedures</p>
                </div>
                <div class="service-item">
                    <h4>🔬 Digital Imaging</h4>
                    <p>Advanced diagnostic and treatment planning</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>🏥 DentoSys Dental Clinic</h3>
                    <p>Your trusted partner in oral health and beautiful smiles.</p>
                    <p><strong>📍 Address:</strong> 123 Main Street, City, State 12345</p>
                    <p><strong>📞 Phone:</strong> +1-555-0123</p>
                    <p><strong>✉️ Email:</strong> info@dentosys.local</p>
                </div>
                <div class="footer-section">
                    <h3>🕒 Office Hours</h3>
                    <ul>
                        <li>Monday - Friday: 8:00 AM - 6:00 PM</li>
                        <li>Saturday: 9:00 AM - 4:00 PM</li>
                        <li>Sunday: Emergency Only</li>
                        <li>24/7 Emergency Hotline Available</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>🔗 Quick Links</h3>
                    <ul>
                        <li><a href="auth/patient_portal.php">Patient Portal</a></li>
                        <li><a href="auth/staff_login.php">Staff Login</a></li>
                        <li><a href="#services">Our Services</a></li>
                        <li><a href="#about">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>🚨 Emergency Care</h3>
                    <p>For dental emergencies outside office hours, please call our emergency hotline.</p>
                    <p><strong>Emergency: +1-555-0123</strong></p>
                    <p>We're here when you need us most.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 DentoSys Dental Clinic. All rights reserved. | Designed with ❤️ for better smiles</p>
            </div>
        </div>
    </footer>

    <script>
        // Enhanced dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.querySelector('.portal-dropdown');
            const dropdownContent = document.querySelector('.dropdown-content');
            const dropdownButton = document.querySelector('.login-btn');
            let dropdownTimeout;

            if (dropdown && dropdownContent && dropdownButton) {
                // Show dropdown on button click
                dropdownButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (dropdownContent.style.display === 'block') {
                        hideDropdown();
                    } else {
                        showDropdown();
                    }
                });

                // Keep dropdown open when hovering over dropdown area
                dropdown.addEventListener('mouseenter', function() {
                    clearTimeout(dropdownTimeout);
                    showDropdown();
                });

                // Hide dropdown when leaving dropdown area
                dropdown.addEventListener('mouseleave', function() {
                    dropdownTimeout = setTimeout(hideDropdown, 150);
                });

                // Hide dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdown.contains(e.target)) {
                        hideDropdown();
                    }
                });

                function showDropdown() {
                    dropdownContent.style.display = 'block';
                    dropdownContent.style.opacity = '1';
                    dropdownContent.style.transform = 'translateY(0)';
                    dropdownContent.style.pointerEvents = 'auto';
                }

                function hideDropdown() {
                    dropdownContent.style.opacity = '0';
                    dropdownContent.style.transform = 'translateY(-10px)';
                    dropdownContent.style.pointerEvents = 'none';
                    setTimeout(() => {
                        if (dropdownContent.style.opacity === '0') {
                            dropdownContent.style.display = 'none';
                        }
                    }, 300);
                }
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(102, 126, 234, 0.95)';
            } else {
                header.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            }
        });
    </script>
</body>
</html>
