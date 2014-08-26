<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin designer general code
 *
 * @package PhpMyAdmin-Designer
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/pmd_common.php';
require_once 'libraries/db_designer.lib.php';

$response = PMA_Response::getInstance();

if (isset($_REQUEST['dialog'])) {

    if ($_REQUEST['dialog'] == 'edit') {
        $html = PMA_getHtmlForEditOrDeletePages($GLOBALS['db'], 'editPage');
    } else if ($_REQUEST['dialog'] == 'delete') {
        $html = PMA_getHtmlForEditOrDeletePages($GLOBALS['db'], 'deletePage');
    } else if ($_REQUEST['dialog'] == 'save_as') {
        $html = PMA_getHtmlForPageSaveAs($GLOBALS['db']);
    } else if ($_REQUEST['dialog'] == 'export') {
        include_once 'libraries/plugin_interface.lib.php';
        $html = PMA_getHtmlForSchemaExport(
            $GLOBALS['db'], $_REQUEST['selected_page']
        );
    }

    if (! empty($html)) {
        $response->addHTML($html);
    }
    return;
}

if (isset($_REQUEST['operation'])) {

    if ($_REQUEST['operation'] == 'deletePage') {
        $result = PMA_deletePage($_REQUEST['selected_page']);
        if ($result) {
            $response->isSuccess(true);
        } else {
            $response->isSuccess(false);
        }
    } elseif ($_REQUEST['operation'] == 'savePage') {
        if ($_REQUEST['save_page'] == 'same') {
            $page = $_REQUEST['selected_page'];
        } elseif ($_REQUEST['save_page'] == 'new') {
            $page = PMA_createNewPage($_REQUEST['selected_value'], $GLOBALS['db']);
            $response->addJSON('id', $page);
        }
        if (PMA_saveTablePositions($page)) {
            $response->isSuccess(true);
        } else {
            $response->isSuccess(false);
        }
    } elseif ($_REQUEST['operation'] == 'setDisplayField') {
        PMA_saveDisplayField(
            $_REQUEST['db'], $_REQUEST['table'], $_REQUEST['field']
        );
        $response->isSuccess(true);
    } elseif ($_REQUEST['operation'] == 'addNewRelation') {
        list($success, $message) = PMA_addNewRelation(
            $_REQUEST['db'],
            $_REQUEST['T1'],
            $_REQUEST['F1'],
            $_REQUEST['T2'],
            $_REQUEST['F2'],
            $_REQUEST['on_delete'],
            $_REQUEST['on_update']
        );
        $response->isSuccess($success);
        $response->addJSON($success ? 'message' : 'error', $message);
    } elseif ($_REQUEST['operation'] == 'removeRelation') {
        PMA_removeRelation(
            $_REQUEST['T1'],
            $_REQUEST['F1'],
            $_REQUEST['T2'],
            $_REQUEST['F2']
        );
        $response->isSuccess(true);
    }
    return;
}

$script_display_field = PMA_getTablesInfo();
$tab_column = PMA_getColumnsInfo();
$script_tables = PMA_getScriptTabs();
$tables_pk_or_unique_keys = PMA_getPKOrUniqueKeys();
$tables_all_keys = PMA_getAllKeys();

$display_page = -1;
$selected_page = null;

if (! isset($_REQUEST['query'])) {
    if (! empty($_REQUEST['page'])) {
        $display_page = $_REQUEST['page'];
    } else {
        $display_page = PMA_getFirstPage($_REQUEST['db']);
    }
}
if ($display_page != -1) {
    $selected_page = PMA_getPageName($display_page);
}
$tab_pos = PMA_getTablePositions($display_page);
$script_contr = PMA_getScriptContr();

$params = array('lang' => $GLOBALS['lang']);
if (isset($_GET['db'])) {
    $params['db'] = $_GET['db'];
}

$response = PMA_Response::getInstance();
$response->getFooter()->setMinimal();
$header   = $response->getHeader();
$header->setBodyId('pmd_body');

$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery.fullscreen.js');
$scripts->addFile('pmd/designer_db.js');
$scripts->addFile('pmd/designer_objects.js');
$scripts->addFile('pmd/designer_page.js');
$scripts->addFile('pmd/history.js');
$scripts->addFile('pmd/move.js');
$scripts->addFile('pmd/iecanvas.js', true);
$scripts->addFile('pmd/init.js');

require 'libraries/db_common.inc.php';
require 'libraries/db_info.inc.php';

// Embed some data into HTML, later it will be read
// by pmd/init.js and converted to JS variables.
$response->addHTML(
    PMA_getHtmlForJSFields(
        $script_tables, $script_contr, $script_display_field, $display_page
    )
);
$response->addHTML(
    PMA_getDesignerPageTopMenu(isset($_REQUEST['query']), $selected_page)
);

$response->addHTML('<div id="canvas_outer">');
$response->addHTML('<form action="" method="post" name="form1">');

$response->addHTML(PMA_getHTMLCanvas());
$response->addHTML(PMA_getHTMLTableList($tab_pos, $display_page));

$response->addHTML(
    PMA_getDatabaseTables(
        $tab_pos, $display_page, $tab_column,
        $tables_all_keys, $tables_pk_or_unique_keys
    )
);
$response->addHTML('</form>');
$response->addHTML('</div>'); // end canvas_outer

$response->addHTML('<div id="pmd_hint"></div>');

$response->addHTML(PMA_getNewRelationPanel());
$response->addHTML(PMA_getDeleteRelationPanel());

if (isset($_REQUEST['query'])) {
    $response->addHTML(PMA_getOptionsPanel());
    $response->addHTML(PMA_getRenameToPanel());
    $response->addHTML(PMA_getHavingQueryPanel());
    $response->addHTML(PMA_getAggregateQueryPanel());
    $response->addHTML(PMA_getWhereQueryPanel());
    $response->addHTML(PMA_getQueryDetails());
}

$response->addHTML(PMA_getCacheImages());
?>
