<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/mailer.php';
app_require_post_login(6);

if ($_POST) {
        
    $fields = [
        'fechasup', 'horasup', 'sede', 'area', 'responsableb', 'hallazgos', 'ryc', 
        'tappv', 'pasc', 'actsup'
    ];
    
    function hasEmptyFields($data, $fields) {
        foreach ($fields as $field) {
            if (isset($data[$field])){
                if (is_array($data[$field])){
                    if (empty($data[$field])){
                        return true;
                    }
                } else{
                    if (strlen(trim($data[$field])) === 0){
                        return true;
                    }
                }
            }
        }
        return false;
    }

if (!$_POST || hasEmptyFields($_POST, $fields)){
    echo "<script>
            $(document).ready(function(){
            Swal.fire({
            icon: 'warning',
            title: 'Advertencia',
            text: 'No puedes dejar campos vacíos, todos los campos son obligatorios!',
            })
        });
        </script>";
} else {
    $data = [];
    foreach ($fields as $field) {
        
        if(is_array($_POST[$field])){
        $data[$field] = implode(', ',$_POST[$field]);
        } else{
            $data[$field] = trim($_POST[$field] ?? '');
        }
        
    }
    $data['fechasup'] = date('d-m-Y', strtotime($_POST['fechasup']));
        
        $mail = new PHPMailer(true);
        app_configure_mailer($mail);
        
        //Recipients
        switch($data['sede']){
            case 'Pance':
                $mail->addAddress('adminpance@misterwings.com');
                break;
            case 'Ciudad Jardín':
                $mail->addAddress('adminciudadjardin@misterwings.com');
                break;
            case 'Jardín Plaza':
                $mail->addAddress('adminjardinplaza@misterwings.com');
                break;
            case 'Unicentro':
                $mail->addAddress('admin.unicentro@misterwings.com');
                break;
            case 'Limonar':
                $mail->addAddress('esquin@hotmail.com');
                $mail->addAddress('lenisalvaro@hotmail.com');
                $mail->addAddress('adminlimonar@misterwings.com');
                break;
            case 'San Fernando':
                $mail->addAddress('esquin@hotmail.com');
                $mail->addAddress('lenisalvaro@hotmail.com');
                $mail->addAddress('adminsanfernando@misterwings.com');
                break;
            case 'Granada':
                $mail->addAddress('admingranada@misterwings.com');
                $mail->addAddress('coor.granada@misterwings.com');
                break;
            case 'Chipichape':
                $mail->addAddress('esquin@hotmail.com');
                $mail->addAddress('adminlaflora@misterwings.com');
                $mail->addAddress('adminchipichape@misterwings.com');
                break;
            case 'Flora':
                $mail->addAddress('esquin@hotmail.com');
                $mail->addAddress('adminlaflora@misterwings.com');
                $mail->addAddress('coordinadorflora@misterwings.com');
                break;
            case 'Llanogrande':
                $mail->addAddress('esquin@hotmail.com');
                $mail->addAddress('lenisalvaro@hotmail.com');
                $mail->addAddress('coordinadorllanogrande@misterwings.com');
                break;
            case 'Bochalema':
                $mail->addAddress('adminbochalema@misterwings.com');
                $mail->addAddress('coor.bochalema@misterwings.com');
                break;
        }
        
        $email_list = [
            'gerente.administrativo@misterwings.com', 'gerencia@misterwings.com', 'gerente.franquicias@misterwings.com'
        ];

        $email_cc_list =[
            'supervisor.cocinas2@misterwings.com','supervisor.comercial@misterwings.com','supervisor.cocinas@misterwings.com',
            'operaciones.supervisor@misterwings.com','coordinador.operaciones@misterwings.com','soporte@misterwings.com'
        ];
        
        foreach($email_list as $email){
            $mail->addAddress($email);
        }

        foreach($email_cc_list as $email_cc){
            $mail->addCC($email_cc);
        }

        //Content
        $emailData = array_map(static function ($value) {
            return nl2br(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
        }, $data);
        $subjectSede = preg_replace('/[\r\n]+/', ' ', (string) $data['sede']);
        $mail->Subject = 'Reporte de Supervisión ' . $subjectSede;
        $mail->Body =
            "<h2 style=text-alling:center;>Información de Supervisión</h2>
            <p style='font-family:Century Gothic; font-size:17px;'><strong>Fecha de supervisión: </strong>".$emailData['fechasup']."</p>
            <p style='font-family:Century Gothic; font-size:17px;'><strong>Horario de supervisión: </strong>".$emailData['horasup']."</p>
            <p style='font-family:Century Gothic; font-size:17px;'><strong>Sede: </strong>".$emailData['sede']."</p>
            <p style='font-family:Century Gothic; font-size:17px;'><strong>Supervisor y Cargo: </strong>".$emailData['responsableb']."</p>
            
            <h2>Resumen de supervisión</h2>

            <li style='font-family:Century Gothic; font-size:17px;'><strong>Hallazgos encontrados: </strong>".$emailData['hallazgos']."</li>
            <br>
            <li style='font-family:Century Gothic; font-size:17px;'><strong>Retroalimentaciones: </strong>".$emailData['ryc']."</li>
            <br>            
            <li style='font-family:Century Gothic; font-size:17px;'><strong>Tareas asignadas para proxima visita: </strong>".$emailData['tappv']."</li>
            <br>
            <li style='font-family:Century Gothic; font-size:17px;'><strong>Plan de acción y/o recomendaciones: </strong>".$emailData['pasc']."</li>
            <br>
            <li style='font-family:Century Gothic; font-size:17px;'><strong>Otras actividades realizadas por el supervisor: </strong>".$emailData['actsup']."</li>";
        if (!$mail->send()) {
            echo "<script>
                    $(document).ready(function(){
                        Swal.fire({
                        icon: 'error',
                        title: 'La bitácora no fue enviada :(',
                        text: 'Hubo un error al enviar la bitácora por favor intentalo nuevamente.',
                    })
                });
                </script>";
        } else {
            echo "<script>
                    $(document).ready(function(){
                        Swal.fire({
                        icon: 'success',
                        title: 'Bitácora enviada',
                        text: '!Se ha enviado la bitácora con éxito!',
                        timerProgressBar: true,
                        allowOutsideClick: false,
                    })
                });
                </script>";
        }
    }
}
?>
