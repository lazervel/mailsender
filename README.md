# Lazervel MailSender

**Lightweight PHP library for effortless email sending.**  
Built on top of [PHPMailer](https://github.com/PHPMailer/PHPMailer), it provides an easy and elegant way to send HTML emails with inline CSS, embedded images, and attachments.

---

## 🚀 Features

- Simple and clean wrapper for PHPMailer  
- Inline **CSS** support (`<link rel="stylesheet">` auto inlines to `<style>`)  
- Embedded **local images** automatically converted to CID  
- File and base64 **attachments** support  
- Handles **multiple recipients** with one call  
- Easy integration with environment variables (`.env` or `$_ENV`)  

---

## 📥 Installation

Install via **Composer**:

```bash
composer require lazervel/mailsender
```

Or manually add to your `composer.json`:

```json
{
  "require": {
    "lazervel/mailsender": "^1.0"
  }
}
```

---

## 🧩 Basic Usage

```php
<?php

require 'vendor/autoload.php';

use Lazervel\MailSender\MailSender;

$mail = new MailSender(
  name: 'My App',
  email: 'youremail@gmail.com',
  password: 'yourpassword'
);

// Single recipient
$mail->addMail('John Doe', 'john@example.com');
$mail->mail->Subject = 'Welcome!';
$mail->mail->Body    = '<h1>Hello John!</h1><p>Welcome to our app.</p>';

// Send
if ($mail->send()) {
  echo "Mail sent successfully!";
} else {
  echo "Failed to send mail!";
}
```

---

## 📧 Multiple Recipients Example

```php
$recipients = [
  [
    'name'  => 'User One',
    'email' => 'user1@example.com',
    'subject' => 'Hello!',
    'body' => '<p>This is a test email.</p>'
  ],
  [
    'name'  => 'User Two',
    'email' => 'user2@example.com',
    'subject' => 'Another Mail',
    'body' => '<p>This is another email.</p>'
  ]
];

$mail->sendTo($recipients)->send();
```

---

## 🖇️ Attachments

### From File Path:
```php
$mail->addAttachment('/path/to/file.pdf');
```

### From Uploaded File (`$_FILES`):
```php
$mail->addTmpFileAttachment($_FILES['file']);
```

### From Base64 or URL:
```php
$mail->addStringAttachment($dataUrl, 'document.pdf');
```

---

## 🎨 Inline CSS & Images

If your HTML contains linked CSS or image paths:

```html
<link rel="stylesheet" href="style.css">
<img src="logo.png">
```

They will automatically be converted into inline `<style>` blocks and embedded CID images in the final email.

---

## ⚙️ Environment Variables (Optional)

You can define these in your `.env` file or system environment:

```
MAILER_NAME="My App"
MAILER_EMAIL="youremail@gmail.com"
MAILER_PASSWORD="yourpassword"
APP_NAME="My App"
```

---

## 🧱 Directory Structure

```
MailSender/
├── composer.json
├── README.md
├── src/
│   ├── MailSender.php
│   └── Exception/
│       └── ConfigurationException.php
└── tests/
    └── MailSenderTest.php
```

---

## 🧪 Testing

```bash
vendor/bin/phpunit
```

---

## 📜 License

This project is open-sourced under the **MIT License**.  
Feel free to use, modify, and distribute with attribution.

---

## ❤️ Author

**Afsara**  
Developer & Founder — *Lazervel*  
[GitHub](https://github.com/yourusername) • [Email](mailto:indianmodassir@gmail.com)