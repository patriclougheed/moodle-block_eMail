<?php
/**
 * Parent class for eMail.
 *
 * @author Toni Mas
 * @version 1.0.1
 * @uses $CFG
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2008 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 * 		                   http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 **/

class eMail_base {
	/**
	 * eMail Id
	 * @var int $id eMail id
	 */
	var $id		= NULL;

	/**
	 * User Id for the writer of email
	 * @var int $userid Writer.
	 */
	var $userid;

	/**
	 * Course Id to which it belongs email
	 * @var int $course Course id.
	 */
	var $course;

	/**
	 * Date of create email
	 * @var int $timecreated TimeStamp
	 */
	var $timecreated;

	/**
	 * Subject
	 * @var string $subject Subject of email
	 */
	var $subject;

	/**
	 * Body
	 * @var text $body Body of email
	 */
	var $body;

	/**
	 * Attachments
	 * @var array $attachments Attachments
	 */
	var $attachments = NULL;

	/**
	 * User who mail has send by type to.
	 * @var array $to To
	 */
	var $to = array();

	/**
	 * User who mail has send by type cc.
	 * @var array $cc CC
	 */
	var $cc = array();

	/**
	 * User who mail has send by type bcc.
	 * @var array $bcc BCC
	 */
	var $bcc = array();

	/**
	 * The class constructor
	 */
	function eMail_base() {
		$this->init();
	}

	/**
	 * Constructor to keep PHP5
	 */
	function __construct() {
		$this->eMail_base();
	}

	/**
     * Set subject
     *
     * @param string $subject Subject
     */
    function set_subject( $subject ) {

		if ( !empty( $subject ) ) {
			// Clean text
			$this->subject = clean_text($subject);
		} else {
			// Display error
			print_error ( get_string('nosubject', 'block_email_list') );
		}
    }

    /**
     * Set body
     *
     * @param text $body Body
     */
    function set_body( $body='' ) {

    	if ( empty($body) ) {
    		$this->body = ''; // Define one value
    	} else {
    		$context = get_context_instance(CONTEXT_COURSE, $this->course);
    		trusttext_after_edit($body, $context);
    		$this->body = $body;
    	}
    }

    /**
     * This function add attahcments to the mail
     *
     * @param array $attachments Attachments
     */
    function set_attachments( $attachments ) {
    	if ( ! empty( $attachments) ) {
    		$this->attachments = $attachments;
    		return true;
    	}
    	return false;
    }

	/**
	 * Set users send type to
	 * @param array To
	 */
	function set_sendusersbyto( $to=array() ) {

		if ( is_array($to) ) {
			$this->to = $to;
		} else {
			// In all other case .. mark as empty
			$this->to = array();
		}
	}

	/**
	 * Set users send type cc
	 * @param array CC
	 */
	function set_sendusersbycc( $cc=array() ) {

		if ( is_array($cc) ) {
			$this->cc = $cc;
		} else {
			// In all other case .. mark as empty
			$this->cc = array();
		}
	}

	/**
	 * Set users send type bcc
	 * @param array BCC
	 */
	function set_sendusersbybcc( $bcc=array() ) {

		if ( is_array($bcc) ) {
			$this->bcc = $bcc;
		} else {
			// In all other case .. mark as empty
			$this->bcc = array();
		}
	}

	/**
	 * This function insert new record on email_mail
	 */
	function insert_mail_record() {
		global $DB;
		
	    $mail = new object();

		$mail->userid = $this->userid;
		$mail->course = $this->course;
		$mail->subject = $this->subject;
		$mail->body = $this->body;
		$mail->timecreated = $this->timecreated;

		if (! $this->id = $DB->insert_record('email_mail', $mail) ) {
			print_error('failinsertrecord', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$this->course);
    	}
	}

	/**
	 * This function update record on email_mail
	 */
	function update_mail_record() {
		global $DB;
		
	    $mail = new object();

		if ( $this->oldmailid <= 0 ) {
			print_error('failupdaterecord', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$this->course);
		}

		$mail->id = $this->oldmailid;
		$mail->userid = $this->userid;
		$mail->course = $this->course;
		$mail->subject = $this->subject;
		$mail->body = $this->body;
		$mail->timecreated = time();

		if (! $DB->update_record('email_mail', $mail) ) {
			print_error('failupdaterecord', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$this->course);
    	}
	}

	/**
	 * This function send this eMail to respective users.
	 * Active the corresponding flag to user sent.
	 * Add new mail in table.
	 * Add all references in send table.
	 *
	 */
	function send() {
		return NULL; // This method sould be implemented by the derived class.
	}

	/**
	 * This function save this eMail and respective users.
	 * Active the corresponding flag to user sent on draft.
	 * Add new mail in table.
	 *
	 */
	function save() {
		return NULL; // This method sould be implemented by the derived class.
	}

	/**
	 * This function remove this eMail. If email does in TRASH folder, drop of BBDD, else move to TRASH folder.
	 */
	function remove() {
		return NULL;
	}

	/**
	 * This function mark eMail as answered
	 *
	 * @param int $mailid Mark as read external eMail
	 * @param int $userid User Id
	 * @param int $courseid Course Id
	 * @param boolean $silent Display success.
	 */
	function mark2answered($userid, $courseid, $mailid=0, $silent=false) {
        global $DB;
        
		// Status
		$success = true;

		if ( $mailid > 0 ) {
			// Mark answered
			if (! $DB->set_field('email_send', 'answered', 1, 'mailid', $mailid, 'userid', $userid, 'course', $courseid)) {
			    	$success = false;
			}
		} else if ($this->id > 0 ) {
			// Mark answered
			if (! $DB->set_field('email_send', 'answered', 1, 'mailid', $this->id, 'userid', $userid, 'course', $courseid)) {
			    	$success = false;
			}
		} else {
			$success = false;
		}

		if ( !$silent && !$success ) {
			notify(get_string('failmarkanswered', 'block_email_list'));
		}
		return $success;
	}

	/**
	 * This function mark mails to read
	 *
	 * @param int $userid User Id
	 * @param int $courseid Course Id
	 * @param boolean $silent Show or not show messages
	 * @return boolean Success/Fail
	 * @todo Finish documenting this function
	 **/
	function mark2read($userid, $course, $silent=false) {
        global $DB;
        
		$success = true;

		// Mark as read if eMail Id exist
		if ( $this->id > 0 ) {
			if (! $DB->set_field('email_send', 'readed', 1, 'mailid', $this->id, 'userid', $userid, 'course', $course)) {
				$success = false;
			}
		} else {
			$success = false;
		}

		if ($success) {
			if (! $silent ) {
				// Display success
				notify(get_string('toreadok', 'block_email_list'), 'notifysuccess');
			}
			return true;
		} else {
			if ( ! $silent ) {
				notify(get_string('failmarkreaded', 'block_email_list'));
			}
			return false;
		}
	}

	/**
	 * This function mark mails to unread
	 *
	 * @param int $userid User Id
	 * @param int $courseid Course Id
	 * @param boolean $silent Show or not show messages
	 * @return boolean Success/Fail
	 **/
	function mark2unread($userid, $course, $silent=false) {
        global $DB;
        
		$success = true;

		// Mark as unread if eMail Id exist
		if ( $this->id > 0 ) {
			if (! $DB->set_field('email_send', 'readed', 0, 'mailid', $this->id, 'userid', $userid, 'course', $course)) {
				$success = false;
			}
		} else {
			$success = false;
		}

		// Display success
		if ($success) {
			if (! $silent ) {
				// Display success
				notify(get_string('tounreadok', 'block_email_list'), 'notifysuccess');
			}
			return true;
		} else {
			if ( ! $silent ) {
				notify(get_string('failmarkunreaded', 'block_email_list'));
			}
			return false;
		}

	}

	/**
	 * This function insert reference mail <-> folder. There apply filters.
	 *
	 * @param int $userid User Id
	 * @param string $foldername Folder name
	 * @return object Contain all users object send mails
	 * @todo Finish documenting this function
	 **/
	function reference_mail_folder($userid, $foldername) {
        global $DB;
        
		$foldermail = new stdClass();

		$foldermail->mailid = $this->id;

		$folder = email_get_root_folder($userid, $foldername);

		$foldermail->folderid = $folder->id;

		// Insert into inbox user
		if (! $DB->insert_record('email_foldermail', $foldermail)) {
			return false;
		}

		return true;

	}

	/**
	 * This function add new files into mailid.
	 *
	 * @uses $CFG
	 * @param $attachments Is an array get to $_FILES
	 * @return string Array of all name attachments upload
	 */

	function add_attachments() {

		global $CFG, $DB;

	    /// Note: $attachments is an array, who it's 5 sub-array in here.
	    /// name, type, tmp_name. size, error who have an arrays.

		// Prevent errors
	    if ( empty($this->oldattachments) and (empty($this->attachments) or ( isset($this->attachments['FILE_0']['error']) and $this->attachments['FILE_0']['error'] == 4) ) ) {
	    	return true;
	    }

		// Get course for upload manager
	    if (! $course = $DB->get_record('course', 'id', $this->course)) {
	        return '';
	    }

	    require_once($CFG->dirroot.'/lib/uploadlib.php');

		// Get directory for save this attachments
		$dir = $this->get_file_area();

		// Now, delete old corresponding files
	    if ( ! empty( $this->oldattachments) ) {

			if ( $this->type != EMAIL_FORWARD and $this->type != EMAIL_REPLY and $this->type != EMAIL_REPLYALL ) {	// Working in same email
				// Necessary library for this function
				include_once($CFG->dirroot.'/lib/filelib.php');

				// Get files of mail
				if ($files = get_directory_list($dir)) {

					// Process all attachments
					foreach ( $files as $file ) {
						// Get path of file
						$attach = $this->get_file_area_name() . '/' .$file;

						$attachments[] = $attach;
					}
				}

				if ( $diff = array_diff($attachments, $this->oldattachments) ) {
					foreach ( $diff as $attachment ) {
						unlink($CFG->dataroot.'/'.$attachment); // Drop file
					}
				}

			} else if ( $this->type === EMAIL_FORWARD ) {	// Copy $this->oldattachments in this new email
				foreach ( $this->oldattachments as $attachment ) {
					copy($CFG->dataroot.'/'.$attachment, $this->get_file_area().'/'. basename($attachment));
				}
			}
	    }

		if ( ! empty($this->attachments) or ( isset($this->attachments['FILE_0']['error']) and $this->attachments['FILE_0']['error'] != 4) )
		// Now, processing all attachments . . .
		$um = new upload_manager(NULL,false,false,$course,false, 0, true, true);

		if (! $um->process_file_uploads($dir)) {
	        // empty file upload. Error solve in latest version of moodle.
	        // Warning! Only comprove first mail. Bug of uploadlib.php.
        	$message = get_string('uploaderror', 'assignment');
        	$message .= '<br />';
        	$message .= $um->get_errors();
			print_simple_box($message, '', '', '', '', 'errorbox');
        	print_continue($CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$course->id);
        	print_footer();
        	die;
	    }

		return true;
	}

	/**
	 * This functions create upload directory if it's necessary.
	 * and return path it.
	 *
	 * @return string Return the upload file path.
	 **/
	function get_file_area() {

		// First, showing if have path to save mails
		if (!$name = $this->get_file_area_name()) {
			return false;
		}

	    return make_upload_directory( $name );
	}

	/**
	 * This function return upload attachment path.
	 *
	 * @return string Path on save upload files
	 * @todo Finish documenting this function
	 **/
	function get_file_area_name() {
        global $DB;
        
		//Get mail
		if (! $mail = $DB->get_record('email_mail', 'id', $this->id) ) {
			return false;
		}

		return "$this->course/email/$this->userid/$this->id";
	}

	/**
	 * This functions return attachments of mail
	 *
	 * @uses $CFG
	 * @return array All attachments
	 * @todo Finish documenting this function
	 **/
	function get_attachments() {

		global $CFG;

		// Necessary library for this function
		include_once($CFG->dirroot.'/lib/filelib.php');

		// Get attachments mail path
		$basedir = $this->get_file_area();
		$attachment = new stdClass();

		// Get files of mail
		if ($files = get_directory_list($basedir)) {

			// Process all attachments
			foreach ( $files as $file ) {
				// Get path of file
				$attachment->path = $this->get_file_area_name();
				$attachment->name = $file;

				$attachments[] = (PHP_VERSION < 5) ? $attachment : clone($attachment);	// Thanks Ann
			}
		}

		return $attachments;
	}


}

class eMail extends eMail_base {

	/**
	 * Mark if eMail has reply, reply all or forward
	 * @var string $type Reply, reply all or forward
	 */
	var $type	= NULL;

	/**
	 * Mark if eMail is save in draft
	 * @var boolean $draft Is draft?
	 */
	var $draft = false;


	/**
	 * Old eMail Id when this send by reply or reply all message.
	 */
	var $oldmailid = NULL;

	/**
	 * Old attachments if mail have forward or draft
	 */
	var $oldattachments = array();


    function eMail() {

    }

    /**
     * This funcion return formated fullname of user. ALLWAYS return firstname lastname
     */
    function fullname($user, $override=false) {

		// Drop all semicolon apears. (Js errors when select contacts)
		return str_replace(',', '', fullname($user, $override));
    }

    /**
     * Function for define new eMail, with this data
     *
     * @param object or int $email eMail
     */
    function set_email($email) {

    	if ( ! empty($email) ) {
			if (is_object($email) ) {
				$this->id = $email->id;
				$this->subject = $email->subject;
				$this->body = $email->body;
				$this->timecreated = $email->timecreated;
				// Get writer
				if ( ! isset($email->writer) ) {
					$this->userid = $email->userid;
				} else {
					$this->userid = $email->writer;
				}
				$this->course = $email->course;
			} else if (is_int($email) ) {
				if ( $mail = $DB->get_record('email_mail', 'id', $email) ) {
					$this->id = $mail->id;
					$this->subject = $mail->subject;
					$this->body = $mail->body;
					$this->timecreated = $mail->timecreated;
					$this->userid = $mail->userid;
					$this->course = $mail->course;
				}
			}
    	}

    }

    /**
     * Set Writer
     *
     * @uses $USER
     * @param int $userid Writer
     */
    function set_writer($userid) {
    	global $USER;

		// Security issues
		if ( isset( $USER->id ) ) {
			if ( $USER->id != $userid or !$userid) {
				// Display error
				print_error ( 'incorrectuserid', 'block_email_list' );
				return false;
			}
		}

		// Assign userid - FIXME: If user don't exist¿?
		$this->userid = $userid;
		return true;
    }

    /**
     * Get Writer
     */
    function get_writer() {
    	return $this->userid;
    }

    /**
     * Get full name of writer
     */
    function get_fullname_writer($override=false) {
        global $DB;
        
    	if ( $user = $DB->get_record('user', 'id', $this->userid) ) {
    		return $this->fullname($user, $override);
    	} else {
    		return ''; // User not found
    	}
    }

    /**
     * Get format string of all fullnames users send
     *
     * @return string Contain user who writed mails
     * @todo Finish documenting this function
     */
    function get_users_send($type='', $override=false) {
    	global $DB;
        // Get send's

    	if ( isset($this->id) ) {
			if ( $type === 'to' or $type === 'cc' or $type === 'bcc' ) {
				if (! $sendbox = $DB->get_records_select('email_send', 'mailid='.$this->id.' AND type="'.$type.'"') ) {
					return false;
				}
			} else {
				if (! $sendbox = $DB->get_records('email_send', 'mailid', $this->id) ) {
					return false;
				}
			}

			$users = '';

			foreach ( $sendbox as $sendmail ) {
				// Get user
				if ( $user = $DB->get_record('user', 'id', $sendmail->userid) ) {
					$users .= $this->fullname($user, $override) .', ';
				}
			}

			// Delete 2 last characters
			$count = strlen($users);
			$users = substr ( $users, 0, $count-2 );

			return $users;
    	} else {
    		return get_string('neverusers', 'block_email_list');
    	}
    }

    /**
	 * This function show if the user can read this mail.
	 *
	 * @param object $user User.
	 * @return boolean True or false if the user can read this mail.
	 * @todo Finish documenting this function
	 */
	function can_readmail($user) {
        global $DB;
        
		// Writer
		if ( $this->userid == $user->id ) {
			return true;
		}

		$senders = $DB->get_records('email_send', 'mailid', $this->id);

		if ( $senders ) {
			foreach( $senders as $sender ) {
				if ( $sender->userid == $user->id ) {
					return true;
				}
			}
		}

		return false;

	}

    /**
	 * This functions return if mail is readed or not readed
	 *
	 * @param int $userid User ID
	 * @param int $courseid Course ID
	 * @return boolean Success/Fail
	 * @todo Finish documenting this function
	 **/
	function is_readed($userid, $courseid) {
        global $DB;
        
		// Get mail
		if (! $send = $DB->get_record('email_send', 'mailid', $this->id, 'userid', $userid, 'course', $courseid) ) {
			return false;
		}

		// Return value
		return $send->readed;
	}

    /**
	 * This function return true or false if mail has answered.
	 *
	 * @param int $userid User Id.
	 * @param int $courseid Course ID.
	 * @return boolean Success/Fail
	 * @todo Finish documenting this function
	 **/
	function is_answered($userid, $courseid) {
        global $DB;
        
		if ( ! $send = $DB->get_record('email_send', 'mailid', $this->id, 'userid', $userid, 'course', $courseid) ) {
			return false; // User Id is the writer (only apears in email_mail)
		}

		return $send->answered;
	}

    /**
	 * This functions return if mail has attachments
	 *
	 * @return boolean Success/Fail
	 * @todo Finish documenting this function
	 **/
	function has_attachments() {

		if ( isset($this->id) ) {
			if (! $this->get_file_area_name()) {
				return false;
			}
			// Get attachments mail path
			$basedir = $this->get_file_area();

			// Get files of mail
			if ($files = get_directory_list($basedir)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * This functions prints attachments of mail
	 *
	 * @uses $CFG
	 * @param boolean $attachmentformat Print attachment formatting box (Optional)
	 * @return boolean Success/Fail
	 * @todo Finish documenting this function
	 **/
	function _get_format_attachments($attachmentformat=true) {

		global $CFG;

		// Necessary library for this function
		include_once($CFG->dirroot.'/lib/filelib.php');

		// Get strings
		$strattachment  = get_string('attachment', 'block_email_list');
		$strattachments = get_string('attachments', 'block_email_list');

		$html = '';

		// Get attachments mail path
		$basedir = $this->get_file_area();

		// Get files of mail
		if ($files = get_directory_list($basedir)) {

			if ( $attachmentformat ) {
				$html .=  '<br /><br /><br />';
		        $html .= '<hr width="80%" />';

		        $result = count($files) == 1 ? $strattachment : $strattachments ;

		        $html .= '<b>'. $result. '</b>';

		        $html .= '<br />';
		        $html .= '<br />';
			}
			// Process all attachments
			foreach ( $files as $file ) {
				// Get icon
				$icon = mimeinfo('icon', $file);
				$html .= '<img border="0" src="'. $CFG->pixpath.'/f/'. $icon.'" alt="icon" height="16" width="16" />';

				$html .= '&#160;';

				// Get path of file
				$filearea = $this->get_file_area_name();

				if ($CFG->slasharguments) {
	                $ffurl = "blocks/email_list/email/file.php/$filearea/$file";
	            } else {
	                $ffurl = "blocks/email_list/email/file.php?file=/$filearea/$file";
	            }

				$html .= '<a href="'.$CFG->wwwroot.'/'.$ffurl. '" target="blank">'. $file .'</a>';
				$html .= '<br />';
			}

			if ( $attachmentformat ) {
		        $html .= '<br />';
			}
		}

		return $html;
	}

	/**
	 * This function set old attachments.
	 *
	 * @param array $oldattachments Old attachments
	 */
	function set_oldattachments($oldattachments) {

		if ( is_array( $oldattachments) ) {
			$this->oldattachments = $oldattachments;
			return true;
		} else if ( is_string($oldattachments) ) {
			$this->oldattachments = array($oldattachments);
			return true;
		}

		return false;
	}

    /**
     * Set Course
     *
     * @uses $COURSE
     * @param int $courseid Course Id
     */
    function set_course($courseid) {
    	global $COURSE;

		if ( empty( $courseid ) and isset($COURSE->id) ) {
			$this->course = $COURSE->id;
		} else if ( empty( $courseid ) ) {
			print_error('specifycourseid', 'block_email_list');
		}
    	$this->course = $courseid;
    	return true;
    }

	/**
	 * Set type of mail
	 */
	function set_type($type) {

		// Security control
		if ( $type === EMAIL_REPLY or $type === EMAIL_REPLYALL or $type === EMAIL_FORWARD ) {
			$this->type = $type;
		}
	}

	/**
	 * Set old mail (if exit)
	 *
	 * @param int $oldmailid Old mail id (Forward)
	 */
	function set_oldmailid($oldmailid) {
		// Forbidden negative id's and zeros
		if ( $oldmailid > 0 AND $this->type != EMAIL_FORWARD ) {
			$this->oldmailid = $oldmailid;
		}
	}

	/**
	 * Set mail ID (if exist)
	 *
	 * @param int $id Mail id (Draft)
	 */
	function set_mailid($id) {
		// Forbidden negative id's and zeros
		if ( $id > 0 ) {
			$this->id = $id;
		}
	}

	/**
	 * This function send this eMail to respective users.
	 * Active the corresponding flag to user sent.
	 * Add new mail in table.
	 * Add all references in send table.
	 *
	 */
	function send() {
		global $COURSE, $USER, $DB;

		// Mark answered old mail
	    if ( $this->type === EMAIL_REPLY or $this->type === EMAIL_REPLYALL ) {
	    	$this->mark2answered($USER->id, $COURSE->id, $this->oldmailid, true);
	    }

		if (! $this->id or $this->type === EMAIL_FORWARD or $this->type === EMAIL_REPLY or $this->type === EMAIL_REPLYALL) {
			$this->timecreated = time();
			// Insert record
			$this->insert_mail_record();
		} else {
			// Update record
			$this->update_mail_record(); // Abans s'ha d'haver introduït el ID del correu que es vol actualitzar!!
		}

	    if (! $this->reference_mail_folder($this->userid, EMAIL_SENDBOX) ) {
	    	return false;
	    }

	    // If mail has saved in draft, delete this reference.
	    if ( $folderdraft = email_get_root_folder($this->userid, EMAIL_DRAFT) ) {
		    if ($foldermail = email_get_reference2foldermail($this->id, $folderdraft->id) ) {
		    	if (! $DB->delete_records('email_foldermail', 'id', $foldermail->id)) {
		    		print_error( 'failremovingdraft', 'block_email_list');
		    	}
		    }
	    }

	    // Add attachments
	    if ($this->attachments or $this->oldattachments) {
			if (! $this->add_attachments() ) {
				notify('Fail uploading attachments');
			}
	    }

		// If mail already exist ... (in draft)
	    if ( $this->id ) {
	    	// Drop all records, and insert all again.
			if (! $DB->delete_records('email_send', 'mailid', $this->id)) {
				return false;
			}
	    }

		// Prepare send mail
		$send = new stdClass();
		$send->userid 	= $this->userid;
		$send->course 	= $this->course;
		$send->mailid	= $this->id;
		$send->readed	= 0;
		$send->sended 	= 1;
		$send->answered = 0;

		if (! empty($this->to) ) {

			// Insert mail into send table, for all senders users.
			foreach ( $this->to as $userid ) {

				// In this moment, create if no exist this root folders
				email_create_parents_folders($userid);

				$send->userid = $userid;

				$send->type		 = 'to';

				if (! $DB->insert_record('email_send', $send)) {
					print_error('failinsertsendrecord', 'block_email_list');
					return false;
			    }

				// Add reference to corresponding user
			    if (! $this->reference_mail_folder($userid, EMAIL_INBOX) ) {
				   	return false;
				}
			}
		}

		if (! empty($this->cc) ) {

			// Insert mail into send table, for all senders users.
			foreach ( $this->cc as $userid ) {

				// In this moment, create if no exist this root folders
				email_create_parents_folders($userid);

				$send->userid = $userid;

				$send->type		 = 'cc';

				if (! $DB->insert_record('email_send', $send)) {
					print_error('failinsertsendrecord', 'block_email_list');
					return false;
			    }

				// Add reference to corresponding user
			    if (! $this->reference_mail_folder($userid, EMAIL_INBOX) ) {
				   	return false;
			    }
			}
		}

		if (! empty($this->bcc) ) {

			// Insert mail into send table, for all senders users.
			foreach ( $this->bcc as $userid ) {

				// In this moment, create if no exist this root folders
				email_create_parents_folders($userid);

				$send->userid = $userid;

				$send->type		 = 'bcc';

				if (! $DB->insert_record('email_send', $send)) {
					print_error('failinsertsendrecord', 'block_email_list');
					return false;
			    }

				// Add reference to corresponding user
		    	if (! $this->reference_mail_folder($userid, EMAIL_INBOX) ) {
			    	return false;
			    }
			}
		}

		add_to_log($this->course, 'email', "add mail", 'sendmail.php', "$this->subject", 0, $this->userid);

		return $this->id;
	}

	/**
	 * This functions add mail in user draft folder.
	 * Add new mail in table.
	 * Add all references in table send.
	 *
	 * @param int $mailid Old mail ID
	 * @return boolean Success/Fail
	 * @todo Finish documenting this function
	 **/
	function save($mailid=NULL) {
        global $DB;
        
		$this->timecreated = time();

		if (! $mailid ) {

			$this->insert_mail_record();

	    	$writer = $this->userid;

		    // Prepare send mail
			$send = new stdClass();
			$send->userid = $this->userid;
			$send->course = $this->course;
			$send->mailid = $this->id;
			$send->readed = 0;
			$send->sended = 0; // Important
			$send->answered = 0;

			if (! empty($this->to) ) {

				// Insert mail into send table, for all senders users.
				foreach ( $this->to as $userid ) {

					// In this moment, create if no exist this root folders
					email_create_parents_folders($userid);

					$send->userid = $userid;

					$send->type		 = 'to';

					if (! $DB->insert_record('email_send', $send)) {
						print_error('failinsertsendrecord', 'block_email_list');
						return false;
				    }
				}
			}

			if (! empty($this->cc) ) {

				// Insert mail into send table, for all senders users.
				foreach ( $this->cc as $userid ) {

					// In this moment, create if no exist this root folders
					email_create_parents_folders($userid);

					$send->userid = $userid;

					$send->type		 = 'cc';

					if (! $DB->insert_record('email_send', $send)) {
						print_error('failinsertsendrecord', 'block_email_list');
						return false;
				    }
				}
			}

			if (! empty($this->bcc) ) {

				// Insert mail into send table, for all senders users.
				foreach ( $this->bcc as $userid ) {

					// In this moment, create if no exist this root folders
					email_create_parents_folders($userid);

					$send->userid = $userid;

					$send->type		 = 'bcc';

					if (! $DB->insert_record('email_send', $send)) {
						print_error('failinsertsendrecord', 'block_email_list');
						return false;
				    }
				}
			}

	    	if (! $this->reference_mail_folder($this->userid, EMAIL_DRAFT) ) {
	    		print_error('failinsertrecord', 'block_email_list');
	    	}

		} else {
			$this->oldmailid = $mailid;

			$this->update_mail_record();

			// Drop all records, and insert all again.
			if ( $DB->delete_records('email_send', 'mailid', $mailid)) {

			    // Prepare send mail
				$send = new stdClass();
				$send->userid = $this->userid;
				$send->course = $this->course;
				$send->mailid = $mailid;
				$send->readed = 0;
				$send->sended = 0; // Important
				$send->answered = 0;

				// Now, I must verify the users who sended mail, in case they have changed.
				if (! empty($this->to) ) {

					// Insert mail into send table, for all senders users.
					foreach ( $this->to as $userid ) {

						$send->userid = $userid;

						$send->type		 = 'to';

						if (! $DB->insert_record('email_send', $send)) {
							print_error('failinsertsendrecord', 'block_email_list');
							return false;
					    }
					}
				}

				if (! empty($this->cc) ) {

					// Insert mail into send table, for all senders users.
					foreach ( $this->cc as $userid ) {

						$send->userid = $userid;

						$send->type		 = 'cc';

						if (! $DB->insert_record('email_send', $send)) {
							print_error('failinsertsendrecord', 'block_email_list');
							return false;
					    }
					}
				}

				if (! empty($this->bcc) ) {

					// Insert mail into send table, for all senders users.
					foreach ( $this->bcc as $userid ) {

						$send->userid = $userid;

						$send->type		 = 'bcc';

						if (! $DB->insert_record('email_send', $send)) {
							print_error('failinsertsendrecord', 'block_email_list');
							return false;
					    }
					}
				}
			}
		}

	    // Add attachments
	    if ($this->attachments or $this->oldattachments ) {
			if (! $this->add_attachments() ) {
				notify('Fail uploading attachments');
			}
	    }

	    add_to_log($this->course, 'email', "add mail in draft", 'sendmail.php', "$this->subject", 0, $this->userid);

		return $this->id;
	}

	/**
	 * This function remove eMail, if this does in TRASH folder remove of BBDD else move to TRASH folder.
	 *
	 * @param int $userid User Id
	 * @param int $courseid Course Id
	 * @param int $folderid Folder Id
	 * @param boolean $silent Show or not show messages
	 * @return boolean Success/Fail
	 * @todo Finish documenting this function
	 */
	function remove($userid, $courseid, $folderid, $silent=false ) {
        global $DB;
		// First, show if folder remove or not

		$deletemails = false;
		$success = true;

		if ( email_isfolder_type(get_record('email_folder', 'id', $folderid), EMAIL_TRASH) ) {
			$deletemails = true;
		}

		// FIXME: Esborrar els attachments quan no hagi cap referència al mail


		// If delete definity mails ...
		if ( $deletemails ) {
			// Delete reference mail
			if (! $DB->delete_records('email_send', 'mailid', $this->id, 'userid', $userid, 'course', $courseid)) {
			    	return false;
			}
		} else {
			// Get remove folder user
			$removefolder = email_get_root_folder($userid, EMAIL_TRASH);

			// Get actual folder
			$actualfolder = email_get_reference2foldermail($this->id, $folderid);

			if ($actualfolder) {
				// Move mails to trash
				if (! email_move2folder($this->id, $actualfolder->id, $removefolder->id) ) {
					$success = false;
				} else {
					// Mark the message as read
					$DB->set_field('email_send', 'readed', 1, 'mailid', $this->id, 'userid', $userid, 'course', $courseid); 		//Thanks Ann
				}
			} else {
				$success = false;
			}
		}

		// Notify
		if ( $success ) {
			add_to_log($this->course, 'email', 'remove mail', '', 'Remove '.$this->subject, 0, $this->userid);
	    	if ( ! $silent ) {
	    		notify( get_string('removeok', 'block_email_list'), 'notifysuccess' );
	    	}
	    	return true;
		} else {
			if ( ! $silent ) {
				notify( get_string('removefail', 'block_email_list') );
			}
			return false;
		}
	}

	/**
	 * This function display an eMail
	 *
	 * @uses $COURSE
	 * @param
	 */
	function display($courseid, $folderid, $urlpreviousmail, $urlnextmail, $baseurl, $user, $override=false) {

		global $COURSE;

		// SECURITY. User can read this mail?
		if (! $this->can_readmail($user) ) {
			print_error('dontreadmail', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?'.$baseurl);
		}

		// Now, mark mail as readed
		if (! $this->mark2read($user->id, $COURSE->id, true) ) {
			print_error('failmarkreaded', 'block_email_list');
		}

		echo $this->get_html($courseid, $folderid, $urlpreviousmail, $urlnextmail, $baseurl, $override);


	}

	/**
	 * This function return an HTML code for display this eMail
	 */
	function get_html($courseid, $folderid, $urlpreviousmail, $urlnextmail, $baseurl, $override=false) {

		global $USER, $CFG, $DB;

		$html = '';

		$html .= '<table class="sitetopic" border="0" cellpadding="5" cellspacing="0" width="100%">';
	    $html .= '<tr class="headermail">';
	    $html .= '<td style="border-left: 1px solid black; border-top:1px solid black" width="7%" align="center">';

	    // Get user picture
	    $user = $DB->get_record('user', 'id', $this->userid);
	    $html .= print_user_picture($this->userid, $this->course, $user->picture, 0, true, false);

	    $html .= '</td>';

	    $html .= '<td style="border-right: 1px solid black; border-top:1px solid black" align="right" colspan="2">';
	    $html .= $this->subject;
	    $html .= '</td>';

	    $html .= '</tr>';

	    $html .= '<tr>';

	    $html .= '<td  style="border-left: 1px solid black; border-right: 1px solid black; border-top:1px solid black" align="right" colspan="3">';
	    $html .= '&nbsp;&nbsp;&nbsp;';
	    $html .= '<b> '. get_string('from','block_email_list'). ':</b>&nbsp;';
	    $html .= $this->get_fullname_writer($override);

	    $html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';

		$userstosendto = $this->get_users_send('to');

		$html .= '<td style="border-right: 1px solid black;" width="80%" align="right" colspan="2">';
		$html .= '&nbsp;&nbsp;&nbsp;';

		if ( $userstosendto != '' ) {
			$html .= '<b> '. get_string('for','block_email_list') .':</b>&nbsp;';

			$html .= $this->get_users_send('to');
		}

		$html .= '</td>';

		$html .= '<td style="border-left: 1px solid black;" width="20%">';

		if ( $urlnextmail or $urlpreviousmail ) {
	    	$html .= "&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;";
	    }
	    if( $urlpreviousmail ) {
	    	$html .= '<a href="view.php?'. $urlpreviousmail .'">' . get_string('previous','block_email_list') . '</a>';
	    }
	    if( $urlnextmail ) {
	    	if ( $urlpreviousmail ) {
	        	$html .= '&nbsp;|&nbsp;';
	        }
	        $html .= '<a href="view.php?'. $urlnextmail .'">' . get_string('next', 'block_email_list').'</a>';
	    }

	    $html .= '&nbsp;&nbsp;';
	    $html .= '</td>';
	    $html .= '</tr>';

		$userstosendcc = $this->get_users_send('cc');
	    if ( $userstosendcc != '' ) {
	    	$html .= '<tr>
	                    <td  style="border-left: 1px solid black; border-right: 1px solid black;" align="right" colspan="3">
	                        &nbsp;&nbsp;&nbsp;
	                        <b> ' . get_string('cc','block_email_list') . ':</b>&nbsp;' . $userstosendcc . '
	                    </td>
	                </tr>';
	    }

	    // Drop users sending by bcc if user isn't writer
	    if ( $userstosendbcc = $this->get_users_send('bcc') != '' and $USER->id != $this->userid ) {
	    	$html .= '<tr>
	                    <td  style="border-left: 1px solid black; border-right: 1px solid black;" align="right" colspan="3">
	                        &nbsp;&nbsp;&nbsp;
	                        <b> ' . get_string('bcc','block_email_list') . ':</b>&nbsp;' . $userstosendbcc . '
	                    </td>
	                </tr>';
	    }

		$html .= '<tr>';

		$html .= '<td style="border-left: thin solid black; border-right: 1px solid black" width="60%" align="right" colspan="3">';
		$html .= '&nbsp;&nbsp;&nbsp;';

		$html .= '<b> '. get_string('date','block_email_list') . ':</b>&nbsp;';

		$html .= userdate($this->timecreated);

	    $html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td style="border: 1px solid black" colspan="3" align="right">';
		$html .= '<br />';

		// Options for display body
		$options = new object();
		$options->filter = true;

	    $html .= format_text($this->body, FORMAT_HTML, $options );

	    if ( $this->has_attachments() ) {
	    	$html .= $this->_get_format_attachments();
	    }

	    $html .= '<br />';
	    $html .= '<br />';
	    $html .= '</td>';
	    $html .= '</tr>';

	    $html .= '<tr class="messagelinks">';
		$html .= '<td align="right" colspan="3">';

	    $html .= '<a href="sendmail.php?'.$baseurl .'&amp;action='.EMAIL_REPLY.'"><b>'. get_string('reply','block_email_list'). '</b></a>';
	    $html .= ' | ';
	    $html .= '<a href="sendmail.php?'.$baseurl .'&amp;action='.EMAIL_REPLYALL.'"><b>'. get_string('replyall','block_email_list'). '</b></a>';
	    $html .= ' | ';
	    $html .= '<a href="sendmail.php?'.$baseurl .'&amp;action='.EMAIL_FORWARD.'"><b>'. get_string('forward','block_email_list'). '</b></a>';
	    $html .= ' | ';
	    $html .= '<a href="index.php?id='.$courseid .'&amp;mailid='.$this->id.'&amp;folderid='.$folderid.'&amp;action=removemail"><b>'. get_string('removemail','block_email_list'). '</b></a>';
	    $html .= ' | ';

	    $icon = '<img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/printer.png" height="16" width="16" alt="'.get_string('print','block_email_list').'" />';

	    $html .= email_print_to_popup_window ('link', '/blocks/email_list/email/print.php?courseid='.$courseid.'&amp;mailids='.$this->id, '<b>'.get_string('print','block_email_list').'</b>'.print_spacer(1,3,false,true).$icon , get_string('print','block_email_list'), true);

	    $html .= '</td>';

		$html .= '</tr>';
		$html .= '</table>';

		return $html;
	}

}
?>