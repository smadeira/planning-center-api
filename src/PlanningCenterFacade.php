<?php namespace PlanningCenterAPI;

class PlanningCenterFacade extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return PlanningCenterAPI::class;
    }
}
