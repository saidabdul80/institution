<?php

namespace App\Listeners\BasicSchool;

use App\Events\BasicSchool\EventCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\BasicSchoolAPI\Entities\ParentTenantChild;
use Modules\BasicSchoolAPI\Entities\StudentParent;
use Spatie\Activitylog\Models\Activity;

class SendEventCreatedNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\BasicSchool\\EventCreated  $event
     * @return void
     */
    public function handle(EventCreated $event)
    {                
        $parent_ids = ParentTenantChild::where('tenant_id',tenant('id'))->pluck('parent_id');
        $eventName = $event->data['event'];        
        $startDate = $event->data['start_date'];
        $endDate = $event->data['end_date'];                
        $records = [];
        foreach($parent_ids as $parent_id){
            $records[]=[
                'log_name' => $eventName, 
                'description' => "$eventName has been scheduled for $startDate - $endDate",
                'subject_type' => StudentParent::class,
                'subject_id' => $parent_id,
                'created_at'=>now(),
                'updated_at'=>now()
            ];
        }
        Activity::insert($records);
    }
}
