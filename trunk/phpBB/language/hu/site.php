<?php
/** 
*
* site [Hungarian]
*
* @package language
* @version $Id$
* @copyright (c) 2007 phpbb.hu 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

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

// Common stuff
$lang = array_merge($lang, array(
	'SITE_HOME'				=> 'Kezdőlap',

));

// Additional "MODs"
$lang = array_merge($lang, array(
	'NOTIFICATION_MESSAGE'	=> 'Értesítő üzenet',

	'SEND_NOTIFICATION'		=> 'Értesítő küldése',

));

// Bug tracker
$lang = array_merge($lang, array(
	'ADD_COMMENT'			=> 'Hozzászólás jelentéshez',
	'ADD_REPORT'			=> 'Hiba jelentése',
	'ADD_REPORT_EXPLAIN'	=> 'Itt hibát tudsz jelenteni. Kérünk, légy szép és jó, valamint legfőképp türelmes (míg elkészül a hibajelentő :D) stb.',
	'ALL_BUGS'				=> 'Összes hiba',
	'ASSIGN'				=> 'Hozzárendelés',
	'ASSIGN_TO'				=> 'Hozzárendelés',
	'ASSIGNED_TO'			=> 'Hozzárendelve',
	'ASSIGNED_REPORTS'		=> 'Hozzámrendelt jelentések',

	'BUG_DESCRIPTION'			=> 'Hiba leírás',
	'BUG_DESCRIPTION_EXPLAIN'	=> 'Részletes leírás a hibáról, hol mi miért nem jó. Kérünk, adj meg minnél több adatot.',
	'BUG_LONG_INFO'				=> 'Jelentette %1$s %2$s-kor.',
	'BUG_LONG_INFO_REPLIED'		=> 'Jelentette %1$s %2$s-kor, utoljára hozzászólt %3$s %4$s-kor.',
	'BUG_NO'					=> 'Hiba #%1$d',
	'BUG_TITLE'					=> 'Hiba megnevezése',
	'BUG_TITLE_EXPLAIN'			=> 'Egy rövid, a hibát jól leíró cím a jelentésednek.',
	'BUG_TRACKER'				=> 'Hibajelentő',
	'BUG_TRACKER_EXPLAIN'		=> 'Válaszd ki a projektet, amit meg szeretnél nézni.',

	'CANNOT_REASSIGN_SAME'	=> 'Nem rendelheted hozzá a hibajelentéshez újra ugyanazt az embert.',
	'CHANGE_STATUS'			=> 'Állapot megváltoztatása',
	'CLOSED_BUGS'			=> 'Lezárt hibák',
	'CLOSED_REPORTS'		=> 'Lezárt jelentések',
	'COMMENT_ADDED'			=> 'A hozzászólás sikeresen elküldésre került.<br /><br />%sA hozzászólás megtekintéséhez kattints ide.%s',
	'COMMENTS'				=> 'Hozzászólások',
	'COMMENT_SUBJECT'		=> 'Hozzászólás témája',
	'COMPONENT'				=> 'Komponens',

	'DESCRIPTION'			=> 'Leírás',

	'EDIT_REPORT'			=> 'Jelentés szerkesztése',

	'FILTER'				=> 'Szűrés',

	'MY_ASSIGNED_REPORTS'	=> 'Hozzámrendelt jelentések',
	'MY_REPORTS'			=> 'Saját jelentések',

	'NEW_REPORTS'			=> 'Új jelentések',
	'NONE_SELECTED'			=> '(válassz)',
	'NO_CLOSED_REPORTS'		=> 'Nincs lezárt jelentés.',
	'NO_COMMENTS'			=> 'Még nem küldtek hozzászólást.',
	'NO_COMPONENT'			=> 'Nem választottál ki komponenst.',
	'NO_NEW_REPORTS'		=> 'Nincs frissen beküldött jelentés.',
	'NO_PROJECT'			=> 'Nincs ilyen projekt.',
	'NO_PROJECTS'			=> 'Nincsenek projektek.',
	'NO_REPORT'				=> 'Nincs ilyen jelentés.',
	'NO_REPORTS'			=> 'Nincsenek ilyen jelentések.',
	'NO_REPORT_DESCRIPTION'	=> 'Nem adtad meg a hiba leírását.',
	'NO_STATUS'				=> 'Nincs ilyen állapot.',
	'NO_VERSION'			=> 'Nem választottál ki verziót.',

	'OPEN_BUGS'				=> 'Nyitott hibák',
	'OWN_REPORTS'			=> 'Saját jelentéseim',

	'POSTED_INFO'			=> 'Küldte %1$s %2$s %3$s-kor.',
	'PREVIEW_COMMENT'		=> 'Hozzászólás előnézete',
	'PREVIEW_REPORT'		=> 'Jelentés előnézete',
	'PROJECT'				=> 'Projekt',
	'PROJECT_INFORMATION'	=> 'Projekt információ',
	'PROJECT_NAME'			=> 'Projekt neve',
	'PROJECT_VERSION'		=> 'Projekt verzió',

	'REPORT'				=> 'Jelentés',
	'REPORTED_BY'			=> 'Jelentette',
	'REPORTED_ON'			=> 'Beküldve',
	'REPORT_ADDED'			=> 'A jelentés sikeresen felvételre került.<br /><br />%sA jelentés megtekintéséhez kattints ide.%s',
	'REPORT_DETAILS'		=> 'Jelentés adatok',
	'REPORT_ID'				=> 'Jelentés azonosító',
	'REPORT_UPDATED'		=> 'A jelentés sikeresen szerkesztésre került.<br /><br />%sA jelentés megtekintéséhez kattints ide.%s',
	'RETURN_REPORT'			=> 'Vissza a jelentéshez',

	'STATUS'				=> 'Állapot',
	'SUBMIT_COMMENT'		=> 'Hozzászólás elküldése',
	'SUBMIT_COMMENT_CONFIRM'=> 'Kérünk, győződj meg róla, hogy minden szükséges információt megadtál.',
	'SUBMIT_REPORT'			=> 'Jelentés elküldése',
	'SUBMIT_REPORT_CONFIRM'	=> 'A jelentésed kész az elküldésre? Kérünk, győződj meg róla, hogy elegendő információt adtál meg, ami alapján gyorsan intézkedni tudunk, anélkül, hogy újra kapcsolatba kelljen lépnünk veled. Minnél több információt adsz meg, annál hamarabb tudjuk javítani a hibát.',
	'SUBSCRIBE'				=> 'Feliratkozás',
	'SUBSCRIBE_REPORT'		=> 'Feliratkozás a jelentésre',

	'TOO_LONG_REPORT_TITLE'		=> 'A hiba megnevezése túl hosszú.',
	'TOO_SHORT_REPORT_TITLE'	=> 'A hiba megnevezése túl rövid.',

	'UNASSIGNED'			=> '(nincs)',
	'UNSUBSCRIBE_REPORT'	=> 'Leiratkozás a jelentésről',

	'VERSION'				=> 'Verzió',
	'VIEW_REPORT'			=> '1 jelentés',
	'VIEW_REPORTS'			=> '%1$d jelentés',
));


// Knowledge base
$lang = array_merge($lang, array(
	'ADD_ARTICLE'			=> 'Útmutató beküldése',
	'ADD_ARTICLE_EXPLAIN'	=> 'Itt útmutatót tusz írni, mely meg fog jelenni az oldalunkon. Az útmutatók gyakran elöjövő problémákkal foglalkoznak, így a felhasználók egyik fontos támaszát képzik. Kérünk, légy a lehető legodafigyelőbb (részletesebb stb.), törekedj a legjobb minőségre. Az útmutatók beküldés után lektoráláson esnek át, addig nem lesznek láthatók. Köszönjük a munkád!',
	'ANY'					=> 'Mindegy',
	'ARTICLE'				=> 'Útmutató',
	'ARTICLE_ADDED'			=> 'Az útmutató sikeresen eltárolásra került.<br /><br />%sKattints ide, hogy megtekintsd az útmutatót.%s',
	'ARTICLE_ADDED_MOD'		=> 'Az útmutató sikeresen eltárolásra került, azonban mielőtt mindenki által megtekinthető lenne, még át kell esnie egy lektoráláson.<br /><br />%sKattints ide, hogy visszatérj az útmutatókhoz.%s',
	'ARTICLE_APPROVE'		=> 'Űtmutató jóváhagyása',
	'ARTICLE_CONTENT'		=> 'Útmutató tartalma',
	'ARTICLE_DELETED'		=> 'Az útmutató törlésre került.<br /><br />%sKattints ide, hogy visszatérj az útmutatókhoz.%s',
	'ARTICLE_DESCRIPTION'	=> 'Útmutató leírása',
	'ARTICLE_DESCRIPTION_EXPLAIN'	=> 'Rövid ismertető leírás az útmutatóról, miről és kinek szól stb.',
	'ARTICLE_DISAPPROVE'	=> 'Útmutató elutasítása',
	'ARTICLE_ID'			=> 'Azonosító',
	'ARTICLE_NAME'			=> 'Útmutató azonosítóneve',
	'ARTICLE_NAME_EXPLAIN'	=> 'Az útmutató címének egy számítógépek által jobban kezelhető változata. Nem tartalmazhat csak angol betűket, számokat, kötőjelet és alulvonást (pl. szóközt sem).',
	'ARTICLE_TITLE'			=> 'Útmutató címe',
	'ARTICLE_UPDATED'		=> 'Az útmutató sikeresen frissítésre került.<br /><br />%sKattints ide, hogy megtekintsd az útmutatót.%s',
	'ARTICLE_UPDATED_MOD'	=> 'Az útmutató sikeresen frissítésre került, azonban mielőtt mindenki által megtekinthető lenne, egy moderátornak még jóvá kell hagynia.<br /><br />%sKattints ide, hogy visszatérj az útmutatókhoz.%s',

	'EDIT'					=> 'Szerkesztés',
	'EDIT_ARTICLE'			=> 'Útmutató szerkesztése',

	'DELETE_ARTICLE'		=> 'Útmutató törlése',
	'DELETE_ARTICLE_CONFIRM'=> 'Biztosan törölni akarod az útmutatót?',

	'FILTER'				=> 'Szűrés',

	'KB'					=> 'Útmutatók',
	'KB_EXPLAIN'			=> 'Az útmutatók részleg hasznos leírásokat tartalmaz a phpBB telepítéséről, üzemeltetéséről és az eközben felmerülő kérdésekről.<br />Alább megtekintheted a legfrissebb útmutatókat, vagy választhatsz egy címkét, hogy megtekinthesd az ilyen címkével megjelölt útmutatók listáját.',

	'NO_ARTICLE'			=> 'Nincs ilyen útmutató.',
	'NO_ARTICLES'			=> 'Nincs ilyen útmutató.',
	'NO_ARTICLE_CONTENT'	=> 'Üresen hagytad az útmutató tartalmát.',
	'NO_ARTICLE_DESC'		=> 'Nem adtál meg leírást az útmutatónak.',
	'NO_ARTICLE_DESC_LONG'	=> 'A megadott leírás túl hosszú.',
	'NO_ARTICLE_NAME'		=> 'Nem adtad meg az útmutató azonosítónevét.',
	'NO_ARTICLE_NAME_EXISTS'=> 'Már létezik egy ilyen azonosítónevű útmutató, kérünk adj meg mást.',
	'NO_ARTICLE_NAME_FORMAT'=> 'Az útmutató azonosítóneve nem megengedett karaktereket tartalmaz.',
	'NO_ARTICLE_TITLE'		=> 'Nem adtad meg az útmutató címét.',
	'NO_TAG'				=> 'Nincs ilyen címke.',
	'NO_TAG_VALID'			=> 'Érvénytelen címkét adtál meg!',

	'PREVIEW_ARTICLE'		=> 'Útmutató előnézete',

	'RECENT_ARTICLES'		=> 'Legújabb útmutatók',

	'SEARCH_KB'				=> 'Keresés az útmutatók között…',
	'SUBMIT_ARTICLE'		=> 'Útmutató elküldése',
	'SUBMIT_ARTICLE_CONFIRM'=> 'Biztosan kész az útmutatód? Nem tartalmaz elírást vagy helyesírási hibát, illetve megfelelően tagolva van?',

	'TAGS'					=> 'Címkék',

	'VIEW_ARTICLE'			=> 'Útmutató: %s',
));

// Pages
$lang = array_merge($lang, array(
	'NOT_FOUND'			=> '404-es hiba lépett fel, a keresett oldal nem található. Próbálj meg választani egy oldalt a menüből.',
));

// MODs
$lang = array_merge($lang, array(
	'ACTIONS'			=> 'Műveletek',
	'ADD_MOD'			=> 'Új MOD beküldése',
	'ADD_MOD_EXPLAIN'	=> 'Itt egy új modifikációt (MOD-ot) tudsz beküldeni a MOD adatbázisunkba vagy egy már meglévőt frissíthetsz. A MOD adatbázisunkba csak olyan MOD-ok kerülhetnek be, melyek megtalálhatóak a phpbb.com MOD adatbázisában és tartalmaznak magyar fordítást. Ez utóbbi feltétel két módon teljesülhet, vagy a MOD alapból tartalmaz magyar fordítást (pl. kapcsolatba léptél a készítővel, és közvetlenül neki küldted el a fordításod), vagy ezen az oldalon feltölthetsz egy fordítást. Ennek a fordításnak hasonló formátumban kell lennie az eredeti MODX csomaghoz, viszont csak a nyelvi dolgokkal kapcsolatos részeket kell tartalmaznia. További információ később, esetleg másik oldalon.',
	'APPROVE_MOD'		=> 'MOD jóváhagyása',

	'COM_MOD_DB_PAGE'	=> 'PhpBB.com MOD adatbázisának megfelelő oldala',

	'DELETE_MOD'		=> 'MOD törlése',
	'DELETE_MOD_CONFIRM'=> 'Biztosan törölni akarod a MOD-ot?',
	'DOWNLOAD_MOD'		=> '%1$s %2$s MOD letöltése',

	'EDIT_MOD'			=> 'MOD szerkesztése',

	'LAST_TEN_MODS'		=> 'Utolsó tíz MOD',

	'MD5'							=> 'MD5',
	'MODS'							=> 'MOD-ok',
	'MODS_DB'						=> 'MOD adatbázis',
	'MODS_DB_EXPLAIN'				=> 'Ez itt a MOD adatbázis csupa magyra lefordított MOD-dal, mely automatikusan frissül és melynek tartalmához te is hozzájárulhatsz stb. stb.!',
	'MODS_DB_INDEX'					=> 'MOD-ok kezdőlap',
	'MOD_ADDED'						=> 'A MOD sikeresen felvételre került.<br /><br />%sKattints ide, hogy megtekintsd a modifikációt.%s',
	'MOD_ADDED_MOD'					=> 'A MOD sikeresen felvételre került, azonban mielőtt mindenki által letölhető lenne, egy moderátornak még át kell tekintenie.<br /><br />%sKattints ide, hogy visszatérj a MOD-okhoz.%s',
	'MOD_COM_URL'					=> 'PhpBB.com MOD adatbázis link',
	'MOD_COM_URL_EXPLAIN'			=> 'Link a MOD oldalához a phpBB.com MOD adatbázisában.',
	'MOD_DELETED'					=> 'A MOD sikeresen törlésre került.<br /><br />%sKattints ide, hogy visszatérj a MOD adatbázis kezdőlapjára.%s',
	'MOD_DESCRIPTION'				=> 'MOD leírása',
	'MOD_DESCRIPTION_EXPLAIN'		=> 'Rövid leírás a MOD-ról, milyen funkciókat tartalmaz, mire kell ügyelni stb. (vedd alapul az angol leírást).',
	'MOD_EN_TITLE'					=> 'MOD eredeti angol neve',
	'MOD_LOCALISATION_PACK'			=> 'Fordítás csomag',
	'MOD_LOCALISATION_PACK_EXPLAIN'	=> 'Ha a MOD nem tartalmaz alapból magyar fordítást, itt tölthetsz fel hozzá. A fordítás csomagnak ugyanolyan formátumban kell lennie, mint az eredeti MODX csomagnak (azonos könyvtárszerkezet, állománynevek stb.), de csak a nyelvi dolgokkal kapcsolatos állományokat kell tartalmaznia. A fordítás és az eredeti MOD összevonásra kerül, és egyben lesz letölthető.',
	'MOD_TITLE'						=> 'MOD neve',
	'MOD_TITLE_EXPLAIN'				=> 'A modifikáció magyar neve.',
	'MOD_UPDATED'					=> 'A MOD sikeresen frissítésre került.<br /><br />%sKattints ide, hogy megtekintsd a modifikációt.%s',
	'MOD_UPDATED_MOD'				=> 'A MOD sikeresen frissítésre került, azonban mielőtt mindenki által letölthető lenne, egy moderátornak még jóvá kell hagynia.<br /><br />%sKattints ide, hogy visszatérj a modofikációhoz.%s',
	//'MOD_X'						=> '%s MOD',

	'NO_COM_URL_FORMAT'				=> 'A MOD adatbázis link nem megfelelő formátumú.',
	'NO_MOD'						=> 'Nincs ilyen MOD.',
	'NO_MODS'						=> 'Nincsenek ilyen MOD-ok.',
	'NO_MOD_DESC'					=> 'Nem adtál meg leírást a MOD-hoz.',
	'NO_MOD_LOC_PACK_FILETYPE'		=> 'Nem megfelelő típusú (nem .zip) állományt töltöttél fel.',
	'NO_MOD_MISSING_LANGUAGE_FILE'	=> 'Hiányzó nyelvi állomány: %s',
	'NO_MOD_MISSING_STYLE_IMAGE'	=> 'Hiányzó lokalizálandó gomb/ikon: %s',
	'NO_MOD_MOD_EXISTS'				=> 'Ez a MOD már benne van a MOD adatbázisunkban.',
	'NO_MOD_MOD_NOT_EXISTS'			=> 'A phpbb.com MOD adatbázisában nem létezik ilyen azonosítójú MOD.',
	'NO_MOD_SYNTAX_ERROR'			=> 'A nyelvi állomány PHP szintatikailag nem megfelelő: %s',
	'NO_MOD_TITLE'					=> 'Nem adatad meg a MOD magyar nevét.',
	'NO_MOD_UNAPPROVED'				=> 'A MOD ellenőrzés alatt van, egyelőre nem elérhető.',
	'NO_TAGCAT'						=> 'Nincs ilyen címke kategória.',

	'PHPBB_VERSION'		=> 'phpBB verzió',

	'SIZE'				=> 'Méret',
	'SUBMIT_MOD'		=> 'MOD beküldése',
	'SUBMIT_MOD_CONFIRM'=> 'Biztosan be akarod küldeni a MOD-ot? Ellenőrizted, hogy tartalmazza az összes fordítással kapcsolatos dolgot? Átnézted a fordítást, megnézted, hogy van-e újabb verziója a MOD-nak?<br />Az elküldés után a MOD nem lesz azonnal elérhető, előbb az adminisztrátoroknak jóvá kell hagyniuk.<br />A MOD feldolgozása eltarthat egy ideig, kérünk, várj türelmesen.',

	'TAG_X'				=> '%s címke',
	'TAGCAT_X'			=> '%s címke kategória',

	'UPLOADER'			=> 'Feltöltő',

	'VERSION_UNKNOWN'	=> 'ismeretlen',
	'VIEW'				=> 'Megtekintés',
	'VIEW_MOD'			=> '1 modifikáció',
	'VIEW_MODS'			=> '%1$d modifikáció',
));

?>