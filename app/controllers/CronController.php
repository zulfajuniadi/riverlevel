<?php

class CronController extends BaseController
{

    public function rivers()
    {
        set_time_limit(600);
        Artisan::call('scrape:river');
        return 'ok';
    }

}