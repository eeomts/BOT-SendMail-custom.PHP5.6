<?php
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

    public function getCandidatosAreaNovaVaga($intervalo = 8)
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
            WHERE custom_vagas.created >= DATE_SUB(NOW(), INTERVAL {$intervalo} DAY) 
            ORDER BY custom_contrata_usuario.id, custom_vagas.id;
            ";

        $this->db->executeSql($sql);
        return $this->db->fetchAll();
    }

    public function getEmpresas($tipo = 'vagas_antigas', $intervalo = 30)
    {
        $select = "
            SELECT
                netflex_cliente.id AS id_empresa,
                netflex_cliente.nome AS empresa,
                netflex_cliente.email AS to_email,
                custom_vagas.id AS id_vaga,
                custom_vagas.nome AS nome_vaga,
                custom_vagas.created AS data_publicacao
        ";

        switch ($tipo) {
            case 'vagas_antigas':
                $sql = $select . "
                    FROM custom_vagas
                    INNER JOIN netflex_cliente
                    ON netflex_cliente.id = custom_vagas.fk_cliente
                    WHERE custom_vagas.created <= DATE_SUB(NOW(), INTERVAL {$intervalo} DAY) 
                    AND netflex_cliente.fk_cliente_status = 1
                ";
                break;

            case 'inativas':
                $sql = $select . "
                    FROM netflex_cliente
                    LEFT JOIN custom_vagas
                    ON custom_vagas.fk_cliente = netflex_cliente.id
                    AND custom_vagas.created >= DATE_SUB(NOW(), INTERVAL {$intervalo} DAY)
                    WHERE custom_vagas.id IS NULL 
                    AND netflex_cliente.fk_cliente_status = 1
                ";
                break;
        }

        $this->db->executeSql($sql);
        return $this->db->fetchAll();
    }

    public function SendMail($sending, $assunto, $mensagem, $templateKey, $anexos = [], $copy = null)
    {

        $config = require __DIR__ . '/config/emailCredentials.php';

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

    public function enviarEmailCampanha($tipo, $intervaloEnvio, $intervaloBusca, $assunto = null, $anexos = [], $copy = null)
    {
        $logs = [];
        $to = [];
        $templateKey = '';
        $tituloEmail = '';
        $arquivoMensagem = '';
        $idField = '';
        $nomeField = '';

        
        switch ($tipo) {
            case 'nova_vaga_area':
                $to = $this->getCandidatosAreaNovaVaga($intervaloBusca);
                $templateKey = 'nova_vaga_area';
                $tituloEmail = 'Nova Vaga em Sua Área';
                $arquivoMensagem = 'template_email/messages/nova_vaga_area.txt';
                $assunto = isset($assunto) ? $assunto : 'Nova vaga na sua área de interesse!';
                $idField = 'id_usuario';
                $nomeField = 'nome';
                break;

            case 'empresas_inativas':
                $to = $this->getEmpresas('inativas', $intervaloBusca);
                $templateKey = 'empresas_inativas_30dias';
                $tituloEmail = 'Sentimos sua falta!';
                $arquivoMensagem = 'template_email/messages/empresas_inativas_30dias.txt';
                $assunto = isset($assunto) ? $assunto : 'Sentimos sua falta!';
                $idField = 'id_empresa';
                $nomeField = 'empresa';
                break;

            case 'vagas_antigas':
                $to = $this->getEmpresas('vagas_antigas', $intervaloBusca);
                $templateKey = 'empresas_vagas_antigas';
                $tituloEmail = 'Sua vaga ainda está ativa?';
                $arquivoMensagem = 'template_email/messages/empresas_vagas_antigas.txt';
                $assunto = isset($assunto) ? $assunto : 'Atualize suas vagas!';
                $idField = 'id_empresa';
                $nomeField = 'empresa';
                break;

            default:
                return [
                    'success' => false,
                    'logs' => ['Tipo de campanha inválido: ' . $tipo]
                ];
        }

        
        if (empty($to)) {
            return [
                'success' => false,
                'logs' => ['Nenhum destinatário encontrado para campanha: ' . $tipo]
            ];
        }

        $conteudoBase = file_get_contents($arquivoMensagem);
        if ($conteudoBase === false) {
            return [
                'success' => false,
                'logs' => ['Erro ao carregar arquivo de mensagem: ' . $arquivoMensagem]
            ];
        }

        foreach ($to as $sending) {

            if (empty($sending['to_email'])) {
                $logs[] = "Destinatário sem email ({$sending[$nomeField]})";
                continue;
            }

            if (!$this->SendMailControl($sending[$idField], $templateKey, $intervaloEnvio)) {
                $logs[] = "Já enviado para {$sending[$nomeField]} ({$sending['to_email']})";
                continue;
            }

            $template = file_get_contents('template_email/index.html');
            $template = str_replace('{titulo_email}', $tituloEmail, $template);

            $conteudo = $this->substituirPlaceholders($conteudoBase, $sending, $intervaloBusca);

            $template = str_replace('{conteudo_email}', $conteudo, $template);
            $mensagem = $template;

            $this->SendMail($sending, $assunto, $mensagem, $templateKey, $anexos, $copy);
            $logs[] = "Email enviado para {$sending[$nomeField]} ({$sending['to_email']})";
        }

        return [
            'success' => true,
            'tipo' => $tipo,
            'total_destinatarios' => count($to),
            'logs' => $logs
        ];
    }

    private function substituirPlaceholders($conteudo, $dados, $intervalo)
    {
        $placeholders = array(
            '{nome}' => isset($dados['nome']) ? $dados['nome'] : '',
            '{empresa}' => isset($dados['empresa']) ? $dados['empresa'] : '',
            '{area}' => isset($dados['area']) ? $dados['area'] : '',
            '{vaga}' => isset($dados['vaga']) ? $dados['vaga'] : '',
            '{vaga_id}' => isset($dados['vaga_id']) ? $dados['vaga_id'] : '',
            '{nome_vaga}' => isset($dados['nome_vaga']) ? $dados['nome_vaga'] : '',
            '{intervalo}' => $intervalo,
        );

        return str_replace(array_keys($placeholders), array_values($placeholders), $conteudo);
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
