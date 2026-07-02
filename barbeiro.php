<?php
session_start();
require_once("config/conexao.php");

/*
==========================================================
PAINEL DO BARBEIRO
PARTE 1 - PHP
==========================================================
*/

// Caso ainda não exista login do barbeiro,
// deixe este trecho comentado até criar o login.
/*
if(!isset($_SESSION['barbeiro_id'])){
    header("Location: login.php");
    exit;
}

$barbeiro_id = $_SESSION['barbeiro_id'];
*/

// TEMPORÁRIO
// Mostra todos os agendamentos.
$barbeiro_id = 0;


/*=========================================================
ATUALIZAR STATUS
=========================================================*/

if(isset($_GET['acao']) && isset($_GET['id'])){

    $id = intval($_GET['id']);
    $acao = $_GET['acao'];

    switch($acao){

        case "iniciar":
            $novoStatus = "Em andamento";
        break;

        case "concluir":
            $novoStatus = "Concluido";
        break;

        case "cancelar":
            $novoStatus = "Cancelado";
        break;

        case "falta":
            $novoStatus = "Nao compareceu";
        break;

        default:
            $novoStatus = "";
    }

    if($novoStatus != ""){

        $sql = "UPDATE agendamentos
                SET status=?
                WHERE id=?";

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("si",$novoStatus,$id);
        $stmt->execute();

        header("Location: barbeiro.php");
        exit;

    }

}


/*=========================================================
LISTA DOS AGENDAMENTOS
=========================================================*/

$sql = "

SELECT

agendamentos.id,

clientes.nome AS cliente,

barbeiros.nome AS barbeiro,

servicos.nome AS servico,

servicos.preco,

agendamentos.data_hora,

agendamentos.status

FROM agendamentos

INNER JOIN clientes
ON clientes.id = agendamentos.cliente_id

INNER JOIN barbeiros
ON barbeiros.id = agendamentos.barbeiro_id

INNER JOIN servicos
ON servicos.id = agendamentos.servico_id

ORDER BY agendamentos.data_hora ASC

";

$agendamentos = $conexao->query($sql);


/*=========================================================
ESTATÍSTICAS
=========================================================*/

$totalHoje = 0;
$confirmados = 0;
$andamento = 0;
$concluidos = 0;
$cancelados = 0;

$sqlStatus = "
SELECT status,COUNT(*) total
FROM agendamentos
GROUP BY status
";

$resStatus = $conexao->query($sqlStatus);

while($row = $resStatus->fetch_assoc()){

    switch($row['status']){

        case 'Confirmado':
            $confirmados = $row['total'];
        break;

        case 'Em andamento':
            $andamento = $row['total'];
        break;

        case 'Concluido':
            $concluidos = $row['total'];
        break;

        case 'Cancelado':
            $cancelados = $row['total'];
        break;

    }

}

$sqlHoje = "
SELECT COUNT(*) total
FROM agendamentos
WHERE DATE(data_hora)=CURDATE()
";

$resHoje = $conexao->query($sqlHoje);

if($resHoje->num_rows){

    $linhaHoje = $resHoje->fetch_assoc();

    $totalHoje = $linhaHoje['total'];

}


/*=========================================================
FUNÇÃO DE COR DO STATUS
=========================================================*/

function corStatus($status){

    switch($status){

        case "Confirmado":
            return "#f1c40f";

        case "Em andamento":
            return "#3498db";

        case "Concluido":
            return "#2ecc71";

        case "Cancelado":
            return "#e74c3c";

        case "Nao compareceu":
            return "#9b59b6";

        default:
            return "#999";
    }

}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Painel do Barbeiro</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>
    :root{
    --gold:#d4af37;
    --gold-dark:#b89022;
    --black:#111;
    --black2:#181818;
    --black3:#222;
    --border:#2f2f2f;
    --green:#2ecc71;
    --red:#e74c3c;
    --blue:#3498db;
    --purple:#8e44ad;
    --text:#ffffff;
    --text2:#bdbdbd;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    background:#111;

    color:#fff;

    font-family:'Poppins',sans-serif;

}

.container{

    width:95%;

    max-width:1400px;

    margin:auto;

    padding:30px;

}

.topo{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:30px;

}

.logo{

    display:flex;

    align-items:center;

    gap:15px;

}

.logo img{

    width:70px;

}

.logo h1{

    font-size:30px;

    color:var(--gold);

}

.usuario{

    display:flex;

    align-items:center;

    gap:10px;

    background:#181818;

    padding:15px;

    border-radius:10px;

}

.usuario i{

    font-size:22px;

    color:var(--gold);

}

.cards{

    display:grid;

    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));

    gap:20px;

    margin-bottom:35px;

}

.card{

    background:#181818;

    border:1px solid #2f2f2f;

    border-radius:15px;

    padding:25px;

    transition:.3s;

}

.card:hover{

    transform:translateY(-5px);

    border-color:var(--gold);

}

.card h2{

    color:#999;

    font-size:14px;

    margin-bottom:10px;

}

.card h3{

    font-size:35px;

    color:var(--gold);

}

.tabela{

    background:#181818;

    border-radius:15px;

    overflow:hidden;

    border:1px solid #2f2f2f;

}

.tituloTabela{

    background:#d4af37;

    color:#000;

    padding:20px;

    font-size:22px;

    font-weight:bold;

}

table{

    width:100%;

    border-collapse:collapse;

}

th{

    background:#222;

    color:#d4af37;

    padding:18px;

    font-size:15px;

}

td{

    padding:18px;

    border-bottom:1px solid #2d2d2d;

}

tr:hover{

    background:#1e1e1e;

}

.status{

    padding:8px 15px;

    border-radius:30px;

    font-size:13px;

    font-weight:bold;

    display:inline-block;

    color:#fff;

}

.botoes{

    display:flex;

    flex-wrap:wrap;

    gap:8px;

}
button{

    border:none;

    padding:10px 15px;

    border-radius:8px;

    cursor:pointer;

    font-size:13px;

    font-weight:600;

    transition:.3s;

}

.btn-iniciar{

    background:#3498db;

    color:#fff;

}

.btn-iniciar:hover{

    background:#217dbb;

}

.btn-concluir{

    background:#2ecc71;

    color:#fff;

}

.btn-concluir:hover{

    background:#27ae60;

}

.btn-cancelar{

    background:#e74c3c;

    color:#fff;

}

.btn-cancelar:hover{

    background:#c0392b;

}

.btn-falta{

    background:#8e44ad;

    color:#fff;

}

.btn-falta:hover{

    background:#6c3483;

}

.status-confirmado{

    background:#f39c12;

}

.status-andamento{

    background:#3498db;

}

.status-concluido{

    background:#2ecc71;

}

.status-cancelado{

    background:#e74c3c;

}

.status-falta{

    background:#8e44ad;

}

.sem-registros{

    text-align:center;

    padding:40px;

    color:#999;

    font-size:18px;

}

footer{

    margin-top:40px;

    text-align:center;

    color:#888;

    font-size:14px;

}

@media(max-width:992px){

.cards{

grid-template-columns:repeat(2,1fr);

}

table{

display:block;

overflow-x:auto;

white-space:nowrap;

}

}

@media(max-width:768px){

.container{

padding:15px;

}

.topo{

flex-direction:column;

gap:20px;

align-items:flex-start;

}

.cards{

grid-template-columns:1fr;

}

.logo h1{

font-size:24px;

}

.usuario{

width:100%;

justify-content:center;

}

button{

width:100%;

margin-top:5px;

}

.botoes{

display:flex;

flex-direction:column;

}

}

@media(max-width:500px){

th,td{

font-size:12px;

padding:10px;

}

.card h3{

font-size:28px;

}

.tituloTabela{

font-size:18px;

}

}

</style>
</head>

<body>

<div class="container">

    <div class="topo">

        <div class="logo">

            <img src="imagens/logo.png" alt="Logo">

            <div>

                <h1>Painel do Barbeiro</h1>

                <span>Controle de Agendamentos</span>

            </div>

        </div>

        <div class="usuario">

            <i class="fa-solid fa-user"></i>

            <strong>Barbeiro</strong>

        </div>

    </div>


    <div class="cards">

        <div class="card">

            <h2>Agendamentos Hoje</h2>

            <h3><?= $totalHoje ?></h3>

        </div>

        <div class="card">

            <h2>Confirmados</h2>

            <h3><?= $confirmados ?></h3>

        </div>

        <div class="card">

            <h2>Em Andamento</h2>

            <h3><?= $andamento ?></h3>

        </div>

        <div class="card">

            <h2>Concluídos</h2>

            <h3><?= $concluidos ?></h3>

        </div>

    </div>


    <div class="tabela">

        <div class="tituloTabela">

            Agendamentos

        </div>

        <table>

            <thead>

            <tr>

                <th>Cliente</th>

                <th>Barbeiro</th>

                <th>Serviço</th>

                <th>Preço</th>

                <th>Data</th>

                <th>Status</th>

                <th>Ações</th>

            </tr>

            </thead>

            <tbody>

            <?php

            if($agendamentos->num_rows > 0){

            while($row = $agendamentos->fetch_assoc()){

                $classeStatus="";

                switch($row["status"]){

                    case "Confirmado":
                        $classeStatus="status-confirmado";
                    break;

                    case "Em andamento":
                        $classeStatus="status-andamento";
                    break;

                    case "Concluido":
                        $classeStatus="status-concluido";
                    break;

                    case "Cancelado":
                        $classeStatus="status-cancelado";
                    break;

                    case "Nao compareceu":
                        $classeStatus="status-falta";
                    break;

                }

            ?>

            <tr>

                <td><?= htmlspecialchars($row["cliente"]) ?></td>

                <td><?= htmlspecialchars($row["barbeiro"]) ?></td>

                <td><?= htmlspecialchars($row["servico"]) ?></td>

                <td>

                    R$

                    <?= number_format($row["preco"],2,",",".") ?>

                </td>

                <td>

                    <?= date("d/m/Y H:i",strtotime($row["data_hora"])) ?>

                </td>

                <td>

                    <span class="status <?= $classeStatus ?>">

                        <?= $row["status"] ?>

                    </span>

                </td>

                <td>

                    <div class="botoes">
                
                    <?php if($row["status"]=="Confirmado"){ ?>

<a href="barbeiro.php?acao=iniciar&id=<?= $row["id"] ?>">
    <button class="btn-iniciar">
        <i class="fa-solid fa-play"></i>
        Iniciar
    </button>
</a>

<a href="barbeiro.php?acao=cancelar&id=<?= $row["id"] ?>">
    <button class="btn-cancelar">
        <i class="fa-solid fa-xmark"></i>
        Cancelar
    </button>
</a>

<a href="barbeiro.php?acao=falta&id=<?= $row["id"] ?>">
    <button class="btn-falta">
        <i class="fa-solid fa-user-slash"></i>
        Não Compareceu
    </button>
</a>

<?php } ?>


<?php if($row["status"]=="Em andamento"){ ?>

<a href="barbeiro.php?acao=concluir&id=<?= $row["id"] ?>">
    <button class="btn-concluir">
        <i class="fa-solid fa-check"></i>
        Finalizar
    </button>
</a>

<?php } ?>

                    </div>

                </td>

            </tr>

<?php

}

}else{

?>

<tr>

<td colspan="7">

<div class="sem-registros">

<i class="fa-solid fa-calendar-xmark"
style="font-size:55px;color:#555;margin-bottom:20px;"></i>

<h2>Nenhum agendamento encontrado.</h2>

<p>Não existem atendimentos cadastrados.</p>

</div>

</td>

</tr>

<?php } ?>

            </tbody>

        </table>

    </div>

<footer>

Sistema Barbearia © <?= date("Y") ?>

</footer>

</div>

</body>

</html>

<?php

$conexao->close();

?>