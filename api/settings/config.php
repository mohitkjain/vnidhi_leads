<?php

class config
{
    public $webaddress = "http://test.vaibhavnidhi.com/";
    
    public function getteammates()
    {
        return $this->webaddress. "api/teammates/";
    }

    public function getleaders()
    {
        return $this->webaddress. "api/leaders/";
    }

    public function getrewards()
    {
        return $this->webaddress. "/api/rewards-percent";
    }

    public function getpercent()
    {
        return $this->webaddress. "/api/incentive-percent";
    }

    public function addFD_Achieved()
    {
        return $this->webaddress. "/api/users/fd/achieved";
    }

    public function add_telecaller_incentive()
    {
        return $this->webaddress. "/api/telecaller/incentive";
    }

    public function get_current_month_target_details()
    {
        return $this->webaddress. "/api/admin/target/current_month/";
    }

    public function get_pre_month_target_details()
    {
        return $this->webaddress. "/api/admin/target/previous_month/";
    }

    public function add_initial_rewards_percent()
    {
        return $this->webaddress. "/api/incentive-reward/initial";
    }

    public function add_comments_on_status_change()
    {
        return $this->webaddress. "/api/leads/comments/add";
    }

    public function set_target_curl()
    {
        return $this->webaddress. "/api/cron/set_target/current_month";
    }

    public function add_fd_reward_curl()
    {
        return $this->webaddress. "/api/cron/fd_reward/add";
    }
}

?>