<?php

declare(strict_types=1);

namespace Lazervel\MailSender;

use Lazervel\MailSender\Exception\ConfigurationException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Class MailSender
 *
 * A lightweight wrapper around PHPMailer to simplify email sending, attachments,
 * inline images, and HTML + CSS formatting.
 * @see https://github.com/lazervel/mailsender
 *
 * @package Lazervel\MailSender
 */
class MailSender
{
  /**
   * Instance of PHPMailer used internally.
   *
   * @var PHPMailer
   */
  public $mail;

  /**
   * Constructor initializes PHPMailer with Gmail SMTP by default.
   * 
   * @param string $name     [optional]
   * @param string $email    [optional]
   * @param string $password [optional]
   */
  public function __construct(?string $name = null, ?string $email = null, ?string $password = null)
  {
    $mail = new PHPMailer;

    try {
      $name = $name ?? $_ENV['MAILER_NAME'] ?? $_ENV['APP_NAME'];
      $email = $email ?? $_ENV['MAILER_EMAIL'];
      $password = $password ?? $_ENV['MAILER_PASSWORD'];

      // PHPMailer Configuration
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPSecure = 'tls';
      $mail->Port       = 587;
      $mail->SMTPAuth   = true;
      $mail->Username   = $email;
      $mail->Password   = $password;
      $mail->isHTML(true);
      $mail->setFrom($email, $name);
      $mail->addReplyTo($email, $name);
      $this->mail = $mail;
    } catch(\Exception $err) {
      throw new ConfigurationException('Failed PHPMailer Configuration!');
    }
  }

  /**
   * Send the prepared email message.
   *
   * @return bool True if sent successfully, false otherwise.
   */
  public function send() : bool
  {
    try {
      $this->mail->send();
      return true;
    } catch(\Exception $err) {
      return false;
    }
  }

  /**
   * Send mail to one recipients with all supported method.
   * 
   * @param string       $name        [required]
   * @param string       $email       [required]
   * @param string       $subject     [required]
   * @param string       $body        [required]
   * @param array|string $attachments [optional]
   */
  public function mailTo(string $name, string $email, string $subject, string $body, ?array $attachments = null) : self
  {
    $this->mail->addAddress($email, $name);
    $this->mail->Subject = $subject;
    $this->Body = $this->formatBody($body);
    $this->AltBody = \strip_tags($body);
    $this->attachments = (array)$attachments;
    return $this;
  }

  /**
   * Add a file attachment to the email.
   * 
   * @param string      $file [required]
   * @param string|null $name [optional]
   * 
   * @return self
   */
  public function addAttachment(string $file, ?string $name = null) : self
  {
    if (\file_exists($file) && \is_readable($file)) {
      $this->mail->addAttachment($file, \basename($file));
    }
    return $this;
  }

  /**
   * Add a single recipient.
   * 
   * @param string $name  [required]
   * @param string $email [required]
   * 
   * @return self
   */
  public function addMail(string $name, string $email) : self
  {
    $this->mail->addAddress($email, $name);
    return $this;
  }

  /**
   * Send mail to one or multiple recipients with provided options.
   * Each element of $options should contain:
   *  - name        => recipient name
   *  - email       => recipient email
   *  - subject     => subject of mail
   *  - body/html   => HTML message content
   *  - altBody     => plain text alternative (optional)
   *  - attachments => array of file paths or uploaded files (optional)
   * 
   * @param array $options [required]
   * @return self
   */
  public function with(array $options) : self
  {
    if (!isset($options[0])) return $this->with([$options]);

    foreach($options as $option) {
      $name        = $option['name'];
      $email       = $option['email'];
      $subject     = $option['subject'];
      $body        = $option['body'] ?? $option['html'];
      $attachments = $option['attachments'] ?? $option['attachment'] ?? [];

      $this->mail->addAddress($email, $name);
      $this->mail->Subject = $subject;
      $this->mail->Body    = $this->formatBody($body);
      $this->mail->AltBody = \strip_tags($altBody);
      $this->attachments((array)$attachments);
    }

    return $this;
  }

  /**
   * Add uploaded (temporary) files as attachments.
   * 
   * @param array|array<array> $tmpFiles
   * @return self
   */
  public function addTmpFileAttachment($tmpFiles) : self
  {
    $fileTmp = $tmpFiles['tmp_name'];

    if (\is_array($fileTmp)) {
      foreach($fileTmp as $i => $tmp) {
        if ($tmpFiles['error'][$i] === \UPLOAD_ERR_OK && \is_uploaded_file($tmp)) {
          $this->mail->addAttachment($tmp, $tmpFiles['name'][$i]);
        }
      }
    } else if (\is_uploaded_file($fileTmp) && $tmpFiles['error'] === \UPLOAD_ERR_OK) {
      $this->mail->addAttachment($fileTmp, $tmpFiles['name']);
    }

    return $this;
  }

  /**
   * Add multiple attachments (files or base64 strings).
   * 
   * @param mixed $files [required]
   * @return self
   */
  public function attachments($files) : self
  {
    if (isset($files['tmp_name'])) {
      $this->addTmpFileAttachment($files);
    } else {
      foreach($files as $file) {
        \is_file($file) ? $this->addAttachment($file) : $this->addStringAttachment($file);
      }
    }
    return $this;
  }

  /**
   * Add a string or base64 encoded file as attachment.
   * 
   * @param string      $dataUrl [required]
   * @param string|null $name    [optional]
   * 
   * @return self
   */
  public function addStringAttachment(string $dataUrl, ?string $name = null) : self
  {
    list($identifier, $data) = explode(',', $dataUrl);
    $data = $identifier ? \base64_decode($data) : $data;

    if (!(\file_exists($dataUrl) && \is_readable($dataUrl))) {
      $data = \file_get_contents($dataUrl);
      $name = $name ?? \basename($dataUrl);
    }

    $this->mail->addStringAttachment($data, $name ?? \time());
    return $this;
  }

  /**
   * Parse CSS code and recursively include files imported via @import.
   * 
   * @param string $code [required]
   * @return string Parsed CSS with inlined @import contents.
   */
  private function css($code) : string
  {
    $pattern = '/@import url\((.*?)\);/';
    return \preg_replace_callback($pattern, function() {
      list($matched, $url) = \func_get_args()[0];
      
      if (!(\file_exists($url) && \is_readable($url))) return $matched;
      return $this->css(\file_get_contents($url));
    }, $code);
  }

  /**
   * Format the HTML body to inline CSS and embed local images.
   * 
   * - Converts <link rel="stylesheet" href=""> to inline <style> blocks.
   * - Converts <img src="path"> and other tag sources to embedded CIDs.
   * 
   * @param string $html [required]
   * @return string Formatted HTML with embedded resources.
   */
  private function formatBody(string $html) : string
  {
    $cssPattern = '/<link rel="stylesheet" href=(\'|")(.*?)(\1)(.*?)>/';
    $html = \preg_replace_callback($cssPattern, function() {
      $args = \func_get_args()[0];

      $matched = $args[0];
      $path = $args[2];
      $cot = $args[3];
      $next = $args[4];

      if (!(\file_exists($path) && \is_readable($path))) return $matched;

      $css = $this->css(\file_get_contents($path));
      return \sprintf("<style>%s</style>", $css);
    }, $html);

    $srcPattern = '/<(\w+) src=(\'|")(.*?)(\2)(.*?)>/';
    $mail = $this->mail;

    return \preg_replace_callback($srcPattern, function() use ($mail) {
      list($matched, $tag, $cot, $src, $_, $next) = \func_get_args()[0];

      if (!(\file_exists($src) && \is_readable($src))) return $matched;
      
      $namespace = \md5($src);
      $mail->addEmbeddedImage($src, $namespace, \basename($src));
      $cid = \sprintf('cid:%s', $namespace);
      return \sprintf('<%s src=%s%s%2$s %s>', $tag, $cot, $cid, \trim($next));
    }, $html);
  }
}
?>