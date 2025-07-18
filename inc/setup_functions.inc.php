<?php
/**
 *
 * This file is part of HESK - PHP Help Desk Software.
 *
 * (c) Copyright Klemen Stirn. All rights reserved.
 * https://www.hesk.com
 *
 * For the full copyright and license agreement information visit
 * https://www.hesk.com/eula.php
 *
 */

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');} 

use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$hesk_settings['barcode_types'] = array(
    'C128A'      => 'CODE 128 A',
    'C128B'      => 'CODE 128 B',
    'C128'       => 'CODE 128',
    'C39E+'      => 'CODE 39 EXTENDED + CHECKSUM',
    'C39E'       => 'CODE 39 EXTENDED',
    'C39+'       => 'CODE 39 + CHECKSUM',
    'C39'        => 'CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.',
    'C93'        => 'CODE 93 - USS-93',
    'DATAMATRIX' => 'DATAMATRIX (ISO/IEC 16022)',
    'PDF417'     => 'PDF417 (ISO/IEC 15438:2006)',
    'QRCODE'     => 'QR-CODE',
);

$hesk_settings['barcode_formats'] = array(
    'svg' => 'SVG image',
    'png' => 'PNG image',
);

/*** FUNCTIONS ***/


function hesk_map_datepicker_date_format_to_php($format)
{
    $js_to_php_date_format_map = array(
        'dd'  => 'zzzz',
        'd'   => 'j',
        'DD'  => 'l',
        'D'   => 'D',
        'mm'  => 'wwww',
        'm'   => 'n',
        'MM'  => 'F',
        'M'   => 'M',
        'yyyy' => 'Y',
        'yy'   => 'y',

        // Trick to not overwrite d and m after matching dd and mm
        'zzzz' => 'd',
        'wwww' => 'm',
    );

    foreach ($js_to_php_date_format_map as $js_format => $php_format) {
        $format = str_replace($js_format, $php_format, $format);
    }

    return $format;
} // END hesk_map_datepicker_date_format_to_php()


function hesk_translate_timezone_list($timezone_list)
{
    global $hesklang;

    $translate_months_short = array(
        'Jan' => $hesklang['ms01'],
        'Feb' => $hesklang['ms02'],
        'Mar' => $hesklang['ms03'],
        'Apr' => $hesklang['ms04'],
        'May' => $hesklang['ms05'],
        'Jun' => $hesklang['ms06'],
        'Jul' => $hesklang['ms07'],
        'Aug' => $hesklang['ms08'],
        'Sep' => $hesklang['ms09'],
        'Oct' => $hesklang['ms10'],
        'Nov' => $hesklang['ms11'],
        'Dec' => $hesklang['ms12']
    );

    return str_replace(array_keys($translate_months_short), array_values($translate_months_short), $timezone_list);
} // END hesk_translate_timezone_list()


function hesk_generate_timezone_list()
{
    static $regions = array(
        DateTimeZone::AFRICA,
        DateTimeZone::AMERICA,
        DateTimeZone::ANTARCTICA,
        DateTimeZone::ASIA,
        DateTimeZone::ATLANTIC,
        DateTimeZone::AUSTRALIA,
        DateTimeZone::EUROPE,
        DateTimeZone::INDIAN,
        DateTimeZone::PACIFIC,
    );

    $timezones = array();
    foreach( $regions as $region )
    {
        $timezones = array_merge( $timezones, DateTimeZone::listIdentifiers( $region ) );
    }

    $timezone_offsets = array();
    foreach( $timezones as $timezone )
    {
        $tz = new DateTimeZone($timezone);
        $timezone_offsets[$timezone] = $tz->getOffset(new DateTime);
    }

    // sort timezone by timezone name
    ksort($timezone_offsets);
    //asort($timezone_offsets); // <-- use this to sort by time offset from UTC instead

    // Add UTC as the first element
    $timezone_offsets = array('UTC' => 0) + $timezone_offsets;

    $timezone_list = array();
    foreach( $timezone_offsets as $timezone => $offset )
    {
        $offset_prefix = $offset < 0 ? '-' : '+';
        $offset_formatted = gmdate( 'H:i', abs($offset) );

        $pretty_offset = "UTC{$offset_prefix}{$offset_formatted}";

        $t = new DateTimeZone($timezone);
        $c = new DateTime("now", $t);
        $current_time = $c->format('d M Y, H:i');

        $timezone_list[$timezone] = "{$timezone} - {$current_time}";
    }

    return $timezone_list;
} // END hesk_generate_timezone_list()


function hesk_testMySQL()
{
	global $hesk_settings, $hesklang, $set, $mysql_error, $mysql_log;

	define('REQUIRE_MYSQL_VERSION','5.0.7');

	// Use MySQLi extension to connect?
	$use_mysqli = function_exists('mysqli_connect') ? true : false;

	// Get variables
	$set['db_host'] = hesk_input( hesk_POST('s_db_host'), $hesklang['err_dbhost']);
	$set['db_name'] = hesk_input( hesk_POST('s_db_name'), $hesklang['err_dbname']);
	$set['db_user'] = hesk_input( hesk_POST('s_db_user'), $hesklang['err_dbuser']);
	$set['db_pass'] = hesk_input( hesk_POST('s_db_pass') );
	$set['db_pfix'] = preg_replace('/[^0-9a-zA-Z_]/', '', hesk_POST('s_db_pfix', 'hesk_') );

	// Allow some special chars in password and username
    $set['db_user'] = str_replace('&amp;', '&', $set['db_user']);
    $set['db_pass'] = str_replace(array('&amp;', '&gt;', '&lt;'), array('&', '>', '<'), $set['db_pass']);

	// MySQL tables used by HESK
	$tables = array(
        $set['db_pfix'].'attachments',
        $set['db_pfix'].'auth_tokens',
        $set['db_pfix'].'banned_emails',
        $set['db_pfix'].'banned_ips',
        $set['db_pfix'].'categories',
        $set['db_pfix'].'custom_fields',
        $set['db_pfix'].'custom_statuses',
        $set['db_pfix'].'kb_articles',
        $set['db_pfix'].'kb_attachments',
        $set['db_pfix'].'kb_categories',
        $set['db_pfix'].'logins',
        $set['db_pfix'].'log_overdue',
        $set['db_pfix'].'mail',
        $set['db_pfix'].'mfa_backup_codes',
        $set['db_pfix'].'mfa_verification_tokens',
        $set['db_pfix'].'notes',
        $set['db_pfix'].'online',
        $set['db_pfix'].'pipe_loops',
        $set['db_pfix'].'replies',
        $set['db_pfix'].'reply_drafts',
        $set['db_pfix'].'reset_password',
        $set['db_pfix'].'service_messages',
        $set['db_pfix'].'std_replies',
        $set['db_pfix'].'tickets',
        $set['db_pfix'].'ticket_templates',
        $set['db_pfix'].'users',
	);

	$connection_OK = false;
    $mysql_error = '';

	ob_start();

	// Connect to MySQL
	if ($use_mysqli)
	{
        mysqli_report(MYSQLI_REPORT_OFF);

		// Do we need a special port? Check and connect to the database
		if ( strpos($set['db_host'], ':') )
		{
			list($set['db_host_no_port'], $set['db_port']) = explode(':', $set['db_host']);
            try {
                $set_link = mysqli_connect($set['db_host_no_port'], $set['db_user'], $set['db_pass'], $set['db_name'], intval($set['db_port']) );
            } catch (Exception $e) {
                $set_link = false;
            }
		}
		else
		{
            try {
                $set_link = mysqli_connect($set['db_host'], $set['db_user'], $set['db_pass'], $set['db_name']);
            } catch (Exception $e) {
                $set_link = false;
            }
		}

		if (empty($set_link))
		{
			ob_end_clean();
			$mysql_error = $hesklang['err_dbconn'];
			$mysql_log = "(".mysqli_connect_errno().") ".mysqli_connect_error();
			return false;
		}

		$res = mysqli_query($set_link, 'SHOW TABLES FROM `'.mysqli_real_escape_string($set_link, $set['db_name']).'`');
		while ($row = mysqli_fetch_row($res))
		{
			foreach($tables as $k => $v)
			{
				if ($v == $row[0])
				{
					unset($tables[$k]);
					break;
				}
			}
		}

		// Get MySQL version
		$mysql_version = mysqli_fetch_assoc( mysqli_query($set_link, 'SELECT VERSION() AS version') );

		// Close connections
		mysqli_close($set_link);
	}
	else
	{
		$set_link = mysql_connect($set['db_host'], $set['db_user'], $set['db_pass']);

		if ( ! $set_link)
		{
			ob_end_clean();
			$mysql_error = $hesklang['err_dbconn'];
			$mysql_log = mysql_error();
			return false;
		}

		// Select database
		if ( ! mysql_select_db($set['db_name'], $set_link) )
		{
			ob_end_clean();
			$mysql_error = $hesklang['err_dbsele'];
			$mysql_log = mysql_error();
			return false;
		}

		$res = mysql_query('SHOW TABLES FROM `'.mysql_real_escape_string($set['db_name']).'`', $set_link);
		while ($row = mysql_fetch_row($res))
		{
			foreach($tables as $k => $v)
			{
				if ($v == $row[0])
				{
					unset($tables[$k]);
					break;
				}
			}
		}

		// Get MySQL version
		$mysql_version = mysql_fetch_assoc( mysql_query('SELECT VERSION() AS version') );

		// Close connections
		mysql_close($set_link);
	}

	// Check MySQL version
	if ( version_compare($mysql_version['version'], REQUIRE_MYSQL_VERSION, '<') )
	{
		ob_end_clean();
		$mysql_error = $hesklang['err_dbversion'] . ' ' . $mysql_version['version'];
		$mysql_log = '';
		return false;
	}

	// Some tables weren't found, show an error
	if (count($tables) > 0)
	{
    	ob_end_clean();
		$mysql_error = $hesklang['err_dpi2'].'<br /><br />'.implode(',<br />', $tables);
		$mysql_log = '';
		return false;
	}
    else
    {
    	$connection_OK = true;
    }

    ob_end_clean();

    return $connection_OK;
} // END hesk_testMySQL()


function hesk_testPOP3($check_old_settings=false)
{
	global $hesk_settings, $hesklang, $set;

	$set['pop3_host_name']	= hesk_input( hesk_POST('s_pop3_host_name', 'mail.example.com') );
	$set['pop3_host_port']	= intval( hesk_POST('s_pop3_host_port', 110) );
	$set['pop3_tls']		= empty($_POST['s_pop3_tls']) ? 0 : 1;
    $set['pop3_keep']		= empty($_POST['s_pop3_keep']) ? 0 : 1;
	$set['pop3_user']		= hesk_input( hesk_POST('s_pop3_user') );
	$set['pop3_password']	= hesk_input( hesk_POST('s_pop3_password') );
    $set['pop3_conn_type']  = hesk_input(hesk_POST('s_pop3_conn_type'));
    $set['pop3_oauth_provider'] = $set['pop3_conn_type'] === 'basic' ? 0 : intval(hesk_POST('s_pop3_oauth_provider'));

    // For compatibility with PHP 5.3 magic quotes...
    if (HESK_SLASH === false)
    {
        $set['pop3_password'] = str_replace('\\&quot;', '&quot;', $set['pop3_password']);
    }

	// Are new settings the same as old? If yes, skip testing connection, assume it works
	if ($check_old_settings)
	{
		$set['tmp_pop3_host_name']	= hesk_input( hesk_POST('tmp_pop3_host_name', 'mail.example.com') );
		$set['tmp_pop3_host_port']	= intval( hesk_POST('tmp_pop3_host_port', 110) );
		$set['tmp_pop3_tls']		= empty($_POST['tmp_pop3_tls']) ? 0 : 1;
		$set['tmp_pop3_keep']		= empty($_POST['tmp_pop3_keep']) ? 0 : 1;
		$set['tmp_pop3_user']		= hesk_input( hesk_POST('tmp_pop3_user') );
		$set['tmp_pop3_password']	= hesk_input( hesk_POST('tmp_pop3_password') );
        $set['tmp_pop3_conn_type']  = hesk_input(hesk_POST('tmp_pop3_conn_type'));
        $set['tmp_pop3_oauth_provider']  = $set['tmp_pop3_conn_type'] === 'basic' ? 0 : intval(hesk_POST('tmp_pop3_oauth_provider'));

        // For compatibility with PHP 5.3 magic quotes...
        if (HESK_SLASH === false)
        {
            $set['tmp_pop3_password'] = str_replace('\\&quot;', '&quot;', $set['tmp_pop3_password']);
        }

		if (
			$set['tmp_pop3_host_name'] != 'mail.example.com'      && // Default setting
			$set['tmp_pop3_host_name'] == $set['pop3_host_name'] &&
			$set['tmp_pop3_host_port'] == $set['pop3_host_port'] &&
			$set['tmp_pop3_tls']       == $set['pop3_tls']       &&
			$set['tmp_pop3_keep']      == $set['pop3_keep']      &&
			$set['tmp_pop3_user']      == $set['pop3_user']      &&
			$set['tmp_pop3_password']  == $set['pop3_password']  &&
            $set['tmp_pop3_conn_type'] == $set['pop3_conn_type'] &&
            $set['tmp_pop3_oauth_provider'] == $set['pop3_oauth_provider']
		)
		{
			return true;
		}
	}

	// Initiate POP3 class and set parameters
	require_once(HESK_PATH . 'inc/mail/pop3.php');
	$pop3 = new pop3_class;
	$pop3->hostname	= $set['pop3_host_name'];
	$pop3->port		= $set['pop3_host_port'];
	$pop3->tls		= $set['pop3_tls'];
	$pop3->debug	= 1;

    if ($set['pop3_conn_type']=='oauth') {
        require_once(HESK_PATH . 'inc/oauth_functions.inc.php');
        $pop3->authentication_mechanism = 'XOAUTH2';
        hesk_dbConnect();
        $access_token = hesk_fetch_access_token($set['pop3_oauth_provider']);
        if (!$access_token) {
            global $pop3_error, $pop3_log;
            $pop3_error = $hesklang['oauth_error_retrieve'];
            $pop3_log = $hesklang['oauth_error_retrieve'];
            return false;
        }
    }

	$connection_OK = false;

	ob_start();

	// Connect to POP3
	if(($error=$pop3->Open())=="")
	{
		// Authenticate
		if(($error=$pop3->Login($set['pop3_user'], ($set['pop3_conn_type']=='oauth' ? $access_token : hesk_htmlspecialchars_decode(stripslashes($set['pop3_password'])))))=="")
		{
            // Get number of messages and total size
            if(($error=$pop3->Statistics($messages,$size))=="")
            {
                global $emails_found;
                $emails_found = $messages;

                if(($error=$pop3->Close()) == "")
                {
                    // Connection OK
                    $connection_OK = true;
                }
            }
		}
	}

	if($error != '')
	{
    	global $pop3_error, $pop3_log;
        $pop3_error = $error;
		$pop3_log   = ob_get_contents();
	}

	ob_end_clean();

    return $connection_OK;
} // END hesk_testPOP3()


function hesk_testSMTP($check_old_settings=false)
{
	global $hesk_settings, $hesklang, $set;

	// Get variables
	$set['smtp_host_name']	= hesk_input( hesk_POST('s_smtp_host_name', 'localhost') );
	$set['smtp_host_port']	= intval( hesk_POST('s_smtp_host_port', 25) );
	$set['smtp_timeout']	= intval( hesk_POST('s_smtp_timeout', 10) );
    $set['smtp_enc']        = hesk_POST('s_smtp_enc');
    $set['smtp_enc']        = ($set['smtp_enc'] == 'ssl' || $set['smtp_enc'] == 'tls') ? $set['smtp_enc'] : '';
    $set['smtp_noval_cert'] = empty($_POST['s_smtp_noval_cert']) ? 0 : 1;
	$set['smtp_user']		= hesk_input( hesk_POST('s_smtp_user') );
	$set['smtp_password']	= hesk_input( hesk_POST('s_smtp_password') );
    $set['smtp_conn_type']  = hesk_input(hesk_POST('s_smtp_conn_type'));
    $set['smtp_oauth_provider']  = $set['smtp_conn_type'] === 'basic' ? 0 : intval(hesk_POST('s_smtp_oauth_provider'));

    // For compatibility with PHP 5.3 magic quotes...
    if (HESK_SLASH === false)
    {
        $set['smtp_password'] = str_replace('\\&quot;', '&quot;', $set['smtp_password']);
    }

	// Are new settings the same as old? If yes, skip testing connection, assume it works
	if ($check_old_settings)
	{
		$set['tmp_smtp_host_name']	= hesk_input( hesk_POST('tmp_smtp_host_name', 'localhost') );
		$set['tmp_smtp_host_port']	= intval( hesk_POST('tmp_smtp_host_port', 25) );
		$set['tmp_smtp_timeout']	= intval( hesk_POST('tmp_smtp_timeout', 10) );
        $set['tmp_smtp_enc']        = hesk_POST('tmp_smtp_enc');
        $set['tmp_smtp_enc']        = ($set['tmp_smtp_enc'] == 'ssl' || $set['tmp_smtp_enc'] == 'tls') ? $set['tmp_smtp_enc'] : '';
        $set['tmp_smtp_noval_cert'] = empty($_POST['tmp_smtp_noval_cert']) ? 0 : 1;
		$set['tmp_smtp_user']		= hesk_input( hesk_POST('tmp_smtp_user') );
		$set['tmp_smtp_password']	= hesk_input( hesk_POST('tmp_smtp_password') );
        $set['tmp_smtp_conn_type']  = hesk_input(hesk_POST('tmp_smtp_conn_type'));
        $set['tmp_smtp_oauth_provider']  = $set['tmp_smtp_conn_type'] === 'basic' ? 0 : intval(hesk_POST('tmp_smtp_oauth_provider'));

        // For compatibility with PHP 5.3 magic quotes...
        if (HESK_SLASH === false)
        {
            $set['tmp_smtp_password'] = str_replace('\\&quot;', '&quot;', $set['tmp_smtp_password']);
        }

		if (
			$set['tmp_smtp_host_name'] != 'mail.example.com'      && // Default setting
			$set['tmp_smtp_host_name'] == $set['smtp_host_name'] &&
			$set['tmp_smtp_host_port'] == $set['smtp_host_port'] &&
			$set['tmp_smtp_timeout']   == $set['smtp_timeout']   &&
			$set['tmp_smtp_enc']       == $set['smtp_enc']       &&
			$set['tmp_smtp_noval_cert'] == $set['smtp_noval_cert'] &&
			$set['tmp_smtp_user']      == $set['smtp_user']      &&
			$set['tmp_smtp_password']  == $set['smtp_password'] &&
            $set['tmp_smtp_conn_type'] == $set['smtp_conn_type'] &&
            $set['tmp_smtp_oauth_provider'] == $set['smtp_oauth_provider']
		)
		{
			return true;
		}
	}

    ob_start();

    //Create a new SMTP instance
    $smtp = new SMTP();

    //Enable connection-level debug output
    if ($hesk_settings['debug_mode']) {
        $smtp->do_debug = SMTP::DEBUG_CONNECTION;
        // $smtp->do_debug = SMTP::DEBUG_LOWLEVEL;
    } else {
        $smtp->do_debug = SMTP::DEBUG_SERVER;
    }
    $smtp->Timeout = $set['smtp_timeout'];
    $smtp->Timelimit = $set['smtp_timeout'];

    if ($set['smtp_noval_cert']) {
        $options = array(
          'ssl' => array(
              'verify_peer' => false,
              'verify_peer_name' => false,
              'allow_self_signed' => true
          )
        );
    } else {
        $options = array();
    }

    if (stripos($set['smtp_host_name'], 'ssl://') === 0) {
        $set['smtp_host_name'] = substr($set['smtp_host_name'], 6);
    }

    $set['smtp_host_name_full'] = ($set['smtp_enc'] == 'ssl') ? 'ssl://' . $set['smtp_host_name'] : $set['smtp_host_name'];

    try {
        //Connect to an SMTP server
        if (!$smtp->connect($set['smtp_host_name_full'], $set['smtp_host_port'], $set['smtp_timeout'], $options)) {
            throw new Exception('Connect failed');
        }
        //Say hello
        if (!$smtp->hello(gethostname())) {
            throw new Exception('EHLO failed: ' . $smtp->getError()['error']);
        }
        //Get the list of ESMTP services the server offers
        $e = $smtp->getServerExtList();

        if ($set['smtp_enc'] == 'tls' && is_array($e)) {
            if ( ! array_key_exists('STARTTLS', $e)) {
                throw new Exception('Server does not support STARTTLS');
            }
            $tlsok = $smtp->startTLS();
            if (!$tlsok) {
                throw new Exception('Failed to start encryption: ' . $smtp->getError()['error']);
            }
            //Repeat EHLO after STARTTLS
            if (!$smtp->hello(gethostname())) {
                throw new Exception('EHLO (2) failed: ' . $smtp->getError()['error']);
            }
            //Get new capabilities list, which will usually now include AUTH if it didn't before
            $e = $smtp->getServerExtList();
        }

        //If server supports authentication, do it (even if no encryption)
        if (is_array($e) && array_key_exists('AUTH', $e)) {
            if ($set['smtp_conn_type']=='oauth') {
                require_once(HESK_PATH . 'inc/oauth_functions.inc.php');
                require_once(HESK_PATH . 'inc/mail/HeskOAuthTokenProvider.php');

                $oauthTokenProvider = new \PHPMailer\PHPMailer\HeskOAuthTokenProvider();
                $oauthTokenProvider->username = $set['smtp_user'];
                $oauthTokenProvider->provider = $set['smtp_oauth_provider'];

                if ($smtp->authenticate($set['smtp_user'], null, 'XOAUTH2', $oauthTokenProvider)) {
                    echo 'Connected ok (OAuth)!';
                } else {
                    throw new Exception('Authentication failed: ' . $smtp->getError()['error']);
                }

            } elseif ($smtp->authenticate($set['smtp_user'], hesk_htmlspecialchars_decode(stripslashes($set['smtp_password'])))) {
                echo 'Connected ok!';
            } else {
                throw new Exception('Authentication failed: ' . $smtp->getError()['error']);
            }
        }
    } catch (Exception $e) {
        global $smtp_error, $smtp_log;
        $smtp_error = $e->getMessage();
        $smtp_log = ob_get_contents();
        $smtp->quit();
        ob_end_clean();
        return false;
    }

    $smtp->quit();
    ob_end_clean();
    return true;
} // END hesk_testSMTP()


function hesk_testIMAP($check_old_settings=false)
{
	global $hesk_settings, $hesklang, $set;

	$set['imap_host_name']	= hesk_input( hesk_POST('s_imap_host_name', 'mail.example.com') );
	$set['imap_host_port']	= intval( hesk_POST('s_imap_host_port', 993) );
	$set['imap_enc']		= hesk_POST('s_imap_enc');
	$set['imap_enc']        = ($set['imap_enc'] == 'ssl' || $set['imap_enc'] == 'tls') ? $set['imap_enc'] : '';
	$set['imap_noval_cert'] = empty($_POST['s_imap_noval_cert']) ? 0 : 1;
    $set['imap_disable_GSSAPI'] = empty($_POST['s_imap_disable_GSSAPI']) ? 0 : 1;
	$set['imap_keep']		= empty($_POST['s_imap_keep']) ? 0 : 1;
	$set['imap_user']		= hesk_input( hesk_POST('s_imap_user') );
	$set['imap_password']	= hesk_input( hesk_POST('s_imap_password') );
    $set['imap_conn_type']  = hesk_input(hesk_POST('s_imap_conn_type'));
    $set['imap_oauth_provider']  = $set['imap_conn_type'] === 'basic' ? 0 : intval(hesk_POST('s_imap_oauth_provider'));
    $set['imap_mailbox']	= hesk_input( hesk_POST('s_imap_mailbox', 'INBOX')); // Added for IMAP Mailbox
    // For compatibility with PHP 5.3 magic quotes...
    if (HESK_SLASH === false)
    {
        $set['imap_password'] = str_replace('\\&quot;', '&quot;', $set['imap_password']);
    }

	// Are new settings the same as old? If yes, skip testing connection, assume it works
	if ($check_old_settings)
	{
		$set['tmp_imap_host_name']	= hesk_input( hesk_POST('tmp_imap_host_name', 'mail.example.com') );
		$set['tmp_imap_host_port']	= intval( hesk_POST('tmp_imap_host_port', 993) );
		$set['tmp_imap_enc']		= hesk_POST('s_imap_enc');
		$set['tmp_imap_enc']        = ($set['tmp_imap_enc'] == 'ssl' || $set['tmp_imap_enc'] == 'tls') ? $set['tmp_imap_enc'] : '';
        $set['tmp_imap_noval_cert'] = empty($_POST['tmp_imap_noval_cert']) ? 0 : 1;
        $set['tmp_imap_disable_GSSAPI'] = empty($_POST['tmp_imap_disable_GSSAPI']) ? 0 : 1;
		$set['tmp_imap_keep']		= empty($_POST['tmp_imap_keep']) ? 0 : 1;
		$set['tmp_imap_user']		= hesk_input( hesk_POST('tmp_imap_user') );
		$set['tmp_imap_password']	= hesk_input( hesk_POST('tmp_imap_password') );
        $set['tmp_imap_conn_type']  = hesk_input(hesk_POST('tmp_imap_conn_type'));
        $set['tmp_imap_oauth_provider']  = $set['tmp_imap_conn_type'] === 'basic' ? 0 : intval(hesk_POST('tmp_imap_oauth_provider'));
        $set['tmp_imap_mailbox']	= hesk_input( hesk_POST('s_imap_mailbox', 'INBOX')); // Added for IMAP Mailbox

        // For compatibility with PHP 5.3 magic quotes...
        if (HESK_SLASH === false)
        {
            $set['tmp_imap_password'] = str_replace('\\&quot;', '&quot;', $set['tmp_imap_password']);
        }

		if (
			$set['tmp_imap_host_name'] != 'mail.example.com'      && // Default setting
			$set['tmp_imap_host_name'] == $set['imap_host_name'] &&
			$set['tmp_imap_host_port'] == $set['imap_host_port'] &&
			$set['tmp_imap_enc']       == $set['imap_enc']       &&
			$set['tmp_imap_noval_cert'] == $set['imap_noval_cert'] &&
            $set['tmp_imap_disable_GSSAPI'] == $set['imap_disable_GSSAPI'] &&
			$set['tmp_imap_keep']      == $set['imap_keep']      &&
			$set['tmp_imap_user']      == $set['imap_user']      &&
			$set['tmp_imap_password']  == $set['imap_password']  &&
            $set['tmp_imap_conn_type'] == $set['imap_conn_type'] &&
            $set['tmp_imap_oauth_provider'] == $set['imap_oauth_provider'] &&
            $set['tmp_imap_mailbox'] == $set['imap_mailbox'] // Added for IMAP Mailbox
		)
		{
			return true;
		}
	}

    $connection_OK = false;

    ob_start();

    // IMAP mailbox based on required encryption
    require_once(HESK_PATH . 'inc/mail/imap/HeskIMAP.php');
    $imap = new HeskIMAP();

    $imap->host = $set['imap_host_name'];
    $imap->port = $set['imap_host_port'];
    $imap->username = $set['imap_user'];
    if ($set['imap_conn_type'] === 'basic') {
        $imap->password = hesk_htmlspecialchars_decode(stripslashes($set['imap_password']));
        $imap->useOAuth = false;
    } elseif ($set['imap_conn_type'] === 'oauth') {
        require_once(HESK_PATH . 'inc/oauth_functions.inc.php');
        $access_token = hesk_fetch_access_token($set['imap_oauth_provider']);
        if (!$access_token) {
            global $imap_error, $imap_log;
            $imap_error = $hesklang['oauth_error_retrieve'];
            $imap_log = $hesklang['oauth_error_retrieve'];
            return false;
        }

        $imap->accessToken = $access_token;
        $imap->useOAuth = true;
        $imap->password = null;
    }

    $imap->readOnly = false;
    $imap->ignoreCertificateErrors = $set['imap_noval_cert'];
    $imap->disableGSSAPI = $set['imap_disable_GSSAPI'];
    $imap->connectTimeout = 15;
    $imap->responseTimeout = 15;
    $imap->imap_mailbox = $set['imap_mailbox'];// Added for IMAP Mailbox
    $imap->folder = $set['imap_mailbox']; //Change for IMAP Mailbox;

    if ($set['imap_enc'] === 'ssl')
    {
        $imap->ssl = true;
        $imap->tls = false;
    }
    elseif ($set['imap_enc'] === 'tls')
    {
        $imap->ssl = false;
        $imap->tls = true;
    }
    else
    {
        $imap->ssl = false;
        $imap->tls = false;
    }

    if ($imap->login())
    {
        global $emails_found;
        $emails_found = 0;
        echo $hesk_settings['debug_mode'] ? "<pre>Connected to the IMAP server &quot;" . $imap->host . ":" . $imap->port . "&quot;.</pre>\n" : '';

        if ($imap->hasUnseenMessages())
        {
            $emails = $imap->getUnseenMessageIDs();
            $emails_found = count($emails);
        }

        $imap->logout();
    }

    // Any error messages?
    if($errors = $imap->getErrors())
    {
        global $imap_error, $imap_log;

        $imap_error = end($errors);
        reset($errors);

        $imap_log = '';

        foreach ($errors as $error)
        {
            $imap_log .= hesk_htmlspecialchars($error) . "\n";
        }
    }
    else
    {
        $connection_OK = true;
    }

    ob_end_clean();

    return $connection_OK;
} // END hesk_testIMAP()


function hesk_generate_SPAM_question()
{
	$useChars = 'AEUYBDGHJLMNPRSTVWXZ23456789';
	$ac = $useChars[mt_rand(0,27)];
	for($i=1;$i<5;$i++)
	{
	    $ac .= $useChars[mt_rand(0,27)];
	}

    $animals = array('dog','cat','cow','pig','elephant','tiger','chicken','bird','fish','alligator','monkey','mouse','lion','turtle','crocodile','duck','gorilla','horse','penguin','dolphin','rabbit','sheep','snake','spider');
    $not_animals = array('ball','window','house','tree','earth','money','rocket','sun','star','shirt','snow','rain','air','candle','computer','desk','coin','TV','paper','bell','car','baloon','airplane','phone','water','space');

    $keys = array_rand($animals,2);
    $my_animals[] = $animals[$keys[0]];
    $my_animals[] = $animals[$keys[1]];

    $keys = array_rand($not_animals,2);
    $my_not_animals[] = $not_animals[$keys[0]];
    $my_not_animals[] = $not_animals[$keys[1]];

	$my_animals[] = $my_not_animals[0];
    $my_not_animals[] = $my_animals[0];

    $e = mt_rand(1,9);
    $f = $e + 1;
    $d = mt_rand(1,9);
    $s = intval($e + $d);

    if ($e == $d)
    {
    	$d ++;
    	$h = $d;
        $l = $e;
    }
    elseif ($e < $d)
    {
    	$h = $d;
        $l = $e;
    }
    else
    {
    	$h = $e;
        $l = $d;
    }

    $spam_questions = array(
    	$f => 'What is the next number after '.$e.'? (Use only digits to answer)',
    	'white' => 'What color is snow? (give a 1 word answer to show you are a human)',
    	'green' => 'What color is grass? (give a 1 word answer to show you are a human)',
    	'blue' => 'What color is water? (give a 1 word answer to show you are a human)',
    	$ac => 'Access code (type <b>'.$ac.'</b> here):',
    	$ac => 'Type <i>'.$ac.'</i> here to fight SPAM:',
    	$s => 'Solve this equation to show you are human: '.$e.' + '.$d.' = ',
    	$my_animals[2] => 'Which of these is not an animal: ' . implode(', ',hesk_randomize_array($my_animals)),
    	$my_not_animals[2] => 'Which of these is an animal: ' . implode(', ',hesk_randomize_array($my_not_animals)),
    	$h => 'Which number is higher <b>'.$e.'</b> or <b>'.$d.'</b>:',
    	$l => 'Which number is lower <b>'.$e.'</b> or <b>'.$d.'</b>:',
        'no' => 'Are you a robot? (yes or no)',
        'yes' => 'Are you a human? (yes or no)'
    );

    $r = array_rand($spam_questions);
	$ask = $spam_questions[$r];
    $ans = $r;

    return array($ask,$ans);
} // END hesk_generate_SPAM_question()


function hesk_randomize_array($array)
{
	$rand_items = array_rand($array, count($array));
	$new_array = array();
	foreach($rand_items as $value)
	{
	    $new_array[$value] = $array[$value];
	}

	return $new_array;
} // END hesk_randomize_array()


function hesk_checkMinMax($myint,$min,$max,$defval)
{
	if ($myint > $max || $myint < $min)
	{
		return $defval;
	}
	return $myint;
} // END hesk_checkMinMax()
