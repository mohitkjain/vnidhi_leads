<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

date_default_timezone_set('Asia/Kolkata');
error_reporting(-1);
ini_set('display_errors', 1);

$app = new \Slim\App([
    'settings' => [
        // Slim Settings
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
    ]
]);

// ADMIN
require_once '../api/admin/deactivate_user.php';
require_once '../api/admin/get_previous_month_targets.php';
require_once '../api/admin/get_current_month_targets.php';
require_once '../api/admin/set_current_month_target.php';
require_once '../api/admin/get_rd_due.php';
require_once '../api/admin/rd_amount_receive.php';
require_once '../api/admin/set_target_cron_job.php';
require_once '../api/admin/get_target_details.php';
require_once '../api/admin/add_fd_reward_cron_job.php';
require_once '../api/admin/edit_user_details.php';
require_once '../api/admin/get_leads_stats.php';

require_once '../api/userauth.php';
require_once '../api/check_user_active.php';
require_once '../api/newuser.php';
require_once '../api/get_teamleader.php';
require_once '../api/get_user_info.php';
require_once '../api/update_user_details.php';
require_once '../api/newlead.php';
require_once '../api/new_direct_lead.php';
require_once '../api/convert_lead.php';
require_once '../api/update_lead_details.php';
require_once '../api/get_requestpending_leads.php';
require_once '../api/get_declined_leads.php';
require_once '../api/getteammates.php';
require_once '../api/add_initial_incentive_rewards.php';
require_once '../api/add_into_fd_target.php';
require_once '../api/add_telecaller_incentive.php';
require_once '../api/get_previous_incentive_details.php';
require_once '../api/get_current_incentive_details.php';
require_once '../api/get_rewards_details.php';
require_once '../api/get_rewards_percent.php';
require_once '../api/get_redeem_data.php';
require_once '../api/add_redeemption_details.php';
require_once '../api/get_incentive_percent.php';
require_once '../api/getleaders.php';
require_once '../api/salaried_user_from_team.php';
require_once '../api/get_active_leads.php';
require_once '../api/get_accepted_leads.php';
require_once '../api/getleadinfo.php';
require_once '../api/get_available_status.php';
require_once '../api/update_status.php';
require_once '../api/add_comments.php';
require_once '../api/get_comments.php';
require_once '../api/change_assignee_list.php';
require_once '../api/update_assignee.php';
require_once '../api/allusers.php';
require_once '../api/getusername.php';
require_once '../api/getschemes.php';
require_once '../api/getschemedetail.php';
require_once '../api/getmaturitydetails.php';
require_once '../api/get_emi_details.php';
require_once '../api/change_assignee_list.php';
require_once '../api/test.php';

$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");

    return $response;
});
$app->run();