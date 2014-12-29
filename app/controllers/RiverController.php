<?php

class RiverController extends BaseController
{

    public function states($state_name)
    {
        return Cache::rememberForever('rivers.states.' . $state_name, function() use ($state_name)
        {
            return River::whereIn('id', function($q) use ($state_name) {
                    $q->select(DB::raw('max(`id`) as "id"'))
                      ->from('rivers')
                      ->groupBy('name')
                      ->where('state', $state_name)
                      ->get();
                })
                ->get();
        });
    }

    public function alerts()
    {
        return Cache::rememberForever('rivers.alerts', function()
        {
            return River::whereIn('id', function($q) {
                    $q->select(DB::raw('max(`id`) as "id"'))
                      ->from('rivers')
                      ->groupBy('name')
                      ->get();
                })
                ->whereIn('status', ['danger', 'warning', 'alert'])
                ->get();
        });
    }

}