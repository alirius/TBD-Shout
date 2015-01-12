<?php
/*
TBD Shout - Real-time chat for MyBB
Copyright (C) 2015 Suhaimi Amir <suhaimi@tbd.my>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software Foundation,
Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
*/


if(!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("xmlhttp", "tbdshout_xmlhttp");
$plugins->add_hook("index_start", "tbdshout_output");
$plugins->add_hook("global_end", "tbdshout_pages");

function tbdshout_info() {
  return array(
    "name"          => "TBD Shout",
    "description"   => "Real-time chat for MyBB",
    "website"       => "http://chat.tbd.my",
    "author"        => "Suhaimi Amir",
    "authorsite"    => "http://github.com/suhz",
    "version"       => "0.1.1",
    "compatibility" => "18*",
  );
}

function tbdshout_install() {
  global $db;

  $tbdshout_group = array(
    'gid'           => 'NULL',
    'name'          => 'tbdshout',
    'title'         => 'TBD.my Shout',
    'description'   => 'Settings For TBD.my Shout',
    'disporder'     => "1",
    'isdefault'     => "0",
  );

  $db->insert_query('settinggroups', $tbdshout_group);
  $gid = $db->insert_id();

  $tbdshout_settings = array(
    array(
      'sid'           => 'NULL',
      'name'          => 'tbdshout_channel',
      'title'         => 'Channel Name',
      'description'   => 'Enter your channel name',
      'optionscode'   => 'text',
      'value'         => '',
      'disporder'     => 1,
      'gid'           => (int)$gid
    ),
    array(
      'sid'           => 'NULL',
      'name'          => 'tbdshout_secret_key',
      'title'         => 'Secret Keys',
      'description'   => 'This key is required for a secure communication between your MyBB and TBD server. Please log into https://chat.tbd.my/ to obtain your key.',
      'optionscode'   => 'text',
      'value'         => '',
      'disporder'     => 2,
      'gid'           => (int)$gid
    ),
    array(
      'sid'           => 'NULL',
      'name'          => 'tbdshout_guest_view',
      'title'         => 'Guest can view TBD Shout?',
      'description'   => 'Allow guests to see your TBD Shout?',
      'optionscode'   => 'yesno',
      'value'         => 0,
      'disporder'     => 6,
      'gid'           => (int)$gid
    ),
    array(
      'sid'           => 'NULL',
      'name'          => 'tbdshout_max_msg_disp',
      'title'         => 'Max number of shout',
      'description'   => 'Maximum number of shouts to be display in TBD Shout',
      'optionscode'   => 'numeric',
      'value'         => '30',
      'disporder'     => 3,
      'gid'           => (int)$gid
    ),
    array(
      'sid'           => 'NULL',
      'name'          => 'tbdshout_height',
      'title'         => 'TBD Shout height (px)',
      'description'   => 'Height of the shoutbox',
      'optionscode'   => 'numeric',
      'value'         => '350',
      'disporder'     => 4,
      'gid'           => (int)$gid
    )
  );

  $db->insert_query_multiple('settings',$tbdshout_settings);
}

function tbdshout_is_installed() {
  global $db;

  $query = $db->query("SELECT * FROM ".TABLE_PREFIX."settinggroups WHERE name='tbdshout'");
  if ($db->num_rows($query)) {
    return true;
  }
  return false;
}

function tbdshout_uninstall() {
  global $db;

  $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN('tbdshout_channel','tbdshout_secret_key','tbdshout_max_msg_disp','tbdshout_height','tbdshout_guest_view')");
  $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='tbdshout'");
  return true;
}

function tbdshout_activate() {
  global $db, $mybb;

  require_once MYBB_ROOT.'inc/class_parser.php';

  if (!$db->table_exists('tbdshout')) {
    $db->query("CREATE TABLE `".TABLE_PREFIX."tbdshout` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `uid` int(11) NOT NULL,
    `msg` text NOT NULL,
    `msg_date` datetime NOT NULL,
    `msg_ip` varchar(40) NOT NULL,
    `mobile` int(1) DEFAULT '0',
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    $db->insert_query("tbdshout", array(
      'uid'       => $mybb->user['uid'],
      'msg'       => 'This is the first shout. Thank you for installing TBD Shout.',
      'msg_date'  => date('Y-m-d H:i:s'),
      'msg_ip'    => get_ip()
    ));
  }


  $css = "masa {
    float:right;
    font-size:.7em;
    padding:.5em .3em;
    text-align:right;
  }

  #tbdshoutRowBox {
    vertical-align:top;
    overflow-y:auto;
    overflow-x:hidden;
    height:350px;
    width:100%;
    padding:0 .2em;
  }

  .status-bull, .hijau, .oren, .merah {
    cursor:help;
    vertical-align:bottom;
    padding-top:10px;
  }

  .hijau {
    color:green;
  }

  .merah {
    color:red;
  }

  .oren {
    color:orange;
  }

  .tbdshoutRow {
    vertical-align:bottom;
  }

  .sr_img {
    float:left;
    width:40px;
  }

  .sr_msg {
    float:left;
    margin-left:10px;
    width:90%;
  }

  .tbdshoutoddRowOdd {
    background-color:#dcdcdc;
  }

  .tbdshoutRowimg {
    width:40px;
    height:40px;
  }

  .tbdshoutRow {
    min-height:50px;
    margin-bottom:.1em;
    overflow:hidden;
    padding:.2em;
    display:block;
  }

  ";

  $template = '
    <div id="tbdshout_box" ng-app="tbdshoutApp">
      <table class="tborder" border="0" cellpadding="4" cellspacing="1">
        <thead>
          <tr>
            <td class="thead" colspan="5">
              <b>Shoutbox</b> (<a href="index.php?action=tbdshout_full">View Full Shoutbox</a>)
              <span style="float:right;font-size:0.7em">TBD Shout</span>
            </td>
          </tr>
        </thead>
        <tbody ng-controller="shoutCtrl">
          <tr>
            <td class="trow2">
              <form ng-submit="sendMsg()">
                Shout: <input size="50" type="text" ng-model="shoutText"> <input type="submit" value="Shout!" > <span ng-class="{hijau:status==1,oren:status==2,merah:status==0,oren:status==3}" title="{{status_txt}}">&#9724; {{status_txt}}</span>
              </form>
            </td>
          </tr>
          <tr>
            <td class="trow1">
              <div id="tbdshoutRowBox" ng-style="sr_tinggi_kotak">
                <div class="tbdshoutRow" ng-class-odd="\'tbdshoutoddRowOdd\'" ng-class-even="\'tbdshoutoddRowEven\'" ng-repeat="row in msgRows">
                  <div class="sr_img">
                    <img class="tbdshoutRowimg" ng-src="{{row.avatar}}">
                  </div>
                  <div class="sr_msg">
                    <b>{{row.name}}</b> : <span ng-bind-html="tawakalJela(row.msg)"></span>
                  </div>
                  <masa>{{row.date | timeAgo}}</masa>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>';

    $template_full = '<html>
    <head>
    <title>{$mybb->settings[\'bbname\']} - All TBD Shout Messages</title>
    {$headerinclude}
    </head>
    <body>
    {$header}
    <table border="0" cellspacing="1" cellpadding="4" class="tborder">
    <thead>
    <tr>
    <td class="thead" colspan="5">
    <div><strong>Shoutbox</strong><br /></div>
    </td>
    </tr>
    </thead>
    <tr>
    {$tbdshout_rows}
    </tr>
    </table>
    <br />
    <center>$multipage</center>
    {$footer}
    </body>
    </html>';

    $insert_css = array(
      'sid'           => NULL,
      'name'          => 'tbdshout.css',
      'tid'           => '1',
      'attachedto'    => 'index.php',
      'stylesheet'    => $db->escape_string($css),
      'cachefile'     => 'tbdshout.css',
      'lastmodified'  => time()
    );

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    $db->insert_query('themestylesheets', $insert_css);
    cache_stylesheet(1, "tbdshout.css", $css);
    update_theme_stylesheet_list(1);

    $insert_array = array(
      'title'     => 'tbdshout_box',
      'template'  => $db->escape_string($template),
      'sid'       => '-1',
      'version'   => '',
      'dateline'  => time()
    );

    $db->insert_query("templates", $insert_array);

    $insert_array = array(
      'title'     => 'tbdshout_box_full',
      'template'  => $db->escape_string($template_full),
      'sid'       => '-1',
      'version'   => '',
      'dateline'  => time()
    );

    $db->insert_query("templates", $insert_array);

    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

    find_replace_templatesets(
    'index',
    '#' . preg_quote('</head>') . '#i','<script type="text/javascript" src="{$mybb->asset_url}/jscripts/bower_components/angular/angular.min.js"></script><script type="text/javascript" src="{$mybb->asset_url}/jscripts/bower_components/angular-websocket/angular-websocket.min.js"></script><script type="text/javascript" src="{$mybb->asset_url}/jscripts/bower_components/angular-timeago/src/timeAgo.js"></script><script type="text/javascript" src="{$mybb->asset_url}/jscripts/tbdshout.js"></script>
    </head>');

    find_replace_templatesets('index','#' . preg_quote('{$header}') . '#i','{$header}{$tbdshoutbox}');
}



function tbdshout_deactivate() {
  global $db;

  $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title IN('tbdshout_box','tbdshout_box_full') AND sid='-1'");
  $db->query("DELETE FROM ".TABLE_PREFIX."themestylesheets WHERE name IN('tbdshout.css') AND tid='1'");

  require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
  update_theme_stylesheet_list(1);

  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
  find_replace_templatesets(
  'index',
  '#' . preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/bower_components/angular/angular.min.js"></script><script type="text/javascript" src="{$mybb->asset_url}/jscripts/bower_components/angular-websocket/angular-websocket.min.js"></script><script type="text/javascript" src="{$mybb->asset_url}/jscripts/bower_components/angular-timeago/src/timeAgo.js"></script><script type="text/javascript" src="{$mybb->asset_url}/jscripts/tbdshout.js"></script>') . '#i',
  ''
  );
  find_replace_templatesets(
  'index',
  '#' . preg_quote('{$tbdshoutbox}') . '#i',''
);

  rebuild_settings();
}

function tbdshout_xmlhttp() {
  global $mybb;

  if ($mybb->input['action'] == 'tbdshout_get') {
    tbdshout_getShout();
  } else
  if ($mybb->input['action'] == 'tbdshout_post') {
    tbdshout_sendShout();
  } else
  if ($mybb->input['action'] == 'tbdshout_smiley') {
    tbshout_smiley();
  } else
  if ($mybb->input['action'] == 'tbdshout_info') {
    tbdshout_getinfo();
  }
}

//get chat messages & infos
function tbdshout_getShout() {
  global $db, $mybb;

  $q = $db->query("SELECT c.*, u.username, u.usergroup, u.avatar, u.displaygroup FROM ".TABLE_PREFIX."tbdshout c
  LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = c.uid)
  ORDER by c.id DESC LIMIT " . (int)$mybb->settings['tbdshout_max_msg_disp']);

  $chat = array();
  require_once MYBB_ROOT.'inc/class_parser.php';
  $parser = new postParser;

  while ($row = $db->fetch_array($q)) {

    $msg = array(
      'name'      => $row['username'],
      'avatar'    => $row['avatar'],
      'msg'       => $parser->parse_message(html_entity_decode($row['msg']),array('me_username' => $mybb->user['username'])),
      'date'      => date('c',strtotime($row['msg_date']))
    );

    $chat[] = $msg;
  }

  if ($mybb->settings['tbdshout_secret_key'] == '' || $mybb->settings['tbdshout_channel'] == '') {
    if ($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['issupermod'] == 1) {
      $msg = array(
        'name'      => 'TBD Shout',
        'avatar'    => ' ',
        'msg'       => '<font color="red">Please configure TBD Shout in the "Admin > Configuration" to start using TBD Shout.</font>',
        'date'      => date('c',time())
      );
      $chat = array($msg);
    }
  }

  $key = sha1($mybb->settings['tbdshout_channel'].$mybb->settings['tbdshout_secret_key'].$mybb->user['uid'].generate_post_check());

  $ret = array(
    'name'    => $mybb->user['username'],
    'uid'     => $mybb->user['uid'],
    'ukey'    => $key, //user key,
    'avatar'  => $mybb->user['avatar'],
    'skey'    => md5($mybb->settings['tbdshout_channel'].$mybb->settings['tbdshout_secret_key']), //server access key
    'channel' => $mybb->settings['tbdshout_channel'],
    'smiley'  => tbshout_smiley(1),
    'max_height'  => (int)$mybb->settings['tbdshout_height'],
    'max_msg'     => (int)$mybb->settings['tbdshout_max_msg_disp'],
    'msg'     => $chat
  );

  die(json_encode($ret));
}

//save shout
function tbdshout_sendShout() {
  global $db, $mybb;

  $data_arr = json_decode($mybb->input['push']);

  foreach ($data_arr as $x) {
    $user = get_user((int)$x->uid);
    tbdshout_post_check($user, $x);
    tbdshout_canView($user);

    //if ($x['channel'] != $mybb->settings['tbdshout_channel']) { continue; }

    $save[] = array(
      'uid'       => $user['uid'],
      'msg'       => htmlspecialchars_uni(html_entity_decode($x->msg)),
      'msg_date'  => date('Y-m-d H:i:s', $x->masa),
      'msg_ip'    => $x->msg_ip,
      //'mobile'    => $mybb->input['mobile']==1?1:0
    );
  }

  $db->insert_query_multiple('tbdshout',$save);

  header('Content-Type: application/json');

  if ($db->insert_query('tbdshout', $data)) {
    $chat_id = $db->insert_id();
    echo 1;
    //die(json_encode(array('status'=>1)));
  } else {
    $chat_id = 0;
    echo 0;
    //die(json_encode(array('status'=>0)));
  }
}

//list of smileys
function tbshout_smiley($nojson = false) {
  global $db;

  $query = $db->simple_select("smilies", "*", "", array('order_by' => 'disporder'));
  $ret = array();

  while($smilie = $db->fetch_array($query)) {
    $ret[$smilie['find']] = array('img' => $smilie['image'],'find' => htmlspecialchars_uni(addslashes($smilie['find'])));
  }

  //return json if true
  if ($nojson == false) {
    die(json_encode($ret));
  } else {
    return $ret;
  }
}

function tbdshout_full() {
  global $mybb, $db, $charset, $templates, $footer, $headerinclude, $header;

  if (tbdshout_canView($mybb->user, false) == false) {
    return;
  }

  header("Content-type: text/html; charset={$charset}");

  add_breadcrumb('TBD Shout', "index.php?action=tbdshout_full");
  $per_page = (int)$mybb->settings['tbdshout_max_msg_disp'];
  if ($per_page < 0) { $per_page = 0; }

  $query = $db->query("SELECT * FROM ".TABLE_PREFIX."tbdshout");
  $jumlah_shout = $db->num_rows($query);

  $page = (int)$mybb->input['page'];
  if ($page < 0) { $page = 0; }

  $pages = ceil($jumlah_shout / $per_page);

  if ($page > $pages) {
    $page = 1;
  }

  if ($page) {
    $start = ($page-1) * $per_page;
  } else {
    $start = 0;
    $page = 1;
  }

  if ($jumlah_shout > $per_page) {
    $multipage = multipage($jumlah_shout, $per_page, $page, "index.php?action=tbdshout_full");
  }

  $q = $db->query("SELECT c.*, u.username, u.usergroup, u.avatar, u.displaygroup FROM ".TABLE_PREFIX."tbdshout c
  LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = c.uid)
  ORDER by c.id DESC LIMIT {$start}, {$per_page}");

  require_once MYBB_ROOT.'inc/class_parser.php';
  $parser = new postParser;

  while ($row = $db->fetch_array($q)) {
    $username = '<a href="member.php?action=profile&uid='.intval($row['uid']).'">'.$row['username'].'</a>';
    $class = alt_trow();
    $msg  = $parser->parse_message(html_entity_decode($row['msg']),array('allow_smilies' => 'yes','me_username' => $mybb->user['username']));
    $msg_date = my_date('d-m H:i',strtotime($row['msg_date']));
    $tbdshout_rows .= "<tr class='tbdshout_rows'><td class='{$class}'>&raquo; {$delete} {$msg_date} - <b>{$username}</b> - {$msg}</td></tr>";
  }

  eval("\$tbdshout_full = \"".$templates->get("tbdshout_box_full")."\";");
  die(output_page($tbdshout_full));
}

function tbdshout_pages() {
  global $mybb;

  if ($mybb->input['action'] == 'tbdshout_full') {
    return tbdshout_full();
  }
}

function tbdshout_getinfo() {
  $info = array('version'  => tbdshout_info()['version']);
  die(json_encode($info));
}

function tbdshout_output() {
  global $mybb, $templates, $tbdshoutbox;

  if (tbdshout_canView($mybb->user, false)) {
    eval("\$tbdshoutbox = \"".$templates->get("tbdshout_box")."<br /><br />\";");
  }
}

function tbdshout_canView($user, $die = true) {
  if ($user['usergroup'] == 7 || ($user['usergroup'] == 1 && $mybb->settings['tbdshout_guest_view'] == 'no') || $user['uid'] < 1) {
    if ($die == true) {
      die();
    } else {
      return false;
    }
  }
  return true;
}

function tbdshout_linkyfy($text) {
  return preg_replace("/([\w]+\:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/", "<a target=\"_blank\" href=\"$1\">$1</a>", $text);
}

function tbdshout_post_check($user,$post_data) {
  global $mybb;

  $key = sha1($mybb->settings['tbdshout_channel'].$mybb->settings['tbdshout_secret_key'].$post_data->uid.md5($user['loginkey'].$user['salt'].$user['regdate']));

  if ($key !== $post_data->key) {
    die('invalid post code');
  }
}