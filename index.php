<?php
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "escola");
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

/* ===== Inserções ===== */
if (isset($_POST["add_professor"])) {
    $nome = $conn->real_escape_string($_POST["nome"]);
    $disciplina = $conn->real_escape_string($_POST["disciplina"]);
    $conn->query("INSERT INTO professores (nome, disciplina) VALUES ('$nome', '$disciplina')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST["add_aluno"])) {
    $nome = $conn->real_escape_string($_POST["nome"]);
    $idade = (int)$_POST["idade"];
    $turma = $conn->real_escape_string($_POST["turma"]);
    $conn->query("INSERT INTO alunos (nome, idade, turma) VALUES ('$nome', '$idade', '$turma')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST["add_nota"])) {
    $aluno_id = (int)$_POST["aluno_id"];
    $professor_id = (int)$_POST["professor_id"];
    $nota = (float)$_POST["nota"];
    $conn->query("INSERT INTO notas (aluno_id, professor_id, nota) VALUES ('$aluno_id', '$professor_id', '$nota')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ===== Exclusões ===== */
if (isset($_GET["delete_professor"])) {
    $id = (int)$_GET["delete_professor"];
    $conn->query("DELETE FROM professores WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET["delete_aluno"])) {
    $id = (int)$_GET["delete_aluno"];
    $conn->query("DELETE FROM alunos WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET["delete_nota"])) {
    $id = (int)$_GET["delete_nota"];
    $conn->query("DELETE FROM notas WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema Escolar</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { margin-top: 30px; }
        form { margin-bottom: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        table, th, td { border: 1px solid black; padding: 8px; }
        th { background: #ddd; }
        a { color: red; text-decoration: none; }
    </style>
</head>
<body>
    <h1>Sistema Escolar</h1>
    <div style="text-align: right; margin-bottom: 20px;">
        Bem-vindo(a), 
        <?php 
        if ($_SESSION['user_type'] == 'professor') {
            $stmt = $conn->prepare("SELECT nome FROM professores WHERE id = ?");
        } else {
            $stmt = $conn->prepare("SELECT nome FROM alunos WHERE id = ?");
        }
        $stmt->bind_param("i", $_SESSION['ref_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        echo htmlspecialchars($user['nome']);
        ?>
        | <a href="logout.php" style="color: #1a73e8;">Sair</a>
    </div>

    <!-- Cadastro de Professores -->
    <h2>Cadastrar Professor</h2>
    <form method="POST">
        Nome: <input type="text" name="nome" required>
        Disciplina: <input type="text" name="disciplina" required>
        <button type="submit" name="add_professor">Salvar</button>
    </form>

    <!-- Cadastro de Alunos -->
    <h2>Cadastrar Aluno</h2>
    <form method="POST">
        Nome: <input type="text" name="nome" required>
        Idade: <input type="number" name="idade" required>
        Turma: <input type="text" name="turma" required>
        <button type="submit" name="add_aluno">Salvar</button>
    </form>

    <!-- Cadastro de Notas -->
    <h2>Lançar Nota</h2>
    <form method="POST">
        Aluno:
        <select name="aluno_id" required>
            <?php
            $alunos = $conn->query("SELECT * FROM alunos");
            while ($row = $alunos->fetch_assoc()) {
                echo "<option value='".$row["id"]."'>".$row["nome"]."</option>";
            }
            ?>
        </select>
        Professor:
        <select name="professor_id" required>
            <?php
            $professores = $conn->query("SELECT * FROM professores");
            while ($row = $professores->fetch_assoc()) {
                echo "<option value='".$row["id"]."'>".$row["nome"]." - ".$row["disciplina"]."</option>";
            }
            ?>
        </select>
        Nota: <input type="number" step="0.01" name="nota" required>
        <button type="submit" name="add_nota">Salvar</button>
    </form>

    <!-- Listagem de Professores -->
    <h2>Professores</h2>
    <table>
        <tr><th>Nome</th><th>Disciplina</th><th>Ação</th></tr>
        <?php
        $professores = $conn->query("SELECT * FROM professores");
        while ($row = $professores->fetch_assoc()) {
            echo "<tr>
                    <td>".$row["nome"]."</td>
                    <td>".$row["disciplina"]."</td>
                    <td><a href='?delete_professor=".$row["id"]."'>Excluir</a></td>
                  </tr>";
        }
        ?>
    </table>

    <!-- Listagem de Alunos -->
    <h2>Alunos</h2>
    <table>
        <tr><th>Nome</th><th>Idade</th><th>Turma</th><th>Ação</th></tr>
        <?php
        $alunos = $conn->query("SELECT * FROM alunos");
        while ($row = $alunos->fetch_assoc()) {
            echo "<tr>
                    <td>".$row["nome"]."</td>
                    <td>".$row["idade"]."</td>
                    <td>".$row["turma"]."</td>
                    <td><a href='?delete_aluno=".$row["id"]."'>Excluir</a></td>
                  </tr>";
        }
        ?>
    </table>

    <!-- Listagem de Notas -->
    <h2>Notas</h2>
    <table>
        <tr><th>Aluno</th><th>Professor</th><th>Disciplina</th><th>Nota</th><th>Ação</th></tr>
        <?php
        $notas = $conn->query("SELECT n.id, n.nota, a.nome AS aluno, p.nome AS professor, p.disciplina
                               FROM notas n
                               JOIN alunos a ON n.aluno_id = a.id
                               JOIN professores p ON n.professor_id = p.id");
        while ($row = $notas->fetch_assoc()) {
            echo "<tr>
                    <td>".$row["aluno"]."</td>
                    <td>".$row["professor"]."</td>
                    <td>".$row["disciplina"]."</td>
                    <td>".$row["nota"]."</td>
                    <td><a href='?delete_nota=".$row["id"]."'>Excluir</a></td>
                  </tr>";
        }
        ?>
    </table>

</body>
</html>
<?php $conn->close(); ?>
