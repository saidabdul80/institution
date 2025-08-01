<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Jobs\QueueMail;
use App\Jobs\SendSMS;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\File;

class NotificationService
{
    public static function sendNotification($notification)
    {
        $owner = $notification->notifiable;
        
        // Send SMS
        // SendSMS::dispatch(
        //     validate_phone_number($owner->phone_number),  
        //     "Hi, You have a new ".NotificationType::getKey($notification->type)." notification. You can preview with the link below. \n" .
        //     config('default.portal.domain') . '/notifications/preview/' . $notification->id
        // );

         // Generate PDF from message
        $pdfContent = Pdf::loadView('pdf.notification', ['message' => $notification->message])->output();
        $pdfPath = sys_get_temp_dir() . '/' . uniqid() . '.pdf';
        file_put_contents($pdfPath, $pdfContent);
        $pdfFile = new File($pdfPath);

        $path = Util::publicUrl(Util::upload( $pdfFile,'notification'));   

        // Send Email 
        $ownerData = $owner->toArray(); 
        //$ownerData["message"] = $notification->message; 
        QueueMail::dispatch($ownerData, 'notification', "Notification Alert",'corporate' , ['path' => $path, 'name' => 'notification.pdf']);
        if ($pdfPath && file_exists($pdfPath)) {
            unlink($pdfPath);
        }
    } 

   
} 
 