<?php

class Inbox
{
    private $server;
    private $user;
    private $password;
    private $inbox;
    private $emailIDs;
    private $emails = array();

    public function __construct($server, $user, $password)
    {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->connect();
    }

    //Opens IMAP connection. Returns an IMAP\Connection instance on success, or false on failure.
    public function connect()
    {
        if (!isset($this->inbox)) {

            return $this->inbox = imap_open($this->server, $this->user, $this->password) or die("Could not connect to email service: " . imap_last_error());
        }

        return false;
    }

    //Closes IMAP connnection. Returns true on success, or false on failure.
    public function disconnect()
    {
        if (isset($this->inbox)) {
            $inbox = $this->inbox;
            $this->inbox = "";

            return imap_close($inbox);
        }

        return false;
    }

    //Returns an array containing the UID of all unseen emails.
    public function listNewEmails()
    {
        if (isset($this->inbox) && $this->inbox != false) {
            return $this->emailIDs = imap_search($this->inbox, "UNSEEN", SE_UID);
        }

        return false;
    }

    public function fetchEmails()
    {
        if (
            isset($this->inbox) && $this->inbox != false &&
            isset($this->emailIDs) && $this->emailIDs != false
        ) {

            $i = 0;
            foreach ($this->emailIDs as $email) {
                $overview = imap_fetch_overview($this->inbox, $email, FT_UID);
                $structure = imap_fetchstructure($this->inbox, $email, FT_UID);
                $body = imap_fetchbody($this->inbox, $email, "1", FT_UID); //"1" => TEXT/PLAIN

                $this->emails[$i] = array(
                    "email_id" => $email,
                    "structure" => $structure,
                    "overview" => $overview,
                    "parts" => $structure->parts,
                    "body" => $body,
                    "attachments" => array()
                );

                /* if any attachments are found... */
                if (isset($structure->parts) && count($structure->parts)) {
                    for ($j = 0; $j < count($structure->parts); $j++) {
                        $this->emails[$i]["attachments"][$j] = array(
                            'is_attachment' => false,
                            'filename' => '',
                            'name' => '',
                            'attachment' => ''
                        );

                        if ($structure->parts[$j]->ifdparameters) {
                            foreach ($structure->parts[$j]->dparameters as $object) {
                                if (strtolower($object->attribute) == 'filename') {
                                    $this->emails[$i]["attachments"][$j]['is_attachment'] = true;
                                    $this->emails[$i]["attachments"][$j]['filename'] = imap_utf8($object->value);
                                }
                            }
                        }

                        if ($structure->parts[$j]->ifparameters) {
                            foreach ($structure->parts[$j]->parameters as $object) {
                                if (strtolower($object->attribute) == 'name') {
                                    $this->emails[$i]["attachments"][$j]['is_attachment'] = true;
                                    $this->emails[$i]["attachments"][$j]['name'] = imap_utf8($object->value);
                                }
                            }
                        }

                        if ($this->emails[$i]["attachments"][$j]['is_attachment']) {
                            //The $section parameter of imap_fetchbody behaves differently if the email has any attachments, so it was necessary to fetch email body again with a different section argument
                            $this->emails[$i]["body"] = imap_fetchbody($this->inbox, $email, "1.1", FT_UID); //"1.1" => TEXT/PLAIN
                            $this->emails[$i]["attachments"][$j]['attachment'] = imap_fetchbody($this->inbox, $email, $j + 1, FT_UID);
                        }
                    }
                }

                //Decodes body text from Quoted-Printable format
                $this->emails[$i]["body"] = quoted_printable_decode($this->emails[$i]["body"]);

                $i++;
            }

            return $this->emails;
        }

        return null;
    }

    /* Iterates through each email and download attachments */
    public function downloadAttachments()
    {
        if (isset($this->emails) && $this->emails) {
            $attachmentsDownloaded = 0;
            foreach ($this->emails as $email) { //Loops through each email
                foreach ($email["attachments"] as $attachment) { //Loops through attachments
                    if ($attachment['is_attachment'] == 1) {
                        $filename = $attachment['name'];

                        if (empty($filename)) $filename = $attachment['filename'];

                        if (empty($filename)) $filename = time() . ".dat";

                        $folder = "attachments";

                        if (!is_dir($folder)) {
                            mkdir($folder);
                        }

                        $fp = fopen("./" . $folder . "/" . $email["email_id"] . "-" . $filename, "w+");
                        fwrite($fp, base64_decode($attachment['attachment']));
                        fclose($fp);

                        $attachmentsDownloaded++;
                    }
                }
            }

            if ($attachmentsDownloaded > 0) return true;
        }

        return false;
    }
}
