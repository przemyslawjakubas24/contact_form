<?php
// Konfiguracja do instalacji PHPMailer przez Composer
// Wykonaj: composer require phpmailer/phpmailer

// Autoload plików z Composera
require 'vendor/autoload.php';

// Import klas PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Funkcja do filtrowania danych wejściowych
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Sprawdzenie czy formularz został wysłany
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Pobranie i filtrowanie danych z formularza
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : 'Nie podano';
    $service = sanitize_input($_POST['service']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);
    $contactPref = isset($_POST['contact_pref']) ? sanitize_input($_POST['contact_pref']) : 'email';
    
    // Walidacja podstawowych pól
    if (empty($name) || empty($email) || empty($subject) || empty($message) || empty($service)) {
        header('Location: index.html?status=error');
        exit();
    }
    
    // Walidacja e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: index.html?status=error');
        exit();
    }

    // Inicjalizacja PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Konfiguracja serwera
        $mail->SMTPDebug = 0;                      // Wyłącz debugowanie (0 = off, 2 = verbose)
        $mail->isSMTP();                           // Użyj SMTP
        $mail->Host       = 'smtp.example.com';    // Serwer SMTP
        $mail->SMTPAuth   = true;                  // Włącz autoryzację SMTP
        $mail->Username   = 'user@example.com';    // Nazwa użytkownika SMTP
        $mail->Password   = 'hasło';              // Hasło SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Włącz szyfrowanie TLS
        $mail->Port       = 587;                   // Port TCP dla połączenia

        // Odbiorcy
        $mail->setFrom('noreply@twojadomena.pl', 'Formularz kontaktowy');
        $mail->addAddress('kontakt@twojadomena.pl');     // Dodaj odbiorcę
        $mail->addReplyTo($email, $name);               // Adres do odpowiedzi

        // Załączniki
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $attachmentTmpName = $_FILES['attachment']['tmp_name'];
            $attachmentName = $_FILES['attachment']['name'];
            $attachmentSize = $_FILES['attachment']['size'];
            
            // Sprawdzenie rozmiaru pliku (max 5MB)
            if ($attachmentSize <= 5 * 1024 * 1024) {
                $mail->addAttachment($attachmentTmpName, $attachmentName);
            }
        }

        // Treść
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Formularz kontaktowy: ' . $subject;
        
        // Przygotowanie treści e-maila
        $emailContent = "
        <h2>Nowa wiadomość z formularza kontaktowego</h2>
        <p><strong>Imię i nazwisko:</strong> {$name}</p>
        <p><strong>E-mail:</strong> {$email}</p>
        <p><strong>Telefon:</strong> {$phone}</p>
        <p><strong>Usługa:</strong> {$service}</p>
        <p><strong>Temat:</strong> {$subject}</p>
        <p><strong>Preferowany kontakt:</strong> {$contactPref}</p>
        <p><strong>Wiadomość:</strong></p>
        <div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>
            " . nl2br($message) . "
        </div>
        <p><small>Wiadomość wysłana z formularza kontaktowego na stronie " . $_SERVER['HTTP_HOST'] . ".</small></p>
        ";
        
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $emailContent));

        $mail->send();
        header('Location: index.html?status=success');
        exit();
    } catch (Exception $e) {
        header('Location: index.html?status=error');
        exit();
    }
} else {
    // Jeśli ktoś próbuje uzyskać bezpośredni dostęp do pliku send.php
    header('Location: index.html');
    exit();
}
?>