<?php
#enviar emails para: novas vagas por area de interesse no usuario
#empresas que nao publicaram vagas nos ultimos 30 dias
#verificar atividade da vaga a cada 15 dias

/* organizacao:
 1 - funcoes get usuarios e empresas do banco;
 2 - funcoes send para cada case de envio de acrodo com os gets
 3- funcoes de controle de envio dos emails inserts e selects da tabela botmail_send_control
*/

require_once 'config/database/DBconn.php';
require_once 'vendor/autoload.php';

class SendEmails
{
    public $db;
    public $mail;


    public function __construct()
    {
        $this->db = new DB();
    }

    public function getCandidatosAreaNovaVaga()
    {

        $sql = "
            SELECT 
                custom_contrata_usuario.nome,
                custom_contrata_usuario.id AS id_usuario,
                custom_contrata_usuario.email as to_email,
                custom_vagas_areas.id AS area_id,
                custom_vagas_areas.nome AS area,
                custom_vagas.id AS vaga_id,
                custom_vagas.nome AS vaga

            FROM custom_contrata_usuario

            INNER JOIN custom_candidato_vaga
                ON custom_candidato_vaga.fk_custom_contrata_usuario = custom_contrata_usuario.id

            INNER JOIN custom_vagas
                ON custom_vagas.id = custom_candidato_vaga.fk_custom_vaga

            INNER JOIN custom_vagas_rel
                ON custom_vagas_rel.fk_custom_vaga = custom_vagas.id
            AND custom_vagas_rel.tabela = 'custom_vagas_areas'

            INNER JOIN custom_vagas_areas
                ON custom_vagas_areas.id = custom_vagas_rel.fk_registro

            WHERE custom_vagas.created >= DATE_SUB(NOW(), INTERVAL 8 DAY) 
            ORDER BY custom_contrata_usuario.id, custom_vagas.id;
            ";

        $this->db->executeSql($sql);

        return $this->db->fetchAll();
    }

    public function getEmpresasVagas()
    {
        $sql = "
            SELECT
            netflex_cliente.id AS id_empresa,
            netflex_cliente.nome AS empresa,
            netflex_cliente.email AS to_email,

            custom_vagas.id AS id_vaga,
            custom_vagas.nome AS nome_vaga,
            custom_vagas.created AS data_publicacao
            FROM custom_vagas
            INNER JOIN netflex_cliente
            ON netflex_cliente.id = custom_vagas.fk_cliente
            WHERE custom_vagas.created <= DATE_SUB(NOW(), INTERVAL 30 DAY) AND netflex_cliente.fk_cliente_status = 1;
        
        ";

        $this->db->executeSql($sql);
        return $this->db->fetchAll();
    }

    public function getEmpresasInativas30diasMais()
    {

        $sql = "
        SELECT
		netflex_cliente.id as id_usuario,
        netflex_cliente.nome as empresa,
        netflex_cliente.email as to_email,
		custom_vagas.nome
		
        FROM netflex_cliente
        LEFT JOIN custom_vagas
        ON custom_vagas.fk_cliente = netflex_cliente.id
        AND custom_vagas.created >= DATE_SUB(NOW(), INTERVAL 31 DAY)
        WHERE custom_vagas.id IS NULL AND netflex_cliente.fk_cliente_status = 1;
        ";

        $this->db->executeSql($sql);
        return $this->db->fetchAll();
    }



    public function NovasVagasSend( $to,  $assunto,  $templateKey,  $intervalo, $anexos = [], $copy = null)
    {
        $logs = [];

        if (empty($to)) {
            return [
                'success' => false,
                'logs' => ['Nenhum destinatário encontrado']
            ];
        }

        $config = require __DIR__ . '/config/emailCredentials.php';

        foreach ($to as $sending) {

            if (empty($sending['to_email'])) {
                $logs[] = "Usuário sem email ({$sending['nome']})";
                continue;
            }

            // Chama a função SendMailControl
            if (!$this->SendMailControl($sending['id_usuario'], $templateKey, $intervalo)) {
                $logs[] = "Já enviado para {$sending['nome']} ({$sending['to_email']})";
                continue;
            }

            // conteúdo do email
            $template = file_get_contents('template_email/index.html');
            $template = str_replace('{titulo_email}', 'Nova Vaga em Sua Área', $template);
            $conteudo = "<p><strong>Olá, {$sending['nome']}!</strong></p>";
            $conteudo .= "<p>Uma nova vaga foi publicada em uma área de seu interesse!</p>";
            $conteudo .= "<p><strong>Área:</strong> {$sending['area']}</p>";
            $conteudo .= "<p><strong>Vaga:</strong> {$sending['vaga']}</p>";
            $conteudo .= "<p>Acesse o site para mais detalhes e candidate-se!</p>";
            $conteudo .= "<a href='https://contratafashion.com/app/login/{$sending['vaga_id']}'><button type='submit' class='btn btn-primary align-items-center justify-content-center rounded' style='padding: 10px 30px;'>Ver Vaga</button></a>";

            $template = str_replace('{conteudo_email}', $conteudo, $template);
            $mensagem = $template;


            $mail = new PHPMailer(true);
            $status = 'failed';

            try {
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->SMTPAuth = $config['SMTPauth'];
                $mail->SMTPDebug = 2; // 2 debug
                $mail->Username = $config['userName'];
                $mail->Password = $config['pass'];
                $mail->SMTPSecure = $config['SMTPSecure'];
                $mail->Port = $config['port'];
                $mail->Mailer = $config['Mailer'];
                $mail->Priority = $config['Priority'];

                $mail->CharSet = 'UTF-8';
                $mail->setFrom($config['sender'], $config['fromName']);
                $mail->isHTML(true);
                $mail->addAddress($sending['to_email']);

                if ($copy) {
                    $mail->addBCC($copy);
                }

                $mail->Subject = $assunto;
                $mail->Body = $mensagem;

                foreach ($anexos as $file) {
                    if (file_exists($file)) {
                        $mail->addAttachment($file);
                    }
                }

                if ($mail->send()) {
                    $status = 'sent';
                }
            } catch (Exception $e) {
                $status = 'failed';
            }

            $this->saveMailControl(
                $sending,
                $templateKey,
                $status
            );


            unset($mail);
        }

        return [
            'success' => true,
            'users' => $to['usuario']
        ];
    }





    public function SendEmpresas30diasMais($intervalo, $assunto = 'Sentimos sua falta!',  $templateKey = 'empresas_inativas_30dias', $anexos = [], $copy = null )
    {

        $logs = [];

        $to = $this->getEmpresasInativas30diasMais();

        if (empty($to)) {
            return [
                'success' => false,
                'logs' => ['Nenhum destinatário encontrado']
            ];
        }

        $config = require __DIR__ . '/config/emailCredentials.php';

        foreach ($to as $sending) {

            if (empty($sending['to_email'])) {
                $logs[] = "Usuário sem email ({$sending['empresa']})";
                continue;
            }

            if (!$this->SendMailControl($sending['id_usuario'], $templateKey, $intervalo)) {
                $logs[] = "Já enviado para {$sending['empresa']} ({$sending['to_email']})";
                continue;
            }

            // conteúdo do email
            $template = file_get_contents('template_email/index.html');
            $template = str_replace('{titulo_email}', 'Sentimos sua falta!', $template);
            $conteudo = "<p><strong>Olá, {$sending['empresa']}!</strong></p>";
            $conteudo .= "<p>Notamos que você não publicou novas vagas no ContrataFashion há mais de 30 dias.</p>";
            $conteudo .= "<p>Temos diversos candidatos qualificados aguardando por novas oportunidades!</p>";
            $conteudo .= "<p>Que tal publicar uma nova vaga hoje e encontrar o profissional ideal para sua equipe?</p>";
            $conteudo .= "<p>Acesse nosso site e publique suas vagas agora mesmo!</p>";

            $template = str_replace('{conteudo_email}', $conteudo, $template);
            $mensagem = $template;


            $mail = new PHPMailer(true);
            $status = 'failed';

            try {
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->SMTPAuth = $config['SMTPauth'];
                $mail->SMTPDebug = 2; // 2 debug
                $mail->Username = $config['userName'];
                $mail->Password = $config['pass'];
                $mail->SMTPSecure = $config['SMTPSecure'];
                $mail->Port = $config['port'];
                $mail->Mailer = $config['Mailer'];
                $mail->Priority = $config['Priority'];

                $mail->CharSet = 'UTF-8';
                $mail->setFrom($config['sender'], $config['fromName']);
                $mail->isHTML(true);
                $mail->addAddress($sending['to_email']);

                if ($copy) {
                    $mail->addBCC($copy);
                }

                $mail->Subject = $assunto;
                $mail->Body = $mensagem;

                foreach ($anexos as $file) {
                    if (file_exists($file)) {
                        $mail->addAttachment($file);
                    }
                }

                if ($mail->send()) {
                    $status = 'sent';
                }
            } catch (Exception $e) {
                $status = 'failed';
            }

            $this->saveMailControl(
                $sending,
                $templateKey,
                $status
            );


            unset($mail);
        }

        return [
            'success' => true,
            'empresas' => $to['empresas']
        ];
    }



    public function SendEmpresasVagasAntigas($intervalo, $assunto = 'Atualize suas vagas!',  $templateKey = 'empresas_vagas_antigas_15dias', $anexos = [], $copy = null)
    {
        $to = $this->getEmpresasVagas();
        $logs = [];

        
        
        if (!$this->SendMailControl($to['id_usuario'], $templateKey, $intervalo)) {
            return [
                'success' => false,
                'message' => 'Email já enviado recentemente.'
            ];
        }

        if (empty($to)) {
            return [
                'success' => false,
                'logs' => ['Nenhum destinatário encontrado']
            ];
        }

        
        

        $config = require __DIR__ . '/config/emailCredentials.php';

        foreach ($to as $sending) {

            if (empty($sending['to_email'])) {
                $logs[] = "Empresa sem email ({$sending['empresa']})";
                continue;
            }

            if (!$this->SendMailControl($sending['id_empresa'], $templateKey, $intervalo)) {
                $logs[] = "Já enviado para {$sending['empresa']} ({$sending['to_email']})";
                continue;
            }

            // conteúdo do email
            $template = file_get_contents('template_email/index.html');
            $template = str_replace('{titulo_email}', 'Sua vaga ainda está ativa?', $template);
            $conteudo = "<p><strong>Olá, {$sending['empresa']}!</strong></p>";
            $conteudo .= "<p>Notamos que sua vaga <strong>{$sending['nome_vaga']}</strong> foi publicada há mais de $intervalo dias no ContrataFashion.</p>";
            $conteudo .= "<p><strong>Essa vaga ainda está ativa?</strong></p>";
            $conteudo .= "<p>Se a vaga já foi preenchida, é importante inativá-la para evitar candidaturas desnecessárias.</p>";
            $conteudo .= "<p>Se a vaga ainda está aberta, considere:</p>";
            $conteudo .= "<ul>";
            $conteudo .= "<li>Atualizar a descrição para maior visibilidade</li>";
            $conteudo .= "<li>Renovar a publicação para alcançar mais candidatos</li>";
            $conteudo .= "<li>Revisar os requisitos da vaga</li>";
            $conteudo .= "</ul>";
            $conteudo .= "<p>Acesse nosso site para gerenciar suas vagas!</p>";

            $template = str_replace('{conteudo_email}', $conteudo, $template);
            $mensagem = $template;


            $mail = new PHPMailer(true);
            $status = 'failed';

            try {
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->SMTPAuth = $config['SMTPauth'];
                $mail->SMTPDebug = 2; // 2 debug
                $mail->Username = $config['userName'];
                $mail->Password = $config['pass'];
                $mail->SMTPSecure = $config['SMTPSecure'];
                $mail->Port = $config['port'];
                $mail->Mailer = $config['Mailer'];
                $mail->Priority = $config['Priority'];

                $mail->CharSet = 'UTF-8';
                $mail->setFrom($config['sender'], $config['fromName']);
                $mail->isHTML(true);
                $mail->addAddress($sending['to_email']);

                if ($copy) {
                    $mail->addBCC($copy);
                }

                $mail->Subject = $assunto;
                $mail->Body = $mensagem;

                foreach ($anexos as $file) {
                    if (file_exists($file)) {
                        $mail->addAttachment($file);
                    }
                }

                if ($mail->send()) {
                    $status = 'sent';
                }
            } catch (Exception $e) {
                $status = 'failed';
            }

            $this->saveMailControl(
                $sending,
                $templateKey,
                $status
            );


            unset($mail);
        }

        return [
            'success' => true,
            'pessoas' => $to['empresas']
        ];
    }

    public function saveMailControl($sending, $templateKey, $status = 'sent')
    {

        if (empty($sending['to_email'])) {
            return false;
        }

        $email  = $sending['to_email'];
        $status = $status;

        $userId = !empty($sending['id_usuario'])
            ? (int) $sending['id_usuario']
            : (!empty($sending['id_empresa']) 
                ? (int) $sending['id_empresa'] 
                : 'NULL');

        $this->db->executeSql("
                INSERT INTO botmail_send_control (
                    to_email,
                    email_category,
                    fk_contrata_usuario,
                    status,
                    sent_at
                ) VALUES (
                    '{$email}',
                    '{$templateKey}',
                    {$userId},
                    '{$status}',
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    status = '{$status}',
                    sent_at = NOW()
                ");

        return true;
    }

    public function SendMailControl($idUser, $templateKey, $intervalo)
    {

        $db = $this->db;

        $sql = "
            SELECT id
            FROM botmail_send_control
            WHERE fk_contrata_usuario = '{$idUser}'
            AND email_category = '{$templateKey}'
            AND sent_at >= DATE_SUB(NOW(), INTERVAL {$intervalo} DAY)
            ORDER BY sent_at DESC
            LIMIT 1
            ";

        $db->executeSql($sql);

        return $db->getNumRows() === 0;
    }
}
