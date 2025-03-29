@extends('admin.layouts.app')

@section ('content')
    @php $services  = []; @endphp
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{__("Cars Availability Calendar")}}</h1>
        </div>
        @include('admin.message')
        <div class="panel">
            <div class="panel-body">
                <div class="filter-div d-flex justify-content-between ">
                    <div class="col-left">
                        <form method="get" action="" class="filter-form filter-form-left d-flex flex-column flex-sm-row" role="search">
                            <input type="text" name="s" value="{{ Request()->s }}" placeholder="{{__('Search by name')}}" class="form-control">
                            <button class="btn-info btn btn-icon btn_search" type="submit">{{__('Search')}}</button>
                        </form>
                    </div>

                    <div class="col-right">
                        @if($rows->total() > 0)
                            <span class="count-string">{{ __("Showing :from - :to of :total cars",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()]) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @if(count($rows))
        <div class="panel">
            <div class="panel-title"><strong>{{__('Availability')}}</strong></div>
            <div class="panel-body no-padding" style="background: #f4f6f8;padding: 0px 15px;">
                <div class="row">
                    <div class="col-md-3" style="border-right: 1px solid #dee2e6;">
                        <ul class="nav nav-tabs  flex-column vertical-nav" id="items_tab"  role="tablist">
                            @foreach($rows as $k=>$item)
                                <li class="nav-item event-name ">
                                    <a class="nav-link" data-id="{{$item->id}}" data-toggle="tab" href="#calendar-{{$item->id}}" title="{{$item->title}}" >#{{$item->id}} - {{$item->title}}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-md-9" style="background: white;padding: 15px;">
                        <div class="d-flex mb-3 view-controller">
                            <!-- <button class="btn btn-secondary mr-2" id="calendarViewBtn">{{__('Calendar View')}}</button> -->
                            <!-- <button class="btn btn-primary" id="weeklyGridViewBtn">{{__('Weekly Grid View')}}</button> -->
                        </div>
                        
                        <!-- Calendar View -->
                        <div id="dates-calendar" class="dates-calendar" style="display: none;"></div>
                        
                        <!-- Weekly Grid View -->
                        <div id="weekly-grid" class="weekly-grid">
                            <div class="controls mb-3">
                                <button class="btn btn-secondary mr-2" id="prevWeekBtn">{{__('Previous Week')}}</button>
                                <span id="currentWeekDisplay" class="font-weight-bold mx-2">{{__('Current Week')}}</span>
                                <button class="btn btn-secondary ml-2" id="nextWeekBtn">{{__('Next Week')}}</button>
                            </div>
                            
                            <div class="grid-loading" id="grid-loading" style="display: none;">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">{{__('Loading...')}}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <table class="table table-bordered weekly-grid-table" id="weekly-grid-table">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">{{__('Hour')}}</th>
                                        <!-- Days will be dynamically populated by JavaScript -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Hours will be dynamically populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
            <div class="alert alert-warning">{{__("No cars found")}}</div>
        @endif
        <div class="d-flex justify-content-center">
            {{$rows->appends($request->query())->links()}}
        </div>
    </div>
    <div id="bravo_modal_calendar" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Date Information')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form class="row form_modal_calendar form-horizontal" novalidate onsubmit="return false">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label >{{__('Date')}}</label>
                                <input readonly type="text" class="form-control has-daterangepicker">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{__('Start Time')}}</label>
                                <input type="time" class="form-control" id="start_time">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{__('End Time')}}</label>
                                <input type="time" class="form-control" id="end_time">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label >{{__('Status')}}</label>
                                <br>
                                <label><input type="checkbox" id="active"> {{__('Available for booking?')}}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label >{{__('Price')}}</label>
                                <input type="number" id="price" class="form-control" value="200">
                            </div>
                        </div>
                        <!-- <div class="col-md-6">
                            <div class="form-group">
                                <label >{{__('Number')}}</label>
                                <input type="number" id="number" class="form-control" value="1">
                            </div>
                        </div> -->
                        <input type="hidden" id="id">
                        <input type="hidden" id="target_id">
                    </form>
                    <div id="response-message" style="display: none;">
                        <br>
                        <div id="response-message-inner" class="alert"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Close')}}</button>
                    <button type="button" class="btn btn-primary" id="save-form">{{__('Save changes')}}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="{{asset('libs/fullcalendar-4.2.0/core/main.css')}}">
    <link rel="stylesheet" href="{{asset('libs/fullcalendar-4.2.0/daygrid/main.css')}}">
    <link rel="stylesheet" href="{{asset('libs/fullcalendar-4.2.0/timegrid/main.css')}}">
    <link rel="stylesheet" href="{{asset('libs/daterange/daterangepicker.css')}}">

    <style>
        .event-name{
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }
        #dates-calendar .loading{
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        
        .weekly-grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .weekly-grid-table th, .weekly-grid-table td {
            text-align: center;
            padding: 8px;
            height: 50px;
        }
        
        .weekly-grid-table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .hour-column {
            background-color: #f8f9fa;
            font-weight: bold;
            position: sticky;
            left: 0;
            z-index: 5;
        }
        
        .available-cell {
            background-color: #d4edda;
            cursor: pointer;
        }
        
        .unavailable-cell {
            background-color: #808080;
            cursor: pointer;
        }
        
        .booked-cell {
            background-color: #ffe0e0;
            cursor: pointer;
            position: relative;
        }
        
        .fully-booked-cell {
            background-color: #f56565;
            color: white;
            cursor: pointer;
        }
        
        .booking-indicator {
            position: absolute;
            bottom: 3px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #721c24;
        }
        
        .default-cell {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        
        .cell-price {
            font-weight: bold;
        }
        
        .grid-loading {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        
        .current-day-header {
            background-color: #fcf8e3 !important;
        }
    </style>
@endpush

@push('js')
    <script src="{{asset('libs/daterange/moment.min.js')}}"></script>
    <script src="{{asset('libs/daterange/daterangepicker.min.js?_ver='.config('app.asset_version'))}}"></script>
    <script src="{{asset('libs/fullcalendar-4.2.0/core/main.js')}}"></script>
    <script src="{{asset('libs/fullcalendar-4.2.0/interaction/main.js')}}"></script>
    <script src="{{asset('libs/fullcalendar-4.2.0/daygrid/main.js')}}"></script>
    <script src="{{asset('libs/fullcalendar-4.2.0/timegrid/main.js')}}"></script>

    <script>
        // Global variables
        var calendarEl, calendar, lastId;
        var currentWeekStart = moment().startOf('week');
        var weekDays = [];
        var availabilityData = [];
        var isLoading = false;
        
        // Initialize when document is ready
        $(document).ready(function() {
            // Initialize daterangepicker
            $('.has-daterangepicker').daterangepicker({ 
                "singleDatePicker": true,
                "locale": {"format": bookingCore.date_format}
            });
            
            // Switch between views
            $('#calendarViewBtn').click(function() {
                $('#dates-calendar').show();
                $('#weekly-grid').hide();
                $(this).removeClass('btn-secondary').addClass('btn-primary');
                $('#weeklyGridViewBtn').removeClass('btn-primary').addClass('btn-secondary');
            });
            
            $('#weeklyGridViewBtn').click(function() {
                $('#dates-calendar').hide();
                $('#weekly-grid').show();
                $(this).removeClass('btn-secondary').addClass('btn-primary');
                $('#calendarViewBtn').removeClass('btn-primary').addClass('btn-secondary');
                
                if (lastId) {
                    loadWeeklyGridData(lastId);
                }
            });
            
            // Week navigation
            $('#prevWeekBtn').click(function() {
                currentWeekStart = moment(currentWeekStart).subtract(7, 'days');
                generateWeekDays();
                updateWeekDisplay();
                if (lastId) {
                    loadWeeklyGridData(lastId);
                }
            });
            
            $('#nextWeekBtn').click(function() {
                currentWeekStart = moment(currentWeekStart).add(7, 'days');
                generateWeekDays();
                updateWeekDisplay();
                if (lastId) {
                    loadWeeklyGridData(lastId);
                }
            });
            
            // Initialize weekly grid as default view
            generateWeekDays();
            renderWeeklyGridTable();
            updateWeekDisplay();
            
            // Use event delegation for cell clicks (better approach)
            $(document).on('click', '.weekly-grid-table td:not(.hour-column)', function() {
                var date = $(this).data('date');
                var hour = $(this).data('hour');
                handleCellClick(date, hour);
            });
            
            // Initialize form modal save button
            $('#save-form').click(function() {
                saveAvailabilityForm();
            });
            
            // Set weekly grid as default view
            $('#dates-calendar').hide();
            $('#weekly-grid').show();
            $('#calendarViewBtn').removeClass('btn-primary').addClass('btn-secondary');
            $('#weeklyGridViewBtn').removeClass('btn-secondary').addClass('btn-primary');
        });
        
        // Week days generation
        function generateWeekDays() {
            weekDays = [];
            for (let i = 0; i < 7; i++) {
                const day = moment(currentWeekStart).add(i, 'days');
                weekDays.push({
                    name: day.format('ddd'),
                    format: day.format('DD/MM/YYYY'),
                    date: day.format('YYYY-MM-DD')
                });
            }
            renderWeeklyGridTable();
        }
        
        // Update the week display text
        function updateWeekDisplay() {
            const startText = moment(currentWeekStart).format('DD MMM YYYY');
            const endText = moment(currentWeekStart).add(6, 'days').format('DD MMM YYYY');
            $('#currentWeekDisplay').text(startText + ' - ' + endText);
        }
        
        // Render weekly grid table
        function renderWeeklyGridTable() {
            // Clear the table
            $('#weekly-grid-table thead tr').html('<th style="width: 70px;">{{__("Hour")}}</th>');
            $('#weekly-grid-table tbody').html('');
            
            // Get today's date for highlighting
            const today = moment().format('YYYY-MM-DD');
            
            // Add days to header
            weekDays.forEach(function(day) {
                // Check if this day is today
                const isToday = (day.date === today);
                const headerClass = isToday ? 'current-day-header' : '';
                
                $('#weekly-grid-table thead tr').append('<th class="' + headerClass + '">' + day.name + '<br>' + day.format + '</th>');
            });
            
            // Add hours and cells
            for (let hour = 0; hour < 24; hour++) {
                const formattedHour = String(hour).padStart(2, '0') + ':00';
                let rowHtml = '<tr><td class="hour-column">' + formattedHour + '</td>';
                
                weekDays.forEach(function(day) {
                    const cellId = 'cell-' + day.date + '-' + hour;
                    rowHtml += '<td id="' + cellId + '" class="default-cell" data-date="' + day.date + '" data-hour="' + hour + '">200</td>';
                });
                
                rowHtml += '</tr>';
                $('#weekly-grid-table tbody').append(rowHtml);
            }
        }
        
        // Handle cell click
        function handleCellClick(date, hour) {
            console.log("Cell clicked:", date, hour);
            
            // Format time properly with leading zeros
            const startHour = String(hour).padStart(2, '0');
            const endHour = String(parseInt(hour) + 1).padStart(2, '0');
            
            // Prepare form data
            const cellId = 'cell-' + date + '-' + hour;
            const cell = $('#' + cellId);
            const cellData = getCellData(date, hour);
            
            // Nếu là booking event, hiển thị thông tin booking
            if (cellData && cellData.is_booking) {
                alert('This time slot is already booked (Booking #' + cellData.booking_id + ', Status: ' + cellData.status + ')');
                return;
            }
            
            // Reset form
            $('#id').val('');
            $('#target_id').val(lastId);
            $('#price').val('200');
            $('#number').val('1');
            $('#active').prop('checked', true);
            $('#start_time').val(startHour + ':00');
            $('#end_time').val(endHour + ':00');
            $('#response-message').hide();
            
            // If cell has data, populate the form
            if (cellData) {
                $('#id').val(cellData.id);
                $('#price').val(cellData.price);
                $('#number').val(cellData.number);
                $('#active').prop('checked', cellData.active == 1);
                
                // Extract time from timestamp and set in form
                const fullStartDateTime = moment(cellData.start);
                const fullEndDateTime = moment(cellData.end);
                
                if (date === fullStartDateTime.format('YYYY-MM-DD')) {
                    $('#start_time').val(fullStartDateTime.format('HH:mm'));
                }
                
                if (date === fullEndDateTime.format('YYYY-MM-DD')) {
                    $('#end_time').val(fullEndDateTime.format('HH:mm'));
                }
                
                // Set date range picker to match the full event
                var drp = $('.has-daterangepicker').data('daterangepicker');
                if (drp) {
                    drp.setStartDate(fullStartDateTime.format(bookingCore.date_format));
                    drp.setEndDate(fullEndDateTime.format(bookingCore.date_format));
                }
                
                if (cellData.total_booked > 0) {
                    const infoText = cellData.total_booked + ' out of ' + cellData.number + ' cars booked. ' + cellData.remaining + ' cars remaining.';
                    $('#response-message').show();
                    $('#response-message-inner').text(infoText).removeClass('alert-danger').addClass('alert-info');
                }
            } else {
                var drp = $('.has-daterangepicker').data('daterangepicker');
                if (drp) {
                    drp.setStartDate(moment(date).format(bookingCore.date_format));
                    drp.setEndDate(moment(date).format(bookingCore.date_format));
                }
            }
            
            // Show modal
            $('#bravo_modal_calendar').modal({
                show: true,
                backdrop: 'static',
                keyboard: false
            });
        }
        
        // Save availability form
        function saveAvailabilityForm() {
            // Get form data
            const id = $('#id').val();
            const target_id = $('#target_id').val();
            const start_date = $('.has-daterangepicker').data('daterangepicker').startDate.format('YYYY-MM-DD');
            const end_date = $('.has-daterangepicker').data('daterangepicker').endDate.format('YYYY-MM-DD');
            const start_time = $('#start_time').val();
            const end_time = $('#end_time').val();
            const price = $('#price').val();
            const number = $('#number').val();
            const active = $('#active').is(':checked') ? 1 : 0;
            
            // Validate form
            if (!start_date || !end_date || !start_time || !end_time) {
                showResponseMessage(false, '{{__("Please fill all required fields")}}');
                return;
            }
            
            // Show loading
            $('#save-form').prop('disabled', true);
            $('#response-message').hide();
            
            // Send AJAX request
            $.ajax({
                url: '{{route("car.admin.availability.store")}}',
                method: 'POST',
                data: {
                    id: id,
                    target_id: target_id,
                    start_date: start_date,
                    end_date: end_date,
                    start_time: start_time,
                    end_time: end_time,
                    price: price,
                    number: number,
                    active: active,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(response) {
                    showResponseMessage(response.status, response.message);
                    
                    if (response.status) {
                        // Reload data after successful save
                        setTimeout(function() {
                            $('#bravo_modal_calendar').modal('hide');
                            if (calendar) {
                                calendar.refetchEvents();
                            }
                            loadWeeklyGridData(target_id);
                        }, 1000);
                    }
                    
                    $('#save-form').prop('disabled', false);
                },
                error: function(error) {
                    console.error(error);
                    showResponseMessage(false, '{{__("An error occurred. Please try again.")}}');
                    $('#save-form').prop('disabled', false);
                }
            });
        }
        
        // Show response message
        function showResponseMessage(status, message) {
            $('#response-message').show();
            $('#response-message-inner').text(message);
            
            if (status) {
                $('#response-message-inner').removeClass('alert-danger').addClass('alert-success');
            } else {
                $('#response-message-inner').removeClass('alert-success').addClass('alert-danger');
            }
        }
        
        // Load weekly grid data
        function loadWeeklyGridData(carId) {
            isLoading = true;
            $('#grid-loading').show();
            
            const startDate = weekDays[0].date;
            const endDate = moment(weekDays[6].date).add(1, 'days').format('YYYY-MM-DD');
            
            // Reset all cells to default
            $('.weekly-grid-table td:not(.hour-column)').each(function() {
                $(this).removeClass('available-cell unavailable-cell booked-cell fully-booked-cell').addClass('default-cell');
                $(this).text('200');
                $(this).find('.booking-indicator').remove();
            });
            
            $.ajax({
                url: "{{route('car.admin.availability.loadDates')}}",
                data: {
                    id: carId,
                    start: startDate,
                    end: endDate
                },
                dataType: 'json',
                success: function(response) {
                    availabilityData = response;
                    
                    // Update grid cells with data
                    availabilityData.forEach(function(item) {
                        const itemStart = moment(item.start);
                        const itemEnd = moment(item.end);
                        
                        const daysToProcess = [];
                        
                        let currentDay = moment(itemStart).startOf('day');
                        const lastDay = moment(itemEnd).startOf('day');
                        
                        while (currentDay <= lastDay) {
                            daysToProcess.push(currentDay.format('YYYY-MM-DD'));
                            currentDay.add(1, 'days');
                        }
                        
                        daysToProcess.forEach(function(date) {
                            let startHour = 0;
                            let endHour = 23;
                            
                            if (date === itemStart.format('YYYY-MM-DD')) {
                                startHour = parseInt(itemStart.format('HH'));
                            }
                            
                            if (date === itemEnd.format('YYYY-MM-DD')) {
                                endHour = parseInt(itemEnd.format('HH')) - 1; 
                                if (endHour < 0) endHour = 0; 
                            }
                            
                            for (let hour = startHour; hour <= endHour; hour++) {
                                const cellId = 'cell-' + date + '-' + hour;
                                const cell = $('#' + cellId);
                                
                                if (cell.length) {
                                    if (item.extendedProps.is_booking) {
                                        const bookingPrice = item.extendedProps.price || 200;
                                        const bookingNumber = item.extendedProps.number || 1;
                                        
                                        cell.html(bookingPrice + ' (' + bookingNumber + ')');
                                        cell.removeClass('default-cell available-cell unavailable-cell').addClass('booked-cell');
                                        continue;
                                    }
                                    
                                    const price = item.extendedProps.price || 200;
                                    const number = item.extendedProps.number || 1;
                                    // let cellContent = price + ' (' + number + ')';
                                    let cellContent = price;
                                    
                                    if (item.extendedProps.total_booked > 0) {
                                        const indicator = `<div class="booking-indicator">${item.extendedProps.total_booked}/${number} booked</div>`;
                                        cell.html(cellContent + indicator);
                                    } else {
                                        cell.text(cellContent);
                                    }
                                    
                                    if (item.extendedProps.is_fully_booked) {
                                        cell.removeClass('default-cell available-cell unavailable-cell').addClass('fully-booked-cell');
                                    } else if (item.extendedProps.active == 1) {
                                        cell.removeClass('default-cell unavailable-cell fully-booked-cell').addClass('available-cell');
                                    } else {
                                        cell.removeClass('default-cell available-cell fully-booked-cell').addClass('unavailable-cell');
                                    }
                                }
                            }
                        });
                    });
                    
                    isLoading = false;
                    $('#grid-loading').hide();
                },
                error: function(error) {
                    console.error(error);
                    isLoading = false;
                    $('#grid-loading').hide();
                }
            });
        }
        
        // Get cell data from availability data
        function getCellData(date, hour) {
            const startHour = String(hour).padStart(2, '0') + ':00:00';
            const targetDateTime = date + ' ' + startHour;
            const targetTime = moment(targetDateTime);
            const targetTimeEnd = moment(targetDateTime).add(59, 'minutes').add(59, 'seconds'); 
            const matchingEvents = availabilityData.filter(item => {
                const itemStart = moment(item.start);
                const itemEnd = moment(item.end);
                
                
                return (
                    (targetTime >= itemStart && targetTime < itemEnd) || 
                    (targetTimeEnd > itemStart && targetTimeEnd <= itemEnd) ||
                    (targetTime <= itemStart && targetTimeEnd >= itemEnd)
                );
            });
            
            for (const event of matchingEvents) {
                if (event.extendedProps.is_booking) {
                    return {
                        is_booking: true,
                        booking_id: event.extendedProps.booking_id,
                        status: event.extendedProps.status,
                        number: event.extendedProps.number,
                        start: event.start,
                        end: event.end,
                        pickup_time: event.extendedProps.pickup_time,
                        dropoff_time: event.extendedProps.dropoff_time
                    };
                }
            }
            
            if (matchingEvents.length > 0) {
                const event = matchingEvents[0];
                return {
                    id: event.id,
                    price: event.extendedProps.price,
                    active: event.extendedProps.active,
                    number: event.extendedProps.number,
                    start: event.start,
                    end: event.end,
                    total_booked: event.extendedProps.total_booked,
                    remaining: event.extendedProps.remaining,
                    is_fully_booked: event.extendedProps.is_fully_booked
                };
            }
            
            return null;
        }

        // Initialize calendar
        $('#items_tab').on('show.bs.tab', function (e) {
			calendarEl = document.getElementById('dates-calendar');
			lastId = $(e.target).data('id');
            
            if (calendar) {
				calendar.destroy();
            }
            
			calendar = new FullCalendar.Calendar(calendarEl, {
                buttonText:{
                    today:  '{{ __('Today') }}',
                    month: '{{ __('Month') }}',
                    week: '{{ __('Week') }}',
                    day: '{{ __('Day') }}'
                },
                plugins: [ 'dayGrid' ,'interaction', 'timeGrid'],
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
				selectable: true,
				selectMirror: false,
                allDay: false,
				editable: false,
				eventLimit: true,
				defaultView: 'dayGridMonth',
                slotDuration: '01:00:00',
                slotLabelInterval: '01:00:00',
                slotLabelFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    omitZeroMinute: false
                },
                firstDay: daterangepickerLocale.first_day_of_week,
				events:{
                    	url:"{{route('car.admin.availability.loadDates')}}",
						extraParams:{
                        id: lastId,
                        }
                },
                loading: function (isLoading) {
                    if (!isLoading) {
						$(calendarEl).removeClass('loading');
                    } else {
						$(calendarEl).addClass('loading');
					}
				},
				select: function(arg) {
                    // Reset form
                    $('#id').val('');
                    $('#target_id').val(lastId);
                    $('#price').val('200');
                    $('#number').val('1');
                    $('#active').prop('checked', true);
                    $('#start_time').val('09:00');
                    $('#end_time').val('10:00');
                    $('#response-message').hide();
                    
                    // Set date in daterangepicker
                    var drp = $('.has-daterangepicker').data('daterangepicker');
                    drp.setStartDate(moment(arg.start).format(bookingCore.date_format));
                    drp.setEndDate(moment(arg.end).format(bookingCore.date_format));
                    
                    // Show modal
                    $('#bravo_modal_calendar').modal('show');
                },
                eventClick: function (info) {
                    // Reset form
                    $('#id').val(info.event.extendedProps.id);
                    $('#target_id').val(lastId);
                    $('#price').val(info.event.extendedProps.price);
                    $('#number').val(info.event.extendedProps.number);
                    $('#active').prop('checked', info.event.extendedProps.active == 1);
                    $('#response-message').hide();
                    
                    // Extract time from timestamp
                    const startTime = moment(info.event.start).format('HH:mm');
                    const endTime = moment(info.event.end || moment(info.event.start).add(1, 'hours')).format('HH:mm');
                    
                    $('#start_time').val(startTime);
                    $('#end_time').val(endTime);
                    
                    // Set date in daterangepicker
                    var drp = $('.has-daterangepicker').data('daterangepicker');
                    drp.setStartDate(moment(info.event.start).format(bookingCore.date_format));
                    drp.setEndDate(moment(info.event.end || info.event.start).format(bookingCore.date_format));
                    
                    // Show modal
                    $('#bravo_modal_calendar').modal('show');
                },
                eventRender: function (info) {
                    $(info.el).find('.fc-title').html(info.event.title);
                }
			});
            
			calendar.render();
            
            // Load weekly grid data
            loadWeeklyGridData(lastId);
		});

        // Trigger first car tab
        $('.event-name:first-child a').trigger('click');
    </script>
@endpush
