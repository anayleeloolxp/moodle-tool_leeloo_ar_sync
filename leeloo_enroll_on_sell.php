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
 * Enrol user on purchase.
 *
 * @package tool_leeloo_ar_sync
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);
require(__DIR__ . '/../../../config.php');
$enrolled = 0;

$reqproductid = optional_param('product_id', 0, PARAM_RAW);
$requsername = optional_param('username', 0, PARAM_RAW);

if (isset($reqproductid) && isset($requsername)) {
    $productid = $reqproductid;
    $username = $requsername;

    $courseidarr = $DB->get_record_sql("SELECT courseid FROM {tool_leeloo_ar_sync} Where productid = ?", [$productid]);
    $courseid = $courseidarr->courseid;

    $useridarr = $DB->get_record_sql("SELECT id FROM {user} Where username = ?", [$username]);
    $userid = $useridarr->id;

    if ($courseid && $userid) {
        $DB->execute("INSERT INTO {tool_leeloo_ar_sync_restrict} (arid,userid, productid) VALUES (?, ? , ?)", [$courseid, $userid, $productid]);
        $enrolled = 1;
    }
}
echo $enrolled;
