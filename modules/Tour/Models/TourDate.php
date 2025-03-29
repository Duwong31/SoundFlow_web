<?php
namespace Modules\Tour\Models;

use App\BaseModel;
use ICal\ICal;
use Modules\Booking\Models\Booking;

class TourDate extends BaseModel
{
    protected $table = 'bravo_tour_dates';
    protected $tourMetaClass;
    protected $bookingClass;

    protected $casts = [
        'person_types'=>'array'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->tourMetaClass = TourMeta::class;
        $this->bookingClass = Booking::class;
    }

    public static function getDatesInRanges($date,$target_id){
        return static::query()->where([
            ['start_date','>=',$date],
            ['end_date','<=',$date],
            ['target_id','=',$target_id],
        ])->first();
    }
    public function saveMeta(\Illuminate\Http\Request $request)
    {
        $locale = $request->input('lang');
        $meta = $this->tourMetaClass::where('tour_date_id', $this->id)->first();
        if (!$meta) {
            $meta = new $this->tourMetaClass();
            $meta->tour_date_id = $this->id;
        }
        return $meta->saveMetaOriginOrTranslation($request->input() , $locale);
    }

    public function loadDates($start_date , $end_date , $tourObject , $is_single = true, $only_for_specific_date = false)
    {
        // initData
        $lang = app()->getLocale();
        $allDates = [];
        if($only_for_specific_date == false){
            $period = periodDate($start_date,$end_date);
            foreach ($period as $dt){
                $i = $dt->getTimestamp();
                $date = [
                    'id'           => rand(0, 999),
                    'active'       => 0,
                    'price'        => (!empty($tourObject->sale_price) and $tourObject->sale_price > 0 and $tourObject->sale_price < $tourObject->price) ? $tourObject->sale_price : $tourObject->price,
                    'is_default'   => true,
                    'textColor'    => '#2791fe',
                ];
                if (!$is_single) {
                    $date['price_html'] = format_money_main($date['price']);
                } else {
                    $date['price_html'] = format_money($date['price']);
                }
                $date['max_guests'] = $tourObject->max_people;
                $date['title_origin'] = $date['price_html'];
                $date['title'] = $date['event'] = $date['price_html'] . "<br>". __('Max guests: ') . $tourObject->max_people;
                $date['start'] = $date['end'] = date('Y-m-d', $i);
                if ($tourObject->default_state) {
                    $date['active'] = 1;
                } else {
                    $date['title'] = $date['event'] = __('Blocked');
                    $date['backgroundColor'] = 'orange';
                    $date['borderColor'] = '#fe2727';
                    $date['classNames'] = ['blocked-event'];
                    $date['textColor'] = '#fe2727';
                }
                if ($is_single) {
                    if (empty(!$tourObject->max_people) and $tourObject->max_people < 1) {
                        $date['active'] = 0;
                    }
                }
                if (!empty($tourObject->meta->enable_person_types) and $tourObject->meta->enable_person_types == 1) {
                    $date['person_types'] = $tourObject->meta->person_types;
                    if (!empty($date['person_types'])) {
                        $c_title = "";
                        foreach ($date['person_types'] as &$person) {
                            $person['name'] = !empty($person['name_' . $lang])?$person['name_' . $lang]:$person['name'];
                            if (!$is_single) {
                                $c_title .= $person['name'] . ": " . format_money_main($person['price']) . "<br>";
                                //for single
                                $person['display_price'] = format_money_main($person['price']);
                            } else {
                                $c_title .= $person['name'] . ": " . format_money($person['price']) . "<br>";
                                //for single
                                $person['display_price'] = format_money($person['price']);
                            }
                            $person['number'] = $person['min'] ?? 0;
                        }
                        $date['title_origin'] = $c_title;
                        $c_title .= __('Max guests: ').$date['max_guests'];
                        $date['title'] = $date['event'] = $c_title;
                    }
                }
                // Open Hours
                if (!empty($tourObject->meta->enable_open_hours) and $tourObject->meta->enable_open_hours == 1) {
                    $open_hours = $tourObject->meta->open_hours;
                    $nDate = date('N', $i);
                    if (!isset($open_hours[$nDate]) or empty($open_hours[$nDate]['enable'])) {
                        $date['active'] = 0;
                    }
                }
                $allDates[date('Y-m-d', $i)] = $date;
            }
        }

        //Check Calendar
        $query = parent::query();
        $query->where('target_id', $tourObject->id);
        $query->where('start_date', '>=', $start_date);
        $query->where('end_date', '<=', $end_date);
        $rows = $query->take(50)->get();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $row->start = date('Y-m-d', strtotime($row->start_date));
                $row->end = date('Y-m-d', strtotime($row->start_date));
                $row->textColor = '#2791fe';
                $price = $row->price;
                if (empty($price)) {
                    $price = (!empty($tourObject->sale_price) and $tourObject->sale_price > 0 and $tourObject->sale_price < $tourObject->price) ? $tourObject->sale_price : $tourObject->price;
                }
                if (!$is_single) {
                    $row->title_origin = format_money_main($price);
                    $row->title = $row->event = format_money_main($price). "<br>". __('Max guests: ') . $row->max_guests;;
                } else {
                    $row->title_origin = format_money($price);
                    $row->title = $row->event = format_money($price). "<br>". __('Max guests: ') . $row->max_guests;;
                }
                $row->price = $price;
                if ($is_single) {
                    if (empty(!$row->max_guests) and $row->max_guests < 1) {
                        $row->active = 0;
                    }
                }
                $list_person_types = null;
                if (!empty($tourObject->meta->enable_person_types) and $tourObject->meta->enable_person_types == 1) {
                    $list_person_types = $tourObject->meta->person_types;
                    $date_person_types = is_array($row->person_types) ? $row->person_types : [];
                    if (!empty($list_person_types) and is_array($list_person_types)) {
                        $c_title = "";
                        foreach ($list_person_types as $k => &$person) {
                            $person['name'] = !empty($person['name_' . $lang])?$person['name_' . $lang]:$person['name'];
                            $person['price'] = $date_person_types[$k]['price'] ?? $person['price'];
                            $person['max'] = $date_person_types[$k]['max'] ?? $person['max'];
                            $person['min'] = $date_person_types[$k]['min'] ?? $person['min'];
                            if (!$is_single) {
                                $c_title .= $person['name'] . ": " . format_money_main($person['price']) . "<br>";
                                //for single
                                $person['display_price'] = format_money_main($person['price']);
                            } else {
                                $c_title .= $person['name'] . ": " . format_money($person['price']) . "<br>";
                                //for single
                                $person['display_price'] = format_money($person['price']);
                            }
                            $person['number'] = $person['min'] ?? 0;
                        }
                        $row->title_origin = $c_title;
                        $c_title .= __('Max guests: ').$row->max_guests;
                        $row->title = $c_title;
                    }
                }
                $row->person_types = $list_person_types;
                if (!$row->active) {
                    $row->title = $row->event = __('Blocked');
                    $row->backgroundColor = '#fe2727';
                    $row->classNames = ['blocked-event'];
                    $row->textColor = '#fe2727';
                    $row->active = 0;
                } else {
                    $row->classNames = ['active-event'];
                    $row->active = 1;
                    // Open Hours
                    if (!empty($tourObject->meta->enable_open_hours) and $tourObject->meta->enable_open_hours == 1) {
                        $open_hours = $tourObject->meta->open_hours;
                        $nDate = date('N', strtotime($row->start_date));
                        if (!isset($open_hours[$nDate]) or empty($open_hours[$nDate]['enable'])) {
                            $row->active = 0;
                        }
                    }
                }
                $allDates[date('Y-m-d', strtotime($row->start_date))] = $row->toArray();
            }
        }

        //check BookingData
        $bookings = $this->bookingClass::getBookingInRanges($tourObject->id, $tourObject->type, $start_date, $end_date);
        if (!empty($bookings)) {
            foreach ($bookings as $booking) {
                $period = periodDate($booking->start_date,$booking->end_date,false);
                foreach ($period as $dt){
                    $i = $dt->getTimestamp();
                    if (isset($allDates[date('Y-m-d', $i)])) {
                        $total_guests_booking = $booking->total_guests;
                        $max_guests = $allDates[date('Y-m-d', $i)]['max_guests'];
                        if ($total_guests_booking >= $max_guests) {
                            $allDates[date('Y-m-d', $i)]['active'] = 0;
                            $allDates[date('Y-m-d', $i)]['event'] = __('Full Book');
                            $allDates[date('Y-m-d', $i)]['title'] = __('Full Book');
                            $allDates[date('Y-m-d', $i)]['classNames'] = ['full-book-event'];
                        } else {
                            if ($is_single) {
                                $c_title = $allDates[date('Y-m-d', $i)]['title_origin'] . "<br>". __('Max guests: ').( $max_guests - $total_guests_booking );
                                $allDates[date('Y-m-d', $i)]['title']  = $c_title;
                            }
                        }
                    }
                }
            }
        }

        //check iCal
        if (!empty($tourObject->ical_import_url)) {
            $startDate = $start_date;
            $endDate = $end_date;
            $timezone = setting_item('site_timezone', config('app.timezone'));
            try {
                $icalevents = new Ical($tourObject->ical_import_url, [
                    'defaultTimeZone' => $timezone
                ]);
                $eventRange = $icalevents->eventsFromRange($startDate, $endDate);
                if (!empty($eventRange)) {
                    foreach ($eventRange as $item => $value) {
                        if (!empty($date = $value->dtstart_array[2])) {
                            $max_guests = $allDates[date('Y-m-d', $date)]['max_guests'] - 1;
                            if ($max_guests == 0) {
                                $allDates[date('Y-m-d', $date)]['active'] = 0;
                                $allDates[date('Y-m-d', $date)]['event'] = __('Full Book');
                                $allDates[date('Y-m-d', $date)]['title'] = __('Full Book');
                                $allDates[date('Y-m-d', $date)]['classNames'] = ['full-book-event'];
                            }
                            if ($is_single) {
                                $allDates[date('Y-m-d', $date)]['max_guests'] = $max_guests;
                            }
                        }
                    }
                }
            } catch (\Exception $exception) {
                return $this->sendError($exception->getMessage());
            }
        }
        $data = array_values($allDates);
        return $data;
    }
}
