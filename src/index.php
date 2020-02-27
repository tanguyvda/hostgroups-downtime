<?php
/**
 * Copyright 2005-2011 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

require_once "../../require.php";
require_once $centreon_path . 'bootstrap.php';
require_once $centreon_path . 'www/class/centreon.class.php';
require_once $centreon_path . 'www/class/centreonSession.class.php';
require_once $centreon_path . 'www/class/centreonWidget.class.php';
require_once $centreon_path . 'www/class/centreonACL.class.php';
require_once $centreon_path . 'www/class/centreonHostgroups.class.php';


CentreonSession::start(1);

if (!isset($_SESSION['centreon']) || !isset($_REQUEST['widgetId']) || !isset($_REQUEST['page'])) {
    exit;
}
$db = $dependencyInjector['configuration_db'];
if (CentreonSession::checkSession(session_id(), $db) == 0) {
    exit;
}

$path = $centreon_path . "www/widgets/centreon-hostgroups-downtime-widget/src/";
$template = new Smarty();
$template = initSmartyTplForPopup($path, $template, "./", $centreon_path);

$query = "SELECT h.*, hg.name as 'hg_name' FROM hosts h, hosts_hostgroups hhg, hostgroups hg WHERE h.host_id=hhg.host_id AND hg.hostgroup_id=hhg.hostgroup_id";

if (isset($preferences['hg_search']) && $preferences['hg_search'] != '') {
    $results = explode(',', $preferences['hg_search']);
    $queryHG = '';
    foreach ($results as $result) {
        if ($queryHG != '') {
            $queryHG .=', ';
        }
        $queryHG .= ":id_" . $result;
        $mainQueryParameters[] = [
            'parameter' => ':id_' . $result,
            'value' => (int)$result,
            'type' => PDO::PARAM_INT
        ];
    }
    $hostgroupHgIdCondition = "hhg.hostgroup_id IN (" . $queryHG . ") ";

    $query = CentreonUtils::conditionBuilder($query, $hostgroupHgIdCondition);
}

$res = $db->prepare($query);
foreach ($mainQueryParameters as $parameter) {
    $res->bindValue($parameter['parameter'], $parameter['value'], $parameter['type']);
}

$res->execute();

$hgNotDisplayed = '';

while ($row = $res->fetch()) {
    if ($row['scheduled_downtime_depth'] == 0 && ! preg_match("/^" . $row['hg_name'] . "$/", $hgNotDisplayed)) {
        $hgNotDisplayed .= $row['hg_name'];
    }
}

 $chartData['rowCount'] = $res->rowCount();
