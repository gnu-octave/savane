<?php
# All the forms and functions to manage unix users
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017  Ineiev
#
# This file is part of Savane.
#
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(__FILE__).'/pwqcheck.php');

# Return a string explaining current pwcheck requirements.
function expand_pwqcheck_options()
{
  global $pwqcheck_args;

  $args = $pwqcheck_args." ";
  $help = "";

  if (preg_match("/max=([[:digit:]]*) /", $args, $matches))
    {
      $help .= "<br />\n";
        $help .= sprintf(_("The maximum allowed password length: %s."),
                         $matches[1]);
    }
  if (preg_match("/passphrase=([[:digit:]]*) /", $args, $matches))
    {
      $help .= "<br />\n";
        $help .= sprintf(_("The number of words required for a passphrase: %s."),
                         $matches[1]);
    }
  if (preg_match("/match=([[:digit:]]*) /", $args, $matches))
    {
      $help .= "<br />\n";
      if ($matches[1])
        $help .= sprintf(
_("The length of common substring required to conclude that a password
is at least partially based on information found in a character string: %s."),
                         $matches[1]);
      else
        $help .= _("Checks for common substrings are disabled.");
    } # preg_match($args, "/match=([^ ]*)/ ", $matches)

  $field = "([[:digit:]]*|disabled)";
  if (preg_match("/min=".$field.",".$field.",".$field.",".$field.",".$field." /",
     $args, $matches))
    {
      $help .= "<br />\n";
      if($matches[1] == "disabled")
        $help .=
_("Passwords consisting of characters from one class only are not allowed.");
      else
        $help .= sprintf(
_("The minimum length for passwords consisting of characters from one class: %s."),
                         $matches[1]);
      $help .= "<br />\n";
      if ($matches[2] == "disabled")
        $help .=
_("Passwords consisting of characters from two classes that don't meet
requirements for passphrases are not allowed.");
      else
        $help .= sprintf(
_("
The minimum length for passwords consisting of characters from two classes
that don't meet requirements for passphrases: %s."), $matches[2]);
      $help .= "<br />\n";
      if ($matches[3] == "disabled")
        $help .= _("Check for passphrases is disabled.");
      else
        $help .=
          sprintf(_("The minimum length for passphrases: %s."), $matches[3]);
      $help .= "<br />\n";
      if($matches[4] == "disabled")
        $help .=
 _("Passwords consisting of characters from three classes are not allowed.");
      else
        $help .= sprintf(
_("The minimum length for passwords consisting of characters
from three classes: %s."), $matches[4]);
      $help .= "<br />\n";
      if($matches[5] == "disabled")
        $help .=
_("Passwords consisting of characters from four classes are not allowed.");
      else
        $help .= sprintf(
_("The minimum length for passwords consisting of characters
from four classes: %s."), $matches[5]);
    } # preg_match("/min=".$field.",".$field.",".$field.",".$field.",".$field." /",
  return $help;
}

function account_password_help()
{
  global $use_pwqcheck, $pwqcheck_args;
  $help =
_("Note: The password should be long enough
or containing multiple character classes:
symbols, digits (0-9), upper and lower case letters.");
  if ($use_pwqcheck)
    {
      $pwqgen = exec("pwqgen");
# TRANSLATORS: the argument is an example of passphrase.
      $help .= " ".sprintf(_("For instance: %s."), htmlspecialchars($pwqgen));
      $help .= " <br />\n".sprintf(_("pwqcheck options are '%s':"),
                           htmlspecialchars($pwqcheck_args));
      $help .= expand_pwqcheck_options();
    }
  return $help;
}

# Modified from
# http://www.openwall.com/articles/PHP-Users-Passwords#enforcing-password-policy
function account_pwvalid ($newpass, $oldpass = '', $user = '')
{
  global $use_pwqcheck, $pwqcheck_args;
  if ($use_pwqcheck)
    $check = pwqcheck($newpass, $oldpass, $user, '', $pwqcheck_args);
  else
    {
      /* Some really trivial and obviously-insufficient password strength
         checks - we ought to use the pwqcheck(1) program instead. */
      $check = 0;
# TRANSLATORS: this string in used in the context "Bad password (%s)".
      if (strlen($newpass) < 7)
        $check = _('way too short');
      elseif (stristr($oldpass, $newpass)
              || (strlen($oldpass) >= 4 && stristr($newpass, $oldpass)))
# TRANSLATORS: this string in used in the context "Bad password (%s)".
        $check = _('based on the old one');
      elseif (stristr($user, $newpass)
              || (strlen($user) >= 4 && stristr($newpass, $user)))
# TRANSLATORS: this string in used in the context "Bad password (%s)".
        $check = _('based on the username');
    }

  if ($check !== 0)
    {
      fb($check, 1);
      return 0;
    }
  return 1;
}

function account_sanitize_realname ($name)
{
  return strtr ($name, "'\",<", "    ");
}

function account_namevalid ($name, $allow_dashes=0, $allow_underscores=1,
                            $allow_dots=0, $MAX_ACCNAME_LENGTH=16,
                            $MIN_ACCNAME_LENGTH=3)
{
  $underscore = '';
  $dashe = '';
  $dot = '';

  # By default, underscore are allowed, creating no specific issue for an
  # account name. It may creates trouble if the account is use to handle DNS...
  if ($allow_underscores)
    $underscore = "_";

  # By default, dashes are not allowed, creating issue with mailing list name
  # and many other potential conflicts. However, it is usually convenient for
  # group name.
  $dash = $allow_dashes ? '-' : '';

  # By default, dots are not allowed. Unix systems may allow it but it
  # is a source of confusion (for instance, a problem if you have the habit
  # to things like `chown user.group`)
  # However, it is sometimes wise to allow it, for instance if we check for
  # a mailing-list name, which is almost like an account name + dots
  $dot = $allow_dots ? '.' : '';

  # No spaces.
  if (strrpos($name,' ') > 0)
    {
      fb(_("There cannot be any spaces in the name"),1);
      return 0;
    }

  # Min and max length.
  if (strlen($name) < $MIN_ACCNAME_LENGTH)
    {
      fb(_("The name is too short"), 1);
      fb(sprintf(ngettext("It must be at least %s character.",
                          "It must be at least %s characters.",
                          $MIN_ACCNAME_LENGTH), $MIN_ACCNAME_LENGTH),1);
      return 0;
    }

  if (strlen($name) > $MAX_ACCNAME_LENGTH)
    {
      fb(_("The name is too long"), 1);
      fb(sprintf(ngettext("It must be at most %s character.",
                          "It must be at most %s characters.",
                          $MAX_ACCNAME_LENGTH), $MAX_ACCNAME_LENGTH),1);
      return 0;
    }

  if (strspn($name,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") == 0)
    {
      fb(_("The name must begin with an alphabetical character."), 1);
      return 0;
    }

  # Must contain only allowed characters, depending on the arguments.
  if (strspn($name,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
                   ."0123456789$underscore$dash$dot")
      != strlen($name))
    {
      $tolerated = '';
      if ($allow_underscores)
        $tolerated .= '_, ';
      if ($allow_dashes)
        $tolerated .= '-, ';
      if ($allow_dots)
        $tolerated .= '., ';

      if ($tolerated)
        {
          $tolerated = rtrim($tolerated, ', ');
# TRANSLATORS: the argument is comma-separated list of additional characters
# (possibly single character).
          fb(sprintf(_('The name must only contain alphanumerics and %s.'),
                     $tolerated),1);
        }
      else
        fb(_("The name must only contain alphanumerics."), 1);
      return 0;
    }

  # Unacceptable names.
  if (preg_match (",^((root)|(savane-keyrings)|(bin)|(daemon)|(adm)"
                  . "|(lp)|(sync)|(shutdown)|(halt)|(mail)|(news)"
                  . "|(uucp)|(apache)|(operator)|(invalid)|(games)"
                  . "|(mysql)|(httpd)|(nobody)|(dummy)|(opensource)"
                  . "|(web)|(www)|(cvs)|(anoncvs)|(anonymous)|(shell)"
                  . "|(ftp)|(irc)|(debian)|(ns)|(download))$,i", $name))
    {
      fb(_("That name is reserved."), 1);
      return 0;
    }
  return 1;
}

# Just check if the email address domain is not from a forbidden domain
# or if it is not already associated to an email account.
function account_emailvalid ($email)
{
  if (db_numrows(db_execute("SELECT user_id FROM user WHERE "
                          . "email LIKE ?", array($email))) > 0)
    {
      fb(
_("An account associated with that email address has already been created."),1);
      return 0;
    }
  utils_get_content("forbidden_mail_domains");
  if (!empty($GLOBALS['forbid_mail_domains_regexp']))
    {
      if (preg_match($GLOBALS['forbid_mail_domains_regexp'], $email))
        {
          fb(
_("It is not allowed to associate an account with this email address."),1);
          return 0;
        }
    }
  return 1;
}

function account_groupnamevalid ($name)
{
  # Test with the usual namevalid function, allowing dashes.
  if (!account_namevalid($name, 1, 0))
    return 0;
  utils_get_content("forbidden_group_names");
  # All these groups are invalid by default. There can be used for system
  # services and already be existing on the system.
  # Please, keep that list in alphabetic order.
  $forbid_group_regexp = "/^(".
     "(adm)|".
     "(admin)|".
     "(apache)|".
     "(bin)|".
     "(compile)|".
     "(cvs[0-9]?)|".
     "(daemon)|".
     "(disk)|".
     "(download[0-9]?)|".
     "(exim)|".
     "(fencepost)|".
     "(ftp)|".
     "(ftp[0-9]?)|".
     "(gnudist)|".
     "(ident)|".
     "(irc[0-9]?)|".
     "(lists)|".
     "(lp)|".
     "(mail[0-9]?)|".
     "(man)|".
     "(monitor)|".
     "(mirrors?)|".
     "(nogroup)|".
     "(ns[0-9]?)|".
     "(news[0-9]?)|".
     "(ntp)|".
     "(postfix)|".
     "(projects)|".
     "(pub)|".
     "(root)|".
     "(rpc)|".
     "(rpcuser)|".
     "(shadow)|".
     "(shell[0-9]?)|".
     "(slayer)|".
     "(sshd)|".
     "(staff)|".
     "(sudo)|".
     "(savane-keyrings)|".   # reserved for keyrings
     "(svusers)|".   # users group for savane users
     "(sys)|".
     "(tty)|".
     "(uucp)|".
     "(users)|".
     "(utmp)|".
     "(web.*)|".
     "(wheel)|".
     "(www[0-9]?)|".
     "(www-data)|".
     "(xfs)".
     ")$/";

  # Forbidden names: check the hardcoded list unless the variable
  # $only_specific_forbid_group_regexp is true.
  if (!$GLOBALS['only_specific_forbid_group_regexp'])
    {
      dbg("apply standard regexp");
      if (preg_match($forbid_group_regexp,$name))
        {
          fb(_("This group name is not allowed."),1);
          return 0;
        }
    }

  # Forbidden names: check the site-specific list if a list is given
  # (by consequence, the variable return true).
  if ($GLOBALS['specific_forbid_group_regexp'])
    {
      dbg("apply specific regexp");
      if (preg_match($GLOBALS['specific_forbid_group_regexp'],$name))
        {
          fb(_("This group name is not allowed."),1);
          return 0;
        }
    }

  if (strpos ($name, "_") !== FALSE)
    {
      fb(_("Group name cannot contain underscore for DNS reasons."),1);
      return 0;
    }
  return 1;
}

# <phpass>
# From http://www.openwall.com/phpass/
# Version 0.3 / genuine
# Public domain
# Author: Solar Designer
function account_encode64($input, $count)
{
  $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $output = '';
  $i = 0;
  do
    {
      $value = ord($input[$i++]);
      $output .= $itoa64[$value & 0x3f];
      if ($i < $count)
        $value |= ord($input[$i]) << 8;
      $output .= $itoa64[($value >> 6) & 0x3f];
      if ($i++ >= $count)
        break;
      if ($i < $count)
        $value |= ord($input[$i]) << 16;
      $output .= $itoa64[($value >> 12) & 0x3f];
      if ($i++ >= $count)
        break;
      $output .= $itoa64[($value >> 18) & 0x3f];
    }
  while ($i < $count);
  return $output;
}

function account_get_random_bytes($count)
{
  $random_state = microtime();
  $output = '';
  if (is_readable('/dev/urandom')
      && ($fh = @fopen('/dev/urandom', 'rb')))
    {
      $output = fread($fh, $count);
      fclose($fh);
    }
  if (strlen($output) < $count)
    {
      $output = '';
      for ($i = 0; $i < $count; $i += 16)
        {
          $random_state =
            md5(microtime() . $random_state);
          $output .=
            pack('H*', md5($random_state));
        }
      $output = substr($output, 0, $count);
    }
  return $output;
}
# </phpass>

function account_gensalt($salt_base64_length=16)
{
  # Note: $salt_base64_length=16 for SHA-512, cf. crypt(3)
  $salt_byte_length = $salt_base64_length * 6 / 8;
  return account_encode64(account_get_random_bytes($salt_byte_length),
                          $salt_byte_length);
}

# Generate unix pw.
function account_genunixpw($plainpw)
{
  return account_encryptpw($plainpw);
}

function account_encryptpw($plainpw)
{
  # rounds=5000 is the 2010 glibc default, possibly we'll upgrade in
  # the future, better have this explicit.
  # Cf. http://www.akkadia.org/drepper/sha-crypt.html
  if (version_compare(PHP_VERSION, '5.3.2', '>='))
    return crypt($plainpw, '$6$rounds=5000$' . account_gensalt(16));
  else
    # The PHP version in Lenny 5.2.6 has troubles with the above
    # (truncated hash at 9 chars).
    return crypt($plainpw, '$6$' . account_gensalt(16));
}

# Return next userid.
function account_nextuid()
{
  db_query("SELECT max(unix_uid) AS maxid FROM user");
  $row = db_fetch_array();
  return ($row[maxid] + 1);
}

# Print out shell selects.
function account_shellselects($current)
{
  $shells = file("/etc/shells");
  for ($i = 0; $i < count($shells); $i++)
    {
      $this_shell = chop($shells[$i]);
      echo "<option ".(($current == $this_shell)?"selected ":"")
           ."value=$this_shell>$this_shell</option>\n";
    }
}

function account_validpw($stored_pw, $plain_pw)
{
  return (crypt($plain_pw,$stored_pw) == $stored_pw);
}
?>
