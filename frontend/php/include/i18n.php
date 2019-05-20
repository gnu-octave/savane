<?php
# Configure locale using browser preferences, via gettext and strftime
#
# Copyright (C) 2016 Karl Berry (disable languages)
# Copyright (C) 2003-2006 Stéphane Urbanovski <s.urbanovski--ac-nancy-metz.fr>
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2018, 2019 Ineiev
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

# Table of supported languages :
# "language variant" => "associated preferred locale"
# 11jun2016 karl disabled all languages since translations are incomplete
# (and the only way to select them is with the inconvenient
# Accept-Language: browser header). https://savannah.gnu.org/support/?108827
$locale_list = array();

# Locale names offered for selection in /i18n.php.
$locale_names = array();

# Add language to arrays:
# $code - language code
# $locale - locale to use
# $name - label for the item in select box; when empty, the language
# isn't offered for selection on /i18n.php.
function register_language ($code, $locale, $name = "")
{
  global $locale_list, $locale_names;
  $locale_list[$code] = $locale.".UTF-8";
  if ($name !== "")
    $locale_names[$code] = $name;
}

#register_language ("ca", "ca_ES", "català");
#register_language ("de", "de_DE", "Deutsch");
register_language ("en", "en_US", "English");
#register_language ("en-gb", "en_GB");
register_language ("es", "es_ES", "español");
register_language ("fr", "fr_FR", "français");
#register_language ("fr-fr", "fr_FR");
#register_language ("it", "it_IT", "italiano");
#register_language ("ja", "ja_JP", "日本語");
#register_language ("ja-jp", "ja_JP");
#register_language ("ko", "ko_KR", "한국어");
#register_language ("ko-kr", "ko_KR");
#register_language ("pt", "pt_BR", "português do Brasil");
#register_language ("pt-br", "pt_BR");
register_language ("ru", "ru_RU", "русский");
#register_language ("sv", "sv_SE", "svenska");
#register_language ("sv-se", "sv_SE");
#register_language ("zh", "zh_CN", "简体中文");
#register_language ("zh-cn", "zh_CN");

# Get user's preferred languages from UA headers.
$accept_language = strtolower (str_replace (array (' ', '	'), '',
                                            getenv("HTTP_ACCEPT_LANGUAGE")));
$browser_preferences = explode(",", $accept_language);

# Set the default locale.
$quality = 0;
$best_lang = "en";

if (isset($GLOBALS['sys_default_locale']))
  $best_lang = $GLOBALS['sys_default_locale'];

# Find the best language available.
while (list(, $lng) = each ($browser_preferences))
  {
  # Parse language and quality factor.
    $q = 1;
    $arr = explode (';', $lng);
    if (isset ($arr[1]))
      {
        $lng = $arr[0];
        $arr[1] = $arr[1];
        if (substr($arr[1], 0, 2) === 'q=')
          $q = substr($arr[1], 2);
        else continue; # The second half doesn't define quality; skip the item.
        if ($q > 1 || $q <= 0)
          continue; # Unusable quality value.
      }
    $cur_lang = $lng;

  # Check language code.
    $lang_len = strpos ($cur_lang, '-');
    if ($lang_len === FALSE)
      $lang_len = strlen ($cur_lang);
    if ($lang_len < 2)
      continue; # Language code must be at least 2 characters long.

    if (!isset($locale_list[$cur_lang] ))
      continue; # No such locale; skip the item.

    if ($q <= $quality)
      continue;

  # Best item available so far: select.
    $quality = $q;
    $best_lang = $cur_lang;
  } # while (list(, $lng) = each ($browser_preferences))

if (isset($_COOKIE['LANGUAGE']) && isset($locale_list[$_COOKIE['LANGUAGE']]))
  $best_lang = $_COOKIE['LANGUAGE'];

$locale = $locale_list[$best_lang];
define('SV_LANG', $best_lang);

setlocale(LC_ALL, $locale);

# Specify the .mo path; defaults to gettext's compile-time $datadir/locale otherwise
if (!empty($sys_localedir))
  bindtextdomain('savane', $sys_localedir);
textdomain('savane');
