<?
$ip_dbreq = true;
require_once('include/init.phph');

list ($common_cfgid, $rnwcommon_cfgid, $rnw_ui_cfgid)
    = msg_init($p_cfgdir, 'config', array('common', 'rnw_common', 'rnw_ui'));

list ($common_mbid, $rnw_mbid)
    = msg_init($p_cfgdir, 'msgbase', array('common', 'rnw'));


// get the type of transaction we are performing
$method = $_REQUEST['method'];
$ws = new MailMergeWebService();
switch ($method)
{
    case 'create':
    case 'update':
    case 'destroy':
    case 'get':
    case 'getAcColumns':
        echo json_encode($ws->getAcColumns($_REQUEST['param']));
        break;
    case 'getReportsByProfileId':
        echo json_encode($ws->getReportsByProfileId($_REQUEST['param']));
        break;
}

class MailMergeWebService
{
    public function MailMergeWebService()
    {
    }

    public function getReportsByProfileId($profile_id)
    {
        $si = sql_prepare(sprintf("select label_id, label from labels where (label_id in (select ac_id from analytics_core where owner_acct_id IN (select acct_id from accounts where profile_id = %d) and ac_id not in (select ac_id from ac_filters where opts & 0x2)) OR label_id in (select ac_id from ac_permissions where profile_id = %d and perms & 0x01 and ac_id not in (select ac_id from ac_filters where opts & 0x2))) and fld = 1 and lang_id = %d and tbl = 121 ORDER BY label ASC", $profile_id, $profile_id, lang_id(LANG_DIR)));
        sql_bind_col($si, 1, BIND_INT, 0);
        sql_bind_col($si, 2, BIND_NTS, 255);

        $reports = array(new Report(-1, "Select a report"));
        while (list($id, $lbl) = sql_fetch($si))
        {
            $reports[] = new Report($id, $lbl);
        }
        sql_free($si);

        return $reports;
    }

    public function getAcColumns($ac_id)
    {
        // only get back top level (n_id = 1)
        $si = sql_prepare(sprintf("SELECT col_id, val FROM ac_columns WHERE ac_id = %d AND n_id = 1", $ac_id));
        sql_bind_col($si, 1, BIND_INT, 0);
        sql_bind_col($si, 2, BIND_NTS, 255);

        $cols = array();
        while (list($id, $val) = sql_fetch($si))
        {
            $cols[] = new Column($ac_id, $id, $val);
        }
        sql_free($si);

        return $cols;
    }
}

/* sample JSON response
Setting = {
    id:1,
    acct_id:1,
    word_tmpl:"sample2.dotx",
    output_dir:"c:\\merges",
    file_format:"$FirstName$LastName",
    ac_id:102017,
    schedule:"2009-09-23 14:00",
    single_doc:0,
    auto_print:0,
    attach_to_contact:1,
    data_map: [
        {id:1, setting_id:1, rnt_field:1, tmpl_field:"FirstName"},
        {id:2, setting_id:1, rnt_field:2, tmpl_field:"LastName"}
    ]
};

Report = {
    ac_id:1,
    name:"My Report"
};
 */

class Report
{
    var $ac_id;
    var $name;

    public function Report($id, $lbl)
    {
        $this->ac_id = $id;
        $this->name = $lbl;
    }
}

class Column
{
    var $ac_id;
    var $id;
    var $val;

    public function Column($ac_id, $id, $val)
    {
        $this->ac_id = $ac_id;
        $this->id = $id;
        $this->val = $val;
    }
}

class Setting
{
    var $id;
    var $acct_id;
    var $word_tmpl;
    var $output_dir;
    var $file_format;
    var $ac_id;
    var $schedule;
    var $single_doc;
    var $auto_print;
    var $attach_to_contact;
    var $data_map = array();
}

class DataMapItem
{
    var $id;
    var $setting_id;
    var $rnt_fld;
    var $tmpl_fld;
}
