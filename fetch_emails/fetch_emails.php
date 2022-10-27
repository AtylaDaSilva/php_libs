<?php
class Inbox
{
    //Opens IMAP connection. Returns an IMAP\Connection resource on success, or false on failure.
    public function connect($server, $user, $password)
    {
        $resource = imap_open($server, $user, $password) or die("Could not connect to email service: " . imap_last_error());

        return $resource;
    }

    //Closes IMAP connnection. Returns true on success, or false on failure.
    public function disconnect($imap_conn)
    {

        return imap_close($imap_conn);
    }

    //Returns an array containing the UID of UNSEEN emails from inbox, or false if no emails in $inbox match the criteria.
    public function listNewEmails($inbox)
    {
        return imap_search($inbox, "UNSEEN", SE_UID);
    }

    //Returns an array of emails, or null if no emails were listed
    public function fetchEmails($inbox, $emailIDs)
    {
        if ($inbox && count($emailIDs) > 0) {
            $i = 0;
            foreach ($emailIDs as $email) {
                $overview = imap_fetch_overview($inbox, $email, FT_UID);
                $structure = imap_fetchstructure($inbox, $email, FT_UID);
                $body = imap_fetchbody($inbox, $email, "1", FT_UID); //"1" => TEXT/PLAIN

                $emails[$i] = array(
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
                        $emails[$i]["attachments"][$j] = array(
                            'is_attachment' => false,
                            'filename' => '',
                            'name' => '',
                            'attachment' => ''
                        );

                        if ($structure->parts[$j]->ifdparameters) {
                            foreach ($structure->parts[$j]->dparameters as $object) {
                                if (strtolower($object->attribute) == 'filename') {
                                    $emails[$i]["attachments"][$j]['is_attachment'] = true;
                                    $emails[$i]["attachments"][$j]['filename'] = imap_utf8($object->value);
                                }
                            }
                        }

                        if ($structure->parts[$j]->ifparameters) {
                            foreach ($structure->parts[$j]->parameters as $object) {
                                if (strtolower($object->attribute) == 'name') {
                                    $emails[$i]["attachments"][$j]['is_attachment'] = true;
                                    $emails[$i]["attachments"][$j]['name'] = imap_utf8($object->value);
                                }
                            }
                        }

                        if ($emails[$i]["attachments"][$j]['is_attachment']) {
                            //The $section parameter of imap_fetchbody behaves differently if the email has any attachments, so it was necessary to fetch email body again with a different $section argument
                            $emails[$i]["body"] = imap_fetchbody($inbox, $email, "1.1", FT_UID); //"1.1" => TEXT/PLAIN
                            $emails[$i]["attachments"][$j]['attachment'] = imap_fetchbody($inbox, $email, $j + 1, FT_UID);
                        }
                    }
                }

                //Decodes body text from Quoted-Printable format
                $emails[$i]["body"] = quoted_printable_decode($emails[$i]["body"]);

                $i++;
            }

            return $emails;
        }

        return null;
    }

    /* Iterates through each email and saves the attachments in the directory "./attachments/dd-mm-yy/email_id". Returns the number of attachments downloaded, or false if no attachments were downloaded */
    public function downloadAttachments($emails)
    {
        if ($emails && count($emails) > 0) {
            $attachmentsDownloaded = 0;
            foreach ($emails as $email) { //Loops through each email
                foreach ($email["attachments"] as $attachment) { //Loops through attachments
                    if ($attachment['is_attachment'] == 1) {
                        $filename = $attachment['name'];

                        if (empty($filename)) $filename = $attachment['filename'];

                        if (empty($filename)) $filename = time() . ".dat";

                        $folder = "attachments/" . date("d-m-y") . "/" . $email["email_id"];

                        if (!is_dir($folder)) {
                            mkdir($folder, 0777, true);
                        }

                        $fp = fopen("./" . $folder . "/" . $filename, "w+");
                        fwrite($fp, base64_decode($attachment['attachment']));
                        fclose($fp);

                        $attachmentsDownloaded++;
                    }
                }
            }

            return $attachmentsDownloaded;
        }

        return false;
    }
}
