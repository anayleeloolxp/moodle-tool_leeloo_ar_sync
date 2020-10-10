<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Index File.
 *
 * @package    tool_leeloo_ar_sync
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
admin_externalpage_setup('toolleeloo_ar_sync');

global $SESSION;

$selcourse = optional_param('sel_course', 0, PARAM_RAW);

$postars = optional_param('courses', null, PARAM_RAW);
$postprices = optional_param('price', null, PARAM_RAW);
$postkeytypes = optional_param('keytype', null, PARAM_RAW);
$postkeyprices = optional_param('keyprice', null, PARAM_RAW);
$postfullnames = optional_param('fullnames', null, PARAM_RAW);

$vendorkey = get_config('tool_leeloo_ar_sync', 'vendorkey');

$leeloolxplicense = get_config('tool_leeloo_ar_sync')->license;

$url = 'https://leeloolxp.com/api_moodle.php/?action=page_info';
$postdata = '&license_key=' . $leeloolxplicense;

$curl = new curl;

$options = array(
    'CURLOPT_RETURNTRANSFER' => true,
    'CURLOPT_HEADER' => false,
    'CURLOPT_POST' => count($postdata),
);

if (!$output = $curl->post($url, $postdata, $options)) {
    $error = get_string('nolicense', 'tool_leeloo_ar_sync');
}

$infoleeloolxp = json_decode($output);

if ($infoleeloolxp->status != 'false') {
    $leeloolxpurl = $infoleeloolxp->data->install_url;
} else {
    $error = get_string('nolicense', 'block_leeloo_prodcuts');
}

$leelooapibaseurl = 'https://leeloolxp.com/api/moodle_sell_course_plugin/';

echo '<style>.sellcoursesynctable td,.sellcoursesynctable th {border: 1px solid;padding: 5px;}</style>';

/**
 * Encrypt Data
 *
 * @param string $texttoencrypt The texttoencrypt
 * @return string Return encrypted string
 */
function encrption_data($texttoencrypt) {

    $encryptionmethod = "AES-256-CBC";
    $secrethash = "25c6c7ff35b9979b151f2136cd13b0ff";
    return openssl_encrypt($texttoencrypt, $encryptionmethod, $secrethash);
}

$post = [
    'license_key' => encrption_data($vendorkey),
];

$url = $leelooapibaseurl . 'get_keytypes_by_licensekey.php';
$postdata = '&license_key=' . encrption_data($vendorkey);
$curl = new curl;
$options = array(
    'CURLOPT_RETURNTRANSFER' => true,
    'CURLOPT_POSTFIELDS' => $post,
);

if (!$output = $curl->post($url, $postdata, $options)) {
    $error = get_string('nolicense', 'tool_leeloo_ar_sync');
}
$keysresponse = json_decode($output);

if ($postars) {
    foreach ($postars as $postcourseid => $postcourse) {
        if ($postcourse == 0) {
            $leeloodept = $DB->get_record_sql('SELECT productid FROM {tool_leeloo_ar_sync} WHERE courseid = ' . $postcourseid . '');

            $productid = $leeloodept->productid;

            $courseprice = $postprices[$postcourseid];
            $coursesynckeyprice = $postkeyprices[$postcourseid];
            $coursesynckeytype = $postkeytypes[$postcourseid];
            $coursesfullname = $postfullnames[$postcourseid];

            if ($courseprice == '') {
                $courseprice = 0;
            }
            if ($coursesynckeyprice == '') {
                $coursesynckeyprice = 0;
            }

            $post = [
                'license_key' => encrption_data($vendorkey),
                'action' => encrption_data('update'),
                'productid' => encrption_data($productid),
                'status' => encrption_data('0'),
                'coursename' => encrption_data($coursesfullname),
                'coursesummary' => encrption_data(''),
                'price' => encrption_data($courseprice),
                'keyprice' => encrption_data($coursesynckeyprice),
                'keytype' => encrption_data($coursesynckeytype),

            ];

            $url = $leelooapibaseurl . 'sync_courses_products.php';
            $curl = new curl;
            $options = array(
                'CURLOPT_RETURNTRANSFER' => true,
            );

            if (!$output = $curl->post($url, $post, $options)) {
                $error = get_string('nolicense', 'tool_leeloo_ar_sync');
            }
            $infoleeloo = json_decode($output);

            if ($infoleeloo->status == 'true') {
                $DB->execute("UPDATE {tool_leeloo_ar_sync} SET enabled = 0,productprice = '$courseprice',keytype = '$coursesynckeytype',keyprice = '$coursesynckeyprice' WHERE courseid = '$postcourseid'");
            }
        }

        if ($postcourse == 1) {
            $leeloocourse = $DB->get_record_sql('SELECT COUNT(*) as countcourse FROM {tool_leeloo_ar_sync} WHERE courseid = ' . $postcourseid . '');

            if ($leeloocourse->countcourse == 0) {
                $courseprice = $postprices[$postcourseid];
                $coursesynckeyprice = $postkeyprices[$postcourseid];
                $coursesynckeytype = $postkeytypes[$postcourseid];
                $coursesfullname = $postfullnames[$postcourseid];

                if ($courseprice == '') {
                    $courseprice = 0;
                }
                if ($coursesynckeyprice == '') {
                    $coursesynckeyprice = 0;
                }

                $post = [
                    'license_key' => encrption_data($vendorkey),
                    'action' => encrption_data('insert'),
                    'courseid' => encrption_data($postcourseid),
                    'coursename' => encrption_data($coursesfullname),
                    'coursesummary' => encrption_data(''),
                    'price' => encrption_data($courseprice),
                    'keyprice' => encrption_data($coursesynckeyprice),
                    'keytype' => encrption_data($coursesynckeytype),
                    'synctype' => encrption_data('2'),
                ];

                $url = $leelooapibaseurl . 'sync_courses_products.php';
                $curl = new curl;
                $options = array(
                    'CURLOPT_RETURNTRANSFER' => true,
                );

                if (!$output = $curl->post($url, $post, $options)) {
                    $error = get_string('nolicense', 'tool_leeloo_ar_sync');
                }
                $infoleeloo = json_decode($output);
                if ($infoleeloo->status == 'true') {
                    $productid = $infoleeloo->data->id;
                    $productalias = $infoleeloo->data->product_alias;
                    $DB->execute("INSERT INTO {tool_leeloo_ar_sync} (courseid, productid, enabled, productprice,product_alias,keytype,keyprice)VALUES ('$postcourseid', '$productid', '1','$courseprice','$productalias','$coursesynckeytype','$coursesynckeyprice')");
                }
            } else {

                $leeloodept = $DB->get_record_sql('SELECT productid FROM {tool_leeloo_ar_sync} WHERE courseid = ' . $postcourseid . '');

                $productid = $leeloodept->productid;

                $courseprice = $postprices[$postcourseid];
                $coursesynckeyprice = $postkeyprices[$postcourseid];
                $coursesynckeytype = $postkeytypes[$postcourseid];
                $coursesfullname = $postfullnames[$postcourseid];

                if ($courseprice == '') {
                    $courseprice = 0;
                }
                if ($coursesynckeyprice == '') {
                    $coursesynckeyprice = 0;
                }

                $post = [
                    'license_key' => encrption_data($vendorkey),
                    'action' => encrption_data('update'),
                    'productid' => encrption_data($productid),
                    'status' => encrption_data('1'),
                    'coursename' => encrption_data($coursesfullname),
                    'coursesummary' => encrption_data(''),
                    'price' => encrption_data($courseprice),
                    'keyprice' => encrption_data($coursesynckeyprice),
                    'keytype' => encrption_data($coursesynckeytype),
                ];

                $url = $leelooapibaseurl . 'sync_courses_products.php';
                $curl = new curl;
                $options = array(
                    'CURLOPT_RETURNTRANSFER' => true,
                );

                if (!$output = $curl->post($url, $post, $options)) {
                    $error = get_string('nolicense', 'tool_leeloo_ar_sync');
                }
                $infoleeloo = json_decode($output);

                if ($infoleeloo->status == 'true') {
                    $DB->execute("UPDATE {tool_leeloo_ar_sync} SET enabled = 1,productprice = '$courseprice',keytype = '$coursesynckeytype',keyprice = '$coursesynckeyprice' WHERE courseid = '$postcourseid'");
                }
            }
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('leeloo_ar_sync', 'tool_leeloo_ar_sync'), 'leeloo_ar_sync', 'tool_leeloo_ar_sync');
if (!empty($error)) {
    echo $OUTPUT->container($error, 'leeloo_ar_sync_myformerror');
}

$courses = $DB->get_records_sql('SELECT id,fullname FROM {course}');
if (!empty($courses)) {
    echo '<form method="get"><label for="">Select Course for which you want to sell A/R. </label>';
    echo '<select onchange="this.form.submit();" name="sel_course"><option value="0">Select</option>';

    foreach ($courses as $courseloop) {
        if ($selcourse == $courseloop->id) {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        echo '<option ' . $selected . ' value="' . $courseloop->id . '">' . $courseloop->fullname . '</option>';
    }

    echo '</select>';
    echo '</form>';
}

if ($selcourse) {
    $course = get_course($selcourse);
    $modinfo = get_fast_modinfo($course);

    if (!empty($modinfo->cms)) {
        echo '<form method="post">
        <table class="sellcoursesynctable">
        <thead>
            <th>&nbsp;</th>
            <th>A/R</th>
            <th>Price($)</th>
            <th>Key Allowed</th>
            <th>Key Price</th>
        </thead>';
        foreach ($modinfo->cms as $arloop) {
            $arid = $arloop->id;
            $arfullname = $arloop->get_formatted_name();
            $aricon = '<img src="' . $arloop->get_icon_url() . '" class="icon" alt="" />&nbsp;';

            $leelooardata = $DB->get_record_sql('SELECT * FROM {tool_leeloo_ar_sync} WHERE courseid = ' . $arid . '');

            $courseenabled = $leelooardata->enabled;
            $courseproductprice = $leelooardata->productprice;
            $coursekeyprice = $leelooardata->keyprice;
            $coursekeytype = $leelooardata->keytype;
            if ($courseenabled == 1) {
                $checkboxchecked = 'checked';
            } else {
                $checkboxchecked = '';
            }

            echo '<tr>';
            echo "<td><input type='hidden' value='0' name='courses[$arid]'><input $checkboxchecked id='course_$arid' type='checkbox' name='courses[$arid]' value='1'></td>";
            echo "<td><label for='course_$arid'>$aricon $arfullname</label><input type='hidden' value='$arfullname' name='fullnames[$arid]'></td>";
            echo "<td><input type='number' value='$courseproductprice' name='price[$arid]' id='price_$arid'></td>";

            $keys_select = "<select name='keytype[$arid]'><option value='-1'>No</option>";
            if ($keysresponse->status == 'true') {
                foreach ($keysresponse->data->keys as $keytype) {
                    if ($coursekeytype == $keytype->id) {
                        $selectedkeytype = 'selected';
                    } else {
                        $selectedkeytype = '';
                    }
                    $keys_select .= "<option $selectedkeytype value='$keytype->id'>$keytype->name</option>";
                }
            }
            $keys_select .= "</select>";

            echo "<td>$keys_select</td>";

            echo "<td><input type='number' value='$coursekeyprice' name='keyprice[$arid]' id='price_$arid'></td>";
            echo '</tr>';
        }
        echo '</table><button type="submit" value="Save and Create Product">Submit</button></form>';
    }
}

echo $OUTPUT->footer();