<?php

namespace App\Common;

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailManager{
    private $mail;

    public function __construct(){
        //Create an instance; passing `true` enables exceptions
        $this->mail = new PHPMailer(true);
    }

    public function setEmailSettings($host,$username,$password,$port){
        //Server settings
        // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $this->mail->isSMTP();                                            //Send using SMTP
        $this->mail->Host       = $host;                     //Set the SMTP server to send through
        $this->mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $this->mail->Username   = $username;                     //SMTP username
        $this->mail->Password   = $password;                               //SMTP password
        $this->mail->SMTPSecure = 'tls';            //Enable implicit TLS encryption
        $this->mail->Port       = $port;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    }

    public function setEmailRecipients($from,$to,$replyTo,$cc,$bcc){
        //Recipients
        // $this->mail->setFrom($from, 'Mailer');
        $this->mail->addAddress($to);     //Add a recipient
        //$this->mail->addReplyTo($replyTo, 'Information');
        //$this->mail->addCC($cc);
        //$this->mail->addBCC($bcc);
    }

    public function setEmailAttachments($attachments){
        //Attachments
        $this->mail->addAttachment($attachments);         //Add attachments
    }

    public function setEmailContent($subject,$body,$altBody){
        //Content
        $this->mail->isHTML(true);                                  //Set email format to HTML
        $this->mail->Subject = $subject;
        $this->mail->Body    = $body;
        $this->mail->AltBody = $altBody;
    }

    public function sendEmail(){
        try {
            $this->mail->send();
            echo 'Message has been sent';
            return true;
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
            return false;
        }
    }
}