<?php
/**
*
* acp_site [Hungarian]
*
* @package language
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

// Site Settings
$lang = array_merge($lang, array(
	'ACP_SITE_SETTINGS_EXPLAIN'	=> 'Itt az oldallal kapcsolatos beállításokat változtathatsz meg.',

	'TEST'	=> 'Teszt beállítás',

	
));

// Bug tracker
$lang = array_merge($lang, array(
	'ADD_COMPONENT'			=> 'Komponens felvétele',
	'ADD_STATUS'			=> 'Státusz felvétele',
	'ADD_VERSION'			=> 'Verzió felvétele',

	'BUG_TRACKER'					=> 'Hibajelentő',
	'BUG_TRACKER_COMPONENTS'		=> 'Hibajelentő komponensek',
	'BUG_TRACKER_COMPONENTS_EXPLAIN'=> 'Itt a hibajelentőben a projektek komponenseit tudod kezelni.',
	'BUG_TRACKER_PROJECTS'			=> 'Hibajelentő projektek',
	'BUG_TRACKER_PROJECTS_EXPLAIN'	=> 'Itt a hibajelentő projektjeit tudod kezelni: hozzáadni, törölni, mozgatni stb.',
	'BUG_TRACKER_SETTINGS_EXPLAIN'	=> 'Itt a bibajelentő beállításait tudod módosítani.',
	'BUG_TRACKER_STATUSES'			=> 'Hibajelentő státuszok',
	'BUG_TRACKER_STATUSES_EXPLAIN'	=> 'Itt a hibajelentőben a jelentésekre alkalmazott státuszokat tudod módosítani.',
	'BUG_TRACKER_VERSIONS'			=> 'Hibajelentő projekt verziók',
	'BUG_TRACKER_VERSIONS_EXPLAIN'	=> 'Itt a hibajelentőben a projektek verzióit tudod kezelni.',

	'COMPONENT_ADDED'			=> 'A komponens hozzáadásra került.',
	'COMPONENT_DELETED'			=> 'A komponens sikeresen törlésre került. Az ezen komponensű jelentések komponense 1-esre lett állítva.',
	'COMPONENT_EDITED'			=> 'A komponens sikeresen szerkesztésre került.',
	'COMPONENT_EDIT_EXPLAIN'	=> 'Az alábbi űrlap segítségével felvehetsz új komponenseket, illetve módosíthatod a már meglévőket.',
	'COMPONENT_SETTINGS'		=> 'Komponens beállítások',
	'COMPONENT_TITLE'			=> 'Komponens neve',
	'COMPONENT_UPDATED'			=> 'A komponens szerkesztésre került.',
	'CREATE_PROJECT'			=> 'Projekt létrehozása',

	'EDIT_COMPONENT'		=> 'Komponens szerkesztése',
	'EDIT_PROJECT'			=> 'Projekt szerkesztése',
	'EDIT_STATUS'			=> 'Státusz szerkesztése',
	'EDIT_VERSION'			=> 'Verzió szerkesztése',

	'NO_COMPONENT'			=> 'Nincs ilyen komponens.',
	'NO_COMPONENTS'			=> 'Nincsenek komponensek.',
	'NO_PROJECT'			=> 'Nincs ilyen projekt.',
	'NO_PROJECTS'			=> 'Nincsenek projektek.',
	'NO_STATUS'				=> 'Nincs ilyen státusz.',
	'NO_STATUSES'			=> 'Nincsenek státuszok.',
	'NO_VERSION'			=> 'Nincs ilyen verzió.',
	'NO_VERSIONS'			=> 'Nincsenek verziók.',

	'REPORTS'				=> 'Jelentések',

	'PROJECT'							=> 'Projekt',
	'PROJECT_EDIT_EXPLAIN'				=> 'Az alább űrlap segítségével beállíthatod a projekt főbb beállításait.',
	'PROJECT_CREATED'					=> 'A projekt sikeresen létrehozásra került.',
	'PROJECT_DELETE'					=> 'Projekt törlése',
	'PROJECT_DELETE_EXPLAIN'			=> 'Az alábbi űrlap segítségével törölheted a projektet. Jelenleg csak törölni lehet a jelentéseket, azonban a jelentések maradványai (amik a külső táblákban találhatók) meg fognak maradni a többi kiegészítő adattal együtt.',
	'PROJECT_DELETED'					=> 'A projekt sikeresen törlésre került.',
	'PROJECT_DESC'						=> 'Projekt leírása',
	'PROJECT_EXPLAIN'					=> 'A verzió melyik projekthez való.',
	'PROJECT_IDNAME'					=> 'Projekt azonosítóneve',
	'PROJECT_IDNAME_EMPTY'				=> 'A projekt azonosítóneve üres.',
	'PROJECT_IDNAME_EXPLAIN'			=> 'Csak latin karakterekből, illetve számokból, alulvonásból és kötőjelből álló név, mely alapján a projekt az url-ben azonosításra kerül.',
	'PROJECT_IDNAME_TOO_LONG'			=> 'A projekt azonosítóneve túl hosszú.',
	'PROJECT_IDNAME_WRONG_CHARACTERS'	=> 'A projekt azonosítóneve nem megengedett karaktereket tartalmaz.',
	'PROJECT_NAME'						=> 'Projekt neve',
	'PROJECT_NAMED'						=> '<strong>%s</strong> projekt',
	'PROJECT_SETTINGS'					=> 'Projekt beállítások',
	'PROJECT_UPDATED'					=> 'A projekt sikeresen frissítésre került.',
	'PROJECTS'							=> 'Projektek',

	'STATUS_ADDED'					=> 'A státusz hozzáadásra került.',
	'STATUS_CLOSED'					=> 'Lezárt',
	'STATUS_CLOSED_STATUS'			=> 'Státusz állapota',
	'STATUS_CLOSED_STATUS_EXPLAIN'	=> 'Az ilyen státusszal megjelölt jelentések lezártnak tekintendők-e.',
	'STATUS_DELETED'				=> 'A státusz sikeresen törlésre került. Az ezen státuszú jelentések státusza 1-esre lett állítva.',
	'STATUS_EDITED'					=> 'A státusz sikeresen szerkesztésre került.',
	'STATUS_EDIT_EXPLAIN'			=> 'Az alábbi űrlap segítségével felvehetsz új státuszokat, illetve módosíthatod a már meglévőket.',
	'STATUS_OPEN'					=> 'Nyitott',
	'STATUS_SETTINGS'				=> 'Státusz beállítások',
	'STATUS_TITLE'					=> 'Státusz neve',
	'STATUS_UPDATED'				=> 'A státusz szerkesztésre került.',

	'VERSION_ACCEPT_NEW'			=> 'Új jelentések fogadása',
	'VERSION_ACCEPT_NEW_EXPLAIN'	=> 'Lehet-e hibajelentést küldeni a projekt ilyen verziójához.',
	'VERSION_ADDED'					=> 'A verzió hozzáadásra került.',
	'VERSION_DELETED'				=> 'A versió sikeresen törlésre került. Az ezen verziójú jelentések verziója 1-esre lett állítva.',
	'VERSION_EDITED'				=> 'A verzió sikeresen szerkesztésre került.',
	'VERSION_EDIT_EXPLAIN'			=> 'Az alábbi űrlap segítségével felvehetsz új verziókat, illetve módosíthatod a már meglévőket.',
	'VERSION_SETTINGS'				=> 'Verzió beállítások',
	'VERSION_TITLE'					=> 'Verzió neve',
	'VERSION_UPDATED'				=> 'A verzió szerkesztésre került.',
));

// Tags
$lang = array_merge($lang, array(
	'ADD_TAG'				=> 'Címke felvétele',
	'ADD_TAGCAT'			=> 'Címke kategória hozzáadása',

	'EDIT_TAG'				=> 'Címke szerkesztése',
	'EDIT_TAGCAT'			=> 'Címke kategória szerkesztése',

	'KB'					=> 'Útmutatók',

	'MODS'					=> 'MOD-ok',

	'NO_TAG'				=> 'Nincs ilyen címke.',
	'NO_TAGCAT'				=> 'Nincs ilyen címke kategória.',
	'NO_TAGCATS'			=> 'Nincsenek címke kategóriák.',
	'NO_TAGS'				=> 'Nincsenek címkék.',

	'TAG_ADDED'						=> 'A címke sikeresen felvételre került.',
	'TAG_EDIT_EXPLAIN'				=> 'Az alábbi űrlap segítségével egy új címkét vehetsz fel, vagy egy már meglévőt szerkeszthetsz.',
	'TAG_EDITED'					=> 'A címke sikeresen szerkesztésre került.',
	'TAG_DELETED'					=> 'A címke sikeresen törlésre került.',
	'TAG_IDNAME'					=> 'Címke azonosítóneve',
	'TAG_IDNAME_ALREADY_EXISTS'		=> 'Már létezik címke ilyen azonosítónévvel.',
	'TAG_IDNAME_EMPTY'				=> 'A címke azonosítóneve üres.',
	'TAG_IDNAME_EXPLAIN'			=> 'Csak latin karakterekből, illetve számokból, plusz jelből, alulvonásból és kötőjelből álló név, mely alapján a címke az url-ekben azonosításra kerül.',
	'TAG_IDNAME_TOO_LONG'			=> 'A címke azonosítóneve túl hosszú.',
	'TAG_IDNAME_WRONG_CHARACTERS'	=> 'A címke azonosítóneve nem megengedett karaktereket tartalmaz.',
	'TAG_SETTINGS'					=> 'Címke beállítások',
	'TAG_TITLE'						=> 'Címke neve',
	'TAG_USED'						=> 'Hozzárendelt elemek',
	'TAGCAT'						=> 'Címke kategória',
	'TAGCAT_ADDED'					=> 'A címke kategória sikeresen hozzáadásra került.',
	'TAGCAT_EDIT_EXPLAIN'			=> 'Az alábbi űrlap segítségével egy új címke kategóriát adhatsz hozzá, vagy egy már meglévőt szerkeszthetsz.',
	'TAGCAT_EDITED'					=> 'A címke kategória sikeresen szerkesztésre került.',
	'TAGCAT_DELETED'				=> 'A címke kategória sikeresen törlésre került.',
	'TAGCAT_HAS_CHILDREN'			=> 'A címke kategória még tartalmaz címkéket, ezért nem tudod törölni.',
	'TAGCAT_IDNAME'					=> 'Címke kategória azonosítóneve',
	'TAGCAT_IDNAME_ALREADY_EXISTS'	=> 'Már létezik címke kategória ilyen azonosítónévvel.',
	'TAGCAT_IDNAME_EMPTY'			=> 'A címke kategória azonosítóneve üres.',
	'TAGCAT_IDNAME_EXPLAIN'			=> 'Csak latin karakterekből, illetve számokból, plusz jelből, alulvonásból és kötőjelből álló név, mely alapján a címke kategória az url-ekben azonosításra kerül.',
	'TAGCAT_IDNAME_TOO_LONG'		=> 'A címke kategória azonosítóneve túl hosszú.',
	'TAGCAT_IDNAME_WRONG_CHARACTERS'=> 'A címke kategória azonosítóneve nem megengedett karaktereket tartalmaz.',
	'TAGCAT_NAMED'					=> '<strong>%1$s</strong> címke kategória (%2$s)',
	'TAGCAT_MODULE'					=> 'Modul',
	'TAGCAT_MODULE_EXPLAIN'			=> 'A címke kategória melyik oldalmodulhoz tartozik.',
	'TAGCAT_MODULE_WRONG'			=> 'A megadott modul nem létezik.',
	'TAGCAT_SETTINGS'				=> 'Címke kategória beállítások',
	'TAGCAT_TITLE'					=> 'Címke kategória neve',
	'TAGCAT_TITLE_EMPTY'			=> 'A címke kategória neve üres.',
	'TAGCAT_TITLE'					=> 'Címke kategória neve',
	'TAGS'							=> 'Címkék',
	'TAGS_CATS'						=> 'Címke kategóriák',
	'TAGS_CATS_EXPLAIN'				=> 'Itt a címkék kategóriáit rendezheted. Az oldalon több féle tartalomtípushoz is rendelhetők címkék, ezek a címkék mind egy adatbázisban vannak tárolva, így mind itt változtathatók meg.',
	'TAGS_EXPLAIN'					=> 'Itt a különböző címke kategóriák alá tartozó címkéket tudod kezelni.',
));

// Pages
$lang = array_merge($lang, array(
	'ACP_PAGES_EXPLAIN'	=> 'Itt oldalakat (tulajdonképp lapokat) állíthatsz be. Minden tartalomhoz tartozik egy PHP fájl, melyet az <code>includes/pages/</code> könyvtárba kell rakni (hacsak nincs valami különleges körülmény). További információért lásd a forráskódot. :P',
	'ADD_PAGE'			=> 'Lap hozzáadása',

	'EDIT_PAGE'			=> 'Lap szerkesztése',
	'EDITOR_HEIGHT'		=> 'Szerkesztő magassága',

	'NO_PAGE'				=> 'Nincs ilyen lap.',
	'NO_PAGES'				=> 'Nincsenek lapok.',
	'NO_PAGE_FILE'			=> 'A megadott állomány nem létezik.',
	'NO_PAGE_FILE_FORMAT'	=> 'Az állomány elérési útvonal nem tartalmazhat <code>..</code>-ot.',
	'NO_PAGE_URL_EXISTS'	=> 'A megadott URL-lel már létezik oldal.',
	'NO_PAGE_URL_FORMAT'	=> 'A megadott URL formátuma nem megfelelő, csak latin betűket, számokat, pontot, kötőjelet és alulvonást tartalmazhat.',

	'PAGE_ADDED'			=> 'A lap sikeresen hozzáadásra került.',
	'PAGE_COMMENTS'			=> 'Megjegyzések',
	'PAGE_COMMENTS_EXPLAIN'	=> 'Ez a mező csak és kizárólag ezen az oldalon jelenik meg. Ide megjegyzéseket írhatsz, átalakítás alatt lévő szövegrészeket tárolhatsz benne stb.',
	'PAGE_CONTENT'			=> 'Oldal tartalma',
	'PAGE_DELETED'			=> 'A lap sikeresen törlésre került.',
	'PAGE_DETAILS'			=> 'Lap adatok',
	'PAGE_EDITED'			=> 'A lap sikeresen szerkesztésre került.',
	'PAGE_EDIT_EXPLAIN'		=> 'Itt lapokat tudsz felvenni, illetve módosítani. Minden egyes oldalhoz egy PHP fájl van rendelve, melynek átadásra kerülnek az itt megadott értékek. Egyszerű szöveges oldal létrehozásához PHP fájlnak add meg az <code>includes/pages/text.php</code>-t. A megjegyzések mező csak itt látható, máshol nem jelenik meg a tartalma, egyfajta <i>homokozónak</i> használható.',
	'PAGE_FILE'				=> 'Állomány',
	'PAGE_FILE_EXPLAIN'		=> 'Az oldal megjelenítéséért felelős PHP állomány. Az állományokat az <code>includes/pages/</code> könyvtárban ajánlott tartani. Sima szöveges tartalomhoz add meg az <code>includes/pages/text.php</code>-t.',
	'PAGE_SECTION'			=> 'Részleg',
	'PAGE_SECTION_EXPLAIN'	=> 'Az oldal részlegének párbetűs rövidítése, létező CSS osztálynak kell lennie. Ez határozza meg, hogy a menüben éppen melyik elem lesz az aktuális, illetve az oldal alap háttérszínét is befolyásolhatja.',
	'PAGE_TITLE'			=> 'Oldal neve',
	'PAGE_URL'				=> 'URL',
	'PAGE_URL_EXPLAIN'		=> 'Erre és csak erre az URL-re fog megjelenni az oldal (azaz aloldalakat nem fogad el, azokat külön hozzá kell adni).',
));
?>
