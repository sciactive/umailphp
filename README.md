# µMailPHP

An email templating system for Nymph.

## Installation

```sh
composer require sciactive/umailphp
npm install --save umailphp
```

uMailPHP is configured with the `\uMailPHP\Mail::configure()` static method. The configuration options are available in `conf/defaults.php`.

## Usage

```php
// After creating the \MyApp\VerifyEmailDefinition class, which extends
// \uMailPHP\Definition, you can use it like this:

// Define any macros, and be sure to escape them with htmlspecialchars.
$link = htmlspecialchars('https://example.com/userVerify?secret='.$someSecret);
$macros = [
  'verify_link' => $link,
  'start_date' => htmlspecialchars(\uMailPHP\Mail::formatDate(
      $user->startDate,
      'date_med',
      '',
      $user->timezone
  )),
  'to_phone' => htmlspecialchars(\uMailPHP\Mail::formatPhone($user->phone)),
  'to_group' => htmlspecialchars($user->group->name)
];
// Create the mail object. Second argument can either be an email address or an
// object with an 'email' property.
$mail = new \uMailPHP\Mail('\MyApp\VerifyEmailDefinition', $user, $macros);
// Send mail.
if (!$mail->send()) {
  throw new \Exception('Email failed.');
}
```

## How It Works

uMailPHP constructs emails using three parts:

- The template.
- The mail definition.
- An optional custom redefinition called a rendition.

The **template** is used for each email that is sent. It provides the basic layout of the email. If you don't specify a template when sending an email, the first one that is enabled will be used. If you haven't defined any templates, a default one will be used.

The **mail definition** is a PHP class responsible for initiating the email. It provides the main content of the email. For example, the mail definition of an email sent when a new user registers would pertain to the user and may have their information in the body of the email. The definition of an email sent when a user makes a purchase could provide information about the purchase and a receipt.

A **rendition** is stored in the database. When defined, it's used in place of the mail definition to construct the body of the email. It can be created through the setup GUI, rather than hard coded to customize the email's content.

## How To Customize

The two ways you can customize the emails are by creating templates and renditions. When you create a template, you can design the overall look and design of all emails. When you create a rendition, you can customize a single type of email (like a new user registration email).

## Macros

The content in an email almost always includes variables, such as the recipient's name, which are handled by macros. A macro is just the name of a variable surrounded by hash symbols (e.g. #to_name#). This text is replaced before the email is sent.

There are universal macros, which can be used in any template, definition, or rendition. Also, there are macros specific to a mail definition. For example, the definition of an email sent when a user changes an appointment with a customer could have macros called #old_date# and #new_date#. When you create a rendition to customize the email, you can use these macros.

### Heads Up

When you format a macro, edit the macro string as a whole. If you want to bold it, change the whole string, including the hash symbols.

- Right: **#to_name#**, *#old_date#*
- Wrong:  #**to_name**#, #*old_date*#
