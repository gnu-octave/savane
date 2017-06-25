<?php
# Configure locale using browser preferences, via gettext and strftime
#
# Copyright (C) 2016 Karl Berry (disable languages)
# Copyright (C) 2003-2006 Stéphane Urbanovski <s.urbanovski--ac-nancy-metz.fr>
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017 Ineiev
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

# TODO: move to init.php or init-i18n.php - this file doesn't define
# functions and is part of the initialization phase

# Table of supported languages :
# "language variant" => "associated preferred locale"
# 11jun16 karl disabled all languages since translations are incomplete
# (and the only way to select them is with the inconvenient
# Accept-Language: browser header). https://savannah.gnu.org/support/?108827
$locale_list = array(
                            #"de"       => "de_DE.UTF-8",
                            #"de-de"    => "de_DE.UTF-8",
                            #"ca"        => "ca_ES.UTF-8",
                            "en"        => "en_US.UTF-8",
                            #"en-gb"    => "en_GB.UTF-8",
                            #"es"        => "es_ES.UTF-8",
                            #"fr"       => "fr_FR.UTF-8",
                            #"fr-fr"    => "fr_FR.UTF-8",
                            #"it"       => "it_IT.UTF-8",
                            #"it-it"     => "it_IT.UTF-8",
                            #"ja"       => "ja_JP.UTF-8",
                            #"ja-jp"    => "ja_JP.UTF-8",
                            #"ko"       => "ko_KR.UTF-8",
                            #"ko-kr"    => "ko_KR.UTF-8",
                            #"pt"       => "pt_BR.UTF-8",
                            #"pt-br"     => "pt_BR.UTF-8",
                            #"ru"        => "ru_RU.UTF-8",
                            #"ru-ru"     => "ru_RU.UTF-8",
                            #"sv"       => "sv_SE.UTF-8",
                            #"sv-se"     => "sv_SE.UTF-8",
                            );

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
while (list(, $lng) = each ($browser_preferences)) {
  # Parse language and quality factor.
  $q = 1;
  $arr = explode (';', $lng);
  if (isset ($arr[1])) {
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

$locale = $locale_list[$best_lang];
define('SV_LANG', str_replace ('_', '-', $locale));

setlocale(LC_ALL, $locale);

# Specify the .mo path; defaults to gettext's compile-time $datadir/locale otherwise
if (!empty($sys_localedir))
  bindtextdomain('savane', $sys_localedir);
textdomain('savane');
