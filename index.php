<?php 
require_once 'vendor/autoload.php';
require_once 'config/database/DBconn.php';
require_once 'config/emailCredentials.php';
require_once 'SendEmails.php';

// $sendEmails = new SendEmails();

// $candidatosVagas = $sendEmails->getCandidatoVaga();
// $UsersContrata = $sendEmails->getUsuariosContrata();
// $novasVagas = $sendEmails->getCandidatosAreaNovaVaga();





$sendEmails = new SendEmails();

$candidatos = $sendEmails->getCandidatosAreaNovaVaga();

$result = $sendEmails->NovasVagasSend(
    $candidatos,
    'Novas vagas para você',
    'nova_vaga_area'
);

echo "<div style='background:#f9f9f9;padding:15px;border:1px solid #ccc'>";

echo "<h3>Log de envio – Novas Vagas</h3>";

if ($result['success']) {
    echo "<p style='color:green'>✓ Processo executado</p>";
} else {
    echo "<p style='color:red'>✗ Erro no processo</p>";
}

echo "<p><strong>Usuários encontrados:</strong> " . count($candidatos) . "</p>";

echo "<ul>";

foreach ($result['logs'] as $log) {
    echo "<li>{$log}</li>";
}

echo "</ul>";
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