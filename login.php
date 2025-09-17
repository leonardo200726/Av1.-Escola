<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "escola");
    
    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }
    
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, password_hash, tipo, id_ref FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_type'] = $row['tipo'];
            $_SESSION['ref_id'] = $row['id_ref'];
            
            header("Location: index.php");
            exit;
        }
    }
    
    $error = "Usuário ou senha incorretos";
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema Escolar</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 { text-align: center; color: #1a73e8; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover { background-color: #1557b0; }
        .error { color: red; text-align: center; margin-bottom: 10px; }
        .register-link {
            text-align: center;
            margin-top: 15px;
        }
        a { color: #1a73e8; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Entrar</button>
        </form>
        
        <div class="register-link">
            Não tem uma conta? <a href="register.php">Cadastre-se</a>
        </div>
    </div>
</body>
</html>
