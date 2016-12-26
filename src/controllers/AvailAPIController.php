<?php namespace Anzware\Avail;
/**
 * All in one API for Available Calendar
 *
 */

use Illuminate\Routing\Controller;
use Anzware\Avail\Helper\AvailResponse;
use Carbon\Carbon;
use Anzware\Avail\AVBooking;
use Anzware\Avail\AVCalendar;
use Anzware\Avail\AVState;
use Input;
use Validator;
use Exception;
use DB;

class AvailAPIController extends Controller
{
    /**
     * AVCalendar $AVCalendar
     */
    protected $AVCalendar = NULL;

    /**
     * AVState $AVState
     */
    protected $AVState = NULL;

    /**
     * string $dateError
     */
    protected $dateError = '';

    /**
     * Method for getting calendar items
     *
     * @param integer    `calendar_id`        (optional)    - the calendar ID
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function getCalendar()
    {
        $data = new AvailResponse();
        try {
            $calendarId = Input::get('calendar_id', NULL);

            $AVCalendar = new AVCalendar();

            // filter by state_id
            if (! empty($calendarId)) {
                $AVCalendar = $AVCalendar->where('calendar_id', $calendarId);
            }

            $AVCalendar = $AVCalendar->get();

            $data->data = $AVCalendar;
        } catch (Exception $e) {
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method for getting the calendar with data
     *
     * @param integer    `take`         (optional)    - the amount of months needed to be displayed (default: 3)
     * @param integer    `page`         (optional)    - indicate the pagination starting from current month (default: 1)
     * @param integer    `calendar_id`  (required)    - the calendar ID
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function getCalendarWithData()
    {
        $data = new AvailResponse();
        try {
            $take = Input::get('take', 3);
            $page = Input::get('page', 1);
            $calendarId = Input::get('calendar_id');

            $this->customValidation();

            $validator = Validator::make(
                array(
                    'take' => $take,
                    'page' => $page,
                    'calendar_id' => $calendarId
                ),
                array(
                    'take' => 'numeric',
                    'page' => 'numeric',
                    'calendar_id' => 'required|numeric|check_active_calendar_id'
                ),
                array(
                    'check_active_calendar_id' => 'Calendar not found'
                )
            );

            // Run the validation
            if ($validator->fails()) {
                $errorMessage = $validator->messages()->first();
                throw new Exception($errorMessage, 1);
            }

            $page--;
            $skip = ($take * $page);

            $calendarData = array();

            $currentDate = Carbon::now();

            $startingDateStartOfMonth = Carbon::now()->addMonths($skip)->startOfMonth();
            $endDateEndOfMonth = Carbon::now()->addMonths($skip)->addMonths($take-1)->endOfMonth();

            // get all bookings within taken months
            $bookings = AVBooking::with('state')
                ->leftJoin('avail_calendars', function($q) use($calendarId) {
                    $q->on('avail_calendars.calendar_id', '=', 'avail_bookings.calendar_id')
                        ->on('avail_calendars.status', '=', DB::raw("'active'"));
                })
                ->where('calendar_date', '>=', $startingDateStartOfMonth)
                ->where('calendar_date', '<=', $endDateEndOfMonth)
                ->get();

            for ($m = 0; $m < $take; $m++) {
                $month = Carbon::now()->addMonths($skip)->startOfMonth()->addMonths($m);
                $monthData = new \stdclass();
                $monthData->name = $month->format('F');
                $monthData->month = $month->month;
                $monthData->year = $month->year;
                $monthData->days = array();

                for ($d = 1; $d <= $month->daysInMonth; $d++) {
                    $day = new \stdclass();
                    $day->day = $d;
                    $day->month = $month->month;
                    $day->year = $month->year;
                    $day->stringDate = Carbon::now()->addMonths($skip)->startOfMonth()->addMonths($m)->addDays($d-1)->toDateString();
                    // check for today date
                    if ($currentDate->day === $d &&
                        $currentDate->month === $month->month &&
                        $currentDate->year === $month->year
                    ) {
                        // set the 'current' flag
                        $day->current = TRUE;
                    }

                    $day->state = 'Available';

                    // override state from bookings table
                    foreach ($bookings as $booking) {
                        if ($day->stringDate === $booking->calendar_date) {
                            $day->state = $booking->state->state;
                        }
                    }

                    $monthData->days[] = $day;
                }

                $calendarData[$m] = $monthData;
            }

            $this->AVCalendar->calendar_data = $calendarData;

            $data->data = $this->AVCalendar;
        } catch (Exception $e) {
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method for creating new calendar item
     *
     * @param string    `name`         (required)    - the calendar name
     * @param string    `status`       (required)    - calendar status ('active', 'inactive')
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function postNewCalendar()
    {
        $data = new AvailResponse();
        try {
            $calendarName = Input::get('calendar_name');
            $status = Input::get('status');

            DB::beginTransaction();

            $validator = Validator::make(
                array(
                    'name' => $calendarName,
                    'status' => $status
                ),
                array(
                    'name' => 'required',
                    'status' => 'required|in:active,inactive'
                )
            );

            // Run the validation
            if ($validator->fails()) {
                $errorMessage = $validator->messages()->first();
                throw new Exception($errorMessage, 1);
            }

            $this->AVCalendar = new AVCalendar();
            $this->AVCalendar->calendar_name = $calendarName;
            $this->AVCalendar->status = $status;
            $this->AVCalendar->save();

            DB::commit();

            $data->data = $this->AVCalendar;
        } catch (Exception $e) {
            DB::rollBack();
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method for updating an existing calendar item
     *
     * @param integer   `calendar_id`  (required)    - the calendar ID
     * @param string    `name`         (optional)    - the calendar name
     * @param string    `status`       (optional)    - calendar status ('active', 'inactive')
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function postUpdateCalendar()
    {
        $data = new AvailResponse();
        try {
            $calendarId = Input::get('calendar_id');
            $calendarName = Input::get('calendar_name');
            $status = Input::get('status');

            DB::beginTransaction();

            $this->customValidation();

            $validator = Validator::make(
                array(
                    'calendar_id' => $calendarId,
                    'status' => $status
                ),
                array(
                    'calendar_id' => 'required|numeric|check_calendar_id',
                    'status' => 'in:active,inactive'
                ),
                array(
                    'check_calendar_id' => 'Calendar not found'
                )
            );

            // Run the validation
            if ($validator->fails()) {
                $errorMessage = $validator->messages()->first();
                throw new Exception($errorMessage, 1);
            }

            if (! empty($calendarName)) {
                $this->AVCalendar->calendar_name = $calendarName;
            }
            if (! empty($status)) {
                $this->AVCalendar->status = $status;
            }

            $this->AVCalendar->save();

            DB::commit();

            $data->data = $this->AVCalendar;
        } catch (Exception $e) {
            DB::rollBack();
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method for getting state items
     *
     * @param integer    `state_id`        (optional)    - the state ID
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function getState()
    {
        $data = new AvailResponse();
        try {
            $stateId = Input::get('state_id', NULL);

            $AVState = new AVState();

            // filter by state_id
            if (! empty($stateId)) {
                $AVState = $AVState->where('state_id', $stateId);
            }

            $AVState = $AVState->get();

            $data->data = $AVState;
        } catch (Exception $e) {
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method for creating new state item
     *
     * @param string    `state`        (required)    - the name of the state
     * @param integer   `state_order`  (optional)    - the order of the state, for displaying purpose (default: 0)
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function postNewState()
    {
        $data = new AvailResponse();
        try {
            $state = Input::get('state');
            $stateOrder = Input::get('state_order');

            DB::beginTransaction();

            $validator = Validator::make(
                array(
                    'state' => $state,
                    'state_order' => $stateOrder
                ),
                array(
                    'state' => 'required|numeric',
                    'state_order' => 'numeric'
                )
            );

            // Run the validation
            if ($validator->fails()) {
                $errorMessage = $validator->messages()->first();
                throw new Exception($errorMessage, 1);
            }

            $AVState = new AVState();
            $AVState->state = $state;
            $AVState->state_order = $stateOrder;
            $AVState->save();

            DB::commit();

            $data->data = $AVState;
        } catch (Exception $e) {
            DB::rollBack();
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method for updating existing state item
     *
     * @param integer   `state_id`     (required)    - the state ID
     * @param string    `state`        (optional)    - the name of the state
     * @param integer   `state_order`  (optional)    - the order of the state, for displaying purpose
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function postUpdateState()
    {
        $data = new AvailResponse();
        try {
            $stateId = Input::get('state_id');
            $state = Input::get('state');
            $stateOrder = Input::get('state_order');

            DB::beginTransaction();

            $this->customValidation();

            $validator = Validator::make(
                array(
                    'state_id' => $stateId,
                    'state_order' => $stateOrder
                ),
                array(
                    'state_id' => 'required|numeric|check_state_id',
                    'state_order' => 'numeric'
                ),
                array(
                    'check_state_id' => 'State not found'
                )
            );

            // Run the validation
            if ($validator->fails()) {
                $errorMessage = $validator->messages()->first();
                throw new Exception($errorMessage, 1);
            }

            if (! empty($state)) {
                $this->AVState->state = $state;
            }
            if (! empty($stateOrder)) {
                $this->AVState->state_order = $stateOrder;
            }

            $this->AVState->save();

            DB::commit();

            $data->data = $this->AVState;
        } catch (Exception $e) {
            DB::rollBack();
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method for creating new booking item
     *
     * @param integer   `calendar_id`           (required)    - the calendar ID
     * @param integer   `state_id`              (required)    - the state ID
     * @param array     `dates`                 (required)    - selected dates
     * @param string    `external_booking_id`   (optional)    - external booking ID (your actual booking detail ID)
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function postNewBooking()
    {
        $data = new AvailResponse();
        try {
            $calendarId = Input::get('calendar_id');
            $stateId = Input::get('state_id');
            $dates = Input::get('dates');
            $externalBookingId = Input::get('external_booking_id', NULL);

            DB::beginTransaction();

            $this->customValidation();

            $validator = Validator::make(
                array(
                    'calendar_id' => $calendarId,
                    'state_id' => $stateId,
                    'dates' => $dates
                ),
                array(
                    'calendar_id' => 'required|numeric|check_active_calendar_id',
                    'state_id' => 'required|numeric|check_state_id',
                    'dates' => 'required'
                ),
                array(
                    'check_active_calendar_id' => 'Calendar not found',
                    'check_state_id' => 'State not found'
                )
            );

            // Run the validation
            if ($validator->fails()) {
                $errorMessage = $validator->messages()->first();
                throw new Exception($errorMessage, 1);
            }

            // validate dates
            $dates = (array) $dates;
            $booking = AVBooking::where('calendar_id', $calendarId)
                ->whereIn('calendar_date', $dates)
                ->first();

            if (is_object($booking)) {
                $errorMessage = sprintf('This date\'s (%s) state is already set', $booking->calendar_date);
                throw new Exception($errorMessage, 1);
            }

            $bookings = array();
            foreach ($dates as $date) {
                $validator = Validator::make(array('date' => $date), array('date' => 'date_format:Y-m-d'));
                if ($validator->fails()) {
                    $errorMessage = $validator->messages()->first();
                    throw new Exception($errorMessage, 1);
                }

                $AVBooking = new AVBooking();
                $AVBooking->calendar_id = $this->AVCalendar->calendar_id;
                $AVBooking->state_id = $this->AVState->state_id;
                $AVBooking->calendar_date = $date;
                $AVBooking->external_booking_id = $externalBookingId;
                $AVBooking->save();

                $bookings[] = $AVBooking;
            }

            // update calendar updated_at, if you want to display calendar last update
            $this->AVCalendar->touch();

            DB::commit();

            $data->data = $bookings;
        } catch (Exception $e) {
            DB::rollBack();
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method for deleting (releasing) dates from booking table
     *
     * All the dates will be deleted so the dates will be 'available'
     *
     * @param integer `calendar_id`      (required)    - the calendar ID
     * @param array   `dates`            (required)    - selected dates
     * @return Anzware\Avail\Helper\AvailResponse
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function postReleaseBooking()
    {
        $data = new AvailResponse();
        try {
            $calendarId = Input::get('calendar_id');
            $dates = Input::get('dates');
            $externalBookingId = Input::get('external_booking_id', NULL);

            DB::beginTransaction();

            $this->customValidation();

            $validator = Validator::make(
                array(
                    'calendar_id' => $calendarId,
                    'dates' => $dates
                ),
                array(
                    'calendar_id' => 'required|numeric|check_active_calendar_id',
                    'dates' => 'required'
                ),
                array(
                    'check_active_calendar_id' => 'Calendar not found',
                )
            );

            // Run the validation
            if ($validator->fails()) {
                $errorMessage = $validator->messages()->first();
                throw new Exception($errorMessage, 1);
            }

            // validate dates
            $dates = (array) $dates;
            $bookingCount = AVBooking::where('calendar_id', $calendarId)
                ->whereIn('calendar_date', $dates)
                ->count();

            if ($bookingCount !== count($dates)) {
                $errorMessage = 'Some dates are not found in booking table';
                throw new Exception($errorMessage, 1);
            }

            $bookings = array();
            foreach ($dates as $date) {
                $validator = Validator::make(array('date' => $date), array('date' => 'date_format:Y-m-d'));
                if ($validator->fails()) {
                    $errorMessage = $validator->messages()->first();
                    throw new Exception($errorMessage, 1);
                }

                $AVBooking = AVBooking::where('calendar_id', $calendarId)
                    ->where('calendar_date', $date)
                    ->first();

                $AVBooking->delete();
            }

            // update calendar updated_at, if you want to display calendar last update
            $this->AVCalendar->touch();

            DB::commit();

            $data->data = $bookings;
        } catch (Exception $e) {
            DB::rollBack();
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }

    /**
     * Method containing custom validations
     *
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function customValidation()
    {
        // check for calendar existance and status = active, should exists
        Validator::extend('check_active_calendar_id', function ($attribute, $value, $parameters) {
            $calendar = AVCalendar::where('status', 'active')
                ->where('calendar_id', $value)
                ->first();

            if (! is_object($calendar)) {
                return FALSE;
            }

            $this->AVCalendar = $calendar;

            return TRUE;
        });

        // check for calendar existance, should exists
        Validator::extend('check_calendar_id', function ($attribute, $value, $parameters) {
            $calendar = AVCalendar::where('calendar_id', $value)
                ->first();

            if (! is_object($calendar)) {
                return FALSE;
            }

            $this->AVCalendar = $calendar;

            return TRUE;
        });

        // check for state existance, should exists
        Validator::extend('check_state_id', function ($attribute, $value, $parameters) {
            $state = AVState::where('state_id', $value)
                ->first();

            if (! is_object($state)) {
                return FALSE;
            }

            $this->AVState = $state;

            return TRUE;
        });
    }
}
