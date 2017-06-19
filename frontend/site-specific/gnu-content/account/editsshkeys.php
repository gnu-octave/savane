<?php

# Note about SSH keys.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004 Rudy Gevaert
# Copyright (C) 2005, 2006 Sylvain Beucler
# Copyright (C) 2010 Karl Berry
# Copyright (C) 2017 Ineiev <ineiev@gnu.org>
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


print '<h3>'._('SSH Public Keys').'</h3>

';
printf ('<p>'
._('To generate a public key, see the
<a href="%s">Savannah FAQ
entry for SSH</a>.  Please use only RSA keys, not DSA keys.').'</p>

',
"/faq/?group_id=5802&question=User_Account_-_How_do_I_configure_my_SSH_access.txt");

print '<p>'._('What you need to copy/paste looks like this:').'<br />

<nobr><div style="font: x-small, fixed">ssh-rsa AAAAB3NzaC1kc3MAAACBAJrWOtbu064jGhpa8aPEUwuRXSKgKD5Tw4hyCjwSGXYUc3+YBzJD1Gh7mxGn6NaaKC3WrfqdghiGC3apwyz2oyuD/VqLM7BFprGxn+IW/T9Y8Bqny+MbQiccXx3jhENsBHZtzYuxubZc7mDeBS8DnWppsC0VWcTkqAyE8nXP1eOJAAAAFQDsRd3zm11x9D/YHD6DEy6whNwl7wAAAIBJTkTf70LRpPz4YZOFxHA2653WIm3qGjX9d9zodjycfOJQmfPetMdlKfvPl/hmuaOnx/fs3Iz3mEsPgCocB1wSSSyU8kpekcgrhqn4QIwQJgsyLjtbWO6VyPMw1YUKxE3e0pHCfN75+4eijAmiJnM2A7KTxesJZNe3IpBNncuEUgAAAIAL041kJojPdIteuyE+yeVYbZOQZSBMMKUAZMSUdOoxNRM/CbDzh6E6Pc1cRvv2sOITH7svenfttTBjK8hc8EZBAVv3Or1JppSRTsRQq8n9R5Q8qgEY5t2gO4xbli/7wKNq1RYmHQTWf5myXih7lN4qFjfir4BJyPC/uJc3yxuD0Q== user@localhost.localdomain</fixed></nobr></p>

<p>'._('This public key data is commonly located in
<code>~/.ssh/id_rsa.pub</code>.  If questions, check the FAQ.').'</p>

<p>';
printf (_('If your key gets truncated, it is probably because your browser
limits the maximum length of the text fields. Try with <a
href="%s">IceCat</a> (or Firefox), which is known to work.').'</p>',
"//www.gnu.org/software/gnuzilla/");
?>
