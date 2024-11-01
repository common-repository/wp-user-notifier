<?php
/*wp-user-notifier*/
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( class_exists( 'WpUserNotifier' ) ) return;

/**
* Class WpUserNotifier
* This class instance all functionals
* 
* @class WpUserNotifier
* @version 1.0
* @author Mike Luskavets
* 
*/
class WpUserNotifier{
	/**
	* Version plugin
	* 
	* @since 1.0
	* 
	* @var 
	* 
	*/
	public $version = '1.0';
	/**
	* Default vars
	* 
	* @since 1.0
	* 
	* @var vars
	* 
	*/
	private $TextDomain = 'wp-user-notifier',
			$Slug = 'wp_user_notifier',
			$SupportCF7 = FALSE,
			$UserName,
			$Email,
			$Url,
			$Shortcodes = array(),
			$Letter = array(
				'email'			=>	NULL,
				'subject'		=>	NULL,
				'message'		=>	NULL,
				'headers'		=>	NULL,
				'attachment'	=>	NULL,
			);
	
	
	/**
	* Construct.
	*
	* @since 1.0
	*
	* @return void.
	*/
	public function __construct(){
		
		add_action( 'plugins_loaded', array( $this, 'LoadPluginTextDomain') );
		
		if(class_exists('WPCF7')) $this->SupportCF7 = TRUE;
				
		if(!get_option($this->Slug)){
			if(!update_option($this->Slug, 'install')) add_option($this->Slug, 'install', '', 'yes');
			$this->SetDefaultSettings();
		}		
		
		add_action('admin_menu', array($this, 'AdminMenu'));
		
		add_action('comment_post', array($this, 'SendLetterAfterCommentSent'), 11, 2);	
		
		if($this->SupportCF7){
			add_action('wpcf7_before_send_mail', array($this, 'SendLetterAfterCF7Sent'), 10, 1);
		}
		
	}
	
	/**
	* Init text domain
	* 
	* @since 1.0
	* 
	* @return void.
	*/
	public function LoadPluginTextDomain(){
		
		$locale = apply_filters('plugin_locale', get_locale(), $this->TextDomain);
		
		if( $loaded = load_textdomain( $this->TextDomain, trailingslashit( WP_LANG_DIR ) . $this->TextDomain . '/' . $this->TextDomain . '-' . $locale . '.mo' ) )
		return $loaded;
		else
		load_plugin_textdomain( $this->TextDomain, FALSE, $this->TextDomain . '/languages' );
	    
	}
	
	/**
	* Add sub menu to admin panel
	* 
	* @since 1.0
	* 
	* @return void.
	*/
	public function AdminMenu(){
	
		add_submenu_page(
			'options-general.php',
			__( 'User Notifier', $this->TextDomain ),
			__( 'User Notifier', $this->TextDomain ),
			'manage_options',
			$this->TextDomain.'-settings',
			array( $this, 'Settings' ),
			'dashicons-admin-tools'
		);

	}
	
	/**
	* Initial settings page in admin panel
	* 
	* @since 1.0
	* 
	* @return
	*/
	public function Settings(){
		
		$updated = FALSE;

		if( isset( $_POST[$this->Slug] ) && ! wp_verify_nonce( $_POST['_wpnonce'], $this->Slug ) )
		wp_die( 'Could not verify nonce' );

		if( isset( $_POST[$this->Slug] ) ){

			if( isset( $_POST[$this->Slug.'_enable'] ) )
			$this->enable = 1;
			else
			$this->enable = 0;	
				
			if( isset( $_POST[$this->Slug.'_comment_need_pending'] ) )
			$this->comment_need_pending = 1;
			else
			$this->comment_need_pending = 0;	
			
			if( isset( $_POST[$this->Slug.'_enabled_cf7'] ) )
			$this->enabled_cf7 = 1;
			else
			$this->enabled_cf7 = 0;	
	
			
			if( isset( $_POST[$this->Slug.'_subject'] ) )		
			$this->subject = $_POST[$this->Slug.'_subject'];
			
			if( isset( $_POST[$this->Slug.'_message'] ) )
			$this->message = $_POST[$this->Slug.'_message'];
			
			$this->UpdateSettings();
			
			$updated = TRUE;			
			$update_message = esc_html('Settings saved', $this->TextDomain);

		}
		
		if( isset( $_POST[$this->Slug.'_restore_default_settings'] ) ){
			
			$this->SetDefaultSettings();
			
			$updated = TRUE;			
			$update_message = esc_html('Settings restored default', $this->TextDomain);

		}
		
		$this->GetSettings();
		
		$this->GetShortcodes();
		
		?>
		<div class="wrap">
			<?php if( $updated ) : ?>
			<div id="message" class="updated fade">
				<p><?php echo $update_message;?></p>
			</div>
			<?php endif; ?>
			<h1><?php esc_html_e('User Notifier', $this->TextDomain);?></h1>
			<p><?php esc_html_e( 'Send user notify when sent comment!', $this->TextDomain ); ?></p>
			<form method="post">
				<table class="form-table">
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Enable', $this->TextDomain ); ?></label></th>
						<td>
							<input type="checkbox" name="<?php echo $this->Slug.'_enable';?>" value="1" <?php checked( $this->enable ); ?> />
							<?php esc_html_e( 'Enable user notifier', $this->TextDomain ); ?>
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Send notify:', $this->TextDomain ); ?></label></th>
						<td>
							<input type="checkbox" name="<?php echo $this->Slug.'_comment_need_pending';?>" value="1" <?php checked( $this->comment_need_pending ); ?> />
							<?php esc_html_e( 'Comment needs to be pending', $this->TextDomain ); ?>							
						</td>
					</tr>
					<?php if($this->SupportCF7):?>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Enable CF7:', $this->TextDomain ); ?></label></th>
						<td>
							<input type="checkbox" name="<?php echo $this->Slug.'_enabled_cf7';?>" value="1" <?php checked( $this->enabled_cf7 ); ?> />
							<?php esc_html_e( 'Send notify via Contact Form 7', $this->TextDomain ); ?>
							<p class="help">
								<?php esc_html_e( 'Only support default cf7 fields name:', $this->TextDomain );?>
								<?php echo ' <code>[your-email]</code>, <code>[your-name]</code>'; ?>
							</p>							
						</td>
					</tr>
					<?php endif;?>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Subject', $this->TextDomain ); ?></label></th>
						<td>
							<input type="text" name="<?php echo $this->Slug.'_subject';?>" class="large-text" value="<?php echo esc_attr( $this->subject ); ?>" />
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Message', $this->TextDomain ); ?></label></th>
						<td>
							<?php wp_editor( $this->message, $this->Slug.'_message', array('wpautop'=>true));?>						
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Available shortcodes:', $this->TextDomain ); ?></label></th>
						<td>							
							<?php if(!empty($this->Shortcodes)) echo '<code>'.implode('</code>, <code>', array_keys($this->Shortcodes)).'</code>';?>
							<p class="help">
								<?php esc_html_e( 'Shortcodes work in the fields subject and message', $this->TextDomain ); ?>								
							</p>
						</td>
					</tr>
					<tr class="default-row">
						<th></th>
						<td>
							<?php wp_nonce_field( $this->Slug ); ?>
							<input type="submit" class="button submit button-primary" name="<?php echo $this->Slug;?>" value="<?php esc_attr_e( 'Save', $this->TextDomain ); ?>" />
						</td>
					</tr>
					
				</table>
			</form>
			<hr>			
			<form method="post">
				<table class="form-table">
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Restore default', $this->TextDomain ); ?></label></th>
						<td id="default_settings">
							<button class="button" type="button" onclick="document.getElementById('default_settings').hidden = true;document.getElementById('default_settings_confirm').hidden = false; return false;"><?php esc_html_e( 'Default settings', $this->TextDomain ); ?></button>
							<p class="help"><?php esc_html_e( 'Restore all settings to default', $this->TextDomain ); ?></p>
						</td>
						<td id="default_settings_confirm" hidden="hidden">
							<?php wp_nonce_field( $this->Slug ); ?>							
							<button class="button button-primary" type="submit" name="<?php echo $this->Slug.'_restore_default_settings';?>" value="yes"><?php esc_html_e( 'Yes', $this->TextDomain ); ?></button>
							<button class="button" type="button" onclick="document.getElementById('default_settings').hidden = false;document.getElementById('default_settings_confirm').hidden = true; return false;"><?php esc_html_e( 'No', $this->TextDomain ); ?></button>
							<p class="help"><?php esc_html_e( 'Are you sure?', $this->TextDomain ); ?></p>								
						</td>
					</tr>
				</table>
			</form>
		</div>
		<?php

	}
	
	/**
	* Send letter after comment sent
	* 
	* @since 1.0
	* 
	* @param integer $comment_ID
	* @param integer $approved
	* 
	* @return void.
	*/
	public function SendLetterAfterCommentSent($comment_ID, $approved){
		
		$this->GetSettings();
		
		if(!$this->enable) return;
		
		if(!$this->comment_need_pending || $this->comment_need_pending && $approved == 0){
			
			$comment = get_comment($comment_ID);
	    	
			$this->Email = $comment->comment_author_email;
			$this->UserName = trim($comment->comment_author);
			$this->Url = get_comment_link( $comment );	    	
	    	
			$this->GetLetter();
	    		    	
			$this->SentLetter();
			
		}   
		
	}
	
	/**
	* Send letter after CF7 form submit
	* 
	* @since 1.0
	* 
	* @param undefined $cf7
	* 
	* @return void.
	*/
	public function SendLetterAfterCF7Sent($cf7){
		
		$this->GetSettings();
		
		if(!$this->enable) return;
		if(!$this->enabled_cf7) return;
		
		
		$this->Email = trim($_POST['your-email']);
		$this->UserName = trim($_POST['your-name']);
		$this->Url = $this->CurrentPageURL();
	    
		$this->GetLetter();
    	
		$this->SentLetter();
		
	}
	
	/**
	* Set default settings
	* 
	* @since 1.0
	* 
	* @return void.
	*/
	private function SetDefaultSettings(){
		
		$this->enable = 1;	
		$this->comment_need_pending = 1;	
		$this->enabled_cf7 = (int)$this->SupportCF7;
		$this->subject = __( 'Notify from [sitename]', $this->TextDomain );
		$this->message = __( '
		<strong>Hello, [name], We got your message.</strong>
		<strong>Answer would be sent as soon as possible.</strong>
			
		&nbsp;
			
		<strong>If you didn\'t do this, someone has done it for you from the page [url], using your e-mail [email]. We are sorry for this email (we are apologize).</strong>
			
		&nbsp;
			
		<strong>Sincerely, [sitename]</strong>
		<strong>[siteurl]</strong>
		', $this->TextDomain );		
		
		$this->UpdateSettings();
		
	}
	
	/**
	* Get settings
	* 
	* @since 1.0
	* 
	* @return void.
	*/
	private function GetSettings(){
		
		$this->enable = get_option( $this->Slug.'_enable' );
		$this->comment_need_pending = get_option( $this->Slug.'_comment_need_pending' );
		$this->enabled_cf7 = get_option( $this->Slug.'_enabled_cf7' );
		$this->subject = get_option( $this->Slug.'_subject' );
		$this->message = html_entity_decode(get_option( $this->Slug.'_message'));
			
	}
	
	/**
	* Update settings
	* 
	* @since 1.0
	* 
	* @return void.
	*/
	private function UpdateSettings(){
		
		update_option( $this->Slug.'_enable', $this->enable );
		update_option( $this->Slug.'_comment_need_pending', $this->comment_need_pending );
		update_option( $this->Slug.'_enabled_cf7', $this->enabled_cf7 );
		update_option( $this->Slug.'_subject', esc_html($this->subject) );
		update_option( $this->Slug.'_message', htmlentities(stripslashes($this->message), ENT_QUOTES, 'UTF-8') );
		
	}
	
	/**
	* Create letter
	* 
	* @since 1.0
	* 
	* @return void.
	*/
	private	function GetLetter(){
		
		$this->GetShortcodes();
		
		$this->subject = str_replace(array_keys($this->Shortcodes), array_values($this->Shortcodes), $this->subject);
		$this->message = str_replace(array_keys($this->Shortcodes), array_values($this->Shortcodes), $this->message);
		
		$this->Letter['email'] = $this->Email;
		$this->Letter['subject'] = $this->subject;
		$this->Letter['message'] = '
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>'.$this->subject.'</title>
		</head>
		<body width="100%" marginheight="0" topmargin="0" marginwidth="0" leftmargin="0" style="margin:0;padding:0 30px;font-family:Calibri, Tahoma, Arial">
		'.$this->GetContent($this->message).'
		</body>
		</html>
		';
		$this->Letter['headers'] = array('Content-Type: text/html; charset=UTF-8'."\r\n");
		$this->Letter['attachment'] = NULL;
		
	}
	
	/**
	* Initial shortcodes
	* 
	* @since 1.0
	* 
	* @return void.
	*/
	private function GetShortcodes(){
		
		$this->Shortcodes = array(
			'[name]'    => $this->UserName,
			'[email]'   => $this->Email,
			'[url]'     => $this->Url,
			'[sitename]'=> wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
			'[siteurl]' => site_url(),
		);
		
	}
	
	/**
	* Sent Letter
	* 
	* @since 1.0
	* 
	* @return void.
	*/
	private	function SentLetter(){
		
		if(!is_null($this->Letter['email']) && is_email($this->Letter['email'])){
			$this->Send = @wp_mail( $this->Letter['email'], $this->Letter['subject'], $this->Letter['message'], $this->Letter['headers'], $this->Letter['attachment']);
		}
		
	}
	
	/**
	* Filter letter message
	* 
	* @since 1.0
	* 
	* @param string $content
	* 
	* @return string html
	*/
	private function GetContent($content){
		
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		
		return $content;
		
	}
	
	/**
	* Get current page url when submit form
	* 
	* @since 1.0
	* 
	* @return string
	*/
	private function CurrentPageURL(){		
	
		$url = 'http';	    
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $url .= 's';	    	
		$url .= "://";
		if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') $url .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		else $url .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		return $url;
		
	}
	
}