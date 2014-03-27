<?php

namespace Hackstack\Helpers;

/**
 * Connects to the DB using Illuminate and the connection details in the yaml configuration file
 */
class MailServiceHelper extends \Hackstack\Helpers\Helper {

	/**
	 * The array of config options loaded from the mailer.yml file
	 * @var [Array]
	 */
	private static $MailerConfiguration;

	/**
	 * Instance of the smtp transport class
	 * @var instance Swift_SmtpTransport
	 */
	public static $Transport;

	/**
	 * Instance of the swift mailer class
	 * @var instance Swift_Mailer
	 */
	public static $Mailer;

	protected function __construct() {
		parent::__construct();

		self::$MailerConfiguration = \pakeYaml::loadFile(self::$AppRoot . "/configuration/mailer.yml");
		if(!empty(self::$MailerConfiguration)) {
			self::$Transport = \Swift_SmtpTransport::newInstance(
				self::$MailerConfiguration["development"]["host"], 
				self::$MailerConfiguration["development"]["port"],
				"ssl"
			)->setUsername(
				self::$MailerConfiguration["development"]["username"]
			)->setPassword(
				self::$MailerConfiguration["development"]["password"]
			);
			
			self::$Mailer = \Swift_Mailer::newInstance(self::$Transport);
		} else {
			throw new \Exception("No email service configuration could be found.");
		}
	}

	/**
	 * Creates a new message instance and sends it through the configured mailer
	 * @param  [String] $to
	 * @param  [String] $subject
	 * @param  [String/HTML] $body
	 * @return [Integer] The number of messages sent (should be 1)
	 */
	public function message($to, $subject, $body) {
		if(!empty(self::$MailerConfiguration["development"]["sender"])) {
			$message = \Swift_Message::newInstance();
				$message->setFrom(Array(
					self::$MailerConfiguration["development"]["sender"]["email"] => self::$MailerConfiguration["development"]["sender"]["name"]
				));
				$message->setTo($to);
				$message->setSubject($subject);
				$message->setBody($body);
				$message->setContentType("text/html");

			return self::$Mailer->send($message);
		} else {
			return 0;
		}
	}	

}

?>