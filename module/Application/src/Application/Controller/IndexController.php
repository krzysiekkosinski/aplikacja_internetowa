<?php 

namespace Application\Controller;
use Zend\Mvc\Controller\AbstractActionController;

use Zend\View\Model\ViewModel;
use Application\Model\Uzytkownik;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Kadra\Model\Kadra;
 

class IndexController extends AbstractActionController
{
    protected $uzytkownikTable;
    protected $kadraTable;
    
    public function indexAction() 
    {
 
         $this->sesja();
         $this->layout()->kluby = $this->KadraTable()->wszystko(null);
         return $this->redirect()->toRoute('/login');
 
    }
    
    public function loginAction() {
        $this->sesja();
        
        if (!empty($_POST['login']) && !empty($_POST['pwd'])) {
            $uzytkownicy = $this->Tabela()->wszystko(array('u_login' => $_POST['login'], 'u_haslo' => hash('sha512',$_POST['pwd'])));
            $zlicz = $uzytkownicy->count();
            $uzytkownik = $uzytkownicy->current();
            if ($zlicz == 1) {
                $_SESSION['id'] = $uzytkownik->id_uzytkownik;
                if ($_SESSION['id'] != 0) {
                    $_SESSION['name'] = $uzytkownik->u_imie.' '.$uzytkownik->u_nazwisko;
                    $_SESSION['funkcja'] = $uzytkownik->u_funkcja; 
                    
                    if($_SESSION['funkcja']=='admin'){
                    return $this->redirect()->toUrl('mecz');}
                        elseif ($_SESSION['funkcja']=='dzialacz') {
                            $_SESSION['id_zespol'] = $uzytkownik->id_zespol; 
                            return $this->redirect()->toUrl('podglad');
                        }
                        else {return $this->redirect()->toUrl('sprawozdanie');}
                } else
                    return $this->redirect()->toRoute('home');
            }
        }else {
            echo '<div class="alert alert-danger">Nie podano wszystkich danych</div>';
            return new ViewModel();
        }
    }

    public function registerAction() {
        $this->sesja();
        $this->layout()->kluby = $this->KadraTable()->wszystko(null);

        if (empty($_POST['Imie']) || empty($_POST['Nazwisko']) || empty($_POST['Login']) || empty($_POST['Haslo']) || empty($_POST['Powtorz_haslo']) || empty($_POST['Email'])) {
            echo '<div class="alert alert-danger">Nie podano wszystkich danych</div>';
            return new ViewModel();
        }
        if(($_POST['Haslo'])!=($_POST['Powtorz_haslo'])){
            echo '<div class="alert alert-danger">Hasła są różne</div>';
            return new ViewModel();
        }
        $uzytkownicy = $this->Tabela()->wszystko(array('u_mail' => $_POST['Email']));
        $zlicz = $uzytkownicy->count();
        if ($zlicz > 0) {
            echo '<div class="alert alert-danger">Użytkownik o podanym mailu już istnieje</div>';
            return new ViewModel();
        } else {

            $data = array(
                'id_uzytkownik' => "",
                'u_nazwisko' => addslashes(htmlspecialchars($_POST['Nazwisko'])),
                'u_imie' => addslashes(htmlspecialchars($_POST['Imie'])),
                'u_funkcja' => addslashes(htmlspecialchars($_POST['Funkcja'])),
                'id_zespol' => addslashes(htmlspecialchars($_POST['Id_zespol'])),
                'u_login' => addslashes(htmlspecialchars($_POST['Login'])),
                'u_haslo' => hash('sha512',(addslashes(htmlspecialchars($_POST['Haslo'])))),
                'u_mail' => addslashes(htmlspecialchars($_POST['Email'])),
            );

            $user = new Uzytkownik(); 
            $user->exchangeArray($data);
            $this->Tabela()->dodaj($user);

            $message = new Message();
            $message->addTo($data['u_mail'])
                    ->addFrom('adm_extranet_2@interia.pl', 'Internetowy system EXTRANET 2.0')
                    ->setSubject('Rejestracja');
            $wiadomosc = "Witaj " . $data['u_imie'] ." ". $data['u_nazwisko'].",\n\n" . "Rejestracja przebiegła pomyślnie możesz korzystać z internetowego systemu EXTRANET 2.0";
            $message->setBody($wiadomosc);

// Setup SMTP transport using LOGIN authentication
            $transport = new SmtpTransport();
            $options = new SmtpOptions(array(
                'host' => 'poczta.interia.pl',
                'connection_class' => 'login',
                'connection_config' => array(
                    'ssl' => 'tls',
                    'username' => 'adm_extranet_2@interia.pl',
                    'password' => 'krzysiek!!'
                ),
                'port' => 587,
            ));

            $transport->setOptions($options);
            $transport->send($message);
            //***********************return $this->redirect()->toRoute('home');
            echo '<div class="alert alert-success">Rejestracja przebiegła pomyślnie. Na podany adres wysłano również potwierdzenie o pomyślności rejestracji.</div>';
            //return new ViewModel(array('kluby' => $kluby,));
            return new ViewModel();
        }
        
    }

    public function logoutAction()
    {
        session_destroy();
        return $this->redirect()->toRoute('home');
    }
    
    
    

//public function losowehaslo() {
//        $znaki= "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
//$wynik="";
// 
//for ($i=0; $i < 8; $i++)
//{
// 
//$wynik .= substr($znaki, rand(0, strlen($znaki)-1), 1);    
//}
//  
//return $wynik;
//}

    private function sesja()
    {
        session_start();
        if (!isset($_SESSION['id'])) {$_SESSION['id'] = 0;}
    }
 
    public function KadraTable() {
        if (!$this->kadraTable) {
            $sm = $this->getServiceLocator();
            $this->kadraTable = $sm->get('Kadra\Model\KadraTable');
        }

        return $this->kadraTable;
    }
    
    public function Tabela() {
        if (!$this->uzytkownikTable) {
            $sm = $this->getServiceLocator();
            $this->uzytkownikTable = $sm->get('Application\Model\UzytkownikTable');
        }

        return $this->uzytkownikTable;
    }
}	