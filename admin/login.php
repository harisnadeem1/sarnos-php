<?php
session_start();

// Admin wachtwoord - verander dit naar je gewenste wachtwoord
$admin_password = "leeuwen08"; // VERANDER DIT WACHTWOORD!

$error_message = "";

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Check for logout or timeout messages
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success_message = "Je bent succesvol uitgelogd.";
}
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $error_message = "Je sessie is verlopen. Log opnieuw in.";
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $entered_password = $_POST['password'];
    
    if ($entered_password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        header('Location: dashboard.php');
        exit();
    } else {
        $error_message = "Verkeerd wachtwoord!";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - Sarnos</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 15px;
        }

        .logo h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 1rem;
        }

        .login-form {
            margin-top: 40px;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #dc3545;
            background: white;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .login-btn {
            width: 100%;
            background: #dc3545;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-size: 0.9rem;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            font-size: 0.9rem;
        }

        .security-info {
            margin-top: 30px;
            padding: 20px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .security-info i {
            color: #28a745;
            margin-right: 8px;
        }

        .security-info p {
            color: #155724;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
            }
            
            .logo h1 {
                font-size: 1.8rem;
            }
            
            .logo i {
                font-size: 3rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
                          <div class="logo">
            <h1 style="color: #333; font-size: 2.5rem; font-weight: 700; margin: 0 0 20px 0; text-align: center;">Sarnos</h1>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Wachtwoord
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="Voer admin wachtwoord in"
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Inloggen
            </button>
        </form>

        <div class="security-info">
            <p>
                <i class="fas fa-info-circle"></i>
                Deze pagina is beveiligd. Alleen geautoriseerde gebruikers hebben toegang tot het admin dashboard.
            </p>
        </div>
    </div>

    <script>
        // Focus on password field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('password').focus();
        });
        
        // Handle Enter key
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.target.form.submit();
            }
        });
    </script>
</body>
</html> 