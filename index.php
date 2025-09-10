<?php
$conn = new mysqli("localhost", "root", "", "escola");
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

/* ===== Inserir Professor ===== */
if (isset($_POST["add_professor"])) {
    $nome = $_POST["nome"];
    $disciplina = $_POST["disciplina"];
    $conn->query("INSERT INTO professores (nome, disciplina) VALUES ('$nome', '$disciplina')");
}

/* ===== Inserir Aluno ===== */
if (isset($_POST["add_aluno"])) {
    $nome = $_POST["nome"];
    $idade = $_POST["idade"];
    $turma = $_POST["turma"];
    $conn->query("INSERT INTO alunos (nome, idade, turma) VALUES ('$nome', '$idade', '$turma')");
}

/* ===== Inserir Nota ===== */
if (isset($_POST["add_nota"])) {
    $aluno_id = $_POST["aluno_id"];
    $professor_id = $_POST["professor_id"];
    $nota = $_POST["nota"];
    $conn->query("INSERT INTO notas (aluno_id, professor_id, nota) VALUES ('$aluno_id', '$professor_id', '$nota')");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema Escolar</title>
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

    <!-- Listagem -->
    <h2>Lista de Professores</h2>
    <?php
    $professores = $conn->query("SELECT * FROM professores");
    while ($row = $professores->fetch_assoc()) {
        echo $row["nome"]." - ".$row["disciplina"]."<br>";
    }
    ?>

    <h2>Lista de Alunos</h2>
    <?php
    $alunos = $conn->query("SELECT * FROM alunos");
    while ($row = $alunos->fetch_assoc()) {
        echo $row["nome"]." - ".$row["idade"]." anos - Turma: ".$row["turma"]."<br>";
    }
    ?>

    <h2>Notas</h2>
    <?php
    $notas = $conn->query("SELECT n.nota, a.nome AS aluno, p.nome AS professor, p.disciplina
                           FROM notas n
                           JOIN alunos a ON n.aluno_id = a.id
                           JOIN professores p ON n.professor_id = p.id");
    while ($row = $notas->fetch_assoc()) {
        echo "Aluno: ".$row["aluno"]." | Professor: ".$row["professor"]." (".$row["disciplina"].") | Nota: ".$row["nota"]."<br>";
    }
    $conn->close();
    ?>
</body>
</html>

