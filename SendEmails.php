<?php
#enviar emails para: novas vagas por area de interesse no usuario
# empresas que nao publicaram vagas nos ultimos 30 dias
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

                GROUP_CONCAT(
                    DISTINCT custom_vagas_areas.id 
                    ORDER BY custom_vagas_areas.id 
                    SEPARATOR ', '
                ) AS areas_ids,

                GROUP_CONCAT(
                    DISTINCT custom_vagas_areas.nome 
                    ORDER BY custom_vagas_areas.nome 
                    SEPARATOR ' | '
                ) AS areas,

                GROUP_CONCAT(
                    DISTINCT custom_vagas.id 
                    ORDER BY custom_vagas.id 
                    SEPARATOR ', '
                ) AS vagas_ids,

                GROUP_CONCAT(
                    DISTINCT custom_vagas.nome 
                    ORDER BY custom_vagas.nome 
                    SEPARATOR ' | '
                ) AS vagas

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

            GROUP BY
                custom_contrata_usuario.id,
                custom_contrata_usuario.nome;
            ";

        $this->db->executeSql($sql);

        return $this->db->fetchAll();
    }

    public function saveMailControl(array $sending, string $templateKey, string $status = 'sent')
    {

        if (empty($sending['to_email'])) {
            return false;
        }

        $email  = $sending['to_email'];
        $status = $status;

        $userId = !empty($sending['id_usuario'])
            ? (int) $sending['id_usuario']
            : 'NULL';

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

    public function NovasVagasSend(array $to, string $assunto, string $templateKey, array $anexos = [], $copy = null)
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

            if (!$this->SendMailControl($sending['id_usuario'], $templateKey)) {
                $logs[] = "Já enviado para {$sending['nome']} ({$sending['to_email']})";
                continue;
            }

            // conteúdo do email
            $template = file_get_contents('template_email/index.html');
            $template = str_replace('{titulo_email}', 'Novas Vagas', $template);
            $conteudo = "<p><strong>Olá, {$sending['nome']}!</strong></p>";
            $conteudo .= "<p>O ContrataFashion esta com novas vagas disponeis em suas areas de interesse, acesse o site para não perder nenhuma vaga</p>";
            $conteudo .= "<p><strong>Áreas:</strong> {$sending['areas']}</p>";
            $conteudo .= "<p><strong>Vagas:</strong> {$sending['vagas']}</p>";
            $conteudo .= "<p>Acesse nosso site para mais detalhes e candidate-se!</p>";
            
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
            'logs' => $logs
        ];
    }

    public function SendMailControl($idUser, $templateKey)
    {

        $db = $this->db;

        $sql = "
            SELECT id
            FROM botmail_send_control
            WHERE fk_contrata_usuario = '{$idUser}'
            AND email_category = '{$templateKey}'
            LIMIT 1
            ";

        $db->executeSql($sql);

        return $db->getNumRows() === 0;
    }

    
}
