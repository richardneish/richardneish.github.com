<?php

/*  settings.php: Plugin file responsible for defining site settings
    Copyright (C) 2002-2009  Hastymail Development group

    This file is part of Hastymail.

    Hastymail is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Hastymail is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Hastymail; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

* $Id:$
*/

$context_btn[] = array(
    'value' => 'Google',
    'id'    => 'google_search',
    'href'  => 'http://www.google.com/search?q=%q'
);
$context_btn[] = array(
    'value' => 'Wikipedia',
    'id'    => 'wiki_search',
    'href'  => 'http://www.wikipedia.org/wiki/%q'
);
$context_btn[] = array(
    'value' => 'Dictionary',
    'id'    => 'dict_search',
    'href'  => 'http://www.onelook.com/?w=%q&ls=a'
);
$context_btn[] = array(
    'value' => 'Thesaurus',
    'id'    => 'thesaurus_search',
    'href'  => 'http://thesaurus.reference.com/browse/%q'
);
?>
