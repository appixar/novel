<?php
class jobs extends Mason
{
  public function __construct()
  {
    Job::run_all_jobs();
    exit;
  }
}
