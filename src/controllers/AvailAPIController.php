<?php namespace Anzware\Avail;

use \Illuminate\Routing\Controller;
use Anzware\Avail\Helper\AvailResponse;
use \Carbon\Carbon;

class AvailAPIController extends Controller
{
    public function getCalendar()
    {
        try {
            $calendar = array();

            $currentDate = Carbon::now();
            $currentDateStartOfDay = Carbon::now()->startOfDay();
            $currentDateStartOfMonth = Carbon::now()->startOfMonth();

            // the amount of months needed to be displayed
            $take = 3;

            for ($i = 0; $i < $take; $i++) {
                $month = Carbon::now()->startOfMonth()->addMonths($i);
                $monthData = new \stdclass();
                $monthData->name = $month->format('F');
                $monthData->days = array();

                for ($d = 1; $d <= $month->daysInMonth; $d++) {
                    $day = new \stdclass();
                    $day->day = $d;
                    $day->month = $month->month;
                    $day->year = $month->year;
                    $day->stringDate = Carbon::now()->startOfMonth()->addMonths($i)->addDays($d-1)->toDateString();
                    // check for today date
                    if ($currentDate->day === $d &&
                        $currentDate->month === $month->month &&
                        $currentDate->year === $month->year
                    ) {
                        // set the 'current' flag
                        $day->current = TRUE;
                    }
                    $monthData->days[] = $day;
                }

                $calendar[$i] = $monthData;
            }

            $data = new AvailResponse();
            $data->data = $calendar;
        } catch (Exception $e) {

        }

        return $data->render();
    }
}
