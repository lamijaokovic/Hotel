<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Povezivanje s bazom podataka na localhost
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "projekat"; 

// Provjera veze s bazom podataka
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Povezivanje s bazom podataka nije uspjelo: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Provjera da li je unijet email
    if (empty($_POST['email'])) {
        echo "Molimo unesite vaš email.";
        exit;
    }

    if (isset($_POST['submit'])) {
        // Provjera praznih polja
        if (empty($_POST['ime']) || empty($_POST['prezime']) || empty($_POST['broj_telefona']) || empty($_POST['email']) || empty($_POST['dolazak']) || empty($_POST['odlazak']) || empty($_POST['broj_osoba']) || empty($_POST['soba'])) {
            echo "Molimo ispunite sva polja.";
            // Prekini daljnje izvršavanje koda
            exit;
        }

        // Validacija i priprema unesenih podataka
        $ime = $_POST['ime'];
        $prezime = $_POST['prezime'];
        $broj_telefona = $_POST['broj_telefona'];
        $email = $_POST['email'];
        $dolazak = $_POST['dolazak'];
        $odlazak = $_POST['odlazak'];
        $broj_osoba = $_POST['broj_osoba'];
        $soba = $_POST['soba'];


// Provjera valjanosti imena i prezimena (mora sadržavati najmanje 3 slova)
if (strlen($ime) < 3 || strlen($prezime) < 3) {
     echo '<script>alert("Ime i prezime moraju sadržavati najmanje 3 slova.")</script>';
    // Prekini daljnje izvršavanje koda
    exit;
}


        // Provjera ispravnosti formata datuma
        $datum_format = '/^\d{4}-\d{2}-\d{2}$/'; // Očekivani format datuma (YYYY-MM-DD)
        if (!preg_match($datum_format, $dolazak) || !preg_match($datum_format, $odlazak)) {
            echo "Molimo unesite ispravan format datuma (YYYY-MM-DD) za polja dolazak i odlazak.";
            // Prekini daljnje izvršavanje koda
            exit;
        }

        // Provjera valjanosti datuma
        $currentDate = date('Y-m-d'); // Trenutni datum
        if ($dolazak < $currentDate) {
            echo "Datum dolaska ne može biti u prošlosti.";
            // Prekini daljnje izvršavanje koda
            exit;
        }
        if ($odlazak <= $dolazak) {
            echo "Datum odlaska mora biti nakon datuma dolaska.";
            // Prekini daljnje izvršavanje koda
            exit;
        }

   // Provjera dostupnosti sobe za odabrane datume
$sql = "SELECT * FROM rezervacija WHERE soba = ? AND (dolazak <= ? AND odlazak >= ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $soba, $odlazak, $dolazak);
$stmt->execute();
$result = $stmt->get_result();

// Ako postoje rezultati, soba nije dostupna za odabrane datume
if ($result->num_rows > 0) {
    echo "Soba nije dostupna za odabrane datume. Molimo odaberite drugu sobu ili promijenite datume.";
    // Prekini daljnje izvršavanje koda
    exit;
}

        // Generisanje HTML-a za prikaz podataka iz rezervacije
        $html = "
        <!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <meta charset=\"UTF-8\" />
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
            <title>Booking Confirmation</title>
            <style>
                /* Add your custom styles here */
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                .header {
                    background-color: #007bff;
                    color: #fff;
                    padding: 10px;
                    text-align: center;
                    font-size: 24px;
                }
                .footer {
                    background-color: #f1f1f1;
                    color: #333;
                    padding: 10px;
                    text-align: center;
                    font-size: 14px;
                }
                .reservation-details {
                    margin-top: 20px;
                    border: 1px solid #ccc;
                    padding: 10px;
                }
                .text-center {
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">Rezervacija - Potvrda</div>

                <div id=\"form\">
                    <div class=\"reservation-details\">
                        <p><strong>Ime:</strong> $ime</p>
                        <p><strong>Prezime:</strong> $prezime</p>
                        <p><strong>Broj telefona:</strong> $broj_telefona</p>
                        <p><strong>Dolazak:</strong> $dolazak</p>
                        <p><strong>Odlazak:</strong> $odlazak</p>
                        <p><strong>Broj osoba:</strong> $broj_osoba</p>
                        <p><strong>Soba:</strong> $soba</p>
                    </div>

                    <p class=\"text-center\">Hvala što ste izabrali naš hotel!</p>
                </div>

                <div class=\"footer\">
                    Ovo je automatska email potvrda. Molimo vas da ne odgovarate na ovu poruku.
                </div>
            </div>
        </body>
        </html>
        ";

        // Slanje emaila
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'pimhotelbooking@gmail.com';
            $mail->Password = 'ntyodvfliuvrqefx';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            $mail->setFrom('pimhotelbooking@gmail.com', 'pimhotelbooking@gmail.com');
            $mail->addAddress($email);
            $mail->Subject = 'Potvrda rezervacije';
            $mail->Body = $html;
            $mail->isHTML(true); // Email koristi HTML format

            $mail->send();
            echo 'Email je uspešno poslat.';
        } catch (Exception $e) {
            echo "Došlo je do greške prilikom slanja emaila: " . $mail->ErrorInfo;
        }

        // Izvršavanje SQL upita za umetanje podataka u tablicu
        try {
            $sql = "INSERT INTO rezervacija (ime, prezime, broj_telefona, email, dolazak, odlazak, broj_osoba, soba) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $ime, $prezime, $broj_telefona, $email, $dolazak, $odlazak, $broj_osoba, $soba);
            $stmt->execute();
            
            // Poruka o uspješnoj rezervaciji
            echo "Uspješno ste rezervirali sobu!";
        } catch (Exception $e) {
            echo "Greška: " . $e->getMessage();
        }

        // Zatvaranje veze s bazom podataka
        $stmt->close();
        $conn->close();
    }
}

?>
