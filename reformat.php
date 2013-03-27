<? 
$script = array_shift($_SERVER['argv']);

// GoogleBookmarkFile - validHtml with tidy
// download at http://www.google.com/bookmarks/bookmarks.html?hl=del
if (count($_SERVER['argv']) > 2 || in_array('--help', $_SERVER['argv'])) {
	echo "usage: php $script [GOOGLE_BOOKMARK_FILE] [OUTPUT_FILE]\n (eg: php reformat.php ./GoogleBookmarks.html ./bookmarks.html)\n";
	exit(1);
}

// ermittle dateipfade
$default_src_file = __DIR__.'/GoogleBookmarks.html';
$default_target_file = __DIR__.'/bookmarks.html';

$src_file = array_shift($_SERVER['argv']);
if (empty($src_file) || !file_exists($src_file) && file_exists($default_src_file)) {
	$src_file = $default_src_file;
}
$target_file = array_shift($_SERVER['argv']);
if (empty($target_file)) {
	$target_file = $default_target_file;
}
	
// Einlesen und HTML korrigieren
$tidy = new tidy();
$html = $tidy->repairfile($src_file);

// parsen
$doc = new DOMDocument();
$doc->loadHTML($html);
$xml = simplexml_import_dom($doc);

// simpleXmlNode to String
function toStr($node) {
	return htmlspecialchars(trim(utf8_decode((string)$node)));
}

// links auslesen
$links = array();
foreach($xml->body->dl->children() as $item) {
	// Tag ist die Überschrift -> speichern
	$tag = toStr($item->h3);
	
	// links zu beschreibung zuordnenen
	foreach($item->dl->children() as $line) {		
      switch (dom_import_simplexml($line)->localName) {
          case 'dt':
					$url = trim((string)$line->a->attributes()->href);
					if (array_key_exists($url, $links))
						$links[$url]['tags'][] = $tag;
					else
						$links[$url] = array (
							'tags' => array(toStr($tag)),
							'title' => toStr($line->a),
							'description' => null
						);
              break;
          case 'dd': // Beschreibung für letzten Link
              $links[$url]['description'] = toStr($line);
              break;
      }
				
	}
}

// ausgabe formatieren
$html = array();
foreach($links as $url => $link) {
	$html[] = sprintf('<a href="%s" tags="%s" desc="%s">%s</a>', $url, implode(',', $link['tags']), $link['description'], $link['title']);
}

// ausgeben
$data = sprintf('<html><body>%s</body></html>', implode("\n", $html));
file_put_contents($target_file, $data);