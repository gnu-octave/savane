<?php
# Check password strength.
#
# Copyright (C) 2000-2002, 2010, 2013, 2016 Solar Designer
# Copyright (C) 2017, 2022 Ineiev
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
#
# From:
# http://www.openwall.com/articles/PHP-Users-Passwords#enforcing-password-policy
#
# No copyright to the source code snippets found in this article and
# to the sample programs included in the accompanying archive is
# claimed, and they're hereby placed in the public domain. Please feel
# free to reuse them in your programs.
#
# Author: Alexander Peslyak
# Author: Ineiev (i18n).

# The original pwqcheck is not internationalized. Strings to localize
# are taken from passwdqc_check.c (the 1.3.1 release); they are copyrighted
# by Solar Designer.
# The license for passwdqc_check.c is:
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted.
#
# THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
# ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
# IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
# ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
# FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
# DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
# OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
# HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
# OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
# SUCH DAMAGE.

$pwqcheck_messages_for_i18n = [
  _("Bad passphrase (is the same as the old one)"),
  _("Bad passphrase (is based on the old one)"),
  _("Bad passphrase (too short)"),
  _("Bad passphrase (too long)"),
  _("Bad passphrase (not enough different characters or classes for this "
    . "length)"),
  _("Bad passphrase (not enough different characters or classes)"),
  _("Bad passphrase (based on personal login information)"),
  _("Bad passphrase (based on a dictionary word and not a passphrase)"),
  _("Bad passphrase (based on a common sequence of characters and not "
    . "a passphrase)")
];

function pwqcheck ($newpass, $oldpass = '', $user = '', $aux = '', $args = '')
{
  # pwqcheck(1) itself returns the same message on internal error.
  $retval = _('Bad passphrase (check failed)');

  $descriptorspec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w']];
  # Leave stderr (fd 2) pointing to where it is, likely to error_log.

  # Replace characters that would violate the protocol.
  $newpass = strtr ($newpass, "\n", '.');
  $oldpass = strtr ($oldpass, "\n", '.');
  $user = strtr ($user, "\n:", '..');

  # Trigger a "too short" rather than "is the same" message in this special
  # case.
  if (!$newpass && !$oldpass)
    $oldpass = '.';

  if ($args)
    $args = " $args";
  if (!$user)
    $args = " -2 $args"; # passwdqc 1.2.0+

  $command = 'exec '; # No need to keep the shell process around on Unix.
  $command .= "pwqcheck$args";
  if (!($process = @proc_open ($command, $descriptorspec, $pipes)))
    return $retval;

  $err = 0;
  fwrite ($pipes[0], "$newpass\n$oldpass\n") || $err = 1;
  if ($user)
    fwrite ($pipes[0], "$user::::$aux:/:\n") || $err = 1;
  fclose ($pipes[0]) || $err = 1;
  ($output = stream_get_contents ($pipes[1])) || $err = 1;
  fclose ($pipes[1]);

  $status = proc_close ($process);

  # There must be a linefeed character at the end.  Remove it.
  if (substr ($output, -1) === "\n")
    $output = substr($output, 0, -1);
  else
    $err = 1;

  if ($err === 0 && ($status === 0 || $output !== 'OK'))
    $retval = $output;

  if ($retval == 'OK')
    return 0;
  return gettext ($retval);
}
?>
