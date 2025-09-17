<?php
header('Content-Type: application/json; charset=utf-8');

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

$conn = new mysqli('localhost', 'root', '', 'escola');
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$list = [];
if ($tipo === 'professor') {
    $res = $conn->query("SELECT p.id, CONCAT(p.nome, ' - ', p.disciplina) AS label
                         FROM professores p
                         LEFT JOIN usuarios u ON u.tipo='professor' AND u.id_ref = p.id
                         WHERE u.id IS NULL ORDER BY p.nome");
    while ($r = $res->fetch_assoc()) $list[] = $r;
} elseif ($tipo === 'aluno') {
    $res = $conn->query("SELECT a.id, CONCAT(a.nome, ' - ', a.turma) AS label
                         FROM alunos a
                         LEFT JOIN usuarios u ON u.tipo='aluno' AND u.id_ref = a.id
                         WHERE u.id IS NULL ORDER BY a.nome");
    while ($r = $res->fetch_assoc()) $list[] = $r;
}

echo json_encode($list);
$conn->close();
