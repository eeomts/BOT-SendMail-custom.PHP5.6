<?php
require_once 'config/DBconn.php';
require_once 'vendor/autoload.php';

class SendEmails
{
    public $db;
    public $mail;


    public function __construct()
    {
        $this->db = new DB();
    }


    public function getCandidatoVaga()
    {
        $sql = " 
        SELECT 
        custom_contrata_usuario.email, 
        custom_candidato_vaga.fk_custom_contrata_usuario 
        FROM 
        custom_contrata_usuario
        RIGHT JOIN custom_candidato_vaga on custom_candidato_vaga.fk_custom_contrata_usuario = custom_contrata_usuario.id
        where custom_contrata_usuario.email = 'mateus.moreira1@ufu.br' 
        ";


        $this->db->executeSql($sql);

        return $this->db->fetchAll();
    }

    public function getUsuariosContrata(){

    $db = $this->db;
    $db->executeSql("
                SELECT 

               
                custom_contrata_usuario.email
                FROM custom_contrata_usuario
                WHERE custom_contrata_usuario.email IS NOT NULL AND custom_contrata_usuario.email = 'mateus.moreira1@ufu.br'          
                ");
    $result = $db->fetchAll();
    return $result;
    }

    public function sendCandidatoVaga($to)
    {
        if (!is_array($to) || empty($to)) {
            return false;
        }

        $config = require __DIR__ . '/config/emailCredentials.php';

        foreach ($to as $sending) {

            if (empty($sending['email'])) {
                continue;
            }

            $mail = new PHPMailer();
            $mail->isSMTP();

            $mail->Host = $config['host'];
            $mail->SMTPAuth = $config['SMTPauth'];
            $mail->SMTPDebug = 2;
            $mail->Username = $config['userName'];
            $mail->Password = $config['pass'];
            $mail->SMTPSecure = $config['SMTPSecure'];
            $mail->Port = $config['port'];
            $mail->Mailer = $config['Mailer'];
            $mail->Priority = $config['Priority'];

            $mail->CharSet = 'UTF-8';
            $mail->setFrom(
                $config['sender'],
                $config['fromName']
            );


            $mail->SMTPKeepAlive = false;

            $mail->isHTML(true);
            $mail->addAddress($sending['email']);

            $mail->Subject = 'Assunto do email';
            $mail->Body    = '<h3>Email de teste</h3><p>Conteúdo do email: eviado da funcao sendCandidatoVaga </p>';

            if (!$mail->send()) {
                error_log('Erro ao enviar para ' . $sending['email'] . ': ' . $mail->ErrorInfo);
            }

            unset($mail);
        }

        return true;
    }

    public function sendALLusers($to)
    {
        if (!is_array($to) || empty($to)) {
            return false;
        }

        $config = require __DIR__ . '/config/emailCredentials.php';

        foreach ($to as $sending) {

            if (empty($sending['email'])) {
                continue;
            }

            $mail = new PHPMailer();
            $mail->isSMTP();

            $mail->Host = $config['host'];
            $mail->SMTPAuth = $config['SMTPauth'];
            $mail->SMTPDebug = 2;
            $mail->Username = $config['userName'];
            $mail->Password = $config['pass'];
            $mail->SMTPSecure = $config['SMTPSecure'];
            $mail->Port = $config['port'];
            $mail->Mailer = $config['Mailer'];
            $mail->Priority = $config['Priority'];

            $mail->CharSet = 'UTF-8';
            $mail->setFrom(
                $config['sender'],
                $config['fromName']
            );


            $mail->SMTPKeepAlive = false;

            $mail->isHTML(true);
            $mail->addAddress($sending['email']);

            $mail->Subject = 'Assunto do email';
            $mail->Body    = '<h3>Email de teste</h3><p>enviado da funcao sendALLusers</p>';

            if (!$mail->send()) {
                error_log('Erro ao enviar para ' . $sending['email'] . ': ' . $mail->ErrorInfo);
            }

            unset($mail);
        }

        return true;
    }

}
