<?php
$conn = new mysqli("localhost", "root", "", "escola");
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

/* ===== Inserções ===== */
if (isset($_POST["add_professor"])) {
    $nome = $_POST["nome"];
    $disciplina = $_POST["disciplina"];
    $conn->query("INSERT INTO professores (nome, disciplina) VALUES ('$nome', '$disciplina')");
}

if (isset($_POST["add_aluno"])) {
    $nome = $_POST["nome"];
    $idade = $_POST["idade"];
    $turma = $_POST["turma"];
    $conn->query("INSERT INTO alunos (nome, idade, turma) VALUES ('$nome', '$idade', '$turma')");
}

if (isset($_POST["add_nota"])) {
    $aluno_id = $_POST["aluno_id"];
    $professor_id = $_POST["professor_id"];
    $nota = $_POST["nota"];
    $conn->query("INSERT INTO notas (aluno_id, professor_id, nota) VALUES ('$aluno_id', '$professor_id', '$nota')");
}

/* ===== Exclusões ===== */
if (isset($_GET["delete_professor"])) {
    $id = $_GET["delete_professor"];
    $conn->query("DELETE FROM professores WHERE id=$id");
}

if (isset($_GET["delete_aluno"])) {
    $id = $_GET["delete_aluno"];
    $conn->query("DELETE FROM alunos WHERE id=$id");
}

if (isset($_GET["delete_nota"])) {
    $id = $_GET["delete_nota"];
    $conn->query("DELETE FROM notas WHERE id=$id");
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
