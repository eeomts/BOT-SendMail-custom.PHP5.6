<?php 
/**
 * Uso da função enviarEmailCampanha():
 * @param string $tipo - Tipo: 'nova_vaga_area', 'empresas_inativas', 'vagas_antigas'
 * @param int $intervaloEnvio - Dias para controle de reenvio (não reenvia se já enviou nesse período)
 * @param int $intervaloBusca - Dias para busca no BD (ex: vagas dos últimos X dias)
 * @param string $assunto - Assunto do email (opcional)
 * @param array $anexos - Arquivos para anexar (opcional)
 * @param string $copy - Email para BCC (opcional)
 **/

require_once 'vendor/autoload.php';
require_once 'config/database/DBconn.php';
require_once 'config/emailCredentials.php';
require_once 'SendEmails.php';

$sendEmails = new SendEmails();

// Campanha 1: Novas vagas por área de interesse
$sendEmails->enviarEmailCampanha('nova_vaga_area', 7, 8, 'Novas vagas para você!');

// Campanha 2: Empresas inativas (30+ dias sem publicar)
$sendEmails->enviarEmailCampanha('empresas_inativas', 30, 30);

// Campanha 3: Vagas antigas (15+ dias)
$sendEmails->enviarEmailCampanha('vagas_antigas', 15, 15);
?>