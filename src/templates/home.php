<?php
require_once '../config/config.php';

// Verificam daca utilizatorul este deja logat
$user_info = get_user_info();
$is_logged_in = is_logged_in();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);

// Statistici publice pentru landing page
$public_stats = [];

if ($connection) {
    // Total locatii active
    $locations_query = "SELECT COUNT(*) as count FROM locations WHERE location_type = 'collection_point' AND is_active = TRUE";
    $locations_result = mysqli_query($connection, $locations_query);
    $public_stats['locations'] = $locations_result ? mysqli_fetch_assoc($locations_result)['count'] : 0;
    
    // Total deseuri colectate (luna curenta)
    $waste_query = "SELECT SUM(quantity_kg) as total FROM waste_deposits WHERE MONTH(deposit_date) = MONTH(CURRENT_DATE()) AND YEAR(deposit_date) = YEAR(CURRENT_DATE())";
    $waste_result = mysqli_query($connection, $waste_query);
    $public_stats['waste_month'] = $waste_result ? mysqli_fetch_assoc($waste_result)['total'] ?? 0 : 0;
    
    // Total depozitari verificate
    $deposits_query = "SELECT COUNT(*) as count FROM waste_deposits WHERE verified_at IS NOT NULL";
    $deposits_result = mysqli_query($connection, $deposits_query);
    $public_stats['verified_deposits'] = $deposits_result ? mysqli_fetch_assoc($deposits_result)['count'] : 0;
    
    // Total rapoarte rezolvate
    $reports_query = "SELECT COUNT(*) as count FROM reports WHERE status = 'resolved'";
    $reports_result = mysqli_query($connection, $reports_query);
    $public_stats['resolved_reports'] = $reports_result ? mysqli_fetch_assoc($reports_result)['count'] : 0;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Sistemul Inteligent pentru Gestionarea Deseurilor</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/home.css">
    <meta name="description" content="EcoManager - Platforma inteligenta pentru gestionarea deseurilor urbane. Contribuie la un oras mai curat!">
</head>
<body>
    <?php if ($is_logged_in): ?>
        <!-- Navbar pentru utilizatori logati -->
        <nav>
        <ul class="nav-links">
            <?php if ($is_staff): ?>
                <!-- Linkuri pentru staff/admin -->
                <li><a class="pagini" href="home.php">🏠 Home</a></li>
                <li><a class="pagini" href="report.php">♻️ Depozitare</a></li>
                <li><a class="pagini" href="locations.php">🗺️ Locatii</a></li>
                <li><a class="pagini" href="simulator.php">🔬 Simulator</a></li>
                <li><a class="pagini" href="dashboard_staff.php">📊 Dashboard</a></li>
            <?php else: ?>
                <!-- Linkuri pentru cetateni -->
                <li><a class="pagini" href="home.php">🏠 Home</a></li>
                <li><a class="pagini" href="report.php">♻️ Depozitare</a></li>
                <li><a class="pagini" href="locations.php">🗺️ Locatii</a></li>
                <li><a class="pagini" href="simulator.php">🔬 Simulator</a></li>
            <?php endif; ?>
        </ul>
        
        <div class="userprofile">
            <img src="../public/images/user.jpg" class="userpic" alt="Profil utilizator">
            <span class="username">
                <?= htmlspecialchars($user_info['name'] ?? 'Utilizator') ?>
                <?php if ($is_staff): ?>
                    (<?= ucfirst($user_info['role']) ?>)
                <?php endif; ?>
            </span>
            
            <div class="profile-dropdown">
                <div class="dropdown-content">
                    <a href="profile.php">👤 Profil Personal</a>
                    <a href="settings.php">⚙️ Setari Cont</a>
                    <?php if ($user_info['role'] === 'admin'): ?>
                        <hr>
                        <a href="admin_panel.php">🔧 Panel Administrare</a>
                    <?php endif; ?>
                    <?php if ($is_staff): ?>
                        <hr>
                        <a href="staff_export.php">📊 Export Date</a>
                    <?php endif; ?>
                    <hr>
                    <a href="logout.php" onclick="return confirm('Sigur doriti sa va delogati?')">🚪 Delogare</a>
                </div>
            </div>
        </div>
        
        <button class="mobile-menu" aria-label="Toggle navigation menu" aria-expanded="false">☰</button>
    </nav>
    <?php endif; ?>

    <section class="hero">
        <div class="hero-background">
            <div class="floating-icons">
                <div class="floating-icon">🌱</div>
                <div class="floating-icon">♻️</div>
                <div class="floating-icon">🌍</div>
                <div class="floating-icon">🗑️</div>
                <div class="floating-icon">📊</div>
                <div class="floating-icon">🏙️</div>
            </div>
        </div>
        
        <div class="hero-content">
            <div class="logo-section">
                <div class="main-logo">🌱</div>
                <h1 class="hero-title">EcoManager</h1>
                <p class="hero-subtitle">Sistemul Inteligent pentru Gestionarea Deseurilor Urbane</p>
            </div>
            
            <div class="hero-description">
                <p>Transforma modul in care gestionezi deseurile! Contribuie la un oras mai curat prin raportarea problemelor, monitorizarea colectarii si urmarirea progresului de reciclare.</p>
            </div>
            
            <?php if (!$is_logged_in): ?>
                <div class="cta-buttons">
                    <a href="login.php" class="btn btn-primary">
                        <span class="btn-icon">🔑</span>
                        Intra in cont
                    </a>
                    <a href="register.php" class="btn btn-secondary">
                        <span class="btn-icon">👤</span>
                        Creeaza cont
                    </a>
                </div>
            <?php else: ?>
                <div class="cta-buttons">
                    <?php if ($is_staff): ?>
                        <a href="dashboard_staff.php" class="btn btn-primary">
                            <span class="btn-icon">📊</span>
                            Dashboard Staff
                        </a>
                        <a href="staff_reports.php" class="btn btn-secondary">
                            <span class="btn-icon">📋</span>
                            Gestioneaza Rapoarte
                        </a>
                    <?php else: ?>
                        <a href="report.php" class="btn btn-primary">
                            <span class="btn-icon">♻️</span>
                            Depozitare Deseuri
                        </a>
                        <a href="locations.php" class="btn btn-secondary">
                            <span class="btn-icon">🗺️</span>
                            Vezi Locatii
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="scroll-indicator">
            <div class="scroll-icon">↓</div>
            <span>Descopera mai multe</span>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <h2 class="section-title">Impactul Comunitatii</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📍</div>
                    <div class="stat-number"><?= number_format($public_stats['locations']) ?></div>
                    <div class="stat-label">Puncte de Colectare Active</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">♻️</div>
                    <div class="stat-number"><?= number_format($public_stats['waste_month'], 1) ?>kg</div>
                    <div class="stat-label">Deseuri Colectate Luna Aceasta</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?= number_format($public_stats['verified_deposits']) ?></div>
                    <div class="stat-label">Depozitari Verificate</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🛠️</div>
                    <div class="stat-number"><?= number_format($public_stats['resolved_reports']) ?></div>
                    <div class="stat-label">Probleme Rezolvate</div>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Functionalitati Principale</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🗑️</div>
                    <h3>Depozitare Inteligenta</h3>
                    <p>Raporteaza depozitarea deseurilor la punctele de colectare si monitorizeaza capacitatea in timp real.</p>
                    <div class="feature-benefits">
                        <span>✓ Tracking in timp real</span>
                        <span>✓ Verificare automata</span>
                        <span>✓ Alerte pentru capacitate</span>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Rapoarte & Statistici</h3>
                    <p>Genereaza rapoarte detaliate despre colectare, sortare si progresul de reciclare pe categorii.</p>
                    <div class="feature-benefits">
                        <span>✓ Export HTML, CSV, PDF</span>
                        <span>✓ Analize pe cartiere</span>
                        <span>✓ Grafice interactive</span>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🗺️</div>
                    <h3>Harta Interactiva</h3>
                    <p>Localizeaza punctele de colectare si raporteaza probleme direct pe harta cu coordonate GPS.</p>
                    <div class="feature-benefits">
                        <span>✓ Localizare GPS</span>
                        <span>✓ Puncte de colectare</span>
                        <span>✓ Raportare probleme</span>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔬</div>
                    <h3>Simulator Reciclare</h3>
                    <p>Simuleaza procesele de reciclare si vizualizeaza impactul ecologic al actiunilor tale.</p>
                    <div class="feature-benefits">
                        <span>✓ Predictii scientifice</span>
                        <span>✓ Impact ecologic</span>
                        <span>✓ Scenarii multiple</span>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⚠️</div>
                    <h3>Sistem de Alerte</h3>
                    <p>Primeste notificari automate cand containerele sunt pline sau cand apar probleme.</p>
                    <div class="feature-benefits">
                        <span>✓ Alerte automate</span>
                        <span>✓ Notificari staff</span>
                        <span>✓ Prioritizare probleme</span>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Management Multi-nivel</h3>
                    <p>Acces diferentiat pentru cetateni, personal autorizat si factori de decizie.</p>
                    <div class="feature-benefits">
                        <span>✓ Roluri personalizate</span>
                        <span>✓ Dashboard staff</span>
                        <span>✓ Panel administrare</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">Cum Functioneaza</h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Inregistreaza-te</h3>
                        <p>Creeaza un cont gratuit si alatura-te comunitatii pentru un oras mai curat.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Depune Deseuri</h3>
                        <p>Raporteaza depozitarea deseurilor la punctele de colectare din oras.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Raporteaza Probleme</h3>
                        <p>Sesizeaza zone cu acumulari de gunoi pentru interventie rapida.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Monitorizeaza Progresul</h3>
                        <p>Urmareste statistici si contribuie la un mediu mai sanatos.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (!$is_logged_in): ?>
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Incepe Sa Contribui Astazi!</h2>
                    <p>Alatura-te miilor de cetateni care fac deja diferenta in comunitatea lor. Impreuna putem construi un oras mai curat si un viitor mai verde.</p>
                    
                    <div class="cta-buttons-large">
                        <a href="register.php" class="btn btn-primary-large">
                            <span class="btn-icon">🚀</span>
                            Creeaza Cont Gratuit
                        </a>
                        <a href="login.php" class="btn btn-outline-large">
                            <span class="btn-icon">🔑</span>
                            Am Deja Cont
                        </a>
                    </div>
                    
                    <div class="cta-benefits">
                        <div class="benefit">
                            <span class="benefit-icon">✅</span>
                            <span>Gratuit pentru totdeauna</span>
                        </div>
                        <div class="benefit">
                            <span class="benefit-icon">⚡</span>
                            <span>Inregistrare in 2 minute</span>
                        </div>
                        <div class="benefit">
                            <span class="benefit-icon">🔒</span>
                            <span>Date securizate</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <span class="logo-icon">🌱</span>
                        <span class="logo-text">EcoManager</span>
                    </div>
                    <p>Sistemul inteligent pentru gestionarea deseurilor urbane. Contribuie la un oras mai curat!</p>
                </div>
                
                <div class="footer-section">
                    <h4>Functionalitati</h4>
                    <ul>
                        <li><a href="#features">Depozitare Deseuri</a></li>
                        <li><a href="#features">Raportare Probleme</a></li>
                        <li><a href="#features">Statistici & Analize</a></li>
                        <li><a href="#features">Simulator Reciclare</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Pentru Dezvoltatori</h4>
                    <ul>
                        <li><a href="#api">API Documentation</a></li>
                        <li><a href="#github">GitHub Repository</a></li>
                        <li><a href="#docs">Documentatie Tehnica</a></li>
                        <li><a href="#support">Suport Tehnic</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <ul>
                        <li><a href="mailto:contact@ecomanager.ro">contact@ecomanager.ro</a></li>
                        <li><a href="tel:+40123456789">+40 123 456 789</a></li>
                        <li>Iasi, Romania</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    <p>&copy; 2024 EcoManager. Toate drepturile rezervate.</p>
                </div>
                <div class="footer-links">
                    <a href="#privacy">Politica de Confidentialitate</a>
                    <a href="#terms">Termeni si Conditii</a>
                    <a href="#cookies">Cookie-uri</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="../public/js/navbar.js"></script>
    <script src="../public/js/home.js"></script>
</body>
</html>