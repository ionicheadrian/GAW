<?php
require_once 'config/config.php';
if (!is_logged_in()) {
    redirect('login.php');
}
$user_info = get_user_info();
$errors = [];
$success = '';
$old_data = [];

//verificam daca avem mesaje deja in sesiune 
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_messages'])) {
    $errors = $_SESSION['error_messages'];
    unset($_SESSION['error_messages']);
}


//stergem datele vechi daca avem erori
if (isset($_SESSION['form_data']) && !empty($_SESSION['error_messages'])) {
    $old_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
} else {
    unset($_SESSION['form_data']);
}
//AICI INCEPE PROCESAREA FORMULUI

//facem un request POST (deoarece este form :D)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //initializari
    $title = '';
    $description = '';
    $waste_category = 0;
    $latitude = 0.0;
    $longitude = 0.0;
    $priority = 'medium';
    $address = '';
    if (isset($_POST['title']))
        $title = clean_input($_POST['title']);
    if (isset($_POST['description']))
        $description = clean_input($_POST['description']);
    if (isset($_POST['waste_category']))
        $waste_category = (int)$_POST['waste_category'];
    if (isset($_POST['latitude']))
        $latitude = (float)$_POST['latitude'];
    if (isset($_POST['longitude']))
        $longitude = (float)$_POST['longitude'];
    if (isset($_POST['priority']))
        $priority = $_POST['priority'];
    if (isset($_POST['address'])) 
        $address = clean_input($_POST['address']);
    
        $form_data = $_POST;
    
    //validarea datelor si error handling 
    if (empty($title)) {
        $errors[] = "Titlul raportului este obligatoriu!";
    } elseif (strlen($title) < 5) {
        $errors[] = "Titlul trebuie sa aiba cel putin 5 caractere!";
    }
    
    if (empty($description)) {
        $errors[] = "Descrierea este obligatorie!";
    } elseif (strlen($description) < 10) {
        $errors[] = "Descrierea trebuie sa aiba cel putin 10 caractere!";
    }

    if ($waste_category <= 0)
        $errors[] = "Va rugam să selectați o categorie de deseuri!";
    if ($latitude == 0 || $longitude == 0)
        $errors[] = "Locatia este obligatorie! Va rugam sa introduceti adresa sau sa folositi GPS.";
    if (empty($address))
        $errors[] = "Adresa este obligatorie!";
    
    //in caz ca nu exista erori, inseram in bd
    if (empty($errors)) {
        //inseram locatia

        //obisnuit, facem queryul ca sa combatem sql injections :D
        $location_query = "INSERT INTO locations (name, latitude, longitude, address, city, created_at) VALUES (?, ?, ?, ?, 'iasi', NOW())";
        $location_stmt = mysqli_prepare($connection, $location_query);
        
        if (!$location_stmt) {
            $errors[] = "Eroare la pregatirea inserarii locatiei!";
        } else {
            $location_name = "Locație raportata: " . substr($address, 0, 50);
            mysqli_stmt_bind_param($location_stmt, "sdds", $location_name, $latitude, $longitude, $address);
            if (mysqli_stmt_execute($location_stmt)) {
                $location_id = mysqli_insert_id($connection);
                mysqli_stmt_close($location_stmt);
                
                //inseram raportul 
                //la fel folosim queryul pregatit pentru a preveni sql injections
                $report_query = "INSERT INTO reports (user_id, location_id, waste_category_id, title, description, latitude, longitude, status, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'new', ?, NOW())";
                $report_stmt = mysqli_prepare($connection, $report_query);
                
                // AICI ERA PROBLEMA: trebuie să verifici dacă $report_stmt a fost pregătit cu succes
                if (!$report_stmt) {
                    $errors[] = "Eroare la pregatirea inserarii raportului!";
                } else { // Acest 'else' este esențial pentru a încadra logica următoare
                    mysqli_stmt_bind_param($report_stmt, "iiissdds", 
                            $user_info['id'], 
                            $location_id, 
                            $waste_category, 
                            $title, 
                            $description, 
                            $latitude, 
                            $longitude, 
                            $priority);
                        
                    if (mysqli_stmt_execute($report_stmt)) {
                        //in caz de succes, redirectionam la report si prefenim trimiterile repetate de form
                        $_SESSION['success_message'] = "Raportul a fost inregistrat cu succes! Va multumim pentru contributie.";
                        mysqli_stmt_close($report_stmt);
                        redirect('report.php');//aici avem redirectul (functie din functions.php, apelata prin config.php)
                    } else { // Acest else (linia 119 în codul tău original) este acum corect încadrat
                        $errors[] = "Eroare la inregistrarea raportului: " . mysqli_stmt_error($report_stmt);
                        mysqli_stmt_close($report_stmt);
                    }
                }
            } else {
                $errors[] = "Eroare la inregistrarea locatiei: " . mysqli_stmt_error($location_stmt);
                mysqli_stmt_close($location_stmt);
            }
        }
    }
    
    //daca avem erori, le salvam in sesiune si redirectionam la report.php
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        $_SESSION['form_data'] = $form_data;//aici am salvat datel doar daca avem erori 
        redirect('report.php');
    }
}

//categoria de deseuri
//selectam categoriile din baza de date
$categories_query = "SELECT id, type, description FROM waste_categories ORDER BY type";
$categories_result = mysqli_query($connection, $categories_query);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoManager - Raportează Deșeuri</title>
    <link rel="stylesheet" href="styles/home.css">
    <link rel="stylesheet" href="styles/report.css">
</head>
<body>

    <!-- NAVBARUL-->
    <nav>
        <ul class="nav-links">
            <li><a class="pagini" href="dashboard.php">Dashboard</a></li>
            <li><a class="pagini" href="report.php">Rapoarte</a></li>
            <li><a class="pagini" href="#">Harta</a></li>
            <li><a class="pagini" href="#">Statistici</a></li>
            <li><a class="pagini" href="#">Simulator</a></li>
        </ul>
        <div class="userprofile">
            <img src="imagini/user.png" class="userpic" alt="Profil">
            <span class="username"><?= htmlspecialchars($user_info['name'] ?? 'Utilizator') ?></span>
        </div>
        <button class="mobile-menu">☰</button>
    </nav>


        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="message success">
                <p><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>

        <div class="containers-wrapper">
            <div class="container form-container-wrapper">
                <div class="container-header">
                    <h2>📝 Raport Nou</h2>
                    <p>Completeaza formularul pentru a raporta o problema</p>
                </div>

                <div class="form-container">
                    <form method="POST" class="report-form">
                        <div class="form-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="title">Titlul raportului *</label>
                                    <input type="text" id="title" name="title" required 
                                            placeholder="Ex: Gunoi aruncat pe strada Copou">
                                </div>
                                
                                <div class="form-group">
                                    <label for="waste_category">Categoria deseurilor *</label>
                                    <select id="waste_category" name="waste_category" required>
                                        <option value="">Selecteaza categoria...</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                    <?= (($old_data['waste_category'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                                                <?= ucfirst($category['type']) ?> - <?= htmlspecialchars($category['description']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Descrierea problemei *</label>
                                <textarea id="description" name="description" required rows="2"
                                            placeholder="Descrie detaliat problema: cantitatea aproximativa, tipul deseurilor..."><?= htmlspecialchars($old_data['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="priority">Prioritatea</label>
                                    <select id="priority" name="priority">
                                        <option value="low">Scazuta</option>
                                        <option value="medium">Medie</option>
                                        <option value="high">Ridicata</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Adresa *</label>
                                    <input type="text" id="address" name="address" required
                                            placeholder="Strada, numărul, cartierul">
                                </div>
                            </div>

                            <div class="location-section compact">
                                <h3>📍 Locatia</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="latitude">Latitudine</label>
                                        <input type="number" step="any" id="latitude" name="latitude" placeholder="47.1585">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="longitude">Longitudine</label>
                                        <input type="number" step="any" id="longitude" name="longitude" placeholder="27.6014">
                                    </div>
                                </div>
                                <div class="location-buttons compact">
                                    <button type="button" id="get-location" class="butoane secondary">
                                        📱 GPS
                                    </button>
                                    <button type="button" id="find-address" class="butoane secondary">
                                        🔍 Harta
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions compact">
                            <button type="submit" class="butoane primary">
                                ✅ Trimite Raportul
                            </button>
                            <button type="reset" class="butoane secondary">
                                🔄 Reseteaza
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="container recent-container-wrapper">
                <div class="container-header">
                    <h2>📊 Rapoartele Tale</h2>
                    <p>Toate rapoartele trimise de tine</p>
                </div>

                <div class="recent-reports">
                    <div class="reports-list">
                        <?php
                        //AFISAM toate raportele facute de userul din sesiune cu toate informatiile (later pentru modal)
                        $recent_query = "SELECT r.id, r.title, r.description, r.status, r.priority, r.created_at, r.latitude, r.longitude, 
                                            wc.type as waste_type, wc.description as waste_description,
                                            l.address, l.name as location_name
                                            FROM reports r 
                                            LEFT JOIN waste_categories wc ON r.waste_category_id = wc.id 
                                            LEFT JOIN locations l ON r.location_id = l.id
                                            WHERE r.user_id = ? 
                                            ORDER BY r.created_at DESC";
                        $recent_stmt = mysqli_prepare($connection, $recent_query);
                        if ($recent_stmt) {
                            mysqli_stmt_bind_param($recent_stmt, "i", $user_info['id']);
                            mysqli_stmt_execute($recent_stmt);
                            $recent_result = mysqli_stmt_get_result($recent_stmt);
                            if (mysqli_num_rows($recent_result) > 0):
                                while ($report = mysqli_fetch_assoc($recent_result)):
                        ?>
                                <div class="report-item" onclick="openReportModal(<?= htmlspecialchars(json_encode($report)) ?>)">
                                    <div class="report-info">
                                        <h4><?= htmlspecialchars($report['title']) ?></h4>
                                        <p>
                                            <span class="waste-type"><?= ucfirst($report['waste_type'] ?? 'General') ?></span>
                                            •
                                            <span class="priority priority-<?= $report['priority'] ?>">
                                                <?= ucfirst($report['priority']) ?>
                                            </span>
                                            •
                                            <span class="date"><?= date('d.m.Y H:i', strtotime($report['created_at'])) ?></span>
                                        </p>
                                    </div>
                                    <div class="report-status">
                                        <span class="status status-<?= $report['status'] ?>">
                                            <?php
                                            $status_text = [
                                                'new' => 'Nou',
                                                'in_progress' => 'În progres',
                                                'resolved' => 'Rezolvat'
                                            ];
                                            echo $status_text[$report['status']] ?? 'Necunoscut';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                        <?php
                                endwhile;
                            else:
                                //daca nu sunt rapoarte facute de acel user ii apare un mesaj ca nu exista raporate \/ \/
                        ?>
                                <div class="no-reports">
                                    <div class="no-reports-icon">📝</div>
                                    <h3>Incă nu ai rapoarte</h3>
                                    <p>Acest ar putea fi primul tau raport pentru un oras mai curat! :)</p>
                                </div>
                        <?php
                            endif;
                            mysqli_stmt_close($recent_stmt);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <script src="scripts/report.js"></script>
</body>
</html>