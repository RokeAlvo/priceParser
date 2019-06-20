<?php
if(!function_exists('uStristr')) {
    function uStristr($phrase, $stops)
    {
        if (empty($phrase)) {
            return false;
        }
        foreach ($stops as $stop) {
            $result = mb_stristr($phrase, $stop);
            if ($result) {
                return true;
            }
        }
        return false;
    }
}
if(!function_exists('pr')) {
    function pr($var, $mark = '', $tag = 'h3')
    {
        if ($var) {
            if ($mark) {
                echo "\n", "<{$tag}>", $mark, "</{$tag}>", "\n";
            }
            print_r($var);
        }
    }
}
if(!function_exists('sendMail')) {
    function sendMail($from, $to, $subject, $attachment = '', $smtp = [], $message = '')
    {
        //Create a new PHPMailer instance
        $mail = new PHPMailer;

        if ($smtp) {
            //Tell PHPMailer to use SMTP
            $mail->isSMTP();

            foreach ($smtp as $key => $value) {
                $mail->$key = $value;
            }
        } else {
            $mail->isMail();
        }

//Set who the message is to be sent from
        $mail->setFrom($from['address'], $from['mail']);

//Set an alternative reply-to address
//$mail->addReplyTo('replyto@example.com', 'First Last');

//Set who the message is to be sent to
        if (isset($to[0]['address'])) {
            foreach ($to as $item) {
                $mail->addAddress($item['address'], $item['name']);
            }
        } else {
            $mail->addAddress($to['address'], $to['name']);
        }


//Set the subject line
        $mail->Subject = $subject;

//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body

        $mail->msgHTML($message ? $message : $subject);

//Replace the plain text body with one created manually
        $mail->AltBody = $subject;

        if ($attachment) {
            $mail->addAttachment($attachment);
        }

        if (!$mail->send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
            return false;
        }

        return true;
    }
}
if(!function_exists('sendMailSimple')) {
    function sendMailSimple($message = '', $attachment = '', $customSubject = '')
    {
        global $from, $to, $smtp, $subject;
        if (!$customSubject) {
            $customSubject = $subject;
        }
        return sendMail($from, $to, $customSubject, $attachment, $smtp, $message);
    }
}
if(!function_exists('cutSpaceArticle')) {
    function cutSpaceArticle($str)
    {
        return str_replace(array('.', "\s", "\n", "r", "\t", '-', '|', '/', '\\', ' ', ','), '', $str);
    }
}
if(!function_exists('objectToArray')) {
    function objectToArray($d)
    {
        if (is_object($d)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map(__FUNCTION__, $d);
        } else {
            // Return array
            return $d;
        }
    }
}

if(!function_exists('cutHTML')) {
    function cutHTML($text){
        $search = array ("'<script[^>]*?>.*?</script>'si",  // Вырезает javaScript
            "'<[\/\!]*?[^<>]*?>'si",           // Вырезает HTML-теги
            "'([\r\n\t])[\s]+'"               // Вырезает пробельные символы
        );

        $replace = array ("",
            "",
            "\\1",




            "\"");

        return trim(preg_replace($search, $replace, $text));
    }
}