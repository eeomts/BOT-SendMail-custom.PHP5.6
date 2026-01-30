<?php 
require_once 'vendor/autoload.php';
require_once 'config/database/DBconn.php';
require_once 'config/emailCredentials.php';
require_once 'SendEmails.php';

// $sendEmails = new SendEmails();

// $candidatosVagas = $sendEmails->getCandidatoVaga();
// $UsersContrata = $sendEmails->getUsuariosContrata();
// $novasVagas = $sendEmails->getCandidatosAreaNovaVaga();



echo '<pre>';

$sendEmails = new SendEmails();

$candidatos = $sendEmails->getCandidatosAreaNovaVaga();

$result = $sendEmails->NovasVagasSend(
    $candidatos, #alvos para o envio do email
    'Novas vagas para você', # assunto do email
    'nova_vaga_area', #template_key verificador na tabela de botmail_send_control
    7 #intervalo de dias para a busca, busca vagas dos ultimos dias
);

echo "<div style='background:#f9f9f9;padding:15px;border:1px solid #ccc'>";

echo "<h3>Log de envio – Novas Vagas</h3>";

if ($result['success']) {
    echo "<p style='color:green'>✓ Processo executado</p>";
} else {
    echo "<p style='color:red'>✗ Erro no processo</p>";
}

echo "<p><strong>Usuários encontrados:</strong> " . count($candidatos) . "</p>";
echo "</div>";



#envia email para empresas inativas no coontratafahion a mais de 30 dias
$resultEmpresas = $sendEmails->SendEmpresas30diasMais(30);


echo "<div style='background:#fff3cd;padding:15px;border:1px solid #ffc107;margin-top:20px'>";
echo "<h3>Log de envio – Empresas Inativas 30+ dias</h3>";
if ($resultEmpresas['success']) {
    echo "<p style='color:green'>✓ Processo executado</p>";
} else {
    echo "<p style='color:red'>✗ Erro no processo</p>";
}
echo "<p><strong>Empresas encontradas:</strong> " . count($empresasInativas) . "</p>";



echo "</div>";
#envia um email para empresas que tem vagas postadas a 30 dias ou mais
$resultEmpresasVagas = $sendEmails->SendEmpresasVagasAntigas(30);

echo "<div style='background:#e7f3ff;padding:15px;border:1px solid #0d6efd;margin-top:20px'>";

echo "<h3>Log de envio – Empresas com Vagas Antigas 15+ dias</h3>";

if ($resultEmpresasVagas['success']) {
    echo "<p style='color:green'>✓ Processo executado</p>";
} else {
    echo "<p style='color:red'>✗ Erro no processo</p>";
}

echo "<p><strong>Vagas encontradas:</strong> " . count($empresasVagasAntigas) . "</p>";

echo "<ul>";


echo "</ul>";
echo '</pre>';
echo "</div>";

// echo "<p>Candidatos encontrados: " . count($candidatosVagas) . "</p>";
// echo "<pre>" . print_r($candidatosVagas, true) . "</pre>";


// echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;'>";
// echo "<strong>Log de envio CANDIDATO VAGA:</strong><br>";


// ob_start();
// $result = $sendEmails->sendCandidatoVaga($candidatosVagas);
// $debugOutput = ob_get_clean();

// echo nl2br(htmlspecialchars($debugOutput));
// echo "</div>";

// if ($result) {
//     echo "<p style='color: green;'>✓ Emails enviados com sucesso!</p>";
// } else {
//     echo "<p style='color: red;'>✗ Falha ao enviar emails</p>";
// }
// echo "<p><small>Caminho do log: " . ini_get('error_log') . "</small></p>";

// echo "<p>Usuarios encontrados: " . count($UsersContrata) . "</p>";
// echo "<pre>" . print_r($UsersContrata, true) . "</pre>";

// echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;'>";
// echo "<strong>Log de envio USUARIOS CONTRATA:</strong><br>";


// ob_start();
// $result2 = $sendEmails->sendALLusers($UsersContrata);
// $debugOutput = ob_get_clean();

// echo nl2br(htmlspecialchars($debugOutput));
// echo "</div>";

// if ($result2) {
//     echo "<p style='color: green;'>✓ Emails enviados com sucesso!</p>";
// } else {
//     echo "<p style='color: red;'>✗ Falha ao enviar emails</p>";
// }
// echo "<p><small>Caminho do log: " . ini_get('error_log') . "</small></p>";
?>