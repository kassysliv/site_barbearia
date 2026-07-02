<?php
/**
 * Sistema de Barbearia - Módulo de Agendamento (Premium Design Refatorado)
 * PHP version 8.2
 */

declare(strict_types=1);
session_start();

// Controle de Erros para Desenvolvimento
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Controle de Acesso / Autenticação
if (!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit();
}

/*============================================================================
  1. CONEXÃO COM O BANCO DE DADOS
============================================================================*/

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_barbearia');
define('DB_PORT', 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conexao->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Erro de Conexão: " . $e->getMessage());
    die("Erro técnico de conexão. Tente novamente mais tarde.");
}

/*============================================================================
  2. PROCESSAMENTO DO FORMULÁRIO (POST)
============================================================================*/

$mensagem = "";
$status_mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $cliente_id  = (int) $_SESSION["cliente_id"];
        $barbeiro_id = isset($_POST["barbeiro"]) ? (int) $_POST["barbeiro"] : 0;
        $servico_id  = isset($_POST["servico"]) ? (int) $_POST["servico"] : 0;
        $data        = filter_input(INPUT_POST, 'data', FILTER_DEFAULT);
        $hora        = filter_input(INPUT_POST, 'hora', FILTER_DEFAULT);

        if ($barbeiro_id <= 0 || $servico_id <= 0 || empty($data) || empty($hora)) {
            throw new Exception("Por favor, preencha todas as etapas do agendamento.");
        }

        $data_hora = $data . " " . $hora . ":00";

        // Verifica disponibilidade
        $sqlCheck = "SELECT id
        FROM agendamentos
        WHERE barbeiro_id = ?
        AND data_hora = ?
        AND status IN ('Confirmado', 'Em andamento')
        LIMIT 1";
        $stmtCheck = $conexao->prepare($sqlCheck);
        $stmtCheck->bind_param("is", $barbeiro_id, $data_hora);
        $stmtCheck->execute();
        $resultadoCheck = $stmtCheck->get_result();

        if ($resultadoCheck->num_rows > 0) {
            throw new Exception("Horário indisponível para o barbeiro selecionado.");
        }
        $stmtCheck->close();

        // Insere agendamento
        $sqlInsert = "INSERT INTO agendamentos (cliente_id, barbeiro_id, servico_id, data_hora, status)
VALUES (?, ?, ?, ?, ?)";

        $stmtInsert = $conexao->prepare($sqlInsert);

        $status = "Confirmado";

        $stmtInsert->bind_param("iiiss", $cliente_id, $barbeiro_id, $servico_id, $data_hora, $status);

        if ($stmtInsert->execute()) {
            $mensagem = "Agendamento realizado com sucesso!";
            $status_mensagem = "sucesso";
        } else {
            throw new Exception("Erro ao registrar o agendamento.");
        }
        $stmtInsert->close();

    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        $status_mensagem = "erro";
    }
}

/*============================================================================
  3. BUSCA DE DADOS PARA A INTERFACE
============================================================================*/

$barbeiros = [];

try {
    $sqlBarbeiros = "SELECT id, nome FROM barbeiros ORDER BY nome ASC";
    $resultBarbeiros = $conexao->query($sqlBarbeiros);
    while ($row = $resultBarbeiros->fetch_assoc()) {
        $barbeiros[] = $row;
    }

    $sqlServicos = "SELECT id, nome, preco FROM servicos ORDER BY nome ASC";
    $resultado_servicos = $conexao->query($sqlServicos);

} catch (mysqli_sql_exception $e) {
    error_log("Erro de query: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbearia Estilo & Cia | Agendamento</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="agendar.css">
    
    <style>
        :root {
            --primary-gold: #d4af37;
            --bg-gray: #111111;
            --border-gray: #292929;
            --text-muted: #888888;
        }

        .stepper-container {
            margin-bottom: 25px;
            background: #161616;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--border-gray);
        }
        .stepper-header {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .stepper-bar-bg {
            width: 100%;
            background: #252525;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }
        .stepper-bar-progress {
            width: 25%;
            background: var(--primary-gold);
            height: 100%;
            transition: width 0.4s ease;
        }

        .campo-passo {
            transition: opacity 0.3s ease, pointer-events 0.3s ease;
            margin-bottom: 25px;
        }
        .campo-passo.passo-bloqueado {
            opacity: 0.25;
            pointer-events: none;
            user-select: none;
        }

        .cards-servicos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .card-servico input[type="radio"] { display: none; }
        .card-servico label {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 20px 15px;
            background: #111;
            border: 2px solid #292929;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }
        .card-servico label i {
            font-size: 24px;
            color: var(--primary-gold);
            margin-bottom: 10px;
        }
        .card-servico label h3 { font-size: 15px; margin: 5px 0; }
        .card-servico label .preco { font-weight: 700; color: #fff; }
        
        .card-servico input[type="radio"]:checked + label {
            border-color: var(--primary-gold);
            background: rgba(212, 175, 55, 0.05);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.1);
        }

        .calendar-numbers-grid span.past-day {
            color: #333 !important;
            cursor: not-allowed !important;
            background: transparent !important;
            text-decoration: line-through;
        }
        .calendar-numbers-grid span.sunday-disabled {
            color: #442222 !important;
            cursor: not-allowed !important;
            background: rgba(255, 0, 0, 0.02) !important;
        }

        .grid-horarios {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .opcao-hora input { display: none; }
        .opcao-hora label {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #111;
            border: 1px solid #292929;
            border-radius: 8px;
            height: 45px;
            cursor: pointer;
            transition: .3s;
            font-weight: 500;
            font-size: 14px;
        }
        .opcao-hora label:hover { border-color: var(--primary-gold); }
        .opcao-hora input:checked + label {
            background: var(--primary-gold);
            color: #000;
            font-weight: 600;
            border-color: var(--primary-gold);
        }
        .opcao-hora.hora-indisponivel label {
            background: #181818 !important;
            color: #444 !important;
            border-color: #222 !important;
            cursor: not-allowed !important;
            text-decoration: line-through;
            pointer-events: none;
        }

        .sticky-summary-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #161616;
            border-top: 2px solid var(--border-gray);
            padding: 15px 20px;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.5);
            z-index: 999;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .summary-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .summary-text {
            font-size: 12px;
            color: var(--text-muted);
            max-width: 450px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .summary-total-box {
            font-size: 16px;
            font-weight: 700;
        }
        .summary-total-box span {
            color: var(--primary-gold);
        }
        .sticky-summary-footer .btn-agendar-sticky {
            background: #252525;
            color: #555;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: not-allowed;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sticky-summary-footer .btn-agendar-sticky.ready {
            background: var(--primary-gold);
            color: #000;
            cursor: pointer;
        }
        .sticky-summary-footer .btn-agendar-sticky.ready:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }

        .container { padding-bottom: 120px; }
    </style>
</head>
<body>

<main class="container">
    <div class="agendamento-box">

        <header class="logo-container">
            <img src="imagens/logo.png" alt="logo" class="logo-img">
        </header>

        <section class="header-profile">
            <div class="icone-usuario">
                <i class="fa-regular fa-user"></i>
            </div>
            <div class="texto-header">
                <h1>Olá, <?php echo htmlspecialchars($_SESSION["cliente_nome"] ?? 'Cliente', ENT_QUOTES, 'UTF-8'); ?>!</h1>
                <p>Agende seu horário.</p> 
            </div>
        </section>

        <div class="stepper-container">
            <div class="stepper-header">
                <span id="stepper-status">Passo 1 de 4: Escolha o Profissional</span>
                <span id="stepper-percentage">25%</span>
            </div>
            <div class="stepper-bar-bg">
                <div id="stepper-bar" class="stepper-bar-progress"></div>
            </div>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="alert-msg <?php echo $status_mensagem === 'sucesso' ? 'msg-sucesso' : 'msg-erro'; ?>" role="alert">
                <span><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>" id="main-form-agendamento" class="form-agendamento">

            <div class="campo campo-passo" id="passo-1">
                <label class="label-passo">1. ESCOLHA SEU BARBEIRO</label>
                <div class="select-wrapper">
                    <select name="barbeiro" id="select-barbeiro" required>
                        <option value="" disabled selected>-- Escolha um Profissional --</option>
                        <?php foreach ($barbeiros as $barbeiro): ?>
                            <option value="<?php echo (int)$barbeiro['id']; ?>">
                                <?php echo htmlspecialchars($barbeiro['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="campo campo-passo passo-bloqueado" id="passo-2">
                <label class="label-passo">2. ESCOLHA O SERVIÇO</label>
                <div class="cards-servicos">
                <?php 
                if (isset($resultado_servicos) && $resultado_servicos->num_rows > 0) {
                    while($servico = $resultado_servicos->fetch_assoc()){
                        $icone = "fa-scissors";

                        if(stripos($servico["nome"],"barba") !== false){
                            $icone = "fa-user";
                        }

                        if(stripos($servico["nome"],"combo") !== false || stripos($servico["nome"],"corte + barba") !== false){
                            $icone = "fa-scissors";
                        }

                        if(stripos($servico["nome"],"sobrancelha") !== false){
                            $icone = "fa-eye"; 
                        }
                ?>
                        <div class="card-servico">
                            <input type="radio" id="servico<?php echo $servico['id']; ?>" name="servico" value="<?php echo $servico['id']; ?>" data-nome="<?php echo htmlspecialchars($servico["nome"], ENT_QUOTES, 'UTF-8'); ?>" data-preco="<?php echo (float)$servico["preco"]; ?>" required>
                            <label for="servico<?php echo $servico['id']; ?>">
                                <i class="fa-solid <?php echo $icone; ?>"></i>
                                <h3><?php echo htmlspecialchars($servico["nome"], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <span class="preco">
                                    R$ <?php echo number_format((float)$servico["preco"], 2, ",", "."); ?>
                                </span>
                            </label>
                        </div>
                <?php 
                    }
                } else {
                    echo '<p class="sem-dados">Nenhum serviço disponível.</p>';
                }
                ?>
                </div> 
            </div>

            <div class="linha">
                
                <div class="metade">
                    <div class="campo campo-passo passo-bloqueado" id="passo-3">
                        <label class="label-passo">3. ESCOLHA A DATA</label>
                        <div class="calendario-wrapper">
                            <input type="date" name="data" id="data-input" required style="display: none;">
                            
                            <div class="calendar-header">
                                <button type="button" class="cal-btn" id="btn-prev-mes"><i class="fa-solid fa-chevron-left"></i></button>
                                <span id="calendar-mes-ano">-- 2026</span>
                                <button type="button" class="cal-btn" id="btn-next-mes"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="calendar-days-week">
                                <span>DOM</span><span>SEG</span><span>TER</span><span>QUA</span><span>QUI</span><span>SEX</span><span>SAB</span>
                            </div>
                            <div class="calendar-numbers-grid" id="calendar-dias-grid"></div>
                        </div>
                    </div>
                </div>

                <div class="metade">
                    <div class="campo campo-passo passo-bloqueado" id="passo-4">
                        <label class="label-passo">4. ESCOLHA O HORÁRIO</label>
                        <div class="grid-horarios">
                            <?php 
                            $horarios_estaticos = ["08:00", "08:30", "09:00", "09:30", "10:00", "10:30", "11:00", "11:30", "13:30", "14:00", "14:30", "15:00", "15:30", "16:00", "16:30"];
                            foreach($horarios_estaticos as $h): 
                                $classes_extras = "";
                                if($h === "10:00" || $h === "15:30") { $classes_extras = "hora-indisponivel"; }
                            ?>
                                <div class="opcao-hora <?php echo $classes_extras; ?>">
                                    <input type="radio" id="hora_<?php echo $h; ?>" name="hora" value="<?php echo $h; ?>" required>
                                    <label for="hora_<?php echo $h; ?>"><?php echo $h; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div> 

            <div class="campo campo-passo passo-bloqueado" id="passo-confirmacao" style="margin-top: 20px; background: #161616; padding: 15px; border-radius: 8px; border: 1px dashed #333;">
                <p style="font-size: 13px; color: var(--text-muted);">
                    <i class="fa-solid fa-user-check" style="color: var(--primary-gold);"></i> 
                    Titular do Agendamento: <strong><?php echo htmlspecialchars($_SESSION["cliente_nome"] ?? 'Cliente', ENT_QUOTES, 'UTF-8'); ?></strong> (ID #<?php echo $_SESSION['cliente_id']; ?>)
                </p>
            </div>

        </form>
    </div>
</main>

<div class="sticky-summary-footer">
    <div class="summary-info">
        <div class="summary-total-box">Total: <span id="resumo-total">R$ 0,00</span></div>
        <div class="summary-text" id="resumo-escolhas">Nenhum item selecionado. Siga as etapas guiadas acima.</div>
    </div>
    <button type="button" id="btn-submit-fake" class="btn-agendar-sticky" disabled>
        <i class="fa-solid fa-calendar-check"></i> CONFIRMAR AGENDAMENTO
    </button>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Referências do DOM
    const selectBarbeiro = document.getElementById("select-barbeiro");
    const radiosServico = document.querySelectorAll('input[name="servico"]');
    const radiosHora = document.querySelectorAll('input[name="hora"]');
    const inputData = document.getElementById("data-input");
    
    const txtMesAno = document.getElementById("calendar-mes-ano");
    const gridDias = document.getElementById("calendar-dias-grid");
    const btnPrev = document.getElementById("btn-prev-mes");
    const btnNext = document.getElementById("btn-next-mes");

    const stepperStatus = document.getElementById("stepper-status");
    const stepperPercentage = document.getElementById("stepper-percentage");
    const stepperBar = document.getElementById("stepper-bar");

    const resumoTotal = document.getElementById("resumo-total");
    const resumoEscolhas = document.getElementById("resumo-escolhas");
    const btnSubmitFake = document.getElementById("btn-submit-fake");
    const mainForm = document.getElementById("main-form-agendamento");

    const nomesMeses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    
    let dataAtual = new Date();
    let mesAtual = dataAtual.getMonth();
    let anoAtual = dataAtual.getFullYear();

    let estadoAgendamento = {
        barbeiroNome: '',
        servicoNome: '',
        servicoPreco: 0,
        dataSelecionada: '',
        horaSelecionada: ''
    };

    /* ========================================================================
       GERENCIADOR DE FLUXO & STEPPER (Harmonizado com IDs do HTML)
       ======================================================================== */
    function atualizarFluxoUI() {
        let passoAtual = 1;
        let porcentagem = 25;
        let statusTexto = "Passo 1 de 4: Escolha o Profissional";

        // Desbloqueia passo 2 se barbeiro for escolhido
        if (selectBarbeiro.value !== "") {
            document.getElementById("passo-2").classList.remove("passo-bloqueado");
            passoAtual = 2;
            porcentagem = 50;
            statusTexto = "Passo 2 de 4: Selecione o Serviço Desejado";
        } else {
            // Se resetar o barbeiro, bloqueia os passos seguintes
            document.getElementById("passo-2").classList.add("passo-bloqueado");
            document.getElementById("passo-3").classList.add("passo-bloqueado");
            document.getElementById("passo-4").classList.add("passo-bloqueado");
            document.getElementById("passo-confirmacao").classList.add("passo-bloqueado");
        }

        // Desbloqueia passo 3 se serviço for escolhido
        let servicoMarcado = document.querySelector('input[name="servico"]:checked');
        if (selectBarbeiro.value !== "" && servicoMarcado) {
            document.getElementById("passo-3").classList.remove("passo-bloqueado");
            passoAtual = 3;
            porcentagem = 75;
            statusTexto = "Passo 3 de 4: Selecione o Dia no Calendário";
        } else {
            document.getElementById("passo-3").classList.add("passo-bloqueado");
            document.getElementById("passo-4").classList.add("passo-bloqueado");
            document.getElementById("passo-confirmacao").classList.add("passo-bloqueado");
        }

        // Desbloqueia passo 4 se data estiver preenchida
        if (selectBarbeiro.value !== "" && servicoMarcado && inputData.value !== "") {
            document.getElementById("passo-4").classList.remove("passo-bloqueado");
            passoAtual = 4;
            porcentagem = 90;
            statusTexto = "Passo 4 de 4: Selecione o Horário Disponível";
        } else {
            document.getElementById("passo-4").classList.add("passo-bloqueado");
            document.getElementById("passo-confirmacao").classList.add("passo-bloqueado");
        }

        // Finaliza se o horário também foi escolhido
        let horaMarcada = document.querySelector('input[name="hora"]:checked');
        if (selectBarbeiro.value !== "" && servicoMarcado && inputData.value !== "" && horaMarcada) {
            document.getElementById("passo-confirmacao").classList.remove("passo-bloqueado");
            porcentagem = 100;
            statusTexto = "Pronto! Tudo pronto para confirmar o agendamento.";
        }

        // Atualização do Stepper Superior
        stepperStatus.textContent = statusTexto;
        stepperPercentage.textContent = `${porcentagem}%`;
        stepperBar.style.width = `${porcentagem}%`;

        // Construção do Resumo Fixo
        let stringResumo = [];
        if (estadoAgendamento.barbeiroNome) stringResumo.push(`Profissional: ${estadoAgendamento.barbeiroNome}`);
        if (estadoAgendamento.servicoNome) stringResumo.push(`Serviço: ${estadoAgendamento.servicoNome}`);
        if (estadoAgendamento.dataSelecionada) {
            let partesData = estadoAgendamento.dataSelecionada.split("-");
            stringResumo.push(`Dia: ${partesData[2]}/${partesData[1]}/${partesData[0]}`);
        }
        if (estadoAgendamento.horaSelecionada) stringResumo.push(`às ${estadoAgendamento.horaSelecionada}`);

        if (stringResumo.length > 0) {
            resumoEscolhas.textContent = stringResumo.join(" | ");
        } else {
            resumoEscolhas.textContent = "Nenhum item selecionado. Siga as etapas guiadas acima.";
        }

        // Atualização de Preço
        resumoTotal.innerHTML = `R$ ${estadoAgendamento.servicoPreco.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;

        // Controle do Botão de Envio
        if (selectBarbeiro.value !== "" && servicoMarcado && inputData.value !== "" && horaMarcada) {
            btnSubmitFake.classList.add("ready");
            btnSubmitFake.disabled = false;
        } else {
            btnSubmitFake.classList.remove("ready");
            btnSubmitFake.disabled = true;
        }
    }

    /* ========================================================================
       LISTENERS DE SELEÇÃO
       ======================================================================== */
    selectBarbeiro.addEventListener("change", function() {
        estadoAgendamento.barbeiroNome = selectBarbeiro.options[selectBarbeiro.selectedIndex].text;
        atualizarFluxoUI();
    });

    radiosServico.forEach(radio => {
        radio.addEventListener("change", function() {
            estadoAgendamento.servicoNome = this.getAttribute("data-nome");
            estadoAgendamento.servicoPreco = parseFloat(this.getAttribute("data-preco"));
            atualizarFluxoUI();
        });
    });

    // Delegando evento de mudança no container de horários para escutar os inputs dinamicamente
    document.querySelector('.grid-horarios').addEventListener("change", function(e) {
        if(e.target && e.target.name === "hora") {
            estadoAgendamento.horaSelecionada = e.target.value;
            atualizarFluxoUI();
        }
    });

    btnSubmitFake.addEventListener("click", function() {
        if(!btnSubmitFake.classList.contains("ready")) return;
        mainForm.submit();
    });

    /* ========================================================================
       SISTEMA DE CALENDÁRIO
       ======================================================================== */
    function renderizarCalendario(mes, ano) {
        gridDias.innerHTML = "";
        txtMesAno.textContent = `${nomesMeses[mes]} ${ano}`;

        let primeiroDiaMes = new Date(ano, mes, 1).getDay();
        let totalDiasMes = new Date(ano, mes + 1, 0).getDate();
        let totalDiasMesAnterior = new Date(ano, mes, 0).getDate();

        let hoje = new Date();
        hoje.setHours(0,0,0,0);

        for (let i = primeiroDiaMes; i > 0; i--) {
            let elementoDia = document.createElement("span");
            elementoDia.classList.add("fade-day");
            elementoDia.textContent = totalDiasMesAnterior - i + 1;
            gridDias.appendChild(elementoDia);
        }

        for (let dia = 1; dia <= totalDiasMes; dia++) {
            let elementoDia = document.createElement("span");
            elementoDia.textContent = dia;

            let mesFormatado = String(mes + 1).padStart(2, '0');
            let diaFormatado = String(dia).padStart(2, '0');
            let dataStringCompleta = `${ano}-${mesFormatado}-${diaFormatado}`;
            
            let dataInstancia = new Date(ano, mes, dia);
            let diaSemana = dataInstancia.getDay(); 

            if (dataInstancia < hoje) {
                elementoDia.classList.add("past-day");
            } 
            else if (diaSemana === 0) { 
                elementoDia.classList.add("sunday-disabled");
                elementoDia.title = "Barbearia Fechada aos Domingos";
            } 
            else {
                elementoDia.classList.add("active-day");

                if (inputData.value === dataStringCompleta) {
                    elementoDia.classList.add("selected-day");
                }

                elementoDia.addEventListener("click", function() {
                    document.querySelectorAll(".active-day").forEach(d => d.classList.remove("selected-day"));
                    elementoDia.classList.add("selected-day");
                    
                    inputData.value = dataStringCompleta;
                    estadoAgendamento.dataSelecionada = dataStringCompleta;
                    atualizarFluxoUI();
                });
            }

            gridDias.appendChild(elementoDia);
        }
    }

    btnPrev.addEventListener("click", () => {
        mesAtual--;
        if (mesAtual < 0) { mesAtual = 11; anoAtual--; }
        renderizarCalendario(mesAtual, anoAtual);
    });

    btnNext.addEventListener("click", () => {
        mesAtual++;
        if (mesAtual > 11) { mesAtual = 0; anoAtual++; }
        renderizarCalendario(mesAtual, anoAtual);
    });

    renderizarCalendario(mesAtual, anoAtual);
    atualizarFluxoUI();
});
</script>

</body>
</html>
<?php 
if (isset($conexao)) {
    $conexao->close();
}
?>