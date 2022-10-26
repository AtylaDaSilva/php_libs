# php_libs
PHP libraries made with ❤️ by me

* convertTime

* fetch_emails
  Fetches unread emails from an email inbox. So far, I've only tested it with Outlook.
  Usage:
    1 - Instanciate the class Inbox and pass to it the args $server, $user and $password. 
    Example.:
    
    $server = "{outlook.office365.com/imap/ssl/novalidate-cert}INBOX";
    $user = "yourname@service.com";
    $password = "your_password";
    
     $inbox = new Inbox($server, $user, $password);
    
    2 - Profit.
