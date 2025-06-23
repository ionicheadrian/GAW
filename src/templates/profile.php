<?php
require_once '../config/config.php';
$user_info = get_user_info();
$is_staff = $user_info && in_array($user_info['role'], ['staff', 'admin']);
if (!is_logged_in()) {
    redirect('login.php');
}

$reports_count = 0;
$deposits_count = 0;
$total_kg = 0;
$member_since_days = 0;

if ($user_info) {
    $user_id = intval($user_info['id']);
    $result = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM reports WHERE user_id = $user_id");
    if ($result) {
        $reports_count = mysqli_fetch_assoc($result)['cnt'];
    }
    $result2 = mysqli_query($connection, "SELECT COUNT(*) as cnt, SUM(quantity_kg) as total FROM waste_deposits WHERE user_id = $user_id");
    if ($result2) {
        $data = mysqli_fetch_assoc($result2);
        $deposits_count = $data['cnt'];
        $total_kg = round($data['total'] ?? 0, 1);
    }
    if (isset($user_info['created_at'])) {
        $created_date = new DateTime($user_info['created_at']);
        $current_date = new DateTime();
        $member_since_days = $created_date->diff($current_date)->days;
    }
}

$badges = [];
if ($deposits_count >= 10) {
    $badges[] = ['name' => 'Eco Warrior', 'class' => 'eco-warrior', 'icon' => '🌱'];
}
if ($total_kg >= 50) {
    $badges[] = ['name' => 'Reciclator Activ', 'class' => 'eco-warrior', 'icon' => '♻️'];
}
if ($is_staff) {
    $badges[] = ['name' => 'Staff', 'class' => 'staff', 'icon' => '👔'];
}
if ($member_since_days >= 365) {
    $badges[] = ['name' => 'Veteran', 'class' => 'eco-warrior', 'icon' => '🏆'];
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilul Meu - EcoManager</title>
    <link rel="stylesheet" href="../public/css/navbar.css">
    <link rel="stylesheet" href="../public/css/profile.css">
</head>
<body>
    <nav>
        <ul class="nav-links">
            <?php if ($is_staff): ?>
                <li><a class="pagini" href="home.php">🏠 Home</a></li>
                <li><a class="pagini" href="report.php">♻️ Depozitare</a></li>
                <li><a class="pagini" href="locations.php">🗺️ Locatii</a></li>
                <li><a class="pagini" href="simulator.php">🔬 Simulator</a></li>
                <li><a class="pagini" href="dashboard_staff.php">📊 Dashboard</a></li>
            <?php else: ?>
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

    <div class="profile-container">
        <div class="profile-header">
            <img src="../public/images/user.jpg" alt="Avatar utilizator">
            <div class="profile-info">
                <h2><?= htmlspecialchars($user_info['full_name'] ?? $user_info['name'] ?? 'Utilizator') ?></h2>
                <div class="role">
                    <?= ucfirst($user_info['role']) ?>
                </div>
                
                <?php if (!empty($badges)): ?>
                    <div class="profile-badges">
                        <?php foreach ($badges as $badge): ?>
                            <span class="badge <?= $badge['class'] ?>">
                                <?= $badge['icon'] ?> <?= $badge['name'] ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-content">
            <div class="profile-grid">
                <!-- Detalii profil -->
                <div class="profile-details">
                    <h3>Informatii Cont</h3>
                    
                    <div class="detail-item">
                        <dt>📧 Email</dt>
                        <dd><?= htmlspecialchars($user_info['email']) ?></dd>
                    </div>
                    
                    <div class="detail-item">
                        <dt>👤 Rol</dt>
                        <dd><?= ucfirst($user_info['role']) ?></dd>
                    </div>
                    
                    <div class="detail-item">
                        <dt>📅 Membru din</dt>
                        <dd><?= date('d.m.Y', strtotime($user_info['created_at'] ?? '')) ?></dd>
                    </div>
                    
                    <div class="detail-item">
                        <dt>⏱️ Zile active</dt>
                        <dd><?= $member_since_days ?> zile</dd>
                    </div>
                    
                    <?php if (isset($user_info['phone'])): ?>
                        <div class="detail-item">
                            <dt>📱 Telefon</dt>
                            <dd><?= htmlspecialchars($user_info['phone']) ?></dd>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-stats">
                    <h3>Activitatea Ta</h3>
                    <div class="stats-grid">
                        <div class="stat">
                            <h4><?= $reports_count ?></h4>
                            <p>Rapoarte trimise</p>
                        </div>
                        <div class="stat">
                            <h4><?= $deposits_count ?></h4>
                            <p>Depozitari efectuate</p>
                        </div>
                        <div class="stat">
                            <h4><?= $total_kg ?>kg</h4>
                            <p>Total reciclat</p>
                        </div>
                        <div class="stat">
                            <h4><?= round($total_kg * 0.8, 1) ?>kg</h4>
                            <p>CO2 economisit</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <a href="settings.php">Editeaza profilul</a>
                <a href="report.php" class="secondary-btn">Depozitare noua</a>
            </div>
        </div>
    </div>

    <script src="../public/js/navbar.js"></script>
    <script src="../public/js/profile.js"></script>
</body>
</html>