<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::unprepared('
            DROP TRIGGER IF EXISTS after_booking_update;
            
            CREATE TRIGGER after_booking_update 
            AFTER UPDATE ON bravo_bookings 
            FOR EACH ROW 
            BEGIN 
                IF NEW.status = "completed" AND (OLD.status != "completed" OR OLD.status IS NULL) THEN 
                    UPDATE bravo_cars 
                    SET book_complete = book_complete + 1 
                    WHERE id = NEW.object_id AND NEW.object_model = "car"; 
                ELSEIF OLD.status = "completed" AND NEW.status != "completed" THEN 
                    UPDATE bravo_cars 
                    SET book_complete = GREATEST(book_complete - 1, 0) 
                    WHERE id = NEW.object_id AND NEW.object_model = "car"; 
                END IF; 
            END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_booking_update');
    }
};
