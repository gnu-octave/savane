<?php
# Configure locale using browser preferences, via gettext and strftime
# 
# <one line to give a brief idea of what this does.>
# 
#  Copyright 2003-2006 (c) Stéphane Urbanovski <s.urbanovski--ac-nancy-metz.fr>
#                          Mathieu Roy <yeupou--gnu.org>
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


# Should we use _ENV["LANG"] also?

// TODO: move to init.php or init-i18n.php - this file doesn't define
// functions are is part of the initialization phase

# Get the user's prefered language from the navigator sended headers :
$navigatorLanguages = explode(",",getenv("HTTP_ACCEPT_LANGUAGE"));

// Set the default language:
if (isset($GLOBALS['sys_default_locale'])) {
  $locale = $GLOBALS['sys_default_locale'];
} else {
  $locale = 'en';
}

# Table of supported languages :
# "lang" => "associated prefered sublanguage"
$supportedLanguages = array(
			    "de"	=> "DE.UTF-8",
			    "de_DE"	=> "DE.UTF-8",
			    "ca"        => "ES.UTF-8",
			    "en"	=> "US.UTF-8",
			    "en_GB"	=> "GB.UTF-8",
                            "es"        => "ES.UTF-8",
			    "fr"	=> "FR.UTF-8",
			    "fr_FR"	=> "FR.UTF-8",
			    "it"	=> "IT.UTF-8",
			    "it_IT"     => "IT.UTF-8",
			    "ja"	=> "JP.UTF-8",
			    "ja_JP"	=> "JP.UTF-8",
			    "ko"	=> "KR.UTF-8",
			    "ko_KR"	=> "KR.UTF-8",
			    "pt"	=> "BR.UTF-8",
			    "pt_BR"     => "BR.UTF-8",
			    "ru"	=> "RU.UTF-8",
			    "ru_RU"     => "RU.UTF-8",
			    "sv"	=> "SE.UTF-8",
			    "sv_SE"     => "SE.UTF-8",
			    );

# Try to find the best supported language from user's navigator preferences :
while (list(, $lng) = each ($navigatorLanguages)) {

  $lng = trim($lng);
  $curlocale = strtolower(substr($lng,0,2));
  if  (substr($lng,2,1) == "-") {
    $sublocale = $curlocale."_".strtoupper(substr($lng,3,2));
    if ( isset($supportedLanguages[$sublocale] )) {
      $locale = $curlocale."_".$supportedLanguages[$sublocale];
      define('SV_LANG', $curlocale."-".$supportedLanguages[$sublocale]);
      break;
    }
  }
  if ( isset($supportedLanguages[$curlocale] )) {
    $locale = $curlocale."_".$supportedLanguages[$curlocale];
    define('SV_LANG', $curlocale."-".$supportedLanguages[$curlocale]);
    break;
  }

}

# Set the locale used by gettext() and strftime() functions :
setlocale(LC_ALL, $locale);
if (!defined('SV_LANG'))
  define('SV_LANG', 'en-US.UTF-8');

# Specify the .mo path; defaults to gettext's compile-time $datadir/locale otherwise
if (!empty($sys_localedir)) {
  bindtextdomain('savane', $sys_localedir);
}
textdomain('savane');


#print "[".$locale.",".setlocale(LC_ALL,0)."]"; //debug

# this provides a custom ngettext() function for PHP versions < 4.2
# it should have the same functionality, but note that there is the
# encoding of the po-file hardcoded to speed things up
if (!function_exists("ngettext")) {
  function ngettext($string1, $string2, $n)
  {
    $locale = setlocale(LC_ALL, 0);
    # strip possible charset extension from the locale (e.g. "de_DE.UTF-8")
    $locale = array_shift(explode(".", $locale));

    # assume a sane default for the return value
    if ($n != 1)
      {
        $msgstr = $string2;
      }
    else
      {
        $msgstr = $string1;
      }

    # FIXME: This should not be hardcoded, but taken from the configure
    # script input somehow.
    $mo_file = "/usr/share/locale/".$locale."/LC_MESSAGES/savane.mo";
    $alternative[] = "/usr/share/locale/".substr($locale, 0, 2)."/LC_MESSAGES/savane.mo";
    $alternative[] = "/usr/local/share/locale/".$locale."/LC_MESSAGES/savane.mo";
    $alternative[] = "/usr/local/share/locale/".substr($locale, 0, 2)."/LC_MESSAGES/savane.mo";

    foreach ($alternative as $location)
      {
        if (is_readable($location))
          {
            $mo_file = $location;
            break;
          }
      }

    # fallback, also used for English
    if (!is_readable($mo_file))
      {
        return $msgstr;
      }

    # open mo file for binary reading
    $mo = fopen($mo_file, "rb");

    # get the number of strings
    fseek($mo, 8);
    $str_count = array_pop(unpack("L", fread($mo, 4)));

    # read in the start of the msgids and msgstrs
    fseek($mo, 12);
    $start = unpack("Loriginal/Ltranslation", fread($mo, 8));

    # read in the table for the lengths and offsets for the msgids
    fseek($mo, $start['original']);
    $msgids = fread($mo, $str_count*8);
    for ($q = 0; $q < $str_count; $q++)
      {
        $original[$q] = unpack("Llength/Loffset", substr($msgids, $q*8, 8));
      }

    # read the msgids in, until the specified msgid is found
    $found = false;
    for ($q = 0; $q < $str_count; $q++)
      {
        fseek($mo, $original[$q]['offset']);
        if ($original[$q]['length'] != 0)
          {
            $msgid = array_pop(unpack("a*", fread($mo, $original[$q]['length'])));
            if ($msgid == $string1."\0".$string2)
              {
                $msgid = $q;
		$found = true;
                break;
              }
          }
      }

    if (!$found)
      {
        return $msgstr;
      }

    # get the length and offset for the corresponding msgstr
    fseek($mo, $start['translation'] + 8*$msgid);
    $translation = unpack("Llength/Loffset", fread($mo, 8));

    # read the msgstr
    fseek($mo, $translation['offset']);
    if ($translation['length'] != 0)
      {
        $msgstr = array_pop(unpack("a*", fread($mo, $translation['length'])));
      }

    # the plural forms rule needs to be hardcoded, because
    # the PHP operator precedence differs a little bit from
    # the one C uses -> the plural rule provided in the po-file
    # does not work for complicated rules (e.g. Russian)
    #
    # for the canonical rules see
    # <http://www.gnu.org/software/gettext/manual/html_chapter/gettext_10.html#SEC150>
    $plural_rule["de_DE"] = ($n != 1);
    $plural_rule["fr_FR"] = ($n > 1);
    $plural_rule["it_IT"] = ($n != 1);
    $plural_rule["ja_JP"] = 0;
    $plural_rule["ko_KR"] = 0;
    $plural_rule["pt_BR"] = ($n > 1);
    $plural_rule["ru_RU"] = ($n%10 == 1 && $n%100 != 11 ? 0 : ($n%10 >= 2 && $n%10 <= 4 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2));
    $plural_rule["sv_SE"] = ($n != 1);

    # if there's no plural rule defined, use a generic one
    if (!isset($plural_rule[$locale]))
      {
        $plural_rule[$locale] = 0;
      }

    # split the msgstr into the different plural forms
    $plural = preg_split("/\\x00/", $msgstr);

    # finally, find the msgstr that's wanted ...
    $msgstr = $plural[$plural_rule[$locale]];

    fclose($mo);

    return $msgstr;
  }
}
